<div class="table-responsive">
    <table class="table table-hover table-bordered align-middle w-100 mb-0">
        <thead class="table-dark text-center">
            <tr>
                <th width="50">No</th>
                <th>Risiko</th>
                <th class="text-center">Arah</th>
                <th>Sasaran Departemen</th>
                <th class="text-center">Rating</th>
                <th>Jenis Risiko</th>
                <th>Taksonomi</th>
                <th class="text-center">Penyebab</th>
                <th class="text-center">Dampak</th>
                <th class="text-center">Analisis</th>
                <th class="text-center">Strategi</th>
                <th class="text-center">Program Kerja</th>
                <th class="text-center">Level</th>
                <th class="text-center">Status</th>
                <th style="width: 210px" class="text-center">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $index => $item)
            @php
                $model = $item->approvalable;
                $rating = optional(optional($model->departmentTarget)->ratingCriteria)->rating ?? null;
            @endphp
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td class="fw-bold">{{ $model->risk ?? '-' }}</td>
                <td class="text-center">
                    @if($model->risk_direction === 'positive')
                        <span class="badge bg-success">Positif</span>
                    @else
                        <span class="badge bg-danger">Negatif</span>
                    @endif
                </td>
                <td>{{ $model->departmentTarget->target ?? '-' }}</td>
                <td class="text-center">
                    @if($rating)
                        <span class="badge bg-primary">{{ $rating }}</span>
                    @else
                        -
                    @endif
                </td>
                <td>{{ $model->riskType->name ?? '-' }}</td>
                <td>{{ $model->riskTaxonomy->name ?? '-' }}</td>
                <td class="text-center">{{ $model->reasons->count() }}</td>
                <td class="text-center">{{ $model->impacts->count() }}</td>
                <td class="text-center">{{ $model->analysis->count() }}</td>
                <td class="text-center">{{ $model->departmentRiskStrategies->count() }}</td>
                <td class="text-center">
                    @if($model->workPrograms->count() > 0)
                        <span class="badge bg-success">{{ $model->workPrograms->count() }}</span>
                    @else
                        <span class="badge bg-warning text-dark">0</span>
                    @endif
                </td>
                <td class="text-center">{{ $item->getLevelLabel() }}</td>
                <td class="text-center">
                    <span class="badge bg-{{ $model->statusClass() }}">{{ $model->statusLabel() }}</span>
                </td>
                <td class="text-center">
                    @include('erkap.approvals.partials.actions', ['item' => $item, 'type' => $type])
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="15" class="text-center py-4 text-muted">Tidak ada Form 1 (identifikasi risiko) yang menunggu persetujuan.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
