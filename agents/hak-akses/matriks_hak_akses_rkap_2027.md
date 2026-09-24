# Matriks Hak Akses & Wewenang Penyusunan RKAP 2027

Berdasarkan pedoman dan alur bisnis PT Satria Bahana Sarana (SBS), berikut adalah pemetaan hak akses (*Role-Based Access Control*) untuk setiap entitas yang terlibat dalam siklus penyusunan RKAP 2027.

## 1. Seluruh Departemen (Unit Kerja)
**Peran:** *End-User / Submitter*
*   **Akses Data:** *Create, Read, Update, Submit* (Terbatas pada departemennya sendiri).
*   **Wewenang & Tugas:**
    *   Mengisi **Form 1** (Sasaran & Asesmen Risiko).
    *   Mengisi **Form 2** (Jadwal Rencana Kerja bulanan).
    *   Mengisi **Form 3** (Anggaran Biaya Rutin / OPEX).
    *   Mengisi **Form 4** (Anggaran Investasi / CAPEX) beserta proposal kelayakan.
*   **Batasan:** Tidak dapat melihat atau mengubah usulan anggaran departemen lain.

## 2. Departemen Risk Management
**Peran:** *Validator Asesmen Risiko*
*   **Akses Data:** *Read, Evaluate, Approve/Reject* (Khusus Form 1).
*   **Wewenang & Tugas:**
    *   Mengevaluasi kesesuaian identifikasi risiko dari seluruh departemen.
    *   Memberikan persetujuan atau meminta perbaikan jika *risk rating* dan rencana mitigasi tidak logis.

## 3. Departemen Manajemen Aset
**Peran:** *Validator Investasi (CAPEX)*
*   **Akses Data:** *Read, Evaluate* (Khusus Form 4 & Proposal Investasi).
*   **Wewenang & Tugas:**
    *   Memeriksa kelayakan usulan investasi non-rutin.
    *   Membahas usulan investasi bersama Direksi dan menyiapkan bahan untuk *Stage Gate Review*.

## 4. Departemen Anggaran
**Peran:** *Super Administrator / Konsolidator*
*   **Akses Data:** *Full Read, Compile, Consolidate, Broadcast*.
*   **Wewenang & Tugas:**
    *   Mendistribusikan Nota Dinas dan Pedoman RKAP ke seluruh sistem/departemen.
    *   Menerima dan mengunci (*lock*) data Form 1, 2, 3, dan 4 dari seluruh departemen.
    *   Melakukan evaluasi silang dan mengkonsolidasikan seluruh usulan menjadi satu *Draft* Final RKAP.

## 5. VP / Pimpinan Unit Usaha
**Peran:** *Internal Reviewer*
*   **Akses Data:** *Read, Discuss, Request Adjustment*.
*   **Wewenang & Tugas:**
    *   Melakukan pembahasan anggaran berjenjang dengan departemen di bawahnya dan Departemen Anggaran.
    *   Melakukan efisiensi dan pemotongan anggaran (*budget cut*) jika dinilai berlebihan sebelum masuk ke *Draft* Final.

## 6. Direksi (PT SBS)
**Peran:** *Strategic Initiator & Internal Approver*
*   **Akses Data:** *Read (Executive Summary), Evaluate, Approve*.
*   **Wewenang & Tugas:**
    *   Memberikan parameter dan arahan strategis di awal fase *Top-Down*.
    *   Meninjau kelayakan usulan investasi skala besar.
    *   Mengesahkan dan menandatangani RKAP 2027 secara internal.

## 7. PT Bukit Multi Investama (BMI)
**Peran:** *External Gatekeeper*
*   **Akses Data:** *Read (Final Draft & Capex Proposal), Gate Approval*.
*   **Wewenang & Tugas:**
    *   Bertindak sebagai penentu dalam *Stage Gate Review* untuk kelayakan investasi.
    *   Memberikan Persetujuan Prinsip ( *Alignment*) terhadap keseluruhan draft RKAP sebelum disahkan secara final.

## 8. Dewan Komisaris & Holding (Pemegang Saham)
**Peran:** *Final Approver & Stakeholder*
*   **Akses Data:** *Read (Final Document), Final Approve*.
*   **Wewenang & Tugas:**
    *   Holding menyampaikan aspirasi makro perusahaan di awal tahun.
    *   Dewan Komisaris memberikan pengesahan paling akhir (mengetok palu) untuk mendistribusikan RKAP yang siap diimplementasikan.