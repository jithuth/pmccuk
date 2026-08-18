<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Event Ticket - {{ $booking->event->title ?? 'PMCC Event' }}</title>
    <style>
        @page { margin: 0px; }
        * { box-sizing: border-box; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; margin: 0px; padding: 0px; background-color: #ffffff; }
        .ticket-card { width: 540px; height: 330px; position: relative; margin: 15px auto 0 auto; background: #ffffff; border-radius: 16px; border: 2px solid #0f172a; overflow: hidden; }
        
        .header { background: #0f172a; color: #ffffff; padding: 12px 20px; text-align: center; height: 65px; }
        .header-title { font-size: 14px; font-weight: bold; color: #ffffff; letter-spacing: 0.5px; text-transform: uppercase; white-space: nowrap; }
        .header-sub { font-size: 9px; color: #fbbf24; font-weight: bold; text-transform: uppercase; letter-spacing: 2px; margin-top: 3px; }
        
        .body-section { padding: 12px 20px; text-align: center; height: 215px; position: relative; }
        .status-badge { background-color: #dcfce7; color: #166534; font-size: 10px; font-weight: bold; padding: 4px 12px; border-radius: 12px; display: inline-block; margin-bottom: 6px; text-transform: uppercase; }
        .event-name { font-size: 17px; font-weight: bold; color: #0f172a; margin-bottom: 3px; }
        .event-date { font-size: 11px; color: #475569; margin-bottom: 8px; font-weight: bold; }
        
        .qr-box { background: #f8fafc; padding: 8px 14px; border-radius: 12px; border: 1.5px dashed #cbd5e1; display: inline-block; margin: 3px 0; text-align: center; }
        .qr-img { width: 110px; height: 110px; }
        .ref-no { font-family: monospace; font-size: 12px; font-weight: bold; color: #0f172a; margin-top: 4px; }
        
        .details-table { width: 100%; border-collapse: collapse; margin-top: 8px; border-top: 1px solid #e2e8f0; padding-top: 8px; text-align: left; }
        .label { font-size: 9px; color: #64748b; font-weight: bold; text-transform: uppercase; }
        .val { font-size: 12px; color: #0f172a; font-weight: bold; margin-top: 1px; }
        
        .footer { background: #f8fafc; padding: 8px 20px; text-align: center; font-size: 9px; color: #64748b; border-top: 1px solid #e2e8f0; position: absolute; bottom: 0; left: 0; right: 0; height: 45px; }
    </style>
</head>
<body>
    @php
        $refNo = $booking->reference_no ?: ("BOOK-" . ($booking->membership_no != 'NON-MEMBER' ? $booking->membership_no : 'NM') . "-" . $booking->id);
        $totalTickets = $booking->adult_count + $booking->child_count + $booking->infant_count;
        $eventTitle = $booking->event ? $booking->event->title : 'PMCC Event';
        $eventDate = $booking->event ? \Carbon\Carbon::parse($booking->event->event_date)->format('l, F j, Y') : 'N/A';
        $eventLocation = $booking->event ? $booking->event->location : 'Plymouth, UK';

        // Verification QR Code
        $verifyUrl = route('event.verify-ticket', ['reference' => $refNo]);
        $qrApiUrl = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($verifyUrl);
        
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
                    <div style="font-size: 9px; padding: 35px 0; color: #666; font-weight: bold;">SCAN QR AT ENTRANCE</div>
                @endif
                <div class="ref-no">{{ $refNo }}</div>
            </div>

            <table class="details-table">
                <tr>
                    <td style="width: 50%;">
                        <div class="label">Attendee Name</div>
                        <div class="val">{{ $booking->full_name }}</div>
                    </td>
                    <td style="width: 50%; text-align: right;">
                        <div class="label">Total Attendees</div>
                        <div class="val">{{ $totalTickets }} Person(s)</div>
                    </td>
                </tr>
                <tr>
                    <td style="padding-top: 6px;">
                        <div class="label">Membership No</div>
                        <div class="val" style="color: #d32f2f;">{{ $booking->membership_no ?: 'NON-MEMBER' }}</div>
                    </td>
                    <td style="padding-top: 6px; text-align: right;">
                        <div class="label">Ticket Breakdown</div>
                        <div class="val">{{ $booking->adult_count }} Adult, {{ $booking->child_count }} Child, {{ $booking->infant_count }} Infant</div>
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
