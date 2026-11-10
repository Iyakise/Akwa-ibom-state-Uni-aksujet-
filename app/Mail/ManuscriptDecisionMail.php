<?php

namespace App\Mail;

use App\Models\Manuscript;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ManuscriptDecisionMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Manuscript $manuscript, public string $decision, public string $letter, public string $reason) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Editorial update: '.$this->manuscript->reference);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.manuscript-decision');
    }
}
