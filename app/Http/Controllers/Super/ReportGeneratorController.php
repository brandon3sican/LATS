<?php

namespace App\Http\Controllers\Super;

use App\Http\Controllers\Controller;
use App\Models\Division;
use App\Services\ReportDataService;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class ReportGeneratorController extends Controller
{
    protected ReportDataService $reportDataService;
    protected AuditLogService $auditLogService;

    public function __construct(ReportDataService $reportDataService, AuditLogService $auditLogService)
    {
        $this->reportDataService = $reportDataService;
        $this->auditLogService = $auditLogService;
        
        // Note: Role-based access control is handled at the route level in routes/web.php
        // These routes are protected with: Route::middleware('role:super_admin,approver_chief_personnel')
    }

    /**
     * Display report generation form with filters
     */
    public function index(Request $request)
    {
        // Additional role check for security
        $user = $request->user();
        if (!$user || (!$user->hasRole('approver_chief_personnel') && !$user->hasRole('super_admin'))) {
            abort(403, 'Access denied. Report generation is restricted to Chief Personnel and Super Admin.');
        }

        $divisions = Division::orderBy('name')->get();
        $selectedDivision = $request->get('division_id');
        $fromDate = $request->get('from_date', now()->subMonths(3)->format('Y-m-d'));
        $toDate = $request->get('to_date', now()->format('Y-m-d'));
        $reportType = $request->get('report_type', 'efficiency');

        return view('super.reports.generate', compact(
            'divisions',
            'selectedDivision',
            'fromDate',
            'toDate',
            'reportType'
        ));
    }

    /**
     * Generate efficiency metrics PDF report
     */
    public function generateEfficiencyReport(Request $request)
    {
        // Additional role check for security
        $user = $request->user();
        if (!$user || (!$user->hasRole('approver_chief_personnel') && !$user->hasRole('super_admin'))) {
            abort(403, 'Access denied. Report generation is restricted to Chief Personnel and Super Admin.');
        }

        $divisionId = $request->get('division_id');
        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');

        // Validate date range
        $from = Carbon::parse($fromDate)->startOfDay();
        $to = Carbon::parse($toDate)->endOfDay();

        // Get report data
        $data = $this->reportDataService->getEfficiencyMetricsData($divisionId, [$from, $to]);

        // Log report generation
        $this->auditLogService->logExport(
            $user,
            'efficiency_metrics_report',
            'PDF',
            [
                'division_id' => $divisionId,
                'from_date' => $fromDate,
                'to_date' => $toDate,
            ],
            $request
        );

        // Generate PDF
        $pdf = Pdf::loadView('reports.efficiency_metrics', array_merge($data, [
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'generated_at' => now()->format('F d, Y g:i A'),
        ]))
            ->setPaper('a4', 'portrait');

        $filename = 'efficiency_metrics_report_' . now()->format('Y_m_d_His') . '.pdf';
        return $pdf->stream($filename);
    }

    /**
     * Generate audit trail PDF report
     */
    public function generateAuditReport(Request $request)
    {
        // Additional role check for security
        $user = $request->user();
        if (!$user || (!$user->hasRole('approver_chief_personnel') && !$user->hasRole('super_admin'))) {
            abort(403, 'Access denied. Report generation is restricted to Chief Personnel and Super Admin.');
        }

        $divisionId = $request->get('division_id');
        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');

        // Validate date range
        $from = Carbon::parse($fromDate)->startOfDay();
        $to = Carbon::parse($toDate)->endOfDay();

        // Get report data
        $data = $this->reportDataService->getAuditTrailData($divisionId, [$from, $to]);

        // Log report generation
        $this->auditLogService->logExport(
            $user,
            'audit_trail_report',
            'PDF',
            [
                'division_id' => $divisionId,
                'from_date' => $fromDate,
                'to_date' => $toDate,
            ],
            $request
        );

        // Generate PDF
        $pdf = Pdf::loadView('reports.audit_trail', array_merge($data, [
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'generated_at' => now()->format('F d, Y g:i A'),
        ]))
            ->setPaper('a4', 'portrait');

        $filename = 'audit_trail_report_' . now()->format('Y_m_d_His') . '.pdf';
        return $pdf->stream($filename);
    }

    /**
     * Generate combined analysis PDF report
     */
    public function generateCombinedReport(Request $request)
    {
        // Additional role check for security
        $user = $request->user();
        if (!$user || (!$user->hasRole('approver_chief_personnel') && !$user->hasRole('super_admin'))) {
            abort(403, 'Access denied. Report generation is restricted to Chief Personnel and Super Admin.');
        }

        $divisionId = $request->get('division_id');
        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');

        // Validate date range
        $from = Carbon::parse($fromDate)->startOfDay();
        $to = Carbon::parse($toDate)->endOfDay();

        // Get report data
        $data = $this->reportDataService->getCombinedAnalysisData($divisionId, [$from, $to]);

        // Log report generation
        $this->auditLogService->logExport(
            $user,
            'combined_analysis_report',
            'PDF',
            [
                'division_id' => $divisionId,
                'from_date' => $fromDate,
                'to_date' => $toDate,
            ],
            $request
        );

        // Generate PDF
        $pdf = Pdf::loadView('reports.combined_analysis', array_merge($data, [
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'generated_at' => now()->format('F d, Y g:i A'),
        ]))
            ->setPaper('a4', 'portrait');

        $filename = 'combined_analysis_report_' . now()->format('Y_m_d_His') . '.pdf';
        return $pdf->stream($filename);
    }
}