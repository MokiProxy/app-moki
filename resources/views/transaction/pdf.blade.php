<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>BAST MSI - {{ $transaction->order_number }}</title>
    <style>
        @page { margin: 0.5cm 1cm; }
        body { font-family: 'Arial', sans-serif; font-size: 9pt; color: #000; line-height: 1.2; }
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; table-layout: fixed; }
        .header-table td { border: 1px solid #000; padding: 5px; vertical-align: middle; }
        .logo-section { width: 20%; text-align: center; }
        .title-section { width: 50%; text-align: center; font-weight: bold; font-size: 11pt; }
        .doc-info { width: 30%; font-size: 8pt; }
        .content-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .content-table td { border: 1px solid #000; padding: 4px; vertical-align: middle; }
        .bg-gray { background-color: #f2f2f2; font-weight: bold; }
        .text-center { text-align: center; }
        .indent-col { width: 30px; text-align: center; }
        .signature-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .signature-table td { border: 1px solid #000; padding: 5px; text-align: center; }
        .qr-placeholder { width: 60px; height: 60px; margin: 5px auto; border: 1px solid #ddd; background-color: #fcfcfc; color: #aaa; font-size: 6pt; line-height: 60px; }
        ol { margin: 5px 0; padding-left: 20px; font-size: 8pt; }
    </style>
</head>
<body>

    <table class="header-table">
        <tr>
            <td class="logo-section">
                <img src="{{ public_path('img/logo.png') }}" width="70" alt="LOGO">
            </td>
            <td class="title-section">
                BERITA ACARA {{ $transaction->type == 'IN' ? 'PENGEMBALIAN' : 'PENYERAHAN' }} ASSET<br>
                DEPARTEMEN MANAJEMEN SISTEM INFORMASI
            </td>
            <td class="doc-info">
                No. Dok. : PSBSF:MSI:03:010<br>
                No. Revisi : 0<br>
                Halaman : 1 dari 1
            </td>
        </tr>
    </table>

    <p style="margin: 10px 0;">
        Pada hari ini <b>{{ $transaction->created_at->isoFormat('dddd') }}</b>, 
        tanggal <b>{{ $transaction->created_at->format('d') }}</b> 
        bulan <b>{{ $transaction->created_at->isoFormat('MMMM') }}</b> 
        tahun <b>{{ $transaction->created_at->format('Y') }}</b>, 
        bertempat di <b>JAKARTA</b> telah dilaksanakan serah terima aset perusahaan dengan rincian sebagai berikut:
    </p>

    <table class="content-table">
        <tr>
            <td class="indent-col bg-gray">A</td>
            <td width="30%" class="bg-gray">Nama Pegawai</td>
            <td colspan="3"><b>{{ $transaction->employee->name ?? '-' }}</b></td>
        </tr>
        <tr>
            <td class="bg-gray"></td>
            <td class="bg-gray">NRP / NIK</td>
            <td colspan="3">{{ $transaction->employee->employee_id ?? '-' }}</td>
        </tr>
        <tr>
            <td class="bg-gray"></td>
            <td class="bg-gray">Jabatan</td>
            <td colspan="3">{{ $transaction->employee->jabatan ?? '-' }}</td>
        </tr>
        <tr>
            <td class="bg-gray"></td>
            <td class="bg-gray">Departemen</td>
            <td colspan="3">{{ $transaction->division->name ?? '-' }}</td>
        </tr>

        <tr>
            <td class="indent-col bg-gray">B</td>
            <td class="bg-gray">Kategori Asset</td>
            <td width="25%" class="bg-gray text-center">Merk & SN</td>
            <td width="25%" class="bg-gray text-center">Nomor Asset (UID)</td>
            <td width="15%" class="bg-gray text-center">Kondisi</td>
        </tr>

        @forelse($transaction->details as $item)
            <tr>
                <td class="text-center"></td>
                <td>{{ $item->asset->category->name ?? '-' }}</td>
                <td>{{ $item->asset->brand ?? '-' }} / {{ $item->asset->serial_number ?? '-' }}</td>
                <td class="text-center">{{ $item->new_uid ?? $item->asset->uid }}</td>
                <td class="text-center">
                    @php
                        // Logika konversi angka ke teks (1=Baru, 2=Seken, 3=Rusak)
                        $status_val = $item->asset->condition; // Sesuaikan jika nama field di DB adalah 'status' atau 'condition'
                        $status_text = match((int)$status_val) {
                            1 => 'BARU',
                            2 => 'SEKEN',
                            3 => 'RUSAK',
                            default => '-'
                        };
                    @endphp
                    {{ $status_text }}
                </td>
            </tr>
        @empty
            <tr>
                <td></td>
                <td colspan="4" class="text-center">Tidak ada data aset.</td>
            </tr>
        @endforelse

        <tr>
            <td colspan="5" style="padding: 10px;">
                <b>Catatan/Alasan:</b> {{ $transaction->note ?? '-' }}
            </td>
        </tr>
    </table>

    <div style="text-align: center; font-weight: bold; text-decoration: underline; margin-top: 30px; margin-bottom: 20px;">
        PENGESAHAN SERAH TERIMA & PERSETUJUAN
    </div>

    <table class="signature-table">
        <tr class="bg-gray">
            <td width="20%">Identitas</td>
            <td>Yang Menyerahkan</td>
            <td>Yang Menerima</td>
            <td>Diketahui Oleh</td>
        </tr>
        <tr>
            <td class="bg-gray">Nama</td>
            <td>{{ $transaction->type == 'OUT' ? 'Admin MSI' : ($transaction->employee->name ?? '-') }}</td>
            <td>{{ $transaction->type == 'OUT' ? ($transaction->employee->name ?? '-') : 'Admin MSI' }}</td>
            <td>(Manager MSI)</td>
        </tr>
        <tr>
            <td class="bg-gray" style="height: 80px;">Tanda Tangan Digital</td>
            <td>
                <div class="qr-placeholder">SIGNED</div>
                <small style="font-size: 6pt;">System Generated</small>
            </td>
            <td>
                <div class="qr-placeholder">SIGNED</div>
                <small style="font-size: 6pt;">Approved by User</small>
            </td>
            <td>
                <div class="qr-placeholder">WAITING</div>
                <small style="font-size: 6pt;">Approval Required</small>
            </td>
        </tr>
    </table>

    <div style="margin-top: 20px; border: 1px solid #000; padding: 10px;">
        <b>Catatan / Syarat & Ketentuan:</b>
        <ol>
            <li>Sejak Berita Acara ini ditandatangani maka tanggung jawab pengurusan Asset tersebut beralih kepihak Penerima.</li>
            <li>Unit Aset yang telah di serah terima tidak dapat dipindah tangankan secara sepihak.</li>
            <li>Apabila terjadi kehilangan atau kerusakan akibat kelalaian, Penerima wajib melakukan penggantian.</li>
            <li>Alat Penunjangan Kerja ini hanya dipergunakan untuk keperluan perusahaan.</li>
            <li>Penerima wajib menjaga dan merawat fasilitas penunjang kerja yang sudah diberikan.</li>
        </ol>
    </div>

</body>
</html>