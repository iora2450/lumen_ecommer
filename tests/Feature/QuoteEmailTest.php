<?php

namespace Tests\Feature;

use App\Mail\QuoteNotificationMail;
use App\Mail\QuoteReceivedMail;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class QuoteEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_quote_sends_confirmation_and_internal_notification(): void
    {
        Mail::fake();
        config(['quotes.notification_emails' => ['douglas.manzanares@lumens.com.sv']]);

        $product = Product::create([
            'sku' => 'QUOTE-TEST-SKU',
            'name' => 'Panel LED de prueba',
            'price' => 25.50,
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/quotes', [
            'customer_name' => 'Cliente Prueba',
            'customer_email' => 'cliente@example.com',
            'customer_phone' => '2222-3333',
            'customer_company' => 'Empresa Prueba',
            'notes' => 'Enviar disponibilidad.',
            'items' => [
                [
                    'product_id' => $product->id,
                    'qty' => 2,
                ],
            ],
        ]);

        $response->assertCreated();

        Mail::assertSent(QuoteReceivedMail::class, function (QuoteReceivedMail $mail) {
            return $mail->hasTo('cliente@example.com');
        });

        Mail::assertSent(QuoteNotificationMail::class, function (QuoteNotificationMail $mail) {
            return $mail->hasTo('douglas.manzanares@lumens.com.sv')
                && $mail->hasReplyTo('cliente@example.com');
        });
    }
}
