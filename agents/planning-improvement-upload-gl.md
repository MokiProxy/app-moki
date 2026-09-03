# Planning: Improvement Upload GL - Single Sheet Multi Header & Hapus Entity

## Overview

Perubahan pada modul **EQTax → Menu General Ledger (GL)**:

1. **Format file upload GL berubah**: dari *multi-sheet* (tiap sheet = satu entity, mis. PPNMO/PPNHO/PPNPLTR) menjadi **1 sheet saja** yang berisi **beberapa header table bertumpuk** (stacked) vertikal. Mapping kolom kolom data juga berubah.
2. **Hilangkan kolom & data `entity`** secara menyeluruh di GL **dan** fitur Ekualisasi (termasuk tabel `eqtax_equalization_results` & `eqtax_tb_data`) sesuai keputusan user.

File ini adalah **planning** saja - belum diimplementasikan.

---

## 1. Analisis Kondisi Saat Ini

### 1.1 Alur import GL yang aktif

```
resources/views/eqtax/gl/index.blade.php  (form upload → route eqtax.gl.import)
   → routes/routers/eqtax.php  (Route::post('import', [GLController::class,'import']))
   → app/Http/Controllers/EQTax/GLController.php::import()
        → app/Imports/PPNSheetImport.php (WithMultipleSheets, iterasi semua sheet)
             → app/Imports/PPNSingleSheetImport.php (ToCollection, per sheet)
                  mapping kolom berbeda per sheet (PPNMO offset +1, PPNHO offset 0)
                  entity = entityMap[sheetName] (PPNMO=TJMO, PPNHO=SBHO, PPNPLTR=PLTR)
   → DB::transaction → EQTAXGL::insert(array_chunk 500)
```

- `GLController::import()` : `use App\Imports\PPNSheetImport;` → `new PPNSheetImport($request->file('file'))`.
- `app/Imports/GLReportImport.php` : **legacy / tidak terpakai** (constructor `PPNSheetImport($sheetName)` menyalahi signature `PPNSheetImport(UploadedFile)`). Tidak ada referensi dari controller/manapun → bisa diabaikan (opsional dihapus).

### 1.2 Struktur database tabel `eqtax_gl`

Dari migration:
- `2026_08_15_143005_create_e_q_t_a_x_g_l_s_table.php` (base)
- `2026_08_16_000001_add_entity_to_eqtax_gl_table.php` (tambah `entity`, index `entity`, `no_faktur_pajak`)

Kolom saat ini (model `app/Models/EQTAXGL.php`):

| Kolom | Tipe | Sumber lama |
|-------|------|-------------|
| id | bigint PK | auto |
| sheet | string nullable | nama sheet (PPNMO/PPNHO/…) |
| **entity** | string nullable | entityMap (TJMO/SBHO/PLTR) → **AKAN DIHAPUS** |
| no_supplier | string | - |
| nama_supplier | string | - |
| jurnal_date | string (YYYYMMDD) | dipakai filter periode via `LIKE 'YYYYMM%'` |
| jurnal_no, invoice_date, invoice_no, invoice_item, no_faktur_pajak | string | - |
| dpp, ppn | float | - |
| keterangan | string | - |

---

## 2. Format File Baru (Single Sheet, Multiple Header)

### 2.1 Analisis file contoh `C:\Users\Wahid Aziz\Downloads\eqtax gl7.xlsx`

Struktur: **1 sheet** bernama `PPN MO`. Di dalamnya ada **4 blok table** yang ditandai header tabel 2 baris (`Supplier No / Nama Supplier / ...` + `Date / No / Date / No / Item`).

Posisi header blok (baris 1-index):
- Baris 10 : header blok #1, data mulai baris 13
- Baris 495: header blok #2, data mulai baris 498
- Baris 510: header blok #3, data mulai baris 513
- Baris 552: header blok #4, data mulai baris 555

Setiap blok diakhiri baris kalkulasi:
- `Saldo Awal` (DPP & PPN terisi, **no_faktur_pajak kosong**)
- `Total Bulan Berjalan` (DPP & PPN terisi, **no_faktur_pajak kosong**)
- `Total Sampai Dengan Bulan Berjalan` (DPP & PPN terisi, **no_faktur_pajak kosong**)

### 2.2 Mapping kolom (baris data) → DB

