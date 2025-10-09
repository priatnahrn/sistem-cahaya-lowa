<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pelanggan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PelangganController extends Controller
{
    public function index()
    {
        $auth = Auth::user();
        if (!$auth) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        $pelanggans = Pelanggan::all();
        return view('auth.pelanggan.index', compact('pelanggans'));
    }

    public function create()
    {

        return view('auth.pelanggan.create');
    }


    public function search(Request $request)
    {
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
        $auth = Auth::user();
        if (!$auth) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'nama_pelanggan' => 'required|string|max:255',
            'kontak' => 'nullable|string|max:100',
            'alamat' => 'nullable|string',
            'level' => 'required|in:retail,partai_kecil,grosir',
        ]);

        try {
            $pelanggan = Pelanggan::create($validated);

            // 🧩 Selalu kirim JSON kalau request dari fetch (application/json)
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
            Log::error('Gagal menyimpan pelanggan: ' . $e->getMessage());

            if ($request->expectsJson() || $request->isJson()) {
                return response()->json(['message' => 'Terjadi kesalahan saat menyimpan data.'], 500);
            }

            return back()->withErrors(['error' => 'Terjadi kesalahan saat menyimpan data.'])->withInput();
        }
    }


    public function show($id)
    {
        $auth = Auth::user();
        if (!$auth) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        $pelanggan = Pelanggan::find($id);
        if (!$pelanggan) {
            return redirect()->route('pelanggan.index')->withErrors(['error' => 'Pelanggan tidak ditemukan.']);
        }
        return view('auth.pelanggan.show', compact('pelanggan'));
    }

    public function update(Request $request, $id)
    {
        $auth = Auth::user();
        if (!$auth) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $pelanggan = Pelanggan::find($id);
        if (!$pelanggan) {
            return redirect()->route('pelanggan.index')->withErrors(['error' => 'Pelanggan tidak ditemukan.']);
        }

        $validated = $request->validate([
            'nama_pelanggan' => 'required|string|max:255',
            'kontak' => 'nullable|string|max:100',
            'alamat' => 'nullable|string',
            'level' => 'required|in:retail,partai_kecil,grosir',
        ]);

        try {
            $pelanggan->update($validated);
            return redirect()->route('pelanggan.index', $id)->with('success', 'Pelanggan berhasil diperbarui.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Terjadi kesalahan saat memperbarui data.'])->withInput();
        }
    }

    public function destroy($id)
    {
        $auth = Auth::user();
        if (!$auth) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        try {
            $pelanggan = Pelanggan::find($id);
            if (!$pelanggan) {
                return redirect()->route('pelanggan.index')->withErrors(['error' => 'Pelanggan tidak ditemukan.']);
            }
            $pelanggan->delete();
            return redirect()->route('pelanggan.index')->with('success', 'Pelanggan berhasil dihapus.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Terjadi kesalahan saat menghapus data.']);
        }
    }
}
