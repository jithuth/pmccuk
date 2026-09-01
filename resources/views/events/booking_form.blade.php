@extends('layouts.app')

@section('title', 'Book Event - ' . $event->title)

@section('content')
<div class="min-h-screen bg-[#f8fafc] py-16">
    <div class="max-w-4xl mx-auto px-4">
        <div class="bg-white rounded-[2.5rem] shadow-2xl overflow-hidden border border-slate-100">
            <div class="grid grid-cols-1 md:grid-cols-5">
                <!-- Left Sidebar: Event Info -->
                <div class="md:col-span-2 bg-slate-900 p-12 text-white relative">
                    <div class="absolute top-0 left-0 w-full h-full opacity-20 bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')]"></div>
                    
                    <div class="relative z-10">
                        <a href="{{ route('events.show', $event->id) }}" class="text-slate-400 hover:text-white mb-8 inline-block transition-colors text-sm font-bold uppercase tracking-widest">
                            <i class="fas fa-arrow-left mr-2 font-black text-primary"></i> Back to Event
                        </a>
                        
                        <div class="mt-12">
                            <span class="inline-block px-4 py-1.5 bg-primary/20 text-primary rounded-full text-[10px] font-black uppercase tracking-widest mb-4">Event Booking</span>
                            <h2 class="text-4xl font-extrabold mb-6 leading-tight">{{ $event->title }}</h2>
                            
                            <div class="space-y-6 text-slate-300">
                                <div class="flex items-start">
                                    <i class="fas fa-calendar-alt mt-1.5 mr-4 text-primary"></i>
                                    <div>
                                        <p class="text-white font-bold">Date & Time</p>
                                        <p class="text-sm">{{ date('l, F d, Y', strtotime($event->event_date)) }}</p>
                                    </div>
                                </div>
                                <div class="flex items-start">
                                    <i class="fas fa-map-marker-alt mt-1.5 mr-4 text-primary"></i>
                                    <div>
                                        <p class="text-white font-bold">Location</p>
                                        <p class="text-sm">{{ $event->location }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-24 p-8 bg-white/5 rounded-3xl border border-white/10 backdrop-blur-sm">
                            <h4 class="text-sm font-black uppercase tracking-widest text-primary mb-4">Payment Support</h4>
                            <p class="text-xs text-slate-400 leading-relaxed">After submission, you will receive our bank details to complete the transfer. Your booking will be confirmed once payment is verified.</p>
                        </div>
                    </div>
                </div>

                <!-- Right Side: Booking Form -->
                <div class="md:col-span-3 p-12">
                    <form action="{{ route('event.book.process') }}" method="POST" enctype="multipart/form-data" id="bookingForm">
                        @csrf
                        <input type="hidden" name="event_id" value="{{ $event->id }}">

                        <!-- Step 1: Member Status -->
                        <div class="mb-12">
                            <h3 class="text-xl font-bold text-slate-900 mb-6 flex items-center">
                                <span class="w-8 h-8 bg-primary text-white rounded-full flex items-center justify-center text-sm mr-4 shadow-lg shadow-primary/20">1</span>
                                Attendee Details
                            </h3>
                            
                            <input type="hidden" name="is_member" id="is_member_input" value="1">
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
                                <label class="relative cursor-pointer">
                                    <input type="radio" name="attendee_type" value="member" class="peer sr-only" checked onclick="toggleMember('member')">
                                    <div class="p-5 bg-slate-50 rounded-2xl border-2 border-transparent peer-checked:border-primary peer-checked:bg-white transition-all text-center">
                                        <i class="fas fa-id-card text-2xl mb-2 text-slate-400 peer-checked:text-primary"></i>
                                        <p class="font-bold text-sm text-slate-900">PMCC Member</p>
                                        <p class="text-[10px] text-slate-500 uppercase tracking-wider">Member Rates</p>
                                    </div>
                                </label>
                                <label class="relative cursor-pointer">
                                    <input type="radio" name="attendee_type" value="guest" class="peer sr-only" onclick="toggleMember('guest')">
                                    <div class="p-5 bg-slate-50 rounded-2xl border-2 border-transparent peer-checked:border-primary peer-checked:bg-white transition-all text-center">
                                        <i class="fas fa-user-friends text-2xl mb-2 text-slate-400 peer-checked:text-primary"></i>
                                        <p class="font-bold text-sm text-slate-900">Guest / Public</p>
                                        <p class="text-[10px] text-slate-500 uppercase tracking-wider">Standard Rates</p>
                                    </div>
                                </label>
                                <label class="relative cursor-pointer">
                                    <input type="radio" name="attendee_type" value="student" class="peer sr-only" onclick="toggleMember('student')">
                                    <div class="p-5 bg-amber-50/80 rounded-2xl border-2 border-transparent peer-checked:border-amber-500 peer-checked:bg-white transition-all text-center">
                                        <i class="fas fa-graduation-cap text-2xl mb-2 text-amber-500"></i>
                                        <p class="font-bold text-sm text-slate-900">Student Pass</p>
                                        <p class="text-[10px] text-amber-600 font-bold uppercase tracking-wider">Discounted Rate</p>
                                    </div>
                                </label>
                            </div>

                            <div id="memberField" class="mb-6 space-y-4">
                                <div>
                                    <label class="block text-xs font-black uppercase tracking-widest text-slate-400 mb-2">Membership ID</label>
                                    <div class="relative flex items-center bg-slate-50 rounded-xl overflow-hidden shadow-inner border border-slate-200">
                                        <div class="bg-slate-200 px-5 py-4 text-slate-600 font-black border-r border-slate-300">PMCC-</div>
                                        <input type="text" id="membership_short_no" placeholder="101" onkeyup="autoVerify()"
                                            class="flex-1 bg-transparent border-0 px-4 py-4 focus:ring-0 transition-all text-slate-900 font-bold placeholder:text-slate-300">
                                        <input type="hidden" name="membership_no" id="membership_no">
                                        <button type="button" onclick="verifyID()" id="verifyBtn" class="mr-2 bg-slate-900 text-white px-4 py-2 rounded-lg text-[10px] font-black uppercase tracking-widest hover:bg-primary transition-colors shadow-sm">Verify ID</button>
                                    </div>
                                    <p id="verifyMsg" class="mt-2 text-[10px] font-bold hidden"></p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-xs font-black uppercase tracking-widest text-slate-400 mb-2">Email Address</label>
                                    <input type="email" name="email" id="email" onkeyup="checkGuestEmail()" required
                                        class="w-full bg-slate-50 border-0 rounded-xl px-6 py-4 focus:ring-2 focus:ring-primary transition-all text-slate-900 font-bold placeholder:text-slate-300">
                                </div>
                                <div>
                                    <label class="block text-xs font-black uppercase tracking-widest text-slate-400 mb-2">Phone Number</label>
                                    <input type="text" name="phone" id="phone"
                                        class="w-full bg-slate-50 border-0 rounded-xl px-6 py-4 focus:ring-2 focus:ring-primary transition-all text-slate-900 font-bold placeholder:text-slate-300">
                                </div>

                                <div class="md:col-span-2 border-y border-slate-50 py-6 my-2">
                                    <label class="block text-xs font-black uppercase tracking-widest text-slate-400 mb-2">Verification Code (OTP)</label>
                                    <div class="relative flex gap-2">
                                        <div class="relative flex-1">
                                            <input type="text" name="otp" id="otp" placeholder="Check your email for code"
                                                class="w-full bg-slate-50 border-0 rounded-xl px-6 py-4 focus:ring-2 focus:ring-primary transition-all text-slate-900 font-bold placeholder:text-slate-300">
                                            <button type="button" onclick="sendOTP()" id="otpBtn" class="absolute right-3 top-2 bottom-2 bg-slate-200 text-slate-600 px-4 rounded-lg text-[10px] font-black uppercase tracking-widest hover:bg-primary hover:text-white transition-colors">Send OTP</button>
                                        </div>
                                        <button type="button" onclick="verifyOTP()" id="verifyOtpBtn" class="bg-primary text-white px-8 rounded-xl text-xs font-black uppercase tracking-widest hover:bg-slate-900 transition-colors shadow-lg shadow-primary/20">Verify</button>
                                    </div>
                                    <p id="otpMsg" class="mt-2 text-[10px] font-bold hidden"></p>
                                </div>

                                <div class="md:col-span-2">
                                    <label class="block text-xs font-black uppercase tracking-widest text-slate-400 mb-2">Full Name</label>
                                    <input type="text" name="full_name" id="full_name" required
                                        class="w-full bg-slate-50 border-0 rounded-xl px-6 py-4 focus:ring-2 focus:ring-primary transition-all text-slate-900 font-bold placeholder:text-slate-300">
                                </div>
                            </div>
                        </div>

                        <!-- Step 2: Tickets -->
                        <div class="mb-12 transition-all" id="ticketSection">
                            <h3 class="text-xl font-bold text-slate-900 mb-6 flex items-center">
                                <span class="w-8 h-8 bg-primary text-white rounded-full flex items-center justify-center text-sm mr-4 shadow-lg shadow-primary/20">2</span>
                                Select Tickets
                            </h3>
                            
                            <div class="space-y-4">
                                @foreach($pricing as $p)
                                <div class="ticket-row p-6 bg-slate-50 rounded-[1.5rem] flex items-center justify-between border border-transparent hover:border-slate-200 transition-all" data-name="{{ $p['name'] }}">
                                    <div>
                                        <p class="font-bold text-slate-900">{{ $p['name'] }}</p>
                                        <p class="text-sm font-bold text-primary">
                                            £<span class="price-val" data-member="{{ $p['member_price'] }}" data-guest="{{ $p['guest_price'] }}">
                                                {{ $p['member_price'] }}
                                            </span>
                                        </p>
                                        <input type="hidden" name="prices[{{ $p['category_id'] }}]" class="actual-price-input" value="{{ $p['member_price'] }}">
                                    </div>
                                    <div class="flex items-center bg-white rounded-xl shadow-sm border border-slate-100 p-1">
                                        <button type="button" onclick="updateQty(this, -1)" class="w-8 h-8 flex items-center justify-center text-slate-400 hover:text-primary transition-colors">
                                            <i class="fas fa-minus text-xs"></i>
                                        </button>
                                        <input type="number" name="counts[{{ $p['category_id'] }}]" value="0" min="0" 
                                            class="w-12 text-center border-0 focus:ring-0 font-black text-slate-900 py-0 qty-input" onchange="calculateTotal()">
                                        <button type="button" onclick="updateQty(this, 1)" class="w-8 h-8 flex items-center justify-center text-slate-400 hover:text-primary transition-colors">
                                            <i class="fas fa-plus text-xs"></i>
                                        </button>
                                    </div>
                                </div>
                                @endforeach
                        </div>

                        <!-- Step 3: Student Verification Document (Conditional) -->
                        <div class="mb-12 hidden transition-all" id="studentDocSection">
                            <h3 class="text-xl font-bold text-slate-900 mb-2 flex items-center">
                                <span class="w-8 h-8 bg-amber-500 text-white rounded-full flex items-center justify-center text-sm mr-4 shadow-lg shadow-amber-500/20">3</span>
                                Student Proof Verification
                            </h3>
                            <p class="text-xs text-slate-500 mb-4 ml-12">
                                Since you have selected Student ticket(s), please upload a valid Student ID Card or proof of student status.
                            </p>

                            <div class="ml-12 p-6 bg-amber-50/60 rounded-2xl border border-amber-200/80">
                                <label class="block text-xs font-black uppercase tracking-widest text-slate-700 mb-3">
                                    Upload Student ID Card / Document <span class="text-red-500">*</span>
                                </label>
                                <input type="file" name="student_doc" id="student_doc" accept=".jpg,.jpeg,.png,.webp,.pdf"
                                    class="w-full text-xs text-slate-600 file:mr-4 file:py-3 file:px-6 file:rounded-xl file:border-0 file:text-xs file:font-black file:uppercase file:tracking-wider file:bg-slate-900 file:text-white hover:file:bg-primary file:transition-colors file:cursor-pointer">
                                <p class="text-[10px] font-medium text-slate-400 mt-2">Accepted formats: JPG, PNG, WEBP, PDF (Max 5MB)</p>
                            </div>
                        </div>

                        <!-- Summary & Submit -->
                        <div class="mt-16 pt-8 border-t border-slate-100">
                            <div class="flex items-center justify-between mb-8">
                                <p class="text-sm font-black uppercase tracking-[0.3em] text-slate-400">Total Payment Due</p>
                                <p class="text-4xl font-black text-slate-900">£<span id="totalDisplay">0.00</span></p>
                            </div>
                            
                            <button type="submit" class="w-full bg-primary hover:bg-slate-900 text-white font-black uppercase tracking-widest text-sm py-6 rounded-2xl shadow-xl shadow-primary/20 transition-all transform hover:-translate-y-1 active:scale-[0.98]">
                                Complete Booking <i class="fas fa-chevron-right ml-4"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function toggleMember(mode) {
        // Normalize boolean calls if any legacy call exists
        if (mode === true) mode = 'member';
        if (mode === false) mode = 'guest';

        const isM = (mode === 'member');
        const isStudentMode = (mode === 'student');
        const isGuestMode = (mode === 'guest');

        const field = document.getElementById('memberField');
        const prices = document.querySelectorAll('.price-val');
        const priceInputs = document.querySelectorAll('.actual-price-input');
        const rows = document.querySelectorAll('.ticket-row');
        const isMemberInput = document.getElementById('is_member_input');

        if (isMemberInput) {
            isMemberInput.value = isM ? '1' : '0';
        }
        
        // Locking Section
        const submitBtn = document.querySelector('button[type="submit"]');
        const ticketSection = document.getElementById('ticketSection');
        submitBtn.disabled = true;
        submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
        ticketSection.classList.add('opacity-40', 'pointer-events-none');
        
        if (isM) {
            field.classList.remove('hidden');
        } else {
            field.classList.add('hidden');
            document.getElementById('email').readOnly = false;
            document.getElementById('full_name').readOnly = false;
            document.getElementById('email').value = '';
            document.getElementById('full_name').value = '';
            document.getElementById('phone').value = '';
            document.getElementById('membership_no').value = '';
        }

        rows.forEach((row, idx) => {
            const name = row.getAttribute('data-name').toLowerCase();
            const p = prices[idx];
            const isStudentRow = name.includes('student');
            
            // 1. Update Price: Students receive equal pricing (member rate) for both members and non-members
            const val = (isM || isStudentRow) ? p.dataset.member : p.dataset.guest;
            p.innerText = val;
            priceInputs[idx].value = val;

            // 2. Filter per mode
            if (isStudentMode) {
                // Dedicated Student Pass View: Show only Student tickets
                if (isStudentRow) {
                    row.classList.remove('hidden');
                    const qtyInput = row.querySelector('.qty-input');
                    if (parseInt(qtyInput.value) === 0) {
                        qtyInput.value = 1;
                    }
                } else {
                    row.classList.add('hidden');
                    row.querySelector('.qty-input').value = 0;
                }
            } else if (isGuestMode) {
                // Standard Guest View: Show Adults, Children, Infants. Hide Student & Family/Sponsor
                const isAdult = name.includes('adult');
                const isChild = name.includes('child') || name.includes('kids') || name.includes('kid');
                const isInfant = name.includes('infant');
                const isFamilyOrSponsor = name.includes('family') || name.includes('sponsor');
                
                if ((isAdult || isChild || isInfant) && !isFamilyOrSponsor && !isStudentRow) {
                    row.classList.remove('hidden');
                } else {
                    row.classList.add('hidden');
                    row.querySelector('.qty-input').value = 0; 
                }
            } else {
                // Member View: Show all rows
                row.classList.remove('hidden');
            }
        });
        
        calculateTotal();
    }

    function updateQty(btn, delta) {
        const input = btn.parentElement.querySelector('.qty-input');
        let val = parseInt(input.value) + delta;
        if (val < 0) val = 0;
        input.value = val;
        calculateTotal();
    }

    function calculateTotal() {
        let total = 0;
        let hasStudentTicket = false;

        document.querySelectorAll('.ticket-row').forEach(row => {
            if (!row.classList.contains('hidden')) {
                const price = parseFloat(row.querySelector('.price-val').innerText);
                const qty = parseInt(row.querySelector('.qty-input').value);
                const name = row.getAttribute('data-name').toLowerCase();

                total += (price * qty);

                if (qty > 0 && name.includes('student')) {
                    hasStudentTicket = true;
                }
            }
        });
        document.getElementById('totalDisplay').innerText = total.toFixed(2);

        // Toggle student proof upload requirement dynamically
        const studentSec = document.getElementById('studentDocSection');
        const studentDocInput = document.getElementById('student_doc');
        if (studentSec && studentDocInput) {
            if (hasStudentTicket) {
                studentSec.classList.remove('hidden');
                studentDocInput.required = true;
            } else {
                studentSec.classList.add('hidden');
                studentDocInput.required = false;
            }
        }
    }

    let searchTimer;
    function autoVerify() {
        clearTimeout(searchTimer);
        const shortNo = document.getElementById('membership_short_no').value;
        if (shortNo.length >= 1) { 
            searchTimer = setTimeout(() => {
                verifyID();
            }, 800);
        }
    }

    let emailTimer;
    function checkGuestEmail() {
        const isMember = document.querySelector('input[name="is_member"]:checked').value == '1';
        if (isMember) return; // Only for guests
        
        const email = document.getElementById('email').value.trim();
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        
        if (emailRegex.test(email)) {
            clearTimeout(emailTimer);
            emailTimer = setTimeout(() => {
                // Only send if it's different from the last sent email
                sendOTP();
            }, 1000); // 1 second delay after they stop typing
        }
    }

    let lastVerifiedId = '';
    function verifyID() {
        const shortNo = document.getElementById('membership_short_no').value.trim();
        const fullNo = 'PMCC-' + shortNo;
        document.getElementById('membership_no').value = fullNo;

        const btn = document.getElementById('verifyBtn');
        const msg = document.getElementById('verifyMsg');

        if (!shortNo) return;

        btn.disabled = true;
        btn.innerText = 'Checking...';
        msg.classList.add('hidden');

        fetch(`{{ route('event.verify-member') }}?membership_id=${fullNo}`)
            .then(res => res.json())
            .then(data => {
                btn.disabled = false;
                btn.innerText = 'Verify ID';
                msg.classList.remove('hidden');
                
                if (data.success) {
                    msg.className = "mt-2 text-[10px] font-bold text-green-500";
                    msg.innerText = "✓ Member Found: Sending verification code to " + data.masked_email;
                    document.getElementById('email').value = data.masked_email;
                    document.getElementById('email').readOnly = true;
                    
                    // Auto-send OTP if this ID hasn't been verified in this session yet
                    if (lastVerifiedId !== fullNo) {
                        lastVerifiedId = fullNo;
                        sendOTP();
                    }
                } else {
                    lastVerifiedId = ''; // Reset on failure
                    msg.className = "mt-2 text-[10px] font-bold text-red-500";
                    msg.innerText = "✗ " + data.message;
                }
            });
    }

    let otpTimer;
    function startOTPTimer(duration) {
        const btn = document.getElementById('otpBtn');
        let timer = duration;
        btn.disabled = true;
        
        clearInterval(otpTimer);
        otpTimer = setInterval(() => {
            btn.innerText = `Resend in ${timer}s`;
            if (--timer < 0) {
                clearInterval(otpTimer);
                btn.innerText = 'Send OTP';
                btn.disabled = false;
            }
        }, 1000);
    }

    function sendOTP() {
        const shortNo = document.getElementById('membership_short_no').value.trim();
        const fullNo = 'PMCC-' + shortNo;
        const email = document.getElementById('email').value;
        const isMember = document.querySelector('input[name="is_member"]:checked').value == '1';
        
        const btn = document.getElementById('otpBtn');
        const msg = document.getElementById('otpMsg');

        if (isMember && !shortNo) {
            Swal.fire('Error', 'Please enter and verify your Membership ID first', 'error');
            return;
        }
        if (!isMember && !email) {
            Swal.fire('Error', 'Please enter your email address', 'error');
            return;
        }

        btn.disabled = true;
        const originalText = btn.innerText;
        btn.innerText = 'Sending...';
        msg.classList.add('hidden');

        let payload = { email: email };
        if (isMember) payload.membership_id = fullNo;

        fetch(`{{ route('event.send-otp') }}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(data => {
            msg.classList.remove('hidden');
            
            if (data.success) {
                msg.className = "mt-2 text-[10px] font-bold text-green-500";
                msg.innerText = data.message;
                
                Swal.fire({
                    title: 'Verification Sent!',
                    text: data.message,
                    icon: 'success',
                    timer: 3000,
                    showConfirmButton: false
                });

                startOTPTimer(60); // 60 seconds throttle
            } else {
                btn.disabled = false;
                btn.innerText = originalText;
                msg.className = "mt-2 text-[10px] font-bold text-red-500";
                msg.innerText = data.message;
                Swal.fire('Error', data.message, 'error');
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerText = originalText;
            Swal.fire('Error', 'Failed to connect to server. Please try again.', 'error');
        });
    }

    function verifyOTP() {
        const otp = document.getElementById('otp').value;
        const btn = document.getElementById('verifyOtpBtn');
        const msg = document.getElementById('otpMsg');

        if (!otp) return;

        btn.disabled = true;
        btn.innerText = '...';

        fetch(`{{ route('event.verify-otp') }}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ otp: otp })
        })
        .then(res => res.json())
        .then(data => {
            btn.disabled = false;
            btn.innerText = 'Verify';
            
            if (data.success) {
                msg.classList.remove('hidden');
                msg.className = "mt-2 text-[10px] font-bold text-green-500";
                msg.innerText = "✓ Identity Verified Successfully";
                
                // UNLOCK EVERYTHING
                const submitBtn = document.querySelector('button[type="submit"]');
                const ticketSection = document.getElementById('ticketSection');
                submitBtn.disabled = false;
                submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                ticketSection.classList.remove('opacity-40', 'pointer-events-none');

                // Auto-fill full details
                if (data.name) {
                    document.getElementById('full_name').value = data.name;
                    document.getElementById('email').value = data.email;
                    document.getElementById('phone').value = data.phone;
                    
                    // Mark as readonly to prevent alteration after verification
                    document.getElementById('full_name').readOnly = true;
                    document.getElementById('email').readOnly = true;
                    document.getElementById('phone').readOnly = true;
                }
            } else {
                msg.classList.remove('hidden');
                msg.className = "mt-2 text-[10px] font-bold text-red-500";
                msg.innerText = "✗ " + data.message;
            }
        });
    }

    // Initial load
    toggleMember('member');
</script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endsection
