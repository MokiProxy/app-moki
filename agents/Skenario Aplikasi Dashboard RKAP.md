**SKENARIO APLIKASI DASHBOARD PROGRAM KERJA, RKAP, ANGGARAN & RISIKO**

_Konsep Perencanaan Terintegrasi, Pengendalian Anggaran, Kinerja, Risiko, Laba, dan Notifikasi_

# 1\. Latar Belakang

Perusahaan memerlukan satu aplikasi yang menjadi acuan resmi untuk menyusun, mereview, menyetujui, mengendalikan, memantau, dan mengevaluasi Program Kerja beserta Rencana Kerja dan Anggaran Perusahaan (RKAP) di setiap departemen. Data RKAP dikelola langsung di dalam aplikasi — bukan disusun di Excel lalu diinput ulang — sehingga Key User Departemen bisa memasukkan dan memutakhirkan data sejak tahap awal penyusunan sampai RKAP dinyatakan final.

RKAP di sini tidak berhenti pada program kerja dan anggaran biaya saja, tetapi juga mencakup target laba perusahaan. Setiap anggaran yang diajukan departemen pada dasarnya ikut menentukan apakah target laba tahunan bisa tercapai, sehingga aplikasi perlu menghubungkan sisi biaya — program kerja dan anggaran — dengan sisi hasil, yaitu target dan realisasi laba, dalam satu kesatuan, bukan dua hal yang dipantau terpisah.

Anggaran saja tidak cukup. Setiap program kerja perlu mempertimbangkan risiko yang berpotensi mengganggu pencapaian target, jadwal, biaya, kualitas, atau operasional. Karena itu proses identifikasi risiko, penilaian risiko, rencana mitigasi, penunjukan risk owner, dan kebutuhan biaya mitigasi dijalin langsung dengan program kerja dan anggaran, bukan dikelola terpisah.

# 2\. Tujuan Sistem

- Menjadi satu-satunya sumber data yang sah untuk Program Kerja, RKAP, dan target laba perusahaan.
- Memastikan setiap pos anggaran terhubung jelas dengan program kerja, target, KPI, dan risikonya.
- Menghubungkan penggunaan anggaran departemen dengan pencapaian target laba perusahaan secara keseluruhan.
- Mencegah pengajuan dana yang melampaui sisa anggaran yang tersedia.
- Menyediakan jalur pengalihan/re-alokasi anggaran yang terkontrol dan berjenjang, termasuk untuk anggaran yang belum atau tidak lagi memiliki program kerja.
- Memberi ruang bagi Direksi untuk melakukan penyesuaian anggaran departemen pada tahap awal penyusunan, sebelum RKAP dilanjutkan ke tahap review berikutnya.
- Memantau realisasi anggaran, progres program kerja, capaian KPI, kondisi risiko, laba, dan forecast dalam satu tampilan.
- Merekam audit trail atas setiap perubahan, persetujuan, penolakan, dan pengalihan anggaran.
- Mengirim notifikasi otomatis lewat WhatsApp dan kanal lain untuk kondisi penting atau tindakan yang perlu segera dilakukan.

# 3\. Prinsip Utama

| **Prinsip**            | **Penjelasan**                                                                                                            |
| ---------------------- | ------------------------------------------------------------------------------------------------------------------------- |
| Single Source of Truth | Data RKAP resmi hanya ada di aplikasi. Tidak ada lagi versi Excel yang diinput ulang belakangan.                          |
| Program-Driven Budget  | Anggaran tidak berdiri sendiri — setiap rupiah harus melekat pada program kerja tertentu.                                 |
| Profit-Linked Planning | Program kerja dan anggaran departemen disusun dengan mengacu pada target laba perusahaan, bukan semata-mata plafon biaya. |
| Risk-Based Budgeting   | Program kerja wajib menyertakan analisis risiko beserta mitigasinya, bukan sekadar catatan tambahan.                      |
| Budget Control         | Sistem memvalidasi ketersediaan budget secara otomatis sebelum pengajuan dana diproses.                                   |
| Segregation of Duties  | Orang yang membuat data tidak boleh merangkap sebagai penyetuju akhir atas data yang sama.                                |
| Versioning             | Setiap revisi RKAP dan perubahan budget tersimpan dengan nomor versi dan riwayatnya.                                      |
| Auditability           | Setiap tindakan penting tercatat lengkap: siapa yang melakukan, kapan, nilai sebelum dan sesudah, serta alasannya.        |
| Real-Time Notification | Kondisi penting dan tugas yang masih menunggu tindakan langsung dikirim ke WhatsApp pengguna terkait.                     |

