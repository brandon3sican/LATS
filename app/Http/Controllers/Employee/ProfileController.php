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
            'signature' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'signature_data' => 'nullable|string',
        ]);

        // Explicitly pull a fresh user from the database
        $user = \App\Models\User::find(Auth::id());

        // Handle file upload
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

            return back()->with('status', 'E-Signature uploaded successfully!');
        }

        // Handle drawn signature (base64 data)
        if ($request->filled('signature_data')) {
            // Delete old signature to save space
            if ($user->signature_path && Storage::disk('public')->exists($user->signature_path)) {
                Storage::disk('public')->delete($user->signature_path);
            }

            // Process base64 data
            $signatureData = $request->input('signature_data');
            $imageData = substr($signatureData, strpos($signatureData, ',') + 1);
            $imageData = base64_decode($imageData);

            // Save as PNG file
            $filename = $user->id . '_' . time() . '.png';
            $path = 'signatures/' . $filename;
            Storage::disk('public')->put($path, $imageData);

            // Save to database
            $user->signature_path = $path;
            $user->save();

            return back()->with('status', 'E-Signature saved successfully!');
        }

        return back()->withErrors(['error' => 'No signature data provided.']);
    }
}
