# Planning Implementasi Fitur Validasi Final File

## 1. Overview

### Tujuan
Menambahkan fitur **validasi file** pada halaman File Management (folder final) di modul Dokter, sehingga pegawai dapat menandai file hasil final sudah dicek/divalidasi.

### Alur Sistem
```
Pegbrowse folder final → Lihat daftar file → Centang checkbox "Validated" → Status tersimpan → File ditandai sudah validasi
```

### Problem Saat Ini
- File Management adalah **FTP browser mentah** — file dibaca langsung dari disk `ftp_final`, bukan dari database
- **Tidak ada** tabel atau mekanisme untuk melacak status validasi file
- **Tidak ada** approval/validation workflow di modul Dokter

---

## 2. Analisis Kodebase Saat Ini

### Struktur Database yang Relevan
| Table | Deskripsi | Relevansi |
|-------|-----------|-----------|
| `scan_logs` | Log proses OCR/scanning | Menyimpan `ftp_path` tapi tidak ada status validasi |
| `document_merge_groups` | Group merge dokumen | Status hanya untuk tracking kelengkapan dokumen, bukan validasi manusia |
| `document_merge_group_items` | Item dalam merge group | Relasi ke scan_log |

### File Management Saat Ini
| Komponen | Detail |
|----------|--------|
| **Controller** | `FileManagementController` — membaca file langsung dari FTP disk `ftp_final` |
| **View** | `resources/views/dokter/file-managements/index.blade.php` — tabel list file dengan aksi: Lihat PDF, Download |
| **FTP Path** | Format: `{document_type}/{vendor}/{filename}` atau `FINAL/{vendor}/{filename}` |
| **Data Source** | Tidak ada database — semua dari FTP `Storage::disk('ftp_final')` |

### Cara Kerja File Management Saat Ini
```
1. index() → cek ?path= query parameter
2. Jika path kosong → showRoot() → list direktori root FTP
3. Jika path ada → showFolder() → list subdirektori + file di path tersebut
4. getFiles() → $disk->files($dirPath) → return array [{name, path, size, extension}]
5. View menampilkan tabel dengan aksi: Lihat (PDF only), Download
```

---

## 3. Database Design

### 3.1 Table Baru: `file_validations`

```php
Schema::create('file_validations', function (Blueprint $table) {
    $table->id();

    // Identifikasi file (karena file dari FTP, pakai path sebagai identifier unik)
    $table->string('file_path')->unique()->index(); // FTP path relatif, e.g. "INVOICE/VENDOR_A/INV_001.pdf"
    $table->string('file_name'); // Nama file untuk display
    $table->string('folder_path')->nullable()->index(); // Folder induk, e.g. "INVOICE/VENDOR_A"

    // Status validasi
    $table->boolean('is_validated')->default(false);
    $table->string('validated_by')->nullable(); // employee_id pegawai yang validasi
    $table->timestamp('validated_at')->nullable();

    // Audit
    $table->string('unvalidated_by')->nullable(); // employee_id yang un-validate
    $table->timestamp('unvalidated_at')->nullable();

    $table->timestamps();

    // Index untuk query performa
    $table->index(['folder_path', 'is_validated']);
});
```

### 3.2 Alasan Menggunakan Table Baru (Bukan Kolom di scan_logs)

| Pertimbangan | Penjelasan |
|---|---|
| **File dari FTP bukan dari DB** | File Management membaca langsung dari FTP, tidak melalui scan_logs |
| **File bisa dari merge** | File di `FINAL/` dibuat oleh MergeFlowService, bukan scan_logs |
| **File bisa manual upload** | User bisa upload file langsung ke FTP tanpa melalui sistem |
| **Path sebagai primary key** | Setiap file punya path unik di FTP, cocok sebagai identifier |
| **Audit trail** | Perlu track siapa yang validate dan kapan |

### 3.3 Relasi
- `file_validations.file_path` → tidak ada FK (file dari FTP, bukan DB record)
- `file_validations.validated_by` → `employees.employee_id` (nullable, untuk audit)

---

## 4. Permission & Role

### 4.1 Permission Baru
```php
// Ditambahkan ke RolePermissionSeeder
['name' => 'dokter.file-managements.validate', 'guard_name' => 'web'],
```

### 4.2 Role yang Diberikan Permission

