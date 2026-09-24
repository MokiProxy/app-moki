<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; margin: 20px; }
        h1 { text-align: center; font-size: 16px; margin-bottom: 2px; }
        h2 { text-align: center; font-size: 13px; margin-top: 0; color: #555; }
        .meta { text-align: center; color: #666; font-size: 9px; margin-bottom: 15px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #cbd5e1; padding: 4px 5px; text-align: left; font-size: 9px; }
        th { background-color: #6366f1; color: white; }
        td.r, th.r { text-align: right; }
        tr:nth-child(even) { background-color: #f8fafc; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <h2>{{ $subtitle }}</h2>
    <p class="meta">@if($rkap) Periode RKAP {{ $rkap->year }} | @endif Dicetak pada: {{ date('d F Y, H:i') }}</p>

    <table>
        <thead>
            <tr>
                <th>KPI</th>
                <th>Kuarter</th>
                <th>Tahun</th>
                <th class="r">Target</th>
                <th class="r">Realisasi</th>
                <th class="r">Skor</th>
                <th class="r">Bobot (%)</th>
                <th class="r">Skor Tertimbang</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
            <tr>
                <td>{{ $row->kpi_name }}</td>
                <td>{{ $row->quarter }}</td>
                <td>{{ $row->year }}</td>
                <td class="r">{{ $row->kpi_target }}</td>
                <td class="r">{{ $row->kpi_actual }}</td>
                <td class="r">{{ $row->kpi_score }}</td>
                <td class="r">{{ $row->weight }}</td>
                <td class="r">{{ $row->weighted_score }}</td>
            </tr>
            @empty
            <tr><td colspan="8" style="text-align:center; color:#999;">Tidak ada data performa.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>