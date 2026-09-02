<?php

namespace App\Mail;

use App\Models\Quote;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class QuoteReceivedMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public Quote $quote)
    {
        $this->quote->loadMissing('items');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Lumens recibió tu ' . strtolower($this->quote->request_type_label) . ' ' . $this->quote->quote_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.quote-received',
        );
    }
}
