@extends('layouts.admin')

@section('page_title', 'Staff Counter - Gate Entrance')

@section('content')
<div class="row">
    <!-- SCANNER & CAMERA AREA -->
    <div class="col-lg-8">
        <!-- OPERATIONAL INSTRUCTION CARD -->
        <div class="card shadow-lg border-0 rounded-4 overflow-hidden mb-4 animate__animated animate__fadeInDown">
            <div class="card-header bg-dark text-white p-4 d-flex justify-content-between align-items-center border-0">
                <div>
                    <h4 class="mb-0 fw-bold"><i class="fas fa-camera-retro me-2 text-primary"></i> Verification Station</h4>
                    <p class="text-white-50 small mb-0 mt-1">Ready for attendee entry</p>
                </div>
            </div>
            <div class="card-body p-5 bg-white text-center">
                <div class="display-1 text-primary-subtle mb-4">
                    <i class="fas fa-qrcode"></i>
                </div>
                <h3 class="fw-bold text-dark">PLEASE OPEN DEVICE CAMERA</h3>
                <p class="text-muted fs-5">Capture the QR code from the attendee's ticket to verify entry.</p>
                
                <div class="alert alert-info border-0 rounded-4 p-4 mt-4 d-inline-block">
                    <div class="d-flex align-items-center text-start">
                        <i class="fas fa-info-circle fa-2x me-3 text-primary"></i>
                        <div>
                            <div class="fw-bold text-dark">Alternative Entry</div>
                            <div class="small">If the camera fails, use the manual search box below.</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-light p-4">
                <div class="input-group input-group-lg shadow-sm border rounded-pill overflow-hidden bg-white">
                    <span class="input-group-text bg-white border-0 ps-4"><i class="fas fa-keyboard text-muted"></i></span>
                    <input type="text" id="scannerInput" class="form-control border-0 h-auto py-3 px-3 fw-bold" 
                           placeholder="Type Name or Membership ID manually..." autocomplete="off" autofocus>
                </div>
            </div>
        </div>

        <!-- SEARCH RESULTS -->
        <div id="resultsWrapper" class="animate__animated animate__fadeIn"></div>

        <div id="noResults" class="text-center py-5 text-muted d-none animate__animated animate__fadeIn">
            <div class="opacity-25 mb-3"><i class="fas fa-search fa-4x"></i></div>
            <h5 class="fw-bold">No Active Bookings Found</h5>
        </div>
    </div>

    <!-- RECENT ARRIVALS SIDEBAR -->
    <div class="col-lg-4">
        <div class="card shadow-sm border-0 rounded-4 h-100 bg-white">
            <div class="card-header bg-white border-0 py-4 px-4">
                <h5 class="fw-bold mb-0 text-dark"><i class="fas fa-history me-2 text-muted"></i> Recent Gate Activity</h5>
            </div>
            <div class="card-body p-0">
                <div id="recentArrivals" class="list-group list-group-flush overflow-auto" style="max-height: 700px;"></div>
            </div>
            <div class="card-footer bg-light-subtle border-0 py-3 text-center">
                <span class="text-muted small fw-bold"><i class="fas fa-sync-alt fa-spin me-1"></i> LIVE UPDATES ACTIVE</span>
            </div>
        </div>
    </div>
</div>

<!-- SUCCESS OVERLAY (FLASH) -->
<div id="successOverlay" class="fixed-top h-100 w-100 d-none" style="background: rgba(40, 167, 69, 0.1); pointer-events: none; z-index: 9999; border: 15px solid #28a745;"></div>

<!-- AUDIO ASSETS -->
<audio id="soundSuccess" src="https://assets.mixkit.co/active_storage/sfx/2000/2000-preview.mp3" preload="auto"></audio>
<audio id="soundError" src="https://assets.mixkit.co/active_storage/sfx/2571/2571-preview.mp3" preload="auto"></audio>

@endsection

