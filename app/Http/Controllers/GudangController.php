<?php

namespace App\Http\Controllers;

use App\Models\Gudang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GudangController extends Controller
{
    public function index()
    {

        $auth = Auth::user();
        if (!$auth) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $gudangs = Gudang::all();
        return view('auth.gudang.index', compact('gudangs')); 
    }

    public function create()
    {
        return view('auth.gudang.create');
    }

    public function store(Request $request)
    {
       $auth = Auth::user();
        if (!$auth) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'kode_gudang' => 'required|string|max:100',
            'nama_gudang' => 'required|string|max:100',
            'lokasi' => 'nullable|string',
        ]);

        try {
            Gudang::create($validated);
            return redirect()->route('gudang.index')->with('success', 'Gudang berhasil dibuat.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Terjadi kesalahan saat menyimpan data.'])->withInput();
        }
    }

    public function show($id)
    {
        $auth = Auth::user();
        if (!$auth) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        $gudang = Gudang::find($id);
        if (!$gudang) {
            return redirect()->route('gudang.index')->withErrors(['error' => 'Gudang tidak ditemukan.']);
        }
        return view('auth.gudang.show', compact('gudang'));
    }

    public function update(Request $request, $id)
    {
        $auth = Auth::user();
        if (!$auth) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $gudang = Gudang::find($id);
        if (!$gudang) {
            return redirect()->route('gudang.index')->withErrors(['error' => 'Gudang tidak ditemukan.']);
        }

        $validated = $request->validate([
            'kode_gudang' => 'required|string|max:100',
            'nama_gudang' => 'required|string|max:100',
            'lokasi' => 'nullable|string',
        ]);

        try {
            $gudang->update($validated);
            return redirect()->route('gudang.index')->with('success', 'Gudang berhasil diperbarui.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Terjadi kesalahan saat memperbarui data.'])->withInput();
        }
    }

    public function destroy($id){
        // Implementasi logika penghapusan gudang
        $auth = Auth::user();
        if (!$auth) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
       
        try {
            $gudang = Gudang::find($id);
            if (!$gudang) {
                return redirect()->route('gudang.index')->withErrors(['error' => 'Gudang tidak ditemukan.']);
            }
            $gudang->delete();
            return redirect()->route('gudang.index')->with('success', 'Gudang berhasil dihapus.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Terjadi kesalahan saat menghapus data.']);
        }
    }
}
