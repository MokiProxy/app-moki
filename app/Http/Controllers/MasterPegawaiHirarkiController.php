<?php

namespace App\Http\Controllers;

use App\Models\MasterPegawaiHirarki;
use App\Models\PegawaiMasterPosisi;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Validator;

class MasterPegawaiHirarkiController extends Controller
{
    public function index()
    {
        $positions = PegawaiMasterPosisi::all();

        return view('components.master-hirarki', compact('positions'));
    }

    public function datatable(Request $request)
    {
        if ($request->ajax()) {
            $hirarki = MasterPegawaiHirarki::with('position');

            return DataTables::eloquent($hirarki)
                ->addIndexColumn()
                ->addColumn('posisi_title', function ($row) {
                    return $row->position ? $row->position->pos_title : '-';
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
            'position_id' => 'required|unique:master_pegawai_hirarki,position_id|exists:pegawai_master_posisi,position_id',
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
            $data = ['position_id' => $request->position_id];

            for ($i = 1; $i <= 8; $i++) {
                $val = $request->input("superior_{$i}");
                $data["superior_{$i}"] = !empty($val) ? $val : null;
            }

            MasterPegawaiHirarki::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Master Hirarki created successfully',
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
        $hirarki = MasterPegawaiHirarki::find($id);

        if (is_null($hirarki)) {
            return response()->json([
                'success' => false,
                'message' => 'Master Hirarki not found',
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Master Hirarki retrieved successfully',
            'data' => $hirarki
        ]);
    }

    public function update(Request $request, $id)
    {
        $hirarki = MasterPegawaiHirarki::find($id);

        $validator = Validator::make($request->all(), [
            'position_id' => 'required|unique:master_pegawai_hirarki,position_id,' . $id . ',position_id|exists:pegawai_master_posisi,position_id',
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
            $data = ['position_id' => $request->position_id];

            for ($i = 1; $i <= 8; $i++) {
                $val = $request->input("superior_{$i}");
                $data["superior_{$i}"] = !empty($val) ? $val : null;
            }

            $hirarki->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Master Hirarki updated successfully',
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
        $hirarki = MasterPegawaiHirarki::find($id);

        if (is_null($hirarki)) {
            return response()->json([
                'success' => false,
                'message' => 'Master Hirarki not found',
            ]);
        }

        try {
            $hirarki->delete();
            return response()->json([
                'success' => true,
                'message' => 'Master Hirarki deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
