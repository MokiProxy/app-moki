# Planning G6 — Dukungan COA 16 Digit & Pemilihan COA pada Form Anggaran

> **Gap:** G6 (Parsial/Konsistensi) — Bagian 3
> **Prioritas:** P0 — Wajib
> **Status:** Belum Terpenuhi ❌
> **Referensi:** `agents/recap-pedoman-rkap/hasil-analisis-gap.md` baris 31, 67, 77, 122–124, 162.

---

## 1. Latar Belakang

Pedoman menuntut **Chart of Accounts 16 digit**. Fakta saat ini:

- `chart_of_accounts.code` = `varchar(10)` — migration `2026_09_11_000004_add_columns_to_chart_of_accounts_table.php`.
- Seeder `database/seeders/ChartOfAccountsSeeder.php` mengisi code **4 digit** (`6000`–`9109`).
- Tidak ada validasi `digits:16` di `app/Http/Requests` manapun.
- COA tidak bisa dipilih langsung pada form OPEX (`erkap_routine_costs`); COA di-sync otomatis dari
  `erkap_cost_element.chart_of_account_id` (`ChartOfAccountController::sync`):
  ```php
  // app/Http/Controllers/Erkap/ChartOfAccountController.php:49
  $chartOfAccount = ChartOfAccount::where('code', $costElement->code)->first();
  ```
  (code cost element dijadikan code COA — rapuh bila cost element bukan COA).

---

## 2. Tujuan

1. Kolom `chart_of_accounts.code` mendukung **16 digit numerik**.
2. Validasi wajib 16 digit pada pembuatan/import COA.
3. **COA dapat dipilih langsung** pada form OPEX & CAPEX (bukan hanya derivasi dari cost element).
4. Backfill data seeded 4 digit → 16 digit (pad-kan kiri/kanan dengan skema yang bisa dipertanggung-jawabkan).

---

## 3. Desain Solusi

### 3.1 Migration Schema & Backfill

Migration `2026_09_26_000006_expand_chart_of_accounts_code_to_16.php`:

```php
// 1. Ubah tipe kolom
Schema::table('chart_of_accounts', fn (Blueprint $t) =>
    $t->string('code', 16)->change()            // tetap uniq, bukan numeric constraint di DB
);

// 2. Backfill data lama (Postgres):
//    pad kanan '-0' sampai 16 digit agar unik & deterministik: '6000' -> '6000000000000000'
DB::statement("
  UPDATE chart_of_accounts
     SET code = rpad(code, 16, '0')
   WHERE length(code) < 16
");
```

> Keputusan: `rpad(code,16,'0')` konservatif & reversible (data 4 digit tersimpan di prefix).
> Cadangan: `lpad` — pilih satu & dokumentasikan. Konfirmasi ke Finance sebelum menjalankan di
> produksi; jangan jalankan dua kali (guard `length(code) < 16`).

### 3.2 Validasi Request

- `app/Http/Requests/StoreChartOfAccountRequest` (baru atau yang ada) + import:
  ```php
  'code' => ['required', 'string', 'size:16', 'regex:/^\d{16}$/', 'unique:chart_of_accounts,code']
  ```
- Rule baru reusable `App\Rules\Coa16Digits` untuk dipakai juga pada import Excel COA.

### 3.3 Model & Helper

Update `App\Models\ChartOfAccount`:
- accessor `formattedCodeAttribute(): string` → format `XXXX-XXXX-XXXX-XXXX` untuk tampilan;
- scope `search(?string)`.

Update `App\Models\Erkap\CostElement`:
- tambah konstanta/helper `coaSuggestion(): ?ChartOfAccount` untuk fallback sync lama.

### 3.4 Select COA di Form OPEX & CAPEX

- `App\Http\Controllers\Erkap\RoutineCostController::create/edit` → load
  `ChartOfAccount::expense()->orderBy('code')->get()` untuk dropdown.
- `App\Http\Requests\StoreRoutineCostRequest` / `UpdateRoutineCostRequest`:
  tambah `chart_of_account_id` nullable|exists (validasi: bila kosong → fallback ke
  `erkap_cost_element.chart_of_account_id`; bila terisi → override).
- `erkap_routine_costs.chart_of_account_id` **belum ada kolom** → migration
  `2026_09_26_000007_add_chart_of_account_id_to_routine_costs_table.php`:
  `foreignId('chart_of_account_id')->nullable()->after('erkap_cost_element_id')->constrained('chart_of_accounts')`.
- Update `App\Models\Erkap\RoutineCost`: `$fillable` + relasi `chartOfAccount()`.
- Sama untuk **CAPEX** (`erkap_investment_plans`) — tambah kolom & dropdown di
  `InvestmentPlanController::create/edit` (buat kolom sekalian di migration G9 bila dilakukan
  bersamaan). Tampilkan COA di list/export budget.
- `erkap_budget_opex` sudah memiliki `chart_of_account_id` (lihat migration 2026_09_22_093524) —
  pastikan konsolidasi OPEX membaca relasi ini dari `routine_costs`.

### 3.5 Sync & Import/Export

- `ChartOfAccountController::sync` (line 42-49): perkaya agar membuat/menemukan COA via
  `code` 16 digit bila belum ada, dan set `CostElement.chart_of_account_id`.
- View `chart-of-account/index.blade.php`: tampilkan `code` terformat 16 digit; form create pakai
  validasi 16 digit.
- Jika ada import COA (Excel) — tambahkan rule `Coa16Digits`.

### 3.6 Tests

- Unit: `Coa16DigitsTest` — terima 16 digit, tolak lebih/kurang/kurang dari 16 & non-digit.
- Feature: `RoutineCostCoaTest` — memilih COA langsung di form tersimpan & terpakai di konsolidasi.
- Test backfill: seeded `6000` → `6000000000000000` (integration test migration).

---

## 4. Urutan Implementasi

1. Migration expand + backfill.
2. Model helper & rule validasi.
3. Kolom `chart_of_account_id` pada `routine_costs` (+ optional `investment_plans`).
4. Controller + request + view (dropdown, format tampilan).
5. Perbaikan `sync` COA.
6. Seeder (bila ingin 16 digit penuh — catatan: seeder lama 4 digit; backfill menangani).
7. Tests.

---

## 5. Kriteria Penerimaan

- [ ] `chart_of_accounts.code` menampung 16 digit & unik.
- [ ] Membuat COA dengan code ≠ 16 digit **ditolak** (pesan jelas).
- [ ] Cost element `6000` dapat di-sync ke COA `6000000000000000`.
- [ ] Dropdown "Chart of Account" ada di form OPEX (dan CAPEX); pemilihan langsung tersimpan.
- [ ] Konsolidasi OPEX memakai COA terpilih untuk `erkap_budget_opex.chart_of_account_id`.
- [ ] Semua halaman COA menampilkan kode terformat.

---

## 6. Risiko & Dependensi

- **Dampak besar backfill:** semua sistem membandingkan `chart_of_accounts.code` dgn `cost_element.code`
  (sync + summary) — pastikan mapping di-update (visualisasi & ekspor budget) setelah expand.
- **Risiko 16 digit di code field lain:** `erkap_cost_elements.code` tetap string pendek —
  jangan samakan panjang; COA & cost element adalah entitas berbeda.
- **Dependensi:** G9 menambah kolom CAPEX — satukan migration COLUMN jika diimplementasikan bersamaan.
- **Risiko konsolidasi OPEX:** `BudgetOpexConsolidationService` harus membaca `chart_of_account_id`
  dari routine cost bukan hanya cost center; verifikasi & update service tsb.