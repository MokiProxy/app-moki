<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Exception;

class UserRoleController extends Controller
{
    public function index()
    {
        $employees = Employee::orderBy('name', 'asc')->get();
        $userRoles = DB::table('user_roles')
        ->join('employees', 'user_roles.employee_id', '=', 'employees.employee_id')
        ->select('user_roles.*', 'employees.name', 'employees.employee_id as nip')
        ->orderBy('user_roles.created_at', 'desc')
        ->get();

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
            'role_id'     => 'required',
        ]);

        try {
            if ($request->id) {
                // Update Data
                DB::table('user_roles')->where('id', $request->id)->update([
                ]);
                $msg = "Role berhasil diupdate!";
            } else {
                // Simpan Baru
                $employee = Employee::firstWhere("employee_id", $request->employee_id);
                DB::table('user_roles')->updateOrInsert(
                    ['employee_id' => $request->employee_id],
                    [
                        'jabatan'    => $employee->jabatan ?? '-',
                        'role_id'    => $request->role_id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
                $msg = "Role berhasil ditambahkan!";
            }

            return response()->json(['success' => true, 'message' => $msg]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function edit($id)
    {
        // Join ke employees agar nama muncul di modal popup
        $role = DB::table('user_roles')
            ->join('employees', 'user_roles.employee_id', '=', 'employees.employee_id')
            ->select('user_roles.*', 'employees.name')
            ->where('user_roles.id', $id)
            ->first();

        if ($role) {
            return response()->json(['success' => true, 'data' => $role]);
        }
        return response()->json(['success' => false], 404);
    }

    public function setPassword(Request $request)
    {
        $request->validate([
            'user_role_id' => 'required',
            'password' => 'required|min:6'
        ]);

        try {
            DB::beginTransaction();

            $roleData = DB::table('user_roles')->where('id', $request->user_role_id)->first();
            if (!$roleData) return response()->json(['success' => false, 'message' => 'Data Role tidak ditemukan']);

            // $roleData->employee_id di sini adalah PK employees.id (bukan NIP)
            $emp = DB::table('employees')->where('employee_id', $roleData->employee_id)->first();
            if (!$emp) return response()->json(['success' => false, 'message' => 'Data Employee tidak ditemukan']);

            // NIP asli dari tabel employees (mis. EMP0001) - INI yang dipakai untuk login
            $nip = $emp->employee_id;

            // Cari user existing berdasarkan NIP (bukan PK employees.id lagi)
            $user = DB::table('users')->where('employee_id', $nip)->first();
            // Pastikan NIP unik sebagai login (kecuali milik user yang sedang di-update sendiri)
            $nipTaken = DB::table('users')
                ->where('employee_id', $nip)
                ->when($user, fn ($q) => $q->where('id', '!=', $user->id))
                ->exists();

            if ($nipTaken && !$user) {
                DB::rollback();
                return response()->json(['success' => false, 'message' => "NIP {$nip} sudah dipakai user lain."], 422);
            }

            if ($user) {
                DB::table('users')->where('employee_id', $user->id)->update([
                    'password'   => Hash::make($request->password),
                    'role_id'    => $roleData->role_id,
                    'updated_at' => now()
                ]);
                $msg = "Password user berhasil diperbarui.";
            } else {
                DB::table('users')->insert([
                    'name'        => $emp->name,
                    'email'       => $emp->email ?? ($nip . '@system.com'),
                    'password'    => Hash::make($request->password),
                    'employee_id' => $nip, // NIP string, dipakai untuk login
                    'role_id'     => $roleData->role_id,
                    'created_at'  => now(),
                    'updated_at'  => now()
                ]);
                $msg = "User baru berhasil dibuat di sistem login.";
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => $msg]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['success' => false, 'message' => 'Gagal: ' . $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            DB::table('user_roles')->where('id', $id)->delete();
            return response()->json(['success' => true, 'message' => 'Role berhasil dihapus!']);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
