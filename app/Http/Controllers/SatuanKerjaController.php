<?php

namespace App\Http\Controllers;

use App\Models\PegawaiSatker;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Validator;

class SatuanKerjaController extends Controller
{
    public function index()
    {
        return view('components.satuan-kerja');
    }

    public function datatable(Request $request)
    {
        if ($request->ajax()) {
            $satuanKerja = PegawaiSatker::query();

            return DataTables::eloquent($satuanKerja)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    return '<ul class="list-unstyled hstack gap-1 mb-0">
                                <li data-bs-toggle="tooltip" data-bs-placement="top" title="Edit">
                                    <button class="btn btn-sm btn-soft-info btn-edit" data-id="' . $row->kode_satker . '"><i class="mdi mdi-pencil-outline mdi-18px"></i></button>
                                </li>
                                <li data-bs-toggle="tooltip" data-bs-placement="top" title="Delete">
                                    <button data-id="' . $row->kode_satker . '" class="btn btn-sm btn-soft-danger btn-delete"><i class="mdi mdi-delete-outline mdi-18px"></i></button>
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
            'kode_satker' => 'required|unique:pegawai_satker,kode_satker',
            'nama_satker' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Bad parameter!',
                'data' => [
                    'error' => $validator->errors()
                ]
            ]);
        }

        try {
            PegawaiSatker::create([
                'kode_satker' => $request->kode_satker,
                'nama_satker' => $request->nama_satker,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Satuan Kerja created successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function show($id)
    {
        $satuanKerja = PegawaiSatker::find($id);

        if (is_null($satuanKerja)) {
            return response()->json([
                'success' => false,
                'message' => 'Satuan Kerja not found',
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Satuan Kerja retrieved successfully',
            'data' => $satuanKerja
        ]);
    }

    public function update(Request $request, $id)
    {
        $satuanKerja = PegawaiSatker::find($id);

        $validator = Validator::make($request->all(), [
            'kode_satker' => 'required|unique:pegawai_satker,kode_satker,' . $id . ',kode_satker',
            'nama_satker' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Bad parameter!',
                'data' => [
                    'error' => $validator->errors()
                ]
            ]);
        }

        try {
            $satuanKerja->update([
                'kode_satker' => $request->kode_satker,
                'nama_satker' => $request->nama_satker,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Satuan Kerja updated successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function destroy($id)
    {
        $satuanKerja = PegawaiSatker::find($id);

        if (is_null($satuanKerja)) {
            return response()->json([
                'success' => false,
                'message' => 'Satuan Kerja not found',
            ]);
        }

        try {
            $satuanKerja->delete();
            return response()->json([
                'success' => true,
                'message' => 'Satuan Kerja deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
