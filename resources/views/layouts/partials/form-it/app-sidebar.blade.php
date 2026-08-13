@php
$roleColor = "primary-form-it";
use App\Models\FormitApproval;

$employeeId = auth()->user()->employee_id;
$isApprover = FormitApproval::where('approver_id', $employeeId)->exists();
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
            <a href="{{ route('form-it.forms.index') }}" class="waves-effect">
                <i class="bx bx-file"></i>
                <span key="t-dashboard">Formulir</span>
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
