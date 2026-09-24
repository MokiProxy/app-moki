<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Program Kerja E-RKAP</title>
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
    <h2>Laporan Program Kerja</h2>
    <p class="meta">Dicetak pada: {{ date('d F Y, H:i') }}</p>

    <table>
        <thead>
            <tr>
                <th style="width: 20px;">No</th>
                <th>Program Kerja</th>
                <th>Sasaran</th>
                <th>Divisi</th>
                <th>Rating</th>
                <th>Satuan</th>
                <th>Tahunan</th>
                <th>Jan</th><th>Feb</th><th>Mar</th><th>Apr</th><th>Mei</th><th>Jun</th>
                <th>Jul</th><th>Agu</th><th>Sep</th><th>Okt</th><th>Nov</th><th>Des</th>
                <th>Kum Jan %</th><th>Kum Feb %</th><th>Kum Mar %</th><th>Kum Apr %</th><th>Kum Mei %</th><th>Kum Jun %</th>
                <th>Kum Jul %</th><th>Kum Agu %</th><th>Kum Sep %</th><th>Kum Okt %</th><th>Kum Nov %</th><th>Kum Des %</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($workPrograms as $workProgram)
            @php
            $departmentTarget = $workProgram->riskIdentification?->departmentTarget;
            $statusClass = $workProgram->statusClass();
            $cumulativePercents = $workProgram->monthlyCumulativePercents();
            @endphp
            <tr>
                <td style="text-align: center;">{{ $loop->iteration }}</td>
                <td><strong>{{ $workProgram->name }}</strong></td>
                <td>{{ $departmentTarget->target ?? '-' }}</td>
                <td>{{ $departmentTarget->division->name ?? '-' }}</td>
                <td style="text-align: center;">{{ $departmentTarget->ratingCriteria->rating ?? '-' }}</td>
                <td>{{ $workProgram->units }}</td>
                <td style="text-align: right;">{{ number_format($workProgram->year_plan, 0, ',', '.') }}</td>
                <td style="text-align: right;">{{ number_format($workProgram->jan_plan, 0, ',', '.') }}</td>
                <td style="text-align: right;">{{ number_format($workProgram->feb_plan, 0, ',', '.') }}</td>
                <td style="text-align: right;">{{ number_format($workProgram->mar_plan, 0, ',', '.') }}</td>
                <td style="text-align: right;">{{ number_format($workProgram->apr_plan, 0, ',', '.') }}</td>
                <td style="text-align: right;">{{ number_format($workProgram->may_plan, 0, ',', '.') }}</td>
                <td style="text-align: right;">{{ number_format($workProgram->jun_plan, 0, ',', '.') }}</td>
                <td style="text-align: right;">{{ number_format($workProgram->jul_plan, 0, ',', '.') }}</td>
                <td style="text-align: right;">{{ number_format($workProgram->aug_plan, 0, ',', '.') }}</td>
                <td style="text-align: right;">{{ number_format($workProgram->sep_plan, 0, ',', '.') }}</td>
                <td style="text-align: right;">{{ number_format($workProgram->oct_plan, 0, ',', '.') }}</td>
                <td style="text-align: right;">{{ number_format($workProgram->nov_plan, 0, ',', '.') }}</td>
                <td style="text-align: right;">{{ number_format($workProgram->dec_plan, 0, ',', '.') }}</td>
                @foreach(\App\Models\Erkap\WorkProgram::MONTH_COLUMNS as $m)
                <td style="text-align: right;">{{ number_format($cumulativePercents[$m], 2, ',', '.') }}%</td>
                @endforeach
                <td style="text-align: center;">
                    <span class="status bg-{{ $statusClass }}">{{ $workProgram->statusLabel() }}</span>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="32" style="text-align: center;">Tidak ada program kerja</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <p style="margin-top: 15px; font-size: 9px; color: #999; text-align: center;">
        Total: {{ $workPrograms->count() }} program kerja
    </p>
</body>
</html>