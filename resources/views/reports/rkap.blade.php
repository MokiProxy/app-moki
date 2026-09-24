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
        th { background-color: #4f46e5; color: white; }
        td.r, th.r { text-align: right; }
        tr:nth-child(even) { background-color: #f8fafc; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <h2>{{ $subtitle }}</h2>
    <p class="meta">Periode RKAP {{ $year }} | Dicetak pada: {{ date('d F Y, H:i') }}</p>

    <table>
        <thead>
            <tr>
                <th>Program Kerja</th>
                <th>Kebutuhan</th>
                <th>Elemen Biaya</th>
                <th>Qty</th>
                <th>Satuan</th>
                <th class="r">Harga Satuan</th>
                <th class="r">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
            <tr>
                <td>{{ $row->workProgram->name ?? '-' }}</td>
                <td>{{ $row->need }}</td>
                <td>{{ $row->costElement->name ?? '-' }}</td>
                <td>{{ $row->qty }}</td>
                <td>{{ $row->units }}</td>
                <td class="r">{{ number_format($row->unit_price, 0, ',', '.') }}</td>
                <td class="r">{{ number_format($row->total, 0, ',', '.') }}</td>
            </tr>
            @empty
            <tr><td colspan="7" style="text-align:center; color:#999;">Tidak ada data biaya rutin.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>