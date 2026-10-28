# Strategi Mengambil User Pemilik File Scanner

Status: **brainstorming / belum diimplementasikan**  
Tanggal: 2026-10-26  
Konteks: file scanner akan diletakkan di subfolder user, misalnya:

```text
/incoming/pedrianjunanda/...
/incoming/batrarenggo/...
/incoming/deddygustiawan/...
```

Tujuan: saat `app:monitor-scanner` berjalan, sistem mengetahui folder user asal file, lalu menyimpan identitas user tersebut ke tabel `scan_logs` (Log File).

---

## 1. Ringkasan Rekomendasi

Rekomendasi utama:

1. **Folder user tetap menjadi penanda utama**, tetapi **jangan hanya menyimpan nama folder mentah**.
2. Simpan dua hal di `scan_logs`:
   - `user_id` — relasi ke tabel `users` untuk filter, join, dan laporan.
   - `scanner_folder` — nama folder asli untuk audit/fallback jika user tidak ditemukan atau dihapus.
3. Sediakan **mapping eksplisit** `users.scanner_folder` sebagai sumber kebenaran.
4. Jika `scanner_folder` kosong, lakukan **auto-match** ke user berdasarkan:
   - `employee_id` / `nopeg`,
   - local-part email (tanpa titik/underscore),
   - nama user/nama employee yang dinormalisasi.
5. Jika tidak yakin atau hasil auto-match ambigu, **jangan menebak**. File tetap diproses dengan `user_id = null` dan dicatat sebagai `scanner_user_unresolved`, atau diarahkan ke mode karantina jika bisnis memerlukan attribusi wajib.
6. Struktur folder yang disarankan:

```text
/incoming
├── pedrianjunanda/
│   ├── 20261026_101500.pdf              # doc type dideteksi Gemini
│   └── invoice/
│       └── 20261026_101600.pdf          # doc type dari nama subfolder
├── batrarenggo/
│   └── 20261026_102000.pdf
├── invoice/                             # legacy: folder document type tanpa user
│   └── 20261026_103000.pdf
└── 20261026_104000.pdf                  # legacy: file langsung di root
```

Dengan struktur ini, kode lama tetap berjalan (backward compatible), sementara folder user bisa mulai dipakai bertahap.

---

## 2. Analisis Kondisi Saat Ini

### 2.1 Alur monitor scanner

`app/Console/Commands/Dokter/MonitorScanner.php`

- Command dijadwalkan setiap menit di `app/Console/Kernel.php:23`.
- `handle()`:
  - memastikan minimal ada satu `DocumentType`,
  - memanggil `processRootFiles()`,
  - memanggil `processSubfolderFiles()`.
- `processRootFiles()` (`MonitorScanner.php:86-114`):
  - mengambil file langsung di root disk `ftp_scanner`,
  - mengecek umur file (`isFileReady`),
  - memanggil `processRootFile()`.
- `processRootFile()` (`MonitorScanner.php:120-193`):
  - memvalidasi ekstensi,
  - download file dari FTP,
  - menyimpan ke `storage/app/private/scanner/incoming/{filename}`,
  - menghapus file dari FTP scanner,
  - dispatch `ProcessScanFile($filename)` **tanpa document type**; document type dideteksi Gemini.
- `processSubfolderFiles()` (`MonitorScanner.php:199-224`):
  - mengambil semua direktori level pertama di root FTP,
  - menganggap **nama folder = slug DocumentType**,
  - jika tidak cocok, hanya `warn("No document type found for subfolder: ...")` dan folder dilewati,
  - file langsung di dalam folder diproses dengan `ProcessScanFile($filename, $documentType->id)`.

### 2.2 Konfigurasi FTP

`config/filesystems.php:48-58`

```php
'ftp_scanner' => [
    'driver' => 'ftp',
    'root' => '/incoming',
    // ...
],
```

Artinya `Storage::disk('ftp_scanner')->files()` membaca isi `/incoming`, dan `directories()` membaca subfolder level pertama di `/incoming`.

### 2.3 Tabel `scan_logs`

`database/migrations/2026_07_31_000000_create_scan_logs_table.php`

Kolom yang ada saat ini:

- `source`, `event`, `status`
- `filename`, `extension`
- `document_type_id`, `document_type_name`
- `document_number`, `vendor_name`
- `ftp_path`, `file_size`, `processing_time_ms`
- `message`, `metadata`
- `tanggal`, `keterangan`, `uraian`
- `merge_status`, `total_pages`
- `linked_numbers`, `ocr_text`

**Belum ada kolom user scanner.** `ScanLog` juga belum punya relasi ke `User`.

### 2.4 Identitas user yang tersedia

- `users` tidak punya kolom `username`.
- Kolom identitas yang ada:
  - `name`
  - `email`
  - `employee_id`
  - `nopeg` (unik, nullable)
