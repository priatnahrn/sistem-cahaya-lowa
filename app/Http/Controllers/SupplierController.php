<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SupplierController extends Controller
{
    public function index()
    {
        return view('auth.supplier.index');
    }

    public function create()
    {
        return view('auth.supplier.create');
    }

    public function store(Request $request)
    {

        $auth = Auth::user();
        if (!$auth) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        $validated = $request->validate([
            'nama_supplier' => 'required|string|max:255',
            'kontak' => 'nullable|string|max:100',
            'alamat' => 'nullable|string',
        ]);

        try{
            Supplier::create($validated);
            return redirect()->route('supplier.index')->with('success', 'Supplier berhasil dibuat.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Terjadi kesalahan saat menyimpan data.'])->withInput();
        }
    }

    public function show($id)
    {
        // Tampilkan detail supplier berdasarkan $id
        $auth = Auth::user();
        if (!$auth) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        $supplier = Supplier::find($id);
        if (!$supplier) {
            return redirect()->route('supplier.index')->withErrors(['error' => 'Supplier tidak ditemukan.']);
        }
        return view('auth.supplier.show', compact('supplier'));
    }

    public function update(Request $request, $id)
    {
        // Update supplier berdasarkan $id
        $auth = Auth::user();
        if (!$auth) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        $validated = $request->validate([
            'nama_supplier' => 'required|string|max:255',
            'kontak' => 'nullable|string|max:100',
            'alamat' => 'nullable|string',
        ]);

        try {
            $supplier = Supplier::find($id);
            if (!$supplier) {
                return redirect()->route('supplier.index')->withErrors(['error' => 'Supplier tidak ditemukan.']);
            }
            $supplier->update($validated);
            return redirect()->route('supplier.index')->with('success', 'Supplier berhasil diperbarui.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Terjadi kesalahan saat memperbarui data.'])->withInput();
        }
    }

    public function destroy($id)
    {
        // Hapus supplier berdasarkan $id
        $auth = Auth::user();
        if (!$auth) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        try {
            $supplier = Supplier::find($id);
            if (!$supplier) {
                return redirect()->route('supplier.index')->withErrors(['error' => 'Supplier tidak ditemukan.']);
            }
            $supplier->delete();
            return redirect()->route('supplier.index')->with('success', 'Supplier berhasil dihapus.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Terjadi kesalahan saat menghapus data.']);
        }
    }
}
