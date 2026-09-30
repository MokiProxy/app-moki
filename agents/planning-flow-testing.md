# Planning: End-to-End Flow Testing Modul ERKAP

> **Tujuan:** Menguji seluruh alur aplikasi ERKAP dari awal sampai akhir berdasarkan
> tutorial pengisian untuk pengguna akhir, memastikan sistem berjalan dengan baik,
> dan mendokumentasikan setiap gap yang ditemukan.

---

## 1. Ringkasan Eksekutif

| Item | Detail |
|---|---|
| **Aplikasi** | app-moki (Laravel 8.75 + PostgreSQL) |
| **Modul** | ERKAP (Rencana Kerja dan Anggaran Perusahaan) |
| **Jumlah Controller** | 48 controllers |
| **Jumlah Model** | 44 models + Traits |
| **Jumlah Service** | 5 services |
| **Jumlah Migration** | 17 migrations |
| **Jumlah Seeder** | 17 seeders |
| **Jumlah Route** | ~50 route groups (prefix `/erkap`) |
| **Test yang Ada** | 16 unit tests + 28 feature tests |
| **Helper Test** | `ActsAsErkapRole`, `BuildsErkapChain` |

---

## 2. Arsitektur & Komponen Kunci

### 2.1 Lifecycle RKAP (6 Fase)

```
initiation → preparation → consolidation → finalization → approved → archived
```

| Fase | Label | Kunci Input? |
|---|---|---|
| `initiation` | Inisiasi & Kick-off | Tidak |
| `preparation` | Penyusunan | Tidak |
| `consolidation` | Konsolidasi & Review | Tidak |
| `finalization` | Finalisasi & Pengesahan | **YA** |
| `approved` | Disahkan | **YA** |
| `archived` | Arsip | **YA** |

### 2.2 Approval Matrix

| Dokumen | Level 1 | Level 2 | Level 3 |
|---|---|---|---|
| **Register Risiko** | Manajer Risiko | - | - |
| **Program Kerja** | PPK | Budget Controller | - |
| **Biaya Rutin** | PPK | Budget Controller | - |
| **Rencana Investasi** | PPK | Manajemen Aset | Direksi Keuangan |
| **Periode RKAP** | Komisaris | Direksi | - |

### 2.3 Stage Gate Review (khusus Investasi)

```
Proposal (PPK) → Analisis CBA (PPK) → Aset (Manajemen Aset) → Direksi Keuangan
```

### 2.4 Role yang Terlibat

| Role | Nama Permission |
|---|---|
| Admin/Operator Master Data | `erkap-admin` |
| Pemilik Anggaran | `erkap-cost-owner` |
| PPK | `erkap-ppk` |
| Budget Controller | `erkap-controller` |
| Manajemen Aset | `erkap-manajemen-aset` |
| Manajer Risiko | `erkap-risk-manager` |
| Direksi Keuangan | `erkap-direksi-keuangan` |
| Komisaris | `erkap-komisaris` |
| Direksi | `erkap-direksi` |
| Auditor | `erkap-auditor` |

### 2.5 Struktur Database (Tabel Utama)

#### Core Transactional
- `erkap_rkap` — Periode RKAP utama
- `erkap_work_programs` — Program kerja
- `erkap_routine_costs` — Biaya rutin (OPEX)
- `erkap_investment_plans` — Rencana investasi (CAPEX)
- `erkap_revenue_plans` — Rencana pendapatan
- `erkap_expense_plans` — Rencana beban
- `erkap_budget_capex` — Budget CAPEX
- `erkap_budget_opex` — Budget OPEX
- `erkap_budget_realizations` — Realisasi budget
- `erkap_program_realizations` — Realisasi program

#### Risk Management
- `erkap_risk_identifications` — Identifikasi risiko
- `erkap_risk_identification_reasons` — Alasan risiko
- `erkap_risk_identification_impacts` — Dampak risiko
- `erkap_risk_analysis` — Analisis risiko
- `erkap_risk_assessments_monthly` — Penilaian risiko bulanan
- `erkap_risk_appetites` — Risk appetite
- `erkap_risk_taxonomies` — Taksonomi risiko
- `erkap_risk_types` — Tipe risiko
- `erkap_risk_scales` — Skala risiko
- `erkap_risk_probabilities` — Probabilitas
- `erkap_risk_impacts` — Dampak level
- `erkap_risk_score_levels` — Level skor
- `erkap_rating_criterias` — Kriteria rating
- `erkap_department_risk_strategies` — Strategi risiko departemen