| Role | Permission |
|------|------------|
| `admin` | Semua permission dokter (termasuk validate) |
| `staff` | `dokter.file-managements.view`, `dokter.file-managements.download`, `dokter.file-managements.validate` |

> Catatan: Permission `validate` diberikan ke staff karena yang melakukan validasi adalah pegawai biasa, bukan hanya admin.

---

## 5. Implementation Plan

### Step 1: Database Migration
**File:** `database/migrations/xxxx_xx_xx_create_file_validations_table.php`

Buat migration baru untuk table `file_validations`.

### Step 2: Model
**File:** `app/Models/FileValidation.php`

```php
class FileValidation extends Model
{
    protected $table = 'file_validations';

    protected $fillable = [
        'file_path',
        'file_name',
        'folder_path',
        'is_validated',
        'validated_by',
        'validated_at',
        'unvalidated_by',
        'unvalidated_at',
    ];

    protected $casts = [
        'is_validated' => 'boolean',
        'validated_at' => 'datetime',
        'unvalidated_at' => 'datetime',
    ];

    // Scope: ambil semua file yang sudah/divalidasi
    public function scopeValidated($query) { ... }
    public function scopeUnvalidated($query) { ... }

    // Helper
    public function getValidator(): ?Employee { ... }
}
```

### Step 3: Seeder Permission
**File:** `database/seeders/RolePermissionSeeder.php` (update)

Tambahkan permission baru:
```php
'dokter.file-managements.validate',
```

### Step 4: Routes
**File:** `routes/routers/dokter.php` (update)

```php
// VALIDATION ENDPOINT (AJAX)
Route::post("file-managements/validate", [FileManagementController::class, "validateFile"])
    ->name("file-managements.validate")
    ->middleware('permission:dokter.file-managements.validate');

Route::post("file-managements/unvalidate", [FileManagementController::class, "unvalidateFile"])
    ->name("file-managements.unvalidate")
    ->middleware('permission:dokter.file-managements.validate');
```

### Step 5: Controller - FileManagementController
**File:** `app/Http/Controllers/Dokter/FileManagementController.php` (update)

Modifikasi method `showFolder()`:
```php
protected function showFolder($disk, string $path, string $pageName)
{
    $breadcrumbs = $this->buildBreadcrumbs($path);
    $directories = $this->getSubDirectories($disk, $path);
    $files = $this->getFiles($disk, $path);

    // Ambil status validasi dari database
    $validatedFiles = FileValidation::whereIn('file_path', collect($files)->pluck('path')->toArray())
        ->get()
        ->keyBy('file_path');

    // Gabungkan data validasi ke setiap file
    foreach ($files as &$file) {
        $validation = $validatedFiles->get($file['path']);
        $file['is_validated'] = $validation?->is_validated ?? false;
        $file['validated_by'] = $validation?->validated_by ?? null;
        $file['validated_at'] = $validation?->validated_at ?? null;
    }

    return view('dokter.file-managements.index', compact(
        'pageName', 'breadcrumbs', 'directories', 'files', 'path'
    ));
}
```

Tambahkan method baru:
```php
// AJAX: Validate file
public function validateFile(Request $request)
{
    $request->validate([
        'file_path' => 'required|string',
        'file_name' => 'required|string',
        'folder_path' => 'nullable|string',
    ]);

    $employeeId = auth()->user()->employee_id;

    FileValidation::updateOrCreate(
        ['file_path' => $request->file_path],
        [
            'file_name' => $request->file_name,
            'folder_path' => $request->folder_path,
            'is_validated' => true,
            'validated_by' => $employeeId,
            'validated_at' => now(),
            'unvalidated_by' => null,
            'unvalidated_at' => null,
        ]
    );

    return response()->json([
        'success' => true,
        'message' => 'File berhasil divalidasi.',
    ]);
}

// AJAX: Unvalidate file
public function unvalidateFile(Request $request)
{
    $request->validate([
        'file_path' => 'required|string',
    ]);

    $employeeId = auth()->user()->employee_id;

    $validation = FileValidation::where('file_path', $request->file_path)->first();

    if ($validation) {
        $validation->update([
            'is_validated' => false,
            'unvalidated_by' => $employeeId,
            'unvalidated_at' => now(),
        ]);
    }

    return response()->json([
        'success' => true,
        'message' => 'Validasi file dibatalkan.',
    ]);
}
```

