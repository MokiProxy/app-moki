@php
$authUserRoleNames = auth()->user()->getRoleNames();
$authUserRoleId = $authUserRoleNames->first() ?? 'none';
$roleColor = "primary-it-admin";
$pendingApprovalCount = \App\Models\Erkap\Approval::query()
    ->where('approver_id', auth()->id())
    ->where('status', 'pending')
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

        @can('erkap.approvals.view')
        <li>
            <a href="{{ route('erkap.approvals.index') }}" class="waves-effect">
                <i class="bx bx-check-shield"></i>
                <span key="t-approval">Approval</span>
                @if($pendingApprovalCount > 0)
                <span class="badge bg-danger ms-1">{{ $pendingApprovalCount }}</span>
                @endif
            </a>
        </li>
        @endcan

        @can('erkap.audit-logs.view')
        <li>
            <a href="{{ route('erkap.audit-logs.index') }}" class="waves-effect">
                <i class="bx bx-history"></i>
                <span key="t-audit-log">Audit Trail</span>
            </a>
        </li>
        @endcan

        @can('erkap.rkap.view')
        <li>
            <a href="javascript: void(0);" class="has-arrow waves-effect">
                <i class='bx bx-data'></i>
                <span key="t-master-data">Master Data</span>
            </a>
            <ul class="sub-menu" aria-expanded="false">
                <ul class="metismenu list-unstyled" id="side-menu">
                    <li>
                        <a href="javascript: void(0);" class="has-arrow waves-effect">
                            <i class='bx bx-data'></i>
                            <span key="t-master-data">Chart of Accounts</span>
                        </a>
                        <ul class="sub-menu" aria-expanded="false">
                            <li><a href="{{ route('erkap.chart-of-accounts.index') }}">Chart of Accounts</a></li>
                            <li><a href="{{ route('erkap.cost-element-categories.index') }}">Kategori Elemen Biaya</a></li>
                            <li><a href="{{ route('erkap.cost-elements.index') }}">Elemen Biaya</a></li>
                        </ul>
                    </li>
                </ul>
                <ul class="metismenu list-unstyled" id="side-menu">
                    <li>
                        <a href="javascript: void(0);" class="has-arrow waves-effect">
                            <i class='bx bx-data'></i>
                            <span key="t-master-data">Risk Taxonomy</span>
                        </a>
                        <ul class="sub-menu" aria-expanded="false">
                            <li><a href="{{ route('erkap.risk-appetites.index') }}">Risk Appetites</a></li>
                            <li><a href="{{ route('erkap.risk-taxonomies.index') }}">Risk Taxonomies</a></li>
                            <li><a href="{{ route('erkap.risk-types.index') }}">Risk Types</a></li>
                        </ul>
                    </li>
                </ul>
                <li>
                    <a href="javascript: void(0);" class="has-arrow waves-effect">
                        <i class='bx bx-data'></i>
                        <span key="t-master-data">Rating Criteria</span>
                    </a>
                    <ul class="sub-menu" aria-expanded="false">
                        <li><a href="{{ route('erkap.rating-criterias.index') }}">Rating Criteria</a></li>
                    </ul>
                </li>
                <li>
                    <a href="javascript: void(0);" class="has-arrow waves-effect">
                        <i class='bx bx-data'></i>
                        <span key="t-master-data">Risk Matrix</span>
                    </a>
                    <ul class="sub-menu" aria-expanded="false">
                        <li><a href="{{ route('erkap.risk-scales.index') }}">Risk Scales</a></li>
                        <li><a href="{{ route('erkap.risk-probabilities.index') }}">Risk Probabilities</a></li>
                        <li><a href="{{ route('erkap.risk-impacts.index') }}">Risk Impacts</a></li>
                        <li><a href="{{ route('erkap.risk-score-levels.index') }}">Risk Score & Levels</a></li>
                    </ul>
                </li>
                <li>
                    <a href="javascript: void(0);" class="has-arrow waves-effect">
                        <i class='bx bx-data'></i>
                        <span key="t-master-data">Investment Criteria</span>
                    </a>
                    <ul class="sub-menu" aria-expanded="false">
                        <li><a href="{{ route('erkap.investation-types.index') }}">Investation Types</a></li>
                        <li><a href="{{ route('erkap.investation-criterias.index') }}">Investation Criterias</a></li>
                        <li><a href="{{ route('erkap.investattion-categories.index') }}">Investattion Categories</a></li>
                    </ul>
                </li>
                <li>
                    <a href="javascript: void(0);" class="has-arrow waves-effect">
                        <i class='bx bx-data'></i>
                        <span key="t-master-data">Periode RKAP</span>
                    </a>
                    <ul class="sub-menu" aria-expanded="false">
                        <li><a href="{{ route('erkap.rkap.index') }}">Periode RKAP</a></li>
                    </ul>
                </li>
                @can('erkap.cost-centers.view')
                <li>
                    <a href="{{ route('erkap.cost-centers.index') }}" class="waves-effect">
                        <i class='bx bx-hash'></i>
                        <span key="t-cost-center">Pusat Biaya (Cost Center)</span>
                    </a>
                </li>
                @endcan
            </ul>
        </li>
        @endcan

        @can('erkap.department-targets.view')
        <li>
            <a href="javascript: void(0);" class="has-arrow waves-effect">
                <i class='bx bx-file'></i>
                <span key="t-master-data">Sasaran Asesmen Risiko</span>
            </a>
            <ul class="sub-menu" aria-expanded="false">
                <ul class="sub-menu" aria-expanded="false">
                    <li>
                        <a href="javascript: void(0);" class="has-arrow waves-effect">
                            <i class='bx bx-file'></i>
                            <span key="t-master-data">Sasaran</span>
                        </a>
                        <ul class="sub-menu" aria-expanded="false">
                            @can('erkap.company-targets.view')
                            <li><a href="{{ route('erkap.company-targets.index') }}">Sasaran Perusahaan</a></li>
                            @endcan
                            <li><a href="{{ route('erkap.department-targets.index') }}">Sasaran Departemen</a></li>
                        </ul>
                    </li>
                </ul>
                <ul class="sub-menu" aria-expanded="false">
                    <li>
                        <a href="javascript: void(0);" class="has-arrow waves-effect">
                            <i class='bx bx-file'></i>
                            <span key="t-master-data">Identifikasi Risiko</span>
                        </a>
                        <ul class="sub-menu" aria-expanded="false">
                            <li><a href="{{ route('erkap.risk-identifications.index') }}">Identifikasi Risiko</a></li>
                            <li><a href="{{ route('erkap.risk-identification-reasons.index') }}">Alasan Identifikasi</a></li>
                            <li><a href="{{ route('erkap.risk-identification-impacts.index') }}">Dampak Identifikasi</a></li>
                        </ul>
                    </li>
                </ul>
                <ul class="sub-menu" aria-expanded="false">
                    <li>
                        <a href="javascript: void(0);" class="has-arrow waves-effect">
                            <i class='bx bx-file'></i>
                            <span key="t-master-data">Analisis Risiko</span>
                        </a>
                        <ul class="sub-menu" aria-expanded="false">
                            <li><a href="{{ route('erkap.risk-analysis.index') }}">Analisis Risiko</a></li>
                            <li><a href="{{ route('erkap.risk-rankings.index') }}">Peringkat Risiko</a></li>
                        </ul>
                    </li>
                </ul>
                <ul class="sub-menu" aria-expanded="false">
                    <li>
                        <a href="javascript: void(0);" class="has-arrow waves-effect">
                            <i class='bx bx-file'></i>
                            <span key="t-master-data">Strategi Risiko</span>
                        </a>
                        <ul class="sub-menu" aria-expanded="false">
                            <li><a href="{{ route('erkap.department-risk-strategies.index') }}">Strategi Risiko Departemen</a></li>
                        </ul>
                    </li>
                </ul>
            </ul>
        </li>
        @endcan

        @can('erkap.work-programs.view')
        <li>
            <a href="javascript: void(0);" class="has-arrow waves-effect">
                <i class='bx bx-timer'></i>
                <span key="t-master-data">Jadwal Kerja</span>
            </a>
            <ul class="sub-menu" aria-expanded="false">
                            <li><a href="{{ route('erkap.work-programs.index') }}">Program Kerja</a></li>
                        </ul>
        </li>
        @endcan

        @can('erkap.routine-costs.view')
        <li>
            <a href="javascript: void(0);" class="has-arrow waves-effect">
                <i class='bx bx-money'></i>
                <span key="t-master-data">Biaya Umum</span>
            </a>
            <ul class="sub-menu" aria-expanded="false">
                <li><a href="{{ route('erkap.routine-costs.index') }}">Biaya Rutin</a></li>
            </ul>
        </li>
        @endcan

        @can('erkap.investment-plans.view')
        <li>
            <a href="javascript: void(0);" class="has-arrow waves-effect">
                <i class='bx bx-trending-up'></i>
                <span key="t-master-data">Investasi (CAPEX)</span>
            </a>
            <ul class="sub-menu" aria-expanded="false">
                <li><a href="{{ route('erkap.investment-plans.index') }}">Rencana Investasi</a></li>
                <li><a href="{{ route('erkap.budget-capex.index') }}">Anggaran Investasi</a></li>
            </ul>
        </li>
        @endcan

        @can('erkap.revenue-plans.view')
        <li>
            <a href="javascript: void(0);" class="has-arrow waves-effect">
                <i class='bx bx-line-chart'></i>
                <span key="t-master-data">Financial Projection</span>
            </a>
            <ul class="sub-menu" aria-expanded="false">
                <li><a href="{{ route('erkap.revenue-plans.index') }}">Rencana Pendapatan</a></li>
                <li><a href="{{ route('erkap.expense-plans.index') }}">Rencana Beban</a></li>
                <li><a href="{{ route('erkap.profit-loss.index') }}">Laba Rugi (P&L)</a></li>
                <li><a href="{{ route('erkap.profit-loss.simulate') }}">Simulasi Skenario</a></li>
            </ul>
        </li>
        @endcan

        @can('erkap.budget-realizations.view')
        <li>
            <a href="javascript: void(0);" class="has-arrow waves-effect">
                <i class='bx bx-line-chart-down'></i>
                <span key="t-monitoring">Monitoring & Realisasi</span>
            </a>
            <ul class="sub-menu" aria-expanded="false">
                <li><a href="{{ route('erkap.budget-realizations.index') }}">Realisasi Anggaran (BvA)</a></li>
                @can('erkap.program-realizations.view')
                <li><a href="{{ route('erkap.program-realizations.index') }}">Realisasi Program Kerja</a></li>
                @endcan
                @can('erkap.risk-assessments-monthly.view')
                <li><a href="{{ route('erkap.risk-assessments-monthly.index') }}">Risk Assessment Bulanan</a></li>
                @endcan
                @can('erkap.performance-scorecards.view')
                <li><a href="{{ route('erkap.performance-scorecards.index') }}">Performance Scorecard (KPI)</a></li>
                @endcan
            </ul>
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
