# Planning: Implementasi Mekanisme Gemini API untuk Ekstraksi Teks

## 1. Analisis Codebase

### Struktur Modul Dokter (Relevan)

```
app/
├── Jobs/
│   └── ProcessScanFile.php          # Job utama pemrosesan file scan
├── Services/
│   ├── OcrService.php               # Service OCR (OCR Space API)
│   ├── DocumentTypeProcessor.php    # Proses ekstraksi data dari teks OCR
│   ├── OcrSearchService.php         # Pencarian data OCR
│   ├── ScanLogger.php               # Logger aktivitas scan
│   └── FileConversionService.php    # Konversi file (image↔PDF)
├── Models/
│   ├── ScanLog.php                  # Model scan_logs
│   └── DocumentType.php             # Model document_types (konfigurasi regex)
├── Http/Controllers/Dokter/
│   ├── DocumentTypeController.php   # CRUD Jenis Dokumen
│   └── LogFileController.php        # Log file & export
└── Enums/
    └── (kosong, belum ada enum untuk OCR engine)

config/
└── services.php                     # Konfigurasi OCR Space & Gemini API key

database/migrations/
├── 2026_07_23_082212_create_document_types_table.php
├── 2026_07_26_000000_add_ocr_config_to_document_types_table.php
└── 2026_08_04_000014_add_linked_numbers_and_ocr_text_to_scan_logs_table.php

resources/views/dokter/
├── dashboard/index.blade.php
├── document-type/{index,create,edit}.blade.php
├── log-file/index.blade.php
└── ...

routes/routers/dokter.php            # Route modul dokter
```

### Alur Proses OCR Saat Ini

```
File Scan (FTP/Upload)
    ↓
ProcessScanFile::handle()
    ↓
OcrService::extractText()        ← OCR Space API
    ↓ (mengembalikan teks mentah)
DocumentTypeProcessor::extractDocumentNumber()   ← regex
DocumentTypeProcessor::matchVendor()             ← search
DocumentTypeProcessor::extractTanggal()          ← regex
DocumentTypeProcessor::extractKeterangan()       ← regex
DocumentTypeProcessor::extractUraian()           ← regex
    ↓
Simpan ke scan_logs (ocr_text + field hasil ekstraksi)
Upload ke FTP server
```

### Konfigurasi OCR Space (Saat Ini)

**config/services.php:**
```php
'ocr' => [
    'api_endpoint' => env('OCR_SPACE_API_ENDPOINT'),  // https://api.ocr.space/parse/image
    'api_key' => env('OCR_SPACE_API_KEY'),            // K86996621088957
    'engine' => env('OCR_SPACE_ENGINE'),              // 3
],
'gemini_api_key' => env('GOOGLE_GEMINI_API_KEY')     // Sudah ada di .env
```

### Database Schema

**document_types:**
- `id`, `name`, `slug`, `description`
- `number_regex`, `number_label`
- `keterangan_regex`, `keterangan_label`, `keterangan_enabled`
- `uraian_regex`, `uraian_label`, `uraian_enabled`
- `tanggal_regex`, `tanggal_label`, `tanggal_enabled`
- `filename_template`, `ftp_folder_template`, `ftp_failed_folder`
- `vendor_search_enabled`, `header_regex`

**scan_logs:**
- `id`, `source`, `event`, `status`, `filename`, `extension`
- `document_type_id`, `document_type_name`, `document_number`
- `vendor_name`, `tanggal`, `keterangan`, `uraian`
- `ftp_path`, `file_size`, `processing_time_ms`, `message`
- `metadata` (JSON), `linked_numbers` (JSON), `ocr_text` (TEXT)

---

## 2. Arsitektur yang Direkomendasikan

### Prinsip: Strategy Pattern + Configurable Engine

Menggunakan **Strategy Pattern** agar mekanisme OCR dapat diganti tanpa mengubah kode di `ProcessScanFile`.

```
┌─────────────────────────────────────────────┐
│              ProcessScanFile (Job)           │
│  Memanggil OcrEngineFactory::create()       │
│  berdasarkan config aktif                    │
└──────────────────┬──────────────────────────┘
                   │
         ┌─────────▼─────────┐
         │  OcrEngineFactory  │
         │  → getEngine()    │
         └─────────┬─────────┘
                   │
    ┌──────────────┼──────────────┐
    ▼              ▼              ▼
┌────────┐   ┌──────────┐   ┌──────────┐
│OcrSpace│   │GeminiApi │   │ (future) │
│Engine  │   │Engine    │   │          │
└────────┘   └──────────┘   └──────────┘
```

### Komponen Baru

