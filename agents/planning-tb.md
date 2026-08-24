# Planning: Implementasi Input TB (Trial Balance) pada Modul EQTax

## Overview

User ingin menambahkan fitur input **TB (Trial Balance / Neraca Saldo)** berupa **satu angka total PPN** yang diinput manual oleh user. Angka ini kemudian dibandingkan dengan total PPN SPT dan total PPN GL yang sudah ada di sistem.

**Pendekatan**: Input manual satu angka → simpan di tabel terpisah → tampilkan perbandingan di halaman ekualisasi.

---

## 1. Apa itu TB (Trial Balance)?

**Trial Balance** atau **Neraca Saldo** adalah laporan akuntansi yang berisi daftar seluruh akun dalam buku besar beserta saldo akhirnya pada periode tertentu. Dalam konteks PPN, yang relevan adalah saldo akun **PPN Masukan** dan **PPN Keluaran** di TB.

### Konteks dalam Ekualisasi Pajak

```
SPT PPN (laporan ke DJP)     →  Total PPN dari faktur pajak
GL (buku besar detail)        →  Total PPN dari jurnal accounting
TB (neraca saldo)             →  Total PPN dari saldo akun ( REFERENSI )
```

TB berfungsi sebagai **angka referensi** dari laporan keuangan perusahaan. Jika TB tidak cocok dengan SPT atau GL, berarti ada selisih yang perlu dijelaskan.

---

## 2. Arsitektur Saat Ini

```
eqtax_coretax_spt (SPT) ──┐
                           ├──→ EqualizationController ──→ eqtax_equalization_results
eqtax_gl (GL) ────────────┘
```

### Setelah Implementasi TB

```
eqtax_coretax_spt (SPT) ──┐
                           ├──→ EqualizationController ──→ eqtax_equalization_results
eqtax_gl (GL) ────────────┘
                                                        ←── eqtax_tb_data (input manual)
```

TB **bukan di-join** ke equalization per faktur pajak. TB hanya dibandingkan di **level total (summary)**.

---

## 3. Database Changes

### 3.1 Tabel Baru: `eqtax_tb_data`

```php
Schema::create('eqtax_tb_data', function (Blueprint $table) {
    $table->id();
    $table->string('period')->nullable();        // format "2026-02"
    $table->string('entity')->nullable();        // SBHO, TJMO, PLTR (opsional)
    $table->double('ppn_tb')->nullable();        // total PPN dari TB (angka user input)
    $table->text('keterangan')->nullable();      // catatan opsional
    $table->timestamps();

    $table->index('period');
    $table->index('entity');
});
```

### 3.2 Model Baru: `EQTAXTBData`

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EQTAXTBData extends Model
{
    protected $table = 'eqtax_tb_data';

    protected $fillable = ['period', 'entity', 'ppn_tb', 'keterangan'];

    public function scopePeriod($query, $period) {
        return $query->where('period', $period);
    }

    public function scopeEntity($query, $entity) {
        return $query->where('entity', $entity);
    }
}
```

---

## 4. Routes Baru

```php
// routes/routers/eqtax.php — tambahkan di prefix('equalization')

Route::post('/save-tb', [EqualizationController::class, 'saveTB'])
    ->name('equalization.save-tb');
```

Tidak perlu route GET terpisah — data TB di-load bersamaan saat ekualisasi diproses.

---

## 5. Controller Changes

### 5.1 `EqualizationController::equalization()` — Load data TB

Tambahkan di method `equalization()`, setelah build `$summary`:

```php
// Load data TB untuk periode ini
$tbData = \App\Models\EQTAXTBData::where('period', $period)
    ->when($entity, fn($q) => $q->where('entity', $entity))
    ->first();

$ppn_tb = $tbData->ppn_tb ?? null;

// Tambahkan ke summary
$summary['ppn_tb'] = $ppn_tb;
$summary['selisih_tb_vs_spt'] = $ppn_tb !== null ? $ppn_tb - $summary['total_ppn_spt'] : null;
$summary['selisih_tb_vs_gl'] = $ppn_tb !== null ? $ppn_tb - $summary['total_ppn_gl'] : null;
```

### 5.2 `EqualizationController::saveTB()` — Simpan data TB

```php
public function saveTB(Request $request)
{
    $validated = $request->validate([
        'period'  => 'required|string',
        'entity'  => 'nullable|string',
        'ppn_tb'  => 'required|numeric',
        'keterangan' => 'nullable|string',
    ]);

    // Upsert: jika sudah ada untuk periode+entity, update; jika belum, insert
    \App\Models\EQTAXTBData::updateOrCreate(
        [
            'period' => $validated['period'],
            'entity' => $validated['entity'] ?? null,
        ],
        [
            'ppn_tb' => $validated['ppn_tb'],
            'keterangan' => $validated['keterangan'] ?? null,
        ]
    );

    return response()->json([
        'success' => true,
        'message' => 'Data TB berhasil disimpan',
    ]);
}
```

---

## 6. View Changes

### 6.1 Equalization View (`eqtax/equalization/index.blade.php`)

#### A. Tambah Input Field TB

Di bawah form Proses Ekualisasi, tambahkan **card kecil** untuk input TB:

```html
@if(isset($summary))
<div class="card mb-4 border-primary">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-md-4">
                <label class="form-label fw-bold">PPN Trial Balance (TB)</label>
                <div class="input-group">
                    <span class="input-group-text">Rp</span>
                    <input type="number" class="form-control" id="ppn_tb_input"
                           value="{{ $summary['ppn_tb'] ?? '' }}"
                           placeholder="Masukkan total PPN dari TB">
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">Keterangan</label>
                <input type="text" class="form-control" id="tb_keterangan"
                       placeholder="Catatan (opsional)">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="button" class="btn btn-primary" id="btn-save-tb"
                        onclick="saveTB()">
                    <i class="fas fa-save me-1"></i> Simpan TB
                </button>
            </div>
        </div>
    </div>
