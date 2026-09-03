<?php

namespace App\Http\Controllers\EQTax;

use App\Http\Controllers\Controller;
use App\Models\EQTAXCoretaxSPT;
use App\Models\EQTAXEqualizationResult;
use App\Models\EQTAXGL;
use App\Models\EQTAXTBData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TBController extends Controller
{
    public function index()
    {
        $pageName = "Pencocokkan Trial Balance";

        $distinctPeriods = EQTAXCoretaxSPT::select('masa_pajak', 'tahun')
            ->distinct()
            ->orderBy('tahun', 'desc')
            ->orderBy('masa_pajak', 'desc')
            ->get();

        return view('eqtax.tb.index', compact('pageName', 'distinctPeriods'));
    }

    public function process(Request $request)
    {
        $request->validate([
            'masa_pajak' => 'required|string',
            'tahun' => 'required|string',
        ]);

        $masaPajak = $request->input('masa_pajak');
        $tahun = $request->input('tahun');
        $period = EQTAXEqualizationResult::toPeriod($masaPajak, $tahun);

        // Hitung total PPN SPT
        $totalPpnSpt = EQTAXCoretaxSPT::where('masa_pajak', $masaPajak)
            ->where('tahun', $tahun)
            ->sum('ppn');

        // Hitung total PPN GL
        $totalPpnGl = DB::table('eqtax_gl')
            ->where('jurnal_date', 'like', "{$this->getJurnalDatePrefix($masaPajak, $tahun)}%")
            ->sum('ppn');

        // Load data TB
        $tbData = EQTAXTBData::where('period', $period)->first();
        $ppnTb = $tbData->ppn_tb ?? null;
        $keterangan = $tbData->keterangan ?? null;

        // Hitung selisih
        $selisihTbVsSpt = $ppnTb !== null ? $ppnTb - $totalPpnSpt : null;
        $selisihTbVsGl = $ppnTb !== null ? $ppnTb - $totalPpnGl : null;

        $summary = [
            'total_ppn_spt' => $totalPpnSpt,
            'total_ppn_gl' => $totalPpnGl,
            'ppn_tb' => $ppnTb,
            'keterangan' => $keterangan,
            'selisih_tb_vs_spt' => $selisihTbVsSpt,
            'selisih_tb_vs_gl' => $selisihTbVsGl,
            'masa_pajak' => $masaPajak,
            'tahun' => $tahun,
            'period' => $period,
        ];

        $pageName = "Pencocokkan Trial Balance";

        $distinctPeriods = EQTAXCoretaxSPT::select('masa_pajak', 'tahun')
            ->distinct()
            ->orderBy('tahun', 'desc')
            ->orderBy('masa_pajak', 'desc')
            ->get();

        return view('eqtax.tb.index', compact('pageName', 'summary', 'distinctPeriods'));
    }

    public function save(Request $request)
    {
        $validated = $request->validate([
            'period'  => 'required|string',
            'ppn_tb'  => 'required|numeric',
            'keterangan' => 'nullable|string',
        ]);

        EQTAXTBData::updateOrCreate(
            [
                'period' => $validated['period'],
            ],
            [
                'ppn_tb' => $validated['ppn_tb'],
                'keterangan' => $validated['keterangan'] ?? null,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Data TB berhasil disimpan',
        ]);
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