- Relasi `User::employee()` memakai `employee_id`.
- Login Microsoft SSO memakai `email` dan `name` (`MicrosoftAuthController.php:39-56`).
- Contoh data existing: email seperti `agung@satriabahana.co.id`, `wahid.pgrbe@tpm-facility.com`.

Contoh folder yang disebutkan:

```text
pedrianjunanda
batrarenggo
deddygustiawan
```

Bentuk ini terlihat seperti **local-part email yang dinormalisasi** (titik dihilangkan), atau nama lengkap tanpa spasi. Ini asumsi yang perlu dikonfirmasi ke pemilik scanner.

### 2.5 Yang terjadi jika folder user langsung ditambahkan sekarang

Folder `incoming/pedrianjunanda` akan dianggap sebagai folder document type:

- `basename($directory)` = `pedrianjunanda`
- `DocumentType::firstWhere('slug', 'pedrianjunanda')` = null
- monitor hanya menulis warning dan **file tidak diproses**.

Jadi `processSubfolderFiles()` harus diubah, bukan hanya ditambah kolom di log.

---

## 3. Gap yang Harus Diselesaikan

1. **Identitas folder hilang** saat `MonitorScanner` men-dispatch job; job hanya menerima `filename` dan `documentTypeId`.
2. **Belum ada kolom user** di `scan_logs`.
3. **Belum ada resolver** dari nama folder ke user.
4. **Struktur folder bertingkat** (user → document type) belum didukung.
5. **Local storage rawan tabrakan** karena semua file disimpan sebagai `scanner/incoming/{filename}`. Dua user bisa mengirim nama file yang sama.
6. **Folder tidak dikenal** saat ini hanya di-warning; belum ada kebijakan jelas apakah diproses, dikarantina, atau ditinggal.
7. **Log kegagalan** (`file_read_failed`, `file_write_failed`, `job_failed`) juga perlu membawa identitas user, bukan hanya log sukses.
8. **Log final merge** (`source = merge_flow`) bisa menggabungkan file dari beberapa user; attribusi user perlu kebijakan terpisah.

---

## 4. Opsi Strategi

### Opsi A — Simpan nama folder apa adanya

Contoh: `scan_logs.scanned_by = "pedrianjunanda"`.

Kelebihan:

- Implementasi paling cepat.
- Tidak perlu mapping atau join.
- Tetap mencatat siapa pemilik folder meskipun user belum terdaftar.

Kekurangan:

- Tidak bisa filter berdasarkan user ID.
- Nama folder bisa berubah; tidak ada relasi ke user.
- Tidak ada validasi bahwa folder benar milik user tersebut.
- Sulit dipakai untuk laporan per user.

Cocok untuk **quick win**, bukan solusi jangka panjang.

### Opsi B — Auto-match ke tabel `users`

Saat monitor menemukan folder, sistem mencari user dengan aturan:

- `employee_id` / `nopeg` sama,
- local-part email dinormalisasi sama,
- nama user/employee dinormalisasi sama.

Kelebihan:

- Tidak perlu input mapping manual.
- Langsung mendapatkan `user_id` dan nama display.

Kekurangan:

- Rawan salah cocok jika ada nama/email yang mirip.
- Nama folder harus konsisten dengan data user.
- Nama dengan gelar, spasi, atau karakter non-ASCII bisa gagal match.
- Dua user dengan nama sama → ambigu.

### Opsi C — Mapping eksplisit

Tambah kolom `users.scanner_folder` (atau tabel mapping terpisah).

Admin menentukan folder mana milik user mana.

Kelebihan:

- Paling akurat dan tahan terhadap perubahan nama.
- Bisa menangani folder yang tidak mengikuti pola email/nama.
- Mudah diaudit.

Kekurangan:

- Perlu data entry/UI admin.
- Perlu proses awal untuk mengisi mapping semua user.

### Opsi D — File penanda di dalam folder

Misalnya setiap folder berisi `.scanner-user.json`:

```json
{ "employee_id": "20250812" }
```

Kelebihan:

- Sangat eksplisit.
- Tidak bergantung pada nama folder.

Kekurangan:

- Harus diatur di sisi scanner/operator.
- File penanda bisa ikut terbaca sebagai file scan jika tidak difilter.
- Menambah kompleksitas operasional.

### Opsi E — Prefix nama file / FTP account per user

Scanner menulis nama file seperti `pedrianjunanda__20261026.pdf`, atau tiap user punya FTP account/root sendiri.

Kelebihan:

- Identitas menempel pada file, bukan folder.

Kekurangan:

- Mengubah konfigurasi scanner.
- Nama file jadi tidak natural.
- Per-user FTP account tidak kompatibel dengan satu monitor yang membaca satu root.

### Opsi F — Simpan hanya di kolom `metadata` JSON

