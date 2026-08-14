<?php

namespace App\Http\Controllers\EQTax;

use App\Http\Controllers\Controller;
use App\Imports\EQTaxImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class SPTCoretaxController extends Controller
{
    public function index()
    {
        $pageName = "SPT Coretax";
        return view("eqtax.spt.coretax.index", compact("pageName"));
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
            return redirect()->route("eqtax.spt.coretax.index")->with("error", "Import SPT Coretax Berhasil");
    }
}
