<?php

namespace App\Http\Controllers\Employee;
use App\Http\Controllers\Controller;
use App\Models\LeaveAttachment;
use App\Models\LeaveType;
use App\Services\LeaveRules\RequiredDocsEvaluator;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use App\Models\{LeaveApplication, ApprovalStep};

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user()->loadMissing('employee');

        if (!$user->employee) {
            abort(403, 'No employee profile assigned.');
        }

        $leaves = LeaveApplication::with('leaveType')
            ->where('employee_id', $user->employee->id)
            ->latest()
            ->paginate(15);

        return view('employee.leaves.index', compact('leaves'));
    }

    public function create(Request $request)
    {
        $user = $request->user()->loadMissing('employee');

        if (!$user->employee) {
            abort(403, 'No employee profile assigned.');
        }

        $types = LeaveType::where('is_active', true)->orderBy('name')->get();

        return view('employee.leaves.create', compact('types'));
    }

    public function show(Request $request, int $id)
    {
       $leave = LeaveApplication::with([
            'leaveType',
            'office',
            'attachments',
            'approvals.approver', // make sure relationship exists
            'employee.user',
        ])->findOrFail($id);

        // All steps for this office
        $steps = ApprovalStep::where('office_id', $leave->office_id)
            ->orderBy('step_order')
            ->get();

        // Index approvals by step_order (one per step)
        $approvalsByStep = $leave->approvals->keyBy('step_order');

        $timeline = $steps->map(function ($step) use ($leave, $approvalsByStep) {
            $ap = $approvalsByStep->get($step->step_order);

            // If acted already (approved/returned/disapproved)
            if ($ap) {
                return [
                    'step_order' => $step->step_order,
                    'role_key'   => $step->role_key,
                    'title'      => $step->label ?? $step->role_key,
                    'state'      => $ap->action,
                    'remarks'    => $ap->remarks,
                    'actor'      => $ap->approver->name ?? null,
                    'acted_at'   => $ap->acted_at ?? $ap->created_at,
                ];
            }

            // Not acted yet
            $isCurrent = ((int)$leave->current_step_order === (int)$step->step_order);

            return [
                'step_order' => $step->step_order,
                'role_key'   => $step->role_key,
                'title'      => $step->label ?? $step->role_key,
                'state'      => $isCurrent ? 'current' : 'upcoming',
                'remarks'    => null,
                'actor'      => null,
                'acted_at'   => null,
            ];
        });

        return view('employee.leaves.show', compact('leave', 'timeline'));
    }

    public function store(Request $request)
    {
        $user = $request->user()->loadMissing('employee');

        if (!$user->employee) {
            abort(403, 'No employee profile assigned.');
        }

        $validated = $request->validate([
            'leave_type_id' => 'required|exists:leave_types,id',
            'dates' => 'required|string', // NEW: Flatpickr sends a comma-separated string
            'working_days_requested' => 'required|numeric|min:0.5|max:365',
            'reason' => 'required|string|max:2000',
            'commutation' => 'nullable|string|max:50',
            'details' => 'nullable|array',
            'attachments' => ['nullable', 'array', 'max:10'],
            'attachments.*' => ['file', 'max:5120', 'mimes:pdf,jpg,jpeg,png'],
        ]);

        $leaveType = LeaveType::with('requiredDocuments')->findOrFail($validated['leave_type_id']);

        // Convert the comma-separated string back into an array and sort it
        $datesArray = array_filter(array_map('trim', explode(',', $validated['dates'])));
        if (empty($datesArray)) {
            return back()->withInput()->withErrors(['dates' => 'Please select at least one date.']);
        }

        $dates = collect($datesArray)->map(fn($d) => Carbon::parse($d))->sort()->values();
        $start_date = $dates->first()->toDateString();
        $end_date = $dates->last()->toDateString();

        // Normalize details booleans
        $details = $validated['details'] ?? [];
        $details['abroad'] = !empty($details['abroad']);
        $details['no_consultation'] = !empty($details['no_consultation']);
        $details['reason'] = $validated['reason'];
        $details['selected_dates'] = $dates->map->toDateString()->toArray(); // Save the exact dates

        $start = Carbon::parse($start_date)->startOfDay();
        $today = now()->startOfDay();

        // Vacation Leave: 5 days in advance when possible
        if ($leaveType->code === 'VL') {
            $diff = $today->diffInDays($start, false);
            if ($diff < 5) {
                return back()
                    ->withInput()
                    ->withErrors(['dates' => 'Vacation Leave should be filed at least 5 days in advance when possible.']);
            }
        }

        // Sick Leave filed in advance: require attachment
        if ($leaveType->code === 'SL') {
            if ($start->isFuture() && !$request->hasFile('attachments')) {
                return back()
                    ->withInput()
                    ->withErrors(['attachments' => 'Sick Leave filed in advance requires supporting document (e.g., medical certificate).']);
            }
        }

        // Required documents evaluator
        $payload = [
            'working_days_requested' => (float)$validated['working_days_requested'],
            'filed_in_advance' => $start->isFuture(),
            'details' => $details,
        ];

        $requiredDocs = app(RequiredDocsEvaluator::class)->requiredDocsFor($leaveType, $payload);

        // MVP: if any required docs exist, require at least one uploaded attachment
        if (!empty($requiredDocs) && !$request->hasFile('attachments')) {
            $names = collect($requiredDocs)->pluck('name')->join(', ');
            return back()
                ->withInput()
                ->withErrors(['attachments' => "Required document(s) missing: {$names}"]);
        }

        $leave = DB::transaction(function () use ($validated, $user, $request, $details, $start_date, $end_date) {

            $leave = LeaveApplication::create([
                'employee_id' => $user->employee->id,
                'office_id' => $user->employee->office_id,
                'leave_type_id' => $validated['leave_type_id'],

                'date_filed' => now()->toDateString(),
                'start_date' => $start_date,
                'end_date' => $end_date,
                'working_days_requested' => $validated['working_days_requested'],

                'status' => 'pending',
                'current_step_order' => 1,

                'details_json' => $details ?: null,
                'commutation' => $validated['commutation'] ?? null,
            ]);

            // Attachments (public disk)
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    if (!$file) continue;

                    $path = $file->store("leave_attachments/{$leave->id}", 'public');

                    LeaveAttachment::create([
                        'leave_application_id' => $leave->id,
                        'file_path' => $path,
                        'original_name' => $file->getClientOriginalName(),
                        'mime_type' => $file->getClientMimeType(),
                        'size' => $file->getSize(),
                        'uploaded_by' => $user->id,
                    ]);
                }
            }

            return $leave;
        });

        return redirect()
            ->route('employee.leaves.show', $leave->id)
            ->with('status', 'Leave application submitted.');
    }

    /**
     * AJAX endpoint used by the Apply Leave form to show required docs hint.
     */
    public function requiredDocs(Request $request): JsonResponse
    {
        $request->validate([
            'leave_type_id' => 'required|exists:leave_types,id',
            'working_days_requested' => 'nullable|numeric|min:0.5|max:365',
            'dates' => 'nullable|string', // Handling flatpickr string
            'details' => 'nullable|array',
        ]);

        $leaveType = LeaveType::with('requiredDocuments')->findOrFail($request->leave_type_id);

        $details = $request->input('details', []);
        $details['abroad'] = !empty($details['abroad']);
        $details['no_consultation'] = !empty($details['no_consultation']);

        $filedInAdvance = false;
        if ($request->filled('dates')) {
            // Check based on the earliest selected date
            $datesArray = array_filter(array_map('trim', explode(',', $request->dates)));
            if (count($datesArray) > 0) {
                $dates = collect($datesArray)->map(fn($d) => \Carbon\Carbon::parse($d))->sort();
                $filedInAdvance = $dates->first()->startOfDay()->isFuture();
            }
        }

        $payload = [
            'working_days_requested' => (float)($request->working_days_requested ?? 0),
            'filed_in_advance' => $filedInAdvance,
            'details' => $details,
        ];

        $requiredDocs = app(RequiredDocsEvaluator::class)->requiredDocsFor($leaveType, $payload);

        return response()->json([
            'leave_type' => [
                'id' => $leaveType->id,
                'code' => $leaveType->code,
                'name' => $leaveType->name,
            ],
            'required_docs' => $requiredDocs,
        ]);
    }
}
