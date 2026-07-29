<?php

namespace App\Http\Controllers\ITAdmin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Yajra\DataTables\Facades\DataTables;
use Exception;

class UserController extends Controller
{
    public function index()
    {
        $employees = Employee::orderBy('name', 'asc')->get();
        $roles = Role::all();
        return view('it-admin.users.index', compact('employees', 'roles'));
    }

    public function datatable(Request $request)
    {
        $users = User::with('employee');

        return DataTables::eloquent($users)
            ->addIndexColumn()
            ->addColumn('nip', function ($row) {
                return $row->employee_id ?? '-';
            })
            ->addColumn('employee_name', function ($row) {
                return $row->employee->name ?? $row->name;
            })
            ->addColumn('jabatan', function ($row) {
                return $row->employee->jabatan ?? '-';
            })
            ->addColumn('role_name', function ($row) {
                $roles = $row->getRoleNames();
                $badges = [
                    'super-admin' => 'bg-danger',
                    'admin'       => 'bg-success',
                    'approver'    => 'bg-warning text-dark',
                    'staff'       => 'bg-info text-dark',
                    'teknisi'     => 'bg-primary',
                ];
                $html = '';
                foreach ($roles as $role) {
                    $class = $badges[$role] ?? 'bg-secondary';
                    $html .= '<span class="badge ' . $class . ' me-1">' . ucfirst($role) . '</span>';
                }
                return $html ?: '<span class="badge bg-secondary">None</span>';
            })
            ->addColumn('action', function ($row) {
                $id = $row->id;
                return '<div class="d-flex gap-1 justify-content-center">
                    <button class="btn btn-sm btn-info btn-password" data-id="' . $id . '" title="Set Password">
                        <i class="mdi mdi-key"></i>
                    </button>
                    <button class="btn btn-sm btn-primary btn-edit" data-id="' . $id . '" title="Edit">
                        <i class="mdi mdi-pencil"></i>
                    </button>
                    <button class="btn btn-sm btn-danger btn-delete" data-id="' . $id . '" title="Hapus">
                        <i class="mdi mdi-delete"></i>
                    </button>
                </div>';
            })
            ->rawColumns(['role_name', 'action'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $request->validate([
            'employee_id' => $request->id ? 'nullable' : 'required',
            'role_names'  => 'required|array',
            'role_names.*' => 'exists:roles,name',
        ]);

        try {
            $employee = Employee::firstWhere('employee_id', $request->employee_id);
            if (!$employee) {
                return response()->json(['success' => false, 'message' => 'Karyawan tidak ditemukan'], 404);
            }

            $nip = $employee->employee_id;
            $user = User::firstOrCreate(
                ['employee_id' => $nip],
                [
                    'name'     => $employee->name,
                    'email'    => $employee->email ?? ($nip . '@system.com'),
                    'password' => Hash::make('password'),
                ]
            );

            $user->syncRoles($request->role_names);

            $msg = $request->id ? "Role berhasil diperbarui!" : "User dan role berhasil ditambahkan!";
            return response()->json(['success' => true, 'message' => $msg]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function edit($id)
    {
        $user = User::with('employee')->find($id);
        if ($user) {
            return response()->json([
                'success' => true,
                'data' => [
                    'id'           => $user->id,
                    'employee_id'  => $user->employee_id,
                    'name'         => $user->employee->name ?? $user->name,
                    'jabatan'      => $user->employee->jabatan ?? '-',
                    'role_names'   => $user->getRoleNames()->toArray(),
                ]
            ]);
        }
        return response()->json(['success' => false], 404);
    }

    public function setPassword(Request $request)
    {
        $request->validate([
            'user_id'  => 'required',
            'password' => 'required|min:6',
        ]);

        try {
            DB::beginTransaction();

            $user = User::find($request->user_id);
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'User tidak ditemukan']);
            }

            $user->password = Hash::make($request->password);
            $user->save();

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Password user berhasil diperbarui.']);
        } catch (Exception $e) {
            DB::rollback();
            return response()->json(['success' => false, 'message' => 'Gagal: ' . $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $user = User::find($id);
            if ($user) {
                $user->delete();
            }
            return response()->json(['success' => true, 'message' => 'User berhasil dihapus!']);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
