<?php

namespace App\Http\Controllers;

use App\Imports\TestImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class TestingController extends Controller
{
    public function test() {
        return view("test");
    }
    public function excel(Request $request) {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv'
        ]);

        $excel = Excel::import(new TestImport, $request->file('file'));

        return dd($excel);
    }
}