@section('styles')
<style>
    .result-card { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); border-left: 8px solid #007bff; border-radius: 15px !important; }
    .status-checked-in { border-left-color: #28a745 !important; background-color: #f8fff9 !important; opacity: 0.8; }
    .recent-item { border-left: 4px solid #28a745 !important; }
</style>
@endsection

@section('scripts')
<script>
    const scannerInput = document.getElementById('scannerInput');
    const resultsWrapper = document.getElementById('resultsWrapper');
    const noResults = document.getElementById('noResults');
    const recentArrivals = document.getElementById('recentArrivals');
    
    // Auto-focus scanner input
    document.addEventListener('keydown', () => { if(document.activeElement !== scannerInput) scannerInput.focus(); });

    async function processAutoCheckIn(id) {
        try {
            const response = await fetch(`{{ route('admin.staff.check-in') }}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ booking_id: id })
            });
            const data = await response.json();
            
            if (data.success) {
                playSuccess();
                fetchResults(id);
                fetchRecent();
                scannerInput.value = '';
            } else {
                playError();
                alert(data.message);
            }
        } catch (e) {
            console.error(e);
            playError();
        }
    }

    let searchTimer;
    scannerInput.addEventListener('keyup', function() {
        clearTimeout(searchTimer);
        const q = this.value.trim();
        if (q.length < 2) { resultsWrapper.innerHTML = ''; return; }
        searchTimer = setTimeout(() => fetchResults(q), 300);
    });

    async function fetchResults(q) {
        try {
            const response = await fetch(`{{ route('admin.staff.search') }}?q=${q}`);
            const data = await response.json();
            renderResults(data);
        } catch (e) { console.error(e); }
    }

    function renderResults(bookings) {
        resultsWrapper.innerHTML = '';
        if (bookings.length === 0) { noResults.classList.remove('d-none'); return; }
        noResults.classList.add('d-none');

        bookings.forEach(b => {
            const isCheckedIn = b.check_in_at != null;
            const card = document.createElement('div');
            card.className = `card shadow-sm border-0 mb-3 result-card ${isCheckedIn ? 'status-checked-in' : ''}`;
            card.innerHTML = `
                <div class="card-body p-4 d-flex align-items-center justify-content-between">
                    <div>
                        <h5 class="fw-bold text-dark mb-1">${b.full_name}</h5>
                        <p class="text-muted small mb-0">${b.event.title} | Group: ${(b.adult_count || 0) + (b.child_count || 0)}</p>
                    </div>
                    <div>
                        ${isCheckedIn ? 
                            `<span class="text-success fw-bold"><i class="fas fa-check-circle me-1"></i> IN</span>` :
                            `<button class="btn btn-primary px-4 fw-bold rounded-pill" onclick="processAutoCheckIn(${b.id})">CHECK IN</button>`
                        }
                    </div>
                </div>
            `;
            resultsWrapper.appendChild(card);
        });
    }

    function playSuccess() { document.getElementById('soundSuccess').play(); flashScreen(); }
    function playError() { document.getElementById('soundError').play(); }
    function flashScreen() {
        const overlay = document.getElementById('successOverlay');
        overlay.classList.remove('d-none');
        setTimeout(() => overlay.classList.add('d-none'), 300);
    }

    async function fetchRecent() {
        try {
            const response = await fetch(`{{ route('admin.staff.recent') }}`);
            const data = await response.json();
            recentArrivals.innerHTML = '';
            data.forEach(b => {
                const div = document.createElement('div');
                div.className = 'list-group-item recent-item p-3 border-0 border-bottom';
                div.innerHTML = `<div class="fw-bold small">${b.full_name}</div><div class="text-muted small">${b.check_in_at.split('T')[1].substring(0,5)}</div>`;
                recentArrivals.appendChild(div);
            });
        } catch (e) { console.error(e); }
    }

    document.addEventListener('DOMContentLoaded', () => {
        fetchRecent();
        setInterval(fetchRecent, 30000); // Pulse check every 30s
    });
</script>
@endsection