#### COA / Code Segment (Cascading)
- `erkap_business_units` → `erkap_locations` → `erkap_management_areas` → `erkap_activities`
- `erkap_cost_element_categories` → `erkap_cost_elements`
- `erkap_cost_centers`
- `erkap_chart_of_accounts`

#### Investment & Financial
- `erkap_investation_types`, `erkap_investation_criterias`, `erkap_investattion_categories`
- `erkap_investment_stage_gates`
- `erkap_profit_loss_statements`
- `erkap_performance_scorecards`

#### Supporting
- `erkap_company_targets`, `erkap_department_targets`
- `erkap_kickoff_attendees`
- `erkap_zbb_reviews`
- `erkap_approvals` (polymorphic)
- `erkap_audit_logs` (polymorphic)
- `erkap_report_items`

---

## 3. Rencana Pengujian End-to-End

### Fase 0: Persiapan Environment

| Step | Aktivitas | Status |
|---|---|---|
| 0.1 | Pastikan PostgreSQL running dan database `asset_management_system` ada | ⬜ |
| 0.2 | Jalankan `php artisan migrate:fresh --seed` | ⬜ |
| 0.3 | Pastikan semua role ter-create via seeder | ⬜ |
| 0.4 | Buat user test untuk setiap role | ⬜ |
| 0.5 | Verifikasi aplikasi bisa diakses via browser | ⬜ |

### Fase 1: Inisiasi & Kick-off (PPK)

| Step | Aktivitas | Referensi Tutorial | Status |
|---|---|---|---|
| 1.1 | Login sebagai PPK, buka menu Lifecycle RKAP → Periode RKAP | §3.1 | ⬜ |
| 1.2 | Buat periode baru untuk tahun berjalan | §3.2 | ⬜ |
| 1.3 | Buka halaman detail periode | §3.3 | ⬜ |
| 1.4 | Isi Kick-off: tanggal, catatan, peserta (nama, divisi, hadir) | §3.3a | ⬜ |
| 1.5 | Isi Arahan Direksi: unggah lampiran, catatan | §3.3b | ⬜ |
| 1.6 | Klik "Majukan ke Penyusunan" | §3.4 | ⬜ |
| 1.7 | Verifikasi fase berubah ke `preparation` | - | ⬜ |
| 1.8 | Verifikasi bagan lifecycle, status dokumen, riwayat approval, audit trail tampil | §3 note | ⬜ |

### Fase 2: Penyusunan — Form 1 (Sasaran & Risiko)

| Step | Aktivitas | Referensi Tutorial | Status |
|---|---|---|---|
| 2.1 | **Sasaran Perusahaan** — isi sasaran/target perusahaan | §4.1.1 | ⬜ |
| 2.2 | **Sasaran Departemen** — isi sasaran departemen, kaitkan dengan sasaran perusahaan, rating prioritas | §4.1.2 | ⬜ |
| 2.3 | **Identifikasi Risiko** — tambah risiko dengan alasan dan dampak | §4.2.1 | ⬜ |
| 2.4 | **Import/Export** — uji upload massal via Form 1 Import/Export | §4.2.1 | ⬜ |
| 2.5 | **Analisis Risiko** — tetapkan probabilitas & dampak, verifikasi skor & level otomatis | §4.2.2 | ⬜ |
| 2.6 | **Peringkat Risiko** — beri peringkat/prioritas | §4.2.2 | ⬜ |
| 2.7 | **Strategi Risiko** — isi strategi departemen, perlakuan risiko, penanggung jawab, target | §4.2.3 | ⬜ |
| 2.8 | Verifikasi: risiko tanpa strategi/program kerja/perlakuan **tidak bisa** diajukan | §4.2.4 | ⬜ |

### Fase 2: Penyusunan — Form 2 (Program Kerja)

| Step | Aktivitas | Referensi Tutorial | Status |
|---|---|---|---|
| 2.9 | Buat program kerja yang terhubung dengan risiko | §4.3.1 | ⬜ |
| 2.10 | Isi target besaran (unit) | §4.3.2 | ⬜ |
| 2.11 | Isi rincian rencana bulanan (Jan–Des) | §4.3.2 | ⬜ |
| 2.12 | Isi persentase/kumulatif pencapaian | §4.3.2 | ⬜ |
| 2.13 | Verifikasi urutan: risiko harus ada sebelum program kerja | §4 note | ⬜ |

