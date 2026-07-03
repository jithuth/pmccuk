@extends('layouts.app')

@section('content')
<section class="py-12 bg-slate-50 min-h-screen">
    <div class="max-w-4xl mx-auto px-4">
        <!-- Header -->
        <div class="mb-10 text-center">
            <h1 class="text-4xl font-extrabold text-primary mb-2">Membership Registration 2026</h1>
            <div class="h-1 w-24 bg-secondary mx-auto mb-6"></div>
            <p class="text-slate-600">Please complete the steps below to register or renew your membership.</p>
        </div>

        @if(session('status') == 'success')
            <div id="success-view" class="bg-white border border-slate-200 p-12 rounded-3xl shadow-2xl text-center mb-12 animate-fade-in">
                <div class="w-20 h-20 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-6 text-3xl">
                    <i class="fas fa-check"></i>
                </div>
                <h2 class="text-3xl font-black text-slate-900 mb-4 uppercase">Application Submitted!</h2>
                <div class="bg-blue-50 p-6 rounded-2xl border border-blue-100 mb-8 text-left max-w-md mx-auto">
                    <p class="text-slate-700 mb-2 font-bold">What's next?</p>
                    <ul class="list-disc list-inside text-slate-600 space-y-2 text-sm font-medium">
                        <li>Check your email for payment instructions</li>
                        <li>Submit your reference/receipt to Treasurer</li>
                        <li>Wait for approval notification</li>
                    </ul>
                </div>
                <a href="{{ url('/') }}" class="inline-block px-10 py-4 bg-primary text-white font-black rounded-xl hover:bg-blue-900 transition-all uppercase tracking-widest shadow-lg">Return to Homepage</a>
            </div>
        @else

        <!-- Wizard Container -->
        <div class="bg-white shadow-xl rounded-2xl border border-slate-100 overflow-hidden relative" id="wizard-container">

            <!-- STEP 0: Registration Check -->
            <div id="step-check" class="p-10 md:p-14 text-center wizard-step">
                <div class="w-20 h-20 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-6 text-primary text-3xl">
                    <i class="fas fa-question"></i>
                </div>
                <h2 class="text-2xl font-bold text-slate-800 mb-4">Are you already registered with PMCC?</h2>
                <div class="flex flex-col md:flex-row justify-center gap-6 mb-8" id="check-buttons">
                    <button onclick="showRegInput()" class="px-8 py-4 bg-white border-2 border-primary text-primary font-bold rounded-xl hover:bg-primary hover:text-white transition-all shadow-md flex items-center justify-center gap-3">
                        <i class="fas fa-check-circle"></i> Yes, I am a Member
                    </button>
                    <button onclick="startNewReg()" class="px-8 py-4 bg-white border-2 border-slate-200 text-slate-600 font-bold rounded-xl hover:border-secondary hover:text-secondary transition-all shadow-md flex items-center justify-center gap-3">
                        <i class="fas fa-user-plus"></i> No, New Registration
                    </button>
                </div>

                <!-- Hidden Reg Input -->
                <div id="reg-input-area" class="hidden animate-fade-in max-w-md mx-auto bg-slate-50 p-6 rounded-xl border border-slate-200">
                    <label class="block text-left text-sm font-bold text-slate-700 mb-2">Enter Your Membership Number</label>
                    <div class="flex gap-2">
                        <div class="flex-1 flex items-center border border-slate-300 rounded focus-within:border-primary bg-white transition-all overflow-hidden">
                            <span class="px-4 py-3 text-slate-600 font-bold bg-slate-100 border-r border-slate-300">PMCC-</span>
                            <input type="text" id="temp_reg_no" inputmode="numeric" class="flex-1 px-4 py-3 outline-none w-full" placeholder="104" onkeyup="autoProceedRenewal()">
                        </div>
                        <button onclick="proceedRenewal()" id="renewal-next-btn" class="px-6 py-3 bg-primary text-white font-bold rounded hover:bg-blue-900 transition flex items-center justify-center min-w-[80px]">Next</button>
                    </div>
                    <div id="renewal-msg" class="mt-4 text-xs font-bold hidden text-center animate-fade-in"></div>
                </div>

                <!-- OTP Verification Area -->
                <div id="otp-area" class="hidden animate-fade-in max-w-md mx-auto bg-slate-50 p-10 rounded-2xl border border-slate-200 text-center my-10 relative">
                    <button onclick="resetStepCheck()" class="absolute top-4 left-4 text-slate-400 hover:text-primary"><i class="fas fa-arrow-left"></i></button>
                    <h3 class="text-2xl font-bold text-slate-800 mb-3">Security Verification</h3>
                    <p class="text-slate-500 mb-8 text-sm">We've sent a code to <span id="masked-email" class="font-bold text-primary"></span></p>
                    <input type="text" maxlength="6" id="otp_code" class="w-full text-center text-4xl font-extrabold tracking-[0.5em] py-4 border-2 border-slate-300 rounded-xl mb-8 focus:border-primary outline-none transition-all" placeholder="000000">
                    <button onclick="verifyOTP()" id="verify-otp-btn" class="w-full py-4 bg-primary text-white font-bold rounded-xl hover:bg-blue-900 transition-all shadow-lg uppercase tracking-widest">VERIFY & PROCEED</button>
                </div>
            </div>

            <!-- STEP FORM CONTAINER -->
            <form action="{{ route('membership.submit') }}" method="POST" id="main-form" class="hidden" enctype="multipart/form-data">
                @csrf
                {{-- Honeypot field for anti-spam --}}
                <div style="display: none;">
                    <input type="text" name="pmcc_identity_confirm" value="">
                </div>
                <input type="hidden" name="prev_membership_no" id="final_reg_no">

                <!-- Progress -->
                <div class="bg-slate-50 border-b border-slate-100 flex justify-between px-10 py-5 items-center">
                    <div class="flex items-center space-x-3 text-primary">
                        <span class="w-10 h-10 rounded-full bg-primary text-white flex items-center justify-center text-sm font-black shadow-lg" id="progress-num">1</span>
                        <div>
                            <span class="text-[10px] font-black uppercase tracking-widest text-slate-400 block leading-none">Status</span>
                            <span class="text-sm font-bold uppercase tracking-wider" id="progress-text">Personal Details</span>
                        </div>
                    </div>
                    <div class="text-slate-400 text-xs font-black uppercase tracking-widest">Step <span id="current-step-display">1</span> of 4</div>
                </div>

                <div class="p-8 md:p-12">
                    <!-- Step 1: Personal -->
                    <div class="wizard-step animate-fade-in" id="step-1">
                        <h3 class="text-xl font-bold text-slate-800 mb-8 flex items-center">
                            <span class="w-2 h-8 bg-secondary mr-4 rounded-full"></span> Personal Details
                        </h3>

                        <div class="mb-10 p-6 bg-blue-50/50 rounded-2xl border border-blue-100">
                            <label class="block text-xs font-black text-slate-400 mb-4 uppercase tracking-widest">Membership Type *</label>
                            <div class="grid grid-cols-2 gap-4">
                                <label class="flex items-center justify-center p-5 bg-white border-2 border-slate-200 rounded-2xl cursor-pointer hover:border-primary transition-all group relative overflow-hidden">
                                    <input type="radio" name="membership_type" value="Family" checked onclick="handleMembershipType('Family')" class="absolute opacity-0">
                                    <div class="text-center">
                                        <div class="font-black text-slate-800 group-hover:text-primary transition-colors">FAMILY</div>
                                        <div class="text-xs font-bold text-slate-400 group-hover:text-slate-600">£5.00 / Year</div>
                                    </div>
                                </label>
                                <label class="flex items-center justify-center p-5 bg-white border-2 border-slate-200 rounded-2xl cursor-pointer hover:border-primary transition-all group relative overflow-hidden">
                                    <input type="radio" name="membership_type" value="Single" onclick="handleMembershipType('Single')" class="absolute opacity-0">
                                    <div class="text-center">
                                        <div class="font-black text-slate-800 group-hover:text-primary transition-colors">SINGLE</div>
                                        <div class="text-xs font-bold text-slate-400 group-hover:text-slate-600">£5.00 / Year</div>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div class="md:col-span-2">
                                <label class="block text-sm font-bold text-slate-700 mb-2">Email Address *</label>
                                <input type="email" name="email" required id="f_email" class="w-full px-5 py-4 rounded-xl bg-slate-50 border border-transparent focus:bg-white focus:border-primary outline-none transition-all">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Title *</label>
                                <select name="title" required id="f_title" class="w-full px-5 py-4 rounded-xl bg-slate-50 border border-transparent focus:bg-white focus:border-primary outline-none transition-all appearance-none">
                                    <option value="Mr">Mr.</option>
                                    <option value="Mrs">Mrs.</option>
                                    <option value="Ms">Ms.</option>
                                    <option value="Dr">Dr.</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Full Name *</label>
                                <input type="text" name="full_name" required id="f_full_name" class="w-full px-5 py-4 rounded-xl bg-slate-50 border border-transparent focus:bg-white focus:border-primary outline-none transition-all" placeholder="As per ID">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Mobile Number *</label>
                                <input type="text" name="mobile_number" required id="f_mobile" class="w-full px-5 py-4 rounded-xl bg-slate-50 border border-transparent focus:bg-white focus:border-primary outline-none transition-all" placeholder="07XXXXXXXXX">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Date of Birth *</label>
                                <input type="date" name="dob" required id="f_dob" class="w-full px-5 py-4 rounded-xl bg-slate-50 border border-transparent focus:bg-white focus:border-primary outline-none transition-all">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2 text-primary">Your Photo <small>(Passport Style)</small></label>
                                <input type="file" name="member_photo" accept="image/*" class="w-full px-5 py-4 rounded-xl bg-slate-50 border border-transparent focus:bg-white focus:border-primary outline-none transition-all text-xs">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Marital Status *</label>
                                <div class="flex gap-4 mt-2">
                                    <label class="flex items-center"><input type="radio" name="marital_status" value="Married" checked onclick="toggleSpouse(true)" class="mr-2"> Married</label>
                                    <label class="flex items-center"><input type="radio" name="marital_status" value="Single" onclick="toggleSpouse(false)" class="mr-2"> Single</label>
                                </div>
                            </div>
                        </div>

                        <div id="spouse_field" class="mt-8 p-6 bg-slate-50 rounded-2xl border border-slate-100 flex flex-col md:flex-row gap-6 animate-fade-in">
                            <div class="flex-1">
                                <label class="block text-xs font-black text-slate-400 mb-2 uppercase tracking-widest">Spouse Name</label>
                                <input type="text" name="spouse_name" id="f_spouse_name" class="w-full px-4 py-3 rounded-lg border border-slate-200 outline-none">
                            </div>
                             <div class="flex-1">
                                <label class="block text-xs font-black text-slate-400 mb-2 uppercase tracking-widest">Spouse DOB</label>
                                <input type="date" name="spouse_dob" id="f_spouse_dob" class="w-full px-4 py-3 rounded-lg border border-slate-200 outline-none">
                            </div>
                        </div>

                        <div class="mt-12 flex justify-end">
                            <button type="button" onclick="nextStep(2)" class="px-10 py-5 bg-primary text-white font-black uppercase tracking-widest rounded-2xl shadow-xl shadow-blue-900/10 hover:bg-secondary transition-all flex items-center">
                                Next Step <i class="fas fa-arrow-right ml-3"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Step 2: Address & Emergency -->
                    <div class="wizard-step hidden animate-fade-in" id="step-2">
                         <h3 class="text-xl font-bold text-slate-800 mb-8 flex items-center">
                            <span class="w-2 h-8 bg-secondary mr-4 rounded-full"></span> Address Details
                        </h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Post Code *</label>
                                <input type="text" name="post_code" required id="f_post_code" class="w-full px-5 py-4 rounded-xl bg-slate-50 border border-transparent focus:bg-white focus:border-primary outline-none transition-all uppercase">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-bold text-slate-700 mb-2">House Details & Street *</label>
                                <textarea name="house_details" id="f_house_details" rows="3" required class="w-full px-5 py-4 rounded-xl bg-slate-50 border border-transparent focus:bg-white focus:border-primary outline-none transition-all"></textarea>
                            </div>
                        </div>

                        <h3 class="text-xl font-bold text-slate-800 mt-12 mb-8 flex items-center">
                            <span class="w-2 h-8 bg-secondary mr-4 rounded-full"></span> Emergency Contact
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Contact Name *</label>
                                <input type="text" name="emergency_name" required id="f_emergency_name" class="w-full px-5 py-4 rounded-xl bg-slate-50 border border-transparent focus:bg-white focus:border-primary outline-none transition-all">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Contact Mobile *</label>
                                <input type="text" name="emergency_mobile" required id="f_emergency_mobile" class="w-full px-5 py-4 rounded-xl bg-slate-50 border border-transparent focus:bg-white focus:border-primary outline-none transition-all">
                            </div>
                        </div>

                        <div class="mt-12 flex justify-between">
                            <button type="button" onclick="nextStep(1)" class="px-8 py-4 text-slate-400 font-bold hover:text-primary transition-all uppercase tracking-widest text-xs">Back</button>
                            <button type="button" onclick="nextStep(3)" class="px-10 py-5 bg-primary text-white font-black uppercase tracking-widest rounded-2xl shadow-xl shadow-blue-900/10 hover:bg-secondary transition-all flex items-center">
                                Next Step <i class="fas fa-arrow-right ml-3"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Step 3: Family Info -->
                    <div class="wizard-step hidden animate-fade-in" id="step-3">
                        <h3 class="text-xl font-bold text-slate-800 mb-8 flex items-center">
                            <span class="w-2 h-8 bg-secondary mr-4 rounded-full"></span> Family Information
                        </h3>

                        <div class="mb-10 p-8 bg-slate-50 rounded-[2rem] border border-slate-100">
                            <label class="block text-sm font-bold text-slate-700 mb-4">Family Photo <small class="text-slate-400 font-bold ml-2">(Group Photo)</small></label>
                            <input type="file" name="family_photo" accept="image/*" class="w-full px-5 py-5 rounded-2xl bg-white border border-slate-200 outline-none transition-all text-sm">
                        </div>

                        <div class="flex justify-between items-center mb-6">
                            <p class="text-sm font-bold text-slate-500 uppercase tracking-wider">Children Details</p>
                            <button type="button" onclick="addChildRow()" class="px-5 py-2.5 bg-green-50 text-green-700 font-bold text-xs rounded-xl border border-green-200 hover:bg-green-100 transition-all flex items-center gap-2">
                                <i class="fas fa-plus"></i> ADD CHILD
                            </button>
                        </div>

                        <div id="children-container" class="space-y-4">
                            <div id="no-children-msg" class="text-center py-10 border-2 border-dashed border-slate-100 rounded-[2rem] text-slate-300 font-bold text-sm">No children added yet.</div>
                        </div>

                        <div class="mt-12 flex justify-between">
                            <button type="button" onclick="nextStep(2)" class="px-8 py-4 text-slate-400 font-bold hover:text-primary transition-all uppercase tracking-widest text-xs">Back</button>
                            <button type="button" onclick="nextStep(4)" class="px-10 py-5 bg-primary text-white font-black uppercase tracking-widest rounded-2xl shadow-xl shadow-blue-900/10 hover:bg-secondary transition-all flex items-center">
                                Next Step <i class="fas fa-arrow-right ml-3"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Step 4: Membership Plan -->
                    <div class="wizard-step hidden animate-fade-in" id="step-4">
                        <h3 class="text-xl font-bold text-slate-800 mb-8 flex items-center">
                            <span class="w-2 h-8 bg-secondary mr-4 rounded-full"></span> Membership Plan
                        </h3>

                        <div class="bg-primary rounded-[2.5rem] p-12 text-center text-white mb-10 shadow-2xl shadow-blue-900/40 relative overflow-hidden">
                            <div class="absolute top-0 right-0 w-32 h-32 bg-white/5 rounded-full -translate-y-1/2 translate-x-1/2"></div>
                            <span class="text-[10px] font-black uppercase tracking-[0.4em] text-white/40 mb-4 block">Selected Package</span>
                            <h2 id="display-plan-name" class="text-4xl font-black mb-2">Family Membership</h2>
                            <p class="text-2xl font-bold text-secondary mb-4 italic">£5.00 / YEAR</p>
                            <p id="display-plan-desc" class="text-white/60 font-medium">Includes Spouse & Children</p>
                        </div>

                        <div class="bg-slate-50/80 p-8 rounded-[2rem] border border-slate-100 mb-10 prose prose-sm max-h-60 overflow-y-auto shadow-inner">
                            <p class="font-bold text-primary mb-4">Terms & Conditions</p>
                            <p class="text-slate-500 leading-relaxed text-xs">{!! $settings['legal_terms_conditions'] ?? 'The membership fee is £5 per annum for both families and individuals. The year runs from January to December. Your data is protected under PMCC\'s privacy policy and used solely for community communication.' !!}</p>
                        </div>

                        <div class="p-6 bg-blue-50/50 rounded-2xl border border-blue-100 mb-10 flex items-start gap-4 cursor-pointer hover:bg-blue-50 transition-all select-none" onclick="document.getElementById('consent_check').click()">
                            <input type="checkbox" name="consent" id="consent_check" required class="w-6 h-6 rounded border-slate-300 text-primary mt-0.5 cursor-pointer">
                            <p class="text-sm font-bold text-slate-700 leading-snug">I certify that all provided information is correct and I agree to the terms and privacy policy mentioned above.</p>
                        </div>

                        <div class="flex justify-between items-center">
                            <button type="button" onclick="nextStep(3)" class="px-8 py-4 text-slate-400 font-bold hover:text-primary transition-all uppercase tracking-widest text-xs">Back</button>
                            <button type="submit" class="px-12 py-5 bg-secondary text-white font-black uppercase tracking-widest rounded-2xl shadow-xl shadow-red-900/30 hover:scale-105 active:scale-95 transition-all flex items-center">
                                Confirm & Submit <i class="fas fa-paper-plane ml-3"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        @endif
    </div>

    <!-- Child Row Template -->
    <template id="child-row-template">
        <div class="child-row bg-white p-6 rounded-2xl border border-slate-100 shadow-sm animate-fade-in grid grid-cols-12 gap-4 relative">
            <div class="col-span-12 md:col-span-4">
                <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Full Name</label>
                <input type="text" name="child_name[]" class="w-full px-4 py-3 rounded-xl bg-slate-50 border border-transparent outline-none focus:border-primary text-sm font-bold">
            </div>
            <div class="col-span-6 md:col-span-3">
                <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Gender</label>
                <select name="child_sex[]" class="w-full px-4 py-3 rounded-xl bg-slate-50 border border-transparent outline-none focus:border-primary text-sm font-bold appearance-none">
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                </select>
            </div>
             <div class="col-span-6 md:col-span-4">
                <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Date of Birth</label>
                <input type="date" name="child_dob[]" class="w-full px-4 py-3 rounded-xl bg-slate-50 border border-transparent outline-none focus:border-primary text-sm font-bold">
            </div>
            <div class="col-span-12 md:col-span-1 flex items-end justify-center pb-2">
                <button type="button" onclick="this.closest('.child-row').remove(); checkChildrenEmpty();" class="text-slate-300 hover:text-red-500 transition-colors">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </div>
        </div>
    </template>
