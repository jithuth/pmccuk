@extends('layouts.admin')

@section('page_title', 'Network & IP Diagnostics')

@section('content')
<div class="row">
    <!-- Current Connection Info -->
    <div class="col-md-4">
        <div class="card shadow-sm border-0 rounded-4 overflow-hidden mb-4 h-100">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="fw-bold mb-0 text-primary"><i class="fas fa-network-wired me-2"></i> Connection Diagnostics</h6>
            </div>
            <div class="card-body p-4">
                <div class="text-center mb-4">
                    <span class="badge bg-primary-subtle text-primary p-3 rounded-circle mb-3">
                        <i class="fas fa-globe-americas fa-2x"></i>
                    </span>
                    <h5 class="fw-bold mb-1">{{ request()->header('X-Real-IP', request()->ip()) }}</h5>
                    <p class="text-muted small">Your Detected IP Address</p>
                </div>
                <hr>
                <div class="small">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Host Header:</span>
                        <span class="fw-bold">{{ request()->getHost() }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">HTTPS Status:</span>
                        @if(request()->secure())
                            <span class="badge bg-success-subtle text-success">Secure (HTTPS)</span>
                        @else
                            <span class="badge bg-warning-subtle text-warning">Unsecured (HTTP)</span>
                        @endif
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Forwarded For:</span>
                        <span class="fw-bold text-truncate" style="max-width: 180px;">{{ request()->header('X-Forwarded-For', 'None') }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Port:</span>
                        <span class="fw-bold">{{ request()->header('X-Forwarded-Port', request()->getPort()) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- IP Lookup Tool -->
    <div class="col-md-8">
        <div class="card shadow-sm border-0 rounded-4 overflow-hidden mb-4 h-100">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                <h6 class="fw-bold mb-0 text-primary"><i class="fas fa-search-location me-2"></i> IP Address Lookup</h6>
            </div>
            <div class="card-body p-4">
                <div class="input-group mb-4 shadow-sm rounded-pill overflow-hidden border">
                    <span class="input-group-text bg-white border-0 ps-3 text-muted"><i class="fas fa-search"></i></span>
                    <input type="text" id="ipInput" class="form-control border-0 px-2" placeholder="Enter IP Address (e.g. 8.8.8.8)..." value="{{ request()->header('X-Real-IP', request()->ip()) }}">
                    <button class="btn btn-primary px-4 fw-bold border-0" id="lookupBtn">LOOKUP</button>
                </div>

                <div id="lookupResult" class="d-none animate__animated animate__fadeIn">
                    <div class="row">
                        <div class="col-md-6 border-end">
                            <h6 class="fw-bold text-secondary mb-3 small text-uppercase tracking-wider">Geolocation Info</h6>
                            <table class="table table-sm table-borderless small">
                                <tr><td class="text-muted">Country:</td><td id="resCountry" class="fw-bold text-end"></td></tr>
                                <tr><td class="text-muted">Region/State:</td><td id="resRegion" class="fw-bold text-end"></td></tr>
                                <tr><td class="text-muted">City:</td><td id="resCity" class="fw-bold text-end"></td></tr>
                                <tr><td class="text-muted">Postal Code:</td><td id="resZip" class="fw-bold text-end"></td></tr>
                                <tr><td class="text-muted">Coordinates:</td><td id="resCoords" class="fw-bold text-end"></td></tr>
                            </table>
                        </div>
                        <div class="col-md-6 ps-md-4">
                            <h6 class="fw-bold text-secondary mb-3 small text-uppercase tracking-wider">Network Details</h6>
                            <table class="table table-sm table-borderless small">
                                <tr><td class="text-muted">ISP/Provider:</td><td id="resIsp" class="fw-bold text-end"></td></tr>
                                <tr><td class="text-muted">Organization:</td><td id="resOrg" class="fw-bold text-end"></td></tr>
                                <tr><td class="text-muted">AS Number:</td><td id="resAs" class="fw-bold text-end"></td></tr>
                                <tr><td class="text-muted">Timezone:</td><td id="resTz" class="fw-bold text-end"></td></tr>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Loader -->
                <div id="lookupLoader" class="text-center py-5 d-none">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="text-muted small mt-2">Querying network intelligence databases...</p>
                </div>

                <!-- Error Message -->
                <div id="lookupError" class="alert alert-danger d-none mt-3"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        function lookupIp(ip) {
            if(!ip) return;
            $('#lookupResult').addClass('d-none');
            $('#lookupError').addClass('d-none');
            $('#lookupLoader').removeClass('d-none');

            $.ajax({
                url: `https://ipapi.co/${ip}/json/`,
                method: 'GET',
                success: function(data) {
                    $('#lookupLoader').addClass('d-none');
                    if (data.error) {
                        $('#lookupError').text(data.reason || 'Failed to retrieve information for this IP.').removeClass('d-none');
                        return;
                    }
                    
                    $('#resCountry').text(`${data.country_name || 'N/A'} (${data.country_code || ''})`);
                    $('#resRegion').text(data.region || 'N/A');
                    $('#resCity').text(data.city || 'N/A');
                    $('#resZip').text(data.postal || 'N/A');
                    $('#resCoords').text(`${data.latitude || ''}, ${data.longitude || ''}`);
                    $('#resIsp').text(data.org || 'N/A');
                    $('#resOrg').text(data.asn || 'N/A');
                    $('#resAs').text(data.version || 'N/A');
                    $('#resTz').text(data.timezone || 'N/A');
                    
                    $('#lookupResult').removeClass('d-none');
                },
                error: function() {
                    // Fallback to secondary service
                    $.ajax({
                        url: `http://ip-api.com/json/${ip}`,
                        method: 'GET',
                        success: function(data) {
                            $('#lookupLoader').addClass('d-none');
                            if (data.status === 'fail') {
                                $('#lookupError').text(data.message || 'Lookup failed').removeClass('d-none');
                                return;
                            }
                            $('#resCountry').text(`${data.country || 'N/A'} (${data.countryCode || ''})`);
                            $('#resRegion').text(data.regionName || 'N/A');
                            $('#resCity').text(data.city || 'N/A');
                            $('#resZip').text(data.zip || 'N/A');
                            $('#resCoords').text(`${data.lat || ''}, ${data.lon || ''}`);
                            $('#resIsp').text(data.isp || 'N/A');
                            $('#resOrg').text(data.org || 'N/A');
                            $('#resAs').text(data.as || 'N/A');
                            $('#resTz').text(data.timezone || 'N/A');
                            
                            $('#lookupResult').removeClass('d-none');
                        },
                        error: function() {
                            $('#lookupLoader').addClass('d-none');
                            $('#lookupError').text('Network error. Unable to contact IP lookup API.').removeClass('d-none');
                        }
                    });
                }
            });
        }

        $('#lookupBtn').click(function() {
            const ip = $('#ipInput').val().trim();
            lookupIp(ip);
        });

        $('#ipInput').keypress(function(e) {
            if(e.which == 13) {
                $('#lookupBtn').click();
            }
        });

        // Initial lookup
        const initialIp = $('#ipInput').val().trim();
        if(initialIp) {
            lookupIp(initialIp);
        }
    });
</script>
@endsection
