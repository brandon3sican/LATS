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
use App\Models\{LeaveApplication, ApprovalStep, User};
use Illuminate\Support\Facades\Mail;
use App\Mail\{LeaveActionRequired, LeaveStatusUpdated};

class LeaveController extends Controller
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
            'approvals.approver',
            'employee.user',
        ])->findOrFail($id);

        $steps = ApprovalStep::where('office_id', $leave->office_id)
            ->orderBy('step_order')
            ->get();

        $approvalsByStep = $leave->approvals->keyBy('step_order');

        $timeline = $steps->map(function ($step) use ($leave, $approvalsByStep) {
            $ap = $approvalsByStep->get($step->step_order);

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
            'dates' => 'required|string',
            'working_days_requested' => 'required|numeric|min:0.5|max:365',
            'reason' => 'required|string|max:2000',
            'commutation' => 'nullable|string|max:50',
            'details' => 'nullable|array',
            'attachments' => ['nullable', 'array', 'max:10'],
            'attachments.*' => ['file', 'max:5120', 'mimes:pdf,doc,docx,jpg,jpeg,png'],
        ]);

        $leaveType = LeaveType::with('requiredDocuments')->findOrFail($validated['leave_type_id']);

        $datesArray = array_filter(array_map('trim', explode(',', $validated['dates'])));
        if (empty($datesArray)) {
            return back()->withInput()->withErrors(['dates' => 'Please select at least one date.']);
        }

        $dates = collect($datesArray)->map(fn($d) => Carbon::parse($d))->sort()->values();
        $start_date = $dates->first()->toDateString();
        $end_date = $dates->last()->toDateString();

        $overlappingLeave = LeaveApplication::where('employee_id', $user->employee->id)
            ->whereNotIn('status', ['disapproved', 'cancelled', 'rejected'])
            ->where(function ($query) use ($start_date, $end_date) {
                $query->where('start_date', '<=', $end_date)
                      ->where('end_date', '>=', $start_date);
            })
            ->first();

        if ($overlappingLeave) {
            return back()
                ->withInput()
                ->withErrors(['dates' => 'You already have an existing or pending leave application that overlaps with the selected dates.']);
        }

        $details = $validated['details'] ?? [];
        $details['abroad'] = !empty($details['abroad']);
        $details['no_consultation'] = !empty($details['no_consultation']);
        $details['reason'] = $validated['reason'];
        $details['selected_dates'] = $dates->map->toDateString()->toArray();

        $start = Carbon::parse($start_date)->startOfDay();
        $today = now()->startOfDay();

        if ($leaveType->code === 'VL') {
            $diff = $today->diffInDays($start, false);
            if ($diff < 5) {
                return back()
                    ->withInput()
                    ->withErrors(['dates' => 'Vacation Leave should be filed at least 5 days in advance when possible.']);
            }
        }

        if ($leaveType->code === 'WL') {
            $requiredFilingDate = now()->addDays(5)->startOfDay();
            if ($start->lessThan($requiredFilingDate)) {
                return back()
                    ->withInput()
                    ->withErrors(['dates' => 'Wellness Leave requires at least 5 days advance notice.']);
            }
        }

        if ($leaveType->code === 'SL') {
            if ($validated['working_days_requested'] > 5 && !$request->hasFile('attachments')) {
                return back()
                    ->withInput()
                    ->withErrors(['attachments' => 'Sick Leave filed in advance requires supporting document (e.g., medical certificate).']);
            }
        }

        $payload = [
            'working_days_requested' => (float)$validated['working_days_requested'],
            'filed_in_advance' => $start->isFuture(),
            'details' => $details,
        ];

        $requiredDocs = app(RequiredDocsEvaluator::class)->requiredDocsFor($leaveType, $payload);

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

        // -------------------------------------------------------------
        // NOTIFY THE FIRST APPROVER (STEP 1)
        // -------------------------------------------------------------
        try {
            $step1 = ApprovalStep::where('office_id', $leave->office_id)->where('step_order', 1)->first();

            if ($step1) {
                $approversQuery = User::whereHas('roles', function($q) use ($step1) {
                    $q->where('key', $step1->role_key);
                })->whereHas('employee', function($q) use ($leave) {
                    $q->where('office_id', $leave->office_id);
                });

                // Only alert their specific Division Chief
                if ($step1->role_key === 'approver_division_chief') {
                    $approversQuery->whereHas('employee', function($q) use ($leave) {
                        $q->where('division_id', $leave->employee->division_id);
                    });
                }

                $approvers = $approversQuery->get();
                foreach ($approvers as $approver) {
                    Mail::to($approver->email)->send(
                        new LeaveActionRequired($leave, 'new_application')
                    );

                    $approver->notify(new \App\Notifications\SystemLeaveNotification($leave, 'New leave application requires your action.', route('approver.leaves.show', $leave->id)));
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Approver notification failed: ' . $e->getMessage());
        }

        return redirect()
            ->route('employee.leaves.show', $leave->id)
            ->with('status', 'Leave application submitted.');
    }

    public function requiredDocs(Request $request): JsonResponse
    {
        $request->validate([
            'leave_type_id' => 'required|exists:leave_types,id',
            'working_days_requested' => 'nullable|numeric|min:0.5|max:365',
            'dates' => 'nullable|string',
            'details' => 'nullable|array',
        ]);

        $leaveType = LeaveType::with('requiredDocuments')->findOrFail($request->leave_type_id);

        $details = $request->input('details', []);
        $details['abroad'] = !empty($details['abroad']);
        $details['no_consultation'] = !empty($details['no_consultation']);

        $filedInAdvance = false;
        if ($request->filled('dates')) {
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

    public function requestCancellation(Request $request, int $id)
    {
        $request->validate(['cancellation_reason' => 'required|string|max:1000']);
        $user = $request->user()->loadMissing('employee');

        $leave = LeaveApplication::where('employee_id', $user->employee->id)->findOrFail($id);

        if ($leave->status === 'cancelled' || $leave->cancellation_status === 'pending') {
            return back()->withErrors(['cancellation_reason' => 'Cancellation already requested or processed.']);
        }

        $leave->cancellation_status = 'pending';
        $leave->cancellation_reason = $request->input('cancellation_reason');
        $leave->save();

        // Email to EMPLOYEE (Confirmation)
        try {
            Mail::to($user->email)->send(
                new LeaveStatusUpdated($leave, 'Reason provided: ' . $request->input('cancellation_reason'), null, 'CANCELLATION REQUESTED')
            );
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Cancellation request email to employee failed: ' . $e->getMessage());
        }

        // -------------------------------------------------------------
        // NOTIFY PERSONNEL ABOUT THE CANCELLATION REQUEST
        // -------------------------------------------------------------
        try {
            $personnelApprovers = User::whereHas('roles', function($q) {
                $q->where('key', 'approver_personnel');
            })->whereHas('employee', function($q) use ($leave) {
                $q->where('office_id', $leave->office_id);
            })->get();

            foreach ($personnelApprovers as $approver) {
                Mail::to($approver->email)->send(
                    new LeaveActionRequired($leave, 'cancellation_request', $request->input('cancellation_reason'))
                );

                $approver->notify(new \App\Notifications\SystemLeaveNotification($leave, 'Cancellation requested for approved leave.', route('approver.leaves.show', $leave->id)));
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Cancellation request email to approver failed: ' . $e->getMessage());
        }

        return back()->with('status', 'Cancellation request submitted to Personnel.');
    }
}
