<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Application Received</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f4f4f4; }
        .container { max-width: 600px; margin: 20px auto; background: #ffffff; padding: 40px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        .header { text-align: center; border-bottom: 2px solid #1e3a8a; padding-bottom: 20px; margin-bottom: 30px; }
        .logo { font-size: 24px; font-weight: 800; color: #1e3a8a; text-transform: uppercase; letter-spacing: 1px; }
        h1 { color: #1e3a8a; font-size: 20px; }
        .status-badge { display: inline-block; background-color: #fef3c7; color: #92400e; padding: 5px 15px; border-radius: 20px; font-size: 12px; font-weight: bold; margin-bottom: 20px; }
        .footer { margin-top: 40px; padding-top: 20px; border-top: 1px solid #eee; font-size: 12px; color: #777; text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">PMCC-UK</div>
        </div>
        
        <h1>Hello {{ $full_name }},</h1>
        <div class="status-badge">Application Status: Pending Review</div>
        
        <p>Thank you for submitting your membership application to the <strong>Plymouth Malayalee Cultural Community (PMCC-UK)</strong>.</p>
        
        <p>Your application is currently being reviewed by our administrative team. Once approved, you will receive another email with your official Membership ID card and further instructions.</p>
        
        <p>We are excited to have you as part of our community!</p>
        
        <p>Best Regards,<br>
        <strong>PMCC-UK Administrative Team</strong></p>
        
        <div class="footer">
            &copy; {{ date('Y') }} PMCC-UK. All rights reserved.<br>
            Plymouth, United Kingdom.
        </div>
    </div>
</body>
</html>
