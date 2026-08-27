<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f4f4f4; margin: 0; padding: 0; }
        .wrapper { width: 100%; table-layout: fixed; background-color: #f4f4f4; padding-bottom: 40px; }
        .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .header { background-color: #1e4226; padding: 30px 20px; text-align: center; color: #ffffff; }
        .logo-profile { height: 80px; width: 80px; border-radius: 50%; background-color: #ffffff; padding: 3px; object-fit: contain; box-shadow: 0 2px 4px rgba(0,0,0,0.2); margin-bottom: 15px; }
        .content { padding: 30px; }
        .footer { background-color: #f8f9fa; padding: 15px; text-align: center; font-size: 11px; color: #999; border-top: 1px solid #eee; }
        .details-table { width: 100%; border-collapse: collapse; margin-top: 15px; margin-bottom: 20px; }
        .details-table td { padding: 8px 0; border-bottom: 1px solid #eee; font-size: 14px; }
        .details-table td:first-child { color: #666; width: 35%; }
        .details-table td:last-child { font-weight: bold; color: #333; }
        .details-table tr:last-child td { border-bottom: none; }
        .btn { display: inline-block; padding: 10px 20px; background-color: #0d6efd; color: #ffffff; text-decoration: none; border-radius: 5px; font-weight: bold; margin-top: 10px; }
        .alert-box { background-color: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin-top: 20px; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container">
            <div class="header">
                <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/e/e8/Logo_of_the_Department_of_Environment_and_Natural_Resources.svg/1280px-Logo_of_the_Department_of_Environment_and_Natural_Resources.svg.png" alt="DENR" class="logo-profile">
                <h2 style="margin: 0; font-size: 20px;">Action Required</h2>
                <div style="font-size: 13px; opacity: 0.9;">Leave Application Information System</div>
            </div>

            <div class="content">
                <p>Hello,</p>

                @if($actionType === 'cancellation_request')
                    <p>An employee has submitted a request to <strong>cancel</strong> their leave application. Your review is required.</p>
                @else
                    <p>A new leave application has been submitted and is waiting for your approval.</p>
                @endif

                <table class="details-table">
                    <tr>
                        <td>Applicant</td>
                        <td>{{ $leave->employee->user->name }}</td>
                    </tr>
                    <tr>
                        <td>Leave Type</td>
                        <td>{{ $leave->leaveType->name }}</td>
                    </tr>
                    <tr>
                        <td>Dates Requested</td>
                        <td>{{ \Carbon\Carbon::parse($leave->start_date)->format('M d, Y') }} - {{ \Carbon\Carbon::parse($leave->end_date)->format('M d, Y') }}</td>
                    </tr>
                </table>

                @if($actionType === 'cancellation_request' && !empty($reason))
                    <div class="alert-box">
                        <strong style="color: #856404;">Reason for Cancellation:</strong><br>
                        {{ $reason }}
                    </div>
                @endif

                <div style="text-align: center; margin-top: 30px;">
                    <a href="{{ route('approver.inbox') }}" class="btn">Go to Inbox to Review</a>
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
