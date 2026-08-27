<?php

namespace App\Http\Controllers\Approver;

use App\Http\Controllers\Controller;
use App\Models\{ApprovalStep, LeaveApplication, LeaveApproval, LeaveCredit, Role};
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use App\Mail\LeaveStatusUpdated;

class LeaveActionController extends Controller
{
    public function show(Request $request, int $id)
    {
        $leave = LeaveApplication::with([
            'employee.user','employee.division','leaveType','office',
            'approvals.approver',
            'attachments'
        ])->findOrFail($id);

        // 1. Fetch Credits & History
        $credits = LeaveCredit::where('employee_id', $leave->employee_id)->first();

        $history = LeaveApplication::with('leaveType')
            ->where('employee_id', $leave->employee_id)
            ->where('id', '!=', $id)
            ->latest('date_filed')
            ->take(5)
            ->get();

        // 2. Build Timeline
        $steps = ApprovalStep::where('office_id', $leave->office_id)->orderBy('step_order')->get();

        // Fetch readable Role Names from 'roles' table
        $roleNames = Role::whereIn('key', $steps->pluck('role_key'))
                        ->pluck('name', 'key');

        $approvalsByStep = $leave->approvals->keyBy('step_order');

        $timeline = $steps->map(function ($step) use ($leave, $approvalsByStep, $roleNames) {
            $ap = $approvalsByStep->get($step->step_order);

            $title = $roleNames[$step->role_key] ?? $step->name ?? $step->role_key;

            if ($ap) {
                return [
                    'step_order' => $step->step_order,
                    'role_key'   => $step->role_key,
                    'title'      => $title,
                    'state'      => $ap->action,
                    'remarks'    => $ap->remarks,
                    'actor'      => $ap->approver->first_name . ' ' . $ap->approver->last_name,
                    'acted_at'   => $ap->acted_at ?? $ap->created_at,
                ];
            }

            $isCurrent = ((int)$leave->current_step_order === (int)$step->step_order);

            return [
                'step_order' => $step->step_order,
                'role_key'   => $step->role_key,
                'title'      => $title,
                'state'      => $isCurrent ? 'current' : 'upcoming',
                'remarks'    => null,
                'actor'      => null,
                'acted_at'   => null,
            ];
        });

        // 3. CHECK PERMISSION
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $user->loadMissing('roles', 'employee');

        $canAction = false;

        if ($leave->status === 'pending' && $user->employee?->office_id === $leave->office_id) {
            $currentStepDef = $steps->firstWhere('step_order', $leave->current_step_order);

            if ($currentStepDef && $user->hasRole($currentStepDef->role_key)) {
                if ($currentStepDef->step_order === 1 && $user->hasRole('approver_division_chief')) {
                    if ($leave->employee->division_id === $user->employee->division_id) {
                        $canAction = true;
                    }
                } else {
                    $canAction = true;
                }
            }
        }

        return view('approver.review', compact('leave', 'timeline', 'credits', 'history', 'canAction'));
    }

