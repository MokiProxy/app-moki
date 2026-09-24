# Planning: Perubahan Modul ERKAP - Biaya Rutin

## Ringkasan Permintaan

1. Hilangkan ringkasan anggaran **Per Satuan Kerja** di halaman Biaya Rutin.
2. Semua dropdown memiliki fitur **search** (Select2).
3. Input **Cost Center** di Biaya Rutin dipecah menjadi 2 dropdown: **Swakelola** dan **Non Swakelola**.
4. Hilangkan input & data **Kategori Biaya** dari Biaya Rutin.

## Analisis Codebase

### Struktur terkait Biaya Rutin
- Model: `app/Models/Erkap/RoutineCost.php` (tabel `erkap_routine_costs`)
- Controller: `app/Http/Controllers/Erkap/RoutineCostController.php`
- Request: `StoreRoutineCostRequest`, `UpdateRoutineCostRequest`
- View: `resources/views/erkap/routine-cost/{index,create,edit,consolidate}.blade.php`
- Cost Center: model `app/Models/Erkap/CostCenter.php` (tabel `cost_centers`), seeder `CostCentersSeeder`
- Layout: `resources/views/layouts/Erkap.blade.php` + partials di `layouts/partials/erkap/`
- Select2 sudah tersedia di `public/libs/select2/` (belum dipakai di modul ERKAP)

### Format Kode Cost Center
`F 01 20210 510 9100` (1-2-5-3-4 digit). Segmen ke-4 (3 digit sebelum 4 digit terakhir) adalah kode cost center.
Aturan: segmen == `510` => **Swakelola**, selain itu => **Non Swakelola**.
Implementasi: `substr($code, -7, 3)`.

## Rencana Implementasi

### 1. Hapus ringkasan Per Satuan Kerja
- `RoutineCostController::index()`: hapus query `$totalByDivision` + variabel compact.
- `index.blade.php`: hapus card "Per Satuan Kerja", sesuaikan grid card tersisa.

### 2. Dropdown searchable (Select2) untuk seluruh modul ERKAP
- Tambah CSS Select2 di `layouts/partials/erkap/app-head.blade.php`.
- Buat partial `layouts/partials/erkap/app-plugin.blade.php` berisi JS Select2 + inisialisasi global `$('.page-content select').select2({width:'100%'})`.
- Include partial tersebut di `layouts/Erkap.blade.php` setelah `app-plugin`.
- Aman: tidak ada view ERKAP yang memanipulasi `<option>` secara dinamis; hanya read `.val()`.

### 3. Dua dropdown Cost Center (Swakelola / Non Swakelola)
- `CostCenter` model: tambah `costCenterCode()` & `isSwakelola()`.
- Controller `create()`/`edit()`: group cost center menjadi `swakelolaCostCenters` & `nonSwakelolaCostCenters` via helper privat.
- View create/edit: dua `<select>` (tanpa name) + `<input type="hidden" name="cost_center_id">`; JS saling-exclusive, isi otomatis owner & elemen biaya.
- Request: `cost_center_id` tetap divalidasi seperti sebelumnya.

### 4. Hapus Kategori Biaya
- Migration baru: drop kolom `cost_category` dari `erkap_routine_costs`.
- `RoutineCost` model: hapus `cost_category` dari `$fillable`.
- `Store/UpdateRoutineCostRequest`: hapus rule `cost_category`.
- View create/edit: hapus field Kategori Biaya.
- View index: hapus kolom "Kategori" + `<td>`, sesuaikan colspan.

## Verifikasi
- `php artisan migrate`
- `php -l` file PHP yang diubah
- `php artisan view:cache`
- `php artisan test`
