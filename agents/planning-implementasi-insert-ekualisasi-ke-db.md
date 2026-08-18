# Planning: Implementasi Insert Hasil Ekualisasi ke Database

## Ringkasan Masalah

Saat ini, hasil ekualisasi pajak **dihitung ulang setiap kali** user mengakses halaman ekualisasi. Proses equalization di `EqualizationController::equalization()` melakukan query ke `eqtax_coretax_spt` dan `eqtax_gl`, lalu melakukan pencocokan secara real-time. Tabel `eqtax_equalization_results` sudah dimigrasikan ke database tetapi **belum terpakai sama sekali** — tidak ada Model Eloquent, tidak ada logic insert, dan tidak ada endpoint untuk mengakses data tersimpan.

**Akibatnya:**
- Setiap request ekualisasi melakukan query berat (aggregate GL + filter SPT + loop pencocokan)
- Tidak ada riwayat hasil ekualisasi untuk audit trail
- User harus menjalankan ulang proses ekualisasi untuk melihat hasil yang sama
- Tidak ada cara membandingkan hasil ekualisasi antar periode

---

## Struktur Database Eksisting

### Tabel `eqtax_equalization_results` (sudah ada, belum terpakai)

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | bigint (PK) | Auto increment |
| period | string, indexed | Periode (e.g. "2026-02") |
| entity | string, indexed | Entity (SBHO, TJMO, PLTR) |
| no_faktur_pajak | string, indexed | Nomor faktur pajak |
| nama_penjual | string | Nama penjual |
| dpp_spt | bigInteger | DPP dari SPT |
| dpp_gl | double | DPP dari GL |
| ppn_spt | bigInteger | PPN dari SPT |
| ppn_gl | double | PPN dari GL |
| selisih_ppn | double | Selisih PPN |
| status | string, indexed | MATCH, SPT_ONLY, GL_ONLY |
| keterangan | text | Keterangan tambahan |
| timestamps | - | created_at, updated_at |

### Catatan: Kolom `period` vs `masa_pajak` + `tahun`
Tabel results menggunakan field `period` (string "2026-02") sedangkan SPT/GL menggunakan `masa_pajak` + `tahun` terpisah. Perlu mapping saat insert.

---

## Alur yang Direncanakan

### Saat Ini (Real-time)
```
User klik "Proses Ekualisasi"
  → Controller query SPT + GL
  → Pencocokan di memory
  → Hasil ditampilkan (tidak disimpan)
```

### Setelah Implementasi
```
User klik "Proses Ekualisasi"
  → Controller query SPT + GL
  → Pencocokan di memory
  → HASIL DISIMPAN ke eqtax_equalization_results
  → Ditampilkan dari data tersimpan

User buka halaman ekualisasi lagi
  → Cek apakah ada data tersimpan untuk periode itu
  → Jika ada, tampilkan dari DB (tanpa proses ulang)
  → Jika tidak, proses baru
```

---

## File yang Perlu Dibuat

### 1. Model: `app/Models/EQTAXEqualizationResult.php`

