<?php

namespace App\Mail;

use App\Models\LeaveApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LeaveActionRequired extends Mailable
{
    use Queueable, SerializesModels;

    public $leave;
    public $actionType;
    public $reason;

    public function __construct(LeaveApplication $leave, $actionType, $reason = null)
    {
        $this->leave = $leave;
        $this->actionType = $actionType;
        $this->reason = $reason;
    }

    public function envelope(): Envelope
    {
        $subject = $this->actionType === 'cancellation_request'
            ? "Action Required: Leave Cancellation Request (#{$this->leave->id})"
            : "Action Required: New Leave Application (#{$this->leave->id})";

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.leave_action_required');
    }

    public function attachments(): array
    {
        return [];
    }
}
