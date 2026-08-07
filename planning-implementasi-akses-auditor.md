# Planning Implementasi Akses Auditor

## 1. Overview

### Tujuan
Membuat sistem **shared link** sehingga perusahaan dapat mengirimkan akses File Management kepada auditor **tanpa login**. Auditor membuka link dan melihat file-file yang difilter berdasarkan tahun tertentu.

### Alur Sistem
```
Admin buka menu "Akses Auditor" → Buat link baru → Pilih tahun akses → Copy link
→ Kirim link ke auditor
→ Auditor buka link (tanpa login) → Lihat daftar file difilter tahun
```

### Problem Saat Ini
- File Management bisa diakses semua user dengan permission `dokter.file-managements.view`
- Tidak ada mekanisme share link ke user eksternal (auditor tanpa login)
- Tidak ada filtering berdasarkan tahun file

---

## 2. Analisis Kodebase Saat Ini

### Struktur FTP Path
```
/ftp_final/
  {DOCUMENT_TYPE}/           ← INVOICE, PO, dll
    {VENDOR}/                ← PT_MAJU, PT_SEJAHTERA
      {VENDOR}_{NUMBER}.pdf  ← file individual
  FINAL/                     ← file hasil merge
    {VENDOR}/
      FINAL_{VENDOR}_{NUMBERS}.pdf
```

### Sumber Data Tahun
Field `scan_logs.tanggal` berisi tanggal dari dokumen hasil OCR, format: `"01 Apr 26"`

**Ekstraksi tahun:**
```
"01 Apr 26" → ambil 2 digit terakhir "26" → tahun 2026
"15 Mar 25" → ambil 2 digit terakhir "25" → tahun 2025
```

**Logika konversi:**
```php
// Ambil 2 digit terakhir dari tanggal
$yearSuffix = substr($tanggal, -2); // "26"
$fullYear = 2000 + (int) $yearSuffix; // 2026
```

### Cara Kerja Filtering
```
1. User buka link /auditor/{token}
2. Ambil allowed_years dari database (berdasarkan token)
3. Query scan_logs → whereIn('ftp_path', allFtpPaths) → where tahun tanggal ∈ allowed_years
4. Hasil: array ftp_path yang diizinkan
5. File Management hanya tampilkan file yang ftp_path-nya ada di array tersebut
```

### File yang Relevan
| File | Path | Keterangan |
|------|------|------------|
| FileManagementController | `app/Http/Controllers/Dokter/FileManagementController.php` | Controller utama File Management |
| View index | `resources/views/dokter/file-managements/index.blade.php` | View File Management |
| Sidebar | `resources/views/layouts/partials/dokter/app-sidebar.blade.php` | Menu sidebar |
| Routes | `routes/routers/dokter.php` | Route Dokter |
| Web routes | `routes/web.php` | Route utama (untuk public routes) |
| ScanLog model | `app/Models/ScanLog.php` | Model scan_logs |
| RolePermissionSeeder | `database/seeders/RolePermissionSeeder.php` | Seeder permission |

---

## 3. Database Design

### 3.1 Table Baru: `auditor_access_links`

```php
Schema::create('auditor_access_links', function (Blueprint $table) {
    $table->id();
    $table->string('name');                          // Nama link, e.g. "Akses Auditor 2026"
    $table->string('token', 64)->unique()->index();  // Token unik untuk URL
    $table->string('description')->nullable();        // Keterangan
    $table->json('allowed_years');                    // Tahun yang diizinkan, e.g. [2024, 2025, 2026]
    $table->boolean('is_active')->default(true);      // Aktif/nonaktif
    $table->string('created_by');                     // employee_id pembuat
    $table->timestamp('last_accessed_at')->nullable(); // Terakhir diakses
    $table->timestamps();

    $table->index('is_active');
});
```

### 3.2 Alasan Desain

| Pertimbangan | Penjelasan |
|---|---|
| **Token-based** | Auditor tidak login, cukup buka URL dengan token unik |
| **JSON allowed_years** | Flexible, admin bisa pilih tahun mana saja |
| **is_active** | Bisa nonaktifkan link tanpa hapus |
| **created_by** | Audit trail siapa yang membuat link |
| **last_accessed_at** | Tracking kapan link terakhir diakses |

