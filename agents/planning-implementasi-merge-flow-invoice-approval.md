# Planning: Implementasi Merge Flow Invoice + Persetujuan Pembayaran (Fan-Out)

## Status: IMPLEMENTED (FASE 1) — DIFINALISASI
## Date: 2026-10-21 (finalisasi 2026-10-26)
## Author: opencode
## Modul: Dokter — OCR / Merge Flow / PDF Final

---

## 1. Ringkasan

Dokumen **PERSETUJUAN PEMBAYARAN** berisi daftar beberapa nomor invoice. Setiap invoice yang disebut harus mendapatkan **satu file PDF final baru** yang berisi:

```text
[ halaman invoice ] + [ halaman persetujuan pembayaran ]
```

File final disimpan di folder terpisah:

```text
FINAL/{vendor}/FINAL_{vendor}_{nomor_invoice}_{nomor_persetujuan}.pdf
```

Invoice asli dan dokumen persetujuan asli **tidak diubah/ditimpa**. Yang dibuat adalah salinan final baru per invoice.

Implementasi ini mengganti asumsi lama “satu child document hanya terhubung ke satu parent” menjadi **fan-out satu dokumen ke banyak group**.

### Keputusan yang sudah dikonfirmasi

| No | Keputusan |
|---|---|
| 1 | Final PDF dibuat baru per invoice, disimpan di folder `FINAL` terpisah, bukan menimpa invoice asli. |
| 2 | Urutan halaman: **invoice dulu, baru approval**. |
| 3 | Fase ini cukup **invoice + approval**. Struktur kode dan data harus siap ditambah **BA** tanpa refactor besar. |

---

## 2. Tujuan & Non-Goals

### 2.1 Tujuan

1. Approval dengan 3 nomor invoice menghasilkan 3 group dan 3 final PDF.
2. Setiap final PDF hanya berisi invoice yang bersangkutan + approval yang sama.
3. Approval yang datang sebelum invoice tetap membuat group pending, lalu selesai saat invoice datang.
4. Approval yang datang setelah invoice langsung memicu merge untuk invoice yang sudah ada.
5. Invoice/approval asli tetap utuh di FTP.
6. `linked_numbers` menyimpan seluruh nomor invoice, bukan hanya satu.
7. Merge tidak berjalan ganda saat dokumen di-scan ulang.
8. Struktur siap untuk penambahan BA pada fase berikutnya.

### 2.2 Non-Goals (fase ini)

- Tidak menimpa file invoice asli.
- Tidak menambahkan BA ke final PDF.
- Tidak membuat UI baru yang besar; cukup penyesuaian status/label bila diperlukan.
- Tidak melakukan backfill otomatis seluruh data lama.
- Tidak mengubah mekanisme OCR single-page untuk invoice.
- Tidak mengubah format nama file dokumen asli.

---

## 3. Kondisi Saat Ini

### 3.1 OCR sudah menghasilkan `invoice_numbers`

File OCR nyata:

```text
storage/app/private/scanner/ocr-results/20261020_152215.pdf.json
```

Isi penting:

```json
{
  "document_type": "Persetujuan Pembayaran",
  "document_number": "724/SBS-HO/DIR/PP/VII/2026",
  "vendor_name": "DUTA WAHANA PERSADA",
  "invoice_numbers": [
    "2512014",
    "2604001",
    "2604002"
  ],
  "source_pages": 3,
  "ocr_pages_used": 1
}
```

Artinya:

- Gemini sudah bisa mengembalikan list invoice dalam bentuk array.
- PDF approval 3 halaman, tetapi OCR hanya memakai halaman 1.
- Pada sampel ini seluruh nomor invoice kebetulan ada di halaman 1.

Namun `invoice_numbers` **belum menjadi kontrak eksplisit** di prompt. Saat ini nilainya muncul dari aturan generik “secondary document numbers”. Ini perlu dibuat eksplisit agar tidak berubah-ubah.

### 3.2 `MergeFlowService` masih single-link

Lokasi: `app/Services/MergeFlowService.php`

| Bagian | Perilaku saat ini | Dampak |
|---|---|---|
| `extractLinkedNumber()` baris 142-173 | Mengembalikan `?string` | Hanya satu nomor yang bisa diproses |
| `handleChildDocument()` baris 82-140 | Memakai satu `$linkedNumber` | Tidak bisa fan-out ke 3 group |
| `link_regex` | Memakai `preg_match()` | Hanya match pertama |
| `link_field` | `trim($ocrData[$field])` | Error bila field berisi array |
| `linked_numbers` | Disimpan sebagai satu nilai | Tidak menyimpan list |
| `checkAndTriggerMerge()` baris 175-184 | Menghitung jumlah item | Tidak menghitung step unik; rawan merge ganda |
| `performFinalMerge()` baris 186-276 | Dipanggil di dalam DB transaction | File I/O FTP menahan lock DB |
| `performFinalMerge()` error handling | Hanya di-log | Group bisa tertahan di status “Lengkap” tanpa final PDF |

### 3.3 Flow aktif tidak cocok dengan dokumen aktual

Flow di database saat ini:

```text
1. BERITA ACARA      (document_type_id 3)
2. INVOICE           (document_type_id 2)
3. PEMBAYARAN        (document_type_id 4)
```

Dokumen yang benar-benar diproses berdasarkan `scan_logs`:

```text
SLIP PEMBUKUAN AP       (document_type_id 1) → 2512014, 2604001, 2604002
PERSETUJUAN PEMBAYARAN  (document_type_id 5) → 724/SBS-HO/DIR/PP/VII/2026
```

Saat ini `document_merge_groups` kosong karena tidak ada step yang cocok untuk document type id 1 dan id 5.

