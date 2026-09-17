<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class ApprovalOtpNotification extends Notification
{
    use Queueable;

    protected $otpCode;
    protected $leaveId;
    protected $applicantName;
    protected $leaveType;
    protected $expiresAt;

    public function __construct(string $otpCode, int $leaveId, string $applicantName, string $leaveType, string $expiresAt)
    {
        $this->otpCode = $otpCode;
        $this->leaveId = $leaveId;
        $this->applicantName = $applicantName;
        $this->leaveType = $leaveType;
        $this->expiresAt = $expiresAt;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Your OTP for Leave Application Approval')
            ->markdown('emails.approval-otp', [
                'otpCode' => $this->otpCode,
                'leaveId' => $this->leaveId,
                'applicantName' => $this->applicantName,
                'leaveType' => $this->leaveType,
                'expiresAt' => $this->expiresAt,
                'userName' => $notifiable->name,
            ]);
    }
}