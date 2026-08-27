<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f4f4f4; margin: 0; padding: 0; }
        .wrapper { width: 100%; table-layout: fixed; background-color: #f4f4f4; padding-bottom: 40px; }
        .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }

        .header { background-color: #1e4226; padding: 30px 20px; text-align: center; color: #ffffff; }
        .logo-profile { height: 80px; width: 80px; border-radius: 50%; background-color: #ffffff; padding: 3px; object-fit: contain; box-shadow: 0 2px 4px rgba(0,0,0,0.2); margin-bottom: 15px; }

        .status { font-weight: bold; padding: 5px 10px; border-radius: 4px; display: inline-block; color: white; font-size: 12px; }
        .approved { background-color: #28a745; }
        .disapproved { background-color: #dc3545; }
        .returned { background-color: #17a2b8; }
        .pending { background-color: #ffc107; color: #333; }
        .cancelled { background-color: #6c757d; }

        .content { padding: 30px; }
        .footer { background-color: #f8f9fa; padding: 15px; text-align: center; font-size: 11px; color: #999; border-top: 1px solid #eee; }
        .remarks-box { background-color: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin-top: 20px; border-radius: 4px; }

        .details-table { width: 100%; border-collapse: collapse; margin-top: 15px; margin-bottom: 20px; }
        .details-table td { padding: 8px 0; border-bottom: 1px solid #eee; font-size: 14px; }
        .details-table td:first-child { color: #666; width: 35%; }
        .details-table td:last-child { font-weight: bold; color: #333; }
        .details-table tr:last-child td { border-bottom: none; }

        .btn { display: inline-block; padding: 10px 20px; background-color: #0d6efd; color: #ffffff; text-decoration: none; border-radius: 5px; font-weight: bold; margin-top: 10px; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container">

            <div class="header">
                <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/e/e8/Logo_of_the_Department_of_Environment_and_Natural_Resources.svg/1280px-Logo_of_the_Department_of_Environment_and_Natural_Resources.svg.png" alt="DENR" class="logo-profile">
                <h2 style="margin: 0; font-size: 20px;">Application Status Update</h2>
                <div style="font-size: 13px; opacity: 0.9;">Leave Application Information System</div>
            </div>

            <div class="content">
                <p>Hi <strong>{{ $leave->employee->user->first_name }}</strong>,</p>
                <p>Your leave application has been updated.</p>

                @php
                    $displayStatus = $customStatus ?? ($leave->status === 'pending' ? 'IN PROGRESS' : strtoupper($leave->status));
                    $colorClass = match(true) {
                        str_contains(strtolower($displayStatus), 'approved') => 'approved',
                        str_contains(strtolower($displayStatus), 'rejected') || str_contains(strtolower($displayStatus), 'disapproved') => 'disapproved',
                        str_contains(strtolower($displayStatus), 'progress') || str_contains(strtolower($displayStatus), 'request') => 'pending',
                        str_contains(strtolower($displayStatus), 'cancelled') => 'cancelled',
                        default => 'returned'
                    };
                @endphp

                <table class="details-table">
                    <tr>
                        <td>Date Filed</td>
                        <td>{{ $leave->date_filed->format('M d, Y') }}</td>
                    </tr>
                    <tr>
                        <td>Leave Type</td>
                        <td>{{ $leave->leaveType->name }}</td>
                    </tr>
                    <tr>
                        <td>Status</td>
                        <td><span class="status {{ $colorClass }}">{{ $displayStatus }}</span></td>
                    </tr>

                    @if(!in_array($displayStatus, ['CANCELLED', 'CANCELLATION REQUESTED', 'CANCELLATION REJECTED']))
                    <tr>
                        <td>Progress</td>
                        <td>Step {{ $leave->status === 'approved' ? $totalSteps : $leave->current_step_order }} of {{ $totalSteps }}</td>
                    </tr>
                    @endif

                    @if($approverName)
                    <tr>
                        <td>Action By</td>
                        <td>{{ $approverName }}</td>
                    </tr>
                    @endif
                </table>

                @if(!empty($remarks))
                    <div class="remarks-box">
                        <strong style="color: #856404;">Remarks / Details:</strong><br>
                        {{ $remarks }}
                    </div>
                @endif

                {{-- Dynamic Message based on status --}}
                @if($displayStatus === 'CANCELLED')
                    <p style="color: #6c757d; font-weight: bold;">✓ Your leave application cancellation has been approved.</p>
                @elseif($displayStatus === 'CANCELLATION REJECTED')
                    <p style="color: #dc3545; font-weight: bold;">⚠ Your request to cancel this leave application was rejected. Your leave remains active.</p>
                @elseif($displayStatus === 'CANCELLATION REQUESTED')
                    <p style="color: #0d6efd; font-weight: bold;">ⓘ Your request to cancel this leave application has been successfully submitted to the Personnel for review.</p>
                @elseif($leave->status === 'approved')
                    <p style="color: #28a745; font-weight: bold;">✓ Your application is fully approved.</p>
                @elseif($leave->status === 'pending')
                    <p style="color: #0d6efd; font-weight: bold;">ⓘ Your application was approved by {{ $approverName }} and has been forwarded to the next step.</p>
                @endif

                <div style="text-align: center; margin-top: 30px;">
                    <a href="{{ route('employee.leaves.show', $leave->id) }}" class="btn">View Full Details</a>
                </div>
            </div>

            <div class="footer">
                &copy; {{ date('Y') }} DENR - CAR. All rights reserved.<br>
                This is an automated notification. Please do not reply.
            </div>
        </div>
    </div>
</body>
</html>
