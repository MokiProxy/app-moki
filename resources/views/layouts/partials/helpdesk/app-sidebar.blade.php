@php
$role = session('user_role');
$authUserRoleId = auth()->user()->role_id;

if($authUserRoleId == 1) {
$roleColor = "danger";
} else if ($authUserRoleId == 3) {
$roleColor = "success";
} else if ($authUserRoleId == 4) {
$roleColor = "primary";
}
@endphp

<div id="sidebar-menu">

    <div>
        <div class="navbar-brand-box bg-dark-primary text-start mb-4">
            <a href="{{ url('/') }}" class="logo logo-light text-decoration-none">
                <span class="logo-sm">
                    <h5 class="text-white mt-3 fw-bold"><span class="text-{{ $roleColor }}">Help</span> Desk</h5>
                </span>
                <span class="logo-lg mt-4">
                    <div class="flex">
                        <h3 class="text-white mt-4 fw-bold"><span class="text-{{ $roleColor }}">Help</span> Desk</h3>
                    </div>
                </span>
            </a>
        </div>
        <ul class="metismenu list-unstyled" id="side-menu">

            <li>
                <a href="{{ route('helpdesk.index') }}" class="waves-effect">
                    <i class="bx bx-home-circle"></i>
                    <span key="t-dashboard">Dashboard</span>
                </a>
            </li>

            @if (in_array($authUserRoleId, [1]))
            <li>
                <a href="javascript: void(0);" class="has-arrow waves-effect">
                    <i class='bx bx-data'></i>
                    <span key="t-master-data">Master Data</span>
                </a>
                <ul class="sub-menu" aria-expanded="false">
                    <li><a href="{{ route('helpdesk.ticket-categories.index') }}"><i class='mdi mdi-file'></i>Kategori Tiket</a></li>
                    <li><a href="{{ route('helpdesk.ticket-priorities.index') }}"><i class='mdi mdi-alert-box'></i>Prioritas Tiket</a></li>
                </ul>
            </li>
            @endif

            <li>
                <a href="javascript: void(0);" class="has-arrow waves-effect">
                    <i class='mdi mdi-ticket'></i>
                    <span key="t-master-data">Manajemen Tiket</span>
                </a>
                <ul class="sub-menu" aria-expanded="false">
                    @if ($authUserRoleId == 1)
                    <li><a href="{{ route('helpdesk.tickets.index') }}"><i class='mdi mdi-ticket'></i>Semua Tiket</a></li>
                    @endif

                    @if(in_array($authUserRoleId, [4, 3]))
                    <li><a href="{{ route('helpdesk.tickets.index') }}"><i class='mdi mdi-ticket'></i>Tiket Saya</a></li>
                    @endif

                    @if(in_array($authUserRoleId, [3]))
                    <li><a href="{{ route('helpdesk.tickets.create') }}"><i class='mdi mdi-plus'></i>Buat Tiket</a></li>
                    @endif
                </ul>
            </li>

            @if (in_array($authUserRoleId, [1]))
            <li><a href="{{ route('helpdesk.reports.index') }}"><i class='bx bx-file'></i>Laporan</a></li>
            @endif

            <li>
                <a href="{{ route('portal.index') }}" class="waves-effect text-{{ $roleColor }}">
                    <i class="bx bx-grid-alt text-{{ $roleColor }}"></i>
                    <span key="t-back-portal" class="fw-bold">Back to Portal</span>
                </a>
            </li>

        </ul>
    </div>

    <!-- Profile -->
     <div class="ps-4">
        <div class="dropdown d-inline-block">
            <button type="button" class="btn header-item waves-effect" id="page-header-user-dropdown"
                data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                {{-- Perbaikan: Menambahkan ?-> dan ?? 'Guest' --}}
                <img class="rounded-circle header-profile-user"
                    src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()?->name ?? 'Guest') }}&background=random"
                    alt="User Avatar">
                <span class="d-none d-xl-inline-block ms-1 text-white">{{ auth()->user()?->name ?? 'Guest' }}</span>
                <i class="mdi mdi-chevron-down d-none d-xl-inline-block text-white"></i>
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