### Step 6: View - File Management Index
**File:** `resources/views/dokter/file-managements/index.blade.php` (update)

#### Perubahan pada Tabel File:

**6.1 Tambah kolom checkbox di header tabel:**
```blade
<th class="text-center" style="width: 60px">
    <input type="checkbox" id="checkAll" class="form-check-input" title="Validated Semua">
</th>
```

**6.2 Tambah kolom checkbox di body tabel (per file):**
```blade
<td class="text-center">
    <input type="checkbox"
           class="form-check-input file-validate-checkbox"
           data-path="{{ $file['path'] }}"
           data-name="{{ $file['name'] }}"
           data-folder="{{ $path ?? '' }}"
           {{ $file['is_validated'] ? 'checked' : '' }}
           title="Validated">
</td>
```

**6.3 Tambah kolom "Validated" dan "Divalidasi Oleh":**
```blade
<td class="text-center">
    @if($file['is_validated'])
        <span class="badge bg-success">
            <i class="mdi mdi-check-circle me-1"></i> Validated
        </span>
    @else
        <span class="badge bg-secondary">Belum</span>
    @endif
</td>
<td class="text-nowrap">
    @if($file['validated_by'])
        <small class="text-muted">
            {{ $file['validated_by'] }}<br>
            {{ $file['validated_at']?->format('d M Y H:i') }}
        </small>
    @else
        <small class="text-muted">-</small>
    @endif
</td>
```

**6.4 Tambah JavaScript AJAX untuk checkbox:**
```javascript
// Toggle validasi via AJAX
$(document).on('change', '.file-validate-checkbox', function() {
    var $checkbox = $(this);
    var filePath = $checkbox.data('path');
    var fileName = $checkbox.data('name');
    var folderPath = $checkbox.data('folder');
    var is_checked = $checkbox.is(':checked');

    var url = is_checked
        ? '{{ route("dokter.file-managements.validate") }}'
        : '{{ route("dokter.file-managements.unvalidate") }}';

    var data = {
        file_path: filePath,
        file_name: fileName,
        folder_path: folderPath,
        _token: '{{ csrf_token() }}'
    };

    $.ajax({
        url: url,
        type: 'POST',
        data: data,
        success: function(response) {
            if (response.success) {
                // Update badge status tanpa reload
                var $row = $checkbox.closest('tr');
                var $statusBadge = $row.find('.validation-status');
                var $validatedInfo = $row.find('.validated-info');

                if (is_checked) {
                    $statusBadge.html('<span class="badge bg-success"><i class="mdi mdi-check-circle me-1"></i> Validated</span>');
                    // Update info validated_by
                } else {
                    $statusBadge.html('<span class="badge bg-secondary">Belum</span>');
                    $validatedInfo.html('<small class="text-muted">-</small>');
                }

                // Toast notification
                showToast(response.message, 'success');
            }
        },
        error: function(xhr) {
            $checkbox.prop('checked', !is_checked);
            showToast('Gagal memproses validasi.', 'error');
        }
    });
});
```

**6.5 Tambahkan statistik validasi di atas tabel:**
```blade
@if(isset($files) && count($files) > 0)
@php
    $validatedCount = collect($files)->where('is_validated', true)->count();
    $totalCount = count($files);
@endphp
<div class="d-flex justify-content-between align-items-center mb-2">
    <h6 class="text-muted text-uppercase fw-bold mb-0">
        <i class="mdi mdi-file-outline me-1"></i> File
        <span class="badge bg-secondary ms-1">{{ $totalCount }}</span>
    </h6>
    <small class="text-muted">
        <i class="mdi mdi-check-circle text-success me-1"></i>
        {{ $validatedCount }}/{{ $totalCount }} divalidasi
    </small>
</div>
@endif
```

### Step 7: Layout/Menu (Opsional)
Tidak perlu perubahan sidebar karena fitur validasi sudah termasuk dalam halaman File Management yang ada.

---

## 6. File yang Perlu Dibuat/Update

### File Baru
| No | File | Deskripsi |
|----|------|-----------|
| 1 | `database/migrations/xxxx_create_file_validations_table.php` | Migration |
| 2 | `app/Models/FileValidation.php` | Model |