# 4\. Aktor dan Hak Akses

| **Aktor**                | **Tanggung Jawab Utama**                                                                                                                                                                                                                                                      |
| ------------------------ | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Key User Departemen      | Mengisi Program Kerja, KPI, anggaran, risiko, dan mitigasi; mengajukan revisi dan submission; menyusun ulang anggaran bila diminta Direksi; mengisi formulir pengalihan anggaran tanpa program bila diperlukan; memantau dan menindaklanjuti catatan reviewer.                |
| PIC Program Kerja        | Memutakhirkan progres, target, realisasi kegiatan, kendala, dan risiko yang muncul di lapangan.                                                                                                                                                                               |
| Kepala/Atasan Departemen | Mereview, mengoreksi, dan memberi persetujuan tingkat departemen.                                                                                                                                                                                                             |
| Finance/Budget Control   | Memvalidasi pagu, sisa anggaran, commitment, realisasi, forecast, pengalihan dana, dan kontribusi anggaran terhadap capaian laba.                                                                                                                                             |
| Risk/Management Review   | Meninjau risiko, mitigasi, residual risk, serta dampak perubahan anggaran terhadap profil risiko.                                                                                                                                                                             |
| Atasan Keuangan/Anggaran | Memberi persetujuan akhir untuk pengalihan/revisi tertentu sesuai matrix kewenangan.                                                                                                                                                                                          |
| Direksi/Management       | Menetapkan target laba perusahaan dan pagu indikatif di awal periode; mereview submission awal seluruh departemen dan berwenang memangkas atau menyesuaikan anggarannya; memantau kondisi perusahaan secara keseluruhan; memberi persetujuan strategis; mengevaluasi kinerja. |
| Administrator Sistem     | Mengelola user, role, master data, workflow, parameter threshold, template notifikasi, dan konfigurasi integrasi.                                                                                                                                                             |

# 5\. Struktur Data Utama

Relasi data inti dibangun sebagai berikut:

- Perusahaan → Target Laba → Departemen → Program Kerja → KPI/Target → Anggaran → Pengajuan → Commitment → Realisasi
- Program Kerja → Risiko → Mitigasi → Risk Owner → Residual Risk
- Program Kerja/Anggaran → Revisi/Pengalihan → Approval History → Audit Trail
- Anggaran Tanpa Program Kerja → Formulir Pengalihan → Approval History → Audit Trail

# 6\. Skenario End-to-End Penyusunan RKAP

## 6.1 Inisiasi Periode RKAP

Admin membuka periode RKAP baru: menentukan tahun/periode, batas waktu input, struktur organisasi yang berlaku, template yang dipakai, serta target laba perusahaan dan pagu indikatif per departemen yang ditetapkan Direksi sebagai acuan awal.

## 6.2 Input Key User

Key User menyusun Program Kerja lengkap dengan tujuan, sasaran, KPI, PIC, jadwal, prioritas, kebutuhan sumber daya, dan rincian anggarannya, mengacu pada pagu indikatif departemen masing-masing.

## 6.3 Penyesuaian Anggaran oleh Direksi

Setelah seluruh departemen submit untuk pertama kali, Direksi mereview total pengajuan secara agregat dan membandingkannya dengan target laba perusahaan. Bila perlu, Direksi dapat memangkas atau menyesuaikan anggaran satu maupun beberapa departemen agar RKAP secara keseluruhan tetap sejalan dengan target laba. Departemen yang anggarannya disesuaikan wajib menyusun ulang program kerja atau prioritasnya mengikuti plafon baru sebelum lanjut ke tahap berikutnya.

