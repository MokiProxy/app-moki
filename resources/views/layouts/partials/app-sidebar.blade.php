@php
    $user = auth()->user();
@endphp

<div id="sidebar-menu">
    <ul class="metismenu list-unstyled" id="side-menu">

        <li class="menu-title" key="t-portal">Main Navigation</li>
        <li>
            <a href="{{ route('portal.index') }}" class="waves-effect" style="color: #f46a6a;">
                <i class="bx bx-grid-alt" style="color: #f46a6a;"></i>
                <span key="t-back-portal" class="fw-bold">Back to Portal</span>
            </a>
        </li>

        <li class="menu-title" key="t-menu">Asset Management</li>

        <li>
            <a href="{{ route('dashboard') }}" class="waves-effect">
                <i class="bx bx-home-circle"></i>
                <span key="t-dashboard">Dashboard</span>
            </a>
        </li>

        @can('ams.master-data.view')
        <li>
            <a href="javascript: void(0);" class="has-arrow waves-effect">
                <i class='bx bx-data'></i>
                <span key="t-master-data">Master Data</span>
            </a>
            <ul class="sub-menu" aria-expanded="false">
                <li><a href="{{ route('employee') }}"><i class='bx bx-user-circle'></i> Karyawan</a></li>
                <li><a href="{{ route('regional') }}"><i class='bx bx-map-alt'></i> Regional</a></li>
                <li><a href="{{ route('company') }}"><i class='bx bx-buildings'></i> Perusahaan</a></li>
                <li><a href="{{ route('division') }}"><i class='bx bx-sitemap'></i> Divisi</a></li>
                <li><a href="{{ route('satuan-kerja') }}"><i class='bx bx-home'></i> Satuan Kerja</a></li>
                <li><a href="{{ route('master-posisi') }}"><i class='bx bx-briefcase-alt'></i> Master Posisi</a></li>
                <li><a href="{{ route('master-hirarki') }}"><i class='bx bx-hierarchy'></i> Master Hirarki</a></li>
                <li><a href="{{ route('pegawai-hirarki') }}"><i class='bx bx-network-chart'></i> Pegawai Hirarki</a></li>
            </ul>
        </li>
        @endcan

        @can('ams.assets.view')
        <li>
            <a href="javascript: void(0);" class="has-arrow waves-effect">
                <i class='bx bx-package'></i>
                <span key="t-asset-group">Aset & Supplier</span>
            </a>
            <ul class="sub-menu" aria-expanded="false">
                <li><a href="{{ route('asset') }}"><i class='bx bx-list-check'></i> Daftar Aset</a></li>

                @can('ams.assignment.view')
                <li><a href="{{ route('assignment.index') }}"><i class='bx bx-user-pin'></i> Penugasan Aset</a></li>
                @endcan
                @can('ams.master-data.view')
                <li><a href="{{ route('category') }}"><i class='bx bx-purchase-tag-alt'></i> Kategori</a></li>
                <li><a href="{{ route('supplier') }}"><i class='bx bx-store'></i> Supplier</a></li>
                @endcan
            </ul>
        </li>
        @endcan

        @can('ams.transactions.view')
        <li>
            <a href="javascript: void(0);" class="has-arrow waves-effect">
                <i class='bx bx-transfer'></i>
                <span key="t-transaction">Transaksi</span>
            </a>
            <ul class="sub-menu" aria-expanded="false">
                @can('ams.transactions.create')
                <li><a href="{{ route('transaction.create') }}">Tambah Baru</a></li>
                @endcan
                <li><a href="{{ route('transaction.index') }}">Riwayat Transaksi</a></li>
            </ul>
        </li>
        @endcan

        @can('ams.monitoring.view')
        <li>
            <a href="javascript: void(0);" class="has-arrow waves-effect">
                <i class='bx bx-printer'></i>
                <span key="t-report-master">Laporan Cetak</span>
            </a>
            <ul class="sub-menu" aria-expanded="false">
                <li><a href="{{ route('monitor.asset') }}">Laporan Aset</a></li>
                <li><a href="{{ route('monitor.employee') }}">Laporan Pengguna</a></li>
            </ul>
        </li>
        @endcan

        @can('ams.whatsapp-settings.manage')
        <li>
            <a href="javascript: void(0);" class="has-arrow waves-effect">
                <i class='bx bx-cog'></i>
                <span key="t-settings">Setting</span>
            </a>
            <ul class="sub-menu" aria-expanded="false">
                @can('ams.whatsapp-settings.manage')
                <li><a href="{{ route('setting-fonnte.index') }}">API Whatsapp</a></li>
                @endcan
            </ul>
        </li>
        @endcan

    </ul>
</div>
