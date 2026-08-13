<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal IT MSI - Integrated Platform</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@mdi/font@7.2.96/css/materialdesignicons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        html,
        body {
            height: 100%;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: #f4f7f6;
            font-family: 'Inter', sans-serif;
            color: #1e293b;
            display: flex;
            flex-direction: column;
        }

        .portal-container {
            flex: 1 0 auto;
            padding-bottom: 40px;
        }

        .header-portal {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            border-radius: 0;
            padding: 15px 40px;
            color: white;
            margin-bottom: 25px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
        }

        .brand-wrapper {
            display: flex;
            align-items: center;
            gap: 15px;
            flex: 1;
            min-width: 250px;
        }

        .logo-img {
            height: 45px;
            width: auto;
            object-fit: contain;
        }

        .brand-text h1 {
            font-size: 1.25rem;
            margin-bottom: 0;
            font-weight: 800;
        }

        .brand-text p {
            font-size: 0.8rem;
            opacity: 0.7;
            margin-bottom: 0;
        }

        .profile-section {
            display: flex;
            align-items: center;
            text-align: right;
            gap: 12px;
        }

        .profile-icon {
            width: 38px;
            height: 38px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid rgba(255, 255, 255, 0.2);
            font-size: 20px;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .profile-icon:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .dropdown-menu {
            border: none;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            border-radius: 12px;
            padding: 10px;
            margin-top: 10px !important;
        }

        .dropdown-item {
            border-radius: 8px;
            padding: 8px 15px;
            font-size: 0.9rem;
            transition: all 0.2s;
        }

        .dropdown-item:hover {
            background-color: #f8fafc;
            color: #0f172a;
        }

        .content-wrapper {
            max-width: 1300px;
            margin: 0 auto;
            width: 100%;
            padding: 0 20px;
        }

        .carousel-inner {
            border-radius: 12px;
            margin-bottom: 20px;
            overflow: hidden;
        }

        .slider-content {
            height: 180px;
            position: relative;
            display: flex;
            align-items: end;
            padding: 30px;
            color: white;
        }

        .slider-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(to right, rgba(0, 0, 0, 0.85), transparent);
            z-index: 1;
        }

        .slider-text {
            position: relative;
            z-index: 2;
        }

        .menu-wrapper-box {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.03);
            border: 1px solid #edf2f7;
        }

        .menu-card {
            border: 1px solid #f1f5f9;
            border-radius: 10px;
            transition: all 0.3s ease;
            background: #ffffff;
            text-decoration: none !important;
            position: relative;
            display: flex;
            flex-direction: column;
            height: 100%;
            overflow: hidden;
            cursor: pointer;
        }

        .menu-card::after {
            content: "";
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background-color: var(--menu-color, #4e73df);
            transform: translateY(5px);
            transition: transform 0.3s ease;
        }

        .menu-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.08);
        }

        .menu-card:hover::after {
            transform: translateY(0);
        }

        .card-body-wrapper {
            padding: 25px 15px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .icon-circle {
            width: 55px;
            height: 55px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 12px;
            font-size: 24px;
            background-color: #f8fafc;
        }

        .menu-card h6 {
            font-size: 0.85rem;
            font-weight: 800;
            margin-bottom: 3px;
            color: #1e293b;
            text-transform: uppercase;
        }

        .menu-card p {
            font-size: 0.65rem;
            color: #64748b;
            margin-bottom: 0;
        }

        .footer-portal {
            flex-shrink: 0;
            padding: 18px 0;
            background: #0f172a;
            color: #94a3b8;
            font-size: 0.75rem;
        }

        @media (max-width: 576px) {
            .header-portal {
                padding: 15px 20px;
                text-align: center;
            }

            .profile-section {
                width: 100%;
                justify-content: center;
            }

            .brand-wrapper {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>

<body>

    <div class="portal-container">
        <header class="header-portal">
            <div class="brand-wrapper">
                <img src="{{ asset('img/logo-msi.png') }}" alt="Logo" class="logo-img" onerror="this.style.display='none'">
                <div class="brand-text">
                    <h1>Portal IT MSI</h1>
                    <p>Integrated Solution Platform.</p>
                </div>
            </div>

            <div class="profile-section">
                <div class="d-none d-sm-block text-white">
                    <div class="small fw-bold text-white-50">{{ date('l, d F Y') }}</div>
                    <div class="small">Selamat Datang, <strong>{{ auth()->user()->name ?? 'Harmoko' }}</strong></div>
                </div>

                <div class="dropdown">
                    <div class="profile-icon" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="mdi mdi-account text-white"></i>
                    </div>
                    <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="userDropdown">
                        <li>
                            <h6 class="dropdown-header">Aksi Pengguna</h6>
                        </li>
                        <li><a class="dropdown-item" href="#"><i class="mdi mdi-account-circle-outline me-2"></i> Profil Saya</a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li>
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger">
                                    <i class="mdi mdi-logout-variant me-2"></i> <strong>Keluar / Logout</strong>
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </header>

        <div class="content-wrapper">
            <section class="mb-4">
                <div id="heroSlider" class="carousel slide carousel-fade" data-bs-ride="carousel">
                    <div class="carousel-inner shadow-sm">
                        @foreach($sliders as $key => $slider)
                        <div class="carousel-item {{ $key == 0 ? 'active' : '' }}">
                            <div class="slider-content" style="background: url('{{ $slider->image }}'); background-size: cover; background-position: center;">
                                <div class="slider-overlay"></div>
                                <div class="slider-text text-white">
                                    <h4 class="fw-bold m-0 p-0">{{ $slider->title }}</h4>
                                    <p class="m-0 p-0">{{ $slider->desc }}</p>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </section>

            <div class="menu-wrapper-box">
                <div class="row g-3 justify-content-center">
                    @foreach($menus as $menu)
                    <div class="col-6 col-md-4 col-lg-2">
                        @php
                        // Cek apakah link mengandung '#' atau link kosong untuk trigger Coming Soon
                        $isComingSoon = ($menu['link'] == '#' || empty($menu['link']));
                        @endphp

                        <a @if($isComingSoon)
                            onclick="showComingSoon('{{ $menu['title'] }}')"
                            @else
                            @can($menu["permission"])
                            href="{{ $menu['link'] }}"
                            @endcan
                            @cannot($menu["permission"])
                            onclick="showNotAllowed('{{ $menu['title'] }}')"
                            @endcan()
                            @endif
                            class="menu-card text-center"
                            style="--menu-color: {{ $menu['color'] }};">

                            <div class="card-body-wrapper">
                                <div class="icon-circle mx-auto" style="color: {{ $menu['color'] }};">
                                    <i class="mdi {{ $menu['icon'] }}"></i>
                                </div>
                                <h6>{{ $menu['title'] }}</h6>
                                <p>{{ $menu['sub'] }}</p>
                            </div>
                        </a>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <footer class="footer-portal">
        <div class="container-fluid px-5 text-center text-md-start">
            <div class="row align-items-center">
                <div class="col-md-6 mb-2 mb-md-0">
                    <strong class="text-white">IT MSI Platform</strong> <span class="mx-2">|</span> v1.0.2
                </div>
                <div class="col-md-6 text-md-end">
                    <span>© {{ date('Y') }} PT Satria Bahana Sarana.</span>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        function showComingSoon(title) {
            Swal.fire({
                title: 'Coming Soon!',
                text: 'Modul ' + title + ' sedang dalam tahap pengembangan.',
                icon: 'info',
                confirmButtonColor: '#1e293b',
                confirmButtonText: 'Oke, Mengerti',
                backdrop: `rgba(15, 23, 42, 0.4)`
            });
        }

          function showNotAllowed(title) {
            Swal.fire({
                title: 'Anda Tidak Memiliki Akses!',
                text: 'Anda tidak memiliki akses aplikasi ' + title,
                icon: 'info',
                confirmButtonColor: '#1e293b',
                confirmButtonText: 'Oke, Mengerti',
                backdrop: `rgba(15, 23, 42, 0.4)`
            });
        }
    </script>

</body>

</html>
