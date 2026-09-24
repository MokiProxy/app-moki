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
        th { background-color: #f59e0b; color: white; }
        td.r, th.r { text-align: right; }
        tr:nth-child(even) { background-color: #f8fafc; }
        .summary { margin-top: 12px; border: 1px solid #fcd34d; background: #fffbeb; padding: 8px; font-size: 11px; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <h2>{{ $subtitle }}</h2>
    <p class="meta">Tahun {{ $year }}@if($rkap) | Periode RKAP {{ $rkap->year }}@endif | Dicetak pada: {{ date('d F Y, H:i') }}</p>

    <table>
        <thead>
            <tr>
                <th>Bulan</th>
                <th>Jenis</th>
                <th>Program Kerja</th>
                <th class="r">Anggaran</th>
                <th class="r">Realisasi</th>
                <th class="r">Variance</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
            <tr>
                <td>{{ $row->month }}</td>
                <td>{{ $row->erkap_routine_cost_id !== null ? 'OPEX' : 'CAPEX' }}</td>
                <td>{{ $row->routineCost?->workProgram?->name ?? $row->investmentPlan?->workProgram?->name ?? '-' }}</td>
                <td class="r">{{ number_format($row->budgeted, 0, ',', '.') }}</td>
                <td class="r">{{ number_format($row->realized, 0, ',', '.') }}</td>
                <td class="r">{{ number_format($row->variance, 0, ',', '.') }}</td>
            </tr>
            @empty
            <tr><td colspan="6" style="text-align:center; color:#999;">Tidak ada data realisasi.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="summary">
        Total Anggaran : <strong>{{ number_format($totalBudgeted, 0, ',', '.') }}</strong><br>
        Total Realisasi : <strong>{{ number_format($totalRealized, 0, ',', '.') }}</strong><br>
        Total Variance : <strong>{{ number_format($totalVariance, 0, ',', '.') }}</strong>
    </div>
</body>
</html>