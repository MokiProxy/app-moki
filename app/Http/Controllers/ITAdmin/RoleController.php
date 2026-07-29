<?php

namespace App\Http\Controllers\ITAdmin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Yajra\DataTables\Facades\DataTables;
use Exception;

class RoleController extends Controller
{
    public function index()
    {
        return view('it-admin.roles.index');
    }

    public function datatable(Request $request)
    {
        $roles = Role::query();

        return DataTables::eloquent($roles)
            ->addIndexColumn()
            ->addColumn('user_count', function ($row) {
                return User::role($row->name)->count();
            })
            ->addColumn('created_at_formatted', function ($row) {
                return $row->created_at ? $row->created_at->format('d M Y H:i') : '-';
            })
            ->addColumn('action', function ($row) {
                $id = $row->id;
                $canDelete = User::role($row->name)->count() === 0;
                $deleteAttr = $canDelete
                    ? 'class="btn btn-sm btn-danger btn-delete" data-id="' . $id . '"'
                    : 'class="btn btn-sm btn-danger btn-delete disabled" data-id="' . $id . '" disabled';

                return '<div class="d-flex gap-1 justify-content-center">
                    <button class="btn btn-sm btn-success btn-permissions" data-id="' . $id . '" title="Atur Permission">
                        <i class="mdi mdi-shield-key"></i>
                    </button>
                    <button class="btn btn-sm btn-primary btn-edit" data-id="' . $id . '" data-name="' . e($row->name) . '" title="Edit">
                        <i class="mdi mdi-pencil"></i>
                    </button>
                    <button ' . $deleteAttr . ' title="Hapus">
                        <i class="mdi mdi-delete"></i>
                    </button>
                </div>';
            })
            ->rawColumns(['action', 'user_count'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'       => 'required|unique:roles,name',
            'guard_name' => 'nullable|string',
        ]);

        try {
            Role::create([
                'name'       => $request->name,
                'guard_name' => $request->guard_name ?? 'web',
            ]);

            return response()->json(['success' => true, 'message' => 'Role berhasil ditambahkan!']);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function edit($id)
    {
        $role = Role::find($id);
        if ($role) {
            return response()->json([
                'success' => true,
                'data'    => [
                    'id'         => $role->id,
                    'name'       => $role->name,
                    'guard_name' => $role->guard_name,
                ]
            ]);
        }
        return response()->json(['success' => false], 404);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|unique:roles,name,' . $id,
        ]);

        try {
            $role = Role::find($id);
            if (!$role) {
                return response()->json(['success' => false, 'message' => 'Role tidak ditemukan'], 404);
            }

            $role->update([
                'name' => $request->name,
            ]);

            return response()->json(['success' => true, 'message' => 'Role berhasil diperbarui!']);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $role = Role::find($id);
            if (!$role) {
                return response()->json(['success' => false, 'message' => 'Role tidak ditemukan'], 404);
            }

            if (User::role($role->name)->count() > 0) {
                return response()->json(['success' => false, 'message' => 'Tidak dapat menghapus role yang masih memiliki user!'], 400);
            }

            $role->delete();
            return response()->json(['success' => true, 'message' => 'Role berhasil dihapus!']);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function permissions($id)
    {
        $role = Role::with('permissions')->findOrFail($id);
        $allPermissions = Permission::orderBy('name')->get();
        $rolePermissionNames = $role->permissions->pluck('name')->toArray();

        return response()->json([
            'success' => true,
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
            ],
            'permissions' => $allPermissions,
            'role_permission_names' => $rolePermissionNames,
        ]);
    }

    public function syncPermissions(Request $request, $id)
    {
        $request->validate([
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,name',
        ]);

        $role = Role::findOrFail($id);
        $role->syncPermissions($request->permissions ?? []);

        return response()->json([
            'success' => true,
            'message' => 'Permission berhasil disinkronkan!',
        ]);
    }
}
