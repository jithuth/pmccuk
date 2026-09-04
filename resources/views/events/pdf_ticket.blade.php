<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Event Ticket - {{ $booking->event->title ?? 'PMCC Event' }}</title>
    <style>
        @page {
            size: a5 portrait;
            margin: 0px;
        }
        * { box-sizing: border-box; }
        html, body { 
            font-family: 'Helvetica', 'Arial', sans-serif; 
            margin: 0px; 
            padding: 0px; 
            width: 100%; 
            height: 100%; 
            background-color: #ffffff; 
            overflow: hidden; 
        }
        
        .ticket-card { 
            width: 92%; 
            height: 540px; 
            position: relative; 
            background: #ffffff; 
            border: 2px solid #0f172a; 
            border-radius: 14px; 
            overflow: hidden; 
            margin: 12px auto; 
            page-break-inside: avoid;
        }
        
        .header { background: #0f172a; color: #ffffff; padding: 12px 15px; text-align: center; height: 75px; }
        .header-title { font-size: 14px; font-weight: bold; color: #ffffff; letter-spacing: 0.5px; text-transform: uppercase; line-height: 1.2; }
        .header-sub { font-size: 8px; color: #fbbf24; font-weight: bold; text-transform: uppercase; letter-spacing: 2px; margin-top: 3px; }
        
        .body-section { padding: 15px; text-align: center; height: 410px; position: relative; }
        .status-badge { background-color: #dcfce7; color: #166534; font-size: 10px; font-weight: bold; padding: 4px 12px; border-radius: 12px; display: inline-block; margin-bottom: 8px; text-transform: uppercase; }
        .event-name { font-size: 20px; font-weight: bold; color: #0f172a; margin-bottom: 3px; line-height: 1.1; }
        .event-date { font-size: 12px; color: #475569; margin-bottom: 10px; font-weight: bold; }
        
        .qr-box { background: #f8fafc; padding: 10px 15px; border-radius: 12px; border: 1.5px dashed #cbd5e1; display: inline-block; margin: 2px 0 10px 0; text-align: center; }
        .qr-img { width: 135px; height: 135px; }
        .ref-no { font-family: monospace; font-size: 13px; font-weight: bold; color: #0f172a; margin-top: 5px; letter-spacing: 0.5px; }
        
        .details-table { width: 100%; border-collapse: collapse; margin-top: 8px; border-top: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0; padding: 8px 0; text-align: left; }
        .label { font-size: 8px; color: #64748b; font-weight: bold; text-transform: uppercase; }
        .val { font-size: 12px; color: #0f172a; font-weight: bold; margin-top: 2px; }
        
        .footer { background: #f8fafc; padding: 10px 15px; text-align: center; font-size: 9px; color: #64748b; border-top: 1px solid #e2e8f0; position: absolute; bottom: 0; left: 0; right: 0; height: 50px; }
    </style>
</head>
<body>
    @php
        $refNo = $booking->reference_no ?: ("BOOK-" . ($booking->membership_no != 'NON-MEMBER' ? $booking->membership_no : 'NM') . "-" . $booking->id);
        $totalTickets = $booking->adult_count + $booking->child_count + $booking->infant_count + $booking->student_count;
        $eventTitle = $booking->event ? $booking->event->title : 'PMCC Event';
        $eventDate = $booking->event ? \Carbon\Carbon::parse($booking->event->event_date)->format('l, F j, Y') : 'N/A';
        $eventLocation = $booking->event ? $booking->event->location : 'Plymouth, UK';

        // Verification QR Code
        $verifyUrl = route('event.verify-ticket', ['reference' => $refNo]);
        $qrApiUrl = "https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=" . urlencode($verifyUrl);
        
        $qrBase64 = null;
        try {
            $qrContent = @file_get_contents($qrApiUrl);
            if ($qrContent) {
                $qrBase64 = 'data:image/png;base64,' . base64_encode($qrContent);
            }
        } catch (\Exception $e) {}
    @endphp

    <div class="ticket-card">
        <div class="header">
            <div class="header-title">PLYMOUTH MALAYALEE CULTURAL COMMUNITY</div>
            <div class="header-sub">OFFICIAL EVENT ENTRY TICKET</div>
        </div>

        <div class="body-section">
            <div class="status-badge">CONFIRMED ENTRY TICKET</div>

            <div class="event-name">{{ $eventTitle }}</div>
            <div class="event-date">Date: {{ $eventDate }}</div>

            <div class="qr-box">
                @if($qrBase64)
                    <img src="{{ $qrBase64 }}" class="qr-img">
                @else
                    <div style="font-size: 10px; padding: 50px 0; color: #666; font-weight: bold;">SCAN QR AT ENTRANCE</div>
                @endif
                <div class="ref-no">{{ $refNo }}</div>
            </div>

            <table class="details-table">
                <tr>
                    <td style="width: 50%; padding: 6px 0;">
                        <div class="label">Attendee Name</div>
                        <div class="val">{{ $booking->full_name }}</div>
                    </td>
                    <td style="width: 50%; padding: 6px 0; text-align: right;">
                        <div class="label">Total Attendees</div>
                        <div class="val">{{ $totalTickets }} Person(s)</div>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 6px 0;">
                        <div class="label">Membership No</div>
                        <div class="val" style="color: #d32f2f;">{{ $booking->membership_no ?: 'NON-MEMBER' }}</div>
                    </td>
                    <td style="padding: 6px 0; text-align: right;">
                        <div class="label">Ticket Breakdown</div>
                        <div class="val">A:{{ $booking->adult_count }}, C:{{ $booking->child_count }}, I:{{ $booking->infant_count }}@if($booking->student_count > 0), S:{{ $booking->student_count }}@endif</div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="footer">
            <b>Location:</b> {{ $eventLocation }}<br>
            Please present this PDF ticket or QR code on your phone at the event entrance for scan verification.
        </div>
    </div>
</body>
</html>