Kelebihan:

- Tidak perlu migrasi.

Kekurangan:

- Filter/query per user lambat dan rumit.
- Tidak bisa join ke `users`.
- Tidak cocok untuk Log File dan export.

**Tidak direkomendasikan** untuk kebutuhan reporting.

### Perbandingan singkat

| Opsi | Akurasi | Effort | Query/Filter | Cocok jangka panjang |
|---|---:|---:|---:|---:|
| A. Nama folder mentah | Rendah | Sangat rendah | Sulit | Tidak |
| B. Auto-match | Sedang | Rendah | Baik | Sebagian |
| C. Mapping eksplisit | Tinggi | Sedang | Baik | Ya |
| D. File penanda | Tinggi | Tinggi | Baik | Ya, tapi operasional berat |
| E. Prefix/per-user FTP | Tinggi | Tinggi | Baik | Tergantung scanner |
| F. Metadata JSON | Rendah | Rendah | Buruk | Tidak |

---

## 5. Rekomendasi: Hybrid Mapping + Auto-Match

Gunakan **Opsi C sebagai sumber kebenaran**, dengan **Opsi B sebagai fallback**.

Urutan penentuan untuk satu folder level 1:

1. **Exact match** ke `users.scanner_folder` (case-insensitive, sudah dinormalisasi) → user folder.
2. **Exact match** ke `DocumentType.slug` → legacy document type folder, `user_id = null`.
3. **Exact match** ke `users.employee_id` atau `users.nopeg` → user folder.
4. **Normalized match** ke local-part email → user folder.
5. **Normalized match** ke `users.name` atau `employees.name` → user folder.
6. Jika tidak ada → `user_id = null`, `scanner_folder = <raw folder>`.
7. Jika ada lebih dari satu kandidat pada tingkat yang sama → **ambiguous**, jangan pilih salah satu; perlakukan seperti tidak ditemukan dan catat warning.

Document type dicek sebelum auto-match agar folder document type lama tidak tiba-tiba dianggap folder user karena kebetulan namanya mirip. Jika ada folder yang memang folder user tetapi namanya sama dengan slug document type, isi `users.scanner_folder` secara eksplisit agar mapping user menang.

Contoh normalisasi:

```php
$normalized = Str::of($value)
    ->ascii()
    ->lower()
    ->replaceMatches('/[^a-z0-9]/', '')
    ->toString();
```

Contoh hasil:

| Folder | Email | Hasil normalisasi |
|---|---|---|
| `pedrianjunanda` | `pedri.anjunanda@...` | `pedrianjunanda` |
| `batrarenggo` | `batra.renggo@...` | `batrarenggo` |
| `deddygustiawan` | `deddy.gustiawan@...` | `deddygustiawan` |

Catatan: normalisasi nama sebaiknya hanya fallback. Email local-part lebih stabil daripada nama yang bisa mengandung gelar.

---

## 6. Desain yang Diusulkan

### 6.1 Struktur folder

Struktur yang didukung:

```text
/incoming
├── <user-folder>/
│   ├── <file>                         # document type auto-detect
│   └── <document-type-slug>/
│       └── <file>                     # document type dari folder
├── <document-type-slug>/              # legacy, user null
│   └── <file>
└── <file>                             # legacy root, user null
```

Aturan:

- Level 1 = user folder **atau** document type folder legacy.
- Level 2 di dalam user folder = opsional document type.
- Kedalaman lebih dari 2 diabaikan dengan warning.
- Folder sistem seperti `.`, `..`, `@eaDir`, `#recycle`, `$RECYCLE.BIN` dilewati.

### 6.2 Aturan resolusi folder level 1

Untuk setiap direktori level 1:

1. Jika cocok dengan `users.scanner_folder` → **user folder**.
2. Jika cocok dengan `DocumentType.slug` → **legacy document type folder** (user null).
3. Jika tidak cocok keduanya, coba auto-match user (email/nama/NIP).
4. Jika tetap tidak ada → **unresolved**.
5. Jika nama folder cocok dengan user dan document type sekaligus → mapping eksplisit user menang, tetapi tulis warning agar konflik terlihat.

Prioritas ini penting agar folder document type lama tetap berjalan.

### 6.3 Data model

#### A. Tabel `users`

Tambahkan kolom opsional:

```php
Schema::table('users', function (Blueprint $table) {
    $table->string('scanner_folder')->nullable()->unique()->after('employee_id');
});
```

- `nullable` supaya user lama tidak perlu diisi langsung.
- `unique` supaya satu folder tidak dipakai dua user.
- Simpan setelah trim; perbandingan tetap case-insensitive.
- Tambahkan `scanner_folder` ke `$fillable` di `App\Models\User`.

Alternatif jika satu user bisa punya beberapa folder: buat tabel `scanner_user_mappings` (`folder_name`, `user_id`, `is_active`). Untuk kebutuhan saat ini, satu kolom di `users` lebih sederhana.

