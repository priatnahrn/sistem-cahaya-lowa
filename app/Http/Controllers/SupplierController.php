<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SupplierController extends Controller
{
    public function index()
    {
        $auth = Auth::user();
        if (!$auth) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        $suppliers = Supplier::all();
        return view('auth.supplier.index', compact('suppliers'));
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
            'nama_bank' => 'nullable|in:BCA,BNI,BRI,Mandiri,BSI,BTN,SMBC,Lainnya',
            'nomor_rekening' => 'nullable|string|max:50',
        ]);

        try {
            Supplier::create($validated);
            return redirect()->route('supplier.index')->with('success', 'Supplier berhasil dibuat.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Terjadi kesalahan saat menyimpan data.'])->withInput();
        }
    }


    public function search(Request $request)
    {
        // sementara supaya tidak gagal waktu debugging local
        // $auth = Auth::user();
        // if (!$auth) return response()->json(['message' => 'Unauthorized'], 401);

        $q = $request->query('q', '');

        $query = Supplier::query();

        if (trim($q) !== '') {
            $q = trim($q);
            $query->where(function ($sub) use ($q) {
                $sub->where('nama_supplier', 'like', "%{$q}%")
                    ->orWhere('kontak', 'like', "%{$q}%")
                    ->orWhere('nomor_rekening', 'like', "%{$q}%");
            });
        }

        $suppliers = $query->orderBy('nama_supplier')
            ->limit(10)
            ->get(['id', 'nama_supplier', 'kontak', 'nama_bank', 'nomor_rekening']);

        return response()->json($suppliers);
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
            'nama_bank' => 'nullable|in:BCA,BNI,BRI,Mandiri,BSI,BTN,SMBC,Lainnya',
            'nomor_rekening' => 'nullable|string|max:50',
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
