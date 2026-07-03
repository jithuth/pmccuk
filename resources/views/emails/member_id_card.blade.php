<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 10px; }
        .header { text-align: center; margin-bottom: 20px; }
        .footer { text-align: center; font-size: 12px; color: #999; margin-top: 30px; }
        .btn { display: inline-block; padding: 12px 25px; background-color: #004d40; color: #fff !important; text-decoration: none; border-radius: 5px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2 style="color: #004d40;">Plymouth Malayalee Cultural Community</h2>
        </div>
        
        <p>Dear {{ $member->full_name }},</p>
        
        <p>We are pleased to provide you with your digital membership ID card. Please find the link below to view and print your card for future reference.</p>
        
        <div style="text-align: center; margin: 30px 0;">
            @if(!empty($member->guid))
                <a href="{{ route('member.id-card.view', ['guid' => $member->guid]) }}" class="btn">VIEW MY ID CARD</a>
            @else
                <p style="color: #d32f2f; font-weight: bold;">[ID Card link unavailable - Please contact administrator]</p>
            @endif
        </div>
        
        <p><strong>Membership Details:</strong><br>
        Registration No: {{ $member->membership_id_assigned ?: 'N/A' }}<br>
        Expiry Date: {{ $member->expiry_date ?: 'N/A' }}</p>
        
        <p>If you have any questions, please feel free to reach out to us.</p>
        
        <p>Best Regards,<br>PMCC Management Team</p>
        
        <div class="footer">
            &copy; {{ date('Y') }} PMCC-UK. All rights reserved.
        </div>
    </div>
</body>
</html>