## 6.4 Identifikasi Risiko

Key User memetakan risiko: penyebab, dampak, likelihood, impact, risk score, risk owner, rencana mitigasi, dan berapa biaya yang dibutuhkan untuk mitigasi tersebut — termasuk menyesuaikannya bila anggaran sudah dipangkas Direksi.

## 6.5 Validasi Otomatis

Sistem mengecek kelengkapan data, kemungkinan duplikasi, kepatuhan terhadap batas waktu, serta konsistensi antara total anggaran, KPI, risiko, dan mitigasi.

## 6.6 Review Departemen

Atasan departemen memeriksa apakah program yang diajukan sejalan dengan target departemen, lalu memberi catatan atau meminta revisi bila perlu.

## 6.7 Review Finance

Finance menilai kewajaran dan struktur anggaran, memastikan semuanya konsisten dengan pagu yang tersedia serta kontribusinya terhadap target laba.

## 6.8 Review Risiko

Risk reviewer memeriksa risiko-risiko berkategori tinggi/kritis dan menilai apakah mitigasinya sudah memadai.

## 6.9 Management Review

Program strategis, anggaran bernilai besar, dan risiko kritis diteruskan ke level management untuk direview lebih lanjut.

## 6.10 Final Approval

Setelah semua tahap persetujuan terlewati, sistem menerbitkan RKAP Final dan mengunci baseline-nya, lengkap dengan target laba yang telah disepakati.

# 7\. Mekanisme RKAP Final dan Baseline

Begitu final approval terbit, RKAP menjadi baseline resmi dan angkanya tidak bisa lagi diubah langsung oleh user. Perubahan hanya bisa dilakukan lewat workflow resmi: Revisi RKAP, Budget Adjustment, atau Budget Transfer, sesuai kewenangan masing-masing.

| **Status**              | **Makna**                                                                                                                     |
| ----------------------- | ----------------------------------------------------------------------------------------------------------------------------- |
| Draft                   | Masih disusun oleh Key User.                                                                                                  |
| Submitted               | Sudah diajukan, menunggu direview.                                                                                            |
| Adjusted by Direksi     | Anggaran departemen sedang atau telah disesuaikan Direksi; Key User perlu menyusun ulang sebelum lanjut ke review berikutnya. |
| Revision Required       | Dikembalikan ke Key User disertai catatan perbaikan.                                                                          |
| Department Approved     | Sudah disetujui di level departemen.                                                                                          |
| Finance Review          | Sedang divalidasi oleh Finance/Budget Control.                                                                                |
| Risk Review             | Sedang direview oleh tim risiko.                                                                                              |
| Management Review       | Sedang dalam tahap review manajemen.                                                                                          |
| Final Approved / Locked | Sudah menjadi baseline resmi.                                                                                                 |
| Revised                 | Baseline berubah melalui workflow revisi resmi.                                                                               |

# 8\. Budget Control dan Pencegahan Over Budget

Sebelum pengajuan dana diproses, sistem terlebih dahulu menghitung sisa anggaran yang tersedia (available budget), dengan formula dasar: Available Budget = Approved Budget − Realisasi − Commitment − Pending/Reserved Amount. Parameter-parameter ini bisa dikonfigurasi sesuai kebijakan perusahaan.

Approved Budget yang dipakai dalam perhitungan ini adalah angka final setelah melalui tahap penyesuaian Direksi dan seluruh proses approval — bukan angka pengajuan awal Key User.

