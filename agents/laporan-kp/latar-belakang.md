# BAB I PENDAHULUAN

## 1.1 Latar Belakang

Pengelolaan dokumen merupakan salah satu aktivitas utama organisasi karena hampir setiap
proses bisnis menghasilkan dokumen yang harus disimpan, ditemukan kembali, dan dibuktikan
keberadaannya (Jordan et al., 2022). Seiring bertambahnya volume dokumen yang harus disimpan
secara terus-menerus, organisasi dituntut mengelola dokumen dan arsipnya dengan memanfaatkan
teknologi informasi (Sihaloho & Sobandi, 2018). Pengelolaan dokumen secara konvensional tidak
lagi memadai karena tidak mampu mengikuti kebutuhan organisasi yang menuntut kecepatan dan
kelincahan (Jordan et al., 2022). Pengelolaan berbasis kertas menimbulkan keterlambatan
penginputan data, tingginya risiko kesalahan pencatatan, kesulitan pencarian arsip, serta
potensi kehilangan dan kerusakan dokumen (Firmansyah & Supratman, 2026). Kondisi tersebut
menunjukkan bahwa persoalan pengelolaan dokumen bukan semata persoalan penyimpanan berkas,
melainkan persoalan efisiensi, ketertelusuran, dan keandalan informasi organisasi.

Upaya mengatasi persoalan tersebut diarahkan pada digitalisasi dokumen, yaitu pengalihan
dokumen dari media kertas ke media elektronik melalui pemindaian, konversi, dan impor data
(Rahmah, 2025). Digitalisasi memberikan kedudukan yang lebih kuat kepada arsip elektronik
karena arsip tersebut diakui sebagai alat bukti yang sah menurut Undang-Undang Nomor 11 Tahun
2008 tentang Informasi dan Transaksi Elektronik Pasal 5 ayat (1), sebagaimana ditegaskan oleh
Rahmah (2025). Pengakuan tersebut menjadikan pengelolaan arsip elektronik tidak cukup berhenti
pada penyimpanan berkas, tetapi harus mencakup proses pembuatan dan penyimpanan, distribusi
dan pemanfaatan, pemeliharaan, serta disposisi yang tertib (Rahmah, 2025). Namun, pemindaian
dokumen saja belum menghasilkan arsip yang dapat dikelola secara efektif. Hasil pemindaian
umumnya berupa gambar atau PDF yang tidak dapat dicari berdasarkan isinya, sehingga
penelusuran tetap bergantung pada nama berkas dan ingatan pengguna (Setiawan & Cahyono, 2025).

Keterbatasan tersebut mendorong pemanfaatan pengenalan karakter optik (*optical character
recognition*, OCR). OCR mengekstraksi teks dari gambar dan berkas PDF sehingga isi dokumen
berubah menjadi data digital yang dapat diolah, dicari, dan diintegrasikan dengan basis data
(Setiawan & Cahyono, 2025). Kinerja OCR tidak sepenuhnya seragam karena dipengaruhi oleh
kualitas dokumen. Setiawan dan Cahyono (2025) melaporkan bahwa tingkat kesalahan karakter
(*character error rate*, CER) pada dokumen yang diketik dengan komputer mencapai 0,16%,
sedangkan pada dokumen hasil mesin ketik mencapai 6,41%. Perbedaan tersebut menunjukkan bahwa
hasil OCR masih memerlukan pemeriksaan dan validasi, terutama pada dokumen lama atau dokumen
yang kualitas cetakannya rendah. Dengan demikian, OCR bukan sekadar alat pemindai tambahan,
melainkan titik awal dari rangkaian pengolahan data dokumen yang harus dirancang secara
cermat.