</section>

<script>
    function resetStepCheck() {
        document.getElementById('otp-area').classList.add('hidden');
        document.getElementById('reg-input-area').classList.remove('hidden');
        document.getElementById('check-buttons').classList.remove('hidden');
    }

    let searchTimer;
    function autoProceedRenewal() {
        const regNo = document.getElementById('temp_reg_no').value.trim();
        if (regNo.length >= 1) {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => {
                proceedRenewal();
            }, 800);
        }
    }

    function resetRenewalBtn() {
        const btn = document.getElementById('renewal-next-btn');
        btn.innerHTML = 'Next';
        btn.disabled = false;
    }

    async function proceedRenewal() {
        let regNo = document.getElementById('temp_reg_no').value.trim();
        if (!regNo) return;
        
        // If they typed PMCC-101 in the box, strip the PMCC- first before adding it back
        regNo = regNo.replace(/^PMCC-/i, '');

        const btn = document.getElementById('renewal-next-btn');
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        btn.disabled = true;

        const fullMembershipNo = 'PMCC-' + regNo;

        try {
            const response = await fetch('{{ url("api/membership/send-otp") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ membership_no: fullMembershipNo })
            });

            const result = await response.json();
            if (result.success) {
                document.getElementById('masked-email').innerText = result.masked_email;
                document.getElementById('reg-input-area').classList.add('hidden');
                document.getElementById('otp-area').classList.remove('hidden');
                document.getElementById('check-buttons').classList.add('hidden');
            } else {
                const msg = document.getElementById('renewal-msg');
                msg.classList.remove('hidden');
                msg.className = "mt-4 text-[11px] font-bold text-red-500 text-center animate-fade-in";
                
                let errorMsg = "✗ " + result.message;
                if (result.debug) errorMsg += " (Debug: " + result.debug + ")";
                msg.innerText = errorMsg;
            }
        } catch (e) {
            alert('An error occurred. Please try again.');
        } finally {
            resetRenewalBtn();
        }
    }

    async function verifyOTP() {
        const otp = document.getElementById('otp_code').value.trim();
        if (!otp) return alert('Please enter the verification code.');

        const btn = document.getElementById('verify-otp-btn');
        const oldText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> VERIFYING...';
        btn.disabled = true;

        try {
            const response = await fetch('{{ url("api/membership/verify-otp") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ otp: otp })
            });

            const result = await response.json();
            if (result.success) {
                // Pre-fill form
                const data = result.data;
                const cleanNo = document.getElementById('temp_reg_no').value.trim().replace(/^PMCC-/i, '');
                document.getElementById('final_reg_no').value = 'PMCC-' + cleanNo;
                
                if (data.email) document.getElementById('f_email').value = data.email;
                if (data.title) document.getElementById('f_title').value = data.title;
                if (data.full_name) document.getElementById('f_full_name').value = data.full_name;
                if (data.mobile_number) document.getElementById('f_mobile').value = data.mobile_number;
                if (data.dob) document.getElementById('f_dob').value = data.dob;
                if (data.marital_status) {
                    const r = document.querySelector(`input[name="marital_status"][value="${data.marital_status}"]`);
                    if (r) r.checked = true;
                    toggleSpouse(data.marital_status === 'Married');
                }
                if (data.spouse_name) document.getElementById('f_spouse_name').value = data.spouse_name;
                if (data.spouse_dob) document.getElementById('f_spouse_dob').value = data.spouse_dob;
                if (data.post_code) document.getElementById('f_post_code').value = data.post_code;
                if (data.house_details) document.getElementById('f_house_details').value = data.house_details;
                if (data.emergency_name) document.getElementById('f_emergency_name').value = data.emergency_name;
                if (data.emergency_mobile) document.getElementById('f_emergency_mobile').value = data.emergency_mobile;

                // Handle children later or clear existing...
                
                document.getElementById('step-check').classList.add('hidden');
                document.getElementById('main-form').classList.remove('hidden');
            } else {
                let errorMsg = result.message;
                if (result.debug) errorMsg += "\n\nDebug Info: " + result.debug;
                alert(errorMsg);
            }
        } catch (e) {
            alert('Connection error.');
        } finally {
            btn.innerHTML = oldText;
            btn.disabled = false;
        }
    }

    let currentMemType = 'Family';
    function handleMembershipType(type) {
        currentMemType = type;
        document.getElementById('display-plan-name').innerText = type + ' Membership';
        document.getElementById('display-plan-desc').innerText = type === 'Family' ? 'Includes Spouse & Children' : 'Individual Application';
        
        if (type === 'Single') {
            document.getElementById('step-3').setAttribute('data-skip', 'true');
        } else {
            document.getElementById('step-3').removeAttribute('data-skip');
        }
    }

    function toggleSpouse(show) {
        const field = document.getElementById('spouse_field');
        if (show) field.classList.remove('hidden');
        else field.classList.add('hidden');
    }

    function showRegInput() { document.getElementById('reg-input-area').classList.remove('hidden'); }
    function startNewReg() {
        document.getElementById('step-check').classList.add('hidden');
        document.getElementById('main-form').classList.remove('hidden');
    }

    function nextStep(step) {
        // Validation check (basic)
        const current = document.querySelector('.wizard-step:not(.hidden)');
        const currentId = current.id;
        const currentNum = parseInt(currentId.split('-')[1]);

        if (step > currentNum) {
           const inputs = current.querySelectorAll('input[required], select[required], textarea[required]');
           let valid = true;
           inputs.forEach(i => {
               if (i.offsetParent !== null && !i.value) { i.classList.add('border-red-500'); valid = false; }
               else i.classList.remove('border-red-500');
           });
           if (!valid) return;
        }

        // Handle skip logic for step 3 if Single
        if (currentMemType === 'Single') {
            if (currentNum === 2 && step === 3) step = 4;
            if (currentNum === 4 && step === 3) step = 2;
        }

        document.querySelectorAll('.wizard-step').forEach(el => el.classList.add('hidden'));
        const target = document.getElementById('step-' + step);
        target.classList.remove('hidden');
        
        // Update UI
        document.getElementById('progress-num').innerText = step;
        document.getElementById('current-step-display').innerText = step;
        const titles = {1: 'Personal Details', 2: 'Address Details', 3: 'Family Info', 4: 'Membership Plan'};
        document.getElementById('progress-text').innerText = titles[step];
        window.scrollTo({top: 0, behavior: 'smooth'});
    }

    function addChildRow() {
        const container = document.getElementById('children-container');
        const template = document.getElementById('child-row-template');
        const clone = template.content.cloneNode(true);
        container.appendChild(clone);
        checkChildrenEmpty();
    }

    function checkChildrenEmpty() {
        const container = document.getElementById('children-container');
        const rows = container.querySelectorAll('.child-row');
        const msg = document.getElementById('no-children-msg');
        if (rows.length > 0) msg.classList.add('hidden');
        else msg.classList.remove('hidden');
    }
</script>

<style>
    .animate-fade-in { animation: fadeIn 0.4s ease-out forwards; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    input:checked + div { border-color: #1e3a8a !important; background-color: #f8fafc; }
    input:checked + div .text-slate-800 { color: #1e3a8a !important; }
</style>
@endsection
