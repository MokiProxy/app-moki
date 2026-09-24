<?php

namespace App\Http\Controllers\Erkap;

use App\Http\Controllers\Controller;
use App\Models\Erkap\ReportItem;
use App\Models\Erkap\RKAP;
use App\Services\Reporting\ReportGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $pageName = 'Laporan & Report Center';

        $items = ReportItem::query()
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('erkap.reports.index', compact('pageName', 'items'));
    }

    public function preview(Request $request, string $type)
    {
        if (! in_array($type, ReportGenerator::TYPES, true)) {
            abort(404);
        }

        $rkap = RKAP::find($request->integer('rkap_id'));
        $year = $request->integer('year', now()->year);
        $month = $request->integer('month') ?: null;

        $view = app(ReportGenerator::class)->generate($type, [
            'rkap' => $rkap,
            'year' => $year,
            'month' => $month,
        ], 'html');

        return response($view->render(), 200)->header('Content-Type', 'text/html');
    }

    public function generate(Request $request)
    {
        $request->validate([
            'report_type' => ['required', 'in:' . implode(',', ReportGenerator::TYPES)],
            'format' => ['required', 'in:pdf,excel'],
            'rkap_id' => ['nullable', 'exists:erkap_rkap,id'],
            'year' => ['required', 'digits:4'],
            'month' => ['nullable', 'integer', 'between:1,12'],
        ]);

        $rkap = $request->filled('rkap_id') ? RKAP::find($request->integer('rkap_id')) : null;
        $type = (string) $request->input('report_type');
        $format = (string) $request->input('format');
        $year = $request->integer('year');

        try {
            $path = app(ReportGenerator::class)->store($type, [
                'rkap' => $rkap,
                'year' => $year,
                'month' => $request->integer('month') ?: null,
            ], $format);

            ReportItem::create([
                'report_type' => $type,
                'title' => $this->titleFor($type, $year),
                'frequency' => 'manual',
                'year' => $year,
                'month' => $request->integer('month') ?: null,
                'format' => $format,
                'file_path' => $path,
                'status' => ReportItem::STATUS_GENERATED,
                'created_by' => auth()->id(),
            ]);

            return redirect()->route('erkap.reports.index')
                ->with('success', 'Laporan berhasil dibuat dan tersedia untuk diunduh.');
        } catch (\Throwable $e) {
            ReportItem::create([
                'report_type' => $type,
                'title' => $this->titleFor($type, $year),
                'frequency' => 'manual',
                'year' => $year,
                'month' => $request->integer('month') ?: null,
                'format' => $format,
                'status' => ReportItem::STATUS_FAILED,
                'error' => $e->getMessage(),
                'created_by' => auth()->id(),
            ]);

            return back()->with('error', 'Gagal membuat laporan: ' . $e->getMessage());
        }
    }

    public function download(Request $request, ReportItem $item)
    {
        if (! $item->file_path) {
            abort(404);
        }

        return Storage::disk('public')->download($item->file_path, basename($item->file_path));
    }

    protected function titleFor(string $type, int $year): string
    {
        $labels = [
            'rkap' => 'RKAP',
            'financial' => 'Keuangan (P&L)',
            'risk' => 'Risiko',
            'realization' => 'Realisasi Anggaran',
            'performance' => 'Performa (KPI)',
        ];

        return 'Laporan ' . ($labels[$type] ?? ucfirst($type)) . " {$year}";
    }
}