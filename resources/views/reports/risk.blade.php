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
        th { background-color: #0ea5e9; color: white; }
        td.r, th.r { text-align: right; }
        tr:nth-child(even) { background-color: #f8fafc; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <h2>{{ $subtitle }}</h2>
    <p class="meta">Tahun {{ $year }}@if($month) | Bulan: {{ $month }}@endif | Dicetak pada: {{ date('d F Y, H:i') }}</p>

    <table>
        <thead>
            <tr>
                <th>Bulan</th>
                <th>Divisi</th>
                <th>Risiko</th>
                <th class="r">Prob</th>
                <th class="r">Impact</th>
                <th class="r">Skor</th>
                <th>Level</th>
                <th>Status Mitigasi</th>
            </tr>
        </thead>
        <tbody>
            @php
            $level = function ($score) {
                return match (true) {
                    $score >= 21 => 'VH', $score >= 16 => 'H', $score >= 11 => 'M', $score >= 6 => 'L', default => 'VL',
                };
            };
            @endphp
            @forelse($rows as $row)
            <tr>
                <td>{{ $row->month }}</td>
                <td>{{ $row->riskIdentification?->departmentTarget?->division?->name ?? '-' }}</td>
                <td>{{ $row->riskIdentification?->risk ?? '-' }}</td>
                <td class="r">{{ $row->inherent_probability }}</td>
                <td class="r">{{ $row->inherent_impact }}</td>
                <td class="r">{{ $row->inherent_score }}</td>
                <td>{{ $level($row->inherent_score) }}</td>
                <td>{{ $row->mitigation_status ?? '-' }}</td>
            </tr>
            @empty
            <tr><td colspan="8" style="text-align:center; color:#999;">Tidak ada data risk assessment.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>