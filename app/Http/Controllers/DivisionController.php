<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Division;
use App\Models\Regional;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Validator;

class DivisionController extends Controller
{
    public function index()
    {
        $regionals = Regional::all();
        $companies = Company::all();
        return view('components.division', compact('regionals', 'companies'));
    }

    public function datatable(Request $request)
    {
        if ($request->ajax()) {
            $divisions = Division::with(['regional', 'company'])->select('divisions.*');
            return DataTables::eloquent($divisions)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    return '<ul class="list-unstyled hstack gap-1 mb-0">
                        <li data-bs-toggle="tooltip" title="View">
                            <button type="button" class="btn btn-sm btn-soft-primary btn-view" data-id="' . $row->id . '">
                                <i class="mdi mdi-eye-outline mdi-18px"></i>
                            </button>
                        </li>
                        <li data-bs-toggle="tooltip" title="Edit">
                            <button type="button" class="btn btn-sm btn-soft-info btn-edit" data-id="' . $row->id . '">
                                <i class="mdi mdi-pencil-outline mdi-18px"></i>
                            </button>
                        </li>
                        <li data-bs-toggle="tooltip" title="Delete">
                            <button type="button" data-id="' . $row->id . '" class="btn btn-sm btn-soft-danger btn-delete">
                                <i class="mdi mdi-delete-outline mdi-18px"></i>
                            </button>
                        </li>
                    </ul>';
                })
                ->rawColumns(['action'])
                ->make(true);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'         => 'required|string|max:255',
            'code'         => 'required|max:10|unique:divisions,code',
            'abbreviation' => 'required|string|max:50',
            'company_id'   => 'required|exists:companies,id',
            'regional_id'  => 'required|exists:regionals,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            Division::create($request->all());
            return response()->json(['success' => true, 'message' => 'Divisi berhasil ditambahkan!']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        $division = Division::find($id);
        if (!$division) {
            return response()->json(['success' => false, 'message' => 'Data tidak ditemukan'], 404);
        }
        return response()->json(['success' => true, 'data' => $division]);
    }

    public function update(Request $request, $id)
    {
        $division = Division::find($id);
        if (!$division) {
            return response()->json(['success' => false, 'message' => 'Data tidak ditemukan'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name'         => 'required|string|max:255',
            'code'         => 'required|max:10|unique:divisions,code,' . $id,
            'abbreviation' => 'required|string|max:50',
            'company_id'   => 'required|exists:companies,id',
            'regional_id'  => 'required|exists:regionals,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $division->update($request->all());
            return response()->json(['success' => true, 'message' => 'Divisi berhasil diperbarui!']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $division = Division::findOrFail($id);
            $division->delete();
            return response()->json(['success' => true, 'message' => 'Divisi berhasil dihapus!']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal menghapus data'], 500);
        }
    }
}