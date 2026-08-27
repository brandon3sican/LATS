<?php

namespace App\Mail;

use App\Models\LeaveApplication;
use App\Models\ApprovalStep;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LeaveStatusUpdated extends Mailable
{
    use Queueable, SerializesModels;

    public $leave;
    public $remarks;
    public $approverName;
    public $totalSteps;
    public $customStatus;

    public function __construct(LeaveApplication $leave, $remarks = null, $approverName = null, $customStatus = null)
    {
        $this->leave = $leave;
        $this->remarks = $remarks;
        $this->approverName = $approverName;
        $this->customStatus = $customStatus;

        $this->totalSteps = ApprovalStep::where('office_id', $leave->office_id)->count();
    }

    public function envelope(): Envelope
    {
        // Use custom status if provided, otherwise use the database status
        $status = $this->customStatus ?? strtoupper($this->leave->status);

        if ($status === 'PENDING' && !$this->customStatus) {
            $status = 'IN PROGRESS';
        }

        return new Envelope(
            subject: "Leave Application Update: #{$this->leave->id} - {$status}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.leave_status_updated',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