### Fase 2: Penyusunan — Form 3 (Biaya Rutin / OPEX)

| Step | Aktivitas | Referensi Tutorial | Status |
|---|---|---|---|
| 2.14 | Buat baris anggaran biaya rutin | §4.4.1 | ⬜ |
| 2.15 | Isi elemen biaya, pusat biaya | §4.4.2 | ⬜ |
| 2.16 | Isi besaran/kuantitas, satuan, harga satuan | §4.4.2 | ⬜ |
| 2.17 | Isi rincian per bulan | §4.4.2 | ⬜ |
| 2.18 | Verifikasi total terhitung otomatis | §4.4.2 | ⬜ |
| 2.19 | Verifikasi urutan: program kerja harus ada sebelum biaya rutin | §4 note | ⬜ |

### Fase 2: Penyusunan — Form 4 (Investasi / CAPEX)

| Step | Aktivitas | Referensi Tutorial | Status |
|---|---|---|---|
| 2.20 | Buat rencana investasi untuk program kerja | §4.5.1 | ⬜ |
| 2.21 | Isi kategori, tipe, kriteria investasi | §4.5.2 | ⬜ |
| 2.22 | Isi deskripsi kebutuhan, jumlah, satuan, harga satuan | §4.5.2 | ⬜ |
| 2.23 | Isi prioritas urutan investasi | §4.5.2 | ⬜ |
| 2.24 | Isi rincian pembayaran per bulan | §4.5.2 | ⬜ |
| 2.25 | **Wajib melampirkan proposal kelayakan (PDF)** | §4.5.3 | ⬜ |
| 2.26 | Verifikasi: investasi tanpa proposal **tidak bisa** diajukan | §4.5.3 | ⬜ |
| 2.27 | Cek Anggaran Investasi (Ringkasan Nilai & Distribusi Pembayaran) | §4.5.4 | ⬜ |

### Fase 3: Pengajuan & Persetujuan

| Step | Aktivitas | Referensi Tutorial | Status |
|---|---|---|---|
| 3.1 | **Submit Register Risiko** — PPK ajukan | §5 table | ⬜ |
| 3.2 | **Manajer Risiko** — setujui/tolak | §5 table | ⬜ |
| 3.3 | **Submit Program Kerja** — PPK ajukan | §5 table | ⬜ |
| 3.4 | **PPK** — setujui tingkat 1 | §5 table | ⬜ |
| 3.5 | **Budget Controller** — setujui tingkat 2 | §5 table | ⬜ |
| 3.6 | **Submit Biaya Rutin** — PPK ajukan | §5 table | ⬜ |
| 3.7 | **PPK** — setujui tingkat 1 | §5 table | ⬜ |
| 3.8 | **Budget Controller** — setujui tingkat 2 | §5 table | ⬜ |
| 3.9 | **Submit Rencana Investasi** — PPK ajukan | §5 table | ⬜ |
| 3.10 | **Stage Gate: Proposal** — PPK nilai | §5.1.1 | ⬜ |
| 3.11 | **Stage Gate: Analisis CBA** — PPK nilai | §5.1.2 | ⬜ |
| 3.12 | **Stage Gate: Aset** — Manajemen Aset nilai | §5.1.3 | ⬜ |
| 3.13 | **Stage Gate: Direksi Keuangan** — Direksi Keuangan nilai | §5.1.4 | ⬜ |
| 3.14 | **PPK** — setujui tingkat 1 investasi | §5 table | ⬜ |
| 3.15 | **Manajemen Aset** — setujui tingkat 2 | §5 table | ⬜ |
| 3.16 | **Direksi Keuangan** — setujui tingkat 3 | §5 table | ⬜ |
| 3.17 | Uji penolakan: dokumen ditolak → status "Ditolak" → perbaiki → ajukan ulang | §5.2.4 | ⬜ |
| 3.18 | Uji giliran: non-approver tidak bisa menyetujui | §5.2.1 | ⬜ |

### Fase 4: Konsolidasi & Review

