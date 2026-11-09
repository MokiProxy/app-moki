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
        .negative { color: #b91c1c; }
        .positive { color: #047857; }
        h3 { font-size: 12px; margin: 16px 0 4px; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <h2>{{ $subtitle }}</h2>
    <p class="meta">
        RKAP {{ $summary['rkap']?->year ?? '-' }} &middot;
        Dicetak pada: {{ date('d F Y, H:i') }}
    </p>

    <h3>Ringkasan KPI</h3>
    <table>
        <thead>
            <tr>
                <th>Komponen</th>
                <th class="r">Nilai</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    <td>{{ $row['Komponen'] }}</td>
                    <td class="r">
                        @if (str_contains($row['Komponen'], '(%)'))
                            {{ number_format((float) $row['Nilai'], 2, ',', '.') }}%
                        @else
                            {{ number_format((float) $row['Nilai'], 0, ',', '.') }}
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h3>
        @if ($domain === 'revenue')
            Rencana Pendapatan per Bulan
        @elseif ($domain === 'expense')
            Rencana Pengeluaran per Bulan
        @else
            Rencana Pendapatan vs Pengeluaran per Bulan
        @endif
    </h3>
    <table>
        <thead>
            <tr>
                <th>Bulan</th>
                @if ($domain !== 'expense')
                    <th class="r">Pendapatan</th>
                @endif
                @if ($domain !== 'revenue')
                    <th class="r">Pengeluaran</th>
                @endif
                @if ($domain === 'all')
                    <th class="r">Selisih</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @foreach ($summary['monthly'] as $month)
                @php($difference = $month['revenue'] - $month['expense'])
                <tr>
                    <td>{{ $month['label'] }}</td>
                    @if ($domain !== 'expense')
                        <td class="r">{{ number_format($month['revenue'], 0, ',', '.') }}</td>
                    @endif
                    @if ($domain !== 'revenue')
                        <td class="r">{{ number_format($month['expense'], 0, ',', '.') }}</td>
                    @endif
                    @if ($domain === 'all')
                        <td class="r {{ $difference < 0 ? 'negative' : 'positive' }}">
                            {{ number_format($difference, 0, ',', '.') }}
                        </td>
                    @endif
                </tr>
            @endforeach
        </tbody>
    </table>

    @if ($domain !== 'expense' && count($revenue_by_coa) > 0)
        <h3>Rencana Pendapatan per Akun (CoA)</h3>
        <table>
            <thead>
                <tr>
                    <th>Kode Akun</th>
                    <th>Nama Akun</th>
                    <th class="r">Total Rencana</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($revenue_by_coa as $account)
                    <tr>
                        <td>{{ $account['code'] }}</td>
                        <td>{{ $account['name'] }}</td>
                        <td class="r">{{ number_format($account['total'], 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="summary">
        @if ($domain !== 'expense')
            Total Pendapatan (Rencana) : <strong>{{ number_format($summary['revenue_plan'], 0, ',', '.') }}</strong><br>
        @endif
        @if ($domain !== 'revenue')
            Total Pengeluaran (Rencana) : <strong>{{ number_format($summary['expense_plan'], 0, ',', '.') }}</strong><br>
            Total Pengeluaran (Opex + Capex) : <strong>{{ number_format($summary['expense_total'], 0, ',', '.') }}</strong><br>
            Anggaran Opex + Capex : <strong>{{ number_format($summary['expense_budget'], 0, ',', '.') }}</strong><br>
            Realisasi Pengeluaran : <strong>{{ number_format($summary['realized'], 0, ',', '.') }}</strong>
            ({{ number_format($summary['absorption'], 2, ',', '.') }}%)<br>
        @endif
        @if ($domain === 'all')
            Selisih : <strong>{{ number_format($summary['balance'], 0, ',', '.') }}</strong>
        @endif
    </div>
</body>
</html>
