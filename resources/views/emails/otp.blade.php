<!DOCTYPE html>
<html>
<body style='font-family: Arial, sans-serif; color: #333;'>
    <div style='max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 10px;'>
        <h2 style='color: #004d40; border-bottom: 2px solid #eee; padding-bottom: 10px;'>Renewal Verification</h2>
        <p>Dear <strong>{{ $name }}</strong>,</p>
        <p>You are attempting to renew your PMCC Membership for 2026. To ensure the security of your account, please use the verification code below:</p>
        
        <div style='background: #f4f4f4; padding: 20px; text-align: center; border-radius: 5px; margin: 20px 0;'>
            <span style='font-size: 32px; font-weight: bold; letter-spacing: 5px; color: #d32f2f;'>{{ $otp }}</span>
        </div>
        
        <p style='color: #666; font-size: 14px;'>This code will expire in 10 minutes. If you did not request this renewal, please ignore this email.</p>
        
        <p>Best regards,<br><strong>PMCC Admin Team</strong></p>
    </div>
</body>
</html>