#### B. Tabel `scan_logs`

Minimal:

```php
Schema::table('scan_logs', function (Blueprint $table) {
    $table->foreignId('user_id')
        ->nullable()
        ->after('source')
        ->constrained('users')
        ->nullOnDelete();

    $table->string('scanner_folder')->nullable()->after('user_id');
    $table->index('scanner_folder');
});
```

Rekomendasi tambahan untuk audit jangka panjang:

```php
$table->string('scanned_by_name')->nullable()->after('scanner_folder');
```

- `user_id` untuk filter/join.
- `scanner_folder` menyimpan nama folder asli, tetap ada meskipun user dihapus.
- `scanned_by_name` snapshot nama user saat scan; opsional tetapi berguna jika user di-rename/dihapus.
- Tanpa `scanned_by_name`, display bisa memakai `$log->user?->name ?? $log->scanner_folder ?? '-'`.

#### C. Model `ScanLog`

Tambahkan ke `$fillable`:

```php
'user_id',
'scanner_folder',
'scanned_by_name', // opsional
```

Tambahkan relasi:

```php
public function user(): BelongsTo
{
    return $this->belongsTo(User::class);
}
```

Tambahkan label event baru di `getEventLabelAttribute()`:

```php
'scanner_user_unresolved' => 'User Scanner Tidak Dikenali',
'scanner_user_ambiguous' => 'User Scanner Ambigu',
'scanner_folder_unknown' => 'Subfolder Scanner Tidak Dikenali',
```

### 6.4 Resolver user

Buat service baru, misalnya `app/Services/Scanner/ScannerUserResolver.php`.

Tanggung jawab:

- Menerima nama folder.
- Mengembalikan user (explicit/auto), status ambiguous, atau unresolved.
- Menyediakan data log: `user_id`, `scanner_folder`, `scanned_by_name`.

Strategi performa:

- Load semua user yang diperlukan **sekali per siklus monitor**, bukan query per file.
- Kolom yang diambil: `id`, `name`, `email`, `employee_id`, `nopeg`, `scanner_folder`.
- Jika jumlah user sangat besar, gunakan cache 5–10 menit dan invalidasi saat user diubah.
- Untuk jumlah user normal (ratusan/ribuan), satu query per siklus sudah cukup.

Pseudocode:

```php
final class ScannerUserResolver
{
    /** @var array<string, User> */
    private array $byFolder = [];

    /** @var array<string, User[]> */
    private array $byNormalized = [];

    public function __construct(Collection $users)
    {
        foreach ($users as $user) {
            if ($user->scanner_folder) {
                $this->byFolder[$this->normalize($user->scanner_folder)] = $user;
            }

            foreach ($this->candidates($user) as $candidate) {
                $this->byNormalized[$this->normalize($candidate)][] = $user;
            }
        }
    }

    public function resolveExplicit(string $folder): ?User
    {
        return $this->byFolder[$this->normalize($folder)] ?? null;
    }

    public function resolveAuto(string $folder): UserResolution
    {
        $matches = $this->unique(
            $this->byNormalized[$this->normalize($folder)] ?? []
        );

        return match (count($matches)) {
            1 => UserResolution::resolved($matches[0], $folder),
            0 => UserResolution::unresolved($folder),
            default => UserResolution::ambiguous($folder, $matches),
        };
    }

    private function candidates(User $user): array
    {
        return array_filter([
            $user->employee_id,
            $user->nopeg,
            $user->email ? Str::before($user->email, '@') : null,
            $user->name,
            $user->employee?->name,
        ]);
    }
}
```

`UserResolution` bisa berupa DTO kecil:

```php
final class UserResolution
{
    public function __construct(
        public ?User $user,
        public string $folder,
        public bool $ambiguous = false,
    ) {}
}
```

### 6.5 Perubahan `MonitorScanner`

Refactor utama:

- Pertahankan `processRootFiles()` untuk file legacy di root.
- Ganti `processSubfolderFiles()` menjadi `processIncomingDirectories()`.
- Untuk setiap direktori:
  - resolve user,
  - tentukan apakah direktori itu user folder atau legacy document type folder,
  - proses file langsung,
  - proses satu level subfolder document type jika ada.
- Dispatch job dengan konteks user.

Pseudocode:

