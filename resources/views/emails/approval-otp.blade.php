<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f4f4f4; margin: 0; padding: 0; }
        .wrapper { width: 100%; table-layout: fixed; background-color: #f4f4f4; padding-bottom: 40px; }
        .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }

        .header { background-color: #1e4226; padding: 30px 20px; text-align: center; color: #ffffff; }
        .logo-profile { height: 80px; width: 80px; border-radius: 50%; background-color: #ffffff; padding: 3px; object-fit: contain; box-shadow: 0 2px 4px rgba(0,0,0,0.2); margin-bottom: 15px; }

        .otp-display { background-color: #f8f9fa; padding: 20px; text-align: center; border-radius: 8px; margin: 20px 0; border: 2px dashed #0d6efd; }
        .otp-code { font-size: 32px; font-weight: bold; color: #0d6efd; letter-spacing: 8px; margin: 10px 0; }

        .content { padding: 30px; }
        .footer { background-color: #f8f9fa; padding: 15px; text-align: center; font-size: 11px; color: #999; border-top: 1px solid #eee; }
        .warning-box { background-color: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin-top: 20px; border-radius: 4px; }

        .details-table { width: 100%; border-collapse: collapse; margin-top: 15px; margin-bottom: 20px; }
        .details-table td { padding: 8px 0; border-bottom: 1px solid #eee; font-size: 14px; }
        .details-table td:first-child { color: #666; width: 35%; }
        .details-table td:last-child { font-weight: bold; color: #333; }
        .details-table tr:last-child td { border-bottom: none; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container">

            <div class="header">
                <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/e/e8/Logo_of_the_Department_of_Environment_and_Natural_Resources.svg/1280px-Logo_of_the_Department_of_Environment_and_Natural_Resources.svg.png" alt="DENR" class="logo-profile">
                <h2 style="margin: 0; font-size: 20px;">OTP Verification Required</h2>
                <div style="font-size: 13px; opacity: 0.9;">Leave Application Information System</div>
            </div>

            <div class="content">
                <p>Hi <strong>{{ $userName }}</strong>,</p>
                <p>You are reviewing a leave application that requires OTP verification to complete your approval.</p>

                <table class="details-table">
                    <tr>
                        <td>Application ID</td>
                        <td>#{{ $leaveId }}</td>
                    </tr>
                    <tr>
                        <td>Applicant</td>
                        <td>{{ $applicantName }}</td>
                    </tr>
                    <tr>
                        <td>Leave Type</td>
                        <td>{{ $leaveType }}</td>
                    </tr>
                </table>

                <div class="otp-display">
                    <div style="font-size: 14px; color: #666; margin-bottom: 10px;">Your One-Time Password (OTP):</div>
                    <div class="otp-code">{{ $otpCode }}</div>
                    <div style="font-size: 12px; color: #999; margin-top: 10px;">Enter this code in the approval form to complete your verification.</div>
                </div>

                <div class="warning-box">
                    <strong style="color: #856404;">⚠ Important Security Notice:</strong>
                    <ul style="margin: 10px 0 0 20px; padding: 0; color: #666; font-size: 13px;">
                        <li>This OTP will expire in <strong>5 minutes</strong> (at {{ $expiresAt }})</li>
                        <li>This code can only be used once</li>
                        <li>Never share this code with anyone</li>
                        <li>If you didn't request this OTP, please ignore this email</li>
                    </ul>
                </div>

                <p style="margin-top: 20px; font-size: 13px; color: #666;">
                    If you have any questions or concerns, please contact the system administrator.
                </p>
            </div>

            <div class="footer">
                &copy; {{ date('Y') }} DENR - CAR. All rights reserved.<br>
                This is an automated notification. Please do not reply.
            </div>
        </div>
    </div>
</body>
</html>