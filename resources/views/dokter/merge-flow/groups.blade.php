@extends('layouts.Dokter')

@section('title', $pageName)

@section('css')
<style>
    .text-dark { color: #000000 !important; }
</style>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="card shadow-sm">
            <div class="card-body border-bottom bg-light d-flex justify-content-between align-items-center">
                <h5 class="mb-0 card-title text-dark fw-bold">{{ $pageName }}</h5>
                <div class="d-flex gap-1">
                    <a href="{{ route('dokter.merge-flows.index') }}" class="btn btn-secondary">
                        <i class="mdi mdi-arrow-left me-1"></i> Kembali
                    </a>
                    <a href="#!" class="btn btn-light" id="btn-refresh"><i class="mdi mdi-refresh"></i></a>
                </div>
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

                <form method="GET" class="row g-2 mb-4">
                    <div class="col-md-3">
                        <select name="status" class="form-select">
                            <option value="">-- Semua Status --</option>
                            <option value="0" {{ request('status') == '0' ? 'selected' : '' }}>Pending</option>
                            <option value="1" {{ request('status') == '1' ? 'selected' : '' }}>Lengkap</option>
                            <option value="2" {{ request('status') == '2' ? 'selected' : '' }}>Selesai</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="text" name="vendor_name" class="form-control" placeholder="Cari vendor..." value="{{ request('vendor_name') }}">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100"><i class="mdi mdi-magnify me-1"></i> Filter</button>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-hover table-bordered align-middle w-100">
                        <thead class="table-dark">
                            <tr>
                                <th class="text-center" style="width: 50px">No</th>
                                <th>Alur</th>
                                <th>Vendor</th>
                                <th>Nomor Root</th>
                                <th>Items</th>
                                <th class="text-center">Status</th>
                                <th>Final PDF</th>
                                <th>Merged At</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($groups as $key => $group)
                            <tr>
                                <td class="text-center">{{ $groups->firstItem() + $key }}</td>
                                <td>{{ $group->mergeFlow->name ?? 'N/A' }}</td>
                                <td>{{ $group->vendor_name }}</td>
                                <td><code>{{ $group->root_document_number }}</code></td>
                                <td>
                                    @foreach($group->items->sortBy('order') as $item)
                                        <span class="badge bg-secondary me-1">
                                            {{ $item->order }}. {{ $item->documentType->name ?? 'N/A' }}: {{ $item->document_number }}
                                        </span>
                                    @endforeach
                                </td>
                                <td class="text-center">
                                    <span class="badge {{ $group->status_badge_class }}">{{ $group->status_label }}</span>
                                </td>
                                <td>
                                    @if($group->final_pdf_path)
                                        <code class="small">{{ $group->final_pdf_path }}</code>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>{{ $group->merged_at?->format('d/m/Y H:i') ?? '-' }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted">Belum ada grup penggabungan.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-end mt-3">
                    {{ $groups->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('plugin')
<script>
    $(document).ready(function() {
        $('#btn-refresh').click(function() {
            location.reload();
        });
    });
</script>
@endsection
