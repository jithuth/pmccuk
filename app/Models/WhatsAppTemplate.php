<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WhatsAppTemplate extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_templates';

    protected $fillable = [
        'name',
        'slug',
        'category',
        'header_type',
        'header_content',
        'body',
        'footer',
        'buttons',
        'sample_data',
        'is_system',
        'created_by',
    ];

    protected $casts = [
        'buttons' => 'array',
        'sample_data' => 'array',
        'is_system' => 'boolean',
    ];

    public function creator()
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    /**
     * Render the template body with replaced dynamic variables.
     */
    public function renderBody(array $variables = []): string
    {
        $rendered = $this->body;
        $defaults = [
            'name' => 'Community Member',
            'first_name' => 'Member',
            'phone' => '+44 7901 296858',
            'membership_id' => 'PMCC-2026-089',
            'event_title' => 'PMCC Grand Cultural Fest 2026',
            'date' => date('d M Y'),
            'time' => '10:00 AM',
            'venue' => 'Plymouth Guildhall, Armada Way',
            'booking_ref' => 'BOOK-7842',
            'link' => url('/'),
            'year' => date('Y'),
        ];

        $merged = array_merge($defaults, $this->sample_data ?: [], $variables);

        foreach ($merged as $key => $val) {
            $rendered = str_ireplace('{' . $key . '}', (string) $val, $rendered);
        }

        return $rendered;
    }

    /**
     * Compile complete WhatsApp message payload ready for dispatch.
     */
    public function compileFullWhatsAppMessage(array $variables = []): string
    {
        $lines = [];

        // Header
        if ($this->header_type === 'text' && !empty($this->header_content)) {
            $lines[] = "*{$this->header_content}*";
            $lines[] = "";
        }

        // Body
        $lines[] = $this->renderBody($variables);

        // Footer
        if (!empty($this->footer)) {
            $lines[] = "";
            $lines[] = "_{$this->footer}_";
        }

        // Action Buttons / Links as WhatsApp footer links
        if (!empty($this->buttons) && is_array($this->buttons)) {
            $buttonLines = [];
            foreach ($this->buttons as $btn) {
                $type = $btn['type'] ?? 'url';
                $text = $btn['text'] ?? 'Open Link';
                $val = $btn['value'] ?? ($btn['url'] ?? ($btn['phone'] ?? ''));

                if ($type === 'url') {
                    $buttonLines[] = "👉 *{$text}*: {$val}";
                } elseif ($type === 'call') {
                    $buttonLines[] = "📞 *{$text}*: {$val}";
                } elseif ($type === 'quick_reply') {
                    $buttonLines[] = "💬 [{$text}]";
                }
            }

            if (!empty($buttonLines)) {
                $lines[] = "";
                $lines[] = implode("\n", $buttonLines);
            }
        }

        return implode("\n", $lines);
    }
}
