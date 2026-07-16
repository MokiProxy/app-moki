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
    <ul class="metismenu list-unstyled" id="side-menu">

        <li>
            <a href="{{ route('helpdesk.index') }}" class="waves-effect">
                <i class="bx bx-home-circle"></i>
                <span key="t-dashboard">Dashboard</span>
            </a>
        </li>

        {{-- JIKA BUKAN APPROVER, TAMPILKAN MASTER DATA --}}
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
                <li><a href="{{ route('helpdesk.tickets.create') }}"><i class='mdi mdi-ticket'></i>Buat Tiket</a></li>
                @endif
            </ul>
        </li>

        <li>
            <a href="javascript: void(0);" class="has-arrow waves-effect">
                <i class='bx bx-file'></i>
                <span key="t-master-data">Laporan</span>
            </a>
            <ul class="sub-menu" aria-expanded="false">
                <li><a href="{{ route('employee') }}"><i class='bx bx-user-circle'></i>Semua Tiket</a></li>
                <li><a href="{{ route('regional') }}"><i class='bx bx-map-alt'></i>Prioritas Tiket</a></li>
            </ul>
        </li>

        <li>
            <a href="{{ route('portal.index') }}" class="waves-effect text-{{ $roleColor }}">
                <i class="bx bx-grid-alt text-{{ $roleColor }}"></i>
                <span key="t-back-portal" class="fw-bold">Back to Portal</span>
            </a>
        </li>

    </ul>
</div>