| Step | Aktivitas | Referensi Tutorial | Status |
|---|---|---|---|
| 4.1 | **Review ZBB** — build baris anggaran | §6.1 | ⬜ |
| 4.2 | PPK review setiap baris, beri status & justifikasi | §6.1 | ⬜ |
| 4.3 | Verifikasi konsolidasi hanya jalan setelah semua baris direview | §6.1 | ⬜ |
| 4.4 | **Konsolidasi OPEX** — cek subsistem anggaran | §6.2 | ⬜ |
| 4.5 | **Konsolidasi CAPEX** — cek subsistem anggaran | §6.2 | ⬜ |
| 4.6 | Verifikasi variance dihitung otomatis | §6.2 | ⬜ |
| 4.7 | **Rencana Pendapatan** — isi | §6.3 | ⬜ |
| 4.8 | **Rencana Beban** — isi | §6.3 | ⬜ |
| 4.9 | **Laba Rugi (P&L)** — verifikasi tampilan | §6.3 | ⬜ |
| 4.10 | **Simulasi Skenario** — verifikasi | §6.3 | ⬜ |
| 4.11 | **Dashboard** — cek monitoring kelengkapan | §6.4 | ⬜ |
| 4.12 | **Analytics & Widgets** — cek | §6.4 | ⬜ |
| 4.13 | **Report Center** — cek | §6.4 | ⬜ |
| 4.14 | Klik "Majukan ke Finalisasi & Pengesahan" | §6 | ⬜ |

### Fase 5: Finalisasi & Pengesahan

| Step | Aktivitas | Referensi Tutorial | Status |
|---|---|---|---|
| 5.1 | PPK **mengajukan periode RKAP** | §7.1 | ⬜ |
| 5.2 | **Komisaris** — setujui tingkat 1 | §7.2 | ⬜ |
| 5.3 | **Direksi** — setujui tingkat 2 → status Disahkan | §7.2 | ⬜ |
| 5.4 | Verifikasi tanggal penetapan tercatat otomatis | §7.2 | ⬜ |
| 5.5 | PPK klik **"Tandai Didistribusikan"** | §7.3 | ⬜ |
| 5.6 | PPK klik **"Majukan ke Arsip"** | §7.4 | ⬜ |
| 5.7 | Verifikasi data terkunci setelah finalisasi | §7 note | ⬜ |
| 5.8 | Uji Reset Fase (saat Draft/Ditolak) | §7 note | ⬜ |

### Fase 6: Monitoring & Realisasi

| Step | Aktivitas | Referensi Tutorial | Status |
|---|---|---|---|
| 6.1 | **Realisasi Anggaran (BvA)** — isi realisasi bulanan | §8.1 | ⬜ |
| 6.2 | Verifikasi selisih anggaran vs realisasi dihitung otomatis | §8.1 | ⬜ |
| 6.3 | **Realisasi Program Kerja** — isi capaian per bulan | §8.2 | ⬜ |
| 6.4 | **Risk Assessment Bulanan** — isi penilaian | §8.3 | ⬜ |
| 6.5 | **Performance Scorecard (KPI)** — pantau KPI | §8.4 | ⬜ |
| 6.6 | **Dashboard** — pantau kinerja | §8 | ⬜ |
| 6.7 | **Analytics & Widgets** — pantau | §8 | ⬜ |
| 6.8 | **Report Center** — pantau | §8 | ⬜ |

### Fase 7: Audit Trail & Validasi Lainnya

| Step | Aktivitas | Status |
|---|---|---|
| 7.1 | Verifikasi setiap aksi tercatat di Audit Trail | ⬜ |
| 7.2 | Uji permission matrix (setiap role hanya bisa akses menu yang berhak) | ⬜ |
| 7.3 | Uji COA cascade (Business Unit → Location → Management Area → Activity) | ⬜ |
| 7.4 | Uji validasi form (field wajib, format, dll) | ⬜ |
| 7.5 | Uji notifikasi (email/database notification) | ⬜ |

---

## 4. Metodologi Pengujian

### 4.1 Pendekatan

1. **Manual Testing via Browser** — Ikuti tutorial langkah demi langkah
2. **API Testing** — Gunakan Postman/curl untuk uji endpoint langsung
3. **Automated Test** — Jalankan existing PHPUnit tests sebagai baseline
4. **Database Verification** — Cek langsung ke database untuk verifikasi data

### 4.2 Test Data