Eloquent model untuk tabel `eqtax_equalization_results`.

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EQTAXEqualizationResult extends Model
{
    protected $table = 'eqtax_equalization_results';

    protected $fillable = [
        'period',
        'entity',
        'no_faktur_pajak',
        'nama_penjual',
        'dpp_spt',
        'dpp_gl',
        'ppn_spt',
        'ppn_gl',
        'selisih_ppn',
        'status',
        'keterangan',
    ];

    // Scope: filter by period (format "YYYY-MM")
    public function scopePeriod($query, $period)
    {
        return $query->where('period', $period);
    }

    // Scope: filter by entity
    public function scopeEntity($query, $entity)
    {
        return $query->where('entity', $entity);
    }

    // Scope: filter by status
    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    // Helper: convert masa_pajak + tahun ke format period "YYYY-MM"
    public static function toPeriod($masaPajak, $tahun): string
    {
        $monthMap = [
            'Januari' => '01', 'Februari' => '02', 'Maret' => '03',
            'April' => '04', 'Mei' => '05', 'Juni' => '06',
            'Juli' => '07', 'Agustus' => '08', 'September' => '09',
            'Oktober' => '10', 'November' => '11', 'Desember' => '12',
        ];
        $month = $monthMap[$masaPajak] ?? '01';
        return "{$tahun}-{$month}";
    }
}
```

**Lokasi:** `app/Models/EQTAXEqualizationResult.php`

---

## File yang Perlu Dimodifikasi

### 2. Controller: `app/Http/Controllers/EQTax/EqualizationController.php`

Modifikasi method `equalization()` dan tambah method baru.

#### 2a. Ubah method `equalization()` — tambah logic save ke DB

```php
public function equalization(Request $request)
{
    $request->validate([
        'masa_pajak' => 'required|string',
        'tahun' => 'required|string',
    ]);

    $masaPajak = $request->input('masa_pajak');
    $tahun = $request->input('tahun');
    $entity = $request->input('entity');
    $period = EQTAXEqualizationResult::toPeriod($masaPajak, $tahun);

    // ============================================
    // CEK DATA TERSIMPAN — jika sudah ada, tampilkan dari DB
    // ============================================
    $existingQuery = EQTAXEqualizationResult::where('period', $period);
    if ($entity) {
        $existingQuery->where('entity', $entity);
    }

    if ($existingQuery->exists()) {
        // Ambil dari database (sudah diproses sebelumnya)
        $results = $existingQuery->orderByDesc('selisih_ppn')->get();
        $summary = $this->buildSummary($results, $masaPajak, $tahun, $entity);
        // ... return view
    }

    // ============================================
    // PROSES EKUALISASI BARU (logic existing tetap sama)
    // ============================================
    // ... (query SPT + GL + pencocokan — tidak diubah)

    // ============================================
    // SIMPAN HASIL KE DATABASE
    // ============================================
    $this->saveResults($results, $period, $entity);

    // ... return view
}
```

#### 2b. Tambah method `saveResults()`

```php
private function saveResults($results, string $period, ?string $entity): void
{
    // Hapus hasil lama untuk periode + entity yang sama (replace)
    EQTAXEqualizationResult::where('period', $period)
        ->when($entity, fn($q) => $q->where('entity', $entity))
        ->delete();

    // Insert batch
    $records = $results->map(fn($r) => [
        'period' => $period,
        'entity' => $r->entities ?? $entity,
        'no_faktur_pajak' => $r->no_faktur_pajak,
        'nama_penjual' => $r->nama_penjual,
        'dpp_spt' => $r->dpp_spt,
        'dpp_gl' => $r->dpp_gl,
        'ppn_spt' => $r->ppn_spt,
        'ppn_gl' => $r->ppn_gl,
        'selisih_ppn' => $r->selisih_ppn,
        'status' => $r->status,
        'keterangan' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ])->toArray();

    // Chunk insert untuk data besar
    $chunks = collect($records)->chunk(500);
    foreach ($chunks as $chunk) {
        EQTAXEqualizationResult::insert($chunk->toArray());
    }
}
```

#### 2c. Tambah method `buildSummary()`

```php
private function buildSummary($results, string $masaPajak, string $tahun, ?string $entity): array
{
    return [
        'total_spt' => $results->where('status', '!=', 'GL_ONLY')->count(),
        'total_gl' => $results->where('status', '!=', 'SPT_ONLY')->count(),
        'total_ppn_spt' => $results->sum('ppn_spt'),
        'total_ppn_gl' => $results->sum('ppn_gl'),
        'total_selisih' => $results->sum('selisih_ppn'),
        'count_match' => $results->where('status', 'MATCH')->count(),
        'count_spt_only' => $results->where('status', 'SPT_ONLY')->count(),
        'count_gl_only' => $results->where('status', 'GL_ONLY')->count(),
        'masa_pajak' => $masaPajak,
        'tahun' => $tahun,
        'entity' => $entity ?? 'Semua',
    ];
}
```

#### 2d. Tambah method `reprocess()` — untuk proses ulang

```php
public function reprocess(Request $request)
{
    $request->validate([
        'masa_pajak' => 'required|string',
        'tahun' => 'required|string',
    ]);

    $masaPajak = $request->input('masa_pajak');
    $tahun = $request->input('tahun');
    $entity = $request->input('entity');
    $period = EQTAXEqualizationResult::toPeriod($masaPajak, $tahun);

    // Hapus data lama
    EQTAXEqualizationResult::where('period', $period)
        ->when($entity, fn($q) => $q->where('entity', $entity))
        ->delete();

    // Redirect ke proses ekualisasi (akan diproses ulang)
    return redirect()->route('eqtax.equalization.process', [
        'masa_pajak' => $masaPajak,
        'tahun' => $tahun,
        'entity' => $entity,
    ]);
}
```

#### 2e. Import tambahan di atas

```php
use App\Models\EQTAXEqualizationResult;
```

---

### 3. Routes: `routes/routers/eqtax.php`

Tambah route untuk reprocess dan historical results.

```php
// Proses ulang ekualisasi (hapus data lama, proses baru)
Route::post('/eqtax/equalization/reprocess', [EqualizationController::class, 'reprocess'])
    ->name('eqtax.equalization.reprocess');

// Lihat hasil ekualisasi tersimpan (historical)
Route::get('/eqtax/equalization/history', [EqualizationController::class, 'history'])
    ->name('eqtax.equalization.history');
```

**Note:** Route ini harus diletakkan **sebelum** route parameter `/{period}` jika ada, atau diletakkan di posisi yang tidak konflik.

---

### 4. View: `resources/views/eqtax/equalization/index.blade.php`

Tambahkan elemen UI berikut:

#### 4a. Indikator data tersimpan
Di bagian atas hasil ekualisasi, tampilkan badge/info bahwa data berasal dari database tersimpan:
```blade
@if(isset($fromDatabase) && $fromDatabase)
    <div class="alert alert-info">
        <i class="fas fa-database"></i>
        Menampilkan data tersimpan dari periode {{ $summary['masa_pajak'] }} {{ $summary['tahun'] }}.
        <a href="{{ route('eqtax.equalization.reprocess', [...]) }}">Proses Ulang</a>
    </div>