**Kesimpulan:** masalah pertama yang harus diperbaiki adalah konfigurasi flow, bukan hanya logika fan-out.

### 3.4 `MergeFlowSeeder` juga memakai konfigurasi lama

Lokasi: `database/seeders/MergeFlowSeeder.php`

- Membuat flow `BA-INV-SP`.
- Step 3 memakai `PEMBAYARAN` id 4.
- `link_field` step 3 adalah `no_inv`, bukan `invoice_numbers`.
- `create()` dipakai langsung, sehingga seeder bisa membuat duplikat bila dijalankan ulang.

Seeder perlu disesuaikan untuk instalasi baru.

### 3.5 Skema database sudah mendukung fan-out

Yang sudah ada:

- `scan_logs.linked_numbers` bertipe JSON dan di-cast array.
- `document_merge_groups` memiliki `root_document_number`.
- `document_merge_group_items` unik per `(merge_group_id, document_type_id)`.
- Tidak ada unique constraint pada `scan_log_id`, sehingga **satu scan log approval boleh muncul di banyak group**.

Fase 1 **tidak membutuhkan migration baru** untuk fan-out.

---

## 4. Desain Fase 1 (Invoice + Approval)

### 4.1 Flow yang dipakai

```text
Step 1: SLIP PEMBUKUAN AP       (id 1), order 1, anchor, tanpa link
Step 2: PERSETUJUAN PEMBAYARAN  (id 5), order 2,
        link_field  = invoice_numbers
        link_label  = Invoice Numbers
```

Catatan:

- Jika di instalasi lain dokumen invoice benar-benar memakai `INVOICE` id 2, ganti step 1 ke id 2.
- Approval memakai `PERSETUJUAN PEMBAYARAN` id 5, bukan `PEMBAYARAN` id 4.
- `link_regex` tidak wajib. `link_field` menjadi sumber utama. Regex hanya fallback.

### 4.2 Group key

```text
merge_flow_id + vendor_name + nomor_invoice
```

Contoh:

```text
Group 1: DUTA WAHANA PERSADA + 2512014
Group 2: DUTA WAHANA PERSADA + 2604001
Group 3: DUTA WAHANA PERSADA + 2604002
```

`root_document_number` pada group diisi **nomor invoice**, bukan nomor approval.

Alasan:

- Invoice adalah anchor/root.
- Approval adalah child yang bisa bercabang ke banyak group.
- Nomor approval tetap disimpan pada item approval (`document_number`).

### 4.3 Alur jika invoice datang lebih dulu

```text
Invoice 2512014 masuk
→ handleRootDocument()
→ group 2512014 dibuat
→ item invoice order 1
→ status pending

Invoice 2604001 masuk
→ group 2604001 dibuat
→ status pending

Approval masuk
→ extractLinkedNumbers() = [2512014, 2604001, 2604002]
→ loop 3 nomor:
   - cari group invoice 2512014 → attach approval order 2
   - cari group invoice 2604001 → attach approval order 2
   - cari group invoice 2604002 → tidak ada → buat group pending + approval
→ group 2512014 dan 2604001 lengkap → merge
→ group 2604002 pending menunggu invoice
```

### 4.4 Alur jika approval datang lebih dulu

```text
Approval masuk
→ extractLinkedNumbers() = [2512014, 2604001, 2604002]
→ untuk setiap nomor:
   - tidak ada group → buat group root_document_number = nomor invoice
   - attach approval order 2
   - status pending
→ 3 group pending

Invoice 2512014 masuk
→ handleRootDocument() mencari group root_document_number = 2512014
→ ketemu
→ attach invoice order 1
→ lengkap → merge
```

Karena `handleRootDocument()` memakai `firstOrCreate` berdasarkan `root_document_number`, alur ini bekerja tanpa perubahan besar.

### 4.5 Urutan halaman final

`performFinalMerge()` sudah mengurutkan item berdasarkan `order`.

```text
order 1 = invoice
order 2 = approval
```

Maka final PDF otomatis:

```text
[invoice pages] + [approval pages]
```

Jika nanti BA ditambahkan:

```text
order 1 = BA
order 2 = invoice
order 3 = approval
```

Maka final PDF menjadi:

```text
[BA pages] + [invoice pages] + [approval pages]
```

### 4.6 Lokasi dan nama file final

Mengikuti konvensi yang sudah ada:

```text
FINAL/{vendor}/FINAL_{vendor}_{nomor_invoice}_{nomor_approval}.pdf
```

Contoh:

```text
FINAL/DUTA WAHANA PERSADA/FINAL_DUTA WAHANA PERSADA_2512014_724_SBS-HO_DIR_PP_VII_2026.pdf
```

Ketentuan:

- Folder `FINAL` tetap terpisah dari folder dokumen asli.
- Nama file memakai `FileNameSanitizer` agar `/` pada nomor approval tidak menjadi folder.
- File asli di `SLIP PEMBUKUAN AP/...` dan `PERSETUJUAN PEMBAYARAN/...` tidak diubah.
- Final PDF ditulis ke FTP final setelah merge lokal berhasil.

---

## 5. Perubahan Implementasi

### 5.1 OCR: kontrak `invoice_numbers`

#### Prompt

Tambahkan aturan eksplisit pada `config/services.php` (prompt template):

```text
- Jika dokumen adalah PERSETUJUAN PEMBAYARAN / payment approval,
  kembalikan "invoice_numbers" sebagai array berisi SEMUA nomor invoice
  yang tercantum. Jangan gabungkan menjadi satu string.
- Untuk dokumen lain, kosongkan atau hilangkan field ini.
```

