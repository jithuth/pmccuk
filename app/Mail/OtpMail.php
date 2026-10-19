<?php namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public $name;
    public $otp;
    public $purpose;
    public $expiryMinutes;

    /**
     * Create a new message instance.
     */
    public function __construct(string $name, string $otp, string $purpose = 'login', int $expiryMinutes = 5)
    {
        $this->name = $name;
        $this->otp = $otp;
        $this->purpose = $purpose;
        $this->expiryMinutes = $expiryMinutes;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): \Illuminate\Mail\Mailables\Envelope
    {
        $subject = ($this->purpose === 'renewal')
            ? 'Verification Code for PMCC Membership Renewal'
            : 'Your PMCC Member Login Verification Code';

        return new \Illuminate\Mail\Mailables\Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): \Illuminate\Mail\Mailables\Content
    {
        return new \Illuminate\Mail\Mailables\Content(
            view: 'emails.otp',
            with: [
                'name' => $this->name,
                'otp' => $this->otp,
                'purpose' => $this->purpose,
                'expiryMinutes' => $this->expiryMinutes,
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
