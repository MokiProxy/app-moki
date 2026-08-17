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
    public function index()
    {
        $pageName = "General Ledger";
        return view("eqtax.gl.index", compact("pageName"));
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv'
        ]);

        $import = new PPNSheetImport($request->file('file'));
        Excel::import($import, $request->file('file'));

        if (empty($import->result)) {
            return redirect()->route("eqtax.spt.coretax.index")->with("error", "Import SPT Coretax Gagal, Data Kosong");
        }

        DB::transaction(function () use ($import) {
            foreach (array_chunk($import->result, 500) as $chunk) {
                EQTAXGL::insert($chunk);
            }
        });

        return redirect()->route("eqtax.spt.coretax.index")->with("success", "Import SPT Coretax Berhasil");
    }
}
