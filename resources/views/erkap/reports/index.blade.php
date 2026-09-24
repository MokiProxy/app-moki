@extends('layouts.Erkap')

@section('title', $pageName)

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="card shadow-sm">
            <div class="card-body border-bottom bg-light d-flex justify-content-between align-items-center">
                <h5 class="mb-0 card-title fw-bold">{{ $pageName }}</h5>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalGenerate">
                    <i class="mdi mdi-file-document mdi-18px me-1"></i> Generate Laporan
                </button>
            </div>
            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <ul class="nav nav-pills mb-3" id="reportTabs">
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('erkap.reports.preview', ['type' => 'rkap']) }}" target="_blank">
                            <i class="mdi mdi-file-outline me-1"></i> Pratinjau RKAP
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('erkap.reports.preview', ['type' => 'financial']) }}" target="_blank">
                            <i class="mdi mdi-chart-line me-1"></i> Pratinjau Keuangan
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('erkap.reports.preview', ['type' => 'risk']) }}" target="_blank">
                            <i class="mdi mdi-shield-alert me-1"></i> Pratinjau Risiko
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('erkap.reports.preview', ['type' => 'realization']) }}" target="_blank">
                            <i class="mdi mdi-cash-multiple me-1"></i> Pratinjau Realisasi
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('erkap.reports.preview', ['type' => 'performance']) }}" target="_blank">
                            <i class="mdi mdi-speedometer me-1"></i> Pratinjau Performa
                        </a>
                    </li>
                </ul>

                <div class="table-responsive">
                    <table class="table table-hover table-bordered align-middle w-100">
                        <thead class="table-dark">
                            <tr>
                                <th class="text-center" style="width: 50px">No</th>
                                <th>Judul Laporan</th>
                                <th class="text-center">Tipe</th>
                                <th class="text-center">Frekuensi</th>
                                <th class="text-center">Periode</th>
                                <th class="text-center">Format</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Tanggal</th>
                                <th class="text-center" style="width: 90px">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($items as $key => $item)
                            <tr>
                                <td class="text-center">{{ $items->firstItem() + $key }}</td>
                                <td class="fw-bold">{{ $item->title }}</td>
                                <td class="text-center">
                                    <span class="badge bg-info">{{ $item->report_type }}</span>
                                </td>
                                <td class="text-center text-capitalize">{{ $item->frequency }}</td>
                                <td class="text-center">
                                    {{ $item->month ? $item->month . '/' : '' }}{{ $item->year }}
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-{{ $item->format === 'excel' ? 'success' : 'danger' }} text-uppercase">
                                        {{ $item->format }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    @if($item->status === 'generated')
                                        <span class="badge bg-success">Berhasil</span>
                                    @else
                                        <span class="badge bg-danger" title="{{ $item->error }}">Gagal</span>
                                    @endif
                                </td>
                                <td class="text-center">{{ $item->created_at?->format('d M Y H:i') }}</td>
                                <td class="text-center">
                                    @if($item->status === 'generated' && $item->file_path)
                                        <a href="{{ route('erkap.reports.download', $item) }}" class="btn btn-sm btn-success" title="Unduh">
                                            <i class="mdi mdi-download"></i>
                                        </a>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted">Belum ada laporan. Silakan generate laporan baru.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-end mt-3">
                    {{ $items->links('pagination::bootstrap-4') }}
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalGenerate">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="{{ route('erkap.reports.generate') }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Generate Laporan Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Tipe Laporan</label>
                            <select name="report_type" class="form-select" required>
                                <option value="rkap">RKAP</option>
                                <option value="financial">Keuangan (P&L)</option>
                                <option value="risk">Risiko</option>
                                <option value="realization">Realisasi Anggaran</option>
                                <option value="performance">Performa (KPI)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Format</label>
                            <select name="format" class="form-select" required>
                                <option value="pdf">PDF</option>
                                <option value="excel">Excel</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Periode RKAP</label>
                            <select name="rkap_id" class="form-select">
                                <option value="">- Semua -</option>
                                @foreach(\App\Models\Erkap\RKAP::orderByDesc('year')->get() as $rkap)
                                    <option value="{{ $rkap->id }}">{{ $rkap->year }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Tahun</label>
                            <select name="year" class="form-select" required>
                                @for($y = now()->year; $y >= now()->year - 2; $y--)
                                    <option value="{{ $y }}" {{ $y == now()->year ? 'selected' : '' }}>{{ $y }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Bulan</label>
                            <select name="month" class="form-select">
                                <option value="">- Semua -</option>
                                @foreach(['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'] as $key => $label)
                                    <option value="{{ $key + 1 }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Generate</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection