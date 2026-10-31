# Planning: Implementasi Fitur Delete pada Menu SPT Coretax & General Ledger (EQTax)

## 1. Ringkasan

Fitur yang akan dibuat:

1. **Delete per baris** pada tabel SPT Coretax dan General Ledger melalui tombol aksi di setiap row.
2. **Delete multiple row** dengan checkbox di setiap row.
3. **Check all** untuk memilih seluruh row pada halaman/filter aktif.
4. **Pilih semua data terfilter** (opsional tetapi direkomendasikan) agar user dapat menghapus seluruh data pada tab/filter yang sedang aktif, bukan hanya 25 row di halaman berjalan.
5. Konfirmasi sebelum menghapus, feedback sukses/gagal, dan refresh data + statistik setelah delete.

Pendekatan utama:

- Delete dilakukan **hard delete** dari tabel `eqtax_coretax_spt` dan `eqtax_gl` (sesuai pola aplikasi saat ini yang belum memakai soft delete).
- Satu endpoint delete per modul, dipakai untuk delete single maupun bulk.
- Hak akses delete dibuat terpisah dari `view`, `import`, dan `update-field`.
- Frontend memakai jQuery + SweetAlert2 yang sudah tersedia di layout EQTax.

---

## 2. Analisis Codebase

### 2.1 File terkait

| Komponen | File |
|---|---|
| Route EQTax | `routes/routers/eqtax.php` |
| Controller SPT | `app/Http/Controllers/EQTax/SPTCoretaxController.php` |
| Controller GL | `app/Http/Controllers/EQTax/GLController.php` |
| Model SPT | `app/Models/EQTAXCoretaxSPT.php` |
| Model GL | `app/Models/EQTAXGL.php` |
| View SPT | `resources/views/eqtax/spt/coretax/index.blade.php` |
| View GL | `resources/views/eqtax/gl/index.blade.php` |
| Layout EQTax | `resources/views/layouts/EQTax.blade.php` |
| Permission seeder | `database/seeders/RolePermissionSeeder.php` |
| Migration SPT | `database/migrations/2026_08_14_133832_create_e_q_t_a_x_coretax_s_p_t_s_table.php` + migration tambahan |
| Migration GL | `database/migrations/2026_08_15_143005_create_e_q_t_a_x_g_l_s_table.php` |

### 2.2 Struktur database

#### Tabel `eqtax_coretax_spt`

Kolom utama:

| Kolom | Tipe | Catatan |
|---|---|---|
| `id` | bigint unsigned, PK | Target delete |
| `npwp_penjual` | string, nullable | |
| `nama_penjual` | string, nullable | |
| `no_faktur_pajak` | string, nullable | Ada index, bukan unique |
| `tgl_faktur_pajak` | timestamp, nullable | |
| `masa_pajak` | string, nullable | Filter |
| `tahun` | string, nullable | Filter |
| `entity` | string, nullable | Penentu tab `PK` / `PM` / `PMS` |
| `kode_transaksi` | string, nullable | |
| `masa_pajak_pengkreditan` | string, nullable | |
| `tahun_pajak_pengkreditan` | string, nullable | |
| `status_faktur` | string, nullable | |
| `esign_status` | string, nullable | |
| `harga_jual` | bigint, nullable | |
| `dpp` | bigint, nullable | |
| `ppn` | bigint, nullable | |
| `ppnbm` | bigint, nullable | |
| `penandatangan` | string, nullable | |
| `perekam` | string, nullable | |
| `referensi` | string, nullable | |
| `metode_input` | string, nullable | |
| `uraian` | string, nullable | |
| `is_show_clear_name` | boolean, nullable | |
| `no_sp2d` | string, nullable | |
| `jenis_transaksi` | string, nullable | |
| `keterangan` | string, nullable | |
| `dibuat_oleh` | string, nullable | |
| `valid` | boolean, nullable | |
| `dilaporkan` | boolean, nullable | |
| `dilaporkan_oleh_penjual` | boolean, nullable | |
| `created_at`, `updated_at` | timestamp | |

Karakteristik:

- Tidak ada foreign key.
- `no_faktur_pajak` bukan unique constraint.
- Import SPT memakai `updateOrCreate` dengan key `entity` + `no_faktur_pajak` (jika no faktur tersedia).
- Tabel ini dipakai untuk tab PK/PM/PMS dengan filter `entity LIKE 'PK%'`, `'PM%'`, `'PMS%'`.

#### Tabel `eqtax_gl`

Kolom utama:

| Kolom | Tipe | Catatan |
|---|---|---|
| `id` | bigint unsigned, PK | Target delete |
| `sheet` | string, nullable | Filter |
| `no_supplier` | string, nullable | |
| `nama_supplier` | string, nullable | |
| `jurnal_date` | string, nullable | Filter periode, format `YYYYMM...` |
| `jurnal_no` | string, nullable | |
| `invoice_date` | string, nullable | |
| `invoice_no` | string, nullable | |
| `invoice_item` | string, nullable | |
| `no_faktur_pajak` | string, nullable | Ada index |
| `dpp` | float, nullable | |
| `ppn` | float, nullable | |
| `keterangan` | string, nullable | |
| `created_at`, `updated_at` | timestamp | |

Karakteristik:

- Tidak ada foreign key.
- Kolom `entity` sudah pernah ditambahkan lalu di-drop oleh migration `2026_09_03_101000_drop_entity_from_eqtax_gl_table.php`.
- Import GL memakai `insert()` per chunk, sehingga memungkinkan data duplikat. Delete by `id` aman untuk menghapus row tertentu.

#### Tabel turunan / snapshot

- `eqtax_equalization_results` menyimpan **snapshot** hasil proses ekualisasi.
- Tabel ini **tidak memiliki foreign key** ke SPT/GL.
- Delete SPT/GL **tidak otomatis** memperbarui atau menghapus snapshot ekualisasi.

### 2.3 Alur data

```text
Import SPT Excel  -> eqtax_coretax_spt ─┐
                                       ├─> Proses Ekualisasi -> eqtax_equalization_results
Import GL Excel   -> eqtax_gl ─────────┘                              |
                                                                      v
                                                              Dashboard EQTax
```

Implikasi delete:

- Menghapus SPT/GL akan langsung memengaruhi total record, total DPP, dan total PPN pada halaman SPT/GL karena statistik dihitung ulang dari tabel.
- Dashboard EQTax menghitung total SPT/GL langsung dari tabel, tetapi tabel/grafik ekualisasi memakai snapshot `eqtax_equalization_results`.
- Setelah delete, user perlu **re-run ekualisasi** untuk periode terkait agar snapshot sesuai data terbaru.
- Di planning ini, delete **tidak** otomatis menghapus snapshot ekualisasi. Behavior ini akan dicatat sebagai catatan/opsi lanjutan.

### 2.4 Routing dan permission saat ini

Route EQTax ada di `routes/routers/eqtax.php`:

```php
Route::prefix("spt")->name("spt.")->group(function () {
    Route::prefix("coretax")->name("coretax.")->middleware('permission:eqtax.spt.coretax.view')->group(function () {
        Route::get("/", [SPTCoretaxController::class, "index"])->name("index");
        Route::post("import", [SPTCoretaxController::class, "import"])->name("import")
            ->middleware('permission:eqtax.spt.coretax.import');
        Route::post("update-field", [SPTCoretaxController::class, "updateField"])->name("update-field")
            ->middleware('permission:eqtax.spt.coretax.update-field');
    });
});

Route::prefix('gl')->name('gl.')->middleware('permission:eqtax.gl.view')->group(function () {
    Route::get("/", [GLController::class, "index"])->name("index");
    Route::post("import", [GLController::class, "import"])->name("import")
        ->middleware('permission:eqtax.gl.import');
    Route::post("update-field", [GLController::class, "updateField"])->name("update-field")
        ->middleware('permission:eqtax.gl.update-field');
});
```

Permission EQTax saat ini di `RolePermissionSeeder.php`:

```php
['name' => 'eqtax.spt.coretax.view', ...],
['name' => 'eqtax.spt.coretax.import', ...],
['name' => 'eqtax.spt.coretax.update-field', ...],
['name' => 'eqtax.gl.view', ...],
['name' => 'eqtax.gl.import', ...],
['name' => 'eqtax.gl.update-field', ...],
```

Belum ada permission `delete`.

---

## 3. Ruang Lingkup dan Keputusan Desain

### 3.1 Yang akan diimplementasikan

| Fitur | SPT Coretax | General Ledger |
|---|---|---|
| Tombol delete per row | Ya | Ya |
| Checkbox per row | Ya | Ya |
| Check all halaman aktif | Ya | Ya |
| Bulk delete | Ya | Ya |
| Pilih semua data terfilter | Ya, direkomendasikan | Ya, direkomendasikan |
| Permission delete terpisah | Ya | Ya |
| Konfirmasi SweetAlert2 | Ya | Ya |

### 3.2 Keputusan desain

1. **Hard delete**
   - Model belum memakai `SoftDeletes`.
   - Mengikuti pola fitur delete lain di aplikasi ini.
   - Data yang dihapus tidak dapat dikembalikan, sehingga wajib ada konfirmasi.

2. **Satu endpoint per modul**
   - `POST /eqtax/spt/coretax/delete`
   - `POST /eqtax/gl/delete`
   - Payload bisa berisi `ids[]` (single/bulk di halaman) atau `select_all=1` + filter aktif (seluruh data terfilter).

3. **Check all default = halaman aktif**
   - Aman untuk data besar dan sesuai perilaku pagination.
   - Jika user ingin menghapus seluruh data pada filter/tab, sediakan link/button **"Pilih semua X data"**.
   - Saat `select_all=1`, backend membangun query dari filter yang sama dengan halaman index, lalu menghapus seluruh hasil query.

4. **Delete by ID selalu dibatasi konteks halaman**
   - Untuk SPT, ID yang dikirim tetap difilter dengan tab `entity` aktif agar tidak menghapus data tab lain secara tidak sengaja.
   - Untuk GL, ID difilter dengan filter `search`, `sheet`, `date_from`, dan `date_to` yang aktif jika mode bulk halaman; untuk single delete cukup validasi ID ada.

5. **Setelah delete, reload halaman**
   - Tujuannya agar total record, total DPP/PPN, pagination, dan isi tabel langsung sinkron.
   - Response JSON menyertakan `redirect_url` agar frontend bisa kembali ke halaman/filter yang sama tanpa parameter `page` yang sudah tidak valid.

