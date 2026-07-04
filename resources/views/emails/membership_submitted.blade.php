<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Application Received</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f4f4f4; }
        .container { max-width: 600px; margin: 20px auto; background: #ffffff; padding: 40px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        .header { text-align: center; border-bottom: 2px solid #00897b; padding-bottom: 20px; margin-bottom: 30px; }
        .logo { font-size: 24px; font-weight: 800; color: #00897b; text-transform: uppercase; letter-spacing: 1px; }
        h1 { color: #00897b; font-size: 20px; }
        .status-badge { display: inline-block; background-color: #fef3c7; color: #92400e; padding: 5px 15px; border-radius: 20px; font-size: 12px; font-weight: bold; margin-bottom: 20px; }
        .details { background: #f9f9f9; padding: 20px; border-left: 4px solid #00897b; margin: 20px 0; border-radius: 4px; }
        .details p { margin: 8px 0; font-size: 14px; }
        .button { display: inline-block; padding: 12px 24px; background: #25D366; color: white; text-decoration: none; border-radius: 6px; font-weight: bold; margin: 15px 0; text-align: center; }
        .footer { margin-top: 40px; padding-top: 20px; border-top: 1px solid #eee; font-size: 12px; color: #777; text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">PMCC-UK</div>
        </div>
        
        <h1>Hello {{ $full_name }},</h1>
        <div class="status-badge">Application Status: Pending Payment Review</div>
        
        <p>Thank you for submitting your membership application to the <strong>Plymouth Malayalee Cultural Community (PMCC-UK)</strong>.</p>
        
        <p>To complete your registration, please transfer the membership fee of <strong>£5.00</strong> to our official bank account details below:</p>
        
        <div class="details">
            <p><strong>Bank Name:</strong> {{ $bank_name }}</p>
            <p><strong>Account Name:</strong> {{ $bank_account_name }}</p>
            <p><strong>Sort Code:</strong> {{ $bank_sort_code }}</p>
            <p><strong>Account No:</strong> {{ $bank_account_no }}</p>
            <p><strong>Reference:</strong> {{ $full_name }}</p>
        </div>
        
        <p>Once you have completed the bank transfer, please send the payment confirmation receipt or screenshot to our Treasurer via WhatsApp so we can verify and approve your account:</p>
        
        <p style="text-align: center;">
            <a href="https://wa.me/447345041195" class="button">💬 SEND SCREENSHOT TO TREASURER</a>
        </p>
        
        <p>After your payment is verified, your membership will be approved, and you will receive another email containing your official Membership ID Card.</p>
        
        <p>We look forward to welcoming you into our community!</p>
        
        <p>Best Regards,<br>
        <strong>PMCC-UK Administrative Team</strong></p>
        
        <div class="footer">
            &copy; {{ date('Y') }} PMCC-UK. All rights reserved.<br>
            Plymouth, United Kingdom.
        </div>
    </div>
</body>
</html>
