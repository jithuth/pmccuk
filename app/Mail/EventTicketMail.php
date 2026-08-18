<?php

namespace App\Mail;

use App\Models\EventBooking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Barryvdh\DomPDF\Facade\Pdf;

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
        $refNo = $this->booking->reference_no ?: ("BOOK-" . ($this->booking->membership_no != 'NON-MEMBER' ? $this->booking->membership_no : 'NM') . "-" . $this->booking->id);
        $verificationUrl = route('event.verify-ticket', ['reference' => $refNo]);
        $qrCodeUrl = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($verificationUrl);

        $mailable = $this->subject('Your Entry Ticket - ' . ($this->booking->event->title ?? 'PMCC Event'))
            ->view('emails.event_ticket')
            ->with([
                'qrCodeUrl' => $qrCodeUrl,
                'verificationUrl' => $verificationUrl
            ]);

        // Generate PDF ticket attachment
        try {
            $pdf = Pdf::loadView('events.pdf_ticket', ['booking' => $this->booking])
                ->setPaper([0, 0, 396, 252], 'landscape')
                ->setOption('isRemoteEnabled', true);

            $mailable->attachData($pdf->output(), "PMCC_Event_Ticket_{$refNo}.pdf", [
                'mime' => 'application/pdf',
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Failed to attach PDF to EventTicketMail: " . $e->getMessage());
        }

        return $mailable;
    }
}
