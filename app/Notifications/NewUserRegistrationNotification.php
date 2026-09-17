<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\User;

class NewUserRegistrationNotification extends Notification
{
    use Queueable;

    protected $newUser;

    public function __construct(User $newUser)
    {
        $this->newUser = $newUser;
    }

    public function via($notifiable)
    {
        return ['database']; // Stores the notification in the database
    }

    public function toDatabase($notifiable)
    {
        $employee = $this->newUser->employee;

        return [
            'user_id' => $this->newUser->id,
            'employee_id' => $employee ? $employee->id : null,
            'user_name' => $this->newUser->name,
            'user_email' => $this->newUser->email,
            'message' => "New user registered: {$this->newUser->name}",
            'url' => $employee ? route('super.users.show', $employee->id) : null,
        ];
    }
}
