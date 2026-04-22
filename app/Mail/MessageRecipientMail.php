<?php

namespace App\Mail;

use App\Models\MessageRecipient;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MessageRecipientMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public MessageRecipient $messageRecipient,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->messageRecipient->rendered_subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.message-recipient',
        );
    }
}