6. **Mengikuti perilaku filter tab SPT yang sudah ada**
   - Index SPT memakai `entity LIKE 'PM%'`, sehingga tab `PM` saat ini juga berpotensi mencakup entity `PMS`.
   - Implementasi delete akan mengikuti perilaku filter index tersebut agar konsisten.
   - Jika ingin lebih ketat, perlu penyesuaian terpisah menjadi exact match atau `whereIn('entity', [...])`. Perbaikan ini di luar scope planning delete, tetapi dicatat sebagai risiko.

---

## 4. Desain UX

### 4.1 Perubahan kolom tabel

Urutan kolom baru:

```text
[Checkbox] [No] [Kolom data...] [Aksi]
```

- Kolom checkbox dan aksi hanya ditampilkan jika user punya permission delete.
- Checkbox header berfungsi sebagai **check all**.
- Tombol aksi berisi icon trash:

```html
<button type="button" class="btn btn-sm btn-outline-danger btn-delete-row"
        data-id="{{ $dt->id }}"
        data-label="{{ $dt->no_faktur_pajak ?? $dt->id }}"
        title="Hapus data">
    <i class="fas fa-trash"></i>
</button>
```

### 4.2 Toolbar bulk delete

Tambahkan toolbar di atas tabel, hanya muncul jika user punya permission delete:

```text
[ ] 3 data dipilih   [ Hapus Terpilih (3) ]   [ Pilih semua 1.234 data pada filter ini ]
```

Behavior:

- Tombol **Hapus Terpilih** disabled saat belum ada checkbox yang dipilih.
- Jumlah terpilih ditampilkan secara real-time.
- Jika jumlah data pada filter lebih besar dari jumlah row di halaman, tampilkan link **Pilih semua X data**.
- Saat link diklik, semua data pada filter aktif dianggap terpilih (state `select_all = true`).
- Jika user mengubah filter, reload halaman dan reset state pilihan.

### 4.3 Perilaku checkbox

- Checkbox header:
  - `checked` jika semua row halaman aktif terpilih.
  - `indeterminate` jika sebagian row terpilih.
- Klik checkbox header:
  - Memilih / membatalkan semua row di halaman aktif.
  - Tidak otomatis memilih seluruh halaman lain.
- Klik checkbox row:
  - Update state header checkbox dan toolbar.
- Row yang terpilih diberi highlight, misalnya `table-active` atau class `.row-selected`.

### 4.4 Konfirmasi delete

Gunakan SweetAlert2 yang sudah tersedia di layout EQTax.

**Single delete:**

```text
Hapus data ini?
No Faktur: 010.000-00.12345678
Data yang dihapus tidak dapat dikembalikan.
[ Batal ] [ Ya, Hapus ]
```

**Bulk delete by checkbox:**

```text
Hapus 3 data terpilih?
Data yang dihapus tidak dapat dikembalikan.
[ Batal ] [ Ya, Hapus 3 Data ]
```

**Select all filtered:**

```text
Hapus seluruh 1.234 data pada filter ini?
Tindakan ini akan menghapus semua data SPT/GL yang cocok dengan filter aktif.
Data yang dihapus tidak dapat dikembalikan.
[ Batal ] [ Ya, Hapus Semua ]
```

### 4.5 Feedback

- Sukses: SweetAlert sukses + reload ke `redirect_url`.
- Gagal validasi: SweetAlert error dengan pesan dari backend.
- Tidak ada data terpilih: tampilkan toast/alert peringatan.
- Saat request berjalan: disable tombol dan tampilkan spinner.

---

## 5. Rencana Implementasi Backend

### 5.1 Permission baru

File: `database/seeders/RolePermissionSeeder.php`

Tambahkan di array `$permissions` pada bagian EQTAX:

```php
['name' => 'eqtax.spt.coretax.delete', 'guard_name' => 'web'],
['name' => 'eqtax.gl.delete', 'guard_name' => 'web'],
```

Tambahkan juga ke assignment role `eqtax-user`:

```php
$eqtaxUser->givePermissionTo([
    // ...
    'eqtax.spt.coretax.delete',
    'eqtax.gl.delete',
]);
```

Catatan:

- `super-admin` otomatis mendapatkan seluruh permission karena memakai `$superAdmin->syncPermissions($allPermissions)`.
- Jika ada role lain yang perlu bisa delete, tambahkan manual di assignment role masing-masing.
- Untuk database yang sudah berjalan, jalankan:

```bash
php artisan db:seed --class=RolePermissionSeeder
php artisan permission:cache-reset
```

### 5.2 Route baru

File: `routes/routers/eqtax.php`

```php
Route::prefix("spt")->name("spt.")->group(function () {
    Route::prefix("coretax")->name("coretax.")->middleware('permission:eqtax.spt.coretax.view')->group(function () {
        Route::get("/", [SPTCoretaxController::class, "index"])->name("index");
        Route::post("import", [SPTCoretaxController::class, "import"])->name("import")
            ->middleware('permission:eqtax.spt.coretax.import');
        Route::post("update-field", [SPTCoretaxController::class, "updateField"])->name("update-field")
            ->middleware('permission:eqtax.spt.coretax.update-field');

        // BARU
        Route::post("delete", [SPTCoretaxController::class, "delete"])->name("delete")
            ->middleware('permission:eqtax.spt.coretax.delete');
    });
});

Route::prefix('gl')->name('gl.')->middleware('permission:eqtax.gl.view')->group(function () {
    Route::get("/", [GLController::class, "index"])->name("index");
    Route::post("import", [GLController::class, "import"])->name("import")
        ->middleware('permission:eqtax.gl.import');
    Route::post("update-field", [GLController::class, "updateField"])->name("update-field")
        ->middleware('permission:eqtax.gl.update-field');

    // BARU
    Route::post("delete", [GLController::class, "delete"])->name("delete")
        ->middleware('permission:eqtax.gl.delete');
});
```

