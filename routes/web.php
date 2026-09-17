<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\SignatureController;
use App\Http\Controllers\OtpController;
use App\Http\Controllers\Google2faController;

// Admin Controllers
use App\Http\Controllers\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Admin\ApprovalStepController as AdminApprovalStepController;
use App\Http\Controllers\Admin\EmployeeController as AdminEmployeeController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;

// Employee Controllers
use App\Http\Controllers\Employee\DashboardController as EmployeeDashboardController;
use App\Http\Controllers\Employee\LeaveController as EmployeeLeaveController;
use App\Http\Controllers\Employee\ReportController as EmployeeReportController;
use App\Http\Controllers\Employee\ProfileController as EmployeeProfileController;

// Approver Controllers
use App\Http\Controllers\Approver\InboxController as ApproverInboxController;
use App\Http\Controllers\Approver\LeaveActionController as ApproverLeaveActionController;
use App\Http\Controllers\Approver\ReportController as ApproverReportController;
use App\Http\Controllers\Approver\DashboardController as ApproverDashboardController;

// Super Admin Controllers
use App\Http\Controllers\Super\OfficeController as SuperOfficeController;
use App\Http\Controllers\Super\UserController as SuperUserController;
use App\Http\Controllers\Super\DivisionController as SuperDivisionController;
use App\Http\Controllers\Super\DashboardController as SuperDashboardController;
use App\Http\Controllers\Super\AuditLogController as SuperAuditLogController;
use App\Http\Controllers\Super\ReportGeneratorController as SuperReportGeneratorController;
use App\Http\Controllers\Super\LeaveController as SuperLeaveController;