| Komponen | Path | Fungsi |
|----------|------|--------|
| `OcrEngineInterface` | `app/Services/Ocr/Contracts/OcrEngineInterface.php` | Kontrak untuk semua OCR engine |
| `OcrSpaceEngine` | `app/Services/Ocr/OcrSpaceEngine.php` | Wrapper OCR Space API |
| `GeminiEngine` | `app/Services/Ocr/GeminiEngine.php` | Implementasi Gemini API |
| `OcrEngineFactory` | `app/Services/Ocr/OcrEngineFactory.php` | Factory untuk create engine |
| `OcrSetting` | `app/Models/OcrSetting.php` | Model untuk menyimpan config aktif |
| `OcrSettingController` | `app/Http/Controllers/Dokter/OcrSettingController.php` | Controller untuk switch engine |
| Migration | `database/migrations/...` | Tabel `ocr_settings` |
| View | `resources/views/dokter/ocr-setting/index.blade.php` | UI untuk switch engine |

---

## 3. Detail Implementasi

### 3.1. Enum OCR Engine

```php
// app/Enums/OcrEngineType.php
enum OcrEngineType: string
{
    case OCR_SPACE = 'ocr_space';
    case GEMINI = 'gemini';

    public function label(): string
    {
        return match ($this) {
            self::OCR_SPACE => 'OCR Space API',
            self::GEMINI => 'Google Gemini API',
        };
    }
}
```

### 3.2. Interface OCR Engine

```php
// app/Services/Ocr/Contracts/OcrEngineInterface.php
interface OcrEngineInterface
{
    /**
     * Ekstraksi teks dari file.
     * @return array{success: bool, text: string, processing_time_ms: ?int}
     */
    public function extractText(UploadedFile $file): array;

    /**
     * Nama engine untuk logging.
     */
    public function engineName(): string;
}
```

### 3.3. OcrSpaceEngine (Wrapper Existing)

```php
// app/Services/Ocr/OcrSpaceEngine.php
class OcrSpaceEngine implements OcrEngineInterface
{
    protected OcrService $service;

    public function __construct()
    {
        $this->service = app(OcrService::class);
    }

    public function extractText(UploadedFile $file): array
    {
        return $this->service->extractText($file);
    }

    public function engineName(): string
    {
        return 'ocr_space';
    }
}
```

### 3.4. GeminiEngine (Implementasi Baru)

```php
// app/Services/Ocr/GeminiEngine.php
class GeminiEngine implements OcrEngineInterface
{
    protected string $apiKey;
    protected string $model;
    protected string $prompt;

    public function __construct()
    {
        $this->apiKey = config('services.gemini_api_key');
        $this->model = config('services.gemini.model', 'gemini-1.5-flash');
        $this->prompt = config('services.gemini.prompt',
            'Baca dan ekstrak seluruh teks dari dokumen ini secara akurat. '
            .'Pertahankan format asli dokumen.'
        );
    }

    public function extractText(UploadedFile $file): array
    {
        $startTime = microtime(true);

        // 1. Encode file ke base64
        $fileContent = file_get_contents($file->getRealPath());
        $mimeType = $file->getMimeType();
        $base64Content = base64_encode($fileContent);

        // 2. Kirim ke Gemini API
        $response = Http::timeout(120)
            ->withHeaders([
                'Content-Type' => 'application/json',
            ])
            ->post("https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}", [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $this->prompt],
                            [
                                'inline_data' => [
                                    'mime_type' => $mimeType,
                                    'data' => $base64Content,
                                ],
                            ],
                        ],
                    ],
                ],
                'generationConfig' => [
                    'temperature' => 0.1,
                    'maxOutputTokens' => 8192,
                ],
            ]);

        if ($response->failed()) {
            throw new Exception('Gemini API error: '.$response->body());
        }

        $data = $response->json();
        $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

        $elapsed = (microtime(true) - $startTime) * 1000;

        return [
            'success' => true,
            'text' => trim($text),
            'processing_time_ms' => (int) round($elapsed),
        ];
    }

    public function engineName(): string
    {
        return 'gemini';
    }
}
```

### 3.5. OcrEngineFactory

```php
// app/Services/Ocr/OcrEngineFactory.php
class OcrEngineFactory
{
    public static function create(?string $engine = null): OcrEngineInterface
    {
        $engine = $engine ?? self::getActiveEngine();

        return match ($engine) {
            'gemini' => new GeminiEngine(),
            default => new OcrSpaceEngine(),
        };
    }

    public static function getActiveEngine(): string
    {
        // 1. Cek database setting
        $setting = OcrSetting::where('key', 'active_engine')->first();
        if ($setting) {
            return $setting->value;
        }

        // 2. Fallback ke config
        return config('services.ocr.active_engine', 'ocr_space');
    }
}
```

### 3.6. Model OcrSetting

