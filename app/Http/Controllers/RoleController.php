<?php

namespace App\Http\Controllers;

use App\Models\LogActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $roles = Role::with(['permissions', 'users'])->get();
        return view('auth.pengguna.role.index', compact('roles'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Ambil semua permissions
        $permissions = Permission::all();

        return view('auth.pengguna.role.create', compact('permissions'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'guard_name' => 'required|string|in:web,api',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        try {
            DB::beginTransaction();

            $role = Role::create([
                'name' => $request->name,
                'guard_name' => $request->guard_name ?? 'web',
            ]);

            if ($request->has('permissions')) {
                $permissions = Permission::whereIn('id', $request->permissions)->get();
                $role->syncPermissions($permissions);
            }

            DB::commit();

            // ✅ Return JSON kalau request dari AJAX
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Role berhasil ditambahkan!'
                ]);
            }

            LogActivity::create([
                'user_id'       => Auth::id(),
                'activity_type' => 'create_role',
                'description'   => 'Created role: ' . $role->name,
                'ip_address'    => $request->ip(),
                'user_agent'    => $request->userAgent(),
            ]);

            return redirect()->route('roles.index')
                ->with('success', 'Role berhasil ditambahkan!');
        } catch (\Exception $e) {
            DB::rollBack();

            // ✅ Return JSON error kalau request dari AJAX
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal menambahkan role: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                ->withInput()
                ->with('error', 'Gagal menambahkan role: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Role $role)
    {
        // Load relasi permissions dan users
        $role->load(['permissions', 'users']);

        // Group permissions by module untuk tampilan yang lebih rapi
        $permissions = $role->permissions->groupBy(function ($permission) {
            $parts = explode('.', $permission->name);
            return $parts[0] ?? 'other';
        });

        return view('auth.pengguna.role.show', compact('role', 'permissions'));
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Role $role)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
            'guard_name' => 'required|string|in:web,api',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        try {
            DB::beginTransaction();

            $role->update([
                'name' => $request->name,
                'guard_name' => $request->guard_name,
            ]);

            if ($request->has('permissions')) {
                $permissions = Permission::whereIn('id', $request->permissions)->get();
                $role->syncPermissions($permissions);
            } else {
                $role->syncPermissions([]);
            }

            LogActivity::create([
                'user_id'       => Auth::id(),
                'activity_type' => 'update_role',
                'description'   => 'Updated role: ' . $role->name,
                'ip_address'    => $request->ip(),
                'user_agent'    => $request->userAgent(),
            ]);

            DB::commit();

            // ✅ Return JSON kalau request dari AJAX/fetch
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Role berhasil diperbarui!'
                ]);
            }

            return redirect()->route('roles.index')
                ->with('success', 'Role berhasil diperbarui!');
        } catch (\Exception $e) {
            DB::rollBack();

            // ✅ Return JSON error kalau request dari AJAX
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal memperbarui role: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                ->withInput()
                ->with('error', 'Gagal memperbarui role: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Role $role)
    {
        try {
            // Check if role is assigned to any users
            if ($role->users()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Role tidak dapat dihapus karena masih digunakan oleh ' . $role->users()->count() . ' user.'
                ], 422);
            }

            // Prevent deleting super-admin role
            if ($role->name === 'super-admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Role super-admin tidak dapat dihapus.'
                ], 422);
            }

            $role->delete();

            LogActivity::create([
                'user_id'       => Auth::id(),
                'activity_type' => 'delete_role',
                'description'   => 'Deleted role: ' . $role->name,
                'ip_address'    => request()->ip(),
                'user_agent'    => request()->userAgent(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Role berhasil dihapus!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus role: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Assign permissions to role (optional custom method)
     */
    public function assignPermissions(Request $request, Role $role)
    {
        $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        try {
            $permissions = Permission::whereIn('id', $request->permissions)->get();
            $role->syncPermissions($permissions);

            LogActivity::create([
                'user_id'       => Auth::id(),
                'activity_type' => 'assign_permissions',
                'description'   => 'Assigned permissions to role: ' . $role->name,
                'ip_address'    => $request->ip(),
                'user_agent'    => $request->userAgent(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Permissions berhasil di-assign ke role ' . $role->name
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal assign permissions: ' . $e->getMessage()
            ], 500);
        }
    }
}