### 3.3 Relasi
- `auditor_access_links.created_by` → `employees.employee_id` (nullable, untuk audit)
- Tidak ada FK ke users karena auditor bukan user sistem

---

## 4. Permission & Role

### 4.1 Permission Baru
```php
// Ditambahkan ke RolePermissionSeeder
['name' => 'dokter.auditor-access.manage', 'guard_name' => 'web'],
```

### 4.2 Role yang Diberikan Permission

| Role | Permission |
|------|------------|
| `admin` | `dokter.auditor-access.manage` |
| `staff` | Tidak diberikan (hanya admin yang bisa buat link) |

---

## 5. Implementation Plan

### Step 1: Database Migration
**File:** `database/migrations/2026_08_07_000002_create_auditor_access_links_table.php`

Bat migration untuk table `auditor_access_links`.

### Step 2: Model
**File:** `app/Models/AuditorAccessLink.php`

```php
class AuditorAccessLink extends Model
{
    protected $table = 'auditor_access_links';

    protected $fillable = [
        'name', 'token', 'description', 'allowed_years',
        'is_active', 'created_by', 'last_accessed_at',
    ];

    protected $casts = [
        'allowed_years' => 'array',
        'is_active' => 'boolean',
        'last_accessed_at' => 'datetime',
    ];

    // Generate unique token
    public static function generateToken(): string { ... }

    // Check if year is allowed
    public function isYearAllowed(int $year): bool { ... }

    // Get allowed years as collection
    public function getAllowedYearsAttribute(): Collection { ... }
}
```

### Step 3: Seeder Permission
**File:** `database/seeders/RolePermissionSeeder.php` (update)

Tambahkan permission:
```php
'dokter.auditor-access.manage',
```

### Step 4: Routes

#### 4a. Admin Routes (Authenticated)
**File:** `routes/routers/dokter.php` (update)

```php
Route::prefix('auditor-access')->name('auditor-access.')->middleware('permission:dokter.auditor-access.manage')->group(function () {
    Route::get('/', [AuditorAccessController::class, 'index'])->name('index');
    Route::post('/', [AuditorAccessController::class, 'store'])->name('store');
    Route::get('/{auditorAccessLink}', [AuditorAccessController::class, 'show'])->name('show');
    Route::put('/{auditorAccessLink}', [AuditorAccessController::class, 'update'])->name('update');
    Route::delete('/{auditorAccessLink}', [AuditorAccessController::class, 'destroy'])->name('destroy');
    Route::post('/{auditorAccessLink}/toggle', [AuditorAccessController::class, 'toggle'])->name('toggle');
    Route::get('/{auditorAccessLink}/copy-link', [AuditorAccessController::class, 'copyLink'])->name('copy-link');
});
```

#### 4b. Public Routes (No Auth)
**File:** `routes/web.php` (update)

```php
// --- AUDITOR PUBLIC ACCESS ---
Route::get('/auditor/{token}', [AuditorFileController::class, 'index'])->name('auditor.access');
Route::get('/auditor/{token}/view', [AuditorFileController::class, 'view'])->name('auditor.view');
Route::get('/auditor/{token}/download', [AuditorFileController::class, 'download'])->name('auditor.download');
```

### Step 5: Controller - AuditorAccessController (Admin Management)
**File:** `app/Http/Controllers/Dokter/AuditorAccessController.php`

```php
class AuditorAccessController extends Controller
{
    // List semua link
    public function index() { ... }

    // Buat link baru
    public function store(Request $request) {
        // Validate: name, allowed_years (array of integers)
        // Generate token
        // Save to database
        // Return with full URL
    }

    // Detail link
    public function show(AuditorAccessLink $auditorAccessLink) { ... }

    // Update link
    public function update(Request $request, AuditorAccessLink $auditorAccessLink) { ... }

    // Hapus link
    public function destroy(AuditorAccessLink $auditorAccessLink) { ... }

    // Toggle active/inactive
    public function toggle(AuditorAccessLink $auditorAccessLink) { ... }

    // Get copy-able link
    public function copyLink(AuditorAccessLink $auditorAccessLink) {
        return response()->json([
            'link' => url("/auditor/{$auditorAccessLink->token}"),
        ]);
    }
}
```