```php
public function handle(
    FileConversionService $converter,
    ScanLogger $logger,
): int {
    if (DocumentType::count() === 0) {
        return self::FAILURE;
    }

    $users = User::with('employee:id,employee_id,name')
        ->get(['id', 'name', 'email', 'employee_id', 'nopeg', 'scanner_folder']);

    $resolver = new ScannerUserResolver($users);

    $this->processRootFiles($converter, $logger);
    $this->processIncomingDirectories($converter, $logger, $resolver);

    return self::SUCCESS;
}

protected function processIncomingDirectories(
    FileConversionService $converter,
    ScanLogger $logger,
    ScannerUserResolver $resolver,
): void {
    $documentTypes = DocumentType::all()->keyBy('slug');

    foreach (Storage::disk('ftp_scanner')->directories() as $directory) {
        $folder = basename($directory);

        if ($this->isSystemFolder($folder)) {
            continue;
        }

        $explicitUser = $resolver->resolveExplicit($folder);
        $legacyDocType = $documentTypes->get($folder);

        // 1. Mapping eksplisit user (paling dipercaya)
        if ($explicitUser !== null) {
            $this->processUserFolder(
                $directory,
                $explicitUser,
                $folder,
                $documentTypes,
                $converter,
                $logger,
            );

            continue;
        }

        // 2. Legacy document type folder
        if ($legacyDocType !== null) {
            $this->processFilesInDirectory(
                $directory,
                $legacyDocType,
                null,
                null,
                $converter,
                $logger,
            );

            continue;
        }

        // 3. Fallback auto-match (email/nama/NIP)
        $resolution = $resolver->resolveAuto($folder);

        if ($resolution->user !== null) {
            $this->processUserFolder(
                $directory,
                $resolution->user,
                $folder,
                $documentTypes,
                $converter,
                $logger,
            );

            continue;
        }

        // 4. Ambiguous / unknown
        $logger->log(
            $resolution->ambiguous ? 'scanner_user_ambiguous' : 'scanner_user_unresolved',
            'warning',
            [
                'scanner_folder' => $folder,
                'message' => $resolution->ambiguous
                    ? 'Folder cocok dengan lebih dari satu user; file tidak diproses otomatis.'
                    : 'Folder tidak cocok dengan user maupun document type.',
            ],
        );
    }
}

protected function processUserFolder(
    string $directory,
    User $user,
    string $folder,
    Collection $documentTypes,
    FileConversionService $converter,
    ScanLogger $logger,
): void {
    // File langsung di folder user: document type auto-detect.
    $this->processFilesInDirectory(
        $directory,
        null,
        $user,
        $folder,
        $converter,
        $logger,
    );

    // Subfolder document type di dalam folder user.
    foreach (Storage::disk('ftp_scanner')->directories($directory) as $subDirectory) {
        $subFolder = basename($subDirectory);
        $documentType = $documentTypes->get($subFolder);

        if ($documentType === null) {
            $logger->log('scanner_folder_unknown', 'warning', [
                'scanner_folder' => $folder.'/'.$subFolder,
                'message' => 'Subfolder di dalam folder user bukan document type yang dikenal.',
            ]);

            continue;
        }

        $this->processFilesInDirectory(
            $subDirectory,
            $documentType,
            $user,
            $folder,
            $converter,
            $logger,
        );
    }
}
```

`processFilesInDirectory()` bertanggung jawab:

- `Storage::disk('ftp_scanner')->files($directory)`,
- cek `isFileReady`,
- panggil `processFile($file, $documentType, $user, $folder, ...)`.

### 6.6 Perubahan `ProcessScanFile`

Tambahkan properti baru dengan default agar job lama tetap aman:

```php
public function __construct(
    public string $filename,
    public ?int $documentTypeId = null,
    public ?int $userId = null,
    public ?string $scannerFolder = null,
    public ?string $scannedByName = null,
    public ?string $localPath = null,
) {}
```

Catatan penting:

- Semua argumen baru harus **opsional** agar call site lama (`ProcessScanFile::dispatch($filename)`) tetap jalan.
- Beri default `null` pada properti promoted. Job lama yang masih ada di queue tidak memiliki properti baru; default membantu saat unserialize.
- Setelah deploy, jalankan `php artisan queue:restart` agar worker memakai class terbaru.

Gunakan `localPath` untuk menghindari tabrakan:

```php
$incomingPath = $this->localPath
    ?: "scanner/incoming/{$this->filename}";
```

Saat monitor menyimpan file:

```php
$folderSegment = $user?->scanner_folder ?: ($scannerFolder ?: '_root');
$localPath = "scanner/incoming/{$folderSegment}/{$filename}";

Storage::disk('local')->put($localPath, $content);
```

Dengan begitu dua user yang mengirim nama file sama tidak saling menimpa.

Hal yang sama perlu diterapkan pada:

- `scanner/ocr-results/{filename}.json` → gunakan subfolder/folder segment.
- file temp/merged di `scanner/temp` dan `scanner/merged` → gunakan nama unik berbasis `localPath` atau hash.

### 6.7 Propagasi user ke semua log

Tambahkan konteks user ke **semua** pemanggilan `ScanLogger::log()` di `MonitorScanner` dan `ProcessScanFile`, bukan hanya `job_completed`.

