# Planning G7 — Evaluasi & Penanganan Risiko (CRUD Risk Treatments + Selaras Terminologi)

> **Gap:** G7 (Parsial) — Bagian 2 & 4 (Form 1)
> **Prioritas:** P1 — Penting
> **Status:** Parsial ⚠️
> **Referensi:** `agents/recap-pedoman-rkap/hasil-analisis-gap.md` baris 32, 58, 126–128, 165.

---

## 1. Latar Belakang

Tahap **Evaluasi & Penanganan Risiko** (rencana perlakuan risiko) belum "hidup" di UI:

- Tabel `erkap_risk_treatments`, model `RiskTreatment`, seeder & test ADA, **tetapi tidak ada
  controller/rute/view** (grep `RiskTreatmentController|risk-treatments` → hanya test/migration/model).
- Terminologi strategi tidak sesuai pedoman:
  - Pedoman: **Avoidance / Reduction / Sharing / Acceptance**.
  - DB (`erkap_department_risk_strategies.strategy`): CHECK `('avoid','reduce','transfer','accept')`
    (migration `2026_09_11_000002_change_strategy_enum_to_department_risk_strategies_table.php`).
  - `RiskTreatment.treatment_type`: `avoid/mitigate/transfer/accept`
    (migration `2026_09_22_093011_create_risk_treatments_table.php`).
  - **"Sharing" (berbagi/transfer risiko kepada pihak kedua) tidak terwakili.**
- Label UI `DepartmentRiskStrategy::getStrategies()`: `avoid=Hindari, reduce=Kurangi, transfer=Transfer, accept=Terima`.
- Map import/export Form 1: `F1ImportExportService::STRATEGY_MAP` + `strategyLabel`.

---

## 2. Tujuan

1. **Aktifkan CRUD `erkap_risk_treatments`**: controller, routes, views, menu, permission.
2. **Selaraskan terminologi** menjadi `Avoidance / Reduction / Sharing / Acceptance` (nilai simpan +
   label tampilan + import/export + constraint DB) tanpa merusak data lama.
3. Setiap Risk Identification yang sudah punya strategi **wajib memiliki minimal 1 treatment** sebelum
   di-submit ke Dept Risk Management (integrasi G2).

---

## 3. Desain Solusi

### 3.1 Terminologi (Satu Sumber)

Buat enum `App\Enums\ErkapRiskTreatmentType` (atau `ErkapRiskStrategy`):

```php
enum ErkapRiskTreatmentType: string
{
    case Avoidance    = 'avoidance';   // Hindari
    case Reduction    = 'reduction';   // Kurangi/Mitigasi
    case Sharing      = 'sharing';     // Berbagi (transfer/insurance)
    case Acceptance   = 'acceptance';  // Terima
    public function label(): string
    public static function legacyMap(): array // ['avoid'=>'avoidance','reduce'=>'reduction','transfer'=>'sharing','mitigate'=>'reduction','accept'=>'acceptance']
    public static function values(): array
}
```

### 3.2 Migrasi Data & Constraint

Migration `2026_09_26_000008_unify_risk_strategy_terminology.php`:

1. Update `erkap_risk_treatments.treatment_type`:
   ```sql
   UPDATE erkap_risk_treatments
      SET treatment_type = CASE treatment_type
            WHEN 'avoid'    THEN 'avoidance'
            WHEN 'reduce'   THEN 'reduction'
            WHEN 'mitigate' THEN 'reduction'
            WHEN 'transfer' THEN 'sharing'
            WHEN 'accept'   THEN 'acceptance'
            ELSE treatment_type END
     WHERE treatment_type IN ('avoid','reduce','mitigate','transfer','accept');
   ```
   + `ALTER COLUMN treatment_type TYPE VARCHAR(12)` + CHECK `IN ('avoidance','reduction','sharing','acceptance')`.
2. Update `erkap_department_risk_strategies.strategy`:
   ```sql
   UPDATE erkap_department_risk_strategies SET strategy='avoidance'  WHERE strategy='avoid';
   UPDATE ... 'reduce'->'reduction', 'transfer'->'sharing', 'accept'->'acceptance';
   ```
   + drop & re-add CHECK constraint `('avoidance','reduction','sharing','acceptance')`.
3. Update model & label:
   - `DepartmentRiskStrategy::getStrategies()` → pakai enum label.
   - `RiskTreatment::getTreatmentTypes()` → pakai enum.
   - `Form1ImportExportService::STRATEGY_MAP` & `strategyLabel()` → terima BOTH legacy & baru
     (`'avoid'|'avoidance'|'hindari' => avoidance`, dst) agar file Excel lama tetap bisa diimpor.
   - `Form1TemplateExport::VALIDATIONS[15]` dropdown → `Hindari, Kurangi, Berbagi, Terima`.
   - Check template contoh baris (`'Kurangi'` tetap ok via map).