```php
// app/Models/OcrSetting.php
class OcrSetting extends Model
{
    protected $table = 'ocr_settings';

    protected $fillable = ['key', 'value', 'description'];

    // Cache helper
    public static function getValue(string $key, ?string $default = null): ?string
    {
        return cache()->remember("ocr_setting_{$key}", 3600, function () use ($key, $default) {
            return static::where('key', $key)->value('value') ?? $default;
        });
    }

    public static function setValue(string $key, ?string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        cache()->forget("ocr_setting_{$key}");
    }
}
```

### 3.7. Migration: Tabel ocr_settings

```php
// database/migrations/2026_08_10_000001_create_ocr_settings_table.php
Schema::create('ocr_settings', function (Blueprint $table) {
    $table->id();
    $table->string('key')->unique();
    $table->text('value')->nullable();
    $table->text('description')->nullable();
    $table->timestamps();
});

// Seed default value
OCRSetting::create([
    'key' => 'active_engine',
    'value' => 'ocr_space',
    'description' => 'Mekanisme OCR yang aktif: ocr_space atau gemini',
]);
```

### 3.8. Controller: OCR Setting

```php
// app/Http/Controllers/Dokter/OcrSettingController.php
class OcrSettingController extends Controller
{
    public function index()
    {
        $pageName = 'Pengaturan OCR';
        $engines = OcrEngineType::cases();
        $activeEngine = OcrSetting::getValue('active_engine', 'ocr_space');

        return view('dokter.ocr-setting.index', compact('pageName', 'engines', 'activeEngine'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'engine' => ['required', Rule::in(OcrEngineType::values())],
        ]);

        OcrSetting::setValue('active_engine', $request->engine);

        return back()->with('success', 'Mekanisme OCR berhasil diubah ke: '
            .OcrEngineType::from($request->engine)->label());
    }

    public function test(Request $request)
    {
        $request->validate([
            'engine' => ['required', Rule::in(OcrEngineType::values())],
        ]);

        // Test dengan file sample jika ada
        $engine = OcrEngineFactory::create($request->engine);
        // ... logic test ...
    }
}
```

### 3.9. Route

```php
// routes/routers/dokter.php (tambahkan)
Route::prefix('ocr-setting')->name('ocr-setting.')->middleware('permission:dokter.settings.manage')->group(function () {
    Route::get('/', [OcrSettingController::class, 'index'])->name('index');
    Route::post('/', [OcrSettingController::class, 'update'])->name('update');
    Route::post('/test', [OcrSettingController::class, 'test'])->name('test');
});
```

### 3.10. View: UI Switch OCR Engine

```blade
{{-- resources/views/dokter/ocr-setting/index.blade.php --}}
@extends('layouts.Dokter')

@section('title', $pageName)

@section('content')
<div class="row">
    <div class="col-lg-6">
        <div class="card shadow-sm">
            <div class="card-body">
                <h5 class="card-title fw-bold">
                    <i class="mdi mdi-cog me-1"></i> {{ $pageName }}
                </h5>
                <p class="text-muted small">Pilih mekanisme OCR yang digunakan untuk ekstraksi teks dokumen.</p>

                <form action="{{ route('dokter.ocr-setting.update') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Mekanisme OCR Aktif</label>
                        <div class="d-flex gap-3">
                            @foreach($engines as $engine)
                            <div class="form-check">
                                <input class="form-check-input" type="radio"
                                    name="engine" value="{{ $engine->value }}"
                                    id="engine-{{ $engine->value }}"
                                    {{ $activeEngine === $engine->value ? 'checked' : '' }}>
                                <label class="form-check-label" for="engine-{{ $engine->value }}">
                                    <strong>{{ $engine->label() }}</strong>
                                    <br>
                                    <small class="text-muted">
                                        @if($engine->value === 'ocr_space')
                                            Menggunakan OCR Space API eksternal
                                        @else
                                            Menggunakan Google Gemini API (multimodal)
                                        @endif
                                    </small>
                                </label>
                            </div>
                            @endforeach
                        </div>
                        @error('engine')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="mdi mdi-content-save me-1"></i> Simpan Pengaturan
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card shadow-sm">
            <div class="card-body">
                <h5 class="card-title fw-bold">
                    <i class="mdi mdi-information-outline me-1"></i> Informasi
                </h5>
                <div class="alert alert-info mb-0">
                    <strong>OCR Space API:</strong> API OCR tradisional, cocok untuk dokumen teks biasa.<br><br>
                    <strong>Google Gemini API:</strong> AI multimodal, lebih akurat untuk dokumen kompleks,
                    bisa menggunakan custom prompt untuk ekstraksi terstruktur.
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
```

### 3.11. Modifikasi ProcessScanFile

