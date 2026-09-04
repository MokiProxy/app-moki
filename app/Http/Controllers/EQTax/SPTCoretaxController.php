<?php

namespace App\Http\Controllers\EQTax;

use App\Http\Controllers\Controller;
use App\Imports\SPTSheetImport;
use App\Models\EQTAXCoretaxSPT;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class SPTCoretaxController extends Controller
{
    public function index(Request $request)
    {
        $pageName = "SPT Coretax";

        $tabs = [
            'PK'  => 'PK',
            'PM'  => 'PM',
            'PMS' => 'PMS',
        ];

        $activeTab = $request->input('tab');
        if (!array_key_exists($activeTab, $tabs)) {
            $activeTab = 'PK';
        }

        $entity = $tabs[$activeTab];

        $query = EQTAXCoretaxSPT::query()->where('entity', 'like', $entity . '%');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('no_faktur_pajak', 'like', "%{$search}%")
                  ->orWhere('nama_penjual', 'like', "%{$search}%")
                  ->orWhere('npwp_penjual', 'like', "%{$search}%");
            });
        }

        if ($request->filled('masa_pajak')) {
            $query->where('masa_pajak', $request->input('masa_pajak'));
        }

        if ($request->filled('tahun')) {
            $query->where('tahun', $request->input('tahun'));
        }

        $sptData = $query->orderBy('created_at', 'desc')->paginate(25)->withQueryString();

        $totalRecords = EQTAXCoretaxSPT::where('entity', 'like', $entity . '%')->count();
        $totalPpn = EQTAXCoretaxSPT::where('entity', 'like', $entity . '%')->sum('ppn');
        $totalDpp = EQTAXCoretaxSPT::where('entity', 'like', $entity . '%')->sum('dpp');
        $masaPajakList = EQTAXCoretaxSPT::where('entity', 'like', $entity . '%')->select('masa_pajak')->distinct()->orderBy('masa_pajak')->pluck('masa_pajak');
        $tahunList = EQTAXCoretaxSPT::where('entity', 'like', $entity . '%')->select('tahun')->distinct()->orderBy('tahun', 'desc')->pluck('tahun');

        return view("eqtax.spt.coretax.index", compact("pageName", "sptData", "totalRecords", "totalPpn", "totalDpp", "activeTab", "tabs", "masaPajakList", "tahunList"));
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv'
        ]);

        $import = new SPTSheetImport($request->file('file'));
        Excel::import($import, $request->file('file'));
        if (empty($import->result)) {
            return redirect()->route("eqtax.spt.coretax.index")->with("error", "Import SPT Coretax Gagal, Data Kosong");
        }

        DB::transaction(function () use ($import) {
            foreach ($import->result as $record) {
                $key = !empty($record['no_faktur_pajak'])
                    ? ['entity' => $record['entity'], 'no_faktur_pajak' => $record['no_faktur_pajak']]
                    : null;

                if ($key) {
                    EQTAXCoretaxSPT::updateOrCreate($key, $record);
                } else {
                    EQTAXCoretaxSPT::create($record);
                }
            }
        });

        $imported = count($import->result);
        return redirect()->route("eqtax.spt.coretax.index")->with("success", "Import SPT Coretax Berhasil: {$imported} record diproses");
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
            return format_rupiah($value);
        }
        return (string) $value;
    }
}
