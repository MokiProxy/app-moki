@php
$authUserRoleNames = auth()->user()->getRoleNames();
$authUserRoleId = $authUserRoleNames->first() ?? 'none';
$roleColor = "primary-it-admin";
@endphp

<div id="sidebar-menu" class="mt-2">

    <ul class="metismenu list-unstyled" id="side-menu">

        @can('eqtax.dashboard')
        <li>
            <a href="{{ route('eqtax.index') }}" class="waves-effect">
                <i class="bx bx-home-circle"></i>
                <span key="t-dashboard">Dashboard</span>
            </a>
        </li>
        @endcan

        @can('eqtax.spt.coretax.view')
        <li>
            <a href="{{ route('eqtax.spt.coretax.index') }}" class="waves-effect">
                <i class="bx bx-file-blank"></i>
                <span key="t-spt">SPT Coretax</span>
            </a>
        </li>
        @endcan

        @can('eqtax.gl.view')
        <li>
            <a href="{{ route('eqtax.gl.index') }}" class="waves-effect">
                <i class="bx bx-book"></i>
                <span key="t-gl">General Ledger</span>
            </a>
        </li>
        @endcan

        @can('eqtax.equalization.view')
        <li>
            <a href="{{ route('eqtax.equalization.index') }}" class="waves-effect">
                <i class="bx bx-cog"></i>
                <span key="t-equalization">Ekualisasi Pajak</span>
            </a>
        </li>
        @endcan

        @can('eqtax.tb.view')
        <li>
            <a href="{{ route('eqtax.tb.index') }}" class="waves-effect">
                <i class="bx bx-file-blank"></i>
                <span key="t-tb">Pencocokkan TB</span>
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
