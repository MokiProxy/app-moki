{{--
    Form bersama untuk create & edit Aktivitas (segmen d).

    Rantai a → b → c diambil penuh lewat dropdown cascading; segmen Lokasi
    dan Bisnis Unit tidak diinput manual karena diwarisi dari Manajemen Area
    oleh `Activity::syncInheritedSegments()`.

    Parameter:
      $businessUnits  — Collection dropdown segmen a
      $locations      — Collection Lokasi milik unit (khusus form edit)
      $managementAreas— Collection Area milik lokasi (khusus form edit)
      $activity       — model pada edit, null pada create
      $action         — URL form
      $method         — "POST" atau "PUT"
--}}
@php
    $selectedUnit = old('erkap_business_unit_id', $activity->erkap_business_unit_id ?? null);
    $selectedLocation = old('erkap_location_id', $activity->erkap_location_id ?? null);
    $selectedArea = old('erkap_management_area_id', $activity->erkap_management_area_id ?? null);
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
            'options' => $locations ?? [],
            'value' => $selectedLocation,
            'placeholder' => '— Pilih Bisnis Unit dulu —',
            'hint' => 'Berfungsi sebagai filter; nilai final diwarisi dari Manajemen Area.',
        ])

        @include('erkap.partials.cascade-select', [
            'name' => 'erkap_management_area_id',
            'label' => 'Manajemen Area (c)',
            'required' => true,
            'options' => $managementAreas ?? [],
            'value' => $selectedArea,
            'placeholder' => '— Pilih Lokasi dulu —',
        ])

        <div class="col-md-3">
            <label class="form-label fw-bold" for="code">Kode (d) <span class="text-danger">*</span></label>
            <input type="text" name="code" id="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code', $activity->code ?? '') }}" maxlength="3" required autofocus>
            @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
            <div class="form-text">3 digit, contoh <code>202</code>.</div>
        </div>

        <div class="col-md-3">
            <label class="form-label fw-bold" for="sort_order">Urutan</label>
            <input type="number" name="sort_order" id="sort_order" min="0" class="form-control @error('sort_order') is-invalid @enderror" value="{{ old('sort_order', $activity->sort_order ?? 0) }}">
            @error('sort_order') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-4">
            <label class="form-label fw-bold" for="name">Nama <span class="text-danger">*</span></label>
            <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $activity->name ?? '') }}" maxlength="150" required>
            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-4">
            <label class="form-label fw-bold" for="is_swakelola">Biaya Swakelola</label>
            <select name="is_swakelola" id="is_swakelola" class="form-select @error('is_swakelola') is-invalid @enderror">
                <option value="1" @selected(old('is_swakelola', $activity->is_swakelola ?? false) == 1)>Ya</option>
                <option value="0" @selected(old('is_swakelola', $activity->is_swakelola ?? false) == 0)>Tidak</option>
            </select>
            @error('is_swakelola') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-4">
            <label class="form-label fw-bold" for="is_active">Status</label>
            <select name="is_active" id="is_active" class="form-select @error('is_active') is-invalid @enderror">
                <option value="1" @selected(old('is_active', $activity->is_active ?? true) == 1)>Aktif</option>
                <option value="0" @selected(old('is_active', $activity->is_active ?? true) == 0)>Nonaktif</option>
            </select>
            @error('is_active') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-12">
            <label class="form-label fw-bold">Pratinjau Kode Pusat Biaya</label>
            <div class="d-flex align-items-center gap-2">
                <code id="erkap_code_preview" class="fs-5 px-2 py-1 bg-light border rounded">—</code>
                <span class="form-text mb-0">11 karakter = <code>a</code> (1) + <code>b</code> (2) + <code>c</code> (5) + <code>d</code> (3), dirangkai otomatis oleh sistem.</span>
            </div>
        </div>

        <div class="col-md-12">
            <label class="form-label fw-bold" for="description">Deskripsi</label>
            <textarea name="description" id="description" rows="2" maxlength="255" class="form-control @error('description') is-invalid @enderror">{{ old('description', $activity->description ?? '') }}</textarea>
            @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    </div>

    <div class="mt-4 border-top pt-3">
        <button type="submit" class="btn btn-primary">
            <i class="mdi mdi-content-save me-1"></i> Simpan
        </button>
        <a href="{{ route('erkap.activities.index') }}" class="btn btn-secondary">Batal</a>
    </div>
</form>