    public function action(Request $request, int $id)
    {
        $action = $request->input('action');

        $request->validate([
            'action' => 'required|in:approved,returned,disapproved',
            'remarks' => 'nullable|string|max:2000',
            'signature_mode' => 'required_if:action,approved|in:saved,upload,draw',
            'signature_file' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'signature_data' => 'nullable|string',
        ]);

        /** @var \App\Models\User $user */
        $user = $request->user();
        $user->loadMissing('roles', 'employee');

        $leave = LeaveApplication::with('employee.user')->lockForUpdate()->findOrFail($id);

        $this->authorizeAction($user, $leave);

        $action = $request->input('action');
        $remarks = $request->input('remarks');

        if (in_array($action, ['returned', 'disapproved'], true) && blank($remarks)) {
            return back()->withErrors(['remarks' => 'Remarks are required when returning or disapproving.']);
        }

        if ($action === 'approved') {
            try {
                $this->storeApproverSignature($request, $user);
            } catch (\InvalidArgumentException $e) {
                return back()->withErrors(['signature' => $e->getMessage()]);
            }
        }

        DB::transaction(function () use ($request, $leave, $user, $action, $remarks) {

            // Personnel Leave Credits Certification Details
            if ($user->hasRole('approver_personnel')) {
                $details = $leave->details_json ?? [];

                $fieldsToSave = [
                    'credits_as_of',
                    'vl_earned', 'vl_less', 'vl_balance',
                    'sl_earned', 'sl_less', 'sl_balance'
                ];

                foreach($fieldsToSave as $field) {
                    if ($request->has($field)) {
                        $details[$field] = $request->input($field);
                    }
                }

                $leave->details_json = $details;
            }

            // 1. Log the Approval Action
            LeaveApproval::create([
                'leave_application_id' => $leave->id,
                'step_order' => $leave->current_step_order,
                'approver_user_id' => $user->id,
                'action' => $action,
                'remarks' => $remarks,
                'acted_at' => Carbon::now(),
            ]);

            // 2. Update Leave Application Status
            if ($action === 'approved') {
                $maxStep = ApprovalStep::where('office_id', $leave->office_id)->max('step_order');

                if ($leave->current_step_order < (int)$maxStep) {
                    $leave->current_step_order += 1;
                    $leave->status = 'pending';
                } else {
                    $leave->status = 'approved';
                }
            } elseif ($action === 'returned') {
                $leave->status = 'returned';
            } elseif ($action === 'disapproved') {
                $leave->status = 'disapproved';
            }

            $leave->save();
        });

        // =========================================================================
        // 5. STANDARD PDF GENERATION (Replaces the PNPKI Hybrid Lock)
        // =========================================================================
        if ($action === 'approved') {
            try {
                // Generate the visual Form 6 using DomPDF
                $leave->refresh();
                $domPdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.form6_leave', ['leave' => $leave])->setPaper('a4', 'portrait');

                // Save the PDF permanently to the locked_leaves directory
                $lockedDir = storage_path('app/public/locked_leaves');
                if (!file_exists($lockedDir)) {
                    mkdir($lockedDir, 0755, true);
                }

                $lockedPdfPath = $lockedDir . '/CS_Form_6_' . $leave->id . '.pdf';
                $domPdf->save($lockedPdfPath);

            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("PDF SAVING CRASHED: " . $e->getMessage());
            }
        }

        // 3. SEND EMAIL NOTIFICATION TO APPLICANT
        if ($leave->employee && $leave->employee->user) {
            try {
                Mail::to($leave->employee->user->email)->send(
                    new LeaveStatusUpdated($leave, $remarks, $user->first_name . ' ' . $user->last_name)
                );
                $actionText = ucfirst($action);

                if ($action === 'approved' && $leave->status === 'pending') {
                    $actionText = 'Endorsed (Step ' . $leave->current_step_order . ')';
                }

                $leave->employee->user->notify(new \App\Notifications\SystemLeaveNotification(
                    $leave,
                    "Your application was {$actionText} by {$user->first_name}.",
                    route('employee.leaves.show', $leave->id)
                ));
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Failed to send leave email: ' . $e->getMessage());
            }
        }

        // 4. NOTIFY THE NEXT APPROVER IN LINE
        if ($action === 'approved' && $leave->status === 'pending') {
            try {
                // Find what the new current step is
                $nextStep = ApprovalStep::where('office_id', $leave->office_id)
                    ->where('step_order', $leave->current_step_order)
                    ->first();

                if ($nextStep) {
                    $nextApproversQuery = \App\Models\User::whereHas('roles', function($q) use ($nextStep) {
                        $q->where('key', $nextStep->role_key);
                    })->whereHas('employee', function($q) use ($leave) {
                        $q->where('office_id', $leave->office_id);
                    });

                    // If the next step is a Division Chief, only alert their specific Division Chief
                    if ($nextStep->role_key === 'approver_division_chief') {
                        $nextApproversQuery->whereHas('employee', function($q) use ($leave) {
                            $q->where('division_id', $leave->employee->division_id);
                        });
                    }

                    $nextApprovers = $nextApproversQuery->get();

                    foreach ($nextApprovers as $nextApprover) {
                        $nextApprover->notify(new \App\Notifications\SystemLeaveNotification(
                            $leave,
                            'A leave has been forwarded to you for approval.',
                            route('approver.leaves.show', $leave->id)
                        ));
                    }
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Next approver notification failed: ' . $e->getMessage());
            }
        }

        return redirect()->route('approver.inbox')->with('status', 'Application processed successfully.');
    }

    /**
     * Make sure the approver has a signature on file, storing a newly uploaded
     * or drawn one so it can be reused on future approvals.
     */
    private function storeApproverSignature(Request $request, \App\Models\User $user): void
    {
        $mode = $request->input('signature_mode');

        if ($mode === 'saved') {
            if (blank($user->signature_path) || !Storage::disk('public')->exists($user->signature_path)) {
                throw new \InvalidArgumentException('No saved signature found. Please upload or draw your signature to approve.');
            }

            return;
        }

        if ($mode === 'upload') {
            $file = $request->file('signature_file');
            if (!$file) {
                throw new \InvalidArgumentException('Please upload or draw your signature to approve.');
            }

            $path = $file->storeAs(
                'signatures',
                $user->id . '_' . time() . '.' . $file->getClientOriginalExtension(),
                'public'
            );
        } else {
            $binary = $this->decodeSignatureData((string) $request->input('signature_data', ''));

            $path = 'signatures/' . $user->id . '_' . time() . '.png';
            Storage::disk('public')->put($path, $binary);
        }

        $oldPath = $user->signature_path;

        $user->signature_path = $path;
        $user->save();

        if ($oldPath && $oldPath !== $path && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }
    }

    private function decodeSignatureData(string $data): string
    {
        if (!preg_match('/^data:image\/png;base64,/', $data)) {
            throw new \InvalidArgumentException('The drawn signature could not be read. Please try again.');
        }

        $binary = base64_decode(substr($data, strlen('data:image/png;base64,')), true);

        if ($binary === false || $binary === '') {
            throw new \InvalidArgumentException('The drawn signature could not be read. Please try again.');
        }

        return $binary;
    }

    private function authorizeAction(\App\Models\User $user, LeaveApplication $leave): void
    {
        if ($leave->status !== 'pending') abort(403, 'Leave is not pending.');
        if (!$user->employee || $user->employee->office_id !== $leave->office_id) abort(403, 'Office mismatch.');

        $step = ApprovalStep::where('office_id', $leave->office_id)
            ->where('step_order', $leave->current_step_order)
            ->firstOrFail();

        if (!$user->hasRole($step->role_key)) abort(403, 'Not assigned to this step.');

        if ($step->step_order === 1) {
            $leaveDivision = $leave->employee()->value('division_id');
            if ($leaveDivision !== $user->employee->division_id) abort(403, 'Division mismatch.');
        }
    }

    public function processCancellation(Request $request, int $id)
    {
        $request->validate(['cancellation_action' => 'required|in:approved,rejected']);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (!$user->hasRole('approver_personnel')) {
            abort(403, 'Only Chief Personnel can process cancellations.');
        }

        $leave = LeaveApplication::findOrFail($id);

        if ($leave->cancellation_status !== 'pending') {
            return back()->withErrors(['message' => 'No pending cancellation request.']);
        }

        $actionWord = '';

        if ($request->input('cancellation_action') === 'approved') {
            $leave->cancellation_status = 'approved';
            $leave->status = 'cancelled';
            $actionWord = 'Approved Cancellation';
        } else {
            $leave->cancellation_status = 'rejected';
            $actionWord = 'Rejected Cancellation';
        }

        $leave->save();

        LeaveApproval::create([
            'leave_application_id' => $leave->id,
            'step_order' => $leave->current_step_order,
            'approver_user_id' => $user->id,
            'action' => $actionWord,
            'remarks' => 'Processed employee cancellation request.',
            'acted_at' => \Carbon\Carbon::now(),
        ]);

        // Send email to the employee with the result of the cancellation
        try {
            $statusLabel = $actionWord === 'Approved Cancellation' ? 'CANCELLED' : 'CANCELLATION REJECTED';
            \Illuminate\Support\Facades\Mail::to($leave->employee->user->email)->send(
                new \App\Mail\LeaveStatusUpdated($leave, 'The Personnel has reviewed and processed your cancellation request.', $user->first_name . ' ' . $user->last_name, $statusLabel)
            );
            $leave->employee->user->notify(new \App\Notifications\SystemLeaveNotification(
                $leave,
                "Your leave cancellation request was {$statusLabel}.",
                route('employee.leaves.show', $leave->id)
            ));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Cancellation process email failed: ' . $e->getMessage());
        }

        return redirect()->route('approver.inbox')->with('status', 'Cancellation request processed successfully.');
    }
}
