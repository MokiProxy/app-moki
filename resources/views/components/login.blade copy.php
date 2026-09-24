@extends('layouts.Auth')

@section('title')
    Login | Portal IT MSI
@endsection

@section('content')
<style>
    html, body {
        height: 100%;
        margin: 0;
        overflow-x: hidden;
    }

    /* ===== Landing / video background ===== */
    .auth-video-wrapper {
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        z-index: 1000;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
    }

    .auth-video-wrapper video.bg-video {
        position: absolute;
        top: 50%;
        left: 50%;
        min-width: 100%;
        min-height: 100%;
        width: auto;
        height: auto;
        transform: translate(-50%, -50%);
        object-fit: cover;
        z-index: 0;
    }

    .auth-video-overlay {
        position: absolute;
        inset: 0;
        background: linear-gradient(180deg, rgba(15,23,42,.25) 0%, rgba(15,23,42,.45) 100%);
        z-index: 1;
    }

    .auth-topbar {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        z-index: 2;
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 22px 32px;
    }

    .auth-brand {
        display: flex;
        align-items: center;
        gap: 10px;
        color: #fff;
    }

    .auth-brand img {
        height: 34px;
    }

    .auth-brand span {
        font-weight: 700;
        font-size: 1.15rem;
        letter-spacing: .3px;
    }

    .auth-contact {
        color: rgba(255,255,255,.85);
        font-size: .9rem;
    }

    .auth-contact a {
        color: #7dd3fc;
        font-weight: 600;
        text-decoration: none;
        margin-left: 4px;
    }

    .auth-hero {
        position: relative;
        z-index: 2;
        text-align: center;
        color: #fff;
        padding: 16px;
        width: 100%;
        max-width: 460px;
    }

    .auth-hero-logo {
        height: 56px;
        margin-bottom: 14px;
    }

    .auth-hero h2 {
        font-weight: 800;
        font-size: 1.9rem;
        margin-bottom: 4px;
        letter-spacing: .2px;
    }

    .auth-hero .version {
        color: rgba(255,255,255,.65);
        font-size: .85rem;
        margin-bottom: 28px;
    }

    .auth-hero .select-text {
        color: rgba(255,255,255,.9);
        font-size: 1rem;
        margin-bottom: 18px;
    }

    .btn-auth-option {
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        padding: 13px 18px;
        border-radius: 10px;
        font-weight: 700;
        font-size: .95rem;
        margin-bottom: 14px;
        border: none;
        transition: transform .12s ease, box-shadow .12s ease;
    }

    .btn-auth-option:hover {
        transform: translateY(-1px);
    }

    .btn-microsoft {
        background: #fff;
        color: #1e293b;
    }

    .btn-microsoft:hover {
        color: #1e293b;
        box-shadow: 0 8px 20px rgba(255,255,255,.15);
    }

    .btn-guest {
        background: #2563eb;
        color: #fff;
    }

    .btn-guest:hover {
        color: #fff;
        background: #1d4ed8;
        box-shadow: 0 8px 20px rgba(37,99,235,.35);
    }

    .auth-legal {
        position: relative;
        z-index: 2;
        color: rgba(255,255,255,.65);
        font-size: .8rem;
        text-align: center;
        margin-top: 6px;
    }

    .auth-legal a {
        color: #7dd3fc;
        text-decoration: none;
    }

    /* ===== Modal (guest login & forgot password) ===== */
    .auth-modal .modal-content {
        border-radius: 18px;
        border: none;
        padding: 8px;
        box-shadow: 0 25px 60px rgba(0,0,0,.4);
    }

    .auth-modal .modal-body {
        padding: 32px 34px 30px;
        text-align: center;
    }

    .auth-modal .btn-close-custom {
        position: absolute;
        top: 16px;
        right: 18px;
        background: transparent;
        border: none;
        font-size: 1.25rem;
        color: #64748b;
        line-height: 1;
        cursor: pointer;
    }

    .auth-modal .modal-logo {
        height: 44px;
        margin-bottom: 16px;
    }

    .auth-modal .modal-desc {
        color: #64748b;
        font-size: .92rem;
        margin-bottom: 22px;
        line-height: 1.45;
    }

    .auth-modal .form-label {
        display: none;
    }

    .auth-modal .form-control {
        border-radius: 10px;
        border: 1px solid #e2e8f0;
        padding: 12px 14px;
        font-size: .95rem;
        text-align: left;
    }

    .auth-modal .form-control:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37,99,235,.12);
    }

    .auth-modal .auth-pass-inputgroup .btn {
        border: 1px solid #e2e8f0;
        border-left: none;
        border-radius: 0 10px 10px 0;
        background: #fff;
    }

    .auth-modal .forgot-link {
        display: block;
        text-align: right;
        font-size: .85rem;
        color: #2563eb;
        text-decoration: none;
        margin: 8px 2px 20px;
        background: transparent;
        border: none;
        cursor: pointer;
    }

    .auth-modal .btn-signin {
        width: 100%;
        background: #2563eb;
        border: none;
        border-radius: 10px;
        padding: 12px;
        font-weight: 700;
        letter-spacing: .3px;
    }

    .auth-modal .btn-signin:hover {
        background: #1d4ed8;
    }

    .auth-modal .form-check {
        text-align: left;
        margin-bottom: 4px;
    }

    /* ===== Forgot password modal - elemen tambahan ===== */
    .auth-modal .modal-title-lg {
        font-weight: 800;
        font-size: 1.4rem;
        color: #1e293b;
        margin-bottom: 12px;
    }

    .auth-modal .modal-desc .contact-link {
        color: #2563eb;
        text-decoration: none;
    }

    .auth-modal .form-label-visible {
        display: block;
        text-align: left;
        font-weight: 600;
        font-size: .85rem;
        color: #334155;
        margin-bottom: 6px;
    }

    .auth-modal .helper-text {
        font-size: .82rem;
        color: #64748b;
        text-align: left;
        margin: 10px 0 22px;
    }

    .auth-modal .modal-footer-links {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
    }

    .auth-modal .back-to-signin {
        color: #2563eb;
        text-decoration: none;
        font-weight: 600;
        font-size: .9rem;
        white-space: nowrap;
    }

    .auth-modal .btn-continue {
        background: #2563eb;
        border: none;
        border-radius: 10px;
        padding: 12px 24px;
        font-weight: 700;
        color: #fff;
    }

    .auth-modal .btn-continue:hover {
        background: #1d4ed8;
    }

    .auth-modal .modal-copyright {
        font-size: .78rem;
        color: #94a3b8;
        text-align: center;
        margin-top: 24px;
        margin-bottom: 0;
    }
