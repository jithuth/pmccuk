<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: 'Helvetica', sans-serif; background: #f4f4f4; padding: 20px; }
        .ticket { background: white; max-width: 500px; margin: 0 auto; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .header { background: #1e293b; color: #fbbf24; padding: 30px; text-align: center; }
        .content { padding: 30px; text-align: center; }
        .qr-box { background: #f8fafc; padding: 20px; border-radius: 15px; display: inline-block; margin: 20px 0; border: 2px solid #e2e8f0; }
        .details { text-align: left; margin-top: 30px; border-top: 1px solid #f1f5f9; padding-top: 20px; }
        .footer { background: #f8fafc; padding: 20px; text-align: center; color: #94a3b8; font-size: 12px; }
        .badge { background: #dcfce7; color: #166534; padding: 4px 12px; border-radius: 99px; font-weight: bold; font-size: 12px; }
    </style>
</head>
<body>
    <div class="ticket">
        <div class="header">
            <h1 style="margin:0; font-size: 24px; font-weight: 900; letter-spacing: -1px;">PMCC <span style="color: white; font-style: italic;">UK</span></h1>
            <p style="margin: 5px 0 0; color: #fbbf24; font-size: 10px; text-transform: uppercase; font-weight: bold; letter-spacing: 2px;">Official Entry Permit</p>
        </div>
        <div class="content">
            <span class="badge">CONFIRMED</span>
            <h2 style="margin: 15px 0 5px; color: #0f172a;">{{ $booking->event->title }}</h2>
            <p style="color: #64748b; font-size: 14px;">{{ \Carbon\Carbon::parse($booking->event->event_date)->format('l, F j, Y') }}</p>
            
            <div class="qr-box">
                <img src="{{ $qrCodeUrl }}" alt="QR Code Ticket" width="180">
                <p style="margin-top: 10px; font-family: monospace; font-weight: bold; color: #1e293b;">{{ $booking->reference_no }}</p>
            </div>
            
            <p style="color: #1e293b; font-weight: bold; font-size: 14px;">Scan this QR code at the entrance for entry level approval.</p>

            <div class="details">
                <table width="100%">
                    <tr>
                        <td width="50%" style="font-size: 10px; text-transform: uppercase; color: #94a3b8; font-weight: bold;">Attendee Name</td>
                        <td width="50%" style="font-size: 10px; text-transform: uppercase; color: #94a3b8; font-weight: bold; text-align: right;">Tickets</td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold; padding-top: 5px;">{{ $booking->full_name }}</td>
                        <td style="font-weight: bold; padding-top: 5px; text-align: right;">{{ $booking->adult_count + $booking->child_count + $booking->infant_count + $booking->student_count }} Total</td>
                    </tr>
                </table>
            </div>
        </div>
        <div class="footer">
            Location: {{ $booking->event->location }}<br>
            Please have this email ready on your phone at the entrance.
        </div>
    </div>
</body>
</html>
