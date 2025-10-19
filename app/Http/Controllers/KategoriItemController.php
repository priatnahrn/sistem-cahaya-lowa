<?php

namespace App\Http\Controllers;

use App\Models\KategoriItem;
use App\Models\LogActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;

class KategoriItemController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of kategori items.
     */
    public function index()
    {
        // ✅ Check permission view
        $this->authorize('kategori_items.view');

        $categories = KategoriItem::withCount('items')
            ->orderBy('nama_kategori')
            ->get();
        
        return view('auth.items.categories.index', compact('categories'));
    }

    /**
     * Show the form for creating a new kategori.
     */
    public function create()
    {
        // ✅ Check permission create
        $this->authorize('kategori_items.create');

        return view('auth.items.categories.create');
    }

    /**
     * Store a newly created kategori in storage.
     */
    public function store(Request $request)
    {
        // ✅ Check permission create
        $this->authorize('kategori_items.create');

        $validated = $request->validate([
            'nama_kategori' => 'required|string|max:255|unique:kategori_items,nama_kategori',
            'deskripsi' => 'nullable|string',
        ], [
            'nama_kategori.required' => 'Nama kategori wajib diisi.',
            'nama_kategori.unique' => 'Nama kategori sudah digunakan.',
        ]);

        try {
            KategoriItem::create($validated);

            LogActivity::create([
                'user_id'       => Auth::id(),
                'activity_type' => 'create_kategori_item',
                'description'   => 'Created kategori item: ' . $validated['nama_kategori'],
                'ip_address'    => $request->ip(),
                'user_agent'    => $request->userAgent(),
            ]);
            
            return redirect()->route('items.categories.index')
                ->with('success', 'Kategori item berhasil ditambahkan.');
        } catch (\Exception $e) {
            Log::error('Error store kategori: ' . $e->getMessage());
            
            return back()
                ->withErrors(['error' => 'Terjadi kesalahan saat menyimpan data: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Display the specified kategori.
     * ✅ Semua user bisa lihat detail (untuk read-only mode)
     */
    public function show($id)
    {
        // ✅ Tidak perlu authorize karena view sudah di-check di index
        // User tanpa permission update tetap bisa lihat (read-only)
        
        $category = KategoriItem::with('items')->findOrFail($id);
        
        return view('auth.items.categories.show', compact('category'));
    }

    /**
     * Update the specified kategori in storage.
     */
    public function update(Request $request, $id)
    {
        // ✅ Check permission update
        $this->authorize('kategori_items.update');

        $category = KategoriItem::findOrFail($id);

        $validated = $request->validate([
            'nama_kategori' => 'required|string|max:255|unique:kategori_items,nama_kategori,' . $id,
            'deskripsi' => 'nullable|string',
        ], [
            'nama_kategori.required' => 'Nama kategori wajib diisi.',
            'nama_kategori.unique' => 'Nama kategori sudah digunakan.',
        ]);

        try {
            $category->update($validated);

            LogActivity::create([
                'user_id'       => Auth::id(),
                'activity_type' => 'update_kategori_item',
                'description'   => 'Updated kategori item: ' . $validated['nama_kategori'],
                'ip_address'    => $request->ip(),
                'user_agent'    => $request->userAgent(),
            ]);
            
            return redirect()->route('items.categories.index')
                ->with('success', 'Kategori item berhasil diperbarui.');
        } catch (\Exception $e) {
            Log::error('Error update kategori: ' . $e->getMessage(), ['id' => $id]);
            
            return back()
                ->withErrors(['error' => 'Terjadi kesalahan saat memperbarui data: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Remove the specified kategori from storage.
     */
    public function destroy($id)
    {
        // ✅ Check permission delete
        $this->authorize('kategori_items.delete');

        try {
            $category = KategoriItem::findOrFail($id);

            // ✅ Check if category is being used by items
            if ($category->items()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kategori tidak dapat dihapus karena masih digunakan oleh ' . $category->items()->count() . ' item.'
                ], 422);
            }

            $category->delete();

            LogActivity::create([
                'user_id'       => Auth::id(),
                'activity_type' => 'delete_kategori_item',
                'description'   => 'Deleted kategori item: ' . $category->nama_kategori,
                'ip_address'    => request()->ip(),
                'user_agent'    => request()->userAgent(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Kategori item berhasil dihapus.'
            ]);
        } catch (\Exception $e) {
            Log::error('Error delete kategori: ' . $e->getMessage(), ['id' => $id]);
            
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menghapus data: ' . $e->getMessage()
            ], 500);
        }
    }
}