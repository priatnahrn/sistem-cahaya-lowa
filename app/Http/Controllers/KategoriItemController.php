<?php

namespace App\Http\Controllers;

use App\Models\KategoriItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class KategoriItemController extends Controller
{
    public function index()
    {
        $auth = Auth::user();
        if (!$auth) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $categories = KategoriItem::all();
        return view('auth.items.categories.index', compact('categories'));
    }

    public function create()
    {
        return view('auth.items.categories.create');
    }
    
    
    
    public function show($id)
    {
        $auth = Auth::user();
        if (!$auth) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        $category = KategoriItem::find($id);
        if (!$category) {
            return redirect()->route('items.categories.index')->withErrors(['error' => 'Kategori item tidak ditemukan.']);
        }
        return view('auth.items.categories.show', compact('category'));
    }
    public function store(Request $request)
    {
        
        $auth = $request->user();
        if (!$auth) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'nama_kategori' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
        ]);

        try {
            KategoriItem::create($validated);
            return redirect()->route('items.categories.index')->with('success', 'Kategori item berhasil dibuat.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Terjadi kesalahan saat menyimpan data.'])->withInput();
        }
    }

    public function update(Request $request)
    {
        // Implementasi update kategori item
        $auth = Auth::user();
        if (!$auth) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        
        try {
            // Logika update kategori item
            $category = KategoriItem::find($request->id);
            $category->update($request->all());
            return redirect()->route('items.categories.index')->with('success', 'Kategori item berhasil diperbarui.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Terjadi kesalahan saat memperbarui data.'])->withInput();
        }
        
    }
}
