<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SystemLeaveNotification extends Notification
{
    use Queueable;

    protected $leave;
    protected $message;
    protected $url;

    public function __construct($leave, $message, $url)
    {
        $this->leave = $leave;
        $this->message = $message;
        $this->url = $url;
    }

    public function via($notifiable)
    {
        return ['database']; // Stores the notification in the database
    }

    public function toDatabase($notifiable)
    {
        return [
            'leave_id' => $this->leave->id,
            'message' => $this->message,
            'applicant_name' => $this->leave->employee->user->name,
            'leave_type' => $this->leave->leaveType->code,
            'url' => $this->url,
        ];
    }
}
