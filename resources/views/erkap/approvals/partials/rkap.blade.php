<div class="table-responsive">
    <table class="table table-hover table-bordered align-middle w-100 mb-0">
        <thead class="table-dark text-center">
            <tr>
                <th width="50">No</th>
                <th>Periode RKAP</th>
                <th class="text-center">Level</th>
                <th class="text-center">Status</th>
                <th style="width: 210px" class="text-center">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $index => $item)
            @php
                $model = $item->approvalable;
            @endphp
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td class="fw-bold">RKAP {{ $model->year }}</td>
                <td class="text-center">{{ $item->getLevelLabel() }}</td>
                <td class="text-center">
                    <span class="badge bg-soft-warning text-warning">{{ $model->statusLabel() }}</span>
                </td>
                <td class="text-center">
                    @include('erkap.approvals.partials.actions', ['item' => $item, 'type' => $type])
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="text-center py-4 text-muted">Tidak ada periode RKAP yang menunggu persetujuan.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>