/*
|--------------------------------------------------------------------------
| Public
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return redirect()->route('login');
});

require __DIR__ . '/auth.php';

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {

    Route::get('/profile', [\App\Http\Controllers\ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [\App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [\App\Http\Controllers\ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/my-profile', [\App\Http\Controllers\Employee\ProfileController::class, 'show'])->name('employee.profile.show');
    Route::post('/my-profile/signature', [\App\Http\Controllers\Employee\ProfileController::class, 'uploadSignature'])->name('employee.profile.signature');
    Route::get('/signature-preview/{path}', [SignatureController::class, 'show'])
        ->where('path', '.*')
        ->name('signatures.show');

    /*
    |--------------------------------------------------------------------------
    | TRAFFIC COP: Single Landing Dashboard
    |--------------------------------------------------------------------------
    */
    Route::get('/dashboard', function () {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        if (!$user) return redirect()->route('login');

        $user->loadMissing('roles');

        // Priority Checks
        if ($user->hasRole('super_admin')) return redirect()->route('super.dashboard');
        if ($user->hasRole('office_admin')) return redirect()->route('admin.dashboard');

        // Employee role takes priority for users with both employee and approver roles
        // This allows approvers to create their own leave applications
        if ($user->hasRole('employee')) return redirect()->route('employee.dashboard');

        // Check for any approver role (if they don't have employee role)
        if ($user->roles->pluck('key')->intersect([
            'approver_division_chief',
            'approver_personnel',
            'approver_chief_personnel',
            'approver_chief_admin',
            'approver_ard_ms'
        ])->isNotEmpty()) {
            return redirect()->route('approver.dashboard');
        }

        abort(403, 'Your account does not have a valid role assigned. Please contact the administrator.');
    })->name('dashboard');

    // Attachments
    Route::get('/attachments/{attachment}/preview', [AttachmentController::class, 'preview'])->name('attachments.preview');
    Route::get('/attachments/{attachment}/download', [AttachmentController::class, 'download'])->name('attachments.download');

    // Notifications
    Route::get('/notifications/{id}/read', function($id) {

        /** @var \App\Models\User $user */
        $user = \Illuminate\Support\Facades\Auth::user();

        $notification = $user->notifications()->findOrFail($id);
        $notification->markAsRead();

        return redirect($notification->data['url']);

    })->name('notifications.read');

    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.markAllRead');
    Route::post('/notifications/{id}/mark-read', [NotificationController::class, 'markAsRead'])->name('notifications.markRead');

    /*
    |--------------------------------------------------------------------------
    | EMPLOYEE
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:employee,approver_division_chief,approver_personnel,approver_chief_personnel,approver_chief_admin,approver_ard_ms')->prefix('employee')->name('employee.')->group(function () {

        Route::get('/dashboard', [EmployeeDashboardController::class, 'index'])->name('dashboard');
        Route::get('/dashboard/events', [EmployeeDashboardController::class, 'events'])->name('dashboard.events');

        // LEAVES
        Route::get('/leaves', [EmployeeLeaveController::class, 'index'])->name('leaves.index');
        Route::get('/leaves/create', [EmployeeLeaveController::class, 'create'])->name('leaves.create');
        Route::post('/leaves', [EmployeeLeaveController::class, 'store'])->name('leaves.store');
        Route::get('/leaves/{id}', [EmployeeLeaveController::class, 'show'])->name('leaves.show');
        Route::post('/leaves/required-docs', [EmployeeLeaveController::class, 'requiredDocs'])->name('leaves.requiredDocs');
        Route::post('/leaves/{id}/cancel', [EmployeeLeaveController::class, 'requestCancellation'])->name('leaves.cancel');

        // REPORTS (Employee Specific Only)
        Route::get('/reports', [EmployeeReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/my-forms', [EmployeeReportController::class, 'myForms'])->name('reports.myForms');
        Route::get('/reports/my-forms/excel', [EmployeeReportController::class, 'myFormsExcel'])->name('reports.myForms.excel');
        Route::get('/reports/my-forms/pdf', [EmployeeReportController::class, 'myFormsPdf'])->name('reports.myForms.pdf');

        // Form 6 PDF
        Route::get('/leaves/{id}/form6/pdf', [EmployeeReportController::class, 'form6Pdf'])->name('leaves.form6.pdf');
    });

    /*
    |--------------------------------------------------------------------------
    | APPROVER
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:approver_division_chief,approver_personnel,approver_chief_personnel,approver_chief_admin,approver_ard_ms')
        ->prefix('approver')->name('approver.')->group(function () {

            Route::get('/dashboard', [ApproverDashboardController::class, 'index'])->name('dashboard');

            Route::get('/inbox', [ApproverInboxController::class, 'index'])->name('inbox');
            Route::get('/leaves/{id}', [ApproverLeaveActionController::class, 'show'])->name('leaves.show');
            Route::post('/leaves/{id}/action', [ApproverLeaveActionController::class, 'action'])->name('leaves.action');
            Route::post('/leaves/{id}/complete-with-otp', [ApproverLeaveActionController::class, 'completeApprovalWithOtp'])->name('leaves.completeWithOtp');
            Route::post('/leaves/{id}/process-cancellation', [ApproverLeaveActionController::class, 'processCancellation'])->name('leaves.processCancellation');

            // OTP Routes
            Route::post('/otp/send/{id}', [OtpController::class, 'sendOtp'])->name('otp.send');
            Route::post('/otp/verify/{id}', [OtpController::class, 'verifyOtp'])->name('otp.verify');
            Route::post('/otp/resend/{id}', [OtpController::class, 'resendOtp'])->name('otp.resend');
            Route::post('/otp/verify-google2fa/{id}', [OtpController::class, 'verifyGoogle2fa'])->name('otp.verifyGoogle2fa');

            // Google Authenticator Routes
            Route::get('/google2fa/setup', [Google2faController::class, 'showSetup'])->name('google2fa.setup');
            Route::post('/google2fa/enable', [Google2faController::class, 'enable'])->name('google2fa.enable');
            Route::post('/google2fa/confirm', [Google2faController::class, 'confirmSetup'])->name('google2fa.confirm');
            Route::post('/google2fa/disable', [Google2faController::class, 'disable'])->name('google2fa.disable');
            Route::post('/google2fa/verify', [Google2faController::class, 'verifyCode'])->name('google2fa.verify');
            Route::get('/google2fa/recovery-codes', [Google2faController::class, 'showRecoveryCodes'])->name('google2fa.recovery-codes');
            Route::post('/google2fa/regenerate-codes', [Google2faController::class, 'regenerateRecoveryCodes'])->name('google2fa.regenerate-codes');

            Route::get('/reports', [ApproverReportController::class, 'index'])->name('reports.index');
            Route::get('/reports/my-actions', [ApproverReportController::class, 'myActions'])->name('reports.myActions');
            Route::get('/reports/my-actions/excel', [ApproverReportController::class, 'myActionsExcel'])->name('reports.myActions.excel');
            Route::get('/reports/my-actions/pdf', [ApproverReportController::class, 'myActionsPdf'])->name('reports.myActions.pdf');

            Route::get('/leaves/{id}/form6/pdf', [ApproverReportController::class, 'form6Pdf'])->name('leaves.form6.pdf');
    });

    /*
    |--------------------------------------------------------------------------
    | OFFICE ADMIN
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:office_admin')->prefix('admin')->name('admin.')->group(function () {

        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

        Route::get('/approval-steps', [AdminApprovalStepController::class, 'index'])->name('approvalSteps.index');
        Route::put('/approval-steps', [AdminApprovalStepController::class, 'update'])->name('approvalSteps.update');

        // Reports
        Route::get('/reports', [AdminReportController::class, 'index'])->name('reports.index');

        Route::get('/reports/monthly', [AdminReportController::class, 'monthly'])->name('reports.monthly');
        Route::get('/reports/monthly/excel', [AdminReportController::class, 'monthlyExcel'])->name('reports.monthly.excel');
        Route::get('/reports/monthly/pdf', [AdminReportController::class, 'monthlyPdf'])->name('reports.monthly.pdf');

        Route::get('/reports/employee', [AdminReportController::class, 'employee'])->name('reports.employee');
        Route::get('/reports/employee/excel', [AdminReportController::class, 'employeeExcel'])->name('reports.employee.excel');
        Route::get('/reports/employee/pdf', [AdminReportController::class, 'employeePdf'])->name('reports.employee.pdf');

        Route::get('/reports/division', [AdminReportController::class, 'division'])->name('reports.division');
        Route::get('/reports/division/excel', [AdminReportController::class, 'divisionExcel'])->name('reports.division.excel');
        Route::get('/reports/division/pdf', [AdminReportController::class, 'divisionPdf'])->name('reports.division.pdf');

        // Employee Management
        Route::resource('employees', AdminEmployeeController::class);

        Route::get('/leaves/{id}/form6/pdf', [AdminReportController::class, 'form6Pdf'])->name('leaves.form6.pdf');
    });

    /*
    |--------------------------------------------------------------------------
    | SUPER ADMIN
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:super_admin')->prefix('super')->name('super.')->group(function () {

        Route::get('/dashboard', [SuperDashboardController::class, 'index'])->name('dashboard');

        Route::resource('offices', SuperOfficeController::class)->except(['show', 'destroy']);

        Route::resource('divisions', SuperDivisionController::class);

        Route::resource('users', SuperUserController::class)->except(['show', 'destroy']);
        Route::get('users/{id}', [SuperUserController::class, 'show'])->name('users.show');
        Route::delete('users/{id}', [SuperUserController::class, 'destroy'])->name('users.destroy');
        Route::post('users/{id}/reset-password', [SuperUserController::class, 'resetPassword'])->name('users.resetPassword');
        Route::get('users/{id}/modal-data', [SuperUserController::class, 'getModalData'])->name('users.modalData');
    });

    /*
    |--------------------------------------------------------------------------
    | AUDIT LOGS (Shared by Super Admin and Chief Personnel)
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:super_admin,approver_chief_personnel')->prefix('super')->name('super.')->group(function () {
        Route::get('/audit-logs', [SuperAuditLogController::class, 'index'])->name('audit-logs.index');
        Route::get('/audit-logs/export', [SuperAuditLogController::class, 'export'])->name('audit-logs.export');
        Route::get('/audit-logs/{id}', [SuperAuditLogController::class, 'show'])->name('audit-logs.show');
        Route::get('/leaves/{id}', [SuperLeaveController::class, 'show'])->name('leaves.show');
    });

    /*
    |--------------------------------------------------------------------------
    | REPORT GENERATION (Shared by Super Admin and Chief Personnel)
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:super_admin,approver_chief_personnel')->prefix('super')->name('super.')->group(function () {
        Route::get('/reports/generate', [SuperReportGeneratorController::class, 'index'])->name('reports.generate');
        Route::post('/reports/efficiency', [SuperReportGeneratorController::class, 'generateEfficiencyReport'])->name('reports.efficiency');
        Route::post('/reports/audit', [SuperReportGeneratorController::class, 'generateAuditReport'])->name('reports.audit');
        Route::post('/reports/combined', [SuperReportGeneratorController::class, 'generateCombinedReport'])->name('reports.combined');
    });
});