### 5.3 Controller SPT: `delete()`

File: `app/Http/Controllers/EQTax/SPTCoretaxController.php`

Tambahkan method:

```php
public function delete(Request $request)
{
    $validated = $request->validate([
        'select_all'      => 'nullable|boolean',
        'ids'             => 'required_without:select_all|array|min:1',
        'ids.*'           => 'integer',
        'tab'             => 'nullable|string|in:PK,PM,PMS',
        'search'          => 'nullable|string',
        'masa_pajak'      => 'nullable|string',
        'tahun'           => 'nullable|string',
    ]);

    $activeTab = $validated['tab'] ?? 'PK';
    $entity = $activeTab;

    $query = EQTAXCoretaxSPT::query()
        ->where('entity', 'like', $entity . '%');

    // Filter hanya dipakai saat select_all
    if (!empty($validated['select_all'])) {
        if (!empty($validated['search'])) {
            $search = $validated['search'];
            $query->where(function ($q) use ($search) {
                $q->where('no_faktur_pajak', 'like', "%{$search}%")
                  ->orWhere('nama_penjual', 'like', "%{$search}%")
                  ->orWhere('npwp_penjual', 'like', "%{$search}%");
            });
        }
        if (!empty($validated['masa_pajak'])) {
            $query->where('masa_pajak', $validated['masa_pajak']);
        }
        if (!empty($validated['tahun'])) {
            $query->where('tahun', $validated['tahun']);
        }
    } else {
        $query->whereIn('id', $validated['ids']);
    }

    $deleted = $query->delete();

    if ($deleted === 0) {
        return response()->json([
            'success' => false,
            'message' => 'Tidak ada data yang dihapus. Data mungkin sudah tidak ada atau tidak sesuai tab aktif.',
        ], 422);
    }

    return response()->json([
        'success'       => true,
        'message'       => "{$deleted} data SPT berhasil dihapus.",
        'deleted_count' => $deleted,
        'redirect_url'  => route('eqtax.spt.coretax.index', array_filter([
            'tab'        => $activeTab,
            'search'     => $validated['search'] ?? null,
            'masa_pajak' => $validated['masa_pajak'] ?? null,
            'tahun'      => $validated['tahun'] ?? null,
        ])),
    ]);
}
```

Catatan implementasi:

- `array_filter()` membuang nilai `null`/string kosong agar URL bersih.
- Parameter `page` sengaja tidak dikirim. Setelah bulk delete, halaman bisa berkurang sehingga lebih aman kembali ke halaman 1 pada filter yang sama.
- Jika ingin mempertahankan halaman untuk single delete, frontend bisa memakai `redirect_url` yang sama; halaman 1 tetap aman.
- Delete tetap memakai filter `entity` agar ID dari tab lain tidak ikut terhapus.

### 5.4 Controller GL: `delete()`

File: `app/Http/Controllers/EQTax/GLController.php`

Tambahkan method:

```php
public function delete(Request $request)
{
    $validated = $request->validate([
        'select_all' => 'nullable|boolean',
        'ids'        => 'required_without:select_all|array|min:1',
        'ids.*'      => 'integer',
        'search'     => 'nullable|string',
        'sheet'      => 'nullable|string',
        'date_from'  => 'nullable|date',
        'date_to'    => 'nullable|date',
    ]);

    $query = EQTAXGL::query();

    if (!empty($validated['select_all'])) {
        if (!empty($validated['search'])) {
            $search = $validated['search'];
            $query->where(function ($q) use ($search) {
                $q->where('no_faktur_pajak', 'like', "%{$search}%")
                  ->orWhere('nama_supplier', 'like', "%{$search}%")
                  ->orWhere('jurnal_no', 'like', "%{$search}%")
                  ->orWhere('no_supplier', 'like', "%{$search}%");
            });
        }
        if (!empty($validated['sheet'])) {
            $query->where('sheet', $validated['sheet']);
        }
        if (!empty($validated['date_from'])) {
            $query->where('jurnal_date', '>=', $validated['date_from']);
        }
        if (!empty($validated['date_to'])) {
            $query->where('jurnal_date', '<=', $validated['date_to']);
        }
    } else {
        $query->whereIn('id', $validated['ids']);
    }

    $deleted = $query->delete();

    if ($deleted === 0) {
        return response()->json([
            'success' => false,
            'message' => 'Tidak ada data yang dihapus. Data mungkin sudah tidak ada.',
        ], 422);
    }

    return response()->json([
        'success'       => true,
        'message'       => "{$deleted} data GL berhasil dihapus.",
        'deleted_count' => $deleted,
        'redirect_url'  => route('eqtax.gl.index', array_filter([
            'search'    => $validated['search'] ?? null,
            'sheet'     => $validated['sheet'] ?? null,
            'date_from' => $validated['date_from'] ?? null,
            'date_to'   => $validated['date_to'] ?? null,
        ])),
    ]);
}
```