@endif
```

#### 4b. Tombol "Proses Ulang"
Tambahkan tombol di samping tombol "Proses" yang sudah ada:
```blade
@if(isset($fromDatabase) && $fromDatabase)
    <form action="{{ route('eqtax.equalization.reprocess') }}" method="POST" class="d-inline">
        @csrf
        <input type="hidden" name="masa_pajak" value="{{ $summary['masa_pajak'] }}">
        <input type="hidden" name="tahun" value="{{ $summary['tahun'] }}">
        <input type="hidden" name="entity" value="{{ $summary['entity'] }}">
        <button type="submit" class="btn btn-warning">
            <i class="fas fa-sync"></i> Proses Ulang
        </button>
    </form>
@endif
```

#### 4c. Halaman History (opsional)
Buat view baru `resources/views/eqtax/equalization/history.blade.php` untuk menampilkan daftar periode yang sudah diproses:
- Tabel: Period | Entity | Jumlah FP | Total Selisih | Tanggal Proses | Aksi
- Klik periode → tampilkan detail hasil ekualisasi

---

### 5. Export: `app/Exports/EqualizationExport.php`

**Tidak perlu diubah** — export tetap menggunakan data yang sudah di-query. Namun bisa dimodifikasi untuk mendukung export dari data tersimpan di DB.

---

## Urutan Implementasi

### Tahap 1: Model & Basic Save (Estimasi: 30 menit)
1. [ ] Buat `app/Models/EQTAXEqualizationResult.php`
2. [ ] Tambah import di `EqualizationController.php`
3. [ ] Tambah method `saveResults()` di controller
4. [ ] Tambah method `buildSummary()` di controller
5. [ ] Modifikasi method `equalization()` untuk call `saveResults()` setelah proses

### Tahap 2: Load dari DB (Estimasi: 30 menit)
1. [ ] Modifikasi method `equalization()` — cek data tersimpan sebelum proses
2. [ ] Tambah variabel `$fromDatabase` untuk view
3. [ ] Update view `equalization/index.blade.php` — tampilkan indikator + tombol reprocess

### Tahap 3: Reprocess & History (Estimasi: 30 menit)
1. [ ] Tambah method `reprocess()` di controller
2. [ ] Tambah route reprocess di `eqtax.php`
3. [ ] Tambah method `history()` di controller (opsional)
4. [ ] Tambah route history di `eqtax.php` (opsional)
5. [ ] Buat view `history.blade.php` (opsional)

### Tahap 4: Testing & Validation (Estimasi: 30 menit)
1. [ ] Test proses ekualisasi baru → data tersimpan ke DB
2. [ ] Test buka ulang periode sama → load dari DB
3. [ ] Test proses ulang → data lama terhapus, data baru tersimpan
4. [ ] Test export dari data tersimpan
5. [ ] Test dengan filter entity
6. [ ] Test dengan data besar (performance batch insert)

---

## Pertimbangan Teknis

### 1. Replace Strategy
Saat user proses ulang untuk periode + entity yang sama, data lama **dihapus** lalu di-insert ulang. Ini lebih sederhana daripada upsert karena:
- Data hasil ekualisasi bersifat snapshot (bukan mutable)
- Setiap kali proses menghasilkan dataset lengkap baru
- Menghindari inkonsistensi data parsial

### 2. Batch Insert
Menggunakan `chunk(500)` untuk insert data dalam batch agar tidak timeout pada dataset besar. Satu periode bisa menghasilkan ratusan hingga ribuan baris hasil ekualisasi.

### 3. Period Format
Tabel results menggunakan format `period` ("2026-02") sedangkan input user menggunakan `masa_pajak` + `tahun` terpisah. Mapping diperlukan:
- `EQTAXEqualizationResult::toPeriod('Februari', '2026')` → `"2026-02"`

### 4. Entity pada Hasil
Kolom `entity` di tabel results bisa berisi:
- Entity spesifik jika user filter tertentu
- Gabungan entity jika user proses semua ("SBHO, TJMO, PLTR")
- Perlu dipertimbangkan: apakah menyimpan per-entity atau gabungan?

**Rekomendasi:** Simpan **per-baris** berdasarkan entity asli dari GL aggregate (bukan gabungan). Ini memungkinkan filter entity tanpa proses ulang.

### 5. Relasi Tidak Berubah
Tabel `eqtax_equalization_results` tetap tanpa foreign key — hanya koneksi logis via `no_faktur_pajak`. Ini konsisten dengan design existing.

---

## Checklist Akhir

- [ ] Model `EQTAXEqualizationResult` dibuat
- [ ] Method `saveResults()` berfungsi
- [ ] Method `buildSummary()` berfungsi
- [ ] Method `equalization()` sudah menyimpan ke DB
- [ ] Method `equalization()` load dari DB jika sudah ada
- [ ] View menampilkan indikator data tersimpan
- [ ] Tombol "Proses Ulang" berfungsi
- [ ] Route reprocess ditambahkan
- [ ] History view dibuat (opsional)
- [ ] Export dari data tersimpan berfungsi
- [ ] Testing semua skenario selesai
