<!DOCTYPE html>
<html lang="en" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Primary SEO -->
    <title>@yield('title', ($settings['site_name'] ?? 'PMCC-UK'))</title>
    <meta name="description"
        content="@yield('meta_description', ($settings['seo_description'] ?? 'PMCC-UK is a voluntary cultural organization serving the Malayalee community in Plymouth.'))">
    <meta name="keywords" content="PMCC-UK, Plymouth Malayalee, Indian Community UK, Cultural Association Plymouth">

    <!-- Social Meta Tags (OpenGraph) -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ request()->url() }}">
    <meta property="og:title" content="@yield('title', ($settings['site_name'] ?? 'PMCC-UK'))">
    <meta property="og:description"
        content="@yield('meta_description', ($settings['seo_description'] ?? 'PMCC-UK is a voluntary cultural organization serving the Malayalee community in Plymouth.'))">
    <meta property="og:image"
        content="@yield('meta_image', !empty($settings['site_logo']) ? asset('storage/' . $settings['site_logo']) : asset('assets/img/og-image.jpg'))">

    @if (!empty($settings['site_favicon']))
        <link rel="icon" type="image/x-icon" href="{{ asset('storage/' . $settings['site_favicon']) }}">
    @endif

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    @php
        $site_font = $settings['site_font'] ?? 'Outfit';
        $font_urls = [
            'Roboto' => "https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&display=swap",
            'Open Sans' => "https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;600;700;800&display=swap",
            'Poppins' => "https://fonts.googleapis.com/css2?family=Poppins:wght@100;300;400;500;600;700;800;900&display=swap",
            'Montserrat' => "https://fonts.googleapis.com/css2?family=Montserrat:wght@100;300;400;500;600;700;800;900&display=swap",
            'Lato' => "https://fonts.googleapis.com/css2?family=Lato:wght@100;300;400;700;900&display=swap",
            'Inter' => "https://fonts.googleapis.com/css2?family=Inter:wght@100;300;400;500;600;700;800;900&display=swap",
            'Outfit' => "https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap"
        ];
        $font_url = $font_urls[$site_font] ?? $font_urls['Outfit'];
    @endphp

    <link href="{{ $font_url }}" rel="stylesheet">

    <style>
        :root {
            --primary: #1e3a8a;
            --secondary: #991b1b;
            --accent: #f59e0b;
        }

        body {
            font-family: '{{ $site_font }}', sans-serif;
            background-color: #f8fafc;
        }

        .bg-primary {
            background-color: var(--primary);
        }

        .text-primary {
            color: var(--primary);
        }

        .bg-secondary {
            background-color: var(--secondary);
        }

        .text-secondary {
            color: var(--secondary);
        }

        .bg-accent {
            background-color: var(--accent);
        }

        .text-accent {
            color: var(--accent);
        }

        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f5f9;
        }

        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--accent);
        }

        .loading-bar {
            position: fixed;
            top: 0;
            left: 0;
            height: 3px;
            background: var(--accent);
            z-index: 10000;
            width: 0%;
            transition: width 0.4s ease, opacity 0.5s ease;
        }

        .reveal {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.8s ease-out;
        }

        .reveal.active {
            opacity: 1;
            transform: translateY(0);
        }

        .nav-link {
            color: #334155;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 13px;
            transition: color 0.2s;
        }

        .nav-link:hover {
            color: var(--primary);
        }

        .dropdown-menu {
            display: none;
            opacity: 0;
            transform: translateY(10px);
            transition: all 0.2s ease;
        }

        .dropdown:hover .dropdown-menu {
            display: block;
            opacity: 1;
            transform: translateY(0);
        }

        .btn-primary {
            background-color: var(--primary);
            color: white;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background-color: #1e40af;
            transform: translateY(-1px);
        }

        @keyframes pulse-slow {

            0%,
            100% {
                transform: scale(1.05);
            }

            50% {
                transform: scale(1.1);
            }
        }

        .animate-pulse-slow {
            animation: pulse-slow 2s infinite ease-in-out;
        }
    </style>
    @yield('styles')
</head>

