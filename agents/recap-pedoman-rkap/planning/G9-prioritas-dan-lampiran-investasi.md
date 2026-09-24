# Planning G9 — Form 4: Urutan Prioritas Investasi & Lampiran Kelayakan

> **Gap:** G9 (Parsial/Konsistensi) — Bagian 4 (Form 4)
> **Prioritas:** P1 — Penting
> **Status:** Parsial ⚠️
> **Referensi:** `agents/recap-pedoman-rkap/hasil-analisis-gap.md` baris 34, 78, 133–136, 167.

---

## 1. Latar Belakang

Form 4 pedoman mewajibkan:
1. **Urutan Prioritas** investasi (rank atas usulan).
2. **Lampiran proposal kelayakan (CBA)** per usulan.

Fakta di codebase:

- `erkap_investment_plans` **tidak punya kolom `priority`/`priority_order`**
  (migration `2026_09_11_000002_create_investment_plans_table.php`).
- Kolom `cost_center_id` **ada di DB** (migration `2026_09_22_073418_add_missing_foreign_keys...`)
  **tetapi tidak ada di `InvestmentPlan::$fillable`**
  (`app/Models/Erkap/InvestmentPlan.php:17-42`) → mass-assignment tidak pernah mengisi cost center.
- Tidak ada bidang upload proposal/CBA di migration maupun form `investment-plan/create`.
- (G1 telah menangani stage gate & CBA JSON — file ini fokus pada kolom dasar + lampiran + prioritas;
  ikuti dan selaraskan dengan G1 agar tidak dobel.)

---

## 2. Tujuan

1. Tambah kolom `priority_order` (urutan prioritas per divisi/per rkap) pada `erkap_investment_plans`.
2. Masukkan `cost_center_id` ke `$fillable` & form/pilih cost center.
3. Tambah lampiran proposal kelayakan (file upload) pada investasi.
4. Tampilkan prioritas & lampiran pada list/detail/export CAPEX.

---

## 3. Desain Solusi

### 3.1 Migration

Migration `2026_09_26_000009_add_priority_and_attachment_to_investment_plans_table.php`:

| Kolom | Tipe | Keterangan |
|---|---|---|
| `priority_order` | smallint nullable | urutan prioritas (1 = tertinggi) |
| `proposal_file_path` | string nullable | path file proposal (PDF/DOC/XLSX) |
| `proposal_original_name` | string nullable | nama file asli (untuk display & download) |

> Catatan: G1 juga menambahkan `proposal_file_path`/`cba_*` — **implementasikan satu kali** di sini
> atau di G1; jangan dua migration dengan nama kolom sama. Koordinasi: buat semua kolom lampiran/CBA
> dalam satu migration dan kedua file planning mengacu padanya.

### 3.2 Model

`App\Models\Erkap\InvestmentPlan`:
- tambah ke `$fillable`: `cost_center_id`, `priority_order`, `proposal_file_path`, `proposal_original_name`;
- cast: `cost_center_id => integer`, `priority_order => integer`;
- relasi `costCenter()` → `belongsTo(CostCenter::class, 'cost_center_id')`.
- helper `downloadUrl(): ?string` memakai `Storage`.

### 3.3 Request & Validasi

`StoreInvestmentPlanRequest` / `UpdateInvestmentPlanRequest` (`app/Http/Requests` — verifikasi nama file aktual):

```php
'cost_center_id' => ['nullable', 'exists:cost_centers,id'],
'priority_order' => ['nullable', 'integer', 'min:1'],
'proposal_file'  => ['nullable', 'file', 'mimes:pdf,doc,docx,xls,xlsx', 'max:20480'],
```

- Uniqueness prioritas opsional: `unique` per `work_program/division` bila diisi.
- Upload diproses di `InvestmentPlanController::store/update`:
  ```php
  if ($request->hasFile('proposal_file')) {
      $data['proposal_file_path'] = $request->file('proposal_file')->store('erkap/investment-proposals', 'public');
      $data['proposal_original_name'] = $request->file('proposal_file')->getClientOriginalName();
  }
  ```

### 3.4 Controller & Views

- `InvestmentPlanController::create/edit`: load `CostCenter::orderBy('code')` + `divisions` untuk
  dropdown cost center; form tambah input `priority_order` + file upload.
- `resources/views/erkap/investment-plan/form.blade.php` (atau create/edit) + index + show:
  - kolom "Prioritas" (sortable desc) & tombol **Download Proposal**.
- `InvestmentPlanController::download(InvestmentPlan $plan)` — route:
  ```php
  GET /erkap/investment-plans/{plan}/proposal  name=proposal  permission:erkap.investment-plans.view
  ```

### 3.5 Konsumsi Prioritas

- `BudgetCapexController::summary` & `paymentDistribution`: tambah `orderBy('priority_order')`
  per divisi di `InvestmentPlan` query, tampilkan nomor prioritas pada tabel.
- Konsolidasi CAPEX (`consolidate`): dihitung `total` per divisi — prioritas tidak mempengaruhi
  total, hanya urutan penyajian (dokumentasikan).

### 3.6 Tests

- Unit: `InvestmentPlanTest` (extend) — mass-assignment cost center tersimpan, `priority_order`
  validasi, download url.
- Feature: upload proposal (Storage fake), daftar CAPEX urut prioritas.

---

## 4. Urutan Implementasi

1. Migration kolom (koordinasi G1).
2. Model fillable + relasi + helper.
3. Request validasi + controller upload.
4. Views & sidebar (tombol download).
5. Summary/payment distribution urut prioritas.
6. Tests.

---

## 5. Kriteria Penerimaan

- [ ] `cost_center_id` tersimpan saat create investasi (tidak lagi hilang).
- [ ] Plan memiliki `priority_order` & tampilan urutan di index/summary CAPEX.
- [ ] File proposal bisa di-upload dan di-download.
- [ ] Validasi file (tipe PDF/doc/Excel, ≤ 20MB).
- [ ] Form tanpa file tetap valid (opsional).

---

## 6. Risiko & Dependensi

- **Dependensi:** G1 (stage gate/proposal) — satukan kolom attachment agar tidak konflik.
- **Risiko:** `priority_order` global vs per-divisi — tentukan scope: **per divisi per RKAP**
  (paling masuk akal untuk "urutan investasi" di Form 4). Validasi unik via whereHas.
- **Risiko:** field `unit_price`/`total` sudah wajib (check const `total = qty*unit_price`) —
  upload jangan mempengaruhi validasi numerik.