### 5.5 Aturan validasi dan keamanan

1. Route delete wajib memakai middleware permission baru.
2. Semua request harus menyertakan CSRF token (`X-CSRF-TOKEN` atau `_token`).
3. `ids` divalidasi sebagai array integer.
4. Untuk SPT, `tab` divalidasi hanya `PK`, `PM`, atau `PMS`.
5. Query delete tidak boleh memakai input mentah; gunakan Eloquent/query builder.
6. Mode `select_all` hanya menghapus data yang cocok dengan filter yang dikirim, bukan seluruh tabel tanpa konteks.
7. Jika `select_all=1`, frontend wajib menampilkan konfirmasi khusus yang menyebutkan jumlah total data.
8. Jika tidak ada row yang terhapus, kembalikan HTTP 422 dan pesan yang jelas.

---

## 6. Rencana Implementasi Frontend

### 6.1 View SPT Coretax

File: `resources/views/eqtax/spt/coretax/index.blade.php`

Perubahan yang diperlukan:

1. Di atas tabel, tambahkan toolbar bulk delete.
2. Di `<thead>`, tambahkan kolom checkbox sebelum `No` dan kolom `Aksi` setelah semua kolom data.
3. Di `<tbody>`, tambahkan checkbox row dan tombol delete per row.
4. Sesuaikan `colspan` empty state.

Contoh struktur:

```blade
@php
    $canDelete = auth()->user()->can('eqtax.spt.coretax.delete');
    $extraCols = $canDelete ? 2 : 0;
@endphp

@if($canDelete)
<div class="d-flex flex-wrap align-items-center gap-2 mb-3" id="bulk-toolbar">
    <span class="text-muted small" id="selected-info">0 data dipilih</span>
    <button type="button" class="btn btn-danger btn-sm" id="btn-delete-selected" disabled>
        <i class="fas fa-trash me-1"></i> Hapus Terpilih
    </button>
    <button type="button" class="btn btn-link btn-sm p-0 d-none" id="btn-select-all-filtered">
        Pilih semua data pada filter ini
    </button>
</div>
@endif

<table class="table table-hover table-bordered align-middle w-100" id="spt-table"
       data-total-filtered="{{ $sptData->total() }}">
    <thead class="table-dark">
        <tr class="align-middle">
            @if($canDelete)
            <th class="text-center" style="width: 42px">
                <input type="checkbox" id="checkAll" class="form-check-input" title="Pilih semua pada halaman ini">
            </th>
            @endif
            <th>No</th>
            @foreach($columns as $col)
            <th>{{ $col['label'] }}</th>
            @endforeach
            @if($canDelete)
            <th class="text-center" style="width: 70px">Aksi</th>
            @endif
        </tr>
    </thead>
    <tbody>
        @forelse($sptData as $key => $dt)
        <tr data-id="{{ $dt->id }}">
            @if($canDelete)
            <td class="text-center">
                <input type="checkbox" class="form-check-input row-checkbox" value="{{ $dt->id }}">
            </td>
            @endif
            <td>{{ ($sptData->currentPage() - 1) * $sptData->perPage() + $key + 1 }}</td>
            @foreach($columns as $col)
                {{-- kolom existing tetap --}}
            @endforeach
            @if($canDelete)
            <td class="text-center">
                <button type="button"
                        class="btn btn-sm btn-outline-danger btn-delete-row"
                        data-id="{{ $dt->id }}"
                        data-label="{{ $dt->no_faktur_pajak ?? $dt->id }}"
                        title="Hapus data">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
            @endif
        </tr>
        @empty
        <tr>
            <td colspan="{{ count($columns) + 1 + $extraCols }}" class="text-center text-muted">
                Belum ada data untuk tab {{ $activeTab }}. Silakan import file SPT terlebih dahulu.
            </td>
        </tr>
        @endforelse
    </tbody>
</table>
```

Catatan:

- Jangan menambahkan class `editable` pada kolom checkbox dan aksi agar tidak memicu inline edit.
- Event `dblclick` existing hanya terpasang pada `.editable`, jadi aman.
- Toolbar perlu menyimpan filter aktif. Bisa memakai `data-*` atau hidden input:

```blade
<div id="bulk-toolbar"
     data-url="{{ route('eqtax.spt.coretax.delete') }}"
     data-tab="{{ $activeTab }}"
     data-search="{{ request('search') }}"
     data-masa-pajak="{{ request('masa_pajak') }}"
     data-tahun="{{ request('tahun') }}"
     data-total="{{ $sptData->total() }}">
</div>
```

### 6.2 View General Ledger

File: `resources/views/eqtax/gl/index.blade.php`

Perubahan sama seperti SPT:

1. Toolbar bulk delete di atas tabel.
2. Checkbox column + `Aksi` column.
3. Checkbox per row + tombol delete per row.
4. `colspan` empty state berubah dari `13` menjadi `13 + 2 = 15` saat user punya permission delete.

Data filter yang perlu dikirim:

- `search`
- `sheet`
- `date_from`
- `date_to`

Contoh toolbar:

