<?php

namespace App\Http\Controllers;

use App\Models\Gudang;
use App\Models\LogActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Log;

class GudangController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of gudang.
     */
    public function index()
    {
        // ✅ Check permission
        $this->authorize('gudang.view');

        $gudangs = Gudang::orderBy('created_at', 'desc')->get();

        return view('auth.gudang.index', compact('gudangs'));
    }

    /**
     * Show the form for creating a new gudang.
     */
    public function create()
    {
        // ✅ Check permission
        $this->authorize('gudang.create');

        // Generate kode gudang otomatis
        $lastGudang = Gudang::orderBy('id', 'desc')->first();

        $lastNumber = 0;
        if ($lastGudang && preg_match('/GD-(\d+)/', $lastGudang->kode_gudang, $matches)) {
            $lastNumber = (int) $matches[1];
        }

        $newCode = 'GD-' . str_pad($lastNumber + 1, 5, '0', STR_PAD_LEFT);

        return view('auth.gudang.create', compact('newCode'));
    }

    /**
     * Store a newly created gudang in storage.
     */
    public function store(Request $request)
    {
        // ✅ Check permission
        $this->authorize('gudang.create');

        $validated = $request->validate([
            'kode_gudang' => 'required|string|max:100|unique:gudangs,kode_gudang',
            'nama_gudang' => 'required|string|max:100',
            'lokasi' => 'nullable|string',
        ], [
            'kode_gudang.required' => 'Kode gudang wajib diisi.',
            'kode_gudang.unique' => 'Kode gudang sudah digunakan.',
            'nama_gudang.required' => 'Nama gudang wajib diisi.',
        ]);

        try {
            Gudang::create([
                'kode_gudang' => $validated['kode_gudang'],
                'nama_gudang' => $validated['nama_gudang'],
                'lokasi' => $validated['lokasi'] ?? null,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            LogActivity::create([
                'user_id'       => Auth::id(),
                'activity_type' => 'create_gudang',
                'description'   => 'Created gudang: ' . $validated['nama_gudang'] . ' (' . $validated['kode_gudang'] . ')',
                'ip_address'    => $request->ip(),
                'user_agent'    => $request->userAgent(),
            ]);

            return redirect()->route('gudang.index')
                ->with('success', 'Gudang berhasil ditambahkan.');
        } catch (\Exception $e) {
            Log::error('Error store gudang: ' . $e->getMessage());
            
            return back()
                ->withErrors(['error' => 'Terjadi kesalahan saat menyimpan data: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Display the specified gudang.
     */
    public function show($id)
    {
        // ✅ User yang bisa view bisa lihat detail
        $this->authorize('gudang.view');

        $gudang = Gudang::findOrFail($id);

        return view('auth.gudang.show', compact('gudang'));
    }

    /**
     * Update the specified gudang in storage.
     */
    public function update(Request $request, $id)
    {
        // ✅ Check permission
        $this->authorize('gudang.update');

        $gudang = Gudang::findOrFail($id);

        $validated = $request->validate([
            'kode_gudang' => 'required|string|max:100|unique:gudangs,kode_gudang,' . $id,
            'nama_gudang' => 'required|string|max:100',
            'lokasi' => 'nullable|string',
        ], [
            'kode_gudang.required' => 'Kode gudang wajib diisi.',
            'kode_gudang.unique' => 'Kode gudang sudah digunakan.',
            'nama_gudang.required' => 'Nama gudang wajib diisi.',
        ]);

        try {
            $gudang->update([
                'kode_gudang' => $validated['kode_gudang'],
                'nama_gudang' => $validated['nama_gudang'],
                'lokasi' => $validated['lokasi'] ?? null,
                'updated_by' => Auth::id(),
            ]);

            LogActivity::create([
                'user_id'       => Auth::id(),
                'activity_type' => 'update_gudang',
                'description'   => 'Updated gudang: ' . $validated['nama_gudang'] . ' (' . $validated['kode_gudang'] . ')',
                'ip_address'    => $request->ip(),
                'user_agent'    => $request->userAgent(),
            ]);

            return redirect()->route('gudang.index')
                ->with('success', 'Gudang berhasil diperbarui.');
        } catch (\Exception $e) {
            Log::error('Error update gudang: ' . $e->getMessage(), ['id' => $id]);
            
            return back()
                ->withErrors(['error' => 'Terjadi kesalahan saat memperbarui data: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Remove the specified gudang from storage.
     */
    public function destroy($id)
    {
        // ✅ Check permission
        $this->authorize('gudang.delete');

        try {
            $gudang = Gudang::findOrFail($id);

            // Check jika gudang masih digunakan di transaksi
            if ($gudang->items()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gudang tidak dapat dihapus karena masih memiliki item.'
                ], 422);
            }

            $gudangName = $gudang->nama_gudang;
            $gudangCode = $gudang->kode_gudang;

            $gudang->delete();

            LogActivity::create([
                'user_id'       => Auth::id(),
                'activity_type' => 'delete_gudang',
                'description'   => 'Deleted gudang: ' . $gudangName . ' (' . $gudangCode . ')',
                'ip_address'    => request()->ip(),
                'user_agent'    => request()->userAgent(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Gudang berhasil dihapus.'
            ]);
        } catch (\Exception $e) {
            Log::error('Error delete gudang: ' . $e->getMessage(), ['id' => $id]);
            
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menghapus data: ' . $e->getMessage()
            ], 500);
        }
    }
}