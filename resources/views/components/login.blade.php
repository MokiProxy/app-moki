@extends('layouts.Auth')

@section('title')
    Login | Portal IT MSI
@endsection

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6 col-xl-5">
            <div class="card overflow-hidden shadow-lg border-0">
                <div class="bg-primary bg-soft" style="background-color: #f0f4ff !important;">
                    <div class="row">
                        <div class="col-7">
                            <div class="text-primary p-4">
                                <h5 class="text-primary fw-bold">Welcome Back !</h5>
                                <p class="text-muted small">Sign in to continue to Portal IT MSI.</p>
                            </div>
                        </div>
                        <div class="col-5 align-self-end">
                            <img src="{{ asset('images/profile-img.png') }}" alt="" class="img-fluid">
                        </div>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <div class="auth-logo">
                        <a href="#" class="auth-logo-light">
                            <div class="avatar-md profile-user-wid mb-4">
                                <span class="avatar-title rounded-circle bg-white shadow-sm">
                                    <img src="{{ asset('img/logo-msi.png') }}" alt="Logo" height="34">
                                </span>
                            </div>
                        </a>

                        <a href="#" class="auth-logo-dark">
                            <div class="avatar-md profile-user-wid mb-4">
                                <span class="avatar-title rounded-circle bg-white shadow-sm">
                                    <img src="{{ asset('img/logo-msi.png') }}" alt="Logo" height="34">
                                </span>
                            </div>
                        </a>
                    </div>
                    <div class="p-2">
                        <form class="form-horizontal needs-validation" action="{{ route('login.process') }}" method="POST" novalidate>
                            @csrf
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror"
                                    name="email" id="email" placeholder="name@company.com" value="{{ old('email') }}" required>

                                @error('email')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <div class="input-group auth-pass-inputgroup">
                                    <input type="password" name="password"
                                        class="form-control @error('password') is-invalid @enderror"
                                        placeholder="Enter password" aria-label="Password"
                                        aria-describedby="password-addon" required>
                                    <button class="btn btn-light" type="button" id="password-addon">
                                        <i class="mdi mdi-eye-outline"></i>
                                    </button>
                                </div>

                                @error('password')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            <div class="form-check">
                                <input class="form-check-input" name="remember" type="checkbox" id="remember-check">
                                <label class="form-check-label" for="remember-check">
                                    Remember me
                                </label>
                            </div>

                            <div class="mt-3 d-grid">
                                <button class="btn btn-primary waves-effect waves-light fw-bold" type="submit" 
                                    style="background-color: #1e293b; border: none;">
                                    LOG IN TO PORTAL
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="mt-4 text-center">
                <p class="text-muted small">© {{ date('Y') }} PT Satria Bahana Sarana</p>
            </div>
        </div>
    </div>
@endsection