<body class="text-slate-800">
    <div class="loading-bar" id="page-loading-bar"></div>

    <!-- Top Utility Bar -->
    <div class="bg-primary text-white py-2.5 text-[11px] font-bold uppercase tracking-wider">
        <div class="max-w-7xl mx-auto px-4 flex justify-between items-center text-white/90">
            <div class="flex space-x-6">
                <span><i class="fas fa-envelope mr-2 text-accent"></i>{{ $settings['contact_email'] ?? 'info@pmccuk.org'
                    }}</span>
                <span class="hidden sm:inline"><i
                        class="fas fa-phone mr-2 text-accent"></i>{{ $settings['contact_phone'] ?? '' }}</span>
            </div>
            <div class="flex space-x-6">
                <a href="{{ url('verify-membership') }}" class="hover:text-white transition flex items-center"><i
                        class="fas fa-check-circle mr-1.5 text-accent"></i>Verify Membership</a>
            </div>
        </div>
    </div>

    <!-- Main Navigation -->
    <header class="bg-white/90 backdrop-blur-md shadow-sm sticky top-0 z-[100] border-b border-slate-100">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center h-24">
                <!-- Logo -->
                <a href="{{ url('/') }}" class="flex items-center space-x-4 group">
                    @if (!empty($settings['site_logo']))
                        <img src="{{ asset('storage/' . $settings['site_logo']) }}" alt="Logo"
                            class="h-16 w-auto object-contain">
                    @else
                        <div
                            class="h-14 w-14 bg-primary rounded-xl flex items-center justify-center text-white font-black text-2xl shadow-lg shadow-blue-900/20 group-hover:bg-secondary transition-colors">
                            {{ substr($settings['site_name'] ?? 'P', 0, 1) }}
                        </div>
                    @endif
                    <div class="flex flex-col">
                        <span
                            class="text-2xl font-black tracking-tighter text-slate-900 uppercase leading-none">{{ $settings['site_name'] ?? 'PMCC-UK' }}</span>
                        <span
                            class="text-[10px] font-black text-secondary mt-1.5 uppercase tracking-widest pl-0.5">{{ $settings['tagline'] ?? 'The Power of Unity' }}</span>
                    </div>
                </a>

                <!-- Desktop Menu -->
                <nav class="hidden lg:flex items-center space-x-1">
                    @foreach ($menus as $menu)
                        @if (count($menu['submenus']) == 0)
                            <a href="{{ url($menu['url']) }}" class="nav-link px-4 py-3 rounded-xl hover:bg-slate-50">
                                {{ $menu['title'] }}
                            </a>
                        @else
                            <div class="relative dropdown">
                                <button class="nav-link px-4 py-3 rounded-xl hover:bg-slate-50 flex items-center outline-none">
                                    {{ $menu['title'] }} <i class="fas fa-chevron-down ml-2 text-[8px] opacity-50"></i>
                                </button>
                                <div
                                    class="dropdown-menu absolute left-0 mt-0 w-64 bg-white shadow-2xl border border-slate-100 rounded-2xl overflow-hidden py-3 z-[110]">
                                    @foreach ($menu['submenus'] as $sub)
                                        <a href="{{ url($sub['url']) }}"
                                            class="block px-6 py-3.5 text-sm font-bold text-slate-600 hover:bg-primary hover:text-white transition-all">
                                            {{ $sub['title'] }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endforeach
                    <a href="{{ url('student-corner') }}"
                        class="nav-link px-4 py-3 rounded-xl hover:bg-slate-50">Student Corner</a>
                    <a href="{{ url('membership') }}"
                        class="ml-6 px-10 py-3.5 bg-primary text-white rounded-2xl font-black text-[11px] tracking-widest shadow-xl shadow-blue-900/20 hover:bg-secondary hover:shadow-red-900/20 transform hover:-translate-y-1 transition-all uppercase">
                        Join Community
                    </a>
                </nav>

                <!-- Mobile menu button -->
                <div class="lg:hidden">
                    <button id="mobile-menu-btn" class="text-slate-900 p-3 bg-slate-50 rounded-xl focus:outline-none"><i
                            class="fas fa-bars text-xl"></i></button>
                </div>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div id="mobile-menu"
            class="hidden lg:hidden bg-white border-t border-slate-100 shadow-2xl px-4 py-8 absolute w-full left-0">
            <div class="space-y-2">
                @foreach ($menus as $menu)
                    @if (count($menu['submenus']) == 0)
                        <a href="{{ url($menu['url']) }}"
                            class="block px-6 py-4 text-sm font-black text-slate-800 hover:bg-primary hover:text-white rounded-2xl transition-all">
                            {{ $menu['title'] }}
                        </a>
                    @else
                        <div class="pt-2">
                            <div class="px-6 py-2 text-[10px] font-black text-slate-400 uppercase tracking-widest">
                                {{ $menu['title'] }}
                            </div>
                            @foreach ($menu['submenus'] as $sub)
                                <a href="{{ url($sub['url']) }}"
                                    class="block px-10 py-3.5 text-sm font-bold text-slate-600 hover:bg-slate-50 rounded-2xl">{{ $sub['title'] }}</a>
                            @endforeach
                        </div>
                    @endif
                @endforeach
                <a href="{{ url('student-corner') }}"
                    class="block px-6 py-4 text-sm font-black text-slate-800 hover:bg-primary hover:text-white rounded-2xl transition-all">Student
                    Corner</a>
                <div class="pt-6">
                    <a href="{{ url('membership') }}"
                        class="block w-full text-center py-5 bg-primary text-white rounded-2xl font-black uppercase tracking-widest shadow-xl">Start
                        Membership</a>
                </div>
            </div>
        </div>
    </header>

    <main>
        @yield('content')
    </main>

    <!-- Footer placeholder - will move to partial later -->
    <footer class="bg-slate-900 text-white pt-24 pb-12 shadow-[0_-10px_40px_-15px_rgba(0,0,0,0.1)]">
        <div class="max-w-7xl mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-16 mb-20">
                <!-- About -->
                <div class="space-y-8">
                    <a href="{{ url('/') }}" class="flex items-center space-x-3 group">
                        <div
                            class="w-12 h-12 bg-white rounded-xl flex items-center justify-center text-primary font-black text-2xl shadow-xl transition-transform group-hover:scale-110">
                            P</div>
                        <span
                            class="text-2xl font-black tracking-tighter uppercase">{{ $settings['site_name'] ?? 'PMCC-UK' }}</span>
                    </a>
                    <p class="text-slate-400 text-sm leading-relaxed font-medium">
                        PMCC-UK is a premier cultural organization committed to uniting and empowering the Malayalee
                        community through community engagement and heritage.
                    </p>
                    <div class="flex space-x-4">
                        @if(!empty($settings['social_facebook']))
                            <a href="{{ $settings['social_facebook'] }}" target="_blank"
                                class="w-11 h-11 bg-white/5 border border-white/10 rounded-xl flex items-center justify-center hover:bg-white hover:text-primary hover:-translate-y-1 transition-all duration-300 shadow-lg"><i
                                    class="fab fa-facebook-f"></i></a>
                        @endif
                        @if(!empty($settings['social_instagram']))
                            <a href="{{ $settings['social_instagram'] }}" target="_blank"
                                class="w-11 h-11 bg-white/5 border border-white/10 rounded-xl flex items-center justify-center hover:bg-white hover:text-primary hover:-translate-y-1 transition-all duration-300 shadow-lg"><i
                                    class="fab fa-instagram"></i></a>
                        @endif
                    </div>
                </div>

                <!-- Navigation -->
                <div>
                    <h4 class="text-xs font-black uppercase tracking-[0.2em] text-secondary mb-10 pl-1">Quick Links</h4>
                    <ul class="space-y-5 text-sm font-bold text-slate-400">
                        <li><a href="{{ route('home') }}"
                                class="hover:text-white transition-colors flex items-center group">Home</a></li>
                        <li><a href="{{ route('about') }}"
                                class="hover:text-white transition-colors flex items-center group">About Us</a></li>
                        <li><a href="{{ route('membership') }}"
                                class="hover:text-white transition-colors flex items-center group">Member Portal</a>
                        </li>
                        <li><a href="{{ route('contact') }}"
                                class="hover:text-white transition-colors flex items-center group">Get In Touch</a></li>
                    </ul>
                </div>

                <!-- Community -->
                <div>
                    <h4 class="text-xs font-black uppercase tracking-[0.2em] text-secondary mb-10 pl-1">Resources</h4>
                    <ul class="space-y-5 text-sm font-bold text-slate-400">
                        <li><a href="{{ route('news') }}"
                                class="hover:text-white transition-colors flex items-center group">News Desk</a></li>
                        <li><a href="{{ route('events') }}"
                                class="hover:text-white transition-colors flex items-center group">Upcoming Hub</a></li>
                        <li><a href="{{ route('student-corner') }}"
                                class="hover:text-white transition-colors flex items-center group">Student Corner</a>
                        </li>
                    </ul>
                </div>

                <!-- Contact -->
                <div>
                    <h4 class="text-xs font-black uppercase tracking-[0.2em] text-secondary mb-10 pl-1">Contact Office
                    </h4>
                    <div class="space-y-8 text-sm font-bold text-slate-400">
                        <div class="flex items-start space-x-4">
                            <div
                                class="w-10 h-10 bg-white/5 rounded-xl flex items-center justify-center text-secondary border border-white/10 shadow-lg flex-shrink-0">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <span class="pt-2 leading-relaxed">Plymouth, Devon,<br>United Kingdom</span>
                        </div>
                        <div class="flex items-start space-x-4">
                            <div
                                class="w-10 h-10 bg-white/5 rounded-xl flex items-center justify-center text-secondary border border-white/10 shadow-lg flex-shrink-0">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <span class="pt-2 italic">{{ $settings['contact_email'] ?? 'info@pmccuk.org' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div
                class="pt-10 border-t border-white/10 flex flex-col md:flex-row justify-between items-center text-[11px] font-black uppercase tracking-widest text-slate-500">
                <p class="mb-4 md:mb-0">&copy; {{ date('Y') }} PMCC-UK Made and maintained with ❤️ by Tom Jacob .

                </p>
                <div class="relative inline-block text-left mt-4 md:mt-0">
                    <!-- Dropup Trigger Button -->
                    <button id="legal-dropup-btn" type="button" class="flex items-center space-x-2 text-slate-500 hover:text-white transition-colors bg-white/5 border border-white/10 px-6 py-3 rounded-full font-black uppercase tracking-widest text-[10px] shadow-lg hover:bg-white/10">
                        <span>Legal Agreements</span>
                        <i class="fas fa-chevron-up text-[9px] translate-y-[-0.5px]"></i>
                    </button>
                    
                    <!-- Dropup Menu Content -->
                    <div id="legal-dropup-menu" class="hidden absolute right-0 mb-4 w-64 rounded-[2rem] bg-slate-900 border border-white/10 shadow-2xl p-3 z-50 flex flex-col space-y-1 text-slate-400 font-bold normal-case text-left" style="bottom: 100% !important;">
                        <a href="{{ route('privacy') }}" class="px-4 py-3 hover:bg-white/5 rounded-2xl hover:text-white transition-colors flex items-center">
                            <i class="fas fa-user-shield w-8 text-secondary"></i> Privacy Policy
                        </a>
                        <a href="{{ route('cookie-policy') }}" class="px-4 py-3 hover:bg-white/5 rounded-2xl hover:text-white transition-colors flex items-center">
                            <i class="fas fa-cookie-bite w-8 text-secondary"></i> Cookie Policy
                        </a>
                        <a href="{{ route('terms') }}" class="px-4 py-3 hover:bg-white/5 rounded-2xl hover:text-white transition-colors flex items-center">
                            <i class="fas fa-file-contract w-8 text-secondary"></i> Terms & Conditions
                        </a>
                        <a href="{{ route('refund-policy') }}" class="px-4 py-3 hover:bg-white/5 rounded-2xl hover:text-white transition-colors flex items-center">
                            <i class="fas fa-undo-alt w-8 text-secondary"></i> Refund & Cancellation
                        </a>
                        <a href="{{ route('safeguarding') }}" class="px-4 py-3 hover:bg-white/5 rounded-2xl hover:text-white transition-colors flex items-center">
                            <i class="fas fa-child w-8 text-secondary"></i> Safeguarding Policy
                        </a>
                        <a href="{{ route('code-of-conduct') }}" class="px-4 py-3 hover:bg-white/5 rounded-2xl hover:text-white transition-colors flex items-center">
                            <i class="fas fa-handshake w-8 text-secondary"></i> Code of Conduct
                        </a>
                        <a href="{{ route('accessibility') }}" class="px-4 py-3 hover:bg-white/5 rounded-2xl hover:text-white transition-colors flex items-center">
                            <i class="fas fa-universal-access w-8 text-secondary"></i> Accessibility Statement
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <script>
        window.addEventListener('load', () => {
            const bar = document.getElementById('page-loading-bar');
            if (bar) {
                bar.style.width = '100%';
                setTimeout(() => { bar.style.opacity = '0'; }, 600);
            }
        });

        const menuBtn = document.getElementById('mobile-menu-btn');
        const mobileMenu = document.getElementById('mobile-menu');
        menuBtn?.addEventListener('click', (e) => {
            e.stopPropagation();
            mobileMenu?.classList.toggle('hidden');
        });

        document.addEventListener('click', (e) => {
            if (mobileMenu && !mobileMenu.classList.contains('hidden') && !mobileMenu.contains(e.target) && e.target !== menuBtn) {
                mobileMenu.classList.add('hidden');
            }
        });

        // Legal Policy Dropup Toggle
        const legalBtn = document.getElementById('legal-dropup-btn');
        const legalMenu = document.getElementById('legal-dropup-menu');
        
        legalBtn?.addEventListener('click', (e) => {
            e.stopPropagation();
            legalMenu.classList.toggle('hidden');
        });

        document.addEventListener('click', (e) => {
            if (legalMenu && !legalMenu.classList.contains('hidden') && !legalMenu.contains(e.target) && e.target !== legalBtn) {
                legalMenu.classList.add('hidden');
            }
        });

        function reveal() {
            var reveals = document.querySelectorAll(".reveal");
            for (var i = 0; i < reveals.length; i++) {
                var windowHeight = window.innerHeight;
                var elementTop = reveals[i].getBoundingClientRect().top;
                if (elementTop < windowHeight - 100) {
                    reveals[i].classList.add("active");
                }
            }
        }
        window.addEventListener("scroll", reveal);
        window.addEventListener("load", reveal);
    </script>
    @yield('scripts')
</body>

</html>