Perkembangan kecerdasan buatan generatif memperluas kemampuan OCR. Firmansyah dan Supratman
(2026) mengintegrasikan OCR dengan *generative artificial intelligence* untuk mengekstraksi
dan memahami informasi pada arsip surat secara otomatis. Hasil kajian tersebut menunjukkan
bahwa kombinasi kedua teknologi mempercepat proses digitalisasi, meningkatkan akurasi
pengelolaan data, mempermudah pencarian dokumen, serta mendukung pemantauan dan pelaporan
arsip (Firmansyah & Supratman, 2026). Kajian yang sama juga menerapkan pengendalian akses
berbasis peran (*role-based access control*, RBAC) untuk membedakan hak akses administrator,
pegawai, dan pimpinan (Firmansyah & Supratman, 2026). Temuan tersebut memperlihatkan bahwa
otomasi pembacaan dokumen berjalan seiring dengan kebutuhan pengaturan akses dan pencatatan
aktivitas, bukan sebagai teknologi yang berdiri sendiri.

Sisi lain dari digitalisasi dokumen adalah pengelolaan dokumen secara terpusat melalui
sistem manajemen dokumen (*document management system*, DMS). DMS mengelola dokumen beserta
atributnya, menyediakan sarana pencarian, dan mengatur alur kerja dokumen di dalam organisasi
(Jordan et al., 2022; Sternad Zabukovšek et al., 2023). Pemanfaatan DMS memungkinkan organisasi
mengurangi penggunaan kertas, mempercepat proses bisnis, menurunkan biaya operasional, dan
mendukung keberlanjutan (Sternad Zabukovšek et al., 2023). DMS juga berperan sebagai sarana
pengelolaan pengetahuan karena dokumen yang tersimpan secara terstruktur dapat ditemukan
kembali dan dimanfaatkan sebagai pengetahuan organisasi (Anas & Salim, 2022). Oleh karena itu,
DMS tidak hanya berfungsi sebagai tempat penyimpanan berkas, tetapi juga sebagai infrastruktur
informasi organisasi.

Meskipun manfaat DMS telah banyak diuraikan, penerapannya tidak otomatis berhasil. Keberhasilan
implementasi sistem manajemen dokumen elektronik (*electronic document management system*,
EDMS) dipengaruhi oleh faktor manusia, dukungan manajemen, dan penerimaan pengguna, bukan hanya
oleh kesiapan teknologi (Sari et al., 2025).
Kajian pada Sistem Naskah Dinas Elektronik (SINDE) Kementerian Pendidikan dan Kebudayaan
menunjukkan bahwa penerapan sistem manajemen dokumen elektronik mempercepat arus informasi
dan mendukung layanan, tetapi belum seluruh komponen dasar sistem manajemen dokumen
elektronik terpenuhi dalam praktik (Maharani & Lawanda, 2023). Sihaloho dan Sobandi (2018)
juga menegaskan bahwa setiap organisasi memiliki peluang dan tantangan yang berbeda dalam
menerapkan sistem manajemen dokumen elektronik sehingga diperlukan dukungan internal dan
eksternal yang memadai. Temuan tersebut menunjukkan bahwa kajian mengenai pengelolaan
dokumen tidak dapat dipisahkan dari konteks organisasi, prosedur kerja, dan kesiapan
penggunanya.

Aspek lain yang tidak terpisahkan dari pengelolaan dokumen adalah pengendalian akses dan
jejak aktivitas. Pembatasan akses berdasarkan peran menyederhanakan administrasi keamanan
pada sistem berskala besar karena hak akses diberikan kepada peran, bukan kepada setiap
pengguna secara individual (Sandhu et al., 1996). Penerapan RBAC yang disertai jejak audit
(*audit trail*) memungkinkan sistem mencatat riwayat akses dan perubahan data sehingga
akuntabilitas proses dapat dibuktikan dan tindakan penyangkalan (*non-repudiation*) dapat
dicegah (Febriantika et al., 2025). Dengan demikian, pengelolaan dokumen yang baik menuntut
keseimbangan antara kemudahan akses, pembatasan kewenangan, dan keterlacakan tindakan.

Berdasarkan uraian tersebut, penelitian terdahulu telah membahas pengelolaan dokumen
elektronik, OCR, kecerdasan buatan generatif, DMS, RBAC, dan jejak audit secara terpisah.
Ringkasan fokus kajian tersebut disajikan pada Tabel 1.1, sedangkan peran setiap teknologi
disajikan pada Tabel 1.2.

