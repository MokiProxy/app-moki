@php
$role = session('user_role');
$roleColor = "primary-it-admin";
@endphp

<header id="page-topbar">
    <div class="navbar-header">
        <div class="d-flex">
            <div class="navbar-brand-box bg-dark-primary text-start">
                <a href="{{ route('eqtax.index') }}" class="logo logo-light text-decoration-none">
                    <span class="logo-sm">
                        <i class="mdi mdi-percent fs-4 rounded m-0 p-0 bg-primary-it-admin text-white p-2"></i>
                    </span>
                    <span class="logo-lg mt-4">
                        <div class="flex">
                            <h3 class="text-white mt-4 fw-bold d-flex gap-2 align-items-center">
                                <div class="bg-primary-it-admin p-1 rounded d-flex justify-content-center align-items-center">
                                    <i class="mdi mdi-percent"></i>
                                </div>
                                <div class="d-flex justify-center flex-column">
                                    <div>
                                        <span class="text-{{ $roleColor }} m-0 p-0">EQ</span>Tax
                                    </div>
                                    <p class="fs-6 m-0 p-0 fw-normal">Aplikasi Ekualisasi Pajak</p>
                                </div>
                            </h3>
                        </div>
                    </span>
                </a>
            </div>

            <button type="button" class="btn btn-sm px-3 font-size-16 header-item waves-effect" id="vertical-menu-btn">
                <i class="fa fa-fw fa-bars"></i>
            </button>

        </div>

        <div class="d-flex">
            <div class="dropdown d-inline-block d-lg-none ms-2">
                <button type="button" class="btn header-item noti-icon waves-effect" id="page-header-search-dropdown"
                    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="mdi mdi-magnify"></i>
                </button>
                <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end p-0"
                    aria-labelledby="page-header-search-dropdown">
                    <form class="p-3">
                        <div class="form-group m-0">
                            <div class="input-group">
                                <input type="text" class="form-control" placeholder="Search ..."
                                    aria-label="Recipient's username">
                                <div class="input-group-append">
                                    <button class="btn btn-primary" type="submit">
                                        <i class="mdi mdi-magnify"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="dropdown d-inline-block">
                <button type="button" class="btn header-item waves-effect" id="page-header-user-dropdown"
                    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    {{-- Perbaikan: Menambahkan ?-> dan ?? 'Guest' --}}
                    <img class="rounded-circle header-profile-user"
                        src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()?->name ?? 'Guest') }}&background=random"
                        alt="User Avatar">
                    <span class="d-none d-xl-inline-block ms-1">{{ auth()->user()?->name ?? 'Guest' }}</span>
                    <i class="mdi mdi-chevron-down d-none d-xl-inline-block"></i>
                </button>
                <div class="dropdown-menu dropdown-menu-end shadow">
                    <div class="dropdown-divider"></div>
                    <form action="{{ route('logout') }}" method="post">
                        @csrf
                        <button class="dropdown-item text-danger" type="submit">
                            <i class="bx bx-power-off font-size-16 align-middle me-1 text-danger"></i>
                            <span>Logout</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>


