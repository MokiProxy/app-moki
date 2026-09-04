<?php

namespace App\Http\Controllers\EQTax;

use App\Http\Controllers\Controller;
use App\Imports\GLImport;
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
        $sheets = EQTAXGL::select('sheet')->distinct()->whereNotNull('sheet')->orderBy('sheet')->pluck('sheet');

        return view("eqtax.gl.index", compact("pageName", "glData", "totalRecords", "totalPpn", "totalDpp", "sheets"));
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv'
        ]);

        $import = new GLImport($request->file('file'));
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

    public function updateField(Request $request)
    {
        $record = EQTAXGL::findOrFail($request->input('id'));

        $allowedFields = (new EQTAXGL)->getFillable();
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
        $numberFields = ['dpp', 'ppn'];

        if (in_array($field, $numberFields)) {
            return format_rupiah($value);
        }
        return (string) $value;
    }
}