Tambahan ini hanya beberapa token dan mencegah Gemini memilih nama field lain seperti `no_inv`, `invoice_no`, atau `invoice_number`.

#### Normalisasi

Di `GeminiEngine::extractText()`, setelah `parseJsonResponse()`:

```php
$ocrData = $this->normalizeInvoiceNumbers($ocrData);
```

Helper yang dibutuhkan:

```php
protected function normalizeInvoiceNumbers(array $data): array
{
    if (! array_key_exists('invoice_numbers', $data)) {
        return $data;
    }

    $value = $data['invoice_numbers'];

    if (is_string($value)) {
        $value = preg_split('/[\r\n,;]+/', $value) ?: [];
    }

    if (! is_array($value)) {
        $value = [];
    }

    $data['invoice_numbers'] = collect($value)
        ->map(fn ($number) => trim((string) $number))
        ->map(fn ($number) => preg_replace('/\s+/', ' ', $number))
        ->filter(fn ($number) => $number !== '')
        ->unique()
        ->values()
        ->all();

    return $data;
}
```

Yang harus ditangani:

- array of string
- string dipisah koma
- string multi-line
- angka integer
- spasi berlebih
- duplikat
- nilai kosong/null

#### Validasi

Jika `document_type` mengandung `PERSETUJUAN` tetapi `invoice_numbers` kosong:

- catat `Log::warning('Approval document has no invoice_numbers', ...)`
- jangan membuat group
- tetap simpan scan log sebagai sukses OCR, tetapi merge flow berhenti dengan warning

### 5.2 OCR multi-halaman untuk approval

Masalah: `GEMINI_PDF_OCR_PAGES=1` membuat daftar invoice di halaman 2/3 tidak terbaca.

Rekomendasi fase 1:

1. OCR halaman 1 terlebih dahulu (seperti sekarang) untuk menentukan `document_type`.
2. Jika `document_type` adalah `PERSETUJUAN PEMBAYARAN` dan `source_pages > 1`:
   - jalankan OCR kedua pada seluruh PDF (atau halaman 2..N),
   - gabungkan `invoice_numbers` dari kedua hasil,
   - deduplikasi.
3. Invoice tetap OCR 1 halaman.

Implementasi teknis:

- Tambahkan parameter opsional pada `GeminiEngine::extractText()`:

```php
public function extractText(
    UploadedFile $file,
    ?DocumentType $documentType = null,
    ?int $pdfOcrPages = null
): array
```

- Jika `$pdfOcrPages === null`, pakai config global.
- Untuk pass kedua approval, panggil dengan `pdfOcrPages: 0` (semua halaman).

Alternatif yang lebih murah:

- Hanya lakukan pass kedua jika `source_pages > 1` dan `invoice_numbers` kosong.
- Namun ini berisiko jika halaman 1 hanya memuat sebagian nomor.

Rekomendasi: selalu pass kedua untuk approval multi-halaman, karena jumlah approval jauh lebih sedikit daripada invoice.

Catatan token:

- Pass kedua hanya untuk approval.
- Approval 3 halaman tetap jauh lebih murah daripada mengubah semua dokumen menjadi full-page OCR.

### 5.3 `MergeFlowService`: fan-out

#### 5.3.1 Ganti single-link menjadi multi-link

Ubah:

```php
protected function extractLinkedNumber(...): ?string
```

Menjadi:

```php
protected function extractLinkedNumbers(...): array
```

Urutan sumber:

1. `link_field` dari `metadata.ocr_data`
2. `linked_numbers` yang sudah tersimpan
3. `link_regex` dengan `preg_match_all()`

Contoh:

```php
protected function extractLinkedNumbers(
    MergeFlowStep $step,
    ScanLog $scanLog,
    string $ocrText
): array {
    if ($step->link_field) {
        $value = $scanLog->metadata['ocr_data'][$step->link_field] ?? null;
        $numbers = $this->normalizeLinkedNumbers($value);

        if ($numbers !== []) {
            return $numbers;
        }
    }

    if ($step->link_label) {
        $value = $scanLog->linked_numbers[$step->link_label] ?? null;
        $numbers = $this->normalizeLinkedNumbers($value);

        if ($numbers !== []) {
            return $numbers;
        }
    }

    if ($step->link_regex) {
        preg_match_all($step->link_regex, $ocrText, $matches);

        return $this->normalizeLinkedNumbers($matches[1] ?? []);
    }

    return [];
}
```

Helper:

```php
protected function normalizeLinkedNumbers(mixed $value): array
{
    if (is_string($value)) {
        $value = preg_split('/[\r\n,;]+/', $value) ?: [];
    }

    if (! is_array($value)) {
        return [];
    }

    return collect($value)
        ->map(fn ($item) => trim((string) $item))
        ->map(fn ($item) => preg_replace('/[\x00-\x1F\x7F]/', ' ', $item))
        ->map(fn ($item) => preg_replace('/\s+/', ' ', $item))
        ->filter(fn ($item) => $item !== '')
        ->unique()
        ->values()
        ->all();
}
```

#### 5.3.2 `handleChildDocument()` loop per nomor invoice

Perubahan inti:

```php
$linkedNumbers = $this->extractLinkedNumbers($step, $scanLog, $ocrText);

if ($linkedNumbers === []) {
    Log::warning('Could not extract linked numbers from child document', [
        'scan_log_id' => $scanLog->id,
        'link_field' => $step->link_field,
        'link_regex' => $step->link_regex,
    ]);

    return;
}

$label = $step->link_label ?? 'linked_number';
$linked = $scanLog->linked_numbers ?? [];
$linked[$label] = count($linkedNumbers) === 1
    ? $linkedNumbers[0]
    : $linkedNumbers;
$scanLog->update(['linked_numbers' => $linked]);

$parentStep = MergeFlowStep::where('merge_flow_id', $step->merge_flow_id)
    ->where('order', $step->order - 1)
    ->first();

if (! $parentStep) {
    Log::warning('Parent step not found', ['step_id' => $step->id]);

    return;
}

foreach ($linkedNumbers as $linkedNumber) {
    DB::transaction(function () use (
        $step,
        $scanLog,
        $vendorName,
        $documentNumber,
        $linkedNumber,
        $parentStep
    ) {
        $group = DocumentMergeGroup::where('merge_flow_id', $step->merge_flow_id)
            ->where('vendor_name', $vendorName)
            ->whereHas('items', function ($query) use ($linkedNumber, $parentStep) {
                $query->where('document_number', $linkedNumber)
                    ->where('order', $parentStep->order);
            })
            ->lockForUpdate()
            ->first();

        if (! $group) {
            $group = DocumentMergeGroup::create([
                'merge_flow_id' => $step->merge_flow_id,
                'vendor_name' => $vendorName,
                'root_document_number' => $linkedNumber,
                'status' => 0,
            ]);
        }

        DocumentMergeGroupItem::updateOrCreate(
            [
                'merge_group_id' => $group->id,
                'document_type_id' => $scanLog->document_type_id,
            ],
            [
                'scan_log_id' => $scanLog->id,
                'document_number' => $documentNumber,
                'order' => $step->order,
                'ftp_path' => $scanLog->ftp_path,
            ]
        );
    });
}
```

#### 5.3.3 Merge dipanggil setelah semua attachment selesai

Jangan panggil `performFinalMerge()` di dalam loop/transaksi. Kumpulkan group yang terpengaruh:

```php
$affectedGroupIds = [];

foreach ($linkedNumbers as $linkedNumber) {
    $group = $this->attachChildToGroup(...);
    $affectedGroupIds[] = $group->id;
}

foreach (array_unique($affectedGroupIds) as $groupId) {
    $group = DocumentMergeGroup::find($groupId);

    if ($group) {
        $this->checkAndTriggerMerge($group, $step->mergeFlow);
    }
}
```

Tujuan:

- transaksi DB selesai sebelum file I/O
- menghindari lock panjang saat download/merge/upload FTP
- menghindari merge dobel untuk group yang sama

#### 5.3.4 `handleRootDocument()` tetap, tetapi perlu guard

`handleRootDocument()` sudah membuat group berdasarkan `root_document_number`. Yang perlu ditambahkan:

- gunakan `lockForUpdate()` saat mencari group
- setelah item ditambahkan, panggil `checkAndTriggerMerge()` di luar transaksi
- guard agar group status 2 tidak diproses ulang

### 5.4 `checkAndTriggerMerge()`: hitung step unik dan idempotent

Perubahan:

```php
protected function checkAndTriggerMerge(DocumentMergeGroup $group, MergeFlow $flow): void
{
    if ($group->status === 2) {
        return;
    }

    $totalSteps = $flow->steps()->count();

    $presentOrders = $group->items()
        ->distinct()
        ->pluck('order')
        ->count();

    if ($presentOrders >= $totalSteps) {
        $group->update(['status' => 1]);
        $this->performFinalMerge($group, $flow);
    }
}
```

Catatan:

- `distinct()->pluck('order')` lebih aman daripada `items()->count()`.
- Status 2 tidak boleh di-merge ulang.
- Jika dokumen yang sama di-scan ulang dan item berubah, perlu kebijakan tersendiri (lihat risiko duplikasi).

### 5.5 `performFinalMerge()`: status, lock, dan error handling

Perubahan yang disarankan:

1. Lock group sebelum merge.
2. Cek `status === 2`, langsung return.
3. Set status sementara `1` (sedang lengkap/diproses).
4. Merge dan upload.
5. Jika sukses, set status `2`, `final_pdf_path`, `merged_at`.
6. Jika gagal, set status `3` (gagal) dan log detail.
7. Jangan menelan exception tanpa jejak.

Status:

```text
0 = Pending
1 = Lengkap / siap merge
2 = Selesai
3 = Gagal merge
```

Perlu penyesuaian kecil:

- `DocumentMergeGroup::getStatusLabelAttribute()`
- `getStatusBadgeClassAttribute()`
- filter status di `resources/views/dokter/merge-flow/groups.blade.php`

### 5.6 Final scan log

Setiap final PDF tetap membuat satu `ScanLog`:

```text
source          = merge_flow
event           = merge_final
document_type   = FINAL
document_number = root_document_number (nomor invoice)
vendor_name     = group vendor
ftp_path        = FINAL/{vendor}/{filename}
metadata        = merge_group_id, merge_flow_id, item_ids, group_key, page_order
```

Tambahan metadata yang disarankan:

```json
{
  "merge_group_id": 123,
  "merge_flow_id": 1,
  "group_key": "2512014",
  "approval_document_number": "724/SBS-HO/DIR/PP/VII/2026",
  "item_ids": [1, 2],
  "page_order": ["invoice", "approval"]
}
```

Tujuannya agar audit jelas: final PDF ini berasal dari invoice dan approval mana.

### 5.7 Konfigurasi flow

#### Opsi UI

Buat/edit flow dengan 2 step:

| Order | Document Type | Link Field | Link Label |
|---:|---|---|---|
| 1 | `SLIP PEMBUKUAN AP` | kosong | kosong |
| 2 | `PERSETUJUAN PEMBAYARAN` | `invoice_numbers` | `Invoice Numbers` |

`MergeFlowController` saat ini sudah mendukung minimal 2 step, jadi tidak perlu perubahan validasi untuk flow 2-step.

