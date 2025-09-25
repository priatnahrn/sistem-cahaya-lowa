<?php

namespace App\Http\Controllers;

use App\Models\Penjualan;
use Illuminate\Http\Request;

class PenjualanController extends Controller
{
    //
    public function index()
    {
        $penjualans = Penjualan::all();
        return view('auth.penjualan.index', compact('penjualans'));
    }

    public function create()
    {
        return view('auth.penjualan.create');
    }
}