| **Kondisi**          | **Tindakan Sistem**                                                     | **Notifikasi**                                      |
| -------------------- | ----------------------------------------------------------------------- | --------------------------------------------------- |
| Normal               | Pengajuan diproses sesuai approval matrix.                              | Notifikasi status normal, dikirim bila diperlukan.  |
| Warning              | Pengajuan tetap diproses, tapi sistem memberi peringatan.               | WA ke PIC/Atasan sesuai threshold yang berlaku.     |
| Critical             | Pengajuan butuh perhatian dan approval tambahan.                        | WA prioritas ke Key User, Atasan, dan Finance.      |
| Over Budget          | Pengajuan normal otomatis diblokir.                                     | WA otomatis memberitahukan kondisi overload budget. |
| Forecast Over Budget | Sistem memproyeksikan kebutuhan dana akan melampaui budget ke depannya. | WA alert forecast.                                  |

# 9\. Skenario Program Kerja Over Budget

Kalau realisasi dan commitment sebuah Program Kerja sudah menyentuh atau melewati anggarannya, sistem otomatis mengubah status program menjadi Critical/Over Budget. Sejak saat itu, pengajuan dana baru tidak bisa lagi lewat jalur normal.

Contoh sederhana: budget Rp750 juta, realisasi Rp700 juta, commitment Rp70 juta, lalu muncul pengajuan baru Rp50 juta. Sistem langsung mendeteksi ada shortfall dan memblokir transaksi tersebut.

Kalau program tetap harus dilanjutkan, Key User bisa mengajukan Budget Transfer Request dengan mencantumkan alasan, nominal, sumber anggaran, tujuan anggaran, dampaknya ke Program Kerja sumber, dampaknya ke risiko, serta justifikasi pengalihan.

# 10\. Skenario Pengalihan/Re-Alokasi Anggaran

Pengalihan anggaran selalu melalui workflow resmi. Secara default, pengalihan antar-program dalam satu departemen dibatasi sesuai kebijakan; sedangkan pengalihan antar-departemen memerlukan workflow khusus dengan level approval yang lebih tinggi.

| **Tahap** | **Proses**                                                               |
| --------- | ------------------------------------------------------------------------ |
| 1         | Key User memilih Program Sumber dan Program Tujuan.                      |
| 2         | Sistem menghitung available budget dari program sumber.                  |
| 3         | Key User mengisi nominal pengalihan beserta alasannya.                   |
| 4         | Sistem menilai dampak pengalihan terhadap KPI dan risiko program sumber. |
| 5         | Atasan Departemen mereview pengajuan.                                    |
| 6         | Finance/Budget Control memvalidasi ketersediaan budget.                  |
| 7         | Risk Review dijalankan jika pengalihan berdampak pada risiko.            |
| 8         | Atasan Keuangan/Anggaran memberi approval sesuai matrix kewenangan.      |
| 9         | Setelah semua approval selesai, sistem otomatis memindahkan budget.      |
| 10        | Audit trail dan riwayat versi diperbarui.                                |

# 11\. Skenario Anggaran Tanpa Program Kerja (Unallocated Budget)

Ada kalanya sebuah pos anggaran tidak memiliki program kerja yang jelas di baliknya — bisa karena programnya memang belum ditentukan sejak awal, atau karena Key User lupa menginputkan program kerja saat penyusunan RKAP. Kondisi seperti ini tidak dibiarkan menggantung begitu saja di dalam sistem.

Untuk anggaran semacam ini, departemen wajib mengisi Formulir Pengalihan Anggaran Tanpa Program di dalam sistem. Formulir ini minimal memuat sumber anggaran, alasan tidak atau belum adanya program kerja, serta rencana pemanfaatannya — apakah akan dialihkan ke program kerja lain yang sudah berjalan, disimpan sebagai cadangan (reserve) departemen, atau dikembalikan ke pool anggaran perusahaan.

Setelah formulir diajukan, prosesnya mengikuti alur approval pengalihan anggaran yang sama seperti pengalihan biasa: direview Atasan Departemen, divalidasi Finance/Budget Control, dan bila menyentuh area berisiko, turut melalui Risk Review sebelum mendapat persetujuan akhir sesuai matrix kewenangan.

# 12\. Integrasi Risiko dengan Anggaran

