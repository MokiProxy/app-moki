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

    public function delete(Request $request)
    {
        $validated = $request->validate([
            'select_all' => 'nullable|boolean',
            'ids'        => 'nullable|array',
            'ids.*'      => 'integer',
            'tab'        => 'nullable|string|in:PK,PM,PMS',
            'search'     => 'nullable|string',
            'masa_pajak' => 'nullable|string',
            'tahun'      => 'nullable|string',
        ]);

        $activeTab = $validated['tab'] ?? 'PK';
        $selectAll = !empty($validated['select_all']);

        if (!$selectAll && empty($validated['ids'])) {
            return response()->json([
                'success' => false,
                'message' => 'Pilih minimal satu data yang akan dihapus.',
            ], 422);
        }

        // Delete selalu dibatasi tab/entity aktif agar data tab lain tidak ikut terhapus.
        $query = EQTAXCoretaxSPT::query()->where('entity', 'like', $activeTab . '%');

        if ($selectAll) {
            if (!empty($validated['search'])) {
                $search = $validated['search'];
                $query->where(function ($q) use ($search) {
                    $q->where('no_faktur_pajak', 'like', "%{$search}%")
                      ->orWhere('nama_penjual', 'like', "%{$search}%")
                      ->orWhere('npwp_penjual', 'like', "%{$search}%");
                });
            }
            if (!empty($validated['masa_pajak'])) {
                $query->where('masa_pajak', $validated['masa_pajak']);
            }
            if (!empty($validated['tahun'])) {
                $query->where('tahun', $validated['tahun']);
            }
        } else {
            $query->whereIn('id', $validated['ids']);
        }

        $deleted = $query->delete();

        if ($deleted === 0) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada data yang dihapus. Data mungkin sudah tidak ada atau tidak sesuai tab aktif.',
            ], 422);
        }

        return response()->json([
            'success'       => true,
            'message'       => "{$deleted} data SPT berhasil dihapus.",
            'deleted_count' => $deleted,
            'redirect_url'  => route('eqtax.spt.coretax.index', array_filter([
                'tab'        => $activeTab,
                'search'     => $validated['search'] ?? null,
                'masa_pajak' => $validated['masa_pajak'] ?? null,
                'tahun'      => $validated['tahun'] ?? null,
            ])),
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
