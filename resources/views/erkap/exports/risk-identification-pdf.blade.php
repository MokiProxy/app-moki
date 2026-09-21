<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Identifikasi Risiko E-RKAP</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; }
        h2 { text-align: center; margin-bottom: 5px; }
        .meta { text-align: center; color: #666; font-size: 9px; margin-bottom: 15px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #cbd5e1; padding: 4px 6px; text-align: left; font-size: 9px; }
        th { background-color: #4f46e5; color: white; font-weight: bold; text-align: center; }
        tr:nth-child(even) { background-color: #f9f9f9; }
    </style>
</head>
<body>
    <h2>Laporan Identifikasi Risiko</h2>
    <p class="meta">Dicetak pada: {{ date('d F Y, H:i') }}</p>

    <table>
        <thead>
            <tr>
                <th style="width: 24px;">No</th>
                <th>Risk</th>
                <th>Arah Risiko</th>
                <th>Sasaran</th>
                <th>Divisi</th>
                <th>Rating</th>
                <th>Risk Type</th>
                <th>Risk Taxonomy</th>
                <th>Jml Program</th>
            </tr>
        </thead>
        <tbody>
            @forelse($riskIdentifications as $riskIdentification)
            @php
            $departmentTarget = $riskIdentification->departmentTarget;
            @endphp
            <tr>
                <td style="text-align: center;">{{ $loop->iteration }}</td>
                <td><strong>{{ $riskIdentification->risk }}</strong></td>
                <td style="text-align: center;">{{ $riskIdentification->risk_direction === 'positive' ? 'Positif' : 'Negatif' }}</td>
                <td>{{ $departmentTarget->target ?? '-' }}</td>
                <td>{{ $departmentTarget->division->name ?? '-' }}</td>
                <td style="text-align: center;">{{ $departmentTarget->ratingCriteria->rating ?? '-' }}</td>
                <td>{{ $riskIdentification->riskType->name ?? '-' }}</td>
                <td>{{ $riskIdentification->riskTaxonomy->name ?? '-' }}</td>
                <td style="text-align: center;">{{ $riskIdentification->work_programs_count ?? 0 }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="9" style="text-align: center;">Tidak ada identifikasi risiko</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <p style="margin-top: 15px; font-size: 9px; color: #999; text-align: center;">
        Total: {{ $riskIdentifications->count() }} identifikasi risiko
    </p>
</body>
</html>