Contoh helper di `ProcessScanFile`:

```php
private function ownerLogData(): array
{
    return [
        'user_id' => $this->userId,
        'scanner_folder' => $this->scannerFolder,
        'scanned_by_name' => $this->scannedByName,
    ];
}
```

Lalu:

```php
$logger->log('job_completed', 'success', array_merge($this->ownerLogData(), [
    'filename' => $this->filename,
    'document_type_id' => $documentType->id,
    // ...
]));
```

Untuk `failed()`:

```php
app(ScanLogger::class)->log('job_failed', 'failed', array_merge(
    $this->ownerLogData(),
    ['filename' => $this->filename, 'message' => $exception?->getMessage()]
));
```

Di `MonitorScanner`, `file_skipped`, `file_read_failed`, dan `file_write_failed` juga harus membawa `user_id`/`scanner_folder`.

Yang perlu diperhatikan:

- `ScanLogger::log()` melakukan `array_merge` dengan data yang dikirim (`ScanLogger.php:26-30`), jadi cukup pastikan kolom baru ada di `$fillable`.
- `source`, `event`, dan `status` tetap seperti sekarang.
- Untuk file legacy tanpa user, `user_id` dan `scanner_folder` bernilai `null`; UI menampilkan `-` atau `Tidak diketahui`.
- Jika memilih skema minimal **tanpa** `scanned_by_name`, jangan kirim field itu ke `ScanLogger` (atau hapus dari `$fillable`) agar tidak error kolom tidak ada. Skema yang direkomendasikan tetap menyertakannya untuk snapshot audit.

### 6.8 UI Log File

`app/Http/Controllers/Dokter/LogFileController.php`

- Tambahkan filter `user_id` pada `applyFilters()`.
- Tambahkan `user_id` ke `filterSummary()`.
- Kirim daftar user ke view (bisa semua user, atau hanya user yang punya log).

`resources/views/dokter/log-file/index.blade.php`

- Tambahkan kolom **User** setelah **Waktu Scan**.
- Tampilkan:

```blade
{{ $log->user?->name ?? $log->scanner_folder ?? '-' }}
```

- Tambahkan field user di modal detail.
- Tambahkan `data-user` pada tombol view dan isi modal lewat JS.
- Tambahkan dropdown filter user.
- Perluas pencarian teks agar mencakup `scanner_folder` dan nama user.
- Sesuaikan `colspan` empty state (dari 7 menjadi 8).

`app/Exports/ScanLogsExport.php`

- Tambahkan heading **User**.
- Tambahkan mapping `user?->name ?? scanner_folder ?? '-'`.
- **Perhatian:** class ini memakai kolom hardcoded:
  - `statusColumn = 10`
  - `columnWidths()` untuk kolom D/I/K/N
  - border/footer merge `A:J`
- Jika kolom user disisipkan sebelum status, semua indeks bergeser. Perlu penyesuaian dan pengujian export.

Opsional:

- Dashboard/statistik jumlah scan per user.
- Filter cepat "user saya" untuk user yang login.

### 6.9 Log final merge (`source = merge_flow`)

`MergeFlowService::performFinalMerge()` membuat `ScanLog` baru untuk file final hasil merge (`MergeFlowService.php:416-443`).

File final bisa berasal dari beberapa user sekaligus. Rekomendasi:

- **Jangan** mengisi `user_id` dengan user pertama, karena bisa menyesatkan.
- Simpan daftar user di `metadata`:

```php
'metadata' => [
    // ...
    'scanner_user_ids' => $items->pluck('scanLog.user_id')->filter()->unique()->values()->all(),
    'scanner_folders' => $items->pluck('scanLog.scanner_folder')->filter()->unique()->values()->all(),
]
```

- `user_id` pada log `merge_final` dibiarkan `null` karena satu file final bisa milik beberapa user.
- Log per-file (`job_completed`) tetap menjadi sumber utama attribusi "file ini discan oleh siapa".

### 6.10 Folder tidak dikenal / ambigu

Pilihan kebijakan:

| Mode | Perilaku | Kelebihan | Kekurangan |
|---|---|---|---|
| `process_unknown` | Proses file, `user_id = null`, warning | File tidak hilang | Attribusi tidak lengkap |
| `leave` | Biarkan di FTP, warning | Tidak ada data hilang | Folder terus muncul tiap siklus |
| `quarantine` | Pindahkan ke folder `_unmatched` | Tidak mengganggu alur normal | Perlu pengelolaan manual |

Rekomendasi awal:

- **Default `leave`** untuk folder yang tidak dikenal, agar tidak salah attribusi dan tidak menghapus file.
- Log warning **sekali per folder per siklus**, jangan per file.
- Sediakan konfigurasi opsional:

```env
SCANNER_REQUIRE_USER=false
SCANNER_UNKNOWN_FOLDER_ACTION=leave
```

