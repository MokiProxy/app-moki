<?php

namespace App\Http\Controllers\EQTax;

use App\Http\Controllers\Controller;
use App\Imports\EQTaxImport;
use App\Models\EQTAXCoretaxSPT;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class SPTCoretaxController extends Controller
{
    public function index(Request $request)
    {
        $pageName = "SPT Coretax";

        $query = EQTAXCoretaxSPT::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('no_faktur_pajak', 'like', "%{$search}%")
                  ->orWhere('nama_penjual', 'like', "%{$search}%")
                  ->orWhere('npwp_penjual', 'like', "%{$search}%");
            });
        }

        if ($request->filled('entity')) {
            $query->where('entity', $request->input('entity'));
        }

        if ($request->filled('masa_pajak')) {
            $query->where('masa_pajak', $request->input('masa_pajak'));
        }

        if ($request->filled('tahun')) {
            $query->where('tahun', $request->input('tahun'));
        }

        $sptData = $query->orderBy('created_at', 'desc')->paginate(25)->withQueryString();
        $totalRecords = EQTAXCoretaxSPT::count();
        $totalPpn = EQTAXCoretaxSPT::sum('ppn');
        $totalDpp = EQTAXCoretaxSPT::sum('dpp');
        $entities = EQTAXCoretaxSPT::select('entity')->distinct()->whereNotNull('entity')->orderBy('entity')->pluck('entity');
        $masaPajakList = EQTAXCoretaxSPT::select('masa_pajak')->distinct()->orderBy('masa_pajak')->pluck('masa_pajak');
        $tahunList = EQTAXCoretaxSPT::select('tahun')->distinct()->orderBy('tahun', 'desc')->pluck('tahun');

        return view("eqtax.spt.coretax.index", compact("pageName", "sptData", "totalRecords", "totalPpn", "totalDpp", "entities", "masaPajakList", "tahunList"));
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv'
        ]);

        $excel = Excel::import(new EQTaxImport, $request->file('file'));

        if ($excel) {
            return redirect()->route("eqtax.spt.coretax.index")->with("success", "Import SPT Coretax Berhasil");
        } else
            return redirect()->route("eqtax.spt.coretax.index")->with("error", "Import SPT Coretax Gagal");
    }

    public function updateField(Request $request)
    {
        $record = EQTAXCoretaxSPT::findOrFail($request->input('id'));

        $allowedFields = (new EQTAXCoretaxSPT)->getFillable();
        $field = $request->input('field');

        if (!in_array($field, $allowedFields)) {
            return response()->json(['success' => false, 'message' => 'Field tidak diizinkan'], 422);
        }

        $value = $request->input('value');

        $record->update([$field => $value]);

        return response()->json([
            'success'         => true,
            'message'         => strtoupper($field) . ' berhasil diupdate',
            'formatted_value' => $this->formatFieldValue($field, $value),
        ]);
    }

    private function formatFieldValue(string $field, $value): string
    {
        $booleanFields = ['valid', 'dilaporkan', 'dilaporkan_oleh_penjual'];
        $numberFields = ['harga_jual', 'dpp', 'ppn', 'ppnbm'];

        if (in_array($field, $booleanFields)) {
            return $value ? 'Yes' : 'No';
        }
        if (in_array($field, $numberFields)) {
            return 'Rp ' . number_format((float) $value, 0, ',', '.');
        }
        return (string) $value;
    }
}