#### Opsi seeder

Perbarui `database/seeders/MergeFlowSeeder.php`:

- gunakan `updateOrCreate` berdasarkan slug
- gunakan document type id 1 dan id 5 (atau resolve berdasarkan slug)
- set `link_field = invoice_numbers`
- set `link_label = Invoice Numbers`
- hapus/ubah step lama yang memakai `INVOICE` id 2 dan `PEMBAYARAN` id 4

Contoh target:

```text
Flow: INV-APPROVAL
1. SLIP PEMBUKUAN AP
2. PERSETUJUAN PEMBAYARAN (link_field: invoice_numbers)
```

Jika flow lama `BA-INV-SP` masih dipakai untuk keperluan lain, jangan hapus. Buat flow baru dan nonaktifkan flow lama bila perlu.

### 5.8 Normalisasi nomor dan vendor

#### Nomor invoice

Gunakan satu fungsi canonical untuk matching:

```php
protected function normalizeGroupKey(string $value): string
{
    $value = trim($value);
    $value = preg_replace('/[\x00-\x1F\x7F]/', ' ', $value) ?? '';
    $value = preg_replace('/\s+/', ' ', $value) ?? '';

    return strtoupper($value);
}
```

Yang perlu diperhatikan:

- jangan menghapus `/` atau `-` karena nomor dokumen bisa mengandung keduanya
- untuk nomor invoice numerik seperti `2512014`, normalisasi ini cukup
- jika OCR menghasilkan `2,512,014`, tambahkan aturan khusus hanya untuk field invoice

#### Vendor

Saat ini group matching memakai `vendor_name` mentah. Risiko:

- `PT DUTA WAHANA PERSADA` vs `DUTA WAHANA PERSADA`
- huruf besar/kecil
- spasi ganda

Rekomendasi:

- simpan `vendor_name` yang sudah dinormalisasi untuk group key
- atau tambahkan kolom/ID vendor canonical bila tersedia
- jika belum ada vendor master, minimal lakukan `trim`, collapse spaces, uppercase

---

## 6. Kesiapan untuk BA (Fase 2)

Fase 1 tidak menambahkan BA, tetapi struktur harus siap.

### 6.1 Yang sudah siap dari fase 1

- `MergeFlowService` tidak hardcode jumlah step.
- `checkAndTriggerMerge()` menghitung step unik.
- `performFinalMerge()` mengurutkan item berdasarkan `order`.
- Group item unik per group + document type.
- Fan-out sudah generik untuk field array.

### 6.2 Yang perlu ditambahkan saat BA masuk

Jika BA harus menjadi halaman pertama:

```text
order 1 = BA
order 2 = invoice
order 3 = approval
```

Tetapi anchor/group key tetap **invoice**, bukan BA. Karena itu step invoice tidak lagi bisa diasumsikan sebagai `order === 1`.

Solusi yang disarankan:

1. Migration baru:

```php
Schema::table('merge_flow_steps', function (Blueprint $table) {
    $table->boolean('is_anchor')->default(false)->after('order');
});
```

2. Set `is_anchor = true` pada step invoice.
3. `MergeFlowService` memakai anchor step untuk menentukan group key:

```text
jika step is_anchor:
    group key = document_number step tersebut
jika bukan anchor:
    group key = extractLinkedNumbers(step)
```

4. BA step harus memiliki field yang mengarah ke invoice, misalnya:

```text
link_field = invoice_number
link_label = Invoice Number
```

5. UI flow perlu checkbox “Jadikan anchor”.

### 6.3 Prasyarat data untuk BA

BA hanya bisa digabung ke group invoice jika BA memuat referensi ke invoice, misalnya:

- `invoice_number`
- `no_inv`
- nomor lain yang bisa dipetakan ke invoice

Jika BA hanya memuat nomor BA dan invoice yang memuat nomor BA (arah sebaliknya), maka perlu strategi tambahan:

- reverse lookup dari invoice ke BA, atau
- dua fase attachment, atau
- group key berbasis BA lalu invoice/approval attach melalui BA.

Ini harus diverifikasi dengan dokumen BA nyata sebelum fase 2.

---

## 7. File yang Akan Diubah

| File | Perubahan |
|---|---|
| `config/services.php` | Tambah aturan prompt `invoice_numbers` |
| `app/Services/Ocr/GeminiEngine.php` | Normalisasi `invoice_numbers`; opsi OCR multi-halaman untuk approval |
| `app/Services/MergeFlowService.php` | Fan-out, multi-link, distinct step count, idempotency, status gagal, merge di luar transaksi |
| `app/Models/DocumentMergeGroup.php` | Label/badge status 3 (gagal) |
| `database/seeders/MergeFlowSeeder.php` | Flow invoice + approval, `updateOrCreate`, `link_field = invoice_numbers` |
| `resources/views/dokter/merge-flow/groups.blade.php` | Filter/tampilan status gagal dan info fan-out (opsional) |
| `tests/Feature/Dokter/MergeFlowFanOutTest.php` | Test fan-out, out-of-order, partial, duplicate, vendor mismatch |
| `tests/Unit/Dokter/InvoiceNumberNormalizerTest.php` | Test normalisasi array/string/duplikat |
| `agents/planning-implementasi-merge-flow-invoice-approval.md` | Dokumen ini |

Tidak ada migration baru untuk fase 1.

Migration baru hanya diperlukan pada fase 2 (`is_anchor`), dan opsional index:

```php
$table->index(['document_number', 'order'], 'idx_merge_items_number_order');
```

---

## 8. Rencana Pengujian

### 8.1 Unit test normalisasi

File: `tests/Unit/Dokter/InvoiceNumberNormalizerTest.php`

Kasus:

