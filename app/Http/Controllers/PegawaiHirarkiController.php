<?php

namespace App\Http\Controllers;

use App\Models\PegawaiHirarki;
use App\Models\PegawaiMasterPosisi;
use App\Models\PegawaiSatker;
use App\Models\Employee;
use App\Services\HierarchyService;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Validator;

class PegawaiHirarkiController extends Controller
{
    protected $hierarchyService;

    public function __construct(HierarchyService $hierarchyService)
    {
        $this->hierarchyService = $hierarchyService;
    }

    public function index()
    {
        $positions = PegawaiMasterPosisi::all();
        $satkers = PegawaiSatker::all();
        $employees = Employee::with('user')->get();

        return view('components.pegawai-hirarki', compact('positions', 'satkers', 'employees'));
    }

    public function datatable(Request $request)
    {
        if ($request->ajax()) {
            $hirarki = PegawaiHirarki::with(['posisi', 'satker']);

            return DataTables::eloquent($hirarki)
                ->addIndexColumn()
                ->addColumn('posisi_title', function ($row) {
                    return $row->posisi ? $row->posisi->pos_title : '-';
                })
                ->addColumn('satker_nama', function ($row) {
                    return $row->satker ? $row->satker->nama_satker : '-';
                })
                ->addColumn('action', function ($row) {
                    return '<ul class="list-unstyled hstack gap-1 mb-0">
                                <li data-bs-toggle="tooltip" data-bs-placement="top" title="View">
                                    <button class="btn btn-sm btn-soft-primary btn-view" data-id="' . $row->id . '"><i class="mdi mdi-eye-outline mdi-18px"></i></button>
                                </li>
                                <li data-bs-toggle="tooltip" data-bs-placement="top" title="Edit">
                                    <button class="btn btn-sm btn-soft-info btn-edit" data-id="' . $row->id . '"><i class="mdi mdi-pencil-outline mdi-18px"></i></button>
                                </li>
                                <li data-bs-toggle="tooltip" data-bs-placement="top" title="Delete">
                                    <button data-id="' . $row->id . '" class="btn btn-sm btn-soft-danger btn-delete"><i class="mdi mdi-delete-outline mdi-18px"></i></button>
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
            'position_id' => 'required|exists:pegawai_master_posisi,position_id',
            'kode_satker' => 'required|exists:pegawai_satker,kode_satker',
            'employee_id' => 'nullable',
            'nopeg' => 'nullable',
            'nama' => 'nullable',
            'email' => 'nullable|email',
            'jabatan0' => 'nullable',
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
            $employee = PegawaiHirarki::create($request->only([
                'position_id', 'employee_id', 'nopeg', 'nama', 'email', 'jabatan0', 'kode_satker',
            ]));

            $this->hierarchyService->buildEmployeeHierarchy($employee);

            return response()->json([
                'success' => true,
                'message' => 'Pegawai Hirarki created successfully',
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
        $hirarki = PegawaiHirarki::find($id);

        if (is_null($hirarki)) {
            return response()->json([
                'success' => false,
                'message' => 'Pegawai Hirarki not found',
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Pegawai Hirarki retrieved successfully',
            'data' => $hirarki
        ]);
    }

    public function hierarchy($id)
    {
        $hirarki = PegawaiHirarki::with(['posisi', 'satker'])->find($id);

        if (is_null($hirarki)) {
            return response()->json([
                'success' => false,
                'message' => 'Pegawai Hirarki not found',
            ]);
        }

        $employee = [
            'position_id' => $hirarki->position_id,
            'pos_title'   => $hirarki->posisi->pos_title ?? '-',
            'nama'        => $hirarki->nama ?? '-',
            'jabatan0'    => $hirarki->jabatan0 ?? '-',
            'email'       => $hirarki->email ?? '-',
            'kode_satker' => $hirarki->kode_satker ?? '-',
            'nama_satker' => $hirarki->satker->nama_satker ?? '-',
        ];

        $superiors = [];
        for ($i = 1; $i <= 8; $i++) {
            $posId = $hirarki->{"superior_{$i}"};
            if (!$posId) break;

            $posisi = PegawaiMasterPosisi::find($posId);
            $superiorHirarki = PegawaiHirarki::where('position_id', $posId)->first();

            $superiors[] = [
                'level'      => $i,
                'position_id'=> $posId,
                'pos_title'  => $posisi->pos_title ?? '-',
                'nama'       => $superiorHirarki->nama ?? '-',
                'jabatan'    => $superiorHirarki->jabatan0 ?? '-',
                'email'      => $superiorHirarki->email ?? '-',
            ];
        }

        return response()->json([
            'success' => true,
            'data'    => ['employee' => $employee, 'superiors' => $superiors]
        ]);
    }

    public function update(Request $request, $id)
    {
        $hirarki = PegawaiHirarki::find($id);

        $validator = Validator::make($request->all(), [
            'position_id' => 'required|exists:pegawai_master_posisi,position_id',
            'kode_satker' => 'required|exists:pegawai_satker,kode_satker',
            'employee_id' => 'nullable',
            'nopeg' => 'nullable',
            'nama' => 'nullable',
            'email' => 'nullable|email',
            'jabatan0' => 'nullable',
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
            $hirarki->update($request->only([
                'position_id', 'employee_id', 'nopeg', 'nama', 'email', 'jabatan0', 'kode_satker',
            ]));

            $this->hierarchyService->buildEmployeeHierarchy($hirarki);

            return response()->json([
                'success' => true,
                'message' => 'Pegawai Hirarki updated successfully',
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
        $hirarki = PegawaiHirarki::find($id);

        if (is_null($hirarki)) {
            return response()->json([
                'success' => false,
                'message' => 'Pegawai Hirarki not found',
            ]);
        }

        try {
            $hirarki->delete();
            return response()->json([
                'success' => true,
                'message' => 'Pegawai Hirarki deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
