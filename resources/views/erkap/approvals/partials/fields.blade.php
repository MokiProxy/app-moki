@php
    $fieldColumns = max(1, (int) ($columns ?? 3));
    $fieldWidth = intdiv(12, $fieldColumns);
@endphp

<div class="row g-3 {{ $class ?? '' }}">
    @foreach($fields as $field)
        <div class="col-12 col-sm-6 col-xl-{{ $fieldWidth }}">
            <div class="detail-field h-100">
                <div class="detail-field-label">{{ $field['label'] }}</div>
                <div class="detail-field-value">
                    @if(($field['badge'] ?? null) !== null)
                        <span class="badge bg-{{ $field['badge'] }}">{{ $field['value'] }}</span>
                    @elseif(($field['html'] ?? false) === true)
                        {!! $field['value'] !!}
                    @else
                        {{ $field['value'] }}
                    @endif
                </div>
            </div>
        </div>
    @endforeach
</div>
