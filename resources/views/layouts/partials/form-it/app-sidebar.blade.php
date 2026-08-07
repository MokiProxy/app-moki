@php
$roleColor = "primary-form-it";
@endphp

<div id="sidebar-menu" class="mt-2">

    <ul class="metismenu list-unstyled" id="side-menu">

        <li>
            <a href="{{ route('form-it.index') }}" class="waves-effect">
                <i class="bx bx-home-circle"></i>
                <span key="t-dashboard">Dashboard</span>
            </a>
        </li>

        @can('form-it.forms.create')
        <li>
            <a href="{{ route('form-it.forms.software-installation') }}" class="waves-effect">
                <i class="bx bx-file"></i>
                <span key="t-form-software">Buat Pengajuan</span>
            </a>
        </li>
        @endcan

        @can('form-it.fixed-asset.create')
        <li>
            <a href="{{ route('form-it.forms.fixed-asset.create') }}" class="waves-effect">
                <i class="bx bx-laptop"></i>
                <span key="t-form-fixed-asset">Peminjaman Fixed Asset</span>
            </a>
        </li>
        @endcan

        @can('form-it.forms.view')
        <li>
            <a href="{{ route('form-it.forms.my-submissions') }}" class="waves-effect">
                <i class="bx bx-list-ul"></i>
                <span key="t-my-submissions">Pengajuan Saya</span>
            </a>
        </li>
        @endcan

        @can('form-it.fixed-asset.view')
        <li>
            <a href="{{ route('form-it.forms.fixed-asset.my-submissions') }}" class="waves-effect">
                <i class="bx bx-list-check"></i>
                <span key="t-my-submissions-fixed-asset">Pengajuan Fixed Asset Saya</span>
            </a>
        </li>
        @endcan

        @can('form-it.approval.view')
        <li>
            <a href="{{ route('form-it.approval.index') }}" class="waves-effect">
                <i class="bx bx-check-shield"></i>
                <span key="t-approval">Approval</span>
            </a>
        </li>
        @endcan

        @can('form-it.fixed-asset.approve')
        <li>
            <a href="{{ route('form-it.approval.fixed-asset.index') }}" class="waves-effect">
                <i class="bx bx-check-square"></i>
                <span key="t-approval-fixed-asset">Approval Fixed Asset</span>
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
