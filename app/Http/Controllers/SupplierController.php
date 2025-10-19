<?php

namespace App\Http\Controllers;

use App\Models\LogActivity;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class SupplierController extends Controller
{
    use AuthorizesRequests;

    public function index()
    {
        // ✅ Check permission view
        $this->authorize('supplier.view');

        $suppliers = Supplier::all();
        return view('auth.supplier.index', compact('suppliers'));
    }

    public function create()
    {
        // ✅ Check permission create
        $this->authorize('supplier.create');

        return view('auth.supplier.create');
    }

    public function store(Request $request)
    {
        // ✅ Check permission create
        $this->authorize('supplier.create');

        $validated = $request->validate([
            'nama_supplier' => 'required|string|max:255',
            'kontak' => 'nullable|string|max:100',
            'alamat' => 'nullable|string',
            'nama_bank' => 'nullable|in:BCA,BNI,BRI,Mandiri,BSI,BTN,SMBC,Lainnya',
            'nomor_rekening' => 'nullable|string|max:50',
        ]);

        try {
            Supplier::create($validated);

            LogActivity::create([
                'user_id'       => Auth::id(),
                'activity_type' => 'create_supplier',
                'description'   => 'Created supplier: ' . $validated['nama_supplier'],
                'ip_address'    => $request->ip(),
                'user_agent'    => $request->userAgent(),
            ]);
            return redirect()->route('supplier.index')->with('success', 'Supplier berhasil dibuat.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Terjadi kesalahan saat menyimpan data.'])->withInput();
        }
    }

    public function search(Request $request)
    {
        // ✅ Check permission view
        $this->authorize('supplier.view');

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
        // ✅ Check permission view
        $this->authorize('supplier.view');

        $supplier = Supplier::find($id);
        if (!$supplier) {
            return redirect()->route('supplier.index')->withErrors(['error' => 'Supplier tidak ditemukan.']);
        }
        return view('auth.supplier.show', compact('supplier'));
    }

    public function update(Request $request, $id)
    {
        // ✅ Check permission update
        $this->authorize('supplier.update');

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
            LogActivity::create([
                'user_id'       => Auth::id(),
                'activity_type' => 'update_supplier',
                'description'   => 'Updated supplier: ' . $validated['nama_supplier'],
                'ip_address'    => $request->ip(),
                'user_agent'    => $request->userAgent(),
            ]);
            return redirect()->route('supplier.index')->with('success', 'Supplier berhasil diperbarui.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Terjadi kesalahan saat memperbarui data.'])->withInput();
        }
    }

    public function destroy($id)
    {
        // ✅ Check permission delete
        $this->authorize('supplier.delete');

        try {
            $supplier = Supplier::find($id);
            if (!$supplier) {
                return redirect()->route('supplier.index')->withErrors(['error' => 'Supplier tidak ditemukan.']);
            }
            $supplier->delete();
            LogActivity::create([
                'user_id'       => Auth::id(),
                'activity_type' => 'delete_supplier',
                'description'   => 'Deleted supplier: ' . $supplier->nama_supplier,
                'ip_address'    => request()->ip(),
                'user_agent'    => request()->userAgent(),
            ]);
            return redirect()->route('supplier.index')->with('success', 'Supplier berhasil dihapus.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Terjadi kesalahan saat menghapus data.']);
        }
    }
}