</style>

<div class="auth-video-wrapper">

    {{-- Background video --}}
    {{--
        Sementara pakai video dummy publik dulu biar bisa langsung dilihat hasilnya.
        Nanti kalau video asli sudah siap, tinggal ganti src di bawah ini jadi:
        {{ asset('videos/login-bg.mp4') }}
    --}}
    <video class="bg-video" autoplay muted loop playsinline>
        <source src="https://www.w3schools.com/html/mov_bbb.mp4" type="video/mp4">
    </video>
    <div class="auth-video-overlay"></div>

    {{-- Top bar: brand + contact --}}
    <div class="auth-topbar">
        <div class="auth-brand">
            <img src="{{ asset('img/logo-msi.png') }}" alt="Logo">
            <span>Portal IT MSI</span>
        </div>
        <div class="auth-contact">
            Have any question or need assistance?
            <a href="#">Contact Us</a>
        </div>
    </div>

    {{-- Hero: select login type --}}
    <div class="auth-hero">
        <img src="{{ asset('img/logo-msi.png') }}" alt="Logo" class="auth-hero-logo">
        <h2>Portal IT MSI</h2>
        <div class="version">Version 1.0.0</div>

        <p class="select-text">Welcome, please select your login type</p>

        <a href="{{ route('login.microsoft') }}" class="btn-auth-option btn-microsoft">
            <svg width="18" height="18" viewBox="0 0 21 21" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <rect x="1" y="1" width="9" height="9" fill="#f25022"/>
                <rect x="11" y="1" width="9" height="9" fill="#7fba00"/>
                <rect x="1" y="11" width="9" height="9" fill="#00a4ef"/>
                <rect x="11" y="11" width="9" height="9" fill="#ffb900"/>
            </svg>
            Sign in with Microsoft
        </a>

        <button type="button" class="btn-auth-option btn-guest" data-bs-toggle="modal" data-bs-target="#guestLoginModal">
            Sign in as Guest
        </button>

        <p class="auth-legal">
            © {{ date('Y') }} PT Satria Bahana Sarana<br>
            By signing in you accept our <a href="{{ url('/') }}#">Terms of Use</a> and <a href="{{ url('/') }}#">Privacy Policy</a>
        </p>
    </div>
</div>

