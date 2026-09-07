<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use App\Models\Office;
use App\Models\Division;
use Illuminate\Http\Request;

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
     * Log approval actions with step order
     */
    public function logApproval(
        User $user,
        string $action,
        int $leaveApplicationId,
        int $stepOrder,
        ?string $remarks = null,
        ?Request $request = null
    ): AuditLog {
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
     * Log cancellation actions with step order
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
     * Log view actions
     */
    public function logView(
        User $user,
        string $resourceType,
        int $resourceId,
        ?string $description = null,
        ?Request $request = null
    ): AuditLog {
        $data = [
            'user_id' => $user->id,
            'action_type' => 'view',
            'action' => 'viewed',
            'description' => $description ?? "Viewed {$resourceType}",
            'details' => [
                'resource_type' => $resourceType,
                'resource_id' => $resourceId,
            ],
        ];

        if ($resourceType === 'leave_application') {
            $data['leave_application_id'] = $resourceId;
        }

        $this->addContextualData($data, $user, $request);

        return $this->logAction($data);
    }

    /**
     * Log export actions
     */
    public function logExport(
        User $user,
        string $exportType,
        ?string $format = null,
        ?array $filters = null,
        ?Request $request = null
    ): AuditLog {
        $data = [
            'user_id' => $user->id,
            'action_type' => 'export',
            'action' => 'exported',
            'description' => "Exported {$exportType}" . ($format ? " as {$format}" : ''),
            'details' => [
                'export_type' => $exportType,
                'format' => $format,
                'filters' => $filters,
            ],
        ];

        $this->addContextualData($data, $user, $request);

        return $this->logAction($data);
    }

    /**
     * Log dashboard access
     */
    public function logDashboardAccess(
        User $user,
        ?Request $request = null
    ): AuditLog {
        $data = [
            'user_id' => $user->id,
            'action_type' => 'dashboard',
            'action' => 'accessed',
            'description' => 'Dashboard accessed',
            'details' => [
                'user_roles' => $user->roleKeys(),
            ],
        ];

        $this->addContextualData($data, $user, $request);

        return $this->logAction($data);
    }

    /**
     * Log inbox access
     */
    public function logInboxAccess(
        User $user,
        ?array $filters = null,
        ?Request $request = null
    ): AuditLog {
        $data = [
            'user_id' => $user->id,
            'action_type' => 'inbox',
            'action' => 'accessed',
            'description' => 'Inbox accessed',
            'details' => [
                'filters' => $filters,
            ],
        ];

        $this->addContextualData($data, $user, $request);

        return $this->logAction($data);
    }

    /**
     * Log custom actions (for OTP operations, etc.)
     */
    public function logCustom(
        User $user,
        string $action,
        string $description,
        ?array $details = null,
        ?Request $request = null
    ): AuditLog {
        $data = [
            'user_id' => $user->id,
            'action_type' => 'custom',
            'action' => $action,
            'description' => $description,
            'details' => $details ?? [],
        ];

        if ($details && isset($details['leave_id'])) {
            $data['leave_application_id'] = $details['leave_id'];
        }

        $this->addContextualData($data, $user, $request);

        return $this->logAction($data);
    }

    /**
     * Add contextual data (office, division, IP, user agent)
     */
    private function addContextualData(array &$data, User $user, ?Request $request): void
    {
        // Auto-detect office and division from user's employee profile
        if ($user->employee) {
            $data['office_id'] = $user->employee->office_id;
            $data['division_id'] = $user->employee->division_id;
        }

        // Add IP address and user agent if request is provided
        if ($request) {
            $data['ip_address'] = $request->ip();
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