```blade
@php($canDelete = auth()->user()->can('eqtax.gl.delete'))

@if($canDelete)
<div class="d-flex flex-wrap align-items-center gap-2 mb-3" id="bulk-toolbar"
     data-url="{{ route('eqtax.gl.delete') }}"
     data-search="{{ request('search') }}"
     data-sheet="{{ request('sheet') }}"
     data-date-from="{{ request('date_from') }}"
     data-date-to="{{ request('date_to') }}"
     data-total="{{ $glData->total() }}">
    <span class="text-muted small" id="selected-info">0 data dipilih</span>
    <button type="button" class="btn btn-danger btn-sm" id="btn-delete-selected" disabled>
        <i class="fas fa-trash me-1"></i> Hapus Terpilih
    </button>
    <button type="button" class="btn btn-link btn-sm p-0 d-none" id="btn-select-all-filtered">
        Pilih semua data pada filter ini
    </button>
</div>
@endif
```

### 6.3 JavaScript

Letakkan script di section `@section('plugin')` masing-masing view, memanfaatkan jQuery dan SweetAlert2 yang sudah tersedia.

Contoh implementasi:

```js
(function () {
    const $toolbar = $('#bulk-toolbar');
    if (!$toolbar.length) return;

    const deleteUrl = $toolbar.data('url');
    const totalFiltered = parseInt($toolbar.data('total')) || 0;
    let selectAllFiltered = false;
    let requestRunning = false;

    function getSelectedIds() {
        return $('.row-checkbox:checked').map(function () {
            return $(this).val();
        }).get();
    }

    function updateBulkState() {
        const ids = getSelectedIds();
        const count = ids.length;

        $('#selected-info').text(count + ' data dipilih');
        $('#btn-delete-selected')
            .prop('disabled', count === 0 || requestRunning)
            .html('<i class="fas fa-trash me-1"></i> Hapus Terpilih' + (count ? ` (${count})` : ''));

        const $checkAll = $('#checkAll');
        const $all = $('.row-checkbox');
        const allChecked = $all.length > 0 && $all.length === $all.filter(':checked').length;

        $checkAll.prop('checked', allChecked);
        $checkAll.prop('indeterminate', count > 0 && !allChecked);

        $('tr[data-id]').removeClass('table-active');
        $('.row-checkbox:checked').closest('tr').addClass('table-active');

        const showSelectAll = !selectAllFiltered && count > 0 && totalFiltered > $all.length;
        $('#btn-select-all-filtered')
            .toggleClass('d-none', !showSelectAll)
            .text(`Pilih semua ${totalFiltered.toLocaleString('id-ID')} data pada filter ini`);
    }

    $('#checkAll').on('change', function () {
        selectAllFiltered = false;
        $('.row-checkbox').prop('checked', this.checked);
        updateBulkState();
    });

    $(document).on('change', '.row-checkbox', function () {
        selectAllFiltered = false;
        updateBulkState();
    });

    $('#btn-select-all-filtered').on('click', function () {
        selectAllFiltered = true;
        $('.row-checkbox').prop('checked', true);
        updateBulkState();
        $(this).addClass('d-none');
        $('#selected-info').text(
            `Semua ${totalFiltered.toLocaleString('id-ID')} data pada filter ini dipilih`
        );
    });

    function buildPayload() {
        const payload = {
            _token: $('meta[name="csrf-token"]').attr('content')
        };

        if (selectAllFiltered) {
            payload.select_all = 1;
            payload.tab = $toolbar.data('tab');
            payload.search = $toolbar.data('search') || '';
            payload.masa_pajak = $toolbar.data('masa-pajak') || '';
            payload.tahun = $toolbar.data('tahun') || '';
            payload.sheet = $toolbar.data('sheet') || '';
            payload.date_from = $toolbar.data('date-from') || '';
            payload.date_to = $toolbar.data('date-to') || '';
        } else {
            payload.ids = getSelectedIds();
        }

        return payload;
    }

    function sendDelete(payload) {
        if (requestRunning) return;
        requestRunning = true;
        updateBulkState();

        $.ajax({
            url: deleteUrl,
            type: 'POST',
            data: payload,
            success: function (res) {
                if (!res.success) {
                    Swal.fire('Gagal!', res.message || 'Gagal menghapus data.', 'error');
                    return;
                }
                Swal.fire('Berhasil!', res.message, 'success').then(function () {
                    window.location.href = res.redirect_url;
                });
            },
            error: function (xhr) {
                const msg = xhr.responseJSON?.message || 'Terjadi kesalahan saat menghapus data.';
                Swal.fire('Gagal!', msg, 'error');
            },
            complete: function () {
                requestRunning = false;
                updateBulkState();
            }
        });
    }

    $('#btn-delete-selected').on('click', function () {
        const ids = getSelectedIds();
        if (!ids.length) {
            Swal.fire('Tidak ada data', 'Pilih minimal satu data terlebih dahulu.', 'warning');
            return;
        }

        const total = selectAllFiltered ? totalFiltered : ids.length;
        const text = selectAllFiltered
            ? `Semua ${total.toLocaleString('id-ID')} data pada filter aktif akan dihapus permanen.`
            : `${total} data terpilih akan dihapus permanen.`;

        Swal.fire({
            title: selectAllFiltered ? 'Hapus semua data terfilter?' : `Hapus ${total} data terpilih?`,
            text: text + ' Data yang dihapus tidak dapat dikembalikan.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#f46a6a',
            confirmButtonText: selectAllFiltered ? 'Ya, Hapus Semua' : 'Ya, Hapus',
            cancelButtonText: 'Batal'
        }).then(function (result) {
            if (result.isConfirmed) {
                sendDelete(buildPayload());
            }
        });
    });

    $(document).on('click', '.btn-delete-row', function () {
        const id = $(this).data('id');
        const label = $(this).data('label');

        Swal.fire({
            title: 'Hapus data ini?',
            html: `No Faktur: <strong>${label}</strong><br><br>Data yang dihapus tidak dapat dikembalikan.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#f46a6a',
            confirmButtonText: 'Ya, Hapus',
            cancelButtonText: 'Batal'
        }).then(function (result) {
            if (result.isConfirmed) {
                sendDelete({
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    ids: [id]
                });
            }
        });
    });

    updateBulkState();
})();
```

Catatan:

- Script di atas perlu penyesuaian untuk GL karena field filter berbeda.
- Pastikan tombol delete per row tidak memicu handler lain.
- Jika `select_all` aktif lalu user mengubah checkbox, state `selectAllFiltered` harus di-reset (sudah ditangani di handler checkbox).
- Setelah `select_all`, checkbox header tetap bisa menampilkan state checked.
- Gunakan `data-total` dari paginator (`$sptData->total()` / `$glData->total()`) untuk mengetahui jumlah data seluruh filter.
- Escape label/no faktur sebelum dimasukkan ke `Swal.fire({ html: ... })` untuk mencegah HTML injection. Alternatif yang lebih aman adalah memakai opsi `text` atau helper escape HTML.

### 6.4 CSS tambahan

Tambahkan di section `@section('css')` masing-masing view:

```css
.row-selected,
.table > tbody > tr.table-active > td {
    background-color: #e0e7ff !important;
}

