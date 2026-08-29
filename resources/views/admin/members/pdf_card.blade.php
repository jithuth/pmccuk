<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>ID Card - {{ $member->full_name }}</title>
    <style>
        @page { margin: 0px; }
        * { box-sizing: border-box; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; margin: 0px; padding: 0px; background-color: #ffffff; }
        .card { width: 540px; height: 330px; position: relative; border: 2px solid #004d40; border-radius: 16px; overflow: hidden; background: #ffffff; margin: 15px auto 0 auto; }
        
        .card-header { background-color: #004d40; height: 80px; color: #ffffff; padding: 10px 20px; }
        .header-table { width: 100%; border-collapse: collapse; }
        .org-logo { width: 60px; height: 60px; border-radius: 50%; background: #ffffff; padding: 2px; }
        .org-name { font-size: 16px; font-weight: bold; color: #ffffff; text-transform: uppercase; line-height: 1.15; text-align: right; }
        .org-tagline { font-size: 8px; font-weight: bold; color: #80cbc4; letter-spacing: 1.5px; margin-top: 3px; text-align: right; text-transform: uppercase; }

        .card-body { padding: 15px 20px; position: relative; height: 200px; }
        .content-table { width: 100%; border-collapse: collapse; }

        .field-group { margin-bottom: 6px; }
        .label { font-size: 9px; color: #78909c; font-weight: bold; text-transform: uppercase; display: block; margin-bottom: 1px; }
        .val-name { font-size: 16px; font-weight: bold; color: #000000; text-transform: uppercase; line-height: 1.1; max-width: 270px; }
        .val-highlight { font-size: 16px; font-weight: bold; color: #d32f2f; text-transform: uppercase; }
        .val-text { font-size: 12px; font-weight: bold; color: #263238; text-transform: uppercase; }
        .val-address { font-size: 9px; font-weight: bold; color: #455a64; text-transform: uppercase; max-width: 270px; line-height: 1.2; }

        .qr-box { width: 80px; height: 80px; border: 2px solid #004d40; border-radius: 8px; padding: 3px; background: #ffffff; text-align: center; }
        .qr-img { width: 70px; height: 70px; }

        .photo-box { width: 120px; height: 120px; border: 3px solid #004d40; border-radius: 12px; overflow: hidden; background: #f1f5f9; text-align: center; }
        .photo-img { width: 120px; height: 120px; object-fit: cover; }

        .card-footer { background-color: #004d40; height: 50px; color: #ffffff; padding: 8px 20px; position: absolute; bottom: 0; left: 0; right: 0; }
        .footer-table { width: 100%; border-collapse: collapse; }
        .footer-label { color: #80cbc4; font-size: 8px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; }
        .footer-val { color: #ffffff; font-size: 12px; font-weight: bold; margin-top: 2px; }

        /* Watermark & Invalid Status Styles */
        .watermark-box {
            position: absolute;
            top: 100px;
            left: 20px;
            width: 500px;
            text-align: center;
            z-index: 100;
        }
        .watermark-text {
            font-size: 26px;
            font-weight: 900;
            color: rgba(211, 47, 47, 0.45);
            border: 4px solid rgba(211, 47, 47, 0.45);
            padding: 8px 16px;
            display: inline-block;
            text-transform: uppercase;
            letter-spacing: 2px;
            border-radius: 8px;
            transform: rotate(-15deg);
        }
        .qr-invalid-banner {
            font-size: 8px;
            font-weight: bold;
            color: #ffffff;
            background: #d32f2f;
            padding: 2px 0;
            text-transform: uppercase;
            margin-top: 2px;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    @php
        $isActive = $member->isActive();
        $statusLabel = $member->getMembershipStatusLabel();

        // Original Membership ID ONLY for active members
        if ($isActive) {
            $reg_no = $member->membership_id_assigned ?: ($member->prev_membership_no ?: 'PMCC-' . $member->id);
        } else {
            $reg_no = $statusLabel;
        }

        $valid_till = $member->expiry_date ? \Carbon\Carbon::parse($member->expiry_date)->format('d M Y') : \Carbon\Carbon::parse($member->created_at)->addYear()->format('d M Y');
        $issued_on = \Carbon\Carbon::parse($member->created_at)->format('d M Y');

        if (!function_exists('resolveDompdfImage')) {
            function resolveDompdfImage(?string $rawPath) {
                if (empty($rawPath)) return null;

                $clean = ltrim($rawPath, '/');
                $filenameOnly = basename($clean);

                $possiblePaths = [
                    storage_path('app/public/photos/' . $filenameOnly),
                    storage_path('app/public/' . $clean),
                    storage_path('app/public/photos/' . $clean),
                    storage_path('app/' . $clean),
                    storage_path('app/photos/' . $filenameOnly),
                    public_path('storage/photos/' . $filenameOnly),
                    public_path('storage/' . $clean),
                    public_path($clean),
                ];

                foreach ($possiblePaths as $path) {
                    if (file_exists($path) && !is_dir($path) && filesize($path) > 0) {
                        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

                        if ($ext === 'webp' && function_exists('imagecreatefromwebp')) {
                            $img = @imagecreatefromwebp($path);
                            if ($img) {
                                ob_start();
                                imagejpeg($img, null, 90);
                                $data = ob_get_clean();
                                imagedestroy($img);
                                return 'data:image/jpeg;base64,' . base64_encode($data);
                            }
                        }

                        $mime = ($ext === 'png') ? 'image/png' : (($ext === 'gif') ? 'image/gif' : 'image/jpeg');
                        return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($path));
                    }
                }

                return null;
            }
        }

        $photo_src = resolveDompdfImage($member->photo);

        $logoSetting = \App\Models\Setting::where('setting_key', 'site_logo')->value('setting_value');
        $logo_src = resolveDompdfImage($logoSetting) 
                 ?: resolveDompdfImage('assets/img/697c0e1fba726.webp')
                 ?: resolveDompdfImage('assets/img/logo.png')
                 ?: resolveDompdfImage('favicon.ico');

        // Verification QR Code (Token is invalid if member is not active)
        $secret = 'pmcc_secret_key_2026';
        $v_token = $isActive ? substr(hash('sha256', $member->id . $secret), 0, 10) : 'invalid_token';
        $verify_url = route('admin.members.verify', ['id' => $member->id, 'token' => $v_token]);
        $qr_api_url = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($verify_url);
        
        $qr_src = null;
        try {
            $qrContent = @file_get_contents($qr_api_url);
            if ($qrContent) {
                $qr_src = 'data:image/png;base64,' . base64_encode($qrContent);
            }
        } catch (\Exception $e) {}

        $fullAddress = trim(str_replace(["\r\n", "\r", "\n"], ", ", trim($member->house_details ?? '')));
        if (!empty($member->post_code)) {
            $fullAddress .= ($fullAddress ? ', ' : '') . $member->post_code;
        }
    @endphp

    <div class="card">
        @if(!$isActive)
            <div class="watermark-box">
                <div class="watermark-text">MEMBERSHIP {{ $statusLabel }}</div>
            </div>
        @endif

        <!-- Header -->
        <div class="card-header">
            <table class="header-table">
                <tr>
                    <td style="width: 65px; vertical-align: middle;">
                        @if($logo_src)
                            <img src="{{ $logo_src }}" class="org-logo">
                        @endif
                    </td>
                    <td style="vertical-align: middle; text-align: right;">
                        <div class="org-name">Plymouth Malayalee<br>Cultural Community</div>
                        <div class="org-tagline">THE POWER OF UNITY</div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Body -->
        <div class="card-body">
            <table class="content-table">
                <tr>
                    <!-- Left: Details -->
                    <td style="vertical-align: top; width: 270px;">
                        <div class="field-group">
                            <span class="label">Name</span>
                            <div class="val-name">{{ $member->full_name }}</div>
                        </div>

                        <div class="field-group">
                            <span class="label">Membership No</span>
                            <div class="val-highlight" style="{{ !$isActive ? 'color: #d32f2f;' : '' }}">{{ $reg_no }}</div>
                        </div>

                        <div class="field-group">
                            <span class="label">Type</span>
                            <div class="val-text">{{ $member->membership_type }}</div>
                        </div>

                        @if(!empty($fullAddress))
                            <div class="field-group" style="margin-top: 4px;">
                                <span class="label">Address</span>
                                <div class="val-address">{{ $fullAddress }}</div>
                            </div>
                        @endif
                    </td>

                    <!-- Center: Verification QR -->
                    <td style="vertical-align: middle; text-align: center; width: 95px;">
                        @if($qr_src)
                            <div class="qr-box" style="{{ !$isActive ? 'border-color: #d32f2f; opacity: 0.6;' : '' }}">
                                <img src="{{ $qr_src }}" class="qr-img">
                                @if(!$isActive)
                                    <div class="qr-invalid-banner">INVALID</div>
                                @endif
                            </div>
                        @endif
                    </td>

                    <!-- Right: Member Photo -->
                    <td style="vertical-align: middle; text-align: right; width: 135px;">
                        <div class="photo-box" style="margin-left: auto;">
                            @if($photo_src)
                                <img src="{{ $photo_src }}" class="photo-img">
                            @else
                                <div style="padding-top: 50px; font-size: 9px; color: #78909c; font-weight: bold;">NO PHOTO</div>
                            @endif
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Footer -->
        <div class="card-footer">
            <table class="footer-table">
                <tr>
                    <td style="text-align: left; vertical-align: middle;">
                        <div class="footer-label">Issued On</div>
                        <div class="footer-val">{{ $issued_on }}</div>
                    </td>
                    <td style="text-align: right; vertical-align: middle;">
                        <div class="footer-label">Valid Until</div>
                        <div class="footer-val">{{ $valid_till }}</div>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</body>
</html>
