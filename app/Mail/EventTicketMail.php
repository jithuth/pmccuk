<?php

namespace App\Mail;

use App\Models\EventBooking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EventTicketMail extends Mailable
{
    use Queueable, SerializesModels;

    public $booking;

    public function __construct(EventBooking $booking)
    {
        $this->booking = $booking;
    }

    public function build()
    {
        // QR Code will be generated using an API for simplicity
        $verificationUrl = route('event.verify-ticket', ['reference' => $this->booking->reference_no]);
        $qrCodeUrl = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($verificationUrl);

        return $this->subject('Your Entry Ticket - ' . $this->booking->event->title)
                    ->view('emails.event_ticket')
                    ->with([
                        'qrCodeUrl' => $qrCodeUrl,
                        'verificationUrl' => $verificationUrl
                    ]);
    }
}
