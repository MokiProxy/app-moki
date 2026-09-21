@php
    $monthColumns = ['jan_cost', 'feb_cost', 'mar_cost', 'apr_cost', 'may_cost', 'jun_cost', 'jul_cost', 'aug_cost', 'sep_cost', 'oct_cost', 'nov_cost', 'des_cost'];
    $monthLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
@endphp
<div class="table-responsive">
    <table class="table table-hover table-bordered align-middle w-100 mb-0">
        <thead class="table-dark text-center">
            <tr>
                <th width="50">No</th>
                <th>Program Kerja</th>
                <th>Kebutuhan</th>
                <th>Pusat Biaya</th>
                <th class="text-center">Qty</th>
                <th>Satuan</th>
                <th class="text-end">Harga Satuan</th>
                <th>Elemen Biaya</th>
                @foreach($monthLabels as $label)
                <th class="text-center">{{ $label }}</th>
                @endforeach
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
                <td>{{ $model->need }}</td>
                <td>
                    @if($model->costCenter)
                        {{ $model->costCenter->code }} - {{ $model->costCenter->name }}
                        <small class="d-block text-muted">{{ $model->cost_center_owner }}</small>
                    @else
                        {{ $model->cost_center_owner }}
                    @endif
                </td>
                <td class="text-center">{{ $model->qty }}</td>
                <td>{{ $model->units }}</td>
                <td class="text-end">{{ number_format($model->unit_price, 0, ',', '.') }}</td>
                <td>{{ $model->costElement->name ?? '-' }}</td>
                @foreach($monthColumns as $column)
                <td class="text-center">{{ $model->{$column} ?? '-' }}</td>
                @endforeach
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
                <td colspan="24" class="text-center py-4 text-muted">Tidak ada biaya rutin yang menunggu persetujuan.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>