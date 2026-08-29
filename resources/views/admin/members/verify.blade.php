<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Membership Verification - PMCC UK</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background-color: #f4f7f6; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .verify-card { max-width: 450px; width: 90%; border: none; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); overflow: hidden; }
        .success-header { background: linear-gradient(135deg, #00b09b, #96c93d); color: white; padding: 40px 20px; text-align: center; }
        .fail-header { background: linear-gradient(135deg, #f85032, #e73827); color: white; padding: 40px 20px; text-align: center; }
        .member-img { width: 120px; height: 120px; border: 5px solid white; border-radius: 50%; margin-top: -60px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
    <div class="card verify-card bg-white">
        @if($isValid)
            <div class="success-header">
                <i class="fas fa-check-circle fa-4x mb-3 animate__animated animate__bounceIn"></i>
                <h2 class="fw-bold">VERIFIED</h2>
                <p class="mb-0">Active Member of PMCC-UK</p>
            </div>
            <div class="card-body text-center p-4">
                <img src="{{ $member->photo_url }}" class="member-img mb-3">
                <h3 class="fw-bold text-dark">{{ $member->full_name }}</h3>
                <div class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 mb-4">
                    {{ $member->membership_id_assigned ?: 'PMCC-'.$member->id }}
                </div>
                
                <table class="table table-sm text-start mt-2">
                    <tr><th class="text-muted small">Status</th><td class="fw-bold text-success">{{ strtoupper($member->status) }}</td></tr>
                    <tr><th class="text-muted small">Valid Until</th><td class="fw-bold text-danger">{{ $member->expiry_date ?: 'N/A' }}</td></tr>
                    <tr><th class="text-muted small">Type</th><td class="fw-bold">{{ $member->membership_type }}</td></tr>
                </table>
                
                <hr>
                <div class="text-muted small">
                    This membership is verified via PMCC Global Intelligence.
                </div>
            </div>
        @else
            <div class="fail-header">
                <i class="fas fa-times-circle fa-4x mb-3"></i>
                <h2 class="fw-bold">
                    @if(isset($isActive) && !$isActive && isset($tokenValid) && $tokenValid)
                        {{ $member->getMembershipStatusLabel() }}
                    @else
                        INVALID
                    @endif
                </h2>
                <p class="mb-0">
                    @if(isset($isActive) && !$isActive && isset($tokenValid) && $tokenValid)
                        Membership Status: {{ $member->getMembershipStatusLabel() }}
                    @else
                        Credential Verification Failed
                    @endif
                </p>
            </div>
            <div class="card-body text-center p-4">
                @if(isset($isActive) && !$isActive && isset($tokenValid) && $tokenValid)
                    <img src="{{ $member->photo_url }}" class="member-img mb-3" style="filter: grayscale(100%);">
                    <h3 class="fw-bold text-dark">{{ $member->full_name }}</h3>
                    <div class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2 mb-3">
                        STATUS: {{ $member->getMembershipStatusLabel() }}
                    </div>
                    
                    <table class="table table-sm text-start mt-2">
                        <tr><th class="text-muted small">Status</th><td class="fw-bold text-danger">{{ $member->getMembershipStatusLabel() }}</td></tr>
                        <tr><th class="text-muted small">Expiry Date</th><td class="fw-bold text-danger">{{ $member->expiry_date ?: 'N/A' }}</td></tr>
                        <tr><th class="text-muted small">Type</th><td class="fw-bold">{{ $member->membership_type }}</td></tr>
                    </table>

                    <p class="text-muted small mt-3">
                        This membership is inactive or has expired. Please contact PMCC-UK administration to renew.
                    </p>
                @else
                    <p class="text-muted py-3">The membership credentials provided are invalid or have been tampered with.</p>
                @endif
                <a href="/" class="btn btn-dark rounded-pill px-4 mt-2">Back to Home</a>
            </div>
        @endif
    </div>
</body>
</html>
