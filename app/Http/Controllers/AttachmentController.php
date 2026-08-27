<?php

namespace App\Http\Controllers;

use App\Models\LeaveAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AttachmentController extends Controller
{
    public function preview(Request $request, LeaveAttachment $attachment)
    {
        $this->authorizeAccess($request, $attachment);

        if (!Storage::disk('public')->exists($attachment->file_path)) {
            abort(404, 'Attachment not found.');
        }

        $absolutePath = Storage::disk('public')->path($attachment->file_path);

        // Use stored mime_type if available; otherwise detect using PHP
        $mime = $attachment->mime_type ?: (function () use ($absolutePath) {
            $detected = @mime_content_type($absolutePath);
            return $detected ?: 'application/octet-stream';
        })();

        return response()->file($absolutePath, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="'.$attachment->original_name.'"',
        ]);
    }

    public function download(Request $request, LeaveAttachment $attachment)
    {
        $this->authorizeAccess($request, $attachment);

        if (!Storage::disk('public')->exists($attachment->file_path)) {
            abort(404, 'Attachment not found.');
        }

        $absolutePath = Storage::disk('public')->path($attachment->file_path);

        // Force download only when user clicks the Download button
        return response()->download($absolutePath, $attachment->original_name);
    }

    private function authorizeAccess(Request $request, LeaveAttachment $attachment): void
    {
        $user = $request->user()->loadMissing(['roles', 'employee']);

        // Relationship must exist in model:
        // $attachment->leaveApplication()
        $leave = $attachment->leaveApplication()->first();
        if (!$leave) abort(404);

        $hasRole = function (string $key) use ($user) {
            if (method_exists($user, 'hasRole')) return $user->hasRole($key);
            return $user->roles->contains('key', $key);
        };

        $isApprover =
            $hasRole('approver_division_chief') ||
            $hasRole('approver_personnel') ||
            $hasRole('approver_chief_personnel') ||
            $hasRole('approver_chief_admin') ||
            $hasRole('approver_ard_ms');

        $isOfficeAdmin = $hasRole('office_admin');
        $isSuper = $hasRole('super_admin');

        // Owner employee only
        $isOwnerEmployee = $user->employee && ((int)$leave->employee_id === (int)$user->employee->id);

        if (!($isOwnerEmployee || $isApprover || $isOfficeAdmin || $isSuper)) {
            abort(403, 'Not authorized to view this attachment.');
        }
    }
}