### Step 6: Controller - AuditorFileController (Public Access)
**File:** `app/Http/Controllers/Dokter/AuditorFileController.php`

```php
class AuditorFileController extends Controller
{
    public function index(Request $request, string $token)
    {
        // 1. Validate token → find AuditorAccessLink
        // 2. Check is_active
        // 3. Update last_accessed_at
        // 4. Get allowed_years
        // 5. Get allowed ftp_paths from scan_logs
        // 6. Browse FTP with year filter
        // 7. Return view (tanpa sidebar login, layout khusus auditor)
    }

    public function view(Request $request, string $token)
    {
        // Same token validation
        // Stream PDF if path is in allowed paths
    }

    public function download(Request $request, string $token)
    {
        // Same token validation
        // Download file if path is in allowed paths
    }

    // Helper: Get allowed FTP paths based on years
    protected function getAllowedFtpPaths(array $allowedYears): array
    {
        // Query scan_logs
        // For each year, extract from tanggal field
        // Return array of ftp_path that match allowed years
    }

    // Helper: Extract year from tanggal field
    protected function extractYearFromTanggal(?string $tanggal): ?int
    {
        if (!$tanggal) return null;
        $yearSuffix = substr(trim($tanggal), -2);
        if (!is_numeric($yearSuffix)) return null;
        return 2000 + (int) $yearSuffix;
    }
}
```

### Step 7: View - Admin Management
**File:** `resources/views/dokter/auditor-access/index.blade.php`

Halaman admin untuk mengelola link akses auditor:
- Tabel daftar link (No, Nama, Tahun Akses, Status, Aksi)
- Tombol "Buka Modal Buat Link"
- Setiap baris ada tombol: Copy Link, Toggle Active, Edit, Hapus
- Modal form: Nama Link, Deskripsi, Checkbox Tahun (2024-2030)
- Toast notification saat copy link

### Step 8: View - Auditor Public Access
**File:** `resources/views/auditor/index.blade.php`

Layout khusus (tanpa sidebar login):
- Header: Logo + nama perusahaan + info tahun akses
- Konten: File management yang difilter (sama seperti index.blade.php tapi tanpa fitur validasi)
- Breadcrumb navigasi
- Tabel file dengan aksi: Lihat PDF, Download
- Footer: Info hak akses

### Step 9: Layout - Auditor Public Layout
**File:** `resources/views/layouts/Auditor.blade.php`

Layout minimal untuk auditor:
```blade
<!DOCTYPE html>
<html>
<head>
    <title>Audit Access - {{ $link->name }}</title>
    <!-- CSS sama dengan layout lain -->
</head>
<body>
    <div class="container-fluid">
        <!-- Header sederhana -->
        @yield('content')
    </div>
    <!-- Scripts -->
</body>
</html>
```

### Step 10: Sidebar Update
**File:** `resources/views/layouts/partials/dokter/app-sidebar.blade.php` (update)

Tambahkan menu "Akses Auditor" setelah "File Management":
```blade
@can('dokter.auditor-access.manage')
<li>
    <a href="{{ route('dokter.auditor-access.index') }}" class="waves-effect">
        <i class='bx bx-link'></i>
        <span key="t-auditor-access">Akses Auditor</span>
    </a>
</li>
@endcan
```

---

## 6. File yang Perlu Dibuat/Update

### File Baru
| No | File | Deskripsi |
|----|------|-----------|
| 1 | `database/migrations/2026_08_07_000002_create_auditor_access_links_table.php` | Migration |
| 2 | `app/Models/AuditorAccessLink.php` | Model |
| 3 | `app/Http/Controllers/Dokter/AuditorAccessController.php` | Controller admin management |
| 4 | `app/Http/Controllers/Dokter/AuditorFileController.php` | Controller public access |
| 5 | `resources/views/dokter/auditor-access/index.blade.php` | View admin management |
| 6 | `resources/views/auditor/index.blade.php` | View public auditor access |
| 7 | `resources/views/layouts/Auditor.blade.php` | Layout auditor public |

