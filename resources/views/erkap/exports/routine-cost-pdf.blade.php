<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Biaya Rutin E-RKAP</title>
    <style>
        @page { size: A4 landscape; margin: 10mm; }
        body { font-family: sans-serif; font-size: 10px; }
        h2 { text-align: center; margin-bottom: 5px; }
        .meta { text-align: center; color: #666; font-size: 9px; margin-bottom: 15px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #cbd5e1; padding: 3px 5px; text-align: left; font-size: 8px; }
        th { background-color: #4f46e5; color: white; font-weight: bold; text-align: center; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .status { padding: 1px 6px; border-radius: 8px; color: white; font-size: 7px; font-weight: bold; display: inline-block; }
        .bg-primary { background-color: #4f46e5; }
        .bg-warning { background-color: #f59e0b; }
        .bg-success { background-color: #10b981; }
        .bg-danger { background-color: #ef4444; }
    </style>
</head>
<body>
    <h2>Laporan Biaya Rutin</h2>
    <p class="meta">Dicetak pada: {{ date('d F Y, H:i') }}</p>

    <table>
        <thead>
            <tr>
                <th style="width: 20px;">No</th>
                <th>Program Kerja</th>
                <th>Kebutuhan</th>
                <th>Elemen Biaya</th>
                <th>Chart of Account</th>
                <th>Pusat Biaya</th>
                <th>Qty</th>
                <th>Satuan</th>
                <th>Harga Satuan</th>
                <th>Jan</th><th>Feb</th><th>Mar</th><th>Apr</th><th>Mei</th><th>Jun</th>
                <th>Jul</th><th>Agu</th><th>Sep</th><th>Okt</th><th>Nov</th><th>Des</th>
                <th>Total</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($routineCosts as $routineCost)
            <tr>
                <td style="text-align: center;">{{ $loop->iteration }}</td>
                <td>{{ $routineCost->workProgram->name ?? '-' }}</td>
                <td>{{ $routineCost->need }}</td>
                <td>{{ $routineCost->costElement->name ?? '-' }}</td>
                <td>{{ $routineCost->chartOfAccount ? $routineCost->chartOfAccount->formattedCode . ' - ' . $routineCost->chartOfAccount->name : '-' }}</td>
                <td>{{ $routineCost->costCenter->name ?? '-' }}</td>
                <td style="text-align: right;">{{ number_format($routineCost->qty, 0, ',', '.') }}</td>
                <td>{{ $routineCost->units }}</td>
                <td style="text-align: right;">{{ number_format($routineCost->unit_price, 0, ',', '.') }}</td>
                <td style="text-align: right;">{{ number_format($routineCost->jan_cost, 0, ',', '.') }}</td>
                <td style="text-align: right;">{{ number_format($routineCost->feb_cost, 0, ',', '.') }}</td>
                <td style="text-align: right;">{{ number_format($routineCost->mar_cost, 0, ',', '.') }}</td>
                <td style="text-align: right;">{{ number_format($routineCost->apr_cost, 0, ',', '.') }}</td>
                <td style="text-align: right;">{{ number_format($routineCost->may_cost, 0, ',', '.') }}</td>
                <td style="text-align: right;">{{ number_format($routineCost->jun_cost, 0, ',', '.') }}</td>
                <td style="text-align: right;">{{ number_format($routineCost->jul_cost, 0, ',', '.') }}</td>
                <td style="text-align: right;">{{ number_format($routineCost->aug_cost, 0, ',', '.') }}</td>
                <td style="text-align: right;">{{ number_format($routineCost->sep_cost, 0, ',', '.') }}</td>
                <td style="text-align: right;">{{ number_format($routineCost->oct_cost, 0, ',', '.') }}</td>
                <td style="text-align: right;">{{ number_format($routineCost->nov_cost, 0, ',', '.') }}</td>
                <td style="text-align: right;">{{ number_format($routineCost->des_cost, 0, ',', '.') }}</td>
                <td style="text-align: right;"><strong>{{ number_format($routineCost->total, 0, ',', '.') }}</strong></td>
                <td style="text-align: center;">
                    <span class="status bg-{{ $routineCost->statusClass() }}">{{ $routineCost->statusLabel() }}</span>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="23" style="text-align: center;">Tidak ada biaya rutin</td>
            </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <th colspan="21" style="text-align: right;">GRAND TOTAL</th>
                <th style="text-align: right;">{{ number_format($routineCosts->sum('total'), 0, ',', '.') }}</th>
                <th></th>
            </tr>
        </tfoot>
    </table>

    <p style="margin-top: 15px; font-size: 9px; color: #999; text-align: center;">
        Total: {{ $routineCosts->count() }} biaya rutin
    </p>
</body>
</html>