<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">

    <style>
        @page {
            margin: 10mm;
        }

        body {
            font-family: "Times New Roman", serif;
            font-size: 13px;
            color: #000;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        .border {
            border: 1px solid #000;
        }

        .center {
            text-align: center;
        }

        .middle {
            vertical-align: middle;
        }

        .top {
            vertical-align: top;
        }

        .title {
            font-size: 16px;
            font-weight: bold;
        }

        .header td {
            border: 1px solid #000;
        }

        .label {
            width: 140px;
            vertical-align: top;
        }

        .line {
            border-bottom: 1px solid #000;
            height: 18px;
        }

        .checkbox {
            width: 14px;
            height: 14px;
            border: 1px solid #000;
            display: inline-block;
            margin-right: 5px;
        }

        .checkbox-check::before {
            width: 14px;
            height: 14px;
            border: 1px solid #000;
            display: inline-block;
            margin-right: 5px;
            content: "V"
        }

        .signature td {
            border: 1px solid #000;
        }

        .sign-space {
            height: 90px;
        }

        .small {
            font-size: 13px;
        }

        .note {
            line-height: 16px;
        }
    </style>

</head>

<body>
    <table class="header">
        <tr>
            <td width="20%" class="center middle">
                <img src="{{ public_path('images/sbs-logo.jpg') }}"
                    width="95">
            </td>
            <td width="55%" class="center middle title">
                PENGAJUAN INSTALL SOFTWARE & APLIKASI
            </td>
            <td width="25%" class="small">
                No.Dok &nbsp;&nbsp;&nbsp;: PSBSF:MSI:01:001<br>
                No.Rev &nbsp;&nbsp;&nbsp;: 4<br>
                Halaman : 1 dari 1
            </td>
        </tr>
    </table>
    <table class="border">
        <tr style="border: 1px solid #000;">
            <td style="padding: 10px;"></td>
        </tr>
        <tr>
            <td style="padding:6px;">
                <br>
                <table>
                    <tr>
                        <td width="100">Tanggal</td>
                        <td class="line">{{ $date }}</td>
                    </tr>
                    <tr>
                        <td width="100">Nama Pemohon</td>
                        <td class="line">{{ $pemohon->name }}</td>
                        <td width="230">
                            (Mutasi/Promosi/Lain2)
                            <span class="small">(coret yang tidak perlu)</span>
                        </td>
                    </tr>
                    <tr>
                        <td>Jabatan</td>
                        <td class="line">{{ $pemohon->jabatan }}</td>
                        <td rowspan="3"></td>
                    </tr>
                    <tr>
                        <td>Departmen</td>
                        <td class="line">{{ $pemohon->division->name }}</td>
                    </tr>
                    <tr>
                        <td>Area / Job Site</td>
                        <td class="line">{{ $pemohon->regional->name }}</td>
                    </tr>
                </table>
                <br>
                <table>
                    <tr>
                        <td width="250">
                            Mengajukan Permintaan Untuk :
                        </td>
                        <td>
                            <span class="checkbox-check"></span>
                            Instalasi Software
                        </td>
                    </tr>
                </table>
                <table>
                    <tr>
                        <td width="80">
                            Software :
                        </td>
                        <td>
                            <span class="checkbox">
                                @if($softwareOptions[0]["slug"] == $selectedSoftware[0])
                                V
                                @endif
                            </span>
                            {{ $softwareOptions[0]["title"] }}
                        </td>
                        <td class="line"></td>
                    </tr>
                    @php
                    array_shift($softwareOptions);
                    @endphp
                    @foreach($softwareOptions as $software)
                    <tr>
                        <td></td>
                        <td>
                            <span class="checkbox">
                                @foreach ($selectedSoftware as $s)
                                @if ($software["slug"] == $s)
                                V
                                @endif
                                @endforeach
                            </span>
                            {{ $software["title"] }}
                        </td>
                        <td class="line"></td>
                    </tr>
                    @endforeach
                </table>
                <br>
                <table>
                    <tr>
                        <td width="80">
                            Keterangan
                        </td>
                        <td class="line">{{ $keterangan }}</td>
                    </tr>
                </table>
                <br>
                <table class="signature">
                    <tr>
                        <td class="center">
                            Diajukan Oleh
                        </td>
                        <td class="center">
                            Diketahui Oleh
                        </td>
                        <td class="center">
                            Disetujui Oleh

                        </td>

                    </tr>

                    <tr>
                        <td class="sign-space">
                            @if($sign['diajukan_approved'] ?? false)
                            <div style="text-align: center; color: green; padding-top: 20px;">
                                &#10003; Disetujui
                                @if($sign['diajukan_date'] ?? false)
                                <br><small style="font-size: 10px;">{{ $sign['diajukan_date'] }}</small>
                                @endif
                            </div>
                            @endif
                        </td>
                        <td class="sign-space">
                            @if($sign['diketahui_approved'] ?? false)
                            <div style="text-align: center; color: green; padding-top: 20px;">
                                &#10003; Disetujui
                                @if($sign['diketahui_date'] ?? false)
                                <br><small style="font-size: 10px;">{{ $sign['diketahui_date'] }}</small>
                                @endif
                            </div>
                            @endif
                        </td>
                        <td class="sign-space">
                            @if($sign['disetujui_approved'] ?? false)
                            <div style="text-align: center; color: green; padding-top: 20px;">
                                &#10003; Disetujui
                                @if($sign['disetujui_date'] ?? false)
                                <br><small style="font-size: 10px;">{{ $sign['disetujui_date'] }}</small>
                                @endif
                            </div>
                            @endif
                        </td>
                    </tr>

                    <tr>
                        <td class="center">
                            {{ $sign["diajukan"] }}
                        </td>
                        <td class="center">
                            {{ $sign["diketahui"]?->name ?? '' }}
                        </td>
                        <td class="center">
                            {{ $sign["disetujui"]?->name ?? '' }}
                        </td>
                    </tr>

                </table>

                <table class="border">

                    <tr>

                        <td style="padding:5px">

                            <b>Perhatian :</b>

                            <div class="note small">

                                1. Hak Akses software sepenuhnya menjadi tanggung jawab Departmen yang bersangkutan dan tidak ada hubungannya dengan Dept MSI.<br>

                                2. Software yang di install di luar standar MSI adalah untuk kepentingan kantor dan Departmen terkait.<br>

                                3. User tidak diperbolehkan menginstal software sendiri di komputer/laptop, apabila ditemukan maka user dikenakan sanksi sesuai peraturan yang berlaku.<br>

                                4. Komputer/laptop akan dikembalikan oleh MSI Departemen setelah selesai dilakukan instalasi software yang direquest.

                            </div>

                        </td>

                    </tr>

                </table>

            </td>

        </tr>

    </table>

</body>

</html>
