{{--
    Form bersama untuk create & edit Pusat Biaya (segmen a..d).

    Rantai a â†’ b â†’ c â†’ d diambil lewat dropdown cascading. Kode `code` tidak
    diinput manual: sistem menyusunnya dari segmen terpilih, dan nilai final
    a..c diwarisi dari Aktivitas.

    Parameter:
      $businessUnits   â€” Collection dropdown segmen a
      $locations       â€” Collection Lokasi milik unit (khusus form edit)
      $managementAreas â€” Collection Area milik lokasi (khusus form edit)
      $divisions       â€” Collection divisi
      $costCenter      â€” model pada edit, null pada create
      $action          â€” URL form
      $method          â€” "POST" atau "PUT"
--}}
@php
    $selectedUnit = old('erkap_business_unit_id', $costCenter->erkap_business_unit_id ?? null);
    $selectedLocation = old('erkap_location_id', $costCenter->erkap_location_id ?? null);
    $selectedArea = old('erkap_management_area_id', $costCenter->erkap_management_area_id ?? null);
    $selectedActivity = old('erkap_activity_id', $costCenter->erkap_activity_id ?? null);
    $selectedDivision = old('division_id', $costCenter->division_id ?? null);
    $isCentralized = old('is_centralized', $costCenter->is_centralized ?? false);
    $coordinatingDivision = old('coordinating_division_id', $costCenter->coordinating_division_id ?? null);
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
                <option value="">â€” Pilih â€”</option>
                @foreach($businessUnits as $unit)
                    <option value="{{ $unit->id }}" data-code="{{ $unit->code }}" @selected((string) $selectedUnit === (string) $unit->id)>
                        {{ $unit->code }} â€” {{ $unit->name }}
                    </option>
                @endforeach
            </select>
            @error('erkap_business_unit_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        @include('erkap.partials.cascade-select', [
            'name' => 'erkap_location_id',
            'label' => 'Lokasi (b)',
            'col' => 'col-md-4',
            'options' => $locations ?? [],
            'value' => $selectedLocation,
            'placeholder' => 'â€” Pilih Bisnis Unit dulu â€”',
        ])

        @include('erkap.partials.cascade-select', [
            'name' => 'erkap_management_area_id',
            'label' => 'Manajemen Area (c)',
            'required' => true,
            'col' => 'col-md-4',
            'options' => $managementAreas ?? [],
            'value' => $selectedArea,
            'placeholder' => 'â€” Pilih Lokasi dulu â€”',
        ])

        @include('erkap.partials.cascade-select', [
            'name' => 'erkap_activity_id',
            'label' => 'Aktivitas (d)',
            'required' => true,
            'col' => 'col-md-4',
            'options' => [],
            'value' => $selectedActivity,
            'placeholder' => 'â€” Pilih Manajemen Area dulu â€”',
            'hint' => 'Menentukan segmen a..c secara final.',
        ])

        <div class="col-md-12">
            <label class="form-label fw-bold">Kode Pusat Biaya</label>
            <div class="d-flex align-items-center gap-2">
                <code id="erkap_code_preview" class="fs-5 px-2 py-1 bg-light border rounded">â€”</code>
                <span class="form-text mb-0">
                    11 karakter = <code>a</code> (1) + <code>b</code> (2) + <code>c</code> (5) + <code>d</code> (3).
                    Disusun otomatis oleh sistem; tidak dapat diedit manual.
                </span>
            </div>
            @error('code') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-6">
            <label class="form-label fw-bold" for="name">Nama <span class="text-danger">*</span></label>
            <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $costCenter->name ?? '') }}" maxlength="255" required>
            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-6">
            <label class="form-label fw-bold" for="owner">Pemilik <span class="text-danger">*</span></label>
            <input type="text" name="owner" id="owner" class="form-control @error('owner') is-invalid @enderror" value="{{ old('owner', $costCenter->owner ?? '') }}" maxlength="255" required>
            @error('owner') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-6">
            <label class="form-label fw-bold" for="division_id">Divisi <span class="text-danger">*</span></label>
            <select name="division_id" id="division_id" class="form-select @error('division_id') is-invalid @enderror" required>
                <option value="">â€” Pilih Divisi</option>
                @foreach($divisions as $division)
                    <option value="{{ $division->id }}" @selected((string) $selectedDivision === (string) $division->id)>
                        {{ $division->name }}
                    </option>
                @endforeach
            </select>
            @error('division_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            <div class="form-text">Diturunkan dari Manajemen Area, tetap dapat disesuaikan.</div>
        </div>

        <div class="col-md-6 d-flex align-items-end">
            <div class="form-check form-switch form-check-inline">
                <input type="checkbox" name="is_centralized" value="1" class="form-check-input @error('is_centralized') is-invalid @enderror" id="is_centralized" @checked($isCentralized)>
                <label class="form-check-label fw-bold" for="is_centralized">Biaya Tersentralisasi</label>
            </div>
            <div class="form-text ms-2 mb-0">Status Swakelola mengikuti Aktivitas (d).</div>
        </div>

        <div class="col-md-6" id="coordinator-field" style="{{ $isCentralized ? '' : 'display: none;' }}">
            <label class="form-label fw-bold" for="coordinating_division_id">Departemen Koordinator <span class="text-danger">*</span></label>
            <select name="coordinating_division_id" id="coordinating_division_id" class="form-select @error('coordinating_division_id') is-invalid @enderror">
                <option value="">â€” Pilih Departemen Koordinator</option>
                @foreach($divisions as $division)
                    <option value="{{ $division->id }}" @selected((string) $coordinatingDivision === (string) $division->id)>
                        {{ $division->name }}
                    </option>
                @endforeach
            </select>
            @error('coordinating_division_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    </div>

    <div class="mt-4 border-top pt-3">
        <button type="submit" class="btn btn-primary">
            <i class="mdi mdi-content-save me-1"></i> {{ ($method ?? 'POST') === 'PUT' ? 'Update' : 'Simpan' }}
        </button>
        <a href="{{ route('erkap.cost-centers.index') }}" class="btn btn-secondary">Batal</a>
    </div>
</form>
