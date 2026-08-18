<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>ID Card - {{ $member->full_name }}</title>
    <style>
        @page { margin: 0px; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; margin: 0px; padding: 0px; background-color: #ffffff; }
        .card { width: 500px; height: 300px; position: relative; border: 2px solid #004d40; border-radius: 12px; overflow: hidden; background: #ffffff; margin: 10px auto; }
        .header { background-color: #004d40; height: 65px; color: #ffffff; padding: 10px 20px; }
        .header table { width: 100%; border-collapse: collapse; }
        .org-title { font-size: 16px; font-weight: bold; color: #ffffff; text-transform: uppercase; line-height: 1.2; }
        .org-sub { font-size: 8px; color: #80cbc4; letter-spacing: 1px; font-weight: bold; margin-top: 3px; }
        .body-content { padding: 15px 20px; position: relative; height: 160px; }
        .label { font-size: 9px; color: #78909c; font-weight: bold; text-transform: uppercase; margin-bottom: 2px; }
        .value { font-size: 13px; font-weight: bold; color: #263238; text-transform: uppercase; margin-bottom: 8px; }
        .value-highlight { font-size: 15px; font-weight: bold; color: #d32f2f; text-transform: uppercase; margin-bottom: 8px; }
        .photo-box { position: absolute; right: 20px; top: 15px; width: 105px; height: 110px; border: 3px solid #004d40; border-radius: 8px; overflow: hidden; background: #f1f5f9; text-align: center; }
        .photo-img { width: 105px; height: 110px; object-fit: cover; }
        .footer { background-color: #004d40; height: 35px; color: #ffffff; padding: 5px 20px; position: absolute; bottom: 0; left: 0; right: 0; }
        .footer table { width: 100%; font-size: 10px; color: #ffffff; }
        .footer-sub { color: #80cbc4; font-size: 8px; text-transform: uppercase; font-weight: bold; }
    </style>
</head>
<body>
    @php
        $reg_no = $member->membership_id_assigned ?: ($member->prev_membership_no ?: 'PMCC-' . $member->id);
        $valid_till = $member->expiry_date ? \Carbon\Carbon::parse($member->expiry_date)->format('d M Y') : \Carbon\Carbon::parse($member->created_at)->addYear()->format('d M Y');
        $issued_on = \Carbon\Carbon::parse($member->created_at)->format('d M Y');

        $photo_src = null;
        if (!empty($member->photo)) {
            $path = storage_path('app/public/' . $member->photo);
            if (file_exists($path)) {
                $photo_src = 'data:image/' . pathinfo($path, PATHINFO_EXTENSION) . ';base64,' . base64_encode(file_get_contents($path));
            }
        }
    @endphp

    <div class="card">
        <div class="header">
            <table>
                <tr>
                    <td style="width: 75%;">
                        <div class="org-title">Plymouth Malayalee<br>Cultural Community</div>
                        <div class="org-sub">THE POWER OF UNITY</div>
                    </td>
                    <td style="width: 25%; text-align: right; vertical-align: top;">
                        <span style="font-size: 10px; font-weight: bold; color: #80cbc4; text-transform: uppercase;">MEMBER CARD</span>
                    </td>
                </tr>
            </table>
        </div>

        <div class="body-content">
            <div class="photo-box">
                @if($photo_src)
                    <img src="{{ $photo_src }}" class="photo-img">
                @else
                    <div style="padding-top: 45px; font-size: 9px; color: #78909c; font-weight: bold;">NO PHOTO</div>
                @endif
            </div>

            <div class="label">Member Name</div>
            <div class="value" style="font-size: 15px; color: #000;">{{ $member->full_name }}</div>

            <div class="label">Membership Number</div>
            <div class="value-highlight">{{ $reg_no }}</div>

            <div class="label">Membership Type</div>
            <div class="value">{{ $member->membership_type }}</div>
        </div>

        <div class="footer">
            <table>
                <tr>
                    <td style="text-align: left;">
                        <div class="footer-sub">Issued On</div>
                        <div style="font-weight: bold;">{{ $issued_on }}</div>
                    </td>
                    <td style="text-align: right;">
                        <div class="footer-sub">Valid Until</div>
                        <div style="font-weight: bold;">{{ $valid_till }}</div>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</body>
</html>
