<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use App\Models\Office;
use App\Models\Division;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class AuditLogService
{
    /**
     * Main method to log any action
     */
    public function logAction(array $data): AuditLog
    {
        return AuditLog::create($data);
    }

    /**
     * Log leave application creation
     */
    public function logLeaveCreation(
        User $user,
        int $leaveApplicationId,
        ?Request $request = null
    ): AuditLog {
        $data = [
            'user_id' => $user->id,
            'action_type' => 'creation',
            'action' => 'created',
            'description' => 'Leave application created',
            'leave_application_id' => $leaveApplicationId,
            'details' => [
                'leave_id' => $leaveApplicationId,
            ],
        ];

        $this->addContextualData($data, $user, $request);

        return $this->logAction($data);
    }

    /**
     * Log approval actions with step order (only first action per user per leave per step)
     */
    public function logApproval(
        User $user,
        string $action,
        int $leaveApplicationId,
        int $stepOrder,
        ?string $remarks = null,
        ?Request $request = null
    ): ?AuditLog {
        // Check if this user has already taken an approval action on this leave application at this specific step
        $existingApproval = AuditLog::where('user_id', $user->id)
            ->where('action_type', 'approval')
            ->where('leave_application_id', $leaveApplicationId)
            ->where('step_order', $stepOrder)
            ->first();

        // If already taken an approval action at this step, don't log again
        if ($existingApproval) {
            return null;
        }

        $data = [
            'user_id' => $user->id,
            'action_type' => 'approval',
            'action' => $action,
            'description' => $this->getApprovalDescription($action, $stepOrder),
            'step_order' => $stepOrder,
            'leave_application_id' => $leaveApplicationId,
            'details' => [
                'remarks' => $remarks,
                'step_order' => $stepOrder,
            ],
        ];

        $this->addContextualData($data, $user, $request);

        return $this->logAction($data);
    }

    /**
     * Log cancellation (when leave application is cancelled)
     */
    public function logCancellation(
        User $user,
        int $leaveApplicationId,
        ?int $stepOrder = null,
        ?string $reason = null,
        ?Request $request = null
    ): AuditLog {
        $data = [
            'user_id' => $user->id,
            'action_type' => 'cancellation',
            'action' => 'cancelled',
            'description' => 'Leave application cancelled',
            'step_order' => $stepOrder,
            'leave_application_id' => $leaveApplicationId,
            'details' => [
                'reason' => $reason,
                'step_order' => $stepOrder,
            ],
        ];

        $this->addContextualData($data, $user, $request);

        return $this->logAction($data);
    }

    /**
     * Log cancellation request (when employee requests cancellation)
     */
    public function logCancellationRequest(
        User $user,
        int $leaveApplicationId,
        ?string $reason = null,
        ?Request $request = null
    ): AuditLog {
        $data = [
            'user_id' => $user->id,
            'action_type' => 'cancellation_request',
            'action' => 'requested_cancellation',
            'description' => 'Leave application cancellation requested',
            'leave_application_id' => $leaveApplicationId,
            'details' => [
                'reason' => $reason,
            ],
        ];

        $this->addContextualData($data, $user, $request);

        return $this->logAction($data);
    }

    /**
     * Log cancellation approval/rejection (by personnel)
     */
    public function logCancellationAction(
        User $user,
        string $action,
        int $leaveApplicationId,
        ?string $remarks = null,
        ?Request $request = null
    ): AuditLog {
        $data = [
            'user_id' => $user->id,
            'action_type' => 'cancellation_action',
            'action' => $action,
            'description' => "Cancellation request {$action}",
            'leave_application_id' => $leaveApplicationId,
            'details' => [
                'remarks' => $remarks,
            ],
        ];

        $this->addContextualData($data, $user, $request);

        return $this->logAction($data);
    }

    /**
     * Log view actions for leave applications (only first view per user per leave per step)
     */
    public function logView(
        User $user,
        int $leaveApplicationId,
        ?string $description = null,
        ?Request $request = null,
        ?int $stepOrder = null
    ): ?AuditLog {
        // Check if this user has already viewed this leave application at this specific step
        $query = AuditLog::where('user_id', $user->id)
            ->where('action_type', 'view')
            ->where('leave_application_id', $leaveApplicationId);

        // If step order is provided, check for views at this specific step
        if ($stepOrder !== null) {
            $query->where('step_order', $stepOrder);
        }

        $existingView = $query->first();

        // If already viewed (at this step if specified), don't log again
        if ($existingView) {
            return null;
        }

        $data = [
            'user_id' => $user->id,
            'action_type' => 'view',
            'action' => 'viewed',
            'description' => $description ?? "Viewed leave application #{$leaveApplicationId}",
            'leave_application_id' => $leaveApplicationId,
            'step_order' => $stepOrder,
            'details' => [
                'leave_id' => $leaveApplicationId,
                'step_order' => $stepOrder,
            ],
        ];

        $this->addContextualData($data, $user, $request);

        return $this->logAction($data);
    }

    /**
     * Add contextual data (office, division, encrypted IP, user agent)
     */
    private function addContextualData(array &$data, User $user, ?Request $request): void
    {
        // Auto-detect office and division from user's employee profile
        if ($user->employee) {
            $data['office_id'] = $user->employee->office_id;
            $data['division_id'] = $user->employee->division_id;
        }

        // Add encrypted IP address and user agent if request is provided
        if ($request) {
            $data['ip_address'] = Crypt::encryptString($request->ip());
            $data['user_agent'] = $request->userAgent();
        }
    }

    /**
     * Get description for approval actions
     */
    private function getApprovalDescription(string $action, int $stepOrder): string
    {
        $actionText = $action;
        if (in_array($action, ['approved', 'disapproved', 'returned'])) {
            $actionText = $action;
        }

        return "Leave application {$actionText} at step {$stepOrder}";
    }
}