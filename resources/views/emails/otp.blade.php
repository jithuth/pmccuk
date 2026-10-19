<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $purpose === 'renewal' ? 'PMCC Membership Renewal Code' : 'PMCC Member Login Verification Code' }}</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1e293b; -webkit-font-smoothing: antialiased;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color: #f1f5f9; padding: 40px 15px;">
        <tr>
            <td align="center">
                <!-- Email Container -->
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width: 580px; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05); border: 1px solid #e2e8f0;">
                    
                    <!-- Header Banner -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 100%); padding: 35px 30px; text-align: center;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td align="center">
                                        <!-- Emblem Shield Badge -->
                                        <div style="display: inline-block; width: 52px; height: 52px; line-height: 52px; background: rgba(255, 255, 255, 0.12); border-radius: 14px; text-align: center; border: 1px solid rgba(255, 255, 255, 0.2); margin-bottom: 12px;">
                                            <span style="font-size: 24px; color: #38bdf8;">🛡️</span>
                                        </div>
                                        <h1 style="margin: 0; color: #ffffff; font-size: 22px; font-weight: 800; letter-spacing: -0.5px;">PMCC UNITED KINGDOM</h1>
                                        <p style="margin: 6px 0 0 0; color: #94a3b8; font-size: 11px; text-transform: uppercase; letter-spacing: 2px; font-weight: 700;">Community Member Portal Security</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Body Content -->
                    <tr>
                        <td style="padding: 35px 30px;">
                            <h2 style="margin: 0 0 15px 0; color: #0f172a; font-size: 19px; font-weight: 700;">
                                @if(($purpose ?? 'login') === 'renewal')
                                    Membership Renewal Passcode
                                @else
                                    Your Member Login Passcode
                                @endif
                            </h2>

                            <p style="margin: 0 0 16px 0; font-size: 14px; line-height: 1.6; color: #475569;">
                                Hello <strong style="color: #0f172a;">{{ $name }}</strong>,
                            </p>

                            <p style="margin: 0 0 24px 0; font-size: 14px; line-height: 1.6; color: #475569;">
                                @if(($purpose ?? 'login') === 'renewal')
                                    You recently initiated a membership renewal request with PMCC-UK. Please enter the single-use verification code below to verify your account:
                                @else
                                    We received a sign-in request for your PMCC Member Portal account. Use the secure single-use verification code below to complete your access:
                                @endif
                            </p>

                            <!-- OTP Box -->
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin: 0 0 24px 0;">
                                <tr>
                                    <td align="center" style="background: #f8fafc; border: 2px dashed #cbd5e1; border-radius: 14px; padding: 25px 20px;">
                                        <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1.5px; color: #64748b; margin-bottom: 8px;">
                                            One-Time Security Passcode
                                        </div>
                                        <div style="font-family: 'Courier New', Courier, monospace; font-size: 38px; font-weight: 800; letter-spacing: 10px; color: #1e3a8a; padding: 5px 0;">
                                            {{ $otp }}
                                        </div>
                                        <div style="margin-top: 10px; font-size: 12px; font-weight: 600; color: #dc2626;">
                                            ⏱️ Valid for {{ $expiryMinutes ?? 5 }} minutes only &bull; Single-use
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            <!-- Security Warnings -->
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color: #fffbeb; border: 1px solid #fef3c7; border-radius: 12px; margin-bottom: 24px;">
                                <tr>
                                    <td style="padding: 14px 18px;">
                                        <p style="margin: 0; font-size: 12px; line-height: 1.6; color: #92400e;">
                                            <strong>Security Notice:</strong> PMCC staff will never ask you for this passcode. If you did not make this login request, please ignore this email or contact our support team immediately.
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin: 0; font-size: 13px; color: #64748b; line-height: 1.5;">
                                Warm regards,<br>
                                <strong style="color: #0f172a;">PMCC-UK Executive Administration</strong>
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f8fafc; padding: 24px 30px; text-align: center; border-top: 1px solid #f1f5f9;">
                            <p style="margin: 0 0 6px 0; font-size: 11px; color: #94a3b8; font-weight: 600;">
                                &copy; {{ date('Y') }} PMCC-UK (Praising & Miracle Community Church, United Kingdom). All rights reserved.
                            </p>
                            <p style="margin: 0; font-size: 11px; color: #94a3b8;">
                                Protected by UK GDPR & Dual-Factor Cryptographic Authentication &bull; <a href="https://pmccuk.org/privacy" style="color: #3b82f6; text-decoration: none;">Privacy Policy</a>
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