Setiap Program Kerja wajib punya risk assessment yang relevan. Sistem menghitung risk score dari likelihood dan impact, lalu mengelompokkan risiko ke level Low, Medium, High, atau Critical sesuai parameter yang ditetapkan perusahaan.

Jika anggaran ditarik dari program yang justru menjadi sumber dana mitigasi risiko, sistem wajib memunculkan warning atau memaksa dilakukannya Risk Review. Ketentuan ini juga berlaku untuk anggaran yang dialihkan lewat Formulir Pengalihan Anggaran Tanpa Program pada Bagian 11. Tujuannya sederhana: mencegah penghematan atau realokasi anggaran yang tanpa disadari justru menaikkan exposure risiko perusahaan.

# 13\. Monitoring Realisasi Program Kerja

Setelah RKAP final, setiap PIC memperbarui progres program kerjanya secara berkala. Sistem menghubungkan progres fisik/kinerja dengan realisasi finansial, sehingga terlihat apakah keduanya sejalan.

| **Indikator**      | **Contoh**      |
| ------------------ | --------------- |
| Budget             | Rp1.000.000.000 |
| Realisasi          | Rp650.000.000   |
| Commitment         | Rp200.000.000   |
| Available          | Rp150.000.000   |
| Progress Program   | 70%             |
| Budget Utilization | 65%             |
| Forecast           | Rp1.100.000.000 |
| Risk Status        | High            |

Kalau progres program dan penggunaan anggarannya tidak sejalan, sistem akan menampilkan indikator variance sebagai tanda peringatan.

# 14\. Target dan Monitoring Laba Perusahaan

Karena RKAP mencakup target laba dan bukan cuma anggaran biaya, sistem juga memantau capaian laba perusahaan secara berjenjang — dari level konsolidasi perusahaan sampai kontribusi masing-masing departemen.

| **Indikator**                          | **Contoh**                                                    |
| -------------------------------------- | ------------------------------------------------------------- |
| Target Laba Perusahaan                 | Rp50.000.000.000                                              |
| Realisasi Laba (s.d. periode berjalan) | Rp32.000.000.000                                              |
| Capaian terhadap Target                | 64%                                                           |
| Kontribusi Departemen terhadap Laba    | Sesuai proporsi pendapatan/efisiensi masing-masing departemen |
| Status                                 | On Track / Berisiko / Di Bawah Target                         |

Kalau proyeksi laba pada akhir periode diperkirakan meleset dari target, sistem mengeluarkan peringatan dini, sama seperti mekanisme forecast pada sisi anggaran. Dengan begitu Direksi bisa mengambil langkah lebih awal — misalnya meminta efisiensi tambahan, menunda sebagian program kerja, atau merealokasi anggaran antar departemen.

# 15\. Forecast dan Early Warning

Sistem tidak berhenti pada membandingkan budget dengan realisasi — ia juga membuat forecast ke depan, baik dari sisi anggaran maupun proyeksi pencapaian laba. Kalau tren realisasi dan sisa pekerjaan mengindikasikan budget berpotensi terlampaui atau laba berpotensi meleset, early warning dikirim sebelum kondisi tersebut benar-benar terjadi.

# 16\. Notifikasi WhatsApp Terintegrasi

Aplikasi dilengkapi Notification Engine yang terhubung ke WhatsApp lewat gateway/API resmi atau penyedia layanan yang sudah disetujui perusahaan. Notifikasi ini bukan cuma untuk approval, tapi mencakup seluruh event bisnis yang penting.

