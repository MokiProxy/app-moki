# Planning Implementasi: Data Kelengkapan Perangkat pada Approval Fixed Asset

## Overview

Menambahkan fitur input data **Kelengkapan Perangkat** oleh approver saat melakukan approval pada pengajuan Fixed Asset. Data ini bersifat dinamis (jumlah data tidak menentu) dan terdiri dari 4 field: Uraian, Ada, Tidak Ada, dan Keterangan.

---

## 1. Struktur Database

### 1.1 Tabel Baru: `formit_fixed_asset_device_completions`

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `id` | bigint (PK, auto) | ID unik |
| `fixed_asset_borrowing_id` | bigint (FK) | Relasi ke `formit_fixed_asset_borrowings.id` |
| `uraian` | string | Uraian/perangkat yang dicek |
| `ada` | boolean | Status ada (true/false) |
| `tidak_ada` | boolean | Status tidak ada (true/false) |
| `keterangan` | text (nullable) | Keterangan tambahan |
| `created_at` | timestamp | Waktu pembuatan |
| `updated_at` | timestamp | Waktu update |

**Relasi:**
- `fixed_asset_borrowing()` -> BelongsTo(FixedAssetBorrowing)

**Indexes:**
- `fixed_asset_borrowing_id` (untuk performa query)

---

## 2. Model

### 2.1 Model Baru: `FixedAssetDeviceCompletion`

**File:** `app/Models/FixedAssetDeviceCompletion.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FixedAssetDeviceCompletion extends Model
{
    use HasFactory;

    protected $table = 'formit_fixed_asset_device_completions';

    protected $fillable = [
        'fixed_asset_borrowing_id',
        'uraian',
        'ada',
        'tidak_ada',
        'keterangan',
    ];

    protected $casts = [
        'ada' => 'boolean',
        'tidak_ada' => 'boolean',
    ];

    public function fixedAssetBorrowing()
    {
        return $this->belongsTo(FixedAssetBorrowing::class, 'fixed_asset_borrowing_id');
    }
}
```

### 2.2 Update Model: `FixedAssetBorrowing`

**File:** `app/Models/FixedAssetBorrowing.php`

Tambahkan relasi baru:

```php
public function deviceCompletions()
{
    return $this->hasMany(FixedAssetDeviceCompletion::class, 'fixed_asset_borrowing_id');
}
```

---

## 3. Migration

**File:** `database/migrations/2026_08_07_000001_create_formit_fixed_asset_device_completions_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('formit_fixed_asset_device_completions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fixed_asset_borrowing_id')
                  ->constrained('formit_fixed_asset_borrowings')
                  ->onDelete('cascade');
            $table->string('uraian');
            $table->boolean('ada')->default(false);
            $table->boolean('tidak_ada')->default(false);
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('formit_fixed_asset_device_completions');
    }
};
```

---

## 4. Controller Updates

### 4.1 ApprovalController Updates

**File:** `app/Http/Controllers/FormIT/ApprovalController.php`

#### a. Update Method `fixedAssetShow()`

Tambahkan eager loading untuk data device_completions:

```php
public function fixedAssetShow($id)
{
    $borrowing = FixedAssetBorrowing::with(['pemohon', 'approver', 'deviceCompletions'])->findOrFail($id);
    // ... existing code
}
```

#### b. Update Method `fixedAssetProcess()`

Tambahkan validasi dan penyimpanan data kelengkapan perangkat saat approve:

```php
public function fixedAssetProcess(Request $request, $id)
{
    // ... existing validation rules

    // Tambahkan validasi untuk device_completions
    $validated = $request->validate([
        'action' => 'required|in:approve,reject',
        'notes' => 'nullable|string',
        // Data penyerahan (wajib saat approve)
        'penyerahkan_name' => 'required_if:action,approve|string',
        'penyerahkan_jabatan' => 'required_if:action,approve|string',
        'penyerahkan_departemen' => 'required_if:action,approve|string',
        'penyerahkan_area' => 'required_if:action,approve|string',
        // Data kelengkapan perangkat
        'device_completions' => 'required_if:action,approve|array',
        'device_completions.*.uraian' => 'required|string',
        'device_completions.*.ada' => 'boolean',
        'device_completions.*.tidak_ada' => 'boolean',
        'device_completions.*.keterangan' => 'nullable|string',
        // Alasan penolakan (wajib saat reject)
        'rejection_reason' => 'required_if:action,reject|string',
    ]);

    // ... inside approve logic, setelah update status

    // Simpan data kelengkapan perangkat
    if ($request->action === 'approve' && isset($validated['device_completions'])) {
        foreach ($validated['device_completions'] as $device) {
            $borrowing->deviceCompletions()->create([
                'uraian' => $device['uraian'],
                'ada' => $device['ada'] ?? false,
                'tidak_ada' => $device['tidak_ada'] ?? false,
                'keterangan' => $device['keterangan'] ?? null,
            ]);
        }
    }
}
```

