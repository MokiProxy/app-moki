<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Konsolidasi Anggaran E-RKAP</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; }
        h2 { text-align: center; margin-bottom: 5px; }
        .meta { text-align: center; color: #666; font-size: 9px; margin-bottom: 15px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #cbd5e1; padding: 4px 5px; text-align: left; font-size: 9px; }
        th { background-color: #4f46e5; color: white; font-weight: bold; text-align: center; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .bg-light { background-color: #eef2ff; font-weight: bold; }
        td.r, th.r { text-align: right; }
    </style>
</head>
<body>
    <h2>Konsolidasi Anggaran (Budget vs Realisasi)</h2>
    <p class="meta">Periode TRKA {{ $year }} | Divisi: {{ $division_name }} | Dicetak pada: {{ date('d F Y, H:i') }}</p>

    @php
    $components = [
        'opex_budget' => 'Budget OPEX',
        'capex_budget' => 'Budget CAPEX',
        'total_budget' => 'Total Budget',
        'opex_realized' => 'Realisasi OPEX',
        'capex_realized' => 'Realisasi CAPEX',
        'total_realized' => 'Total Realisasi',
        'variance' => 'Variance',
        'variance_percent' => 'Variance %',
    ];
    @endphp

    <table>
        <thead>
            <tr>
                <th>Komponen</th>
                <th>Jan</th><th>Feb</th><th>Mar</th><th>Apr</th><th>Mei</th><th>Jun</th>
                <th>Jul</th><th>Agu</th><th>Sep</th><th>Okt</th><th>Nov</th><th>Des</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($components as $key => $name)
            <tr>
                <td><strong>{{ $name }}</strong></td>
                @foreach($monthlyRows as $monthRow)
                <td class="r">{{ $key === 'variance_percent' ? number_format($monthRow[$key], 2, ',', '.') . '%' : number_format($monthRow[$key], 0, ',', '.') }}</td>
                @endforeach
                <td class="r">{{ $key === 'variance_percent' ? number_format($totals[$key], 2, ',', '.') . '%' : number_format($totals[$key], 0, ',', '.') }}</td>
            </tr>
            @endforeach
            @foreach($divisionRows as $divisionRow)
            <tr class="bg-light">
                <td>{{ $divisionRow['division_name'] }} - Budget</td>
                @foreach($divisionRow['monthly_budget'] as $value)
                <td class="r">{{ number_format($value, 0, ',', '.') }}</td>
                @endforeach
                <td class="r">{{ number_format($divisionRow['budget'], 0, ',', '.') }}</td>
            </tr>
            <tr class="bg-light">
                <td>{{ $divisionRow['division_name'] }} - Realisasi</td>
                @foreach($divisionRow['monthly_realized'] as $value)
                <td class="r">{{ number_format($value, 0, ',', '.') }}</td>
                @endforeach
                <td class="r">{{ number_format($divisionRow['realized'], 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <p style="margin-top: 15px; font-size: 9px; color: #999; text-align: center;">
        Realisasi dihitung terhadap tahun {{ $year }} (YTD).
    </p>
</body>
</html>