*Tabel 1.1 Ringkasan Permasalahan Pengelolaan Dokumen Secara Manual*

| No | Permasalahan | Dampak | Sumber |
|---|---|---|---|
| 1 | Volume dokumen terus bertambah | Pengelolaan konvensional tidak lagi memadai | Sihaloho & Sobandi (2018) |
| 2 | Penginputan data dilakukan secara manual | Proses pengelolaan menjadi lambat | Firmansyah & Supratman (2026) |
| 3 | Pencatatan dilakukan berulang oleh operator | Risiko kesalahan pencatatan meningkat | Firmansyah & Supratman (2026) |
| 4 | Dokumen tersimpan tanpa indeks yang seragam | Pencarian arsip memerlukan waktu lama | Firmansyah & Supratman (2026) |
| 5 | Media penyimpanan berupa kertas | Dokumen berisiko hilang atau rusak | Firmansyah & Supratman (2026) |
| 6 | Tidak ada jejak akses dan perubahan | Akuntabilitas proses sulit dibuktikan | Febriantika et al. (2025) |

*Tabel 1.2 Peran Teknologi dalam Pengelolaan Dokumen Elektronik*

| Komponen | Fungsi | Sumber |
|---|---|---|
| OCR | Mengubah teks pada gambar dan PDF menjadi teks digital yang dapat diolah | Setiawan & Cahyono (2025) |
| Kecerdasan buatan generatif | Memahami konteks dokumen dan mengekstraksi informasi secara otomatis | Firmansyah & Supratman (2026) |
| DMS | Mengelola dokumen, atribut dokumen, pencarian, dan alur kerja | Jordan et al. (2022); Anas & Salim (2022) |
| RBAC | Membatasi akses berdasarkan peran pengguna | Sandhu et al. (1996) |
| Jejak audit | Mencatat riwayat akses dan perubahan untuk akuntabilitas | Febriantika et al. (2025) |

Tabel 1.1 dan Tabel 1.2 memperlihatkan bahwa setiap komponen menjawab bagian permasalahan
tertentu. OCR dan kecerdasan buatan generatif menjawab keterbatasan data yang masih berbentuk
gambar, sedangkan DMS menjawab kebutuhan penyimpanan dan penelusuran yang terstruktur. Namun,
kajian-kajian yang dirangkum pada kedua tabel tersebut belum menyatukan komponen pemrosesan
dokumen ke dalam satu alur pengelolaan dokumen. Sebagian kajian berhenti pada tataran
konseptual dan tantangan implementasi (Sihaloho & Sobandi, 2018; Anas & Salim, 2022), sebagian
berfokus pada penerapan sistem manajemen dokumen di organisasi tertentu (Maharani & Lawanda,
2023; Sari et al., 2025), dan sebagian lain berfokus pada OCR atau kecerdasan buatan generatif
untuk pengarsipan (Setiawan & Cahyono, 2025; Firmansyah & Supratman, 2026). Ringkasan
penelitian terdahulu disajikan pada Tabel 1.3.

*Tabel 1.3 Penelitian Terdahulu yang Relevan*

| Peneliti | Fokus Kajian | Cakupan |
|---|---|---|
| Sihaloho & Sobandi (2018) | Peluang dan tantangan implementasi sistem manajemen dokumen elektronik | Konseptual dan eksploratif |
| Anas & Salim (2022) | Pemanfaatan EDMS untuk manajemen pengetahuan | Tinjauan literatur sistematis |
| Maharani & Lawanda (2023) | Implementasi EDMS di instansi pemerintah | Studi kasus organisasi |
| Sari et al. (2025) | Implementasi EDMS di perusahaan | Studi kasus organisasi |
| Setiawan & Cahyono (2025) | Penerapan OCR untuk digitalisasi dan pengarsipan | Pengembangan sistem |
| Firmansyah & Supratman (2026) | OCR dan kecerdasan buatan generatif untuk arsip surat | Pengembangan aplikasi |
| Febriantika et al. (2025) | RBAC dan jejak audit pada backend sistem informasi | Pengembangan sistem |

