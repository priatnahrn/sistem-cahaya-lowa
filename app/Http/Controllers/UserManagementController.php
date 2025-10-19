<?php

namespace App\Http\Controllers;

use App\Models\LogActivity;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class UserManagementController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of users.
     */
    public function index()
    {
        // ✅ Check permission view
        $this->authorize('users.view');

        $users = User::with('roles')->orderBy('created_at', 'desc')->get();
        return view('auth.pengguna.akun.index', compact('users'));
    }

    /**
     * Show the form for creating a new user.
     */
    public function create()
    {
        // ✅ Check permission create
        $this->authorize('users.create');

        $roles = Role::all();
        return view('auth.pengguna.akun.create', compact('roles'));
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(Request $request)
    {
        // ✅ Check permission create
        $this->authorize('users.create');

        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|min:6|max:255|unique:users,username',
            'email' => 'nullable|email|unique:users,email', // ✅ Changed to nullable
            'password' => ['required', 'confirmed', Password::min(8)],
            'roles' => 'nullable|array',
            'roles.*' => 'exists:roles,id',
        ], [
            'name.required' => 'Nama wajib diisi.',
            'username.required' => 'Username wajib diisi.',
            'username.min' => 'Username minimal 6 karakter.',
            'username.unique' => 'Username sudah digunakan.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah digunakan.',
            'password.required' => 'Password wajib diisi.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'password.min' => 'Password minimal 8 karakter.',
        ]);

        try {
            DB::beginTransaction();

            $user = User::create([
                'name' => $request->name,
                'username' => $request->username,
                'email' => $request->email, // Will be null if not provided
                'password' => Hash::make($request->password),
            ]);

            if ($request->has('roles')) {
                $roles = Role::whereIn('id', $request->roles)->get();
                $user->syncRoles($roles);
            }

            LogActivity::create([
                'user_id'       => Auth::id(),
                'activity_type' => 'create_user',
                'description'   => 'Created user: ' . $user->username,
                'ip_address'    => $request->ip(),
                'user_agent'    => $request->userAgent(),
            ]);

            DB::commit();

            // ✅ Return JSON response instead of redirect
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Pengguna berhasil ditambahkan!',
                    'user' => $user
                ], 201);
            }

            return redirect()->route('users.index')
                ->with('success', 'Pengguna berhasil ditambahkan!');
        } catch (\Exception $e) {
            DB::rollBack();

            // ✅ Return JSON error response
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal menambahkan pengguna: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                ->withInput()
                ->with('error', 'Gagal menambahkan pengguna: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(string $id)
    {
        // ✅ Check permission update
        $this->authorize('users.update');

        $user = User::with('roles')->findOrFail($id);
        $roles = Role::all();
        return view('auth.pengguna.akun.show', compact('user', 'roles'));
    }
    /**
     * Update the specified user in storage.
     */
    public function update(Request $request, string $id)
    {
        // ✅ Check permission update
        $this->authorize('users.update');

        $user = User::findOrFail($id);

        // Validation rules
        $rules = [
            'name' => 'required|string|max:255',
            'username' => 'required|string|min:6|max:255|unique:users,username,' . $id,
            'email' => 'nullable|email|unique:users,email,' . $id,
            'roles' => 'nullable|array',
            'roles.*' => 'exists:roles,id',
        ];

        // Only validate password if it's provided
        if ($request->filled('password')) {
            $rules['password'] = ['required', 'confirmed', Password::min(8)];
        }

        $messages = [
            'name.required' => 'Nama wajib diisi.',
            'username.required' => 'Username wajib diisi.',
            'username.min' => 'Username minimal 6 karakter.',
            'username.unique' => 'Username sudah digunakan.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah digunakan.',
            'password.required' => 'Password wajib diisi.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'password.min' => 'Password minimal 8 karakter.',
        ];

        $request->validate($rules, $messages);

        try {
            DB::beginTransaction();

            $data = [
                'name' => $request->name,
                'username' => $request->username,
                'email' => $request->email,
            ];

            // Only update password if provided
            if ($request->filled('password')) {
                $data['password'] = Hash::make($request->password);
            }

            $user->update($data);

            if ($request->has('roles')) {
                $roles = Role::whereIn('id', $request->roles)->get();
                $user->syncRoles($roles);
            } else {
                $user->syncRoles([]);
            }

            LogActivity::create([
                'user_id'       => Auth::id(),
                'activity_type' => 'update_user',
                'description'   => 'Updated user: ' . $user->username . ($request->filled('password') ? ' (password changed)' : ''),
                'ip_address'    => $request->ip(),
                'user_agent'    => $request->userAgent(),
            ]);

            DB::commit();

            // ✅ Return JSON response for AJAX requests
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Pengguna berhasil diperbarui!',
                    'user' => $user
                ], 200);
            }

            return redirect()->route('users.index')
                ->with('success', 'Pengguna berhasil diperbarui!');
        } catch (\Exception $e) {
            DB::rollBack();

            // ✅ Return JSON error response
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal memperbarui pengguna: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                ->withInput()
                ->with('error', 'Gagal memperbarui pengguna: ' . $e->getMessage());
        }
    }
    /**
     * Remove the specified user from storage.
     */
    public function destroy(string $id)
    {
        // ✅ Check permission delete
        $this->authorize('users.delete');

        try {
            $user = User::findOrFail($id);

            // Prevent deleting current user
            if ($user->id === Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak dapat menghapus akun Anda sendiri.'
                ], 422);
            }

            $user->delete();

            LogActivity::create([
                'user_id'       => Auth::id(),
                'activity_type' => 'delete_user',
                'description'   => 'Deleted user: ' . $user->username,
                'ip_address'    => request()->ip(),
                'user_agent'    => request()->userAgent(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Pengguna berhasil dihapus!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus pengguna: ' . $e->getMessage()
            ], 500);
        }
    }
}
