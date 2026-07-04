<?php namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MembershipSubmittedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $full_name;
    public $bank_name;
    public $bank_account_name;
    public $bank_sort_code;
    public $bank_account_no;

    /**
     * Create a new message instance.
     */
    public function __construct($full_name)
    {
        $this->full_name = $full_name;
        
        try {
            $this->bank_name = \App\Models\Setting::where('setting_key', 'bank_name')->value('setting_value') ?? 'Barclays Bank';
            $this->bank_account_name = \App\Models\Setting::where('setting_key', 'bank_account_name')->value('setting_value') ?? 'PMCC-UK';
            $this->bank_sort_code = \App\Models\Setting::where('setting_key', 'bank_sort_code')->value('setting_value') ?? 'XX-XX-XX';
            $this->bank_account_no = \App\Models\Setting::where('setting_key', 'bank_account_no')->value('setting_value') ?? 'XXXXXXXX';
        } catch (\Exception $e) {
            $this->bank_name = 'Barclays Bank';
            $this->bank_account_name = 'PMCC-UK';
            $this->bank_sort_code = 'XX-XX-XX';
            $this->bank_account_no = 'XXXXXXXX';
        }
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Membership Application Received - PMCC-UK',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.membership_submitted',
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}
