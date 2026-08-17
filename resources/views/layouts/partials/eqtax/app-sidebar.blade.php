@php
$authUserRoleNames = auth()->user()->getRoleNames();
$authUserRoleId = $authUserRoleNames->first() ?? 'none';
$roleColor = "primary-it-admin";
@endphp

<div id="sidebar-menu" class="mt-2">

    <ul class="metismenu list-unstyled" id="side-menu">

        <li>
            <a href="{{ route('eqtax.index') }}" class="waves-effect">
                <i class="bx bx-home-circle"></i>
                <span key="t-dashboard">Dashboard</span>
            </a>
        </li>

        <li>
            <a href="{{ route('eqtax.spt.coretax.index') }}" class="waves-effect">
                <i class="bx bx-file"></i>
                <span key="t-dashboard">SPT Coretax</span>
            </a>
        </li>

        <li>
            <a href="{{ route('eqtax.gl.index') }}" class="waves-effect">
                <i class="bx bx-file"></i>
                <span key="t-dashboard">General Ledger</span>
            </a>
        </li>

        <li>
            <a href="{{ route('eqtax.equalization.index') }}" class="waves-effect">
                <i class="bx bx-file"></i>
                <span key="t-dashboard">Ekualisasi Pajak</span>
            </a>
        </li>

        <li>
            <a href="{{ route('portal.index') }}" class="waves-effect text-{{ $roleColor }}">
                <i class="bx bx-grid-alt text-{{ $roleColor }}"></i>
                <span key="t-back-portal" class="fw-bold">Back to Portal</span>
            </a>
        </li>

    </ul>
</div>
