<?php

namespace App\Services\Quotes;

use App\Mail\QuoteNotificationMail;
use App\Mail\QuoteReceivedMail;
use App\Models\Quote;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class QuoteEmailService
{
    public function send(Quote $quote): void
    {
        $quote->loadMissing('items');

        $this->sendCustomerConfirmation($quote);
        $this->sendInternalNotification($quote);
    }

    private function sendCustomerConfirmation(Quote $quote): void
    {
        try {
            Mail::to($quote->customer_email)->send(new QuoteReceivedMail($quote));
        } catch (\Throwable $e) {
            Log::warning('Quote confirmation email failed', [
                'quote_id' => $quote->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function sendInternalNotification(Quote $quote): void
    {
        $recipients = $this->notificationRecipients();

        if ($recipients === []) {
            return;
        }

        try {
            Mail::to($recipients)->send(new QuoteNotificationMail($quote));
        } catch (\Throwable $e) {
            Log::warning('Quote internal notification email failed', [
                'quote_id' => $quote->id,
                'recipients' => $recipients,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function notificationRecipients(): array
    {
        $configuredRecipients = config('quotes.notification_emails', []);

        if (is_string($configuredRecipients)) {
            $configuredRecipients = preg_split('/[,;]+/', $configuredRecipients) ?: [];
        }

        return collect($configuredRecipients)
            ->flatMap(fn ($email) => is_array($email) ? $email : preg_split('/[,;]+/', (string) $email))
            ->map(fn ($email) => trim((string) $email))
            ->filter(fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL))
            ->unique()
            ->values()
            ->all();
    }
}
