<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Exception;

class UserRoleController extends Controller
{
    public function index()
    {
        $employees = Employee::orderBy('name', 'asc')->get();
        $userRoles = User::with('employee')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($user) {
                return (object) [
                    'id'         => $user->id,
                    'nip'        => $user->employee_id,
                    'name'       => $user->employee->name ?? $user->name,
                    'jabatan'    => $user->employee->jabatan ?? '-',
                    'role_name'  => $user->getRoleNames()->first() ?? 'none',
                    'email'      => $user->email,
                ];
            });

        return view('components.setting-role', compact('employees', 'userRoles'));
    }

    public function getEmployeeDetail($id)
    {
        $employee = Employee::firstWhere('employee_id', $id);
        if ($employee) {
            return response()->json(['success' => true, 'jabatan' => $employee->jabatan ?? '-']);
        }
        return response()->json(['success' => false], 404);
    }

    public function store(Request $request)
    {
        $request->validate([
            'employee_id' => $request->id ? 'nullable' : 'required',
            'role_name'   => 'required',
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

            $user->syncRoles([$request->role_name]);

            $msg = "Role berhasil ditambahkan!";
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
                'data' => (object) [
                    'id'         => $user->id,
                    'employee_id' => $user->employee_id,
                    'name'       => $user->employee->name ?? $user->name,
                    'jabatan'    => $user->employee->jabatan ?? '-',
                    'role_name'  => $user->getRoleNames()->first() ?? 'none',
                ]
            ]);
        }
        return response()->json(['success' => false], 404);
    }

    public function setPassword(Request $request)
    {
        $request->validate([
            'user_id' => 'required',
            'password' => 'required|min:6'
        ]);

        try {
            DB::beginTransaction();

            $user = User::find($request->user_id);
            if (!$user) return response()->json(['success' => false, 'message' => 'Data Role tidak ditemukan']);

            $user->password = Hash::make($request->password);
            $user->save();

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Password user berhasil diperbarui.']);

        } catch (\Exception $e) {
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
            return response()->json(['success' => true, 'message' => 'Role berhasil dihapus!']);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
