@php
$authUserRoleNames = auth()->user()->getRoleNames();
$authUserRoleId = $authUserRoleNames->first() ?? 'none';
$roleColor = "primary-form-it";
$employeeId = auth()->user()->employee_id;
$isApprover = \App\Models\FormitApproval::where('approver_id', $employeeId)->exists();
@endphp

<div id="sidebar-menu" class="mt-2">

    <ul class="metismenu list-unstyled" id="side-menu">

        <li>
            <a href="{{ route('form-it.index') }}" class="waves-effect">
                <i class="bx bx-home-circle"></i>
                <span key="t-dashboard">Dashboard</span>
            </a>
        </li>

        <li>
            <a href="{{ route('form-it.forms.software-installation') }}" class="waves-effect">
                <i class="bx bx-file"></i>
                <span key="t-form-software">Buat Pengajuan</span>
            </a>
        </li>

        <li>
            <a href="{{ route('form-it.forms.my-submissions') }}" class="waves-effect">
                <i class="bx bx-list-ul"></i>
                <span key="t-my-submissions">Pengajuan Saya</span>
            </a>
        </li>

        @if($isApprover)
        <li>
            <a href="{{ route('form-it.approval.index') }}" class="waves-effect">
                <i class="bx bx-check-shield"></i>
                <span key="t-approval">Approval</span>
            </a>
        </li>
        @endif

        <li>
            <a href="{{ route('portal.index') }}" class="waves-effect text-{{ $roleColor }}">
                <i class="bx bx-grid-alt text-{{ $roleColor }}"></i>
                <span key="t-back-portal" class="fw-bold">Back to Portal</span>
            </a>
        </li>

    </ul>
</div>
