<?php

namespace App\Http\Controllers\EQTax;

use App\Exports\EqualizationExport;
use App\Http\Controllers\Controller;
use App\Models\EQTAXCoretaxSPT;
use App\Models\EQTAXEqualizationResult;
use App\Models\EQTAXGL;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class EqualizationController extends Controller
{
    public function index()
    {
        $pageName = "Ekualisasi Pajak";

        $distinctPeriods = EQTAXCoretaxSPT::select('masa_pajak', 'tahun')
            ->distinct()
            ->orderBy('tahun', 'desc')
            ->orderBy('masa_pajak', 'desc')
            ->get();

        $distinctEntities = EQTAXGL::select('entity')
            ->distinct()
            ->whereNotNull('entity')
            ->orderBy('entity')
            ->get();

        return view('eqtax.equalization.index', compact('pageName', 'distinctPeriods', 'distinctEntities'));
    }

    public function equalization(Request $request)
    {
        $request->validate([
            'masa_pajak' => 'required|string',
            'tahun' => 'required|string',
        ]);

        $masaPajak = $request->input('masa_pajak');
        $tahun = $request->input('tahun');
        $entity = $request->input('entity');
        $period = EQTAXEqualizationResult::toPeriod($masaPajak, $tahun);

        $gl_agg = DB::table('eqtax_gl')
            ->select(
                DB::raw("TRIM(no_faktur_pajak) AS no_faktur_pajak"),
                'entity',
                'nama_supplier',
                DB::raw("SUM(dpp) AS dpp_gl"),
                DB::raw("SUM(ppn) AS ppn_gl"),
                DB::raw("COUNT(*) AS jumlah_item")
            )
            ->where('jurnal_date', 'like', "{$this->getJurnalDatePrefix($masaPajak,$tahun)}%")
            ->when($entity, function ($query) use ($entity) {
                $query->where('entity', $entity);
            })
            ->groupBy(DB::raw("TRIM(no_faktur_pajak)"), 'entity', 'nama_supplier')
            ->get();

        $glTotal = $gl_agg->groupBy('no_faktur_pajak')->map(function ($items) {
            return (object) [
                'no_faktur_pajak' => $items->first()->no_faktur_pajak,
                'nama_supplier' => $items->first()->nama_supplier,
                'dpp_gl_total' => $items->sum('dpp_gl'),
                'ppn_gl_total' => $items->sum('ppn_gl'),
                'entities' => $items->pluck('entity')->implode(', '),
                'jumlah_item' => $items->sum('jumlah_item'),
            ];
        })->keyBy('no_faktur_pajak');

        $sptData = EQTAXCoretaxSPT::select(
            DB::raw("TRIM(no_faktur_pajak) AS no_faktur_pajak"),
            'nama_penjual',
            'npwp_penjual',
            'tgl_faktur_pajak',
            'dpp AS dpp_spt',
            'ppn AS ppn_spt',
            'masa_pajak',
            'tahun',
            'status_faktur'
        )
            ->where('masa_pajak', $masaPajak)
            ->where('tahun', $tahun)
            ->get()
            ->keyBy('no_faktur_pajak');

        $allFakturPajak = $sptData->keys()->merge($glTotal->keys())->unique();

        $results = collect();
        foreach ($allFakturPajak as $fp) {
            $spt = $sptData->get($fp);
            $gl = $glTotal->get($fp);

            $ppnSpt = $spt ? $spt->ppn_spt : 0;
            $ppnGl = $gl ? $gl->ppn_gl_total : 0;
            $dppSpt = $spt ? $spt->dpp_spt : 0;
            $dppGl = $gl ? $gl->dpp_gl_total : 0;
            $selisih = $ppnSpt - $ppnGl;

            if ($spt && $gl) {
                $status = $selisih != 0 ? "TO_BE_CHECK" : "MATCH";
            } elseif ($spt && !$gl) {
                $status = 'SPT_ONLY';
            } else {
                $status = 'GL_ONLY';
            }

            $results->push((object) [
                'no_faktur_pajak' => $fp,
                'nama_penjual' => $spt ? $spt->nama_penjual : ($gl ? $gl->nama_supplier : '-'),
                'npwp_penjual' => $spt ? $spt->npwp_penjual : '-',
                'tgl_faktur_pajak' => $spt ? $spt->tgl_faktur_pajak : '-',
                'dpp_spt' => $dppSpt,
                'dpp_gl' => $dppGl,
                'ppn_spt' => $ppnSpt,
                'ppn_gl' => $ppnGl,
                'selisih_ppn' => $selisih,
                'status' => $status,
                'entities' => $gl ? $gl->entities : '-',
                'jumlah_item_gl' => $gl ? $gl->jumlah_item : 0,
                'status_faktur' => $spt ? $spt->status_faktur : '-',
            ]);
        }

        $results = $results->sortByDesc('selisih_ppn')->values();

        // Simpan hasil ekualisasi ke database
        $this->saveResults($results, $period, $entity);

        $summary = [
            'total_spt' => $sptData->count(),
            'total_gl' => $glTotal->count(),
            'total_ppn_spt' => $sptData->sum('ppn_spt'),
            'total_ppn_gl' => $glTotal->sum('ppn_gl_total'),
            'total_selisih' => $sptData->sum('ppn_spt') - $glTotal->sum('ppn_gl_total'),
            'count_match' => $results->where('status', 'MATCH')->count(),
            'count_spt_only' => $results->where('status', 'SPT_ONLY')->count(),
            'count_gl_only' => $results->where('status', 'GL_ONLY')->count(),
            'masa_pajak' => $masaPajak,
            'tahun' => $tahun,
            'entity' => $entity ?? 'Semua',
        ];

        $pageName = "Ekualisasi Pajak";
        $distinctPeriods = EQTAXCoretaxSPT::select('masa_pajak', 'tahun')
            ->distinct()
            ->orderBy('tahun', 'desc')
            ->orderBy('masa_pajak', 'desc')
            ->get();

        $distinctEntities = EQTAXGL::select('entity')
            ->distinct()
            ->whereNotNull('entity')
            ->orderBy('entity')
            ->get();

        return view('eqtax.equalization.index', compact('pageName', 'results', 'summary', 'distinctPeriods', 'distinctEntities'));
    }

    public function export(Request $request)
    {
        $request->validate([
            'masa_pajak' => 'required|string',
            'tahun' => 'required|string',
        ]);

        $masaPajak = $request->input('masa_pajak');
        $tahun = $request->input('tahun');
        $entity = $request->input('entity');

        $gl_agg = DB::table('eqtax_gl')
            ->select(
                DB::raw("TRIM(no_faktur_pajak) AS no_faktur_pajak"),
                'entity',
                DB::raw("SUM(dpp) AS dpp_gl"),
                DB::raw("SUM(ppn) AS ppn_gl"),
                DB::raw("COUNT(*) AS jumlah_item")
            )
            ->where('jurnal_date', 'like', "{$this->getJurnalDatePrefix($masaPajak,$tahun)}%")
            ->when($entity, function ($query) use ($entity) {
                $query->where('entity', $entity);
            })
            ->groupBy(DB::raw("TRIM(no_faktur_pajak)"), 'entity')
            ->get();

        $glTotal = $gl_agg->groupBy('no_faktur_pajak')->map(function ($items) {
            return (object) [
                'no_faktur_pajak' => $items->first()->no_faktur_pajak,
                'dpp_gl_total' => $items->sum('dpp_gl'),
                'ppn_gl_total' => $items->sum('ppn_gl'),
                'entities' => $items->pluck('entity')->implode(', '),
            ];
        })->keyBy('no_faktur_pajak');

        $sptData = EQTAXCoretaxSPT::select(
            DB::raw("TRIM(no_faktur_pajak) AS no_faktur_pajak"),
            'nama_penjual',
            'npwp_penjual',
            'dpp AS dpp_spt',
            'ppn AS ppn_spt',
            'masa_pajak',
            'tahun'
        )
            ->where('masa_pajak', $masaPajak)
            ->where('tahun', $tahun)
            ->get()
            ->keyBy('no_faktur_pajak');

        $allFakturPajak = $sptData->keys()->merge($glTotal->keys())->unique();

        $exportData = collect();
        foreach ($allFakturPajak as $fp) {
            $spt = $sptData->get($fp);
            $gl = $glTotal->get($fp);

            $ppnSpt = $spt ? $spt->ppn_spt : 0;
            $ppnGl = $gl ? $gl->ppn_gl_total : 0;

            if ($spt && $gl) {
                $status = 'MATCH';
            } elseif ($spt && !$gl) {
                $status = 'SPT_ONLY';
            } else {
                $status = 'GL_ONLY';
            }

            $exportData->push((object) [
                'no_faktur_pajak' => $fp,
                'nama_penjual' => $spt ? $spt->nama_penjual : '-',
                'npwp_penjual' => $spt ? $spt->npwp_penjual : '-',
                'dpp_spt' => $spt ? $spt->dpp_spt : 0,
                'dpp_gl' => $gl ? $gl->dpp_gl_total : 0,
                'ppn_spt' => $ppnSpt,
                'ppn_gl' => $ppnGl,
                'selisih_ppn' => $ppnSpt - $ppnGl,
                'status' => $status,
                'entities' => $gl ? $gl->entities : '-',
            ]);
        }

        $summary = [
            'masa_pajak' => $masaPajak,
            'tahun' => $tahun,
            'entity' => $entity ?? 'Semua',
        ];

        $fileName = "ekualisasi_pajak_{$masaPajak}_{$tahun}" . ($entity ? "_{$entity}" : "") . ".xlsx";

        return Excel::download(new EqualizationExport($exportData, $summary), $fileName);
    }

    private function saveResults($results, string $period, ?string $entity): void
    {
        // Hapus hasil lama untuk periode + entity yang sama
        EQTAXEqualizationResult::where('period', $period)
            ->when($entity, fn($q) => $q->where('entity', $entity))
            ->delete();

        // Insert batch
        $records = $results->map(fn($r) => [
            'period' => $period,
            'entity' => $r->entities ?? $entity,
            'no_faktur_pajak' => $r->no_faktur_pajak,
            'nama_penjual' => $r->nama_penjual,
            'dpp_spt' => $r->dpp_spt,
            'dpp_gl' => $r->dpp_gl,
            'ppn_spt' => $r->ppn_spt,
            'ppn_gl' => $r->ppn_gl,
            'selisih_ppn' => $r->selisih_ppn,
            'status' => $r->status,
            'keterangan' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ])->toArray();

        $chunks = collect($records)->chunk(500);
        foreach ($chunks as $chunk) {
            EQTAXEqualizationResult::insert($chunk->toArray());
        }
    }

    private function getJurnalDatePrefix(string $masaPajak, string $tahun): string
    {
        $monthMap = [
            'Januari' => '01',
            'Februari' => '02',
            'Maret' => '03',
            'April' => '04',
            'Mei' => '05',
            'Juni' => '06',
            'Juli' => '07',
            'Agustus' => '08',
            'September' => '09',
            'Oktober' => '10',
            'November' => '11',
            'Desember' => '12',
        ];
        $month = $monthMap[$masaPajak] ?? '01';
        return "{$tahun}{$month}";
    }
}
