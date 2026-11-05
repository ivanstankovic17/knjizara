<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class OrderConfirmation extends Mailable
{
    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Knjižara: porudžbina {$this->order->id} je primljena");
    }

    public function content(): Content
    {
        return new Content(text: 'mail.order-confirmation');
    }
}