| **Event**                         | **Penerima**                  | **Contoh Notifikasi**                                                                  |
| --------------------------------- | ----------------------------- | -------------------------------------------------------------------------------------- |
| RKAP Submitted                    | Atasan Departemen             | RKAP Departemen X sudah masuk dan menunggu direview.                                   |
| Direksi Budget Adjustment         | Key User Departemen terkait   | Anggaran Program Kerja Departemen X telah disesuaikan Direksi, mohon disusun ulang.    |
| Revision Required                 | Key User                      | RKAP dikembalikan untuk direvisi, lengkap dengan catatan dari reviewer.                |
| Approval Pending                  | Approver                      | Ada RKAP/pengajuan yang menunggu persetujuan Anda.                                     |
| Budget Warning                    | Key User, Atasan              | Budget Program X sudah terpakai 85%.                                                   |
| Critical Budget                   | Key User, Atasan, Finance     | Budget Program X sudah mencapai 95%, status berubah jadi Critical.                     |
| Over Budget                       | Key User, Atasan, Finance     | Pengajuan Rp100 juta tidak bisa diproses karena sisa budget tidak cukup.               |
| Unallocated Budget Form Submitted | Approver terkait              | Ada Formulir Pengalihan Anggaran Tanpa Program dari Departemen X yang menunggu review. |
| Budget Transfer Submitted         | Approver                      | Ada permintaan pengalihan Rp80 juta yang menunggu approval Anda.                       |
| Budget Transfer Approved          | Key User                      | Pengalihan anggaran sudah disetujui, budget sudah diperbarui.                          |
| Program Progress Update           | Atasan/PIC                    | Program X belum diperbarui sesuai jadwal monitoring.                                   |
| Program Delayed                   | Atasan/Management             | Program X terlambat dari target yang ditetapkan.                                       |
| Realization Milestone             | Atasan/Management             | Milestone Program X sudah tercapai 100%.                                               |
| Forecast Over Budget              | Finance/Management            | Forecast Program X diperkirakan melebihi baseline.                                     |
| Profit Target at Risk             | Direksi/Management            | Proyeksi laba perusahaan periode berjalan berpotensi tidak mencapai target.            |
| High/Critical Risk                | Risk Owner/Management         | Risiko Program X sekarang berada di level High/Critical.                               |
| Risk Mitigation Due               | Risk Owner                    | Tindakan mitigasi risiko akan segera jatuh tempo.                                      |
| RKAP Final                        | Departemen/Finance/Management | RKAP periode X sudah final dan terkunci.                                               |

# 17\. Mekanisme Notifikasi Berbasis Event

Notifikasi sebaiknya tidak ditulis hard-code di dalam sistem. Lebih baik disediakan Event & Notification Engine, tempat administrator bisa mengatur sendiri event, threshold, penerima, template pesan, prioritas, jam pengiriman, dan kanal yang dipakai.

| **Parameter** | **Contoh**                                                                           |
| ------------- | ------------------------------------------------------------------------------------ |
| Event         | Budget utilization                                                                   |
| Threshold     | 85%, 95%, 100%                                                                       |
| Recipient     | PIC + Atasan + Finance                                                               |
| Channel       | WhatsApp, Email, In-App                                                              |
| Priority      | Normal / High / Critical                                                             |
| Escalation    | Kalau tidak ditindaklanjuti dalam 24 jam, notifikasi diteruskan ke level berikutnya. |
| Schedule      | Bisa real-time atau dirangkum sebagai daily summary.                                 |

# 18\. Eskalasi dan Reminder

Setiap task approval punya SLA. Kalau approver belum bertindak sampai batas waktunya, sistem mengirim reminder; kalau masih belum ada tindakan, sistem akan eskalasi ke pejabat di atasnya sesuai alur yang berlaku.

| **Waktu**        | **Aksi**                                                       |
| ---------------- | -------------------------------------------------------------- |
| Saat task dibuat | WA + notifikasi In-App.                                        |
| H-1 SLA          | Reminder dikirim lewat WA.                                     |
| Lewat SLA        | WA eskalasi ke approver dan atasannya.                         |
| Lewat SLA kritis | Eskalasi ke Finance/Management, tergantung jenis transaksinya. |

# 19\. Dashboard Executive

Dashboard ini merangkum kondisi perusahaan secara keseluruhan: total RKAP perusahaan dan per departemen, perbandingan budget vs realisasi vs commitment vs forecast, tingkat utilisasi anggaran, target laba perusahaan dibandingkan realisasi dan proyeksinya hingga akhir periode, serta status program kerja — mana yang On Track, Delayed, Completed, atau Cancelled.