{{-- ===== Guest login modal ===== --}}
<div class="modal fade auth-modal" id="guestLoginModal" tabindex="-1" aria-labelledby="guestLoginModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body position-relative">
                <button type="button" class="btn-close-custom" data-bs-dismiss="modal" aria-label="Close">&times;</button>

                <img src="{{ asset('img/logo-msi.png') }}" alt="Logo" class="modal-logo">

                <p class="modal-desc">
                    Welcome, you choose to log in as a guest, please log in to continue.
                </p>

                <form action="{{ route('login.process') }}" method="POST" novalidate>
                    @csrf

                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control @error('username') is-invalid @enderror"
                            name="username" id="username" placeholder="Username" value="{{ old('username') }}" required autofocus>

                        @error('username')
                            <span class="invalid-feedback d-block text-start" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    <div class="mb-2">
                        <label class="form-label" for="password">Password</label>
                        <div class="input-group auth-pass-inputgroup">
                            <input type="password" name="password" id="password"
                                class="form-control @error('password') is-invalid @enderror"
                                placeholder="Password" aria-label="Password"
                                aria-describedby="password-addon" required>
                            <button class="btn" type="button" id="password-addon">
                                <i class="mdi mdi-eye-off-outline"></i>
                            </button>
                        </div>

                        @error('password')
                            <span class="invalid-feedback d-block text-start" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <div class="form-check">
                            <input class="form-check-input" name="remember" type="checkbox" id="remember-check">
                            <label class="form-check-label small" for="remember-check">
                                Remember me
                            </label>
                        </div>
                        <button type="button" class="forgot-link mb-0" data-bs-toggle="modal" data-bs-target="#forgotPasswordModal" data-bs-dismiss="modal">
                            Forgot Password?
                        </button>
                    </div>

                    <div class="mt-3">
                        <button class="btn btn-signin text-white" type="submit">
                            Sign In
                        </button>
                    </div>
                </form>

                <p class="modal-copyright">
                    Copyright &copy; PT Satria Bahana Sarana
                </p>
            </div>
        </div>
    </div>
</div>

{{-- ===== Forgot password modal ===== --}}
<div class="modal fade auth-modal" id="forgotPasswordModal" tabindex="-1" aria-labelledby="forgotPasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body position-relative">
                <button type="button" class="btn-close-custom" data-bs-dismiss="modal" aria-label="Close">&times;</button>

                <img src="{{ asset('img/logo-msi.png') }}" alt="Logo" class="modal-logo">

                <h5 class="modal-title-lg">Forgot Your Password?</h5>

                <p class="modal-desc mb-0">
                    Please provide the email address that you used when you signed up for your account.
                </p>
                <p class="modal-desc" style="font-size:.82rem; margin-top:2px;">
                    If you forgot your email, please
                    <a href="{{ url('/') }}#" class="contact-link">contact us</a>.
                </p>

                @if (Route::has('password.email'))
                    <form action="{{ route('password.email') }}" method="POST" novalidate>
                @else
                    <form action="#" method="POST" novalidate>
                @endif
                    @csrf
                    <input type="hidden" name="_forgot_password_form" value="1">

                    <label for="forgot-email" class="form-label-visible">Email Address</label>
                    <input type="email" class="form-control @error('email') is-invalid @enderror"
                        name="email" id="forgot-email" placeholder="Email Address" value="{{ old('email') }}" required>

                    @error('email')
                        <span class="invalid-feedback d-block text-start" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror

                    <p class="helper-text">We will send you an email that will allow you to reset your password.</p>

                    <div class="modal-footer-links">
                        <button type="button" class="back-to-signin" data-bs-toggle="modal" data-bs-target="#guestLoginModal" data-bs-dismiss="modal">
                            &larr; Return to Sign In
                        </button>
                        <button type="submit" class="btn-continue">Continue</button>
                    </div>
                </form>

                <p class="modal-copyright">
                    © {{ date('Y') }} PT Satria Bahana Sarana. All Rights Reserved
                </p>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const toggleBtn = document.getElementById('password-addon');
        const passwordInput = document.getElementById('password');

        if (toggleBtn && passwordInput) {
            toggleBtn.addEventListener('click', function () {
                const isPassword = passwordInput.getAttribute('type') === 'password';
                passwordInput.setAttribute('type', isPassword ? 'text' : 'password');

                const icon = toggleBtn.querySelector('i');
                icon.classList.toggle('mdi-eye-outline');
                icon.classList.toggle('mdi-eye-off-outline');
            });
        }

        @if ($errors->any())
            @if ($errors->has('email') && old('_forgot_password_form'))
                var forgotModalEl = document.getElementById('forgotPasswordModal');
                if (forgotModalEl && typeof bootstrap !== 'undefined') {
                    new bootstrap.Modal(forgotModalEl).show();
                }
            @else
                var guestModalEl = document.getElementById('guestLoginModal');
                if (guestModalEl && typeof bootstrap !== 'undefined') {
                    new bootstrap.Modal(guestModalEl).show();
                }
            @endif
        @endif
    });
</script>
@endsection
