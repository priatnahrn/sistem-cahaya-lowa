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
        //
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

        KategoriItem::create($validated);

        return redirect()->route('items.categories.index')->with('success', 'Kategori item berhasil dibuat.');
    }

    public function edit($id)
    {
        //
    }
}