#bulk-toolbar {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 10px 12px;
}

#bulk-toolbar .btn-link {
    text-decoration: none;
}
```

---

## 7. Edge Case dan Dampak

### 7.1 Data ekualisasi menjadi stale

Setelah delete SPT/GL:

- `eqtax_equalization_results` masih menyimpan hasil lama.
- Dashboard dan halaman ekualisasi bisa menampilkan angka yang tidak sesuai sampai user menjalankan ulang proses ekualisasi untuk periode terkait.
- Rencana: tampilkan catatan di halaman atau pesan sukses: **"Jalankan ulang ekualisasi untuk memperbarui hasil."**
- Opsi lanjutan (belum termasuk implementasi awal): hapus snapshot `eqtax_equalization_results` untuk periode yang terdampak setelah delete.

### 7.2 Pagination dan halaman terakhir

- Jika user menghapus seluruh row di halaman terakhir, halaman tersebut bisa menjadi kosong.
- Solusi: response delete mengembalikan `redirect_url` tanpa parameter `page`, sehingga user kembali ke halaman 1 dengan filter yang sama.

### 7.3 SPT tab isolation

- Delete SPT selalu dibatasi `entity LIKE tab%`.
- User di tab PK tidak bisa menghapus data PM/PMS lewat manipulasi ID.
- Jika `select_all=1` di tab PK, hanya data PK yang terhapus.

### 7.4 Filter GL

- Mode `select_all` GL harus memakai filter yang sama persis dengan query index.
- Jika filter tanggal kosong, jangan menambahkan kondisi tanggal.
- Jika `sheet` kosong, jangan menambahkan kondisi sheet.

### 7.5 Data duplikat GL

- GL dapat memiliki beberapa row dengan `no_faktur_pajak` yang sama.
- Delete by `id` menghapus row spesifik.
- Delete `select_all` menghapus semua row yang cocok filter, termasuk duplikat. Ini perlu ditegaskan di konfirmasi.

### 7.6 Tidak ada foreign key

- Delete tidak akan gagal karena constraint.
- Tidak ada cascade ke tabel lain.
- Snapshot ekualisasi tidak otomatis ikut terhapus.

### 7.7 Race condition / double click

- Tombol delete di-disable saat request berjalan.
- Setelah sukses, halaman langsung reload.
- Jika data sudah dihapus oleh user lain, backend mengembalikan 422 dengan pesan yang jelas.

### 7.8 Permission

- User tanpa permission delete tidak melihat checkbox, toolbar, dan tombol aksi.
- Route tetap dilindungi middleware, sehingga tetap aman walau UI dimanipulasi.

---

## 8. Testing Checklist

### 8.1 Manual testing SPT

- [ ] User dengan permission delete melihat kolom checkbox dan aksi.
- [ ] User tanpa permission delete tidak melihat checkbox/toolbar/tombol aksi.
- [ ] Delete satu row berhasil dan total record/DPP/PPN berubah.
- [ ] Checkbox satu row mengaktifkan tombol **Hapus Terpilih**.
- [ ] Check all memilih semua row di halaman aktif.
- [ ] Check all lalu uncheck satu row membuat header menjadi `indeterminate`.
- [ ] Bulk delete 2-3 row berhasil.
- [ ] Link **Pilih semua X data** muncul jika total data > jumlah row halaman.
- [ ] Select all filtered menghapus semua data pada tab + filter aktif.
- [ ] Data tab lain tidak ikut terhapus.
- [ ] Filter `search`, `masa_pajak`, dan `tahun` dipertahankan setelah delete.
- [ ] Halaman kembali ke halaman 1 dan tidak menampilkan tabel kosong yang membingungkan.
- [ ] Konfirmasi batal tidak menghapus data.
- [ ] CSRF token valid; request tanpa token ditolak.

### 8.2 Manual testing GL

- [ ] Delete satu row berhasil.
- [ ] Bulk delete beberapa row berhasil.
- [ ] Check all halaman aktif berfungsi.
- [ ] Select all filtered menghormati filter `search`, `sheet`, `date_from`, `date_to`.
- [ ] Row duplikat dengan no faktur sama bisa dihapus per ID.
- [ ] Total record/DPP/PPN berubah setelah delete.
- [ ] Filter aktif tetap setelah reload.

### 8.3 Automated test (opsional, direkomendasikan)

Buat `tests/Feature/EQTax/DeleteSptGlTest.php`:

- Test single delete SPT.
- Test bulk delete SPT by IDs.
- Test select all SPT hanya menghapus entity/tab aktif.
- Test single delete GL.
- Test bulk delete GL dengan filter sheet/tanggal.
- Test user tanpa permission mendapat 403.
- Test validasi `ids` kosong.
- Test `ids` yang tidak ada mengembalikan 422.

Gunakan `RefreshDatabase` dan factory/manual insert data.

---

## 9. Ringkasan File yang Akan Diubah

| File | Perubahan |
|---|---|
| `routes/routers/eqtax.php` | Tambah route `delete` SPT dan GL |
| `app/Http/Controllers/EQTax/SPTCoretaxController.php` | Tambah method `delete()` |
| `app/Http/Controllers/EQTax/GLController.php` | Tambah method `delete()` |
| `database/seeders/RolePermissionSeeder.php` | Tambah permission `eqtax.spt.coretax.delete` dan `eqtax.gl.delete`, assign ke `eqtax-user` |
| `resources/views/eqtax/spt/coretax/index.blade.php` | Checkbox, check all, toolbar bulk, tombol delete, JS, CSS, colspan |
| `resources/views/eqtax/gl/index.blade.php` | Checkbox, check all, toolbar bulk, tombol delete, JS, CSS, colspan |
| `tests/Feature/EQTax/DeleteSptGlTest.php` | Baru, opsional |

Tidak perlu migration baru karena delete memakai kolom `id` yang sudah ada.

---

## 10. Urutan Eksekusi

1. **Backend permission**
   - Tambah permission di seeder.
   - Jalankan seeder + cache reset di environment development.

2. **Backend route & controller**
   - Tambah route delete SPT dan GL.
   - Implementasi method `delete()` di kedua controller.
   - Uji endpoint dengan Postman/curl/AJAX sebelum UI.

3. **Frontend SPT**
   - Tambah checkbox, check all, toolbar, tombol delete, dan JS.
   - Uji single, bulk, dan select all filtered.

4. **Frontend GL**
   - Lakukan perubahan yang sama dengan penyesuaian filter GL.
   - Uji semua skenario.

5. **Testing & regression**
   - Pastikan inline edit existing tetap berjalan.
   - Pastikan import, filter, pagination, dan statistik tidak rusak.
   - Pastikan user tanpa permission tidak bisa mengakses endpoint.

6. **Update dokumentasi**
   - Tambahkan catatan bahwa setelah delete user perlu re-run ekualisasi.

Estimasi effort:

| Bagian | Estimasi |
|---|---|
| Backend route + controller | 1-2 jam |
| Permission + seeder | 30 menit |
| Frontend SPT | 2-3 jam |
| Frontend GL | 1-2 jam |
| Testing & bug fixing | 2-3 jam |
| **Total** | **± 7-11 jam** |

---

## 11. Rollback Plan

Jika fitur bermasalah:

1. Hapus route delete SPT dan GL.
2. Hapus method `delete()` dari kedua controller.
3. Revert perubahan view SPT/GL.
4. Hapus permission baru dari seeder dan jalankan ulang seeder jika perlu.
5. Data yang sudah terhapus tidak dapat dikembalikan kecuali ada backup database.

Rekomendasi sebelum testing delete di data production:

- Backup tabel `eqtax_coretax_spt`, `eqtax_gl`, dan `eqtax_equalization_results`.
- Lakukan pengujian di database development/staging terlebih dahulu.

---

## 12. Opsi Lanjutan (Belum Termasuk Implementasi Awal)

1. **Soft delete**
   - Tambah kolom `deleted_at` dan trait `SoftDeletes`.
   - Cocok jika data pajak perlu audit trail dan restore.

2. **Audit log delete**
   - Catat user, waktu, jumlah data, dan filter/ID yang dihapus.

3. **Auto-invalidate equalization snapshot**
   - Setelah delete, hapus `eqtax_equalization_results` untuk periode yang terdampak.
   - Atau tampilkan badge "perlu re-run ekualisasi" di dashboard.

4. **Restore / undo**
   - Bisa dilakukan jika memakai soft delete.

5. **Export data sebelum delete**
   - Sediakan tombol download backup row yang dipilih sebelum dihapus.