</div>
@endif
```

#### B. Tambah Stat Card TB

Di baris stat cards pertama (Total PPN SPT, Total PPN GL, Total Selisih), tambahkan **2 card baru**:

```html
{{-- Card PPN TB --}}
<div class="col-md-3">
    <div class="card stat-card bg-purple-grad">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-white mb-1">PPN Trial Balance</h6>
                    <h4 class="text-white mb-0">
                        @if($summary['ppn_tb'] !== null)
                            Rp {{ number_format($summary['ppn_tb'], 0, ',', '.') }}
                        @else
                            -
                        @endif
                    </h4>
                </div>
                <div class="icon-overlay">
                    <i class="fas fa-balance-scale-left"></i>
                </div>
            </div>
            <small class="text-white-50">Data dari TB (input manual)</small>
        </div>
    </div>
</div>

{{-- Card Selisih TB vs SPT --}}
<div class="col-md-3">
    <div class="card stat-card {{ ($summary['selisih_tb_vs_spt'] ?? 0) >= 0 ? 'bg-emerald-grad' : 'bg-red-grad' }}">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-white mb-1">Selisih TB vs SPT</h6>
                    <h4 class="text-white mb-0">
                        @if($summary['selisih_tb_vs_spt'] !== null)
                            Rp {{ number_format(abs($summary['selisih_tb_vs_spt']), 0, ',', '.') }}
                        @else
                            -
                        @endif
                    </h4>
                </div>
                <div class="icon-overlay">
                    <i class="fas fa-not-equal"></i>
                </div>
            </div>
            <small class="text-white-50">
                @if($summary['selisih_tb_vs_spt'] !== null)
                    TB {{ $summary['selisih_tb_vs_spt'] >= 0 ? 'lebih besar' : 'lebih kecil' }} dari SPT
                @else
                    Input TB terlebih dahulu
                @endif
            </small>
        </div>
    </div>
</div>
```

#### C. Tambah JavaScript `saveTB()`

```javascript
function saveTB() {
    const ppnTb = $('#ppn_tb_input').val();
    const keterangan = $('#tb_keterangan').val();

    if (ppnTb === '' || isNaN(ppnTb)) {
        showToast('error', 'Masukkan angka PPN TB yang valid');
        return;
    }

    $.ajax({
        url: '{{ route("eqtax.equalization.save-tb") }}',
        type: 'POST',
        data: JSON.stringify({
            period: '{{ $summary["masa_pajak"] }}-{{ $summary["tahun"] }}',
            entity: '{{ $summary["entity"] !== "Semua" ? $summary["entity"] : "" }}',
            ppn_tb: parseFloat(ppnTb),
            keterangan: keterangan
        }),
        contentType: 'application/json',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function(response) {
            if (response.success) {
                showToast('success', response.message);
                location.reload();
            }
        },
        error: function(xhr) {
            showToast('error', xhr.responseJSON?.message || 'Gagal menyimpan');
        }
    });
}
```

---

## 7. Flow User

```
1. User proses ekualisasi (SPT vs GL)
2. Tabel hasil ekualisasi muncul + stat cards
3. Di bawah form, ada card "PPN Trial Balance"
4. User input angka total PPN dari TB mereka → klik "Simpan TB"
5. Stat card menampilkan perbandingan:
   - Total PPN SPT:    Rp 1.000.000.000
   - Total PPN GL:     Rp   950.000.000
   - PPN Trial Balance: Rp   980.000.000  ← dari input user
   - Selisih TB vs SPT: Rp  20.000.000
   - Selisih TB vs GL:  Rp  30.000.000
6. User bisa update angka TB kapan saja
```

---

## 8. File yang Perlu Diubah/Dibuat

| No | File | Aksi | Keterangan |
|----|------|------|-----------|
| 1 | `database/migrations/xxxx_create_eqtax_tb_data_table.php` | **BARU** | Tabel TB |
| 2 | `app/Models/EQTAXTBData.php` | **BARU** | Model TB |
| 3 | `routes/routers/eqtax.php` | **EDIT** | Tambah route `save-tb` |
| 4 | `app/Http/Controllers/EQTax/EqualizationController.php` | **EDIT** | Tambah `saveTB()`, update `equalization()` |
| 5 | `resources/views/eqtax/equalization/index.blade.php` | **EDIT** | Tambah input TB, stat card TB, JS |

---

## 9. CSS Tambahan

```css
.bg-purple-grad {
    background: linear-gradient(135deg, #8b5cf6 0%, #a78bfa 100%);
}
```

---

## 10. Checklist

- [x] Buat planning document ini
- [ ] Buat migration `eqtax_tb_data`
- [ ] Buat model `EQTAXTBData`
- [ ] Tambah route `eqtax.equalization.save-tb`
- [ ] Implement `EqualizationController::saveTB()`
- [ ] Update `EqualizationController::equalization()` — load data TB
- [ ] Tambah input field TB di equalization view
- [ ] Tambah stat card TB + Selisih TB vs SPT
- [ ] Tambah JavaScript `saveTB()` + toast
- [ ] Tambah CSS `bg-purple-grad`
- [ ] Test input TB → simpan → stat card update
- [ ] Test update TB → simpan → stat card update
- [ ] Validasi PHP syntax