Tabel 1.3 menunjukkan bahwa penelitian terdahulu telah membahas komponen pengelolaan dokumen
secara terpisah, tetapi berdasarkan penelusuran literatur yang dilakukan, belum ditemukan
kajian yang secara khusus membahas penyatuan pemindaian, pembacaan isi dokumen, pembentukan
identitas berkas, dan penggabungan dokumen yang saling berkaitan dalam satu alur kerja. Padahal,
pada praktiknya, dokumen transaksi tidak berdiri sendiri. Satu dokumen dapat merujuk beberapa
dokumen lain dan memiliki pola penomoran tertentu. Kesenjangan tersebut menjadi dasar perlunya
perancangan dan implementasi sistem yang mengelola dokumen secara menyeluruh, mulai dari berkas
hasil pemindaian hingga dokumen tersimpan dan dapat ditemukan kembali.

Berdasarkan uraian tersebut, kegiatan kerja praktik ini diarahkan pada perancangan dan
implementasi sistem pemrosesan dokumen terotomasi dengan OCR berbasis kecerdasan buatan
generatif. Sistem tersebut diwujudkan dalam modul Dokter (Dokumen Kontroler) pada Portal
Aplikasi Internal PT Satria Bahana Sarana dan digunakan oleh departemen akuntansi dan
perpajakan untuk mengelola dokumen transaksi. Perancangan dan implementasi diarahkan pada
arsitektur sistem, alur pemrosesan berkas, mekanisme pembacaan dokumen dan pembentukan nama
berkas, serta penggabungan dokumen. Uraian tersebut menjadi dasar perumusan masalah pada
subbab berikutnya.

## 1.2 Rumusan Masalah

Berdasarkan latar belakang yang telah diuraikan, rumusan masalah pada kerja praktik ini
adalah sebagai berikut.

1. Bagaimana kondisi pengelolaan dokumen transaksi pada departemen akuntansi dan perpajakan
   PT Satria Bahana Sarana sebelum sistem pemrosesan dokumen terotomasi dikembangkan?
2. Bagaimana sistem pemrosesan dokumen terotomasi dengan OCR berbasis kecerdasan buatan
   generatif dirancang, meliputi arsitektur, alur pemrosesan, model data, dan antarmuka?
3. Bagaimana sistem tersebut diimplementasikan untuk menangani penangkapan berkas dari
   pemindai, ekstraksi data, penamaan berkas, konversi gambar ke PDF, dan penggabungan
   dokumen?

## 1.3 Batasan Masalah

Agar pembahasan terarah, kerja praktik ini dibatasi pada hal-hal berikut.

1. Objek pembahasan adalah modul Dokter (Dokumen Kontroler) pada Portal Aplikasi Internal
   PT Satria Bahana Sarana, mulai dari penangkapan berkas hasil pemindaian hingga dokumen
   tersimpan pada arsip digital.