```php
// app/Jobs/ProcessScanFile.php (perubahan)
public function handle(
    DocumentTypeProcessor $processor,
    FileConversionService $converter,
    PdfMergeService $merger,
    ScanLogger $logger,
): void {
    // ... (kode existing sampai resolution document type) ...

    // GANTI: Gunakan factory instead of direct OcrService
    $ocr = OcrEngineFactory::create(); // ← Perubahan utama

    $result = $ocr->extractText($uploadedFile);

    // ... (sisa kode existing, tidak berubah) ...
}
```

### 3.12. Config Tambahan

```php
// config/services.php (tambahkan)
'gemini' => [
    'model' => env('GEMINI_MODEL', 'gemini-1.5-flash'),
    'prompt' => env('GEMINI_OCR_PROMPT', 'Baca dan ekstrak seluruh teks dari dokumen ini secara akurat. Pertahankan format asli dokumen.'),
    'max_tokens' => env('GEMINI_MAX_TOKENS', 8192),
],
```

---

## 4. File yang Perlu Dibuat/Diubah

### File Baru (Buat)

| No | File | Fungsi |
|----|------|--------|
| 1 | `app/Enums/OcrEngineType.php` | Enum tipe engine OCR |
| 2 | `app/Services/Ocr/Contracts/OcrEngineInterface.php` | Kontrak interface |
| 3 | `app/Services/Ocr/OcrSpaceEngine.php` | Wrapper OCR Space |
| 4 | `app/Services/Ocr/GeminiEngine.php` | Implementasi Gemini |
| 5 | `app/Services/Ocr/OcrEngineFactory.php` | Factory pattern |
| 6 | `app/Models/OcrSetting.php` | Model pengaturan OCR |
| 7 | `app/Http/Controllers/Dokter/OcrSettingController.php` | Controller setting |
| 8 | `database/migrations/2026_08_10_000001_create_ocr_settings_table.php` | Migration |
| 9 | `database/seeders/OcrSettingSeeder.php` | Seeder default |
| 10 | `resources/views/dokter/ocr-setting/index.blade.php` | UI switch engine |

### File yang Diubah

| No | File | Perubahan |
|----|------|-----------|
| 1 | `app/Jobs/ProcessScanFile.php` | Gunakan `OcrEngineFactory::create()` |
| 2 | `routes/routers/dokter.php` | Tambah route OCR setting |
| 3 | `resources/views/layouts/partials/dokter/app-sidebar.blade.php` | Tambah menu Pengaturan OCR |
| 4 | `config/services.php` | Tambah config Gemini model & prompt |
| 5 | `database/seeders/DatabaseSeeder.php` | Panggil OcrSettingSeeder |

---

## 5. Urutan Implementasi

| Step | Task | Estimasi |
|------|------|----------|
| 1 | Buat `OcrEngineType` enum | 5 menit |
| 2 | Buat `OcrEngineInterface` | 5 menit |
| 3 | Buat `OcrSpaceEngine` (wrap existing) | 10 menit |
| 4 | Buat `GeminiEngine` | 20 menit |
| 5 | Buat `OcrEngineFactory` | 10 menit |
| 6 | Buat migration `ocr_settings` + seeder | 10 menit |
| 7 | Buat `OcrSetting` model | 10 menit |
| 8 | Buat `OcrSettingController` | 15 menit |
| 9 | Buat view UI switch engine | 15 menit |
| 10 | Tambah route + sidebar menu | 5 menit |
| 11 | Modifikasi `ProcessScanFile` | 5 menit |
| 12 | Update config `services.php` | 5 menit |
| 13 | Testing & debugging | 20 menit |
| **Total** | | **~2.5 jam** |

---

## 6. Pertimbangan Teknis

### Keamanan
- API key Gemini sudah ada di `.env` (`GOOGLE_GEMINI_API_KEY`)
- API key tidak di-expose ke frontend
- Gunakan permission `dokter.settings.manage` untuk akses halaman setting

### Performa
- Gemini API timeout lebih lama (120 detik vs 60 detik OCR Space)
- Pertimbangkan async processing untuk file besar
- Cache setting di Redis/Memory untuk mengurangi query

### Error Handling
- Fallback ke OCR Space jika Gemini gagal (optional, bisa dikonfigurasi)
- Log semua percobaan OCR ke `scan_logs` dengan field `ocr_engine`
- Tampilkan error di UI jika switch gagal

### Testing
- Unit test untuk setiap engine
- Test factory mengembalikan engine yang benar
- Test integrasi dengan API asli (manual/feature test)

### Masa Depan
- Tambah engine lain (Tesseract, Azure AI, dll) cukup implement interface
- Per-document-type engine config (bisa beda engine per jenis dokumen)
- A/B testing antar engine