| Kolom Excel | Index array (0-based) | DB column |
|-------------|----------------------|-----------|
| B | 1 | no_supplier |
| C | 2 | nama_supplier |
| E | 4 | jurnal_date |
| G | 6 | jurnal_no |
| H | 7 | invoice_date |
| J | 9 | invoice_no |
| K | 10 | invoice_item |
| L | 11 | no_faktur_pajak |
| M | 12 | dpp |
| N | 13 | ppn |
| O | 14 | keterangan |

> Catatan: baris header tabel punya nilai `No FP` di kolom L (index 11). Kolom `sheet` diisi nama sheet (mis. `PPN MO`). **Tanpa kolom entity.**

### 2.3 Aturan filter baris data

Data diambil **hanya jika `no_faktur_pajak` (kolom L) terisi**, dengan pengecualian nilai `"No FP"` (header tabel) **tidak** dihitung sebagai data.

Rumus filter yang diusulkan (per baris, setelah trim):
```
$noFp = trim((string)$row[11]);
if ($noFp === '' || strcasecmp($noFp, 'No FP') === 0) {
    continue; // lewati: baris kosong, baris header, Saldo Awal, Total ... dst.
}
```

Dengan filter ini otomatis terminasi baris yang TIDAK boleh masuk:
- Baris kosong / blank → `no_faktur_pajak` kosong → dilewati ✓
- Baris header tabel (`No FP`) → dilewati ✓
- Baris `Saldo Awal` → `no_faktur_pajak` kosong → dilewati ✓
- Baris `Total Bulan Berjalan` / `Total Sampai Dengan Bulan Berjalan` → `no_faktur_pajak` kosong → dilewati ✓
- Baris data valid → `no_faktur_pajak` terisi & ≠ "No FP" → **masuk** ✓

Dukungan pembuktian (data contoh, baris 12/13/492/497):
- Baris 12 (`Saldo Awal`): index 11 kosong → skip
- Baris 13: index 11 = `04002600221494087` → masuk
- Baris 492 (`Total Bulan Berjalan`): index 11 kosong → skip

> Deteksi header blok berbasis nilai `No FP` pada kolom L sudah cukup **tanpa** perlu deteksi baris/posisi tertentu, karena baris header selalu berisi `No FP` di kolom L dan baris data tidak akan berisi persis "No FP".

### 2.4 Parsing numbers

- `dpp` dan `ppn` berformat string ribuan/id seperti ` 105,000 ` atau ` 924,529,387,841 `. Wajib dibersihkan (hapus spasi, koma ribuan) lalu `(float)`.
- Contoh: `"1,187,820,784"` → `1187820784`.
- Beberapa nilai total seperti `105.745.329.271,25` (titik pemisah ribuan, koma desimal) hanya muncul di baris *Total* yang sudah dilewati filter, jadi tidak perlu ditangani di baris data. Tetap buat helper pembersih yang aman untuk `dpp`/`ppn`.

---

## 3. Rencana Perubahan File

### 3.1 Importer GL baru

Ganti `PPNSheetImport` + `PPNSingleSheetImport` menjadi importer **single-sheet** baru yang membaca semua baris dalam 1 sheet dan menerapkan mapping + filter di atas.

**Opsi implementasi importer** (pilih salah satu yang paling cocok):
- **Opsi A (direkomendasikan)**: Buat class baru `app/Imports/GLImport.php` (`ToCollection`, atau `ToArray`), load sheet pertama via `IOFactory` seperti pola lama, iterasi semua baris, terapkan mapping & filter, kumpulkan ke `public array $result`. Lalu update `GLController::import()`.
- **Opsi B**: Dengan `WithMultipleSheets` namun hanya ambil sheet pertama.

> Rekomendasi: **hapus** `PPNSheetImport.php`, `PPNSingleSheetImport.php`, dan `GLReportImport.php` (legacy) agar tidak ada kode mati yang membingungkan, lalu buat `GLImport.php` baru.

