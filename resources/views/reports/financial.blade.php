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
        th { background-color: #10b981; color: white; }
        td.r, th.r { text-align: right; }
        tr:nth-child(even) { background-color: #f8fafc; }
        .summary { margin-top: 12px; border: 1px solid #a7f3d0; background: #ecfdf5; padding: 8px; font-size: 11px; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <h2>{{ $subtitle }}</h2>
    <p class="meta">Dicetak pada: {{ date('d F Y, H:i') }}</p>

    <div style="margin-bottom:4px;">
        <strong>Pendapatan</strong>
        <table>
            <thead>
                <tr>
                    <th>Akun (CoA)</th>
                    <th>Divisi</th>
                    <th>Deskripsi</th>
                    <th class="r">Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse($revenues as $row)
                <tr>
                    <td>{{ $row->chartOfAccount?->name ?? $row->description }}</td>
                    <td>{{ $row->division?->name ?? '-' }}</td>
                    <td>{{ $row->description }}</td>
                    <td class="r">{{ number_format($row->total, 0, ',', '.') }}</td>
                </tr>
                @empty
                <tr><td colspan="4" style="text-align:center; color:#999;">Tidak ada data pendapatan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-bottom:4px;">
        <strong>Beban</strong>
        <table>
            <thead>
                <tr>
                    <th>Akun (CoA)</th>
                    <th>Divisi</th>
                    <th>Deskripsi</th>
                    <th class="r">Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse($expenses as $row)
                <tr>
                    <td>{{ $row->chartOfAccount?->name ?? $row->description }}</td>
                    <td>{{ $row->division?->name ?? '-' }}</td>
                    <td>{{ $row->description }}</td>
                    <td class="r">{{ number_format($row->total, 0, ',', '.') }}</td>
                </tr>
                @empty
                <tr><td colspan="4" style="text-align:center; color:#999;">Tidak ada data beban.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="summary">
        Total Pendapatan : <strong>{{ number_format($totalRevenue, 0, ',', '.') }}</strong><br>
        Total Beban : <strong>{{ number_format($totalExpense, 0, ',', '.') }}</strong><br>
        Laba Rugi : <strong>{{ number_format($netProfit, 0, ',', '.') }}</strong>
    </div>
</body>
</html>