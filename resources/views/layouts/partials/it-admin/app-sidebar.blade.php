@php
$authUserRoleNames = auth()->user()->getRoleNames();
$authUserRoleId = $authUserRoleNames->first() ?? 'none';
$roleColor = "primary-it-admin";
@endphp

<div id="sidebar-menu" class="mt-2">

    <ul class="metismenu list-unstyled" id="side-menu">

        <li>
            <a href="{{ route('it-admin.index') }}" class="waves-effect">
                <i class="bx bx-home-circle"></i>
                <span key="t-dashboard">Dashboard</span>
            </a>
        </li>

        <li>
            <a href="{{ route('it-admin.users.index') }}" class="waves-effect">
                <i class="bx bx-user"></i>
                <span key="t-dashboard">Manajemen User</span>
            </a>
        </li>

        <li>
            <a href="{{ route('it-admin.roles.index') }}" class="waves-effect">
                <i class="bx bx-cog"></i>
                <span key="t-dashboard">Manajemen Role</span>
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