| Input | Output yang diharapkan |
|---|---|
| `["2512014","2604001"]` | `["2512014","2604001"]` |
| `"2512014, 2604001"` | `["2512014","2604001"]` |
| `"2512014\n2604001"` | `["2512014","2604001"]` |
| `["2512014","2512014"]` | `["2512014"]` |
| `[]` | `[]` |
| `null` | `[]` |
| `" 2512014 "` | `["2512014"]` |

### 8.2 Feature test fan-out

File: `tests/Feature/Dokter/MergeFlowFanOutTest.php`

Gunakan:

- `Storage::fake('ftp_final')`
- PDF fixture kecil yang valid
- `DocumentType`, `MergeFlow`, `MergeFlowStep` dibuat langsung di test
- `PdfMergeService` asli atau mock sesuai kebutuhan

Kasus wajib:

1. **3 invoice sudah ada, approval masuk**
   - 3 group selesai
   - 3 final PDF dibuat
   - setiap final berisi halaman invoice + approval
   - `linked_numbers` approval berisi 3 nomor

2. **Approval masuk lebih dulu**
   - 3 group pending
   - invoice masuk satu per satu
   - group selesai saat pasangannya lengkap

3. **Baru 1 invoice tersedia**
   - 1 group selesai
   - 2 group pending
   - final PDF hanya 1

4. **Duplicate invoice number di approval**
   - tidak membuat group ganda
   - tidak merge ganda

5. **Approval di-scan ulang**
   - tidak membuat final PDF duplikat
   - group tetap status 2

6. **Approval tanpa `invoice_numbers`**
   - tidak membuat group
   - warning tercatat
   - scan log tetap sukses OCR

7. **Vendor berbeda**
   - invoice vendor A tidak digabung dengan approval vendor B

8. **Urutan halaman**
   - halaman pertama final PDF = halaman invoice
   - halaman berikutnya = halaman approval

9. **Status gagal merge**
   - `PdfMergeService` melempar exception
   - group menjadi status 3
   - tidak ada final path palsu

### 8.3 Regression test

- OCR single-page invoice tetap 1 halaman.
- OCR approval multi-halaman mengambil seluruh `invoice_numbers`.
- `ProcessScanFile` tetap mengunggah file asli ke FTP.
- Merge flow lama tidak error jika `link_field` berupa string tunggal.
- `linked_numbers` string lama tetap bisa dibaca.

### 8.4 Perintah verifikasi

```bash
php vendor/bin/phpunit tests/Unit/Dokter/InvoiceNumberNormalizerTest.php
php vendor/bin/phpunit tests/Feature/Dokter/MergeFlowFanOutTest.php
php vendor/bin/phpunit --testsuite Unit
php vendor/bin/pint --test app/Services/MergeFlowService.php app/Services/Ocr/GeminiEngine.php
```

---

## 9. Risiko & Mitigasi

| Risiko | Dampak | Mitigasi |
|---|---|---|
| Flow masih memakai document type lama (id 3/2/4) | Merge tidak pernah jalan | Perbaiki flow ke id 1 + id 5, verifikasi lewat data nyata |
| `invoice_numbers` tidak konsisten | Fan-out gagal atau hanya 1 nomor | Prompt eksplisit, normalisasi array/string, validasi warning |
| Daftar invoice ada di halaman 2/3 | Sebagian invoice tidak ter-merge | Pass kedua OCR semua halaman khusus approval |
| Approval datang sebelum invoice | Group pending/orphan | Group key = nomor invoice; `handleRootDocument` mencari group yang sudah ada |
| Approval di-scan ulang | Merge ganda / final tertimpa | Guard status 2, lock group, idempotency |
| Dua worker memproses bersamaan | Unique constraint / double merge | `lockForUpdate`, transaksi per group, retry unique violation |
| Vendor OCR berbeda | Group tidak ketemu | Normalisasi vendor; pertimbangkan vendor ID |
| Nomor invoice beda format | Group tidak ketemu | Canonical key; jangan hapus `/` dan `-` |
| Approval mencakup beberapa vendor | Sebagian invoice tidak ter-merge | Asumsikan 1 approval = 1 vendor; jika tidak, cocokkan per baris invoice + vendor |
| Merge di dalam DB transaction | Lock lama / timeout | Pisahkan file I/O ke setelah commit |
| Merge gagal | Group “Lengkap” tanpa PDF | Status 3 + retry + alert |
| Final PDF menimpa file lain | Kehilangan arsip | Nama final deterministik + cek status 2; file asli tidak disentuh |
| Final PDF duplikat approval 3x | Storage naik | Wajar; monitor ukuran dan retensi |
| FPDI gagal pada PDF tertentu | Final tidak terbentuk | Validasi PDF, log, retry manual |
| Backfill data lama | Dokumen lama tidak tergabung | Command backfill terpisah, tidak otomatis |
| UI menampilkan approval di banyak group | Terlihat seperti duplikat | Tampilkan label “fan-out” dan jumlah group |
| `status=3` belum ada di UI | Group gagal tidak terlihat | Update label/badge/filter |

---

## 10. Rollout

### 10.1 Sebelum deploy

1. Backup database.
2. Backup folder FTP `FINAL` dan dokumen asli.
3. Siapkan data uji:
   - 1 approval dengan 3 nomor invoice
   - 3 invoice yang sesuai
4. Pastikan flow config sudah benar di staging.

### 10.2 Deploy

1. Deploy kode.
2. Jalankan:

```bash
php artisan config:clear
php artisan queue:restart
```

3. Update flow di UI atau jalankan seeder yang sudah diperbarui.
4. Pastikan `merge_flow_enabled` aktif.

### 10.3 Verifikasi produksi

