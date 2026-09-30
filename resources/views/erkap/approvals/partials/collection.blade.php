<div class="card mb-3 approval-section">
    <div class="card-header bg-light d-flex align-items-center flex-wrap gap-2">
        <h6 class="mb-0 fw-bold">
            <i class="mdi {{ $icon ?? 'mdi-table' }} me-1"></i> {{ $title }}
        </h6>
        <span class="badge bg-primary ms-auto">{{ count($rows) }} {{ $unit ?? 'data' }}</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0 w-100">
                <thead class="table-light">
                    <tr>
                        <th class="text-center" style="width: 60px;">No</th>
                        @foreach($columns as $column)
                            <th class="{{ $column['class'] ?? '' }}">{{ $column['label'] }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $index => $row)
                        <tr>
                            <td class="text-center text-muted">{{ $index + 1 }}</td>
                            @foreach($columns as $column)
                                <td class="{{ $column['class'] ?? '' }}">{!! $row[$column['key']] ?? '-' !!}</td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($columns) + 1 }}" class="text-center py-4 text-muted">
                                {{ $empty ?? 'Belum ada data.' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @isset($footer)
                    <tfoot class="table-light">
                        <tr>
                            <td colspan="{{ count($columns) + 1 }}" class="fw-bold text-end">{!! $footer !!}</td>
                        </tr>
                    </tfoot>
                @endisset
            </table>
        </div>
    </div>
</div>
