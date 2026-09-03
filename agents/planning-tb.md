# Planning: Implementasi Pencocokkan TB (Trial Balance) — Menu Terpisah

## Overview

Fitur pencocokkan TB (Trial Balance / Neraca Saldo) dijadikan **menu tersendiri** agar tidak bercampur dengan halaman Ekualisasi Pajak. User input angka total PPN dari TB, lalu sistem membandingkannya dengan total PPN SPT dan total PPN GL.

**Pendekatan**: Menu sidebar baru → halaman khusus TB → input manual → tampilkan perbandingan.

---

## 1. Arsitektur

```
Sidebar:
  ├── Dashboard
  ├── SPT Coretax
  ├── General Ledger
  ├── Ekualisasi Pajak        (SPT vs GL — tanpa input TB)
  ├── Pencocokkan TB           ← MENU BARU
  └── Back to Portal
```

### Flow User

```
1. User buka menu "Pencocokkan TB"
2. Pilih periode (masa_pajak + tahun)
3. Klik "Proses Pencocokkan"
4. Sistem load: Total PPN SPT + Total PPN GL + input user PPN TB
5. User input angka PPN TB → klik "Simpan TB"
6. Tabel pencocokkan muncul:
   - Total PPN SPT:     Rp 1.000.000.000
   - Total PPN GL:      Rp   950.000.000
   - PPN Trial Balance:  Rp   980.000.000
   - Selisih TB vs SPT:  Rp   20.000.000
   - Selisih TB vs GL:   Rp   30.000.000
7. User bisa update angka TB kapan saja
```

---

## 2. Database

Tabel `eqtax_tb_data` (sudah ada):

```php
Schema::create('eqtax_tb_data', function (Blueprint $table) {
    $table->id();
    $table->string('period')->nullable();        // format "2026-02"
    $table->double('ppn_tb')->nullable();        // total PPN dari TB
    $table->text('keterangan')->nullable();
    $table->timestamps();
    $table->index('period');
});
```

---

## 3. Routes

```php
// routes/routers/eqtax.php

Route::prefix('tb')->name('tb.')->group(function () {
    Route::get("/", [TBController::class, "index"])->name("index");
    Route::post("/process", [TBController::class, "process"])->name("process");
    Route::post("/save", [TBController::class, "save"])->name("save");
});
```

Route `save-tb` di equalization sudah dihapus.

---

## 4. Controller: `TBController`

**File**: `app/Http/Controllers/EQTax/TBController.php`

| Method | Fungsi |
|--------|--------|
| `index()` | Load distinct periods, render view |
| `process()` | Hitung total PPN SPT & GL, load TB, hitung selisih, return view |
| `save()` | Upsert data TB ke `eqtax_tb_data` |
| `getJurnalDatePrefix()` | Helper map bulan Indonesia ke numeric prefix |

---

## 5. View: `eqtax/tb/index.blade.php`

**File**: `resources/views/eqtax/tb/index.blade.php`

Struktur halaman:
- Info box penjelasan Trial Balance
- Filter form: Masa Pajak + Tahun + Tombol "Proses Pencocokkan"
- Input card: PPN TB (input angka) + Keterangan + Tombol "Simpan TB"
- 3 stat card: Total PPN SPT, Total PPN GL, PPN Trial Balance
- 2 stat card: Selisih TB vs SPT, Selisih TB vs GL
- 1 stat card: Masa Pajak

---

## 6. Sidebar

**File**: `resources/views/layouts/partials/eqtax/app-sidebar.blade.php`

Menu baru ditambahkan setelah "Ekualisasi Pajak":

```html
<li>
    <a href="{{ route('eqtax.tb.index') }}" class="waves-effect">
        <i class="bx bx-balloon"></i>
        <span key="t-tb">Pencocokkan TB</span>
    </a>
</li>
```

---

## 7. Perubahan di Equalization

### Dihapus dari EqualizationController:
- `use App\Models\EQTAXTBData` import
- Load TB data di method `equalization()`
- Method `saveTB()`

### Dihapus dari equalization view:
- Card input TB (PPN TB input + keterangan + tombol simpan)
- Stat card PPN TB, Selisih TB vs SPT, Selisih TB vs GL
- JavaScript `saveTB()`
- CSS `bg-purple-grad`

---

## 8. File yang Diubah/Dibuat

| No | File | Aksi | Keterangan |
|----|------|------|-----------|
| 1 | `database/migrations/xxxx_create_eqtax_tb_data_table.php` | **ADA** | Tabel TB (sudah ada) |
| 2 | `app/Models/EQTAXTBData.php` | **ADA** | Model TB (sudah ada) |
| 3 | `app/Http/Controllers/EQTax/TBController.php` | **BARU** | Controller khusus TB |
| 4 | `routes/routers/eqtax.php` | **EDIT** | Hapus save-tb dari equalization, tambah routes TB |
| 5 | `resources/views/eqtax/tb/index.blade.php` | **BARU** | View pencocokkan TB |
| 6 | `resources/views/layouts/partials/eqtax/app-sidebar.blade.php` | **EDIT** | Tambah menu "Pencocokkan TB" |
| 7 | `app/Http/Controllers/EQTax/EqualizationController.php` | **EDIT** | Hapus saveTB(), hapus load TB |
| 8 | `resources/views/eqtax/equalization/index.blade.php` | **EDIT** | Hapus semua TB-related code |

---

## 9. Checklist

- [x] Buat planning document ini
- [x] Buat `TBController`
- [x] Tambah routes TB (`eqtax.tb.index`, `eqtax.tb.process`, `eqtax.tb.save`)
- [x] Hapus route `save-tb` dari equalization
- [x] Buat view `eqtax/tb/index.blade.php`
- [x] Tambah menu "Pencocokkan TB" di sidebar
- [x] Hapus `saveTB()` dari EqualizationController
- [x] Hapus load TB dari `EqualizationController::equalization()`
- [x] Hapus input TB card dari equalization view
- [x] Hapus stat card TB dari equalization view
- [x] Hapus JavaScript `saveTB()` dari equalization view
- [x] Hapus CSS `bg-purple-grad` dari equalization view
- [x] Test halaman Pencocokkan TB → proses → input → simpan
- [x] Test halaman Ekualisasi → tidak ada TB code
