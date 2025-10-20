<?php

namespace App\Http\Controllers;

use App\Models\LogActivity;
use Illuminate\Http\Request;
use App\Models\Pelanggan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class PelangganController extends Controller
{
    use AuthorizesRequests;

    public function index()
    {
        // ✅ Check permission view
        $this->authorize('pelanggan.view');

        $pelanggans = Pelanggan::orderBy('nama_pelanggan')->get();
        return view('auth.pelanggan.index', compact('pelanggans'));
    }

    public function create()
    {
        // ✅ Check permission create
        $this->authorize('pelanggan.create');

        return view('auth.pelanggan.create');
    }

    /**
     * ✅ Search endpoint - minimal permission view untuk search
     */
    public function search(Request $request)
    {
        // ✅ Check permission view (user harus bisa view untuk search)
        $this->authorize('pelanggan.view');

        $q = $request->query('q', '');
        if (strlen($q) < 2) {
            return response()->json([]);
        }

        $results = Pelanggan::query()
            ->where('nama_pelanggan', 'like', "%{$q}%")
            ->orWhere('kontak', 'like', "%{$q}%")
            ->orderBy('nama_pelanggan')
            ->limit(15)
            ->get(['id', 'nama_pelanggan', 'kontak', 'level']);

        return response()->json($results);
    }

    public function store(Request $request)
    {
        // ✅ Check permission create
        $this->authorize('pelanggan.create');

        $validated = $request->validate([
            'nama_pelanggan' => 'required|string|max:255',
            'kontak' => 'nullable|string|max:100',
            'alamat' => 'nullable|string',
            'level' => 'required|in:retail,partai_kecil,grosir',
        ]);

        try {
            $pelanggan = Pelanggan::create([
                'nama_pelanggan' => $validated['nama_pelanggan'],
                'kontak' => $validated['kontak'] ?? null,
                'alamat' => $validated['alamat'] ?? null,
                'level' => $validated['level'],
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            // ✅ Log activity untuk semua request
            LogActivity::create([
                'user_id'       => Auth::id(),
                'activity_type' => 'create_pelanggan',
                'description'   => 'Created pelanggan: ' . $pelanggan->nama_pelanggan,
                'ip_address'    => $request->ip(),
                'user_agent'    => $request->userAgent(),
            ]);

            // 🧩 Kirim JSON kalau request dari fetch (application/json)
            if ($request->expectsJson() || $request->isJson()) {
                return response()->json([
                    'id' => $pelanggan->id,
                    'nama_pelanggan' => $pelanggan->nama_pelanggan,
                    'kontak' => $pelanggan->kontak,
                    'alamat' => $pelanggan->alamat,
                    'level' => $pelanggan->level,
                ], 201);
            }

            // Fallback: request normal (via browser form)
            return redirect()->route('pelanggan.index')
                ->with('success', 'Pelanggan berhasil dibuat.');
        } catch (\Exception $e) {
            Log::error('Error store pelanggan: ' . $e->getMessage());

            if ($request->expectsJson() || $request->isJson()) {
                return response()->json([
                    'message' => 'Terjadi kesalahan saat menyimpan data: ' . $e->getMessage()
                ], 500);
            }

            return back()
                ->withErrors(['error' => 'Terjadi kesalahan saat menyimpan data: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Display the specified pelanggan.
     * ✅ Semua user bisa lihat detail (untuk read-only mode)
     */
    public function show($id)
    {
        // ✅ User dengan permission view sudah bisa akses via middleware
        $this->authorize('pelanggan.view');

        $pelanggan = Pelanggan::findOrFail($id);
        return view('auth.pelanggan.show', compact('pelanggan'));
    }

    public function update(Request $request, $id)
    {
        // ✅ Check permission update
        $this->authorize('pelanggan.update');

        $pelanggan = Pelanggan::findOrFail($id);

        $validated = $request->validate([
            'nama_pelanggan' => 'required|string|max:255',
            'kontak' => 'nullable|string|max:100',
            'alamat' => 'nullable|string',
            'level' => 'required|in:retail,partai_kecil,grosir',
        ]);

        try {
            $pelanggan->update([
                'nama_pelanggan' => $validated['nama_pelanggan'],
                'kontak' => $validated['kontak'] ?? null,
                'alamat' => $validated['alamat'] ?? null,
                'level' => $validated['level'],
                'updated_by' => Auth::id(),
            ]);

            LogActivity::create([
                'user_id'       => Auth::id(),
                'activity_type' => 'update_pelanggan',
                'description'   => 'Updated pelanggan: ' . $pelanggan->nama_pelanggan,
                'ip_address'    => $request->ip(),
                'user_agent'    => $request->userAgent(),
            ]);

            return redirect()->route('pelanggan.index')
                ->with('success', 'Pelanggan berhasil diperbarui.');
        } catch (\Exception $e) {
            Log::error('Error update pelanggan: ' . $e->getMessage(), ['id' => $id]);
            
            return back()
                ->withErrors(['error' => 'Terjadi kesalahan saat memperbarui data: ' . $e->getMessage()])
                ->withInput();
        }
    }

    public function destroy($id)
    {
        // ✅ Check permission delete
        $this->authorize('pelanggan.delete');

        try {
            $pelanggan = Pelanggan::findOrFail($id);

            // Optional: Check jika pelanggan masih memiliki transaksi
            // if ($pelanggan->penjualans()->count() > 0) {
            //     return response()->json([
            //         'success' => false,
            //         'message' => 'Pelanggan tidak dapat dihapus karena masih memiliki transaksi penjualan.'
            //     ], 422);
            // }

            $pelangganName = $pelanggan->nama_pelanggan;
            
            $pelanggan->delete();

            // ✅ Log activity sebelum return
            LogActivity::create([
                'user_id'       => Auth::id(),
                'activity_type' => 'delete_pelanggan',
                'description'   => 'Deleted pelanggan: ' . $pelangganName,
                'ip_address'    => request()->ip(),
                'user_agent'    => request()->userAgent(),
            ]);

            // ✅ Return JSON untuk AJAX request
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Pelanggan berhasil dihapus.'
                ]);
            }

            return redirect()->route('pelanggan.index')
                ->with('success', 'Pelanggan berhasil dihapus.');
        } catch (\Exception $e) {
            Log::error('Error delete pelanggan: ' . $e->getMessage(), ['id' => $id]);

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Terjadi kesalahan saat menghapus data: ' . $e->getMessage()
                ], 500);
            }

            return back()
                ->withErrors(['error' => 'Terjadi kesalahan saat menghapus data: ' . $e->getMessage()]);
        }
    }
}