---

## 5. View Updates

### 5.1 Approval Show (Form Input)

**File:** `resources/views/form-it/approval/fixed-asset-show.blade.php`

Tambahkan section "Kelengkapan Perangkat" di dalam card "Aksi Approval" (sebelum tombol submit):

```html
<!-- Card: Kelengkapan Perangkat -->
<div class="card mb-3" id="deviceCompletionSection">
    <div class="card-header">
        <h5 class="card-title">Kelengkapan Perangkat</h5>
    </div>
    <div class="card-body">
        <div id="device-completion-list">
            <!-- Dynamic rows will be added here -->
        </div>
        <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="addDeviceCompletion">
            <i class="ri-add-line"></i> Tambah Item
        </button>
    </div>
</div>
```

**JavaScript untuk dynamic rows:**

```javascript
document.getElementById('addDeviceCompletion').addEventListener('click', function() {
    const list = document.getElementById('device-completion-list');
    const rowIndex = list.children.length;

    const row = document.createElement('div');
    row.className = 'row mb-2 device-completion-row';
    row.innerHTML = `
        <div class="col-md-4">
            <input type="text" name="device_completions[${rowIndex}][uraian]" 
                   class="form-control" placeholder="Uraian" required>
        </div>
        <div class="col-md-2">
            <div class="form-check">
                <input type="checkbox" name="device_completions[${rowIndex}][ada]" 
                       class="form-check-input" value="1">
                <label class="form-check-label">Ada</label>
            </div>
        </div>
        <div class="col-md-2">
            <div class="form-check">
                <input type="checkbox" name="device_completions[${rowIndex}][tidak_ada]" 
                       class="form-check-input" value="1">
                <label class="form-check-label">Tidak Ada</label>
            </div>
        </div>
        <div class="col-md-3">
            <input type="text" name="device_completions[${rowIndex}][keterangan]" 
                   class="form-control" placeholder="Keterangan">
        </div>
        <div class="col-md-1">
            <button type="button" class="btn btn-sm btn-outline-danger remove-device">
                <i class="ri-delete-bin-line"></i>
            </button>
        </div>
    `;
    list.appendChild(row);
});

// Event delegation for remove button
document.getElementById('device-completion-list').addEventListener('click', function(e) {
    if (e.target.closest('.remove-device')) {
        e.target.closest('.device-completion-row').remove();
        reindexRows();
    }
});

function reindexRows() {
    const rows = document.querySelectorAll('.device-completion-row');
    rows.forEach((row, index) => {
        row.querySelector('[name*="[uraian]"]').name = `device_completions[${index}][uraian]`;
        row.querySelector('[name*="[ada]"]').name = `device_completions[${index}][ada]`;
        row.querySelector('[name*="[idak_ada]"]').name = `device_completions[${index}][tidak_ada]`;
        row.querySelector('[name*="[eterangan]"]').name = `device_completions[${index}][keterangan]`;
    });
}
```

### 5.2 Pemohon Show (Detail)

**File:** `resources/views/form-it/forms/fixed-asset-show.blade.php`

Tambahkan section untuk menampilkan data kelengkapan perangkat (jika status approved):

```html
@if($borrowing->status === 'approved' && $borrowing->deviceCompletions->count() > 0)
<div class="card mb-3">
    <div class="card-header">
        <h5 class="card-title">Kelengkapan Perangkat</h5>
    </div>
    <div class="card-body">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Uraian</th>
                    <th>Ada</th>
                    <th>Tidak Ada</th>
                    <th>Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @foreach($borrowing->deviceCompletions as $index => $device)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $device->uraian }}</td>
                    <td>{{ $device->ada ? '✓' : '-' }}</td>
                    <td>{{ $device->tidak_ada ? '✓' : '-' }}</td>
                    <td>{{ $device->keterangan ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
```

