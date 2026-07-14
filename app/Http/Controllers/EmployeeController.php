<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Division;
use App\Models\Regional;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use App\Exports\EmployeeTemplateExport;
use App\Imports\EmployeeImport;
use Maatwebsite\Excel\Facades\Excel;

class EmployeeController extends Controller
{
    public function index()
    {
        $divisions = Division::all();
        $regionals = Regional::all();
        return view('components.employee', compact('divisions', 'regionals'));
    }

    public function datatable(Request $request)
    {
        // Eager loading division dan regional
        $data = Employee::with(['division', 'regional'])->latest();
        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('departemen', function ($row) {
                return $row->division->name ?? '-';
            })
            ->addColumn('kode_dept', function ($row) {
                // Menampilkan kode dari tabel divisions
                return $row->division->code ?? '-';
            })
            ->addColumn('regional', function ($row) {
                return $row->regional->name ?? '-';
            })
            ->addColumn('action', function ($row) {
                // Pastikan atribut data-id dan atribut lainnya lengkap untuk JS
                return '
                <div class="btn-group">
                    <button class="btn btn-sm btn-info btn-view" data-id="' . $row->id . '" title="Detail"><i class="mdi mdi-eye"></i></button>
                    <button class="btn btn-sm btn-primary btn-edit"
                        data-id="' . $row->id . '"
                        data-division-id="' . $row->division_id . '"
                        data-division-code="' . ($row->division->code ?? '') . '"
                        title="Edit"><i class="mdi mdi-pencil"></i></button>
                    <button class="btn btn-sm btn-danger btn-delete" data-id="' . $row->id . '" title="Hapus"><i class="mdi mdi-trash-can"></i></button>
                </div>';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $request->validate(['employee_id' => 'required|unique:employees', 'name' => 'required']);

        Employee::create($request->all());
        return response()->json(['success' => true, 'message' => 'Karyawan berhasil ditambahkan!']);
    }

    public function show($id)
    {
        $employee = Employee::find($id);
        return response()->json(['success' => true, 'data' => $employee]);
    }

    public function update(Request $request, $id)
    {
        $employee = Employee::findOrFail($id);
        $employee->update($request->all());
        return response()->json(['success' => true, 'message' => 'Data berhasil diperbarui!']);
    }

    public function destroy($id)
    {
        Employee::destroy($id);
        return response()->json(['success' => true, 'message' => 'Karyawan dihapus!']);
    }

    // --- FITUR EXCEL ---

    public function downloadTemplate()
    {
        // Pastikan Anda sudah membuat Route::get('/employee/template', ...) jika ingin membedakan
        return Excel::download(new EmployeeTemplateExport, 'template_karyawan.xlsx');
    }

    public function import(Request $request)
    {
        $request->validate(['file' => 'required|mimes:xlsx,xls']);

        try {
            Excel::import(new EmployeeImport, $request->file('file'));
            return response()->json(['success' => true, 'message' => 'Data berhasil diimport!']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }
}
