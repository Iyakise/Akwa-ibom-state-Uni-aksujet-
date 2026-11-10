<?php

namespace App\Mail;

use App\Models\Manuscript;
use App\Models\ReviewAssignment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReviewInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Manuscript $manuscript, public ReviewAssignment $assignment) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Aksujet peer-review invitation: '.$this->manuscript->reference);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.review-invitation');
    }
}
