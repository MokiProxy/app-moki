{{--
    Form bersama untuk create & edit Manajemen Area (segmen c).

    Lokasi diambil lewat dropdown cascading dari API `erkap.coa-options`;
    dropdown Bisnis Unit (a) di-render server-side karena hanya handful.

    Parameter:
      $businessUnits — Collection dropdown segmen a
      $divisions     — Collection untuk pemetaan ke org chart
      $locations     — Collection Lokasi milik unit terpilih (khusus form edit)
      $managementArea— model pada edit, null pada create
      $action        — URL form
      $method        — "POST" atau "PUT"
--}}
@php
    $selectedUnit = old('erkap_business_unit_id', $managementArea->erkap_business_unit_id ?? null);
    $selectedLocation = old('erkap_location_id', $managementArea->erkap_location_id ?? null);
    $selectedDivision = old('division_id', $managementArea->division_id ?? null);
@endphp
<form action="{{ $action }}" method="POST">
    @csrf
    @if(($method ?? 'POST') === 'PUT')
        @method('PUT')
    @endif

    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label fw-bold" for="erkap_business_unit_id">Bisnis Unit (a) <span class="text-danger">*</span></label>
            <select name="erkap_business_unit_id" id="erkap_business_unit_id" class="form-select @error('erkap_business_unit_id') is-invalid @enderror" required>
                <option value="">— Pilih —</option>
                @foreach($businessUnits as $unit)
                    <option value="{{ $unit->id }}" data-code="{{ $unit->code }}" @selected((string) $selectedUnit === (string) $unit->id)>
                        {{ $unit->code }} — {{ $unit->name }}
                    </option>
                @endforeach
            </select>
            @error('erkap_business_unit_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        @include('erkap.partials.cascade-select', [
            'name' => 'erkap_location_id',
            'label' => 'Lokasi (b)',
            'required' => true,
            'options' => $locations ?? [],
            'value' => $selectedLocation,
            'placeholder' => '— Pilih Bisnis Unit dulu —',
            'hint' => 'Hanya Lokasi milik Bisnis Unit terpilih.',
        ])

        <div class="col-md-3">
            <label class="form-label fw-bold" for="code">Kode (c) <span class="text-danger">*</span></label>
            <input type="text" name="code" id="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code', $managementArea->code ?? '') }}" maxlength="5" required autofocus>
            @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
            <div class="form-text">5 digit, contoh <code>20200</code>.</div>
        </div>

        <div class="col-md-3">
            <label class="form-label fw-bold" for="sort_order">Urutan</label>
            <input type="number" name="sort_order" id="sort_order" min="0" class="form-control @error('sort_order') is-invalid @enderror" value="{{ old('sort_order', $managementArea->sort_order ?? 0) }}">
            @error('sort_order') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-4">
            <label class="form-label fw-bold" for="name">Nama <span class="text-danger">*</span></label>
            <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $managementArea->name ?? '') }}" maxlength="150" required>
            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-8">
            <label class="form-label fw-bold" for="division_id">Divisi (pemetaan org chart)</label>
            <select name="division_id" id="division_id" class="form-select @error('division_id') is-invalid @enderror">
                <option value="">— Tidak dipetakan —</option>
                @foreach($divisions as $division)
                    <option value="{{ $division->id }}" @selected((string) $selectedDivision === (string) $division->id)>{{ $division->name }}</option>
                @endforeach
            </select>
            @error('division_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            <div class="form-text">Dipakai untuk scoping akses &mdash; pengguna dibatasi pada Pusat Biaya divisinya.</div>
        </div>

        <div class="col-md-4">
            <label class="form-label fw-bold" for="is_active">Status</label>
            <select name="is_active" id="is_active" class="form-select @error('is_active') is-invalid @enderror">
                <option value="1" @selected(old('is_active', $managementArea->is_active ?? true) == 1)>Aktif</option>
                <option value="0" @selected(old('is_active', $managementArea->is_active ?? true) == 0)>Nonaktif</option>
            </select>
            @error('is_active') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-12">
            <label class="form-label fw-bold" for="description">Deskripsi</label>
            <textarea name="description" id="description" rows="2" maxlength="255" class="form-control @error('description') is-invalid @enderror">{{ old('description', $managementArea->description ?? '') }}</textarea>
            @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    </div>

    <div class="mt-4 border-top pt-3">
        <button type="submit" class="btn btn-primary">
            <i class="mdi mdi-content-save me-1"></i> Simpan
        </button>
        <a href="{{ route('erkap.management-areas.index') }}" class="btn btn-secondary">Batal</a>
    </div>
</form>