Contoh kerangka `GLImport.php`:
```php
class GLImport implements ToCollection
{
    public array $result = [];

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            $noFp = trim((string)($row[11] ?? ''));
            if ($noFp === '' || strcasecmp($noFp, 'No FP') === 0) {
                continue;
            }
            $this->result[] = [
                'sheet'          => $this->sheetName, // nama sheet pertama
                'no_supplier'    => trim((string)($row[1]  ?? '')),
                'nama_supplier'  => trim((string)($row[2]  ?? '')),
                'jurnal_date'    => trim((string)($row[4]  ?? '')),
                'jurnal_no'      => trim((string)($row[6]  ?? '')),
                'invoice_date'   => trim((string)($row[7]  ?? '')),
                'invoice_no'     => trim((string)($row[9]  ?? '')),
                'invoice_item'   => trim((string)($row[10] ?? '')),
                'no_faktur_pajak'=> $noFp,
                'dpp'            => $this->parseNumber($row[12] ?? 0),
                'ppn'            => $this->parseNumber($row[13] ?? 0),
                'keterangan'     => trim((string)($row[14] ?? '')),
            ];
        }
    }

    private function parseNumber($value): float
    {
        $clean = str_replace(['.', ' ', ','], '', (string)$value); // sesuaikan logika ribuan/desimal
        return (float)$clean;
    }
}
```

> Catatan pembersih angka: format data konsisten `1,234,567` (koma = ribuan, tanpa desimal). Karena tidak ada desimal di baris data valid, `str_replace([',',' '], '', ...)` aman. (Perhatikan jangan asal `str_replace('.')` karena ada baris Total berformat titik ribuan yang sudah diskip.)

### 3.2 Model `app/Models/EQTAXGL.php`

- Hapus `"entity"` dari `$fillable`.
- `sheet` tetap dipertahankan (diisi nama sheet tunggal).

### 3.3 Controller `app/Http/Controllers/EQTax/GLController.php`

- `import()`: ganti `new PPNSheetImport(...)` → `new GLImport(...)`. Logika `DB::transaction` & chunk tidak berubah. Update `use` import.
- `index()`: **hapus** blok filter `entity` (baris 30-32), hapus `$entitySummary` (baris 50-53), hapus `$entities` (baris 53). Hapus variabel dari `compact`.
- `updateField`: `$allowedFields` otomatis menyesuaikan karena diambil dari `$fillable` → tidak perlu perubahan manual.

### 3.4 Migration: drop kolom `entity`

Buat migration baru, contoh:

```php
Schema::table('eqtax_gl', function (Blueprint $table) {
    $table->dropIndex(['entity']);
    $table->dropColumn('entity');
});
```

### 3.5 Fitur Ekualisasi - hapus entity (menyeluruh)

**`app/Http/Controllers/EQTax/EqualizationController.php`**
- `index()`: hapus `$distinctEntities` (baris 27-31) & dari `compact`.
- `equalization()`: hapus `$entity` retrieval, filter `->when($entity...)` pada query GL, kolom `entity` pada select & groupBy, `'entities'` pada hasil, panggil `saveResults($results, $period)` (tanpa entity), `'entity' => 'Semua'` di summary, filter `$tbData` by entity.
- `export()`: hapus filter entity, kolom entity di query/groupBy, `'entities'`, `'entity'` di summary, dan suffix entity pada nama file.
- `saveTB()`: hapus `'entity'` dari validated; `updateOrCreate` cukup `['period' => ...]`.
- `saveResults()`: hapus parameter `?string $entity`; delete hanya by `period`; insert tanpa kolom `entity`.
- `getJurnalDatePrefix()`: tidak berubah.

**`app/Http/Controllers/EQTax/DashboardController.php`**
- `index()`: hapus `$entitySummary` (GL), `$filterEntity`, filter `where('entity', ...)` pada equalization query, `$distinctEntities` (dari `EQTAXEqualizationResult`), dan variabelnya dari `compact`. Hapus `$dt->entity` usage di resource.
- `getFilteredData()`: hapus `$filterEntity` & filter by entity.

**Model**
- `app/Models/EQTAXEqualizationResult.php`: hapus `"entity"` dari `$fillable`, hapus `scopeEntity`.
- `app/Models/EQTAXTBData.php`: hapus `"entity"` dari `$fillable`, hapus `scopeEntity`.

**Migration drop entity**
- Migration baru untuk `eqtax_equalization_results` (drop index `entity` + kolom).
- Migration baru untuk `eqtax_tb_data` (drop index `entity` + kolom).

> Catatan: `eqtax_coretax_spt.entity` **TIDAK** dihapus — kolom ini terkait tab/sheet SPT Coretax (dipakai `SPTCoretaxController` untuk tab). Di luar scope perubahan GL/ekualisasi ini.

### 3.6 View

**`resources/views/eqtax/gl/index.blade.php`**
- Hapus filter `Entity` (baris 149-157).
- Hapus kartu summary entity `@foreach($entitySummary...)` (baris 232-249) + blok stat yang mereferensikan entity.
- Hapus kolom tabel `<th>Entity</th>` & kolom entity (baris 298) → sesuaikan `colspan` (baris 313) dari 14 → 13.
- `compact` dari controller sudah tanpa `entities`/`entitySummary`.

