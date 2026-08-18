<?php

namespace App\Http\Controllers\EQTax;

use App\Http\Controllers\Controller;
use App\Imports\PPNSheetImport;
use App\Models\EQTAXGL;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class GLController extends Controller
{
    public function index(Request $request)
    {
        $pageName = "General Ledger";

        $query = EQTAXGL::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('no_faktur_pajak', 'like', "%{$search}%")
                  ->orWhere('nama_supplier', 'like', "%{$search}%")
                  ->orWhere('jurnal_no', 'like', "%{$search}%")
                  ->orWhere('no_supplier', 'like', "%{$search}%");
            });
        }

        if ($request->filled('entity')) {
            $query->where('entity', $request->input('entity'));
        }

        if ($request->filled('sheet')) {
            $query->where('sheet', $request->input('sheet'));
        }

        if ($request->filled('date_from')) {
            $query->where('jurnal_date', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->where('jurnal_date', '<=', $request->input('date_to'));
        }

        $glData = $query->orderBy('created_at', 'desc')->paginate(25)->withQueryString();
        $totalRecords = EQTAXGL::count();
        $totalPpn = EQTAXGL::sum('ppn');
        $totalDpp = EQTAXGL::sum('dpp');
        $entitySummary = EQTAXGL::select('entity', DB::raw('COUNT(*) as count'), DB::raw('SUM(dpp) as total_dpp'), DB::raw('SUM(ppn) as total_ppn'))
            ->groupBy('entity')
            ->get();
        $entities = EQTAXGL::select('entity')->distinct()->whereNotNull('entity')->orderBy('entity')->pluck('entity');
        $sheets = EQTAXGL::select('sheet')->distinct()->whereNotNull('sheet')->orderBy('sheet')->pluck('sheet');

        return view("eqtax.gl.index", compact("pageName", "glData", "totalRecords", "totalPpn", "totalDpp", "entitySummary", "entities", "sheets"));
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv'
        ]);

        $import = new PPNSheetImport($request->file('file'));
        Excel::import($import, $request->file('file'));

        if (empty($import->result)) {
            return redirect()->route("eqtax.gl.index")->with("error", "Import GL Gagal, Data Kosong");
        }

        DB::transaction(function () use ($import) {
            foreach (array_chunk($import->result, 500) as $chunk) {
                EQTAXGL::insert($chunk);
            }
        });

        return redirect()->route("eqtax.gl.index")->with("success", "Import GL Berhasil");
    }
}
