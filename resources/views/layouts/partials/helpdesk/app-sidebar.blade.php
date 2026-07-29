@php
$authUserRoleId = auth()->user()->getRoleNames()->first();

if($authUserRoleId == 'super-admin' || $authUserRoleId == 'admin') {
$roleColor = "danger";
} else if ($authUserRoleId == 'staff') {
$roleColor = "success";
} else if ($authUserRoleId == 'teknisi') {
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

        @role('super-admin|admin')
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
        @endrole

        <li>
            <a href="javascript: void(0);" class="has-arrow waves-effect">
                <i class='mdi mdi-ticket'></i>
                <span key="t-master-data">Manajemen Tiket</span>
            </a>
            <ul class="sub-menu" aria-expanded="false">
                @role('super-admin|admin')
                <li><a href="{{ route('helpdesk.tickets.index') }}"><i class='mdi mdi-ticket'></i>Semua Tiket</a></li>
                @endrole

                @role('teknisi|staff')
                <li><a href="{{ route('helpdesk.tickets.index') }}"><i class='mdi mdi-ticket'></i>Tiket Saya</a></li>
                @endrole

                @role('super-admin|staff')
                <li><a href="{{ route('helpdesk.tickets.create') }}"><i class='mdi mdi-plus'></i>Buat Tiket</a></li>
                @endrole
            </ul>
        </li>

        @role('super-admin')
        <li><a href="{{ route('helpdesk.reports.index') }}"><i class='bx bx-file'></i>Laporan</a></li>
        @endrole

        <li>
            <a href="{{ route('portal.index') }}" class="waves-effect text-{{ $roleColor }}">
                <i class="bx bx-grid-alt text-{{ $roleColor }}"></i>
                <span key="t-back-portal" class="fw-bold">Back to Portal</span>
            </a>
        </li>

    </ul>
</div>
