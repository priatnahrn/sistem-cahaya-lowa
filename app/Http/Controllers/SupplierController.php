<?php

namespace App\Http\Controllers;

use App\Models\LogActivity;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class SupplierController extends Controller
{
    use AuthorizesRequests;

    public function index()
    {
        // ✅ Check permission view
        $this->authorize('supplier.view');

        $suppliers = Supplier::orderBy('nama_supplier')->get();
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
            'atas_nama' => 'nullable|string|max:255',
        ]);

        try {
            Supplier::create([
                'nama_supplier' => $validated['nama_supplier'],
                'kontak' => $validated['kontak'] ?? null,
                'alamat' => $validated['alamat'] ?? null,
                'nama_bank' => $validated['nama_bank'] ?? null,
                'nomor_rekening' => $validated['nomor_rekening'] ?? null,
                'atas_nama' => $validated['atas_nama'] ?? null,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            LogActivity::create([
                'user_id'       => Auth::id(),
                'activity_type' => 'create_supplier',
                'description'   => 'Created supplier: ' . $validated['nama_supplier'],
                'ip_address'    => $request->ip(),
                'user_agent'    => $request->userAgent(),
            ]);

            return redirect()->route('supplier.index')
                ->with('success', 'Supplier berhasil dibuat.');
        } catch (\Exception $e) {
            Log::error('Error store supplier: ' . $e->getMessage());
            
            return back()
                ->withErrors(['error' => 'Terjadi kesalahan saat menyimpan data: ' . $e->getMessage()])
                ->withInput();
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
            ->get(['id', 'nama_supplier', 'kontak', 'nama_bank', 'nomor_rekening', 'atas_nama']);

        return response()->json($suppliers);
    }

    public function show($id)
    {
        // ✅ Check permission view
        $this->authorize('supplier.view');

        $supplier = Supplier::findOrFail($id);
        
        return view('auth.supplier.show', compact('supplier'));
    }

    public function update(Request $request, $id)
    {
        // ✅ Check permission update
        $this->authorize('supplier.update');

        $supplier = Supplier::findOrFail($id);

        $validated = $request->validate([
            'nama_supplier' => 'required|string|max:255',
            'kontak' => 'nullable|string|max:100',
            'alamat' => 'nullable|string',
            'nama_bank' => 'nullable|in:BCA,BNI,BRI,Mandiri,BSI,BTN,SMBC,Lainnya',
            'nomor_rekening' => 'nullable|string|max:50',
            'atas_nama' => 'nullable|string|max:255',
        ]);

        try {
            $supplier->update([
                'nama_supplier' => $validated['nama_supplier'],
                'kontak' => $validated['kontak'] ?? null,
                'alamat' => $validated['alamat'] ?? null,
                'nama_bank' => $validated['nama_bank'] ?? null,
                'nomor_rekening' => $validated['nomor_rekening'] ?? null,
                'atas_nama' => $validated['atas_nama'] ?? null,
                'updated_by' => Auth::id(),
            ]);

            LogActivity::create([
                'user_id'       => Auth::id(),
                'activity_type' => 'update_supplier',
                'description'   => 'Updated supplier: ' . $validated['nama_supplier'],
                'ip_address'    => $request->ip(),
                'user_agent'    => $request->userAgent(),
            ]);

            return redirect()->route('supplier.index')
                ->with('success', 'Supplier berhasil diperbarui.');
        } catch (\Exception $e) {
            Log::error('Error update supplier: ' . $e->getMessage(), ['id' => $id]);
            
            return back()
                ->withErrors(['error' => 'Terjadi kesalahan saat memperbarui data: ' . $e->getMessage()])
                ->withInput();
        }
    }

    public function destroy($id)
    {
        // ✅ Check permission delete
        $this->authorize('supplier.delete');

        try {
            $supplier = Supplier::findOrFail($id);

            // Optional: Check jika supplier masih digunakan di transaksi pembelian
            // if ($supplier->pembelians()->count() > 0) {
            //     return response()->json([
            //         'success' => false,
            //         'message' => 'Supplier tidak dapat dihapus karena masih memiliki transaksi pembelian.'
            //     ], 422);
            // }

            $supplierName = $supplier->nama_supplier;
            
            $supplier->delete();

            LogActivity::create([
                'user_id'       => Auth::id(),
                'activity_type' => 'delete_supplier',
                'description'   => 'Deleted supplier: ' . $supplierName,
                'ip_address'    => request()->ip(),
                'user_agent'    => request()->userAgent(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Supplier berhasil dihapus.'
            ]);
        } catch (\Exception $e) {
            Log::error('Error delete supplier: ' . $e->getMessage(), ['id' => $id]);
            
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menghapus data: ' . $e->getMessage()
            ], 500);
        }
    }
}