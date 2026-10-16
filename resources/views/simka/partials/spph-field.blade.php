{{--
    Partial input field yang dipakai ulang pada form konfigurasi SPPH.

    Parameter:
    - $name        : string  Nama & id input.
    - $label       : string  Label input.
    - $type        : string  Tipe input (default "number").
    - $step        : string  Atribut step (default "0.01").
    - $prefix      : string  Teks di depan input (opsional).
    - $suffix      : string  Teks di belakang input (opsional).
    - $currency    : bool    Jika true, input diformat sebagai mata uang Rupiah.
    - $help        : string  Keterangan kecil di bawah input (opsional).
    - $placeholder : string  Placeholder (default "0").
    - $col         : string  Kelas kolom Bootstrap (default "col-md-6 col-xl-4").
--}}
@php
    $type = $type ?? 'number';
    $step = $step ?? '0.01';
    $isCurrency = $currency ?? false;
    $dataType = $isCurrency ? 'currency' : 'percent';
    if ($isCurrency) {
        $type = 'text';
    }
@endphp

<div class="{{ $col ?? 'col-md-6 col-xl-4' }}">
    <label for="{{ $name }}" class="form-label fw-semibold small text-dark">{{ $label }}</label>

    <div class="input-group">
        @isset($prefix)
            <span class="input-group-text">{{ $prefix }}</span>
        @endisset

        <input
            type="{{ $type }}"
            name="{{ $name }}"
            id="{{ $name }}"
            value="{{ old($name, isset($value) ? $value : '') }}"
            class="form-control @if($isCurrency) currency-input @endif @if(isset($errors) && $errors->has($name)) is-invalid @endif"
            @if($isCurrency) data-currency="true" @endif
            data-type="{{ $dataType }}"
            step="{{ $step }}"
            min="0"
            inputmode="decimal"
            placeholder="{{ $placeholder ?? '0' }}"
            required>

        @isset($suffix)
            <span class="input-group-text">{{ $suffix }}</span>
        @endisset

        @if(isset($errors) && $errors->has($name))
            <div class="invalid-feedback">{{ $errors->first($name) }}</div>
        @endif
    </div>

    @isset($help)
        <div class="form-text">{{ $help }}</div>
    @endisset
</div>