### 5.3 PDF Template Updates

**File:** `resources/views/form-it/templates/fixed-asset.blade.php`

Update tabel kelengkapan perangkat untuk menampilkan data dari database:

```html
<!-- Tabel Kelengkapan Perangkat -->
<table class="table table-bordered" style="margin-top: 20px;">
    <thead>
        <tr>
            <th>No</th>
            <th>Uraian</th>
            <th>Ada</th>
            <th>Tidak Ada</th>
            <th>Keterangan</th>
        </tr>
    </thead>
    <tbody>
        @if(isset($borrowing->deviceCompletions) && $borrowing->deviceCompletions->count() > 0)
            @foreach($borrowing->deviceCompletions as $index => $device)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $device->uraian }}</td>
                <td>{{ $device->ada ? '✓' : '' }}</td>
                <td>{{ $device->tidak_ada ? '✓' : '' }}</td>
                <td>{{ $device->keterangan ?? '' }}</td>
            </tr>
            @endforeach
        @else
            @for($i = 0; $i < 5; $i++)
            <tr>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
            </tr>
            @endfor
        @endif
    </tbody>
</table>
```

---

## 6. Validation Rules Summary

| Field | Type | Required When | Notes |
|-------|------|---------------|-------|
| `device_completions` | array | approve | Wajib diisi saat approve |
| `device_completions.*.uraian` | string | approve | Wajib |
| `device_completions.*.ada` | boolean | approve | Default: false |
| `device_completions.*.tidak_ada` | boolean | approve | Default: false |
| `device_completions.*.keterangan` | string | approve | Opsional |

---

## 7. File Changes Summary

| No | File | Action | Description |
|----|------|--------|-------------|
| 1 | `database/migrations/2026_08_07_000001_create_formit_fixed_asset_device_completions_table.php` | CREATE | Migration baru |
| 2 | `app/Models/FixedAssetDeviceCompletion.php` | CREATE | Model baru |
| 3 | `app/Models/FixedAssetBorrowing.php` | UPDATE | Tambah relasi `deviceCompletions()` |
| 4 | `app/Http/Controllers/FormIT/ApprovalController.php` | UPDATE | Update `fixedAssetShow()` dan `fixedAssetProcess()` |
| 5 | `resources/views/form-it/approval/fixed-asset-show.blade.php` | UPDATE | Tambah form input dynamic rows |
| 6 | `resources/views/form-it/forms/fixed-asset-show.blade.php` | UPDATE | Tampilkan data kelengkapan perangkat |
| 7 | `resources/views/form-it/templates/fixed-asset.blade.php` | UPDATE | Tampilkan data di PDF |

---

## 8. Implementation Steps

1. **Buat Migration** - Buat migration baru untuk tabel `formit_fixed_asset_device_completions`
2. **Buat Model** - Buat model `FixedAssetDeviceCompletion` dengan relasi
3. **Update Model FixedAssetBorrowing** - Tambahkan relasi `hasMany` ke `deviceCompletions`
4. **Run Migration** - Jalankan `php artisan migrate`
5. **Update ApprovalController** - Tambahkan validasi dan logic penyimpanan data kelengkapan perangkat
6. **Update View Approval Show** - Tambahkan form dynamic rows untuk input data
7. **Update View Pemohon Show** - Tampilkan data kelengkapan perangkat jika sudah approved
8. **Update PDF Template** - Tampilkan data dari database
9. **Testing** - Test seluruh alur dari approve hingga tampil di PDF

---

## 9. Notes

- Data kelengkapan perangkat hanya bisa diinput oleh approver saat melakukan approval
- Jumlah item kelengkapan perangkat tidak ditentukan (dinamis)
- Checkbox "Ada" dan "Tidak Ada" saling exclusive (gunakan JavaScript untuk memastikan)
- Data kelengkapan perangkat akan tersimpan permanen setelah approval
- PDF akan menampilkan data dari database jika ada, atau baris kosong jika belum diinput
