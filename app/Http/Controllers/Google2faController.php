<?php

namespace App\Http\Controllers;

use App\Http\Requests\Google2faRequest;
use App\Services\AuditLogService;
use App\Services\Google2faService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class Google2faController extends Controller
{
    protected AuditLogService $auditLogService;

    public function __construct(AuditLogService $auditLogService)
    {
        $this->auditLogService = $auditLogService;
    }

    public function showSetup()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $google2fa = new Google2faService();
        $qrCodeUrl = null;
        $secret = null;

        // If user already has a secret but hasn't enabled, show it
        if ($user->google2fa_secret && !$user->google2fa_enabled) {
            $secret = $user->getGoogle2faSecret();
            $qrCodeUrl = $user->getGoogle2faQrCodeUrl();
        }

        return view('google2fa.setup', compact('user', 'qrCodeUrl', 'secret'));
    }

    public function enable(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        try {
            // Simple test to see if we get here
            \Log::info('Google2fa enable called', ['user_id' => $user->id]);

            $secret = $user->enableGoogle2fa();
            \Log::info('Secret generated', ['secret' => $secret]);

            $qrCodeUrl = $user->getGoogle2faQrCodeUrl();
            \Log::info('QR code generated', ['url' => $qrCodeUrl]);

            // Log Google Authenticator setup initiation
            $this->auditLogService->logCustom(
                $user,
                'google2fa_setup_initiated',
                "Google Authenticator setup initiated for user",
                [
                    'user_id' => $user->id,
                ],
                $request
            );

            return response()->json([
                'success' => true,
                'secret' => $secret,
                'qr_code_url' => $qrCodeUrl,
                'message' => 'Google Authenticator setup initiated. Please scan the QR code and verify.',
            ]);
        } catch (\Exception $e) {
            \Log::error('Google2fa enable error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to enable Google Authenticator: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function confirmSetup(Google2faRequest $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        try {
            if ($user->confirmGoogle2fa($request->code)) {
                // Log successful Google Authenticator enablement
                $this->auditLogService->logCustom(
                    $user,
                    'google2fa_enabled',
                    "Google Authenticator successfully enabled for user",
                    [
                        'user_id' => $user->id,
                        'enabled_at' => $user->google2fa_enabled_at,
                    ],
                    $request
                );

                return response()->json([
                    'success' => true,
                    'message' => 'Google Authenticator has been successfully enabled.',
                    'recovery_codes' => $user->getRemainingRecoveryCodes(),
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid code. Please try again.',
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to confirm Google Authenticator setup: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function disable(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        try {
            $user->disableGoogle2fa();

            // Log Google Authenticator disablement
            $this->auditLogService->logCustom(
                $user,
                'google2fa_disabled',
                "Google Authenticator disabled for user",
                [
                    'user_id' => $user->id,
                ],
                $request
            );

            return response()->json([
                'success' => true,
                'message' => 'Google Authenticator has been disabled.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to disable Google Authenticator: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function showRecoveryCodes()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (!$user->hasGoogle2faEnabled()) {
            abort(400, 'Google Authenticator is not enabled.');
        }

        $recoveryCodes = $user->getRemainingRecoveryCodes();

        return view('google2fa.recovery-codes', compact('user', 'recoveryCodes'));
    }

    public function regenerateRecoveryCodes(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Check if user has required role
        if (!$user->hasAnyRole(['approver_chief_personnel', 'approver_ard_ms'])) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to regenerate recovery codes.',
            ], 403);
        }

        if (!$user->hasGoogle2faEnabled()) {
            return response()->json([
                'success' => false,
                'message' => 'Google Authenticator is not enabled.',
            ], 400);
        }

        try {
            $newCodes = $user->generateRecoveryCodes();
            $user->google2fa_recovery_codes = json_encode($newCodes);
            $user->save();

            // Log recovery codes regeneration
            $this->auditLogService->logCustom(
                $user,
                'google2fa_recovery_codes_regenerated',
                "Google Authenticator recovery codes regenerated for user",
                [
                    'user_id' => $user->id,
                ],
                $request
            );

            return response()->json([
                'success' => true,
                'message' => 'Recovery codes have been regenerated.',
                'recovery_codes' => $newCodes,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to regenerate recovery codes: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function verifyCode(Google2faRequest $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (!$user->hasGoogle2faEnabled()) {
            return response()->json([
                'success' => false,
                'message' => 'Google Authenticator is not enabled.',
            ], 400);
        }

        try {
            // Check if it's a recovery code
            if ($request->isRecoveryCode()) {
                if ($user->verifyRecoveryCode($request->code)) {
                    // Log recovery code usage
                    $this->auditLogService->logCustom(
                        $user,
                        'google2fa_recovery_code_used',
                        "Google Authenticator recovery code used by user",
                        [
                            'user_id' => $user->id,
                        ],
                        $request
                    );

                    return response()->json([
                        'success' => true,
                        'message' => 'Recovery code verified successfully.',
                        'remaining_codes' => count($user->getRemainingRecoveryCodes()),
                    ]);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid recovery code.',
                    ], 400);
                }
            }

            // Regular TOTP verification
            if ($user->verifyGoogle2faCode($request->code)) {
                return response()->json([
                    'success' => true,
                    'message' => 'Code verified successfully.',
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid code.',
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to verify code: ' . $e->getMessage(),
            ], 500);
        }
    }
}