2. Pembahasan mencakup pemantauan folder FTP pemindai, OCR berbasis Gemini, klasifikasi jenis
   dokumen, penamaan dan penempatan berkas pada FTP final, konversi gambar ke PDF, penggabungan
   PDF (duplikat dan alur birokrasi/*fan-out*), konfigurasi jenis dokumen, vendor, dan alur
   birokrasi, serta validasi berkas.
3. Pembahasan tidak mencakup pemindaian fisik dan perangkat keras pemindai, proses pencatatan
   akuntansi, penghitungan dan pelaporan perpajakan, proses pembayaran, aplikasi mobile
   pemindai, penghapusan atau perubahan berkas asli, *backfill* data lama ke alur baru, serta
   manajemen pengguna pemindai secara lengkap.
4. Pembahasan tidak mengkaji aspek hukum penyimpanan dokumen dan kepatuhan regulasi secara
   mendalam.
5. Analisis dilakukan secara statis terhadap kode sumber, basis data, konfigurasi, riwayat
   pengembangan, dan dokumen perencanaan internal pada repositori aplikasi. Pengujian beban,
   pengujian penetrasi, dan pengukuran kinerja kuantitatif tidak dilakukan.
6. Kondisi pengelolaan dokumen sebelum sistem dikembangkan direkonstruksi dari aturan bisnis,
   dokumentasi internal, dan riwayat pengembangan aplikasi. Rekonstruksi tersebut perlu
   diverifikasi melalui wawancara dengan pengguna.

## 1.4 Tujuan

### 1.4.1 Tujuan Umum

Tujuan umum kerja praktik ini adalah merancang dan mengimplementasikan sistem pemrosesan
dokumen terotomasi dengan OCR berbasis kecerdasan buatan generatif untuk mendukung pengelolaan
dokumen transaksi pada departemen akuntansi dan perpajakan PT Satria Bahana Sarana.

### 1.4.2 Tujuan Khusus

Tujuan khusus kerja praktik ini adalah sebagai berikut.

1. Mengidentifikasi kondisi pengelolaan dokumen transaksi pada departemen akuntansi dan
   perpajakan PT Satria Bahana Sarana sebelum sistem dikembangkan beserta permasalahan yang
   menyertainya.
2. Merancang sistem pemrosesan dokumen terotomasi, meliputi arsitektur, alur pemrosesan,
   model data, antarmuka, serta aturan penamaan dan penggabungan dokumen.
3. Mengimplementasikan sistem tersebut, meliputi penangkapan berkas dari pemindai, OCR,
   klasifikasi jenis dokumen, penamaan berkas, konversi gambar ke PDF, penggabungan dokumen,
   dan unggah ke penyimpanan akhir.

## 1.5 Manfaat Kerja Praktik

Hasil kerja praktik ini diharapkan memberikan manfaat bagi pihak-pihak berikut.

### 1.5.1 Bagi Perusahaan

Bagi PT Satria Bahana Sarana, kerja praktik ini menghasilkan sistem pemrosesan dokumen
terotomasi yang mengurangi pekerjaan manual pada penamaan berkas, penempatan folder, dan
penggabungan dokumen. Sistem tersebut meningkatkan konsistensi penamaan dan penempatan berkas
serta mengurangi kesalahan akibat pekerjaan manual.

### 1.5.2 Bagi Perguruan Tinggi

Bagi perguruan tinggi, kerja praktik ini menjadi studi kasus penerapan OCR berbasis kecerdasan
buatan generatif dan sistem manajemen dokumen pada proses bisnis nyata. Hasilnya dapat
memperkaya bahan ajar, referensi penelitian, dan penilaian kesesuaian kurikulum dengan
kebutuhan industri. Kerja praktik ini juga memperkuat kerja sama antara perguruan tinggi dan
perusahaan tempat kerja praktik.

### 1.5.3 Bagi Peserta Kerja Praktik

Bagi peserta kerja praktik, kegiatan ini menjadi sarana menerapkan pengetahuan mengenai
analisis sistem, basis data, pemrograman web, dan rekayasa perangkat lunak. Peserta memperoleh
pengalaman teknis dalam menggunakan Laravel, MySQL, FTP, API Gemini, dan pengolahan PDF,
sekaligus melatih kemampuan menganalisis kode, menyusun dokumentasi, dan berkomunikasi teknis.
Kegiatan ini juga memberikan pemahaman mengenai proses bisnis dan budaya kerja profesional di
perusahaan.

## 1.6 Sistematika Penulisan

Penulisan ini disusun dalam lima bab dengan sistematika sebagai berikut.

1. **BAB I PENDAHULUAN** memuat latar belakang, rumusan masalah, batasan masalah, tujuan,
   manfaat, dan sistematika penulisan.
2. **BAB II LANDASAN TEORI** memuat teori yang mendukung pembahasan, antara lain konsep
   sistem manajemen dokumen, pengenalan karakter optik, kecerdasan buatan generatif, *File
   Transfer Protocol*, basis data relasional, dan kerangka kerja Laravel.
3. **BAB III GAMBARAN UMUM** memuat gambaran umum PT Satria Bahana Sarana, portal aplikasi
   internal, serta gambaran umum modul Dokumen Kontroler meliputi aktor, fitur, arsitektur,
   dan alur bisnis.
4. **BAB IV PEMBAHASAN** memuat perancangan dan implementasi sistem, meliputi arsitektur, alur
   pemrosesan berkas, mekanisme OCR, pembentukan nama berkas, dan penggabungan dokumen.
5. **BAB V PENUTUP** memuat kesimpulan dan saran berdasarkan hasil pembahasan.

## Daftar Pustaka

Anas, A., & Salim, T. A. (2022). Tinjauan literatur sistematis pemanfaatan electronic
document management system bagi organisasi dalam menunjang manajemen pengetahuan. *Berkala
Ilmu Perpustakaan dan Informasi, 18*(2), 259–275. https://doi.org/10.22146/bip.v18i2.5649

Febriantika, K. C., Priamita, Z. D., Putra, A. B., & Afandi, M. I. (2025). Integrasi keamanan
API berbasis RBAC dan audit trail pada backend sistem informasi eksekutif rekrutmen untuk
optimalisasi pengambilan keputusan strategis (Studi kasus: PT Solomon Indo Global). *Kohesi:
Jurnal Sains dan Teknologi, 10*(7), 1941–1950.

Firmansyah, W., & Supratman, E. (2026). Digitalisasi arsip surat berbasis mobile menggunakan
OCR dan generative AI pada Sekretariat DPRD Provinsi Sumatera Selatan. *Jurnal Adijaya
Multidisiplin, 4*(4), 273–288.

Jordan, S., Sternad Zabukovšek, S., & Šišovska Klančnik, I. (2022). Document management
system – A way to digital transformation. *Naše gospodarstvo/Our Economy, 68*(2), 43–54.
https://doi.org/10.2478/ngoe-2022-0010

Maharani, V. D. D., & Lawanda, I. I. (2023). Implementasi electronic document management
system untuk mendukung e-government: Studi kasus Sistem Naskah Dinas Elektronik (SINDE)
Kementerian Pendidikan dan Kebudayaan RI. *Multikultura, 2*(1), Artikel 6.
https://doi.org/10.7454/multikultura.v2i1.1132

Rahmah, A. (2025). Pengelolaan arsip berbasis elektronik digital. *Jurnal Pendidikan, Hukum,
Komunikasi (J-DIKUMSI), 1*(2), 89–103. https://doi.org/10.69623/j-dikumsi.v1i2.133

Sandhu, R. S., Coyne, E. J., Feinstein, H. L., & Youman, C. E. (1996). Role-based access
control models. *Computer, 29*(2), 38–47. https://doi.org/10.1109/2.485845

Sari, D. D. P., Wolor, C. W., & Marsofiyati. (2025). Analysis of electronic document
management system (EDMS) implementation at Company X. *International Student Conference on
Business, Education, Economics, Accounting, and Management (ISC-BEAM), 3*(1), 1133–1147.
https://doi.org/10.21009/ISC-BEAM.013.83

Setiawan, J. C., & Cahyono, A. D. (2025). Penerapan OCR untuk digitalisasi dan pengarsipan
data digital. *Jurnal Pendidikan Teknologi Informasi (JUKANTI), 8*(2), 225–238.
https://doi.org/10.37792/jukanti.v8i2.1857

Sihaloho, L., & Sobandi, A. (2018). Peluang dan tantangan dalam mengimplementasikan sistem
manajemen dokumen elektronik. *Khazanah: Jurnal Pengembangan Kearsipan, 11*(1).
https://doi.org/10.22146/khazanah.34470

Sternad Zabukovšek, S., Jordan, S., & Bobek, S. (2023). Managing document management systems'
life cycle in relation to an organization's maturity for digital transformation.
*Sustainability, 15*(21), 15212. https://doi.org/10.3390/su152115212
