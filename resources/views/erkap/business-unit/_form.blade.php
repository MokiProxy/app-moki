{{--
    Form bersama untuk create & edit Bisnis Unit (segmen a).

    Parameter:
      $businessUnit — model pada edit, null pada create
      $action       — URL form
      $method       — "POST" atau "PUT"
--}}
<form action="{{ $action }}" method="POST">
    @csrf
    @if(($method ?? 'POST') === 'PUT')
        @method('PUT')
    @endif

    <div class="row g-3">
        <div class="col-md-3">
            <label class="form-label fw-bold" for="code">Kode (a) <span class="text-danger">*</span></label>
            <input
                type="text"
                name="code"
                id="code"
                class="form-control text-uppercase @error('code') is-invalid @enderror"
                value="{{ old('code', $businessUnit->code ?? '') }}"
                maxlength="1"
                required
                autofocus
            >
            @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
            <div class="form-text">Tepat 1 karakter, contoh <code>A</code>.</div>
        </div>

        <div class="col-md-9">
            <label class="form-label fw-bold" for="name">Nama <span class="text-danger">*</span></label>
            <input
                type="text"
                name="name"
                id="name"
                class="form-control @error('name') is-invalid @enderror"
                value="{{ old('name', $businessUnit->name ?? '') }}"
                maxlength="100"
                required
            >
            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-12">
            <label class="form-label fw-bold" for="description">Deskripsi</label>
            <textarea name="description" id="description" rows="2" maxlength="255" class="form-control @error('description') is-invalid @enderror">{{ old('description', $businessUnit->description ?? '') }}</textarea>
            @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-4">
            <label class="form-label fw-bold" for="sort_order">Urutan</label>
            <input type="number" name="sort_order" id="sort_order" min="0" class="form-control @error('sort_order') is-invalid @enderror" value="{{ old('sort_order', $businessUnit->sort_order ?? 0) }}">
            @error('sort_order') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-4">
            <label class="form-label fw-bold" for="is_active">Status</label>
            <select name="is_active" id="is_active" class="form-select @error('is_active') is-invalid @enderror">
                <option value="1" @selected(old('is_active', $businessUnit->is_active ?? true) == 1)>Aktif</option>
                <option value="0" @selected(old('is_active', $businessUnit->is_active ?? true) == 0)>Nonaktif</option>
            </select>
            @error('is_active') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    </div>

    <div class="mt-4 border-top pt-3">
        <button type="submit" class="btn btn-primary">
            <i class="mdi mdi-content-save me-1"></i> Simpan
        </button>
        <a href="{{ route('erkap.business-units.index') }}" class="btn btn-secondary">Batal</a>
    </div>
</form>
