@php
$authUserRoleNames = auth()->user()->getRoleNames();
$authUserRoleId = $authUserRoleNames->first() ?? 'none';
$roleColor = "primary-dokter";
@endphp

<div id="sidebar-menu" class="mt-2">

    <ul class="metismenu list-unstyled" id="side-menu">

        @can('dokter.dashboard')
        <li>
            <a href="{{ route('dokter.index') }}" class="waves-effect">
                <i class="bx bx-home-circle"></i>
                <span key="t-dashboard">Dashboard</span>
            </a>
        </li>
        @endcan

        @can('dokter.file-managements.view')
        <li>
            <a href="{{ route('dokter.file-managements.index') }}" class="waves-effect">
                <i class='bx bx-folder'></i>
                <span key="t-file-management">File Management</span>
            </a>
        </li>
        @endcan

        @can('dokter.log-file.view')
        <li>
            <a href="{{ route('dokter.log-file.index') }}" class="waves-effect">
                <i class='bx bx-history'></i>
                <span key="t-log-file">Log File</span>
            </a>
        </li>
        @endcan

        @can('dokter.document-types.view')
        <li>
            <a href="javascript: void(0);" class="has-arrow waves-effect">
                <i class='bx bx-data'></i>
                <span key="t-master-data">Master Data</span>
            </a>
            <ul class="sub-menu" aria-expanded="false">
                @can('dokter.document-types.view')
                <li><a href="{{ route('dokter.document-types.index') }}"><i class='mdi mdi-file'></i>Jenis Dokumen</a></li>
                @endcan
                @can('dokter.vendors.view')
                <li><a href="{{ route('dokter.vendors.index') }}"><i class='mdi mdi-alert-box'></i>Vendor</a></li>
                @endcan
            </ul>
        </li>
        @endcan

        @can('dokter.merge-flows.view')
        <li>
            <a href="{{ route('dokter.merge-flows.index') }}" class="waves-effect">
                <i class='bx bx-git-merge'></i>
                <span key="t-alur-birokrasi">Alur Birokrasi</span>
            </a>
        </li>
        @endcan
        <li>
            <a href="{{ route('portal.index') }}" class="waves-effect text-{{ $roleColor }}">
                <i class="bx bx-grid-alt text-{{ $roleColor }}"></i>
                <span key="t-back-portal" class="fw-bold">Back to Portal</span>
            </a>
        </li>

    </ul>
</div>
