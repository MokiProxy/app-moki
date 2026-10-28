@php
    $authUserRoleNames = auth()->user()->getRoleNames();
    $authUserRoleId = $authUserRoleNames->first() ?? 'none';
    $roleColor = "primary-it-admin";
    $pendingApprovalCount = \App\Models\Erkap\Approval::query()
        ->where('approver_id', auth()->id())
        ->where('status', 'pending')
        ->get()
        ->reject(function ($approval) {
            $model = $approval->approvalable;
            return $model instanceof \App\Models\Erkap\InvestmentPlan
                && $model->stageGates()->where('status', 'pending')->exists();
        })
        ->count();
@endphp

<div id="sidebar-menu" class="mt-2">

    <ul class="metismenu list-unstyled" id="side-menu">

        @can('erkap.menu')
            <li>
                <a href="{{ route('erkap.index') }}" class="waves-effect">
                    <i class="bx bx-home-circle"></i>
                    <span key="t-dashboard">Dashboard</span>
                </a>
            </li>
        @endcan

        @canany(['erkap.approvals.view', 'erkap.investment-gates.view'])
            <li>
                <a href="javascript: void(0);" class="has-arrow waves-effect">
                    <i class="bx bx-money"></i>
                    <span key="t-approval">Pendapatan</span>
                </a>
                <ul class="sub-menu" aria-expanded="false">
                    @can('erkap.approvals.view')
                        <li><a href="{{ route('simka.spph773.index') }}">SPPH 773</a></li>
                    @endcan
                    @can('erkap.approvals.view')
                        <li><a href="{{ route('simka.spph2287.index') }}">SPPH 2287</a></li>
                    @endcan
                    @can('erkap.approvals.view')
                        <li><a href="{{ route('simka.spph2899.index') }}">SPPH 2899</a></li>
                    @endcan
                    @can('erkap.approvals.view')
                        <li><a href="{{ route('simka.spph16368.index') }}">SPPH 16368</a></li>
                    @endcan
                </ul>
            </li>
        @endcanany

        <li>
            <a href="{{ route('portal.index') }}" class="waves-effect text-{{ $roleColor }}">
                <i class="bx bx-grid-alt text-{{ $roleColor }}"></i>
                <span key="t-back-portal" class="fw-bold">Back to Portal</span>
            </a>
        </li>

    </ul>
</div>