Jika `SCANNER_REQUIRE_USER=true`, file tanpa user tidak diproses ke final FTP sampai mapping diperbaiki.

---

## 7. Rencana Implementasi Bertahap

### Fase 0 — Konfirmasi aturan bisnis

- Konfirmasi sumber nama folder: email local-part, nama lengkap, NIP, atau bebas.
- Konfirmasi apakah document type masih boleh menjadi subfolder di dalam folder user.
- Konfirmasi perilaku folder unknown.
- Konfirmasi apakah attribusi wajib untuk semua file atau boleh `null`.

### Fase 1 — Fondasi data & resolver

1. Migrasi `users.scanner_folder`.
2. Migrasi `scan_logs.user_id`, `scanner_folder`, opsional `scanned_by_name`.
3. Update `User` dan `ScanLog` model.
4. Buat `ScannerUserResolver`.
5. Unit test resolver.

### Fase 2 — Monitor & job

1. Refactor `MonitorScanner`:
   - deteksi user folder,
   - dukung subfolder document type,
   - pertahankan legacy root/doc-type.
2. Tambahkan `userId`, `scannerFolder`, `scannedByName`, `localPath` ke `ProcessScanFile`.
3. Terapkan local path per folder.
4. Propagasi user ke semua log.
5. Feature test monitor dengan `Storage::fake('ftp_scanner')` dan `Queue::fake()`.

### Fase 3 — UI & export

1. Filter user di Log File.
2. Kolom user di tabel dan modal detail.
3. Kolom user di export Excel.
4. Test render halaman dan export.

### Fase 4 — Operasional

1. Isi `users.scanner_folder` untuk user yang sudah ada.
2. Command bantu, misalnya `php artisan scanner:map-users`, untuk:
   - menampilkan kandidat mapping dari email/nama,
   - menulis mapping jika yakin,
   - menandai yang ambigu untuk review manual.
3. Sosialisasi struktur folder ke operator scanner.
4. Monitoring log `scanner_user_unresolved`.

---

## 8. Rencana Pengujian

### Unit test `ScannerUserResolver`

- Exact `scanner_folder`.
- Exact `employee_id`.
- Exact `nopeg`.
- Email local-part dengan titik.
- Nama dengan spasi.
- Case-insensitive.
- Nama dengan karakter non-ASCII.
- Dua user dengan nama sama → ambiguous/null.
- Folder tidak dikenal → null.
- `scanner_folder` eksplisit menang atas auto-match.

### Feature test `MonitorScanner`

Gunakan `Storage::fake('ftp_scanner')`, `Storage::fake('local')`, `Queue::fake()`, dan beberapa `DocumentType`.

Skenario:

1. File di root → `ProcessScanFile` dengan `userId = null`.
2. File di folder user → `ProcessScanFile` dengan `userId` dan `scannerFolder`.
3. File di `user/invoice` → `ProcessScanFile` dengan `userId` + `documentTypeId`.
4. File di folder document type legacy → `userId = null`.
5. Folder unknown → tidak ada job, ada log warning.
6. File belum cukup umur → tidak diproses.
7. Dua user dengan nama file sama → local path berbeda.

### Test `ProcessScanFile`

Karena `GeminiEngine` diinstansiasi langsung di dalam job, pengujian penuh perlu strategi tambahan:

- Ekstrak helper pembentuk log context agar bisa diuji unit.
- Atau ubah `GeminiEngine` menjadi dependency injection (opsional, di luar scope).
- Minimal test:
  - job membawa properti user,
  - `ownerLogData()` menghasilkan data yang benar,
  - `localPath` yang diberikan dipakai dan file dihapus setelah sukses.

### Test UI/export

- Halaman Log File menampilkan kolom user.
- Filter `user_id` memfilter data.
- Export Excel memiliki kolom user dan format kolom tetap rapi.
- Data lama tanpa user menampilkan `-`.

### Manual end-to-end

1. Buat folder `incoming/<user>/` di FTP scanner.
2. Taruh PDF.
3. Jalankan `php artisan app:monitor-scanner`.
4. Cek:
   - file terhapus dari FTP scanner,
   - job masuk queue,
   - `scan_logs` memiliki `user_id` dan `scanner_folder`,
   - Log File menampilkan nama user.
5. Ulangi untuk `incoming/<user>/invoice/`.
6. Ulangi untuk folder unknown.

---

## 9. Kompatibilitas & Deployment

- **Deploy kode sebelum mengubah scanner** agar folder user sudah dikenali saat mulai dipakai.
- Kode baru harus tetap mendukung:
  - file langsung di root,
  - folder document type legacy,
  - folder user,
  - subfolder document type di dalam user.
- Log lama tidak bisa di-backfill karena tidak ada informasi user di data historis. Baris lama akan menampilkan `-`.
- Setelah perubahan signature job, jalankan:

