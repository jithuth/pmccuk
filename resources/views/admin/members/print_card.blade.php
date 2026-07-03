<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ID Card - {{ $member->full_name }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * { box-sizing: border-box; }
        body {
            background: #222;
            padding: 2rem;
            font-family: 'Roboto', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            flex-direction: column;
            -webkit-print-color-adjust: exact;
        }
        .card {
            background: #ffffff;
            border-radius: 16px;
            width: 550px;
            height: 340px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.5);
            position: relative;
            overflow: hidden;
            border: 1px solid #e0e0e0;
        }
        .card-header-stripe {
            background: #004d40;
            height: 85px;
            padding: 0 25px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .org-logo {
            height: 60px;
            background: #fff;
            border-radius: 50%;
            padding: 2px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }
        .org-name {
            color: #fff;
            text-transform: uppercase;
            font-size: 18px;
            font-weight: 900;
            text-align: right;
            line-height: 1.1;
            letter-spacing: 0.5px;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
        }
        .org-tagline {
            font-size: 9px;
            font-weight: 500;
            letter-spacing: 2px;
            margin-top: 4px;
            text-align: right;
            opacity: 0.9;
            color: #80cbc4;
        }
        .card-body {
            padding: 25px;
            position: relative;
        }
        .member-photo {
            width: 120px;
            height: 120px;
            border-radius: 12px;
            object-fit: cover;
            border: 4px solid #004d40;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            position: absolute;
            right: 25px;
            top: 25px;
            background-color: #f1f5f9;
        }
        .qr-code {
            position: absolute;
            left: 50%;
            bottom: 70px;
            transform: translateX(-50%);
            width: 85px;
            height: 85px;
            padding: 5px;
            background: #fff;
            border: 2px solid #004d40;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            z-index: 10;
        }
        .field-group { margin-bottom: 8px; }
        .label {
            font-size: 10px;
            color: #78909c;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 0.5px;
            display: block;
            margin-bottom: 1px;
        }
        .value {
            font-size: 14px;
            color: #263238;
            font-weight: 800;
            text-transform: uppercase;
            display: block;
        }
        .value.highlight {
            color: #d32f2f;
            font-size: 16px;
            letter-spacing: 1px;
        }
        .value.name_large {
            font-size: 20px;
            color: #000;
            line-height: 1.2;
            max-width: 320px;
            margin-bottom: 2px;
        }
        .location-info {
            font-size: 10px;
            color: #455a64;
            font-weight: 600;
            margin-top: 10px;
            max-width: 350px;
            line-height: 1.3;
            word-wrap: break-word;
            text-transform: uppercase;
        }
        .card-footer {
            background: #004d40;
            color: white;
            position: absolute;
            bottom: 0;
            width: 100%;
            padding: 10px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .footer-label {
            color: #80cbc4;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .footer-val {
            font-size: 13px;
            font-weight: 800;
            margin-top: 2px;
            display: block;
        }
        .print-btn {
            margin-top: 30px;
            padding: 12px 30px;
            font-weight: bold;
            font-size: 16px;
            cursor: pointer;
            background: #fff;
            color: #004d40;
            border: none;
            border-radius: 50px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .print-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.4);
        }
        @media print {
            body { background: none; padding: 0; display: block; }
            .card { box-shadow: none; border: 1px solid #000; margin: 0; page-break-inside: avoid; }
            .no-print { display: none !important; }
        }
    </style>
    @php
        $settings = \App\Models\Setting::pluck('setting_value', 'setting_key')->toArray();
        $site_logo = !empty($settings['site_logo']) ? asset('storage/' . $settings['site_logo']) : 'https://pmccuk.org/assets/img/logo.png';
    @endphp
</head>
<body>
    @php
        $reg_no = $member->membership_id_assigned ?: ($member->prev_membership_no ?: 'PMCC-' . $member->id);
        $valid_till = $member->expiry_date ? \Carbon\Carbon::parse($member->expiry_date)->format('d M Y') : \Carbon\Carbon::parse($member->created_at)->addYear()->format('d M Y');
        
        // Verification QR Logic
        $secret = 'pmcc_secret_key_2026';
        $v_token = substr(hash('sha256', $member->id . $secret), 0, 10);
        $verify_url = route('admin.members.verify', ['id' => $member->id, 'token' => $v_token]);
        $qr_api_url = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($verify_url);
    @endphp

    <div class="card">
        <div class="card-header-stripe">
            <img src="{{ $site_logo }}" onerror="this.src='https://placehold.co/60x60?text=PMCC'" class="org-logo" alt="Logo">
            <div class="org-name">
                Plymouth Malayalee<br>Cultural Community
                <div class="org-tagline">THE POWER OF UNITY</div>
            </div>
        </div>

        <div class="card-body">
            <img src="{{ $member->photo_url }}" class="member-photo" alt="Photo">

            <div class="field-group">
                <span class="label">Name</span>
                <div class="value name_large">{{ $member->full_name }}</div>
            </div>

            <div class="field-group">
                <span class="label">Membership No</span>
                <span class="value highlight">{{ $reg_no }}</span>
            </div>

            <div class="field-group">
                <span class="label">Type</span>
                <span class="value">{{ $member->membership_type }}</span>
            </div>

            <div class="field-group location-group" style="margin-top: 12px;">
                <span class="label">Address</span>
                <div class="location-info" style="margin-top: 2px;">
                    {{ str_replace(["\r\n", "\r", "\n"], ", ", trim($member->house_details)) }}, {{ $member->post_code }}
                </div>
            </div>

            <img src="{{ $qr_api_url }}" class="qr-code" alt="Verification QR">
        </div>

        <div class="card-footer">
            <div style="text-align: left;">
                <span class="footer-label">Issued On</span>
                <span class="footer-val">{{ \Carbon\Carbon::parse($member->created_at)->format('d M Y') }}</span>
            </div>
            <div style="text-align: right;">
                <span class="footer-label">Valid Until</span>
                <span class="footer-val">{{ $valid_till }}</span>
            </div>
        </div>
    </div>

    <div class="no-print">
        <button onclick="window.print()" class="print-btn">PRINT CARD</button>
    </div>
</body>
</html>
