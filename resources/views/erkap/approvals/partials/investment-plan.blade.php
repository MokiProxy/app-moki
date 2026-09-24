<div class="table-responsive">
    <table class="table table-hover table-bordered align-middle w-100 mb-0">
        <thead class="table-dark text-center">
            <tr>
                <th width="50">No</th>
                <th>Program Kerja</th>
                <th>Nama Investasi</th>
                <th>Deskripsi</th>
                <th>Kategori</th>
                <th>Tipe</th>
                <th>Kriteria</th>
                <th class="text-center">Qty</th>
                <th>Satuan</th>
                <th class="text-end">Harga Satuan</th>
                <th class="text-end">Total</th>
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
                <td class="fw-bold">{{ $model->workProgram->name ?? '-' }}</td>
                <td>{{ $model->name }}</td>
                <td style="max-width: 300px;">{{ $model->description }}</td>
                <td>{{ $model->investattionCategory->name ?? '-' }}</td>
                <td>{{ $model->investationType->name ?? '-' }}</td>
                <td>{{ $model->investationCriteria->name ?? '-' }}</td>
                <td class="text-center">{{ $model->qty }}</td>
                <td>{{ $model->unit }}</td>
                <td class="text-end">{{ number_format($model->unit_price, 0, ',', '.') }}</td>
                <td class="text-end fw-bold">{{ number_format($model->total, 0, ',', '.') }}</td>
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
                <td colspan="14" class="text-center py-4 text-muted">Tidak ada rencana investasi yang menunggu persetujuan.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>