```bash
php artisan queue:restart
```

- Jika ada job lama di queue, properti baru harus punya default `null` agar tidak error.
- Jangan menghapus file di folder unknown secara otomatis.
- Pastikan worker queue dan scheduler memakai versi kode yang sama.

---

## 10. Keputusan yang Perlu Dikonfirmasi

1. **Sumber nama folder**: apakah pasti local-part email tanpa titik, atau bisa nama bebas?
2. Apakah semua user scanner ada di tabel `users`, atau ada operator yang tidak punya akun aplikasi?
3. Apakah satu user bisa punya lebih dari satu folder scanner?
4. Apakah document type masih akan diletakkan sebagai subfolder di dalam folder user?
5. Jika folder tidak dikenal, file boleh diproses dengan user `null`, atau harus dikarantina?
6. Apakah attribusi user wajib (blocking) atau hanya informatif?
7. Apakah file final hasil merge perlu menampilkan daftar user asal, atau cukup log per-file?
8. Apakah perlu UI admin untuk mengatur `scanner_folder`, atau cukup via seeder/command?
9. Apakah nama file dari scanner dijamin unik? Jika tidak, local path per user wajib.
10. Apakah ada kebutuhan laporan/statistik scan per user di dashboard?

---

## 11. File yang Akan Terdampak

| File | Perubahan |
|---|---|
| `database/migrations/..._add_scanner_folder_to_users_table.php` | Kolom mapping user |
| `database/migrations/..._add_scanner_user_to_scan_logs_table.php` | `user_id`, `scanner_folder`, opsional `scanned_by_name` |
| `app/Models/User.php` | `scanner_folder` fillable |
| `app/Models/ScanLog.php` | fillable, relasi `user()`, label event baru |
| `app/Services/Scanner/ScannerUserResolver.php` | Baru: resolusi folder → user |
| `app/Console/Commands/Dokter/MonitorScanner.php` | Refactor deteksi folder, dispatch dengan user |
| `app/Jobs/ProcessScanFile.php` | Properti user, local path, propagasi log |
| `app/Http/Controllers/Dokter/LogFileController.php` | Filter user |
| `resources/views/dokter/log-file/index.blade.php` | Kolom/filter/detail user |
| `app/Exports/ScanLogsExport.php` | Kolom user di export |
| `app/Http/Controllers/ITAdmin/UserController.php` | Opsional: simpan/load `scanner_folder` |
| `resources/views/it-admin/users/index.blade.php` | Opsional: input mapping |
| `tests/Unit/Scanner/ScannerUserResolverTest.php` | Baru |
| `tests/Feature/Dokter/MonitorScannerUserFolderTest.php` | Baru |

---

## 12. Risiko & Catatan

1. **Salah attribusi** lebih buruk daripada tidak ada attribusi. Karena itu, jangan menebak saat ambigu.
2. **Nama folder bisa berubah** sewaktu-waktu. Simpan `scanner_folder` mentah di log agar historis tetap bisa ditelusuri.
3. **User bisa dihapus**. Dengan `nullOnDelete`, log tetap ada; `scanner_folder` tetap menjadi fallback.
4. **Tabrakan nama file** nyata jika dua user scan pada detik yang sama. Local path per folder harus diterapkan bersamaan dengan fitur ini.
5. **Folder unknown bisa membanjiri log** karena monitor berjalan setiap menit. Batasi warning per folder/siklus atau gunakan mode karantina.
6. **Attribusi bukan autentikasi**. Siapa pun yang bisa menulis ke FTP bisa membuat folder atas nama user lain. Jika butuh jaminan keamanan, perlu mekanisme tambahan (per-user FTP credential, signed metadata, atau file penanda).
7. **Merge flow** menggabungkan beberapa file; jangan memaksa satu `user_id` untuk file final. Simpan daftar user di metadata.
8. **Export Excel** memakai indeks kolom hardcoded; perubahan kolom harus diuji agar styling dan footer tidak bergeser.

---

## 13. Rekomendasi Akhir

Mulai dari desain **hybrid**:

- `users.scanner_folder` untuk mapping eksplisit,
- `ScannerUserResolver` untuk fallback otomatis,
- `scan_logs.user_id` + `scan_logs.scanner_folder` untuk pencatatan,
- dukungan folder bertingkat `user/document-type/file`,
- local path per user untuk mencegah tabrakan,
- folder unknown tidak dihapus dan tidak ditebak.

Dengan desain ini, saat monitor scanner berjalan, sistem dapat menjawab pertanyaan:

> "File ini berasal dari folder user siapa?"

secara akurat, tetap kompatibel dengan struktur lama, dan siap dikembangkan ke filter/laporan per user tanpa mengubah alur OCR dan merge yang sudah ada.
