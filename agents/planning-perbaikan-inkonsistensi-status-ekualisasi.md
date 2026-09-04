# Planning: Perbaikan Inkonsistensi Status Ekualisasi (TO_BE_CHECK)

## Ringkasan Masalah

Logika penentuan status faktur pajak pada proses ekualisasi memiliki **3 area inkonsisten** di dalam codebase:

### 1. Method `export()` di `EqualizationController.php` (baris 197-203)
Status semua faktur yang ada di SPT **dan** GL selalu diberi `MATCH`, **tanpa memeriksa selisih PPN**.
Tidak ada status `TO_BE_CHECK`, padahal faktur dengan selisih ≠ 0 seharusnya `TO_BE_CHECK`.

### 2. View `resources/views/eqtax/equalization/index.blade.php` (baris 332-338)
Hanya merender 3 status (MATCH, SPT_ONLY, GL_ONLY). Status `TO_BE_CHECK` yang dikirim controller jatuh ke cabang `@else` sehingga **salah ditampilkan sebagai "GL Only"**.

### 3. Export styling `app/Exports/EqualizationExport.php` (baris 172-177)
`$statusColors` hanya punya 3 key (MATCH, SPT_ONLY, GL_ONLY). Status `TO_BE_CHECK` tidak punya warna sehingga fallback ke default (tanpa warna).

---

## Logika Referensi (yang BENAR) — method `equalization()`

```php
if ($spt && $gl) {
    $status = $selisih != 0 ? "TO_BE_CHECK" : "MATCH";
} elseif ($spt && !$gl) {
    $status = 'SPT_ONLY';
} else {
    $status = 'GL_ONLY';
}
```

| Status | Kondisi |
|--------|---------|
| MATCH | Ada di SPT & GL, dan PPN SPT == PPN GL |
| TO_BE_CHECK | Ada di SPT & GL, tetapi PPN SPT != PPN GL |
| SPT_ONLY | Ada di SPT, tidak ada di GL |
| GL_ONLY | Ada di GL, tidak ada di SPT |

Semua area lain HARUS mengikuti logika ini.

---

## Perubahan yang Dilakukan

### 1. `app/Http/Controllers/EQTax/EqualizationController.php` — method `export()`
Samakan logika penentuan status dengan `equalization()`: gunakan `$selisih_ppn != 0 ? 'TO_BE_CHECK' : 'MATCH'`.

### 2. `resources/views/eqtax/equalization/index.blade.php`
Tambah branch `@elseif($dt->status == 'TO_BE_CHECK')` sebelum `@else`, dengan badge/styling baru (warna oranye/sky, berbeda dari SPT_ONLY/GL_ONLY). Tambahkan class CSS `.status-to-be-check`.

### 3. `app/Exports/EqualizationExport.php`
Tambah key `'TO_BE_CHECK'` pada `$statusColors` dengan warna yang mudah dibedakan.

---

## Catatan

- Summary card "Total Faktur Cocok" (`count_match`) hanya menghitung status `MATCH` — tetap benar (TO_BE_CHECK bukan match sempurna).
- Tidak ada perubahan pada logika `equalization()` itu sendiri (sudah benar).
- Dokumen `analisis-proses-ekualisasi-restitusi-pajak.md` bersifat deskriptif desain; tidak diubah agar tidak mengubah spesifikasi, tetapi inkonsistensi di catatan dokumentasi dicatat di sini.

---

## Checklist Akhir
- [ ] `export()` menggunakan status TO_BE_CHECK saat selisih ≠ 0
- [ ] View menampilkan badge TO_BE_CHECK dengan benar
- [ ] Styling export menangani TO_BE_CHECK
- [ ] Syntax PHP valid (php -l)
