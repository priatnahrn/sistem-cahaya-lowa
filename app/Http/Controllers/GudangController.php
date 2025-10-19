<?php

namespace App\Http\Controllers;

use App\Models\Gudang;
use App\Models\LogActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GudangController extends Controller
{
    /**
     * Display a listing of gudang.
     */
    public function index()
    {
        // Middleware sudah handle auth & permission, tidak perlu manual check lagi
        $gudangs = Gudang::orderBy('created_at', 'desc')->get();

        return view('auth.gudang.index', compact('gudangs'));
    }

    /**
     * Show the form for creating a new gudang.
     */
    public function create()
    {
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
        $validated = $request->validate([
            'kode_gudang' => 'required|string|max:100|unique:gudangs,kode_gudang,',
            'nama_gudang' => 'required|string|max:100',
            'lokasi' => 'nullable|string',
        ], [
            'kode_gudang.required' => 'Kode gudang wajib diisi.',
            'kode_gudang.unique' => 'Kode gudang sudah digunakan.',
            'nama_gudang.required' => 'Nama gudang wajib diisi.',
        ]);


        try {
            Gudang::create($validated);

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
        $gudang = Gudang::findOrFail($id);

        return view('auth.gudang.show', compact('gudang'));
    }

    /**
     * Update the specified gudang in storage.
     */
    public function update(Request $request, $id)
    {
        $gudang = Gudang::findOrFail($id);

        $validated = $request->validate([
            'kode_gudang' => 'required|string|max:100|unique:gudang,kode_gudang,' . $id,
            'nama_gudang' => 'required|string|max:100',
            'lokasi' => 'nullable|string',
        ], [
            'kode_gudang.required' => 'Kode gudang wajib diisi.',
            'kode_gudang.unique' => 'Kode gudang sudah digunakan.',
            'nama_gudang.required' => 'Nama gudang wajib diisi.',
        ]);

        try {
            $gudang->update($validated);

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
        try {
            $gudang = Gudang::findOrFail($id);

            // Optional: Check jika gudang masih digunakan di transaksi
            if ($gudang->items()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gudang tidak dapat dihapus karena masih memiliki item.'
                ], 422);
            }

            $gudang->delete();

            LogActivity::create([
                'user_id'       => Auth::id(),
                'activity_type' => 'delete_gudang',
                'description'   => 'Deleted gudang: ' . $gudang->nama_gudang . ' (' . $gudang->kode_gudang . ')',
                'ip_address'    => request()->ip(),
                'user_agent'    => request()->userAgent(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Gudang berhasil dihapus.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menghapus data: ' . $e->getMessage()
            ], 500);
        }
    }
}
