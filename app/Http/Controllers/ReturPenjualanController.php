<?php

namespace App\Http\Controllers;

use App\Models\ReturPenjualan;
use App\Models\ItemReturPenjualan;
use App\Models\Penjualan;
use App\Models\ItemPenjualan;
use App\Models\ItemGudang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReturPenjualanController extends Controller
{
    // daftar retur penjualan
    public function index()
    {
        $returs = ReturPenjualan::with('penjualan.pelanggan', 'creator', 'updater')
            ->latest()
            ->paginate(20);

        return view('auth.penjualan.retur-penjualan.index', compact('returs'));
    }

    // form create
    public function create()
    {
        $penjualans = Penjualan::with('pelanggan')
            ->where('status_bayar', 'paid')
            ->latest()
            ->get();

        return view('auth.penjualan.retur-penjualan.create', compact('penjualans'));
    }

    // API: ambil items berdasarkan penjualan id
    public function getItemsByPenjualan($id)
    {
        try {
            $penjualan = Penjualan::with(['pelanggan', 'items.item'])->findOrFail($id);

            $items = $penjualan->items->map(function ($i) {
                return [
                    'id' => $i->id, // item_penjualan_id
                    'nama_item' => $i->item->nama_item ?? 'Item tidak ditemukan',
                    'jumlah' => $i->jumlah,
                    'harga_jual' => $i->harga_jual ?? $i->harga ?? 0,
                ];
            });

            return response()->json([
                'pelanggan' => $penjualan->pelanggan->nama_pelanggan ?? 'Tidak ada pelanggan',
                'items' => $items
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal memuat data item',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // store retur baru
    public function store(Request $request)
    {
        $request->validate([
            'penjualan_id' => 'required|exists:penjualans,id',
            'catatan'      => 'nullable|string|max:500',
            'total'        => 'required|numeric|min:0',
            'items'        => 'required|array|min:1',
            'items.*.item_penjualan_id' => 'required|exists:item_penjualans,id',
            'items.*.jumlah' => 'required|numeric|min:0.01',
        ]);

        try {
            DB::beginTransaction();

            // generate nomor -> RJ + ddmmyy + 3digit
            $tanggal = now()->format('dmy');
            $last = ReturPenjualan::whereDate('tanggal', now())->orderBy('no_retur', 'desc')->first();
            if ($last) {
                $lastNumber = (int) substr($last->no_retur, -3);
                $nextNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
            } else {
                $nextNumber = '001';
            }
            $noRetur = 'RJ' . $tanggal . $nextNumber;

            $retur = ReturPenjualan::create([
                'penjualan_id' => $request->penjualan_id,
                'no_retur' => $noRetur,
                'tanggal' => now(),
                'catatan' => $request->catatan,
                'total' => $request->total,
                'status' => 'pending',
                'created_by' => Auth::id(),
            ]);

            foreach ($request->items as $row) {
                $itemPenjualan = ItemPenjualan::findOrFail($row['item_penjualan_id']);
                $jumlah = (float) $row['jumlah'];
                $harga = (float) ($itemPenjualan->harga_jual ?? $itemPenjualan->harga ?? 0);
                $subtotal = $jumlah * $harga;

                if ($jumlah > $itemPenjualan->jumlah) {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'Validasi gagal',
                        'errors' => ['items' => ["Jumlah retur untuk item {$itemPenjualan->id} melebihi jumlah penjualan"]]
                    ], 422);
                }

                ItemReturPenjualan::create([
                    'retur_penjualan_id' => $retur->id,
                    'item_penjualan_id' => $itemPenjualan->id,
                    'jumlah' => $jumlah,
                    'harga' => $harga,
                    'sub_total' => $subtotal,
                ]);

                // update stok di gudang — barang kembali ke gudang bila retur dari pelanggan
                $itemGudang = ItemGudang::where('item_id', $itemPenjualan->item_id)
                    ->where('gudang_id', $itemPenjualan->gudang_id)
                    ->first();

                if ($itemGudang) {
                    $itemGudang->stok = $itemGudang->stok + $jumlah;
                    $itemGudang->total_stok = $itemGudang->total_stok + $jumlah;
                    $itemGudang->save();
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Retur penjualan berhasil disimpan',
                'data' => $retur
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal menyimpan retur penjualan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // show detail
    public function show($id)
    {
        $retur = ReturPenjualan::with('penjualan.pelanggan', 'items.itemPenjualan.item')->findOrFail($id);
        return view('auth.penjualan.retur-penjualan.show', compact('retur'));
    }

    // update (opsional, mirip update retur pembelian)
    public function update(Request $request, $id)
    {
        $retur = ReturPenjualan::with('items')->findOrFail($id);

        $request->validate([
            'tanggal' => 'required|date',
            'catatan' => 'nullable|string',
            'total'   => 'required|numeric|min:0',
            'status'  => 'required|in:pending,taken,refund',
            'items'   => 'required|array|min:1',
            'items.*.item_penjualan_id' => 'required|exists:item_penjualans,id',
            'items.*.jumlah' => 'required|numeric|min:0.01',
        ]);

        try {
            DB::beginTransaction();

            // rollback stok lama (kembalikan dulu)
            foreach ($retur->items as $oldItem) {
                $ip = $oldItem->itemPenjualan;
                $itemGudang = ItemGudang::where('item_id', $ip->item_id)
                    ->where('gudang_id', $ip->gudang_id)
                    ->first();

                if ($itemGudang) {
                    // sebelumnya kita menambahkan stok saat create, jadi rollback = kurangi stok
                    $itemGudang->stok = max(0, $itemGudang->stok - $oldItem->jumlah);
                    $itemGudang->total_stok = max(0, $itemGudang->total_stok - $oldItem->jumlah);
                    $itemGudang->save();
                }
            }

            // hapus detail lama
            $retur->items()->delete();

            // update header
            $retur->update([
                'tanggal' => $request->tanggal,
                'catatan' => $request->catatan,
                'total'   => $request->total,
                'status'  => $request->status,
                'updated_by' => Auth::id(),
            ]);

            // insert ulang dan update stok sesuai item baru
            foreach ($request->items as $row) {
                $itemPenjualan = ItemPenjualan::findOrFail($row['item_penjualan_id']);
                $jumlah = (float) $row['jumlah'];
                $harga = (float) ($itemPenjualan->harga_jual ?? $itemPenjualan->harga ?? 0);
                $subtotal = $jumlah * $harga;

                ItemReturPenjualan::create([
                    'retur_penjualan_id' => $retur->id,
                    'item_penjualan_id' => $itemPenjualan->id,
                    'jumlah' => $jumlah,
                    'harga' => $harga,
                    'sub_total' => $subtotal,
                ]);

                $itemGudang = ItemGudang::where('item_id', $itemPenjualan->item_id)
                    ->where('gudang_id', $itemPenjualan->gudang_id)
                    ->first();

                if ($itemGudang) {
                    $itemGudang->stok = $itemGudang->stok + $jumlah;
                    $itemGudang->total_stok = $itemGudang->total_stok + $jumlah;
                    $itemGudang->save();
                }
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Retur berhasil diperbarui',
                'data' => $retur->fresh('items')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal update retur',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // delete
    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $retur = ReturPenjualan::with('items.itemPenjualan.item')->findOrFail($id);

            if ($retur->status !== 'pending') {
                return redirect()->route('retur-penjualan.index')
                    ->with('error', 'Retur sudah diproses, tidak bisa dihapus.');
            }

            // rollback stok: karena pada create kita menambahkan stok, dihapus => kurangi kembali
            foreach ($retur->items as $ri) {
                $ip = $ri->itemPenjualan;
                $itemGudang = ItemGudang::where('item_id', $ip->item_id)
                    ->where('gudang_id', $ip->gudang_id)
                    ->first();

                if ($itemGudang) {
                    $itemGudang->stok = max(0, $itemGudang->stok - $ri->jumlah);
                    $itemGudang->total_stok = max(0, $itemGudang->total_stok - $ri->jumlah);
                    $itemGudang->save();
                }
            }

            $retur->items()->delete();
            $retur->delete();

            DB::commit();

            return redirect()->route('retur-penjualan.index')->with('success', 'Retur berhasil dihapus.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('retur-penjualan.index')->with('error', 'Gagal menghapus retur: ' . $e->getMessage());
        }
    }
}
