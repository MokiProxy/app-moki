{{--
    Satu langkah dropdown cascading.

    Parameter:
      $name        — nama field, contoh "erkap_business_unit_id"
      $label       — label UI
      $options     — Collection berisi id/code/name/label (boleh kosong)
      $value       — nilai terpilih (old() atau model)
      $hint        — keterangan panjang kode segmen
      $required    — bool
      $col         — kelas kolom bootstrap (default col-md-6)
--}}
@php
    $selectedValue = old($name, $value ?? null);
    $placeholder = $placeholder ?? '— Pilih —';
    $columnClass = $col ?? 'col-md-6';
@endphp
<div class="{{ $columnClass }}">
    <label class="form-label fw-bold" for="{{ $name }}">
        {{ $label }} @if($required ?? false)<span class="text-danger">*</span>@endif
    </label>
    <select
        name="{{ $name }}"
        id="{{ $name }}"
        class="form-select @error($name) is-invalid @enderror"
        data-code-field="1"
        @if($required ?? false) required @endif
    >
        <option value="">{{ $placeholder }}</option>
        @foreach($options ?? [] as $option)
            <option
                value="{{ $option->id ?? $option['id'] }}"
                data-code="{{ $option->code ?? $option['code'] }}"
                @selected((string) ($selectedValue ?? '') === (string) ($option->id ?? $option['id']))
            >{{ $option->label ?? $option['label'] ?? (($option->code ?? $option['code']).' — '.($option->name ?? $option['name'])) }}</option>
        @endforeach
    </select>
    @error($name) <div class="invalid-feedback">{{ $message }}</div> @enderror
    @if(! empty($hint))
        <div class="form-text">{{ $hint }}</div>
    @endif
</div>
