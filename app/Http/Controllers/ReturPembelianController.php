<?php

namespace App\Http\Controllers;

use App\Models\ItemGudang;
use App\Models\ReturPembelian;
use App\Models\ItemReturPembelian;
use App\Models\Pembelian;
use App\Models\ItemPembelian;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReturPembelianController extends Controller
{
    // 📌 list retur pembelian
    public function index()
    {
        $returs = ReturPembelian::with('pembelian.supplier', 'creator', 'updater')
            ->latest()
            ->paginate(20);

        return view('auth.pembelian.retur-pembelian.index', compact('returs'));
    }

    // 📌 form tambah retur
    public function create()
    {
        // load pembelian yang sudah paid
        $pembelians = Pembelian::with('supplier')
            ->where('status', 'paid')
            ->latest()
            ->get();

        return view('auth.pembelian.retur-pembelian.create', compact('pembelians'));
    }

    // 📌 API untuk load items berdasarkan pembelian_id (dipanggil dari Alpine.js)
    public function getItemsByPembelian($id)
    {
        try {
            $pembelian = Pembelian::with(['supplier', 'items.item'])
                ->findOrFail($id);

            $items = $pembelian->items->map(function ($item) {
                return [
                    'id' => $item->id, // item_pembelian_id
                    'nama_item' => $item->item->nama_item ?? 'Item tidak ditemukan',
                    'jumlah' => $item->jumlah, // jumlah yang dibeli
                    'harga_beli' => $item->harga_beli,
                ];
            });

            return response()->json([
                'supplier' => $pembelian->supplier->nama_supplier ?? 'Tidak ada supplier',
                'items' => $items
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal memuat data item',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // 📌 simpan retur baru
    public function store(Request $request)
    {
        $request->validate([
            'pembelian_id' => 'required|exists:pembelians,id',
            'catatan'      => 'nullable|string|max:500',
            'total'        => 'required|numeric|min:0',
            'items'        => 'required|array|min:1',
            'items.*.item_pembelian_id' => 'required|exists:item_pembelians,id',
            'items.*.jumlah' => 'required|numeric|min:0.01',
        ]);

        try {
            DB::beginTransaction();

            // Format tanggal: ddmmyy
            $tanggal = now()->format('dmy');

            // Cari nomor urut terakhir untuk tanggal ini
            $lastRetur = ReturPembelian::whereDate('tanggal', now()->toDateString())
                ->orderBy('no_retur', 'desc')
                ->first();

            if ($lastRetur) {
                // ambil 3 digit terakhir dari nomor
                $lastNumber = (int) substr($lastRetur->no_retur, -3);
                $nextNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
            } else {
                $nextNumber = '001';
            }

            $nomor = 'RB' . $tanggal . $nextNumber;

            // Simpan retur pembelian
            $retur = ReturPembelian::create([
                'pembelian_id' => $request->pembelian_id,
                'no_retur'  => $nomor,
                'tanggal'      => now(),
                'catatan'      => $request->catatan,
                'total'        => $request->total,
                'status'       => 'pending',
                'created_by'   => Auth::id(),
            ]);

            // Simpan detail item retur
            foreach ($request->items as $row) {
                $itemPembelian = ItemPembelian::findOrFail($row['item_pembelian_id']);
                $jumlah = (float) $row['jumlah'];
                $harga = (float) $itemPembelian->harga_beli;
                $subtotal = $jumlah * $harga;

                if ($jumlah > $itemPembelian->jumlah) {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'Validasi gagal',
                        'errors' => [
                            'items' => ["Jumlah retur untuk item {$itemPembelian->item->nama_item} melebihi jumlah pembelian"]
                        ]
                    ], 422);
                }

                ItemReturPembelian::create([
                    'retur_pembelian_id' => $retur->id,
                    'item_pembelian_id'  => $itemPembelian->id,
                    'jumlah'             => $jumlah,
                    'harga_beli'         => $harga,
                    'sub_total'           => $subtotal,
                ]);

                // Kurangi stok di item_gudangs (bukan di items)
                $itemGudang = ItemGudang::where('item_id', $itemPembelian->item_id)
                    ->where('gudang_id', $itemPembelian->gudang_id) // sesuaikan jika ada relasi ke gudang
                    ->first();

                if ($itemGudang) {
                    $itemGudang->stok = max(0, $itemGudang->stok - $jumlah);
                    $itemGudang->total_stok = max(0, $itemGudang->total_stok - $jumlah); // kalau mau ikut update
                    $itemGudang->save();
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Retur pembelian berhasil disimpan',
                'data' => $retur
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Gagal menyimpan retur pembelian',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    // 📌 detail retur
    public function show($id)
    {
        $retur = ReturPembelian::with('pembelian.supplier', 'items.itemPembelian.item')->findOrFail($id);
        return view('auth.pembelian.retur-pembelian.show', compact('retur'));
    }



    // 📌 update retur
    public function update(Request $request, $id)
    {
        $retur = ReturPembelian::with('items')->findOrFail($id);

        $request->validate([
            'tanggal' => 'required|date',
            'catatan' => 'nullable|string',
            'total'   => 'required|numeric|min:0',
            'status'  => 'required|in:pending,taken,refund', 
            'items'   => 'required|array|min:1',
            'items.*.item_pembelian_id' => 'required|exists:item_pembelians,id',
            'items.*.jumlah' => 'required|numeric|min:0.01',
        ]);

        try {
            DB::beginTransaction();

            // 🔹 update header + status
            $retur->update([
                'tanggal'    => $request->tanggal,
                'catatan'    => $request->catatan,
                'total'      => $request->total,
                'status'     => $request->status,  // 👈 simpan status
                'updated_by' => Auth::id(),
            ]);

            // 🔹 rollback stok lama
            foreach ($retur->items as $oldItem) {
                $itemGudang = ItemGudang::where('item_id', $oldItem->itemPembelian->item_id)
                    ->where('gudang_id', $oldItem->itemPembelian->gudang_id)
                    ->first();

                if ($itemGudang) {
                    $itemGudang->stok += $oldItem->jumlah;
                    $itemGudang->total_stok += $oldItem->jumlah;
                    $itemGudang->save();
                }
            }

            // 🔹 hapus items lama
            $retur->items()->delete();

            // 🔹 insert ulang
            foreach ($request->items as $row) {
                $itemPembelian = ItemPembelian::findOrFail($row['item_pembelian_id']);
                $jumlah   = (float) $row['jumlah'];
                $harga    = (float) $itemPembelian->harga_beli;
                $subtotal = $jumlah * $harga;

                if ($jumlah > $itemPembelian->jumlah) {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'Validasi gagal',
                        'errors'  => [
                            'items' => ["Jumlah retur untuk item {$itemPembelian->item->nama_item} melebihi jumlah pembelian"]
                        ]
                    ], 422);
                }

                ItemReturPembelian::create([
                    'retur_pembelian_id' => $retur->id,
                    'item_pembelian_id'  => $itemPembelian->id,
                    'jumlah'             => $jumlah,
                    'harga_beli'         => $harga,
                    'sub_total'          => $subtotal,
                ]);

                $itemGudang = ItemGudang::where('item_id', $itemPembelian->item_id)
                    ->where('gudang_id', $itemPembelian->gudang_id)
                    ->first();

                if ($itemGudang) {
                    $itemGudang->stok = max(0, $itemGudang->stok - $jumlah);
                    $itemGudang->total_stok = max(0, $itemGudang->total_stok - $jumlah);
                    $itemGudang->save();
                }
            }

            Pembelian::where('id', $retur->pembelian_id)->update([
                'status' => 'return',
            ]);

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => 'Retur berhasil diperbarui.',
                'data'    => $retur->fresh('items')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal update retur',
                'error'   => $e->getMessage()
            ], 500);
        }
    }


    // 📌 hapus retur
    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $retur = ReturPembelian::with('items.itemPembelian.item')->findOrFail($id);

            if ($retur->status !== 'pending') {
                return redirect()->route('retur-pembelian.index')
                    ->with('error', 'Retur sudah diproses, tidak bisa dihapus.');
            }

            // Kembalikan stok item sebelum dihapus
            foreach ($retur->items as $returItem) {
                $item = $returItem->itemPembelian->item;
                if ($item) {
                    $item->stock = $item->stock + $returItem->jumlah;
                    $item->save();
                }
            }

            // Hapus items dan retur
            $retur->items()->delete();
            $retur->delete();

            DB::commit();

            return redirect()->route('retur-pembelian.index')
                ->with('success', 'Retur berhasil dihapus dan stok dikembalikan.');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->route('retur-pembelian.index')
                ->with('error', 'Gagal menghapus retur: ' . $e->getMessage());
        }
    }
}