1. Scan 1 approval + 3 invoice.
2. Cek `document_merge_groups`:
   - 3 group
   - status 2
   - `root_document_number` = nomor invoice
3. Cek `document_merge_group_items`:
   - tiap group punya item invoice order 1
   - tiap group punya item approval order 2
4. Cek FTP:
   - 3 file di `FINAL/{vendor}/`
   - file asli tetap ada
5. Buka PDF:
   - halaman invoice di depan
   - approval di belakang
6. Cek `scan_logs`:
   - 3 log `merge_final`
   - `document_number` = nomor invoice
   - metadata `item_ids` benar

### 10.4 Backfill (opsional)

Data lama yang sudah ada di `scan_logs` tetapi belum punya group tidak otomatis diproses.

Rekomendasi:

- buat command `merge-flow:backfill`
- filter berdasarkan flow dan rentang tanggal
- jalankan dalam mode `--dry-run` terlebih dahulu
- idempotent: jangan buat group/final duplikat

Jangan jalankan backfill otomatis saat deploy.

---

## 11. Acceptance Criteria

Implementasi dianggap selesai jika:

- [x] Flow `SLIP PEMBUKUAN AP` → `PERSETUJUAN PEMBAYARAN` aktif.
- [x] Approval dengan 3 invoice menghasilkan 3 group dan 3 final PDF.
- [x] Setiap final PDF berisi invoice yang benar di halaman awal, approval di halaman berikutnya.
- [x] File asli invoice dan approval tidak berubah.
- [x] Approval yang datang sebelum invoice tetap menghasilkan group pending dan selesai saat invoice datang.
- [x] Approval dengan 3 invoice tetapi baru 1 invoice tersedia menghasilkan 1 final + 2 pending.
- [x] `linked_numbers` menyimpan array 3 nomor.
- [x] Approval tanpa `invoice_numbers` tidak membuat group dan mencatat warning.
- [x] Approval di-scan ulang tidak membuat final PDF duplikat.
- [x] Vendor berbeda tidak tercampur.
- [x] Merge gagal menghasilkan status 3 dan bisa di-retry.
- [x] Semua unit/feature test lulus.
- [x] Laravel Pint lulus.
- [x] Tidak ada error `trim(): Argument #1 must be of type string, array given`.
- [x] `FINAL/` tetap terpisah dari folder dokumen asli.
- [x] Struktur kode siap untuk penambahan BA (anchor step dapat ditambahkan tanpa refactor besar).

Semua kriteria di atas diverifikasi lewat automated test, kecuali merge ke FTP final nyata
yang belum dijalankan (lihat bagian "Catatan / batasan").

---

## 12. Checklist Implementasi

### Fase 1 — Invoice + Approval

- [x] Update prompt `invoice_numbers`
- [x] Normalisasi `invoice_numbers` di `GeminiEngine`
- [x] Pass kedua OCR untuk approval multi-halaman
- [x] Refactor `extractLinkedNumber()` → `extractLinkedNumbers()`
- [x] Loop fan-out di `handleChildDocument()`
- [x] Simpan `linked_numbers` sebagai array bila > 1
- [x] `checkAndTriggerMerge()` hitung step unik
- [x] Guard status 2
- [x] Pindahkan merge ke luar transaksi
- [x] Tambah status 3 dan error handling
- [x] Update label/badge UI
- [x] Update `MergeFlowSeeder`
- [x] Test unit normalisasi
- [x] Test feature fan-out
- [x] Test regresi OCR single-page
- [ ] Verifikasi manual dengan data nyata
  - [x] Flow `INV-APPROVAL` aktif di database (dicek 2026-10-26): `SLIP PEMBUKUAN AP` (order 1) → `PERSETUJUAN PEMBAYARAN` (order 2, `link_field = invoice_numbers`).
  - [ ] Merge ke FTP final nyata; menunggu kredensial FTP.

### Fase 2 — BA (belum dikerjakan)

- [ ] Verifikasi BA memiliki referensi ke invoice
- [ ] Migration `is_anchor` pada `merge_flow_steps`
- [ ] UI checkbox anchor
- [ ] Step BA + invoice + approval
- [ ] Test urutan BA → invoice → approval
- [ ] Test BA datang sebelum/sesudah invoice

### Fase 3 — Operasional

- [ ] UI detail group per invoice
- [ ] Retry merge gagal
- [ ] Backfill data lama
- [ ] Monitoring status pending/gagal
- [ ] Optimasi cache file approval saat fan-out besar

---

## 13. Asumsi yang Perlu Diverifikasi Saat Implementasi

1. Dokumen yang disebut “invoice” di data aktual adalah `SLIP PEMBUKUAN AP` (id 1). Jika ternyata `INVOICE` (id 2), ganti step 1.
2. Dokumen approval adalah `PERSETUJUAN PEMBAYARAN` (id 5), bukan `PEMBAYARAN` (id 4).
3. Satu approval hanya mencakup invoice dari satu vendor.
4. Nomor invoice unik dalam satu vendor.
5. Daftar invoice pada approval bisa berada di halaman 1 atau tersebar di beberapa halaman.
6. File asli tidak boleh diubah; final PDF selalu file baru.

---

## 14. Ringkasan Perubahan Perilaku

| Skenario | Sebelum | Sesudah |
|---|---|---|
| Approval berisi 3 invoice | Hanya 1 nomor yang dibaca / error | 3 group dibuat |
| Invoice sudah ada | Tidak ada fan-out | Approval masuk ke semua group yang cocok |
| Invoice belum ada | Group tidak konsisten | Group pending dibuat per nomor invoice |
| Final PDF | Satu final per group | Satu final per invoice, isi invoice + approval |
| File asli | Tidak berubah | Tetap tidak berubah |
| Scan ulang | Berpotensi merge ganda | Idempotent, status 2 dijaga |
| BA | Belum didukung | Disiapkan lewat anchor step fase 2 |

