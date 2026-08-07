<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">

    <style>
        @page {
            margin: 12mm;
        }

        body {
            font-family: "Times New Roman", serif;
            font-size: 11pt;
            color: #000;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        td,
        th {
            border: 1px solid #000;
            padding: 2px 4px;
            vertical-align: top;
        }

        .center {
            text-align: center;
        }

        .middle {
            vertical-align: middle;
        }

        .bold {
            font-weight: bold;
        }

        .header-title {
            font-size: 17px;
            font-weight: bold;
        }

        .no-border {
            border: none !important;
        }

        .line {
            border-bottom: 1px solid #000;
            height: 18px;
        }

        .input-table td {
            border: none;
            padding: 2px;
        }

        .signature-space {
            height: 75px;
        }

        .small {
            font-size: 10pt;
        }
    </style>

</head>

<body>

    <!-- HEADER -->

    <table>

        <tr>

            <td width="20%" class="center middle">

                <img src="{{ public_path('images/sbs-logo.jpg') }}"
                    width="95">

            </td>

            <td width="52%" class="center middle header-title">

                FORM PEMINJAMAN FIXED ASSET IT

            </td>

            <td width="28%" class="small middle">
                No.Dok &nbsp;&nbsp;: PSBSF:MSI:03:001<br>
                No.Rev &nbsp;&nbsp;: 4<br>
                Halaman : 1 dari 1
            </td>

        </tr>

    </table>

    <!-- INFORMASI -->

    <table>
        <tr style="border: 1px solid #000;">
            <td style="padding: 10px;"></td>
        </tr>
        <tr>

            <td>

                <table class="input-table">

                    <tr>

                        <td width="100">Tanggal Pengajuan</td>
                        <td width="10">:</td>
                        <td width="80" class="line">{{ $borrowing->date_start->format('d F Y') }}</td>

                        <td width="60">s/d Tanggal</td>
                        <td width="10">:</td>
                        <td class="line">{{ $borrowing->date_end->format('d F Y') }}</td>

                    </tr>

                    <tr>

                        <td>Tujuan lokasi</td>
                        <td>:</td>
                        <td colspan="4" class="line">{{ $borrowing->tujuan_lokasi }}</td>

                    </tr>

                    <tr>

                        <td>Keperluan</td>
                        <td>:</td>
                        <td colspan="4" class="line">{{ $borrowing->keperluan }} </td>

                    </tr>
                    <tr>

                        <td>Type Perangkat</td>
                        <td>:</td>
                        <td colspan="4" class="line">{{ $borrowing->tipe_perangkat }}</td>

                    </tr>

                </table>

            </td>

        </tr>

    </table>

    <!-- DATA -->

    <table>

        <tr>

            <th width="50%" align="left">Data Peminjam</th>
            <th width="50%" align="left">Data Yang Menyerahkan</th>

        </tr>

        <tr>

            <td style="padding:0;">

                <table class="input-table">

                    <tr style="border-top: 1px solid #000;">
                        <td width="70">Nama</td>
                        <td width="10">:</td>
                        <td class="line">{{ $borrowing->pemohon_name }}</td>
                    </tr>

                    <tr style="border-top: 1px solid #000;">
                        <td>Jabatan</td>
                        <td>:</td>
                        <td class="line">{{ $borrowing->pemohon_jabatan }}</td>
                    </tr>

                    <tr style="border-top: 1px solid #000;">
                        <td>Area</td>
                        <td>:</td>
                        <td class="line">{{ $borrowing->pemohon_area ?? '-' }}</td>
                    </tr>

                    <tr style="border-top: 1px solid #000;">
                        <td>Departmen</td>
                        <td>:</td>
                        <td class="line">{{ $borrowing->pemohon_departemen ?? '-' }}</td>
                    </tr>

                </table>

            </td>

            <td style="padding:0;">

                <table class="input-table">

                    <tr style="border-top: 1px solid #000;">
                        <td width="70">Nama</td>
                        <td width="10">:</td>
                        <td class="line">{{ $borrowing->penyerahkan_name ?? '-' }}</td>
                    </tr>

                    <tr style="border-top: 1px solid #000;">
                        <td>Jabatan</td>
                        <td>:</td>
                        <td class="line">{{ $borrowing->penyerahkan_jabatan ?? '-' }}</td>
                    </tr>

                    <tr style="border-top: 1px solid #000;">
                        <td>Area</td>
                        <td>:</td>
                        <td class="line">{{ $borrowing->penyerahkan_area ?? '-' }}</td>
                    </tr>

                    <tr style="border-top: 1px solid #000;">
                        <td>Departmen</td>
                        <td>:</td>
                        <td class="line">{{ $borrowing->penyerahkan_departemen ?? '-' }}</td>
                    </tr>

                </table>

            </td>

        </tr>

    </table>

    <!-- KELENGKAPAN -->

    <table>
        <tr>

            <th width="8%">No</th>
            <th width="32%">Uraian</th>
            <th width="17%">Ada</th>
            <th width="17%">Tidak Ada</th>
            <th width="26%">Keterangan</th>

        </tr>

        <tr>

            <td colspan="5" class="bold">

                A. Kelengkapan Perangkat

            </td>

        </tr>

        @if(isset($borrowing->deviceCompletions) && $borrowing->deviceCompletions->count() > 0)
        @foreach($borrowing->deviceCompletions as $index => $device)
        <tr>
            <td class="center">{{ $index + 1 }}</td>
            <td>{{ $device->uraian }}</td>
            <td class="center">{{ $device->ada ? 'V' : '' }}</td>
            <td class="center">{{ $device->tidak_ada ? 'V' : '' }}</td>
            <td>{{ $device->keterangan ?? '' }}</td>
        </tr>
        @endforeach
        @else
        @for($i=1;$i<=5;$i++)
            <tr>
            <td class="center">{{ $i }}</td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            </tr>
            @endfor
            @endif

    </table>

    <!-- TANDA TANGAN -->

    <table>

        <tr>

            <td width="50%" class="center">

                Diserahkan Oleh

            </td>

            <td width="50%" class="center">

                Diterima Oleh

            </td>

        </tr>

        <tr>

            <td class="signature-space">
                @if($borrowing->getApprovalStatusLabel() == "Disetujui")
                <div style="width: 100%; display: flex; align-items: center; justify-content: center; text-align: center; vertical-align: middle; margin-top: 15px;">
                    <img src="{{ public_path('images/approved.svg') }}"
                        width="150" style="transform: rotate(3deg);">
                </div>
                @endif
            </td>

            <td>
                @if($borrowing->getApprovalStatusLabel() == "Disetujui")
                <div style="width: 100%; display: flex; align-items: center; justify-content: center; text-align: center; vertical-align: middle; margin-top: 15px;">
                    <img src="{{ public_path('images/approved.svg') }}"
                        width="150" style="transform: rotate(3deg);">
                </div>
                @endif
            </td>

        </tr>

        <tr>

            <td class="center">

                {{ $borrowing->approver->name ?? '-' }}

            </td>

            <td class="center">

                {{ $borrowing->pemohon->name ?? '-' }}

            </td>

        </tr>

    </table>

</body>

</html>
