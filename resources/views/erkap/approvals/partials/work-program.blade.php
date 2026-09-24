@php
    $monthColumns = ['jan_plan', 'feb_plan', 'mar_plan', 'apr_plan', 'may_plan', 'jun_plan', 'jul_plan', 'aug_plan', 'sep_plan', 'oct_plan', 'nov_plan', 'dec_plan'];
    $monthLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
@endphp
<div class="table-responsive">
    <table class="table table-hover table-bordered align-middle w-100 mb-0">
        <thead class="table-dark text-center">
            <tr>
                <th width="50">No</th>
                <th>Identifikasi Risiko</th>
                <th>Program Kerja</th>
                <th>Satuan</th>
                <th class="text-center">Rencana Tahunan</th>
                @foreach($monthLabels as $label)
                <th class="text-center">{{ $label }}</th>
                @endforeach
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
                <td class="fw-bold">{{ $model->riskIdentification->risk ?? '-' }}</td>
                <td>{{ $model->name }}</td>
                <td>{{ $model->units }}</td>
                <td class="text-center">{{ $model->year_plan !== null ? number_format($model->year_plan, 0, ',', '.') : '-' }}</td>
                @foreach($monthColumns as $column)
                <td class="text-center">{{ $model->{$column} ?? '-' }}</td>
                @endforeach
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
                <td colspan="21" class="text-center py-4 text-muted">Tidak ada program kerja yang menunggu persetujuan.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>