Selain itu ditampilkan juga program yang masuk kategori Over Budget atau Critical Budget, program dengan forecast yang berpotensi melampaui baseline, jumlah dan nilai Budget Transfer yang terjadi, top 10 risiko High/Critical, status mitigasi termasuk yang sudah overdue, keterkaitan capaian KPI dengan penggunaan anggaran, serta daftar approval yang masih pending atau sudah melewati SLA.

# 20\. Dashboard Departemen

Dashboard departemen menampilkan seluruh program yang menjadi tanggung jawab departemen tersebut: baseline RKAP, realisasi, forecast, status KPI, risiko, mitigasi, pengajuan dana, pengalihan anggaran, anggaran yang belum memiliki program kerja beserta status formulir pengalihannya, sampai task approval yang masih berjalan.

# 21\. Audit Trail dan Governance

Semua perubahan yang sifatnya kritikal harus tercatat. Minimal, audit trail memuat siapa penggunanya, role-nya, waktu kejadian, IP/device (kalau kebijakan mengizinkan), aksi yang dilakukan, objek data yang berubah, nilai sebelum dan sesudah, alasan perubahan, dan approval terkait. Termasuk di dalamnya adalah setiap penyesuaian anggaran yang dilakukan Direksi pada tahap submission awal, sehingga plafon awal maupun plafon setelah penyesuaian tetap bisa ditelusuri kapan pun diperlukan.

Data RKAP yang sudah final tidak bisa dihapus secara permanen lewat fungsi user biasa. Koreksi hanya dilakukan lewat transaksi revisi/versioning, sehingga baseline dan riwayatnya tetap bisa ditelusuri kapan saja.

# 22\. Skenario Lengkap Contoh

Pada submission pertama periode RKAP, total pengajuan seluruh departemen ternyata melebihi kapasitas yang mendukung target laba perusahaan tahun tersebut. Direksi pun memangkas anggaran Departemen IT dari semula Rp900 juta menjadi Rp750 juta. Departemen IT kemudian menyusun ulang Program Kerja Upgrade Infrastruktur Server mengikuti plafon baru tersebut, lengkap dengan KPI availability 99,5% dan risiko keterlambatan pengadaan berkategori High. Setelah melalui review dan approval, RKAP-nya resmi menjadi Final/Locked.

Program berjalan. Realisasi mencapai Rp650 juta, commitment Rp80 juta, sehingga available budget tersisa Rp20 juta. Utilisasi anggaran masuk threshold Critical, dan WhatsApp langsung dikirim ke PIC, Kepala Departemen, dan Finance.

PIC lalu mengajukan tambahan dana Rp100 juta. Sistem mendeteksi shortfall Rp80 juta dan otomatis memblokir pengajuan lewat jalur normal. Key User pun mengajukan pengalihan: Rp50 juta dari Program Pengadaan Laptop dan Rp30 juta dari Program Maintenance.

Sistem mengecek dan memastikan pengurangan dari Program Pengadaan Laptop tidak memicu risiko baru. Tapi pengurangan dari Maintenance ternyata menurunkan dana mitigasi risiko, sehingga transaksi ini ditandai untuk Risk Review. Setelah disetujui Atasan Departemen, Finance, Risk Reviewer, dan Atasan Keuangan/Anggaran, sistem memproses transfer Rp80 juta.

Budget Program Upgrade Server pun naik menjadi Rp830 juta, sementara dua program sumbernya berkurang sesuai nilai transfer. Semua pihak menerima WhatsApp konfirmasi bahwa transfer berhasil. Di dashboard, baseline awal, revisi Direksi, sumber transfer, realisasi, forecast, kontribusi terhadap laba perusahaan, dan residual risk-nya bisa langsung terlihat.

# 23\. Contoh Template Notifikasi WhatsApp

