<?php

namespace App\Mail;

use App\Models\Quote;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class QuoteNotificationMail extends Mailable
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
            replyTo: [new Address($this->quote->customer_email, $this->quote->customer_name)],
            subject: 'Nueva '.strtolower($this->quote->request_type_label).' '.$this->quote->quote_number.' - '.$this->quote->customer_name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.quote-notification',
        );
    }
}