### 3.3 CRUD Risk Treatments

Controller baru `App\Http\Controllers\Erkap\RiskTreatmentController` (pola seragam dgn
`DepartmentRiskStrategyController`):

- `index(?erkap_risk_identification_id)` — list treatment (filter risiko).
- `create` / `store(StoreRiskTreatmentRequest)`.
- `edit` / `update(UpdateRiskTreatmentRequest)`.
- `destroy`.

`StoreRiskTreatmentRequest`:
```php
'erkap_risk_identification_id' => ['required','exists:erkap_risk_identifications,id'],
'erkap_department_risk_strategy_id' => ['nullable','exists:erkap_department_risk_strategies,id'],
'treatment_type' => ['required', Rule::enum(ErkapRiskTreatmentType::class)],
'description' => ['required','string'],
'responsible_party' => ['required','string'],
'target_date' => ['required','date'],
'status' => ['required', Rule::in(['planned','in_progress','completed','cancelled'])],
'result' => ['nullable','string'],
```

Routes (prefix `risk-treatments`):
```php
GET  /erkap/risk-treatments                 index    permission:erkap.risk-treatments.view
GET  /erkap/risk-treatments/create          create   permission:erkap.risk-treatments.create
POST /erkap/risk-treatments                 store    permission:erkap.risk-treatments.create
GET  /erkap/risk-treatments/{t}/edit        edit     permission:erkap.risk-treatments.edit
PUT  /erkap/risk-treatments/{t}             update   permission:erkap.risk-treatments.edit
DELETE /erkap/risk-treatments/{t}           destroy  permission:erkap.risk-treatments.delete
```

Views di `resources/views/erkap/risk-treatment/` (index/create/edit).

Menu sidebar: tambah pada grup "Sasaran & Manajemen Risiko" di bawah "Strategi Risiko Departemen":
```
<li>@can('erkap.risk-treatments.view') <a href="{{ route('erkap.risk-treatments.index') }}">Perlakuan Risiko</a>
```

### 3.4 Guard Submit Form 1 (integrasi G2)

Update `App\Models\Erkap\RiskIdentification`:
```php
public function hasTreatment(): bool { return $this->riskTreatments()->exists(); }
```
`RiskIdentificationController::submit` (lihat G2): sebelum `ApprovalService::submit`,
panggil `validateHasStrategyAndWorkProgram()` + `if (! $this->hasTreatment()) throw`.

### 3.5 Seeder & Permission

- `RolePermissionSeeder`: tambah resource `risk-treatments` ke `$erkapResources` & `$costOwnerResources`
  (cost owner boleh input, risk manager view).
- `RiskTreatmentSeeder` update `treatment_type` ke terminologi baru.

### 3.6 Tests

- Unit: `RiskTreatmentTest` (update existing) — enum terima nilai legacy via legacyMap, tolak nilai tak dikenal.
- Feature: `RiskTreatmentFeatureTest` — CRUD penuh; submit risk tanpa treatment ditolak; penyimpanan
  `sharing` sukses.
- Update `RiskIdentificationBusinessRulesTest` / `WorkProgramBusinessRulesTest` bila memakai strategi lama.

---

## 4. Urutan Implementasi

1. Enum + label.
2. Migration terminologi (data + constraint).
3. Update model & map import/export/template.
4. Controller + requests + routes + views + menu.
5. Guard submit Form 1 (`hasTreatment`).
6. Seeder/permission + tests.

---

## 5. Kriteria Penerimaan

- [ ] "Perlakuan Risiko" menu muncul & CRUD berjalan penuh.
- [ ] Nilai strategi & treatment tersimpan sebagai `avoidance/reduction/sharing/acceptance`.
- [ ] Excel Form 1 lama (hindari/kurangi/transfer/terima) tetap bisa diimpor (legacy mapping).
- [ ] Risk tanpa treatment **tidak dapat** di-submit ke Dept RM.
- [ ] Seeder & test lulus dengan terminologi baru.

---

## 6. Risiko & Dependensi

- **Dependensi:** G2 (submit Form 1) — guard `hasTreatment` menempel pada flow itu.
- **Risiko:** constraint CHECK di Postgres harus di-drop dulu sebelum update data, lalu di-recreate;
  ikuti pola migration `2026_09_11_000002_change_strategy_enum...` yang sudah ada.
- **Risiko:** istilah "Sharing" ≠ "Transfer" — konfirmasi business: mapping `transfer → sharing`
  (asuransi/berbagi) sesuai pedoman; jangan mengubah makna data historis selain terminologi.