| **Jenis**                 | **Template**                                                                                                                                                                                                                                      |
| ------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Over Budget               | ⚠️ ALERT BUDGET <br>Program: {program} <br>Departemen: {dept} <br>Budget: {budget} <br>Available: {available} <br>Pengajuan: {request} <br>Status: OVER BUDGET <br>Tindakan: Pengajuan normal diblokir. Silakan lakukan Budget Transfer/Revision. |
| Direksi Budget Adjustment | 📉 BUDGET ADJUSTMENT <br>Departemen: {dept} <br>Anggaran Awal: {original_budget} <br>Anggaran Baru: {adjusted_budget} <br>Ditetapkan oleh: Direksi <br>Mohon susun ulang Program Kerja sesuai plafon baru.                                        |
| Approval                  | 🔔 APPROVAL REQUIRED <br>Dokumen: {document} <br>Departemen: {dept} <br>Nilai: {amount} <br>Menunggu persetujuan Anda. <br>SLA: {deadline}                                                                                                        |
| Transfer Approved         | ✅ BUDGET TRANSFER APPROVED <br>Dari: {source} <br>Ke: {destination} <br>Nilai: {amount} <br>Approved by: {approver} <br>Budget telah diperbarui.                                                                                                 |
| Risk Alert                | 🚨 RISK ALERT <br>Program: {program} <br>Risk: {risk} <br>Level: {level} <br>Mitigasi: {mitigation} <br>Due Date: {due_date} <br>Mohon dilakukan tindak lanjut.                                                                                   |
| Profit Target Alert       | 📊 PROFIT ALERT <br>Periode: {period} <br>Target Laba: {target} <br>Proyeksi Laba: {forecast} <br>Status: {status} <br>Mohon perhatian Direksi/Management.                                                                                        |

# 24\. Modul Aplikasi yang Direkomendasikan

- Dashboard Executive
- Dashboard Departemen
- Master Organisasi & Departemen
- Master User, Role & Approval Matrix
- Periode RKAP
- Program Kerja
- KPI & Target
- RKAP & Budget Detail
- Target & Monitoring Laba Perusahaan
- Direksi Budget Adjustment
- Risk Register
- Risk Assessment & Mitigation
- Pengajuan Dana
- Budget Commitment
- Realisasi
- Budget Transfer/Re-Allocation
- Formulir Pengalihan Anggaran Tanpa Program
- Revisi RKAP
- Forecast & Variance Analysis
- Approval Workflow
- Notification Center
- WhatsApp Notification Engine
- Reminder & Escalation
- Audit Trail
- Reporting & Export
- System Configuration

# 25\. Kesimpulan Konsep

Aplikasi ini dirancang bukan sekadar sebagai dashboard pelaporan, melainkan sebagai sistem pengendalian end-to-end untuk Program Kerja, RKAP, dan pencapaian laba perusahaan. Key User Departemen mengelola datanya sejak tahap perencanaan sampai final, Direksi berperan menyelaraskan anggaran departemen dengan target laba sejak submission awal, sementara proses approval, budget control, risk review, realisasi, forecast, audit trail, dan notifikasi semuanya berjalan dalam satu platform yang sama.

Alur utamanya kurang lebih begini: PLAN → PROGRAM KERJA → KPI → BUDGET → PENYESUAIAN DIREKSI → RISK → REVIEW → APPROVAL → RKAP FINAL → EXECUTION → REQUEST → BUDGET CONTROL → REALIZATION → PERFORMANCE MONITORING → PROFIT MONITORING → FORECAST → RISK MONITORING → TRANSFER/REVISION (bila diperlukan) → APPROVAL → UPDATE BASELINE → REPORTING.

Dengan integrasi WhatsApp, sistem ini berubah menjadi proactive control system. Aplikasi tidak menunggu user membuka dashboard untuk tahu ada masalah — ia aktif memberi peringatan begitu muncul overload budget, budget kritis, forecast yang berpotensi melebihi baseline, proyeksi laba yang berisiko meleset dari target, program yang terlambat, risiko tinggi/kritis, mitigasi yang jatuh tempo, approval yang masih pending, atau milestone yang baru saja tercapai.