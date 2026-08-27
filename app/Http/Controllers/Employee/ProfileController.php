<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;


class ProfileController extends Controller
{
    /**
     * Display the logged-in employee's information.
     */
    public function show()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $user->load(['employee.office', 'employee.division']);
        $employee = $user->employee;

        return view('employee.profile', compact('user', 'employee'));
    }

    /**
     * Handle the e-signature upload.
     */
    public function uploadSignature(Request $request)
    {
        $request->validate([
            'signature' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        // Explicitly pull a fresh user from the database
        $user = \App\Models\User::find(Auth::id());

        if ($request->hasFile('signature')) {
            // Delete old signature to save space
            if ($user->signature_path && Storage::disk('public')->exists($user->signature_path)) {
                Storage::disk('public')->delete($user->signature_path);
            }

            // Upload new signature
            $file = $request->file('signature');
            $filename = $user->id . '_' . time() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('signatures', $filename, 'public');

            // Save to database
            $user->signature_path = $path;
            $user->save();
        }

        return back()->with('status', 'E-Signature uploaded successfully!');
    }
}