### File yang Perlu Diupdate
| No | File | Deskripsi |
|----|------|-----------|
| 1 | `app/Http/Controllers/Dokter/FileManagementController.php` | Tambah method validate/unvalidate + update showFolder |
| 2 | `resources/views/dokter/file-managements/index.blade.php` | Tambah kolom checkbox + badge validasi + JS AJAX |
| 3 | `routes/routers/dokter.php` | Tambah routes validate/unvalidate |
| 4 | `database/seeders/RolePermissionSeeder.php` | Tambah permission `dokter.file-managements.validate` |

---

## 7. UX Flow

### Flow Validasi
```
1. User buka File Management → navigate ke folder
2. Daftar file muncul dengan kolom checkbox "Validated"
3. Centang checkbox → AJAX POST ke /validate → status tersimpan
4. Badge berubah dari "Belum" → "Validated" (hijau)
5. Info "Divalidasi Oleh: [nama] [tanggal]" muncul
6. Bisa un-check → AJAX POST ke /unvalidate → status dibatalkan
```

### Flow Un-Validasi
```
1. User un-check checkbox yang sudah divalidasi
2. AJAX POST ke /unvalidate
3. Badge kembali ke "Belum"
4. Audit trail tersimpan (siapa yang un-validate, kapan)
```

---

## 8. Edge Cases & Considerations

| Case | Penanganan |
|------|------------|
| **File dihapus dari FTP** | Record di `file_validations` tetap ada (audit trail), tapi checkbox tidak muncul |
| **File di-rename di FTP** | Record lama orphan, file baru tanpa validasi |
| **Multiple user validate file sama** | `updateOrCreate` berdasarkan `file_path`, field `validated_by` di-overwrite ke user terakhir |
| **Race condition** | Menggunakan `updateOrCreate` atomic, aman untuk concurrent |
| **Folder FINAL (merged files)** | File merged juga bisa divalidasi karena menggunakan path FTP |
| **Performance** | Query `whereIn` untuk batch load, tidak N+1 |

---

## 9. Testing Checklist

- [ ] Checkbox muncul di kolom tabel file
- [ ] Centang checkbox → status validasi tersimpan ke database
- [ ] Badge berubah dari "Belum" ke "Validated"
- [ ] Un-check checkbox → status validasi dibatalkan
- [ ] Info "Divalidasi Oleh" muncul dengan benar
- [ ] Toast notification muncul setelah validasi
- [ ] Validasi persist setelah refresh halaman
- [ ] Permission `dokter.file-managements.validate` berfungsi
- [ ] User tanpa permission tidak melihat checkbox
- [ ] File di folder berbeda memiliki validasi terpisah
- [ ] Statistik "X/Y divalidasi" akurat

---

## 10. Estimasi Waktu

| No | Task | Estimasi |
|----|------|----------|
| 1 | Database Migration | 0.5 jam |
| 2 | Model & Relations | 0.5 jam |
| 3 | Seeder Permission | 0.25 jam |
| 4 | Routes | 0.25 jam |
| 5 | Controller (validate/unvalidate + showFolder update) | 1.5 jam |
| 6 | View (checkbox + badge + JS AJAX + statistik) | 2 jam |
| 7 | Testing | 1 jam |
| **Total** | | **6 jam** |

---

## 11. Alternatif Approach (Dipertimbangkan tapi Ditolak)

### Alternatif A: Simpan validasi di metadata FTP
- **Ide**: Simpan status validasi sebagai extended attribute di FTP file
- **Masalah**: FTP tidak support custom metadata/attributes secara native
- **Verdict**: Ditolak

### Alternatif B: Tambah kolom di scan_logs
- **Ide**: Tambah `is_validated` ke tabel `scan_logs` yang sudah ada
- **Masalah**: File di `FINAL/` (merged) tidak punya scan_log record; file manual upload juga tidak
- **Verdict**: Ditolak — tidak semua file punya scan_log

### Alternatif C: Gunakan file `.validated` marker di FTP
- **Ide**: Buat file `.validated` di folder yang sudah divalidasi
- **Masalah**: Hanya level folder, bukan per-file; rentan dihapus; tidak ada audit trail
- **Verdict**: Ditolak

### Pilihan Terbaik: Table Baru `file_validations`
- **Kelebihan**: Clean separation, audit trail lengkap, support per-file, tidak tergantung FTP
- **Kekurangan**: Perlu sync jika file dihapus dari FTP (bisa di-handle di view)