| Data | Keterangan |
|---|---|
| Periode RKAP | Tahun 2026 |
| Divisi | Minimal 2 divisi untuk uji multi-divisi |
| User per role | Minimal 1 user per role |
| Risiko | Minimal 2 risiko (1 lengkap, 1 tidak lengkap untuk uji validasi) |
| Program Kerja | Minimal 2 program |
| Biaya Rutin | Minimal 2 biaya |
| Investasi | Minimal 2 investasi (1 dengan proposal, 1 tanpa) |

### 4.3 Environment Testing

| Item | Value |
|---|---|
| URL | `http://localhost:8000` (atau sesuai env) |
| Database | PostgreSQL `asset_management_system` |
| Browser | Chrome/Firefox (terbaru) |
| API Tool | Postman / curl |

---

## 5. Kriteria Keberhasilan (Definition of Done)

| No | Kriteria | Status |
|---|---|---|
| 1 | Semua 6 fase lifecycle bisa dijalankan tanpa error | ⬜ |
| 2 | Semua form (Form 1–4) bisa diisi dan disimpan | ⬜ |
| 3 | Semua alur approval berjalan sesuai matrix | ⬜ |
| 4 | Stage Gate Review berjalan untuk investasi | ⬜ |
| 5 | Konsolidasi OPEX/CAPEX berjalan | ⬜ |
| 6 | P&L dan Simulasi Skenario tampil | ⬜ |
| 7 | Finalisasi & pengesahan periode RKAP berhasil | ⬜ |
| 8 | Data terkunci setelah finalisasi | ⬜ |
| 9 | Monitoring & realisasi bisa diisi | ⬜ |
| 10 | Audit Trail tercatat untuk setiap aksi | ⬜ |
| 11 | Permission matrix berjalan dengan benar | ⬜ |
| 12 | Tidak ada error 500 atau exception yang tidak tertangani | ⬜ |

---

## 6. Potensi Gap yang Akan Diketahui

Berikut area yang berpotensi memiliki gap berdasarkan analisis kode:

| No | Area | Risiko | Dasar Analisis |
|---|---|---|---|
| 1 | **Form 1 Import/Export** | Sedang | Perlu file template yang valid |
| 2 | **Stage Gate Review** | Tinggi | Flow kompleks, 4 gate berurutan |
| 3 | **Konsolidasi OPEX/CAPEX** | Sedang | Perlu data yang cukup |
| 4 | **P&L & Simulasi Skenario** | Sedang | Perlu revenue & expense plan terisi |
| 5 | **Notifikasi** | Rendah | Perlu mail driver yang benar |
| 6 | **Reset Fase** | Sedang | Hanya bisa saat Draft/Ditolak |
| 7 | **Locking setelah Finalisasi** | Tinggi | Perlu verifikasi menyeluruh |
| 8 | **Multi-divisi approval** | Sedang | Perlu user di divisi yang sama |
| 9 | **ZBB Review** | Sedang | Perlu data anggaran yang cukup |
| 10 | **Proposal upload investasi** | Rendah | Perlu file PDF valid |

---

## 7. Output yang Diharapkan

1. **agents/planning-flow-testing.md** — Dokumen ini
2. **agents/report-gap-flow-testing.md** — Laporan gap yang ditemukan selama testing
3. **Test Results** — Screenshot/bukti pengujian per fase
4. **Database State** — Verifikasi data tersimpan dengan benar

---

## 8. Estimasi Waktu

| Fase | Estimasi |
|---|---|
| Fase 0: Persiapan | 15 menit |
| Fase 1: Inisiasi | 15 menit |
| Fase 2: Penyusunan (Form 1–4) | 45 menit |
| Fase 3: Pengajuan & Persetujuan | 45 menit |
| Fase 4: Konsolidasi & Review | 30 menit |
| Fase 5: Finalisasi & Pengesahan | 15 menit |
| Fase 6: Monitoring & Realisasi | 20 menit |
| Fase 7: Audit & Validasi | 15 menit |
| **Total** | **~3.5 jam** |

---

## 9. Referensi

- Tutorial: `agents/tutorial-pengisian-erkap-end-user-new.md`
- Routes: `routes/routers/erkap.php`
- Models: `app/Models/Erkap/`
- Controllers: `app/Controllers/Erkap/`
- Services: `app/Services/Erkap/`
- Tests: `tests/Unit/Erkap/`, `tests/Feature/Erkap/`
- Test Helpers: `tests/Concerns/ActsAsErkapRole.php`, `tests/Concerns/BuildsErkapChain.php`
