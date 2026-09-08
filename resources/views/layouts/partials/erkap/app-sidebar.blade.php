@php
$authUserRoleNames = auth()->user()->getRoleNames();
$authUserRoleId = $authUserRoleNames->first() ?? 'none';
$roleColor = "primary-it-admin";
@endphp

<div id="sidebar-menu" class="mt-2">

    <ul class="metismenu list-unstyled" id="side-menu">

        <li>
            <a href="{{ route('erkap.index') }}" class="waves-effect">
                <i class="bx bx-home-circle"></i>
                <span key="t-dashboard">Dashboard</span>
            </a>
        </li>

        <li>
            <a href="javascript: void(0);" class="has-arrow waves-effect">
                <i class='bx bx-data'></i>
                <span key="t-master-data">Master Data</span>
            </a>
            <ul class="sub-menu" aria-expanded="false">
                <li><a href="{{ route('erkap.cost-element-categories.index') }}">Kategori Elemen Biaya</a></li>
                <li><a href="{{ route('erkap.cost-elements.index') }}">Elemen Biaya</a></li>
                <li><a href="{{ route('erkap.risk-appetites.index') }}">Risk Appetites</a></li>
                <li><a href="{{ route('erkap.risk-taxonomies.index') }}">Risk Taxonomies</a></li>
                <li><a href="{{ route('erkap.risk-types.index') }}">Risk Types</a></li>
                <li><a href="{{ route('erkap.rating-criterias.index') }}">Rating Criteria</a></li>
                <li><a href="{{ route('erkap.risk-scales.index') }}">Risk Scales</a></li>
                <li><a href="{{ route('erkap.risk-probabilities.index') }}">Risk Probabilities</a></li>
                <li><a href="{{ route('erkap.risk-impacts.index') }}">Risk Impacts</a></li>
                <li><a href="{{ route('erkap.risk-score-levels.index') }}">Risk Score & Levels</a></li>
                <li><a href="{{ route('erkap.investation-types.index') }}">Investation Types</a></li>
                <li><a href="{{ route('erkap.investation-criterias.index') }}">Investation Criterias</a></li>
                <li><a href="{{ route('erkap.investattion-categories.index') }}">Investattion Categories</a></li>
            </ul>
        </li>

        <li>
            <a href="{{ route('portal.index') }}" class="waves-effect text-{{ $roleColor }}">
                <i class="bx bx-grid-alt text-{{ $roleColor }}"></i>
                <span key="t-back-portal" class="fw-bold">Back to Portal</span>
            </a>
        </li>

    </ul>
</div>