### File yang Perlu Diupdate
| No | File | Deskripsi |
|----|------|-----------|
| 1 | `routes/routers/dokter.php` | Tambah admin routes |
| 2 | `routes/web.php` | Tambah public routes |
| 3 | `database/seeders/RolePermissionSeeder.php` | Tambah permission |
| 4 | `resources/views/layouts/partials/dokter/app-sidebar.blade.php` | Tambah menu sidebar |

---

## 7. UX Flow

### Flow Admin - Buat Link
```
1. Admin buka menu "Akses Auditor"
2. Tampil tabel daftar link yang sudah ada
3. Klik "Buat Link Baru"
4. Isi form: Nama Link, Deskripsi, Centang Tahun (2024, 2025, 2026)
5. Klik "Simpan"
6. Link terbuat: https://domain/auditor/a1b2c3d4e5f6...
7. Klik "Copy Link" → link tercopy ke clipboard
8. Kirim link ke auditor via WhatsApp/Email
```

### Flow Auditor - Akses File
```
1. Auditor buka link dari WhatsApp/Email
2. Halaman terbuka (tanpa login)
3. Tampil header: "Akses Audit - Link: Akses Auditor 2026"
4. Tampil filter tahun aktif: 2024, 2025, 2026
5. Daftar folder/file yang difilter berdasarkan tahun
6. Auditor bisa navigasi folder, lihat PDF, download
7. Tidak ada fitur validasi (read-only)
```

---

## 8. Edge Cases & Security

| Case | Penanganan |
|------|------------|
| **Token invalid** | Abort 404 — link tidak ditemukan |
| **Link nonaktif** | Tampil halaman "Link ini sudah tidak aktif" |
| **File tidak ada di scan_logs** | File tidak ditampilkan (karena tidak bisa diverifikasi tahunnya) |
| **Tanggal null di scan_logs** | File tetap ditampilkan (tidak difilter) |
| **Multiple auditor akses link sama** | Bisa — setiap link punya token unik |
| **Token collision** | Gunakan 64-char random string, probability sangat rendah |
| **Brute force token** | Rate limit pada route public |
| **Auditor download file** | Diizinkan — file yang diakses sudah difilter tahun |
| **File FINAL (merged)** | Bisa diakses — path juga ada di scan_logs |

---

## 9. Testing Checklist

### Admin Management
- [ ] Menu "Akses Auditor" muncul di sidebar untuk admin
- [ ] Tabel link tampil dengan benar
- [ ] Form buat link: validasi nama & tahun wajib
- [ ] Link terbuat dengan token unik
- [ ] Tombol Copy Link berfungsi
- [ ] Toggle active/inactive berfungsi
- [ ] Edit link (ubah tahun) berfungsi
- [ ] Hapus link berfungsi
- [ ] Permission `dokter.auditor-access.manage` berfungsi
- [ ] User tanpa permission tidak melihat menu

### Auditor Public Access
- [ ] Link valid → halaman terbuka
- [ ] Token invalid → 404
- [ ] Link nonaktif → pesan "link tidak aktif"
- [ ] File difilter berdasarkan tahun
- [ ] File dengan tanggal null tetap tampil
- [ ] Navigasi folder berfungsi
- [ ] Lihat PDF berfungsi
- [ ] Download file berfungsi
- [ ] Breadcrumb berfungsi
- [ ] Header menampilkan info link & tahun akses
- [ ] Tidak ada fitur validasi (read-only)

---

## 10. Estimasi Waktu

| No | Task | Estimasi |
|----|------|----------|
| 1 | Database Migration | 0.25 jam |
| 2 | Model | 0.5 jam |
| 3 | Seeder Permission | 0.25 jam |
| 4 | Routes (admin + public) | 0.5 jam |
| 5 | Controller Admin (AuditorAccessController) | 1.5 jam |
| 6 | Controller Public (AuditorFileController) | 2 jam |
| 7 | View Admin Management | 2 jam |
| 8 | View Auditor Public Access | 2 jam |
| 9 | Layout Auditor | 0.5 jam |
| 10 | Sidebar Update | 0.25 jam |
| 11 | Testing | 1.5 jam |
| **Total** | | **11.25 jam** |
