@php
$role = session('user_role');
$authUserRoleId = auth()->user()->role_id;

if($authUserRoleId == 1 || $authUserRoleId == 5) {
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

        @if (in_array($authUserRoleId, [1, 5]))
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
                @if (in_array($authUserRoleId, [1, 5]))
                <li><a href="{{ route('helpdesk.tickets.index') }}"><i class='mdi mdi-ticket'></i>Semua Tiket</a></li>
                @endif

                @if(in_array($authUserRoleId, [4, 3]))
                <li><a href="{{ route('helpdesk.tickets.index') }}"><i class='mdi mdi-ticket'></i>Tiket Saya</a></li>
                @endif

                @if(in_array($authUserRoleId, [1, 3]))
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
