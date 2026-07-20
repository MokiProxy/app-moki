<?php

namespace App\Http\Controllers;

use App\Models\PegawaiMasterPosisi;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Validator;

class PegawaiMasterPosisiController extends Controller
{
    public function index()
    {
        $positions = PegawaiMasterPosisi::all();

        return view('components.master-posisi', compact('positions'));
    }

    public function datatable(Request $request)
    {
        if ($request->ajax()) {
            $posisi = PegawaiMasterPosisi::with('parent');

            return DataTables::eloquent($posisi)
                ->addIndexColumn()
                ->addColumn('superior_name', function ($row) {
                    return $row->parent ? $row->parent->pos_title : '-';
                })
                ->addColumn('action', function ($row) {
                    return '<ul class="list-unstyled hstack gap-1 mb-0">
                                <li data-bs-toggle="tooltip" data-bs-placement="top" title="Edit">
                                    <button class="btn btn-sm btn-soft-info btn-edit" data-id="' . $row->position_id . '"><i class="mdi mdi-pencil-outline mdi-18px"></i></button>
                                </li>
                                <li data-bs-toggle="tooltip" data-bs-placement="top" title="Delete">
                                    <button data-id="' . $row->position_id . '" class="btn btn-sm btn-soft-danger btn-delete"><i class="mdi mdi-delete-outline mdi-18px"></i></button>
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
            'position_id' => 'required|unique:pegawai_master_posisi,position_id',
            'pos_title' => 'required',
            'superior_id' => 'nullable|exists:pegawai_master_posisi,position_id',
            'last_mode_date' => 'nullable|date',
            'last_mode_time' => 'nullable',
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
            PegawaiMasterPosisi::create([
                'position_id' => $request->position_id,
                'pos_title' => $request->pos_title,
                'superior_id' => $request->superior_id,
                'last_mode_date' => $request->last_mode_date,
                'last_mode_time' => $request->last_mode_time,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Master Posisi created successfully',
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
        $posisi = PegawaiMasterPosisi::find($id);

        if (is_null($posisi)) {
            return response()->json([
                'success' => false,
                'message' => 'Master Posisi not found',
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Master Posisi retrieved successfully',
            'data' => $posisi
        ]);
    }

    public function update(Request $request, $id)
    {
        $posisi = PegawaiMasterPosisi::find($id);

        $validator = Validator::make($request->all(), [
            'position_id' => 'required|unique:pegawai_master_posisi,position_id,' . $id . ',position_id',
            'pos_title' => 'required',
            'superior_id' => 'nullable|exists:pegawai_master_posisi,position_id',
            'last_mode_date' => 'nullable|date',
            'last_mode_time' => 'nullable',
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
            $posisi->update([
                'position_id' => $request->position_id,
                'pos_title' => $request->pos_title,
                'superior_id' => $request->superior_id,
                'last_mode_date' => $request->last_mode_date,
                'last_mode_time' => $request->last_mode_time,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Master Posisi updated successfully',
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
        $posisi = PegawaiMasterPosisi::find($id);

        if (is_null($posisi)) {
            return response()->json([
                'success' => false,
                'message' => 'Master Posisi not found',
            ]);
        }

        try {
            $posisi->delete();
            return response()->json([
                'success' => true,
                'message' => 'Master Posisi deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