**`resources/views/eqtax/equalization/index.blade.php`**
- Hapus dropdown `Entity (Opsional)` (baris ~180-188), `$distinctEntities` loop.
- Hapus kartu/baris Entity di summary (baris ~348-349).
- Hapus kolom `<th>Entity</th>` pada tabel hasil (baris 455) & seluruh `$dt->entities` / entity di baris data.
- Hapus parameter `entity` pada link export (baris 147).

**`resources/views/eqtax/dashboard/index.blade.php`**
- Hapus filter `Entity`, summary `$dt->entity`, dan `$distinctEntities` dropdown.
- Hapus `dt.entity` pada JS render (baris 637).

### 3.7 Export

**`app/Exports/EqualizationExport.php`**
- `writeTitleBlock`: string meta `' | Entity: '.$this->summary['entity']` → hapus bagian entity (baris 141). Data `entities` dihapus dari `$this->data` sehingga kolom entity di sheet tidak ada.

---

## 4. Data Lama / Backward Compatibility

- Data `eqtax_gl` lama yang sudah ter-import berisi nilai `entity` (TJMO/SBHO/PLTR). Migration drop kolom akan menghapus kolom tsb. Jika ada kebutuhan audit, **backup tabel** sebelum migrasi disarankan.
- Data `eqtax_equalization_results` & `eqtax_tb_data` lama punya entity per periode. Setelah kolom di-drop, histori ekualisasi per-entity tidak lagi dibedakan (menjadi per-periode saja). Perlu konfirmasi reset/refresh data ekualisasi (jalankan ulang proses ekualisasi).

---

## 5. Template / Contoh yang Berpengaruh (tanpa perubahan)

- `resources/views/layouts/EQTax.blade.php`, sidebar — menu GL tetap sama, tidak berubah.
- `routes/routers/eqtax.php` — route GL import/update-field tetap sama.

---

## 6. Checklist Implementasi

- [ ] Buat `app/Imports/GLImport.php` (single sheet, mapping kolom B..O, filter no_faktur_pajak ≠ kosong & ≠ "No FP", parse dpp/ppn).
- [ ] Hapus `PPNSheetImport.php`, `PPNSingleSheetImport.php`, `GLReportImport.php` (legacy).
- [ ] Update `GLController::import()` → gunakan `GLImport`.
- [ ] Model `EQTAXGL.php`: hapus `entity` dari `$fillable`.
- [ ] Migration drop `entity` di `eqtax_gl` + index.
- [ ] Migration drop `entity` di `eqtax_equalization_results` + index.
- [ ] Migration drop `entity` di `eqtax_tb_data` + index.
- [ ] Update `EqualizationController` (index/equalization/export/saveTB/saveResults) tanpa entity.
- [ ] Update `DashboardController` (index/getFilteredData) tanpa entity.
- [ ] Model `EQTAXEqualizationResult` & `EQTAXTBData`: hapus `entity` + scope.
- [ ] View `gl/index.blade.php`: hapus filter entity, summary entity, kolom entity, sesuaikan colspan.
- [ ] View `equalization/index.blade.php`: hapus dropdown entity, kolom entity, link export.
- [ ] View `dashboard/index.blade.php`: hapus filter entity & kolom entity (termasuk JS).
- [ ] `EqualizationExport.php`: hapus entity dari meta & data.
- [ ] Backup DB lalu `php artisan migrate`.
- [ ] Validasi `php -l` pada semua file PHP yang diubah.
- [ ] Uji import file `eqtax gl7.xlsx` → cek jumlah baris & nilai no_faktur_pajak (baris header "No FP", Saldo Awal, dan Total dilewati).
- [ ] Uji ekualisasi per periode tanpa entity & dashboard.

## 7. Risiko

- **Penghapusan histori entity**: data equalization/TB lama dikunci per-entity; setelah drop, hanya per-periode. Perlu re-run ekualisasi untuk data baru.
- **Parsing angka**: harus diuji dengan file nyata (ribuan tidak konsisten antara baris data vs baris Total). Pastikan helper numbers hanya dipakai pada baris data valid.
- **Dependensi hidden**: pastikan tidak ada query lain yang masih `select('entity')` dari tabel yang kolomnya di-drop (grep `entity` dan `->entity` di seluruh codebase setelah implementasi).