---

## 15. Catatan Implementasi (2026-10-21)

### File yang diubah / dibuat

| File | Perubahan |
|---|---|
| `config/services.php` | Aturan prompt `invoice_numbers` eksplisit: array, semua nomor, jangan digabung. |
| `app/Services/InvoiceNumberNormalizer.php` | **Baru** — normalisasi nomor invoice/group key/vendor. |
| `app/Services/Ocr/GeminiEngine.php` | Normalisasi `invoice_numbers`, alias field approval, pass kedua seluruh PDF untuk approval multi-halaman. |
| `app/Services/Ocr/Contracts/OcrEngineInterface.php` | Parameter opsional `$pdfOcrPages` pada kontrak engine. |
| `app/Services/MergeFlowService.php` | Fan-out per nomor invoice, multi-link, merge di luar transaksi, cache lock, status gagal, metadata audit. |
| `app/Services/PdfMergeService.php` | Ukuran halaman sumber dipertahankan saat merge (bukan dipaksa A4). |
| `app/Models/DocumentMergeGroup.php` | Label/badge status `3 = Gagal Merge`. |
| `app/Models/ScanLog.php` | `merge_status` dan `total_pages` masuk `$fillable`. |
| `database/seeders/MergeFlowSeeder.php` | Flow baru `INV-APPROVAL` (idempotent), legacy `BA-INV-SP` dipertahankan. |
| `database/seeders/DocumentTypeSeeder.php` | Tambah `PERSETUJUAN PEMBAYARAN`, seed idempotent. |
| `resources/views/dokter/merge-flow/groups.blade.php` | Filter status gagal. |
| `tests/Unit/Dokter/InvoiceNumberNormalizerTest.php` | **Baru** — 10 test normalisasi. |
| `tests/Feature/Dokter/GeminiEngineApprovalMultiPageTest.php` | **Baru** — 2 test OCR approval multi-halaman. |
| `tests/Feature/Dokter/MergeFlowFanOutTest.php` | **Baru** — 10 test fan-out/merge. |

### Perilaku akhir

- Invoice tetap OCR 1 halaman. Approval multi-halaman melakukan pass kedua seluruh PDF, lalu `invoice_numbers` digabung dan dideduplikasi.
- Satu approval dapat masuk ke banyak group; group key = `vendor_name` ternormalisasi + nomor invoice.
- Approval yang datang lebih dulu membuat group pending; invoice yang datang kemudian memicu merge.
- Final PDF selalu file baru di `FINAL/{vendor}/`; invoice/approval asli hanya dibaca.
- Urutan halaman final mengikuti `order`: invoice lalu approval.
- Group selesai (`status=2`) tidak di-merge ulang saat dokumen di-scan ulang.
- Kegagalan merge menghasilkan `status=3`, `final_pdf_path` tetap null, dan dapat dipicu ulang dengan scan ulang.
- `PdfMergeService` sekarang mempertahankan ukuran halaman sumber.

### Hasil verifikasi

| Verifikasi | Hasil |
|---|---|
| `php -l` seluruh file yang diubah | Tidak ada syntax error |
| `phpunit tests/Unit/Dokter/InvoiceNumberNormalizerTest.php` | 10 test, 12 assertion — OK |
| `phpunit tests/Feature/Dokter/GeminiEngineApprovalMultiPageTest.php` | 2 test, 10 assertion — OK |
| `phpunit tests/Feature/Dokter/GeminiEngineSinglePageOcrTest.php` | 5 test, 27 assertion — OK |
| `phpunit tests/Feature/Dokter/MergeFlowFanOutTest.php` | 10 test, 68 assertion — OK |
| `phpunit tests/Feature/Dokter` (semua test Dokter) | 17 test, 105 assertion — OK |
| `phpunit tests/Unit/Dokter` | 18 test, 32 assertion — OK |
| `phpunit --testsuite Unit` | 131 test, 315 assertion — OK |
| Laravel Pint (file terkait) | Lulus |
| Seeder flow di database aktual | Flow `INV-APPROVAL` aktif: `SLIP PEMBUKUAN AP` (order 1) → `PERSETUJUAN PEMBAYARAN` (order 2, `invoice_numbers`) |

### Finalisasi (2026-10-26)

- `MergeFlowService::performFinalMerge()` dipastikan memakai urutan `order` menaik: halaman invoice di depan, approval di belakang. Tidak ada urutan halaman terbalik.
- Test `test_final_pdf_page_order_is_invoice_then_approval` diperluas untuk memverifikasi metadata audit `page_order`, `group_key`, `approval_document_number`, `total_pages`, dan `item_ids`.
- Flow `INV-APPROVAL` di database aktual dicek read-only dan sudah aktif dengan konfigurasi yang benar.

### Catatan / batasan

- Verifikasi merge ke FTP final nyata belum dijalankan karena kredensial FTP final tidak tersedia di environment ini. Pengujian merge memakai `Storage::fake('ftp_final')` dan PDF nyata yang dibuat di test.
- Data aktual sudah memiliki 3 invoice (`2512014`, `2604001`, `2604002`) dan 1 approval (`724/SBS-HO/DIR/PP/VII/2026`), tetapi group lama belum dibuat ulang. Backfill tetap terpisah sesuai rencana.
- Fase 2 (BA + `is_anchor`) belum diimplementasikan.
- Retry manual via UI belum dibuat; retry saat ini terjadi jika dokumen diproses ulang dan group berstatus 3.
