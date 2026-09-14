<?php

namespace App\Http\Controllers;

use App\Http\Requests\Google2faRequest;
use App\Http\Requests\OtpRequest;
use App\Models\LeaveApplication;
use App\Models\OneTimePassword;
use App\Notifications\ApprovalOtpNotification;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;

class OtpController extends Controller
{
    protected AuditLogService $auditLogService;

    public function __construct(AuditLogService $auditLogService)
    {
        $this->auditLogService = $auditLogService;
    }

    public function sendOtp(Request $request, int $leaveId)
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();
            $user->loadMissing('roles', 'employee');

            // Rate limiting: max 3 OTP sends per 5 minutes
            $key = 'otp-send:' . $user->id . ':' . $leaveId;
            if (RateLimiter::tooManyAttempts($key, 3)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Too many OTP requests. Please wait before trying again.',
                ], 429);
            }

            RateLimiter::hit($key, 300); // 5 minutes

            // Verify user has required role (Chief Personnel or ARD)
            if (!$user->hasAnyRole(['approver_chief_personnel', 'approver_ard_ms'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not authorized to use OTP verification.',
                ], 403);
            }

            // Verify leave exists and user can approve it
            $leave = LeaveApplication::with(['employee.user', 'leaveType'])->findOrFail($leaveId);
            if ($leave->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'This leave application is not pending approval.',
                ], 400);
            }

            // Signature should already be stored by LeaveActionController
            // Just verify it exists
            if (!$leave->temporary_signature) {
                return response()->json([
                    'success' => false,
                    'message' => 'Signature not found. Please start the approval process again.',
                ], 400);
            }

            // Generate OTP
            $otp = $user->generateOneTimePassword(30); // 30 minutes expiration

            // Log OTP code for debugging (remove in production)
            \Illuminate\Support\Facades\Log::info('OTP Generated', [
                'user_id' => $user->id,
                'otp_code' => $otp->code,
                'expires_at' => $otp->expires_at,
                'leave_id' => $leave->id
            ]);

            // Send OTP notification
            try {
                $user->notify(new ApprovalOtpNotification(
                    $otp->code,
                    $leave->id,
                    $leave->employee->user->name,
                    $leave->leaveType->name,
                    $otp->expires_at->format('g:i A')
                ));
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Failed to send OTP notification: ' . $e->getMessage());
                // Continue even if email fails - the OTP is still generated
            }

            // Log OTP generation for audit trail
            try {
                $this->auditLogService->logCustom(
                    $user,
                    'otp_generated',
                    "OTP generated for leave application #{$leave->id}",
                    [
                        'leave_id' => $leave->id,
                        'otp_id' => $otp->id,
                        'expires_at' => $otp->expires_at->toIso8601String(),
                    ],
                    $request
                );
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Failed to log OTP generation: ' . $e->getMessage());
                // Continue even if logging fails
            }

            return response()->json([
                'success' => true,
                'message' => 'OTP sent successfully to your email.',
                'expires_at' => $otp->expires_at->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('OTP generation error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while generating OTP: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function verifyOtp(OtpRequest $request, int $leaveId)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $user->loadMissing('roles', 'employee');

        // Rate limiting: max 5 verification attempts per OTP
        $key = 'otp-verify:' . $user->id . ':' . $leaveId;
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return response()->json([
                'success' => false,
                'message' => 'Too many verification attempts. Please request a new OTP.',
            ], 429);
        }

        RateLimiter::hit($key, 300); // 5 minutes

        // Verify user has required role
        if (!$user->hasAnyRole(['approver_chief_personnel', 'approver_ard_ms'])) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to use OTP verification.',
            ], 403);
        }

        // Verify OTP
        if (!$user->consumeOneTimePassword($request->otp)) {
            try {
                $this->auditLogService->logCustom(
                    $user,
                    'otp_verification_failed',
                    "Failed OTP verification for leave application #{$leaveId}",
                    [
                        'leave_id' => $leaveId,
                        'reason' => 'Invalid or expired OTP',
                    ],
                    $request
                );
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Failed to log OTP verification failure: ' . $e->getMessage());
            }

            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired OTP. Please try again.',
            ], 400);
        }

        // Log successful OTP verification
        try {
            $this->auditLogService->logCustom(
                $user,
                'otp_verified',
                "OTP verified successfully for leave application #{$leaveId}",
                [
                    'leave_id' => $leaveId,
                ],
                $request
            );
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to log OTP verification success: ' . $e->getMessage());
        }

        // Return the temporary signature in the response so it can be used for final approval
        $leave = LeaveApplication::find($leaveId);
        $temporarySignature = $leave->temporary_signature ?? null;

        return response()->json([
            'success' => true,
            'message' => 'OTP verified successfully. You can now complete the approval.',
            'temporary_signature' => $temporarySignature,
        ]);
    }

    public function resendOtp(Request $request, int $leaveId)
    {
        // Reuse sendOtp method with the same rate limiting
        return $this->sendOtp($request, $leaveId);
    }

    public function verifyGoogle2fa(Request $request, int $leaveId)
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();
            $user->loadMissing('roles', 'employee');

            $code = $request->input('code');

            \Log::info('Google2fa verification attempt', [
                'user_id' => $user->id,
                'leave_id' => $leaveId,
                'code' => $code,
                'code_length' => strlen($code),
                'has_google2fa_enabled' => $user->google2fa_enabled,
                'has_google2fa_secret' => !empty($user->google2fa_secret),
            ]);

            // Rate limiting: max 5 verification attempts per OTP
            $key = 'google2fa-verify:' . $user->id . ':' . $leaveId;
            if (RateLimiter::tooManyAttempts($key, 5)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Too many verification attempts. Please try again later.',
                ], 429);
            }

            RateLimiter::hit($key, 300); // 5 minutes

            // Verify Google Authenticator code
            if (!$user->verifyGoogle2faCode($code)) {
                \Log::info('Google2fa verification failed', [
                    'user_id' => $user->id,
                    'code' => $code,
                ]);

                try {
                    $this->auditLogService->logCustom(
                        $user,
                        'google2fa_verification_failed',
                        "Failed Google Authenticator verification for leave application #{$leaveId}",
                        [
                            'leave_id' => $leaveId,
                            'reason' => 'Invalid or expired code',
                        ],
                        $request
                    );
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Failed to log Google Authenticator verification failure: ' . $e->getMessage());
                }

                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or expired code. Please try again.',
                ], 400);
            }

            // Log successful Google Authenticator verification
            try {
                $this->auditLogService->logCustom(
                    $user,
                    'google2fa_verified',
                    "Google Authenticator verified successfully for leave application #{$leaveId}",
                    [
                        'leave_id' => $leaveId,
                    ],
                    $request
                );
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Failed to log Google Authenticator verification success: ' . $e->getMessage());
            }

            // Return the temporary signature in the response so it can be used for final approval
            $leave = LeaveApplication::find($leaveId);
            $temporarySignature = $leave->temporary_signature ?? null;

            return response()->json([
                'success' => true,
                'message' => 'Google Authenticator verified successfully. You can now complete the approval.',
                'temporary_signature' => $temporarySignature,
            ]);
        } catch (\Exception $e) {
            \Log::error('Google2fa verification error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage(),
            ], 500);
        }
    }
}