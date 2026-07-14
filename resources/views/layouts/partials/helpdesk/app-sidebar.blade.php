@php
$role = session('user_role');
@endphp

<div id="sidebar-menu">
    <ul class="metismenu list-unstyled" id="side-menu">

        <li class="menu-title" key="t-portal">Main Navigation</li>
        <li>
            <a href="{{ route('portal.index') }}" class="waves-effect" style="color: #f46a6a;">
                <i class="bx bx-grid-alt" style="color: #f46a6a;"></i>
                <span key="t-back-portal" class="fw-bold">Back to Portal</span>
            </a>
        </li>

        <li class="menu-title" key="t-menu">Ticket Management</li>

        <li>
            <a href="{{ route('helpdesk.index') }}" class="waves-effect">
                <i class="bx bx-home-circle"></i>
                <span key="t-dashboard">Dashboard</span>
            </a>
        </li>

        {{-- JIKA BUKAN APPROVER, TAMPILKAN MASTER DATA --}}
        @if($role != 2)
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

        @if($role != 2)
        <li>
            <a href="javascript: void(0);" class="has-arrow waves-effect">
                <i class='mdi mdi-ticket'></i>
                <span key="t-master-data">Manajemen Tiket</span>
            </a>
            <ul class="sub-menu" aria-expanded="false">
                <li><a href="{{ route('employee') }}"><i class='mdi mdi-ticket'></i>Semua Tiket</a></li>
                @if ($role == "")
                <li><a href="{{ route('helpdesk.tickets.create') }}"><i class='mdi mdi-ticket'></i>Buat Tiket</a></li>
                @endif
                <li><a href="{{ route('regional') }}"><i class='mdi mdi-ticket'></i>Prioritas Tiket</a></li>
            </ul>
        </li>
        @endif

        @if($role != 2)
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
        @endif

    </ul>
</div>
