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
use Illuminate\Support\Facades\Log;

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
        // ✅ Ambil penjualan yang statusnya 'paid' (lunas) dengan relasi pelanggan
        $penjualans = Penjualan::with('pelanggan')
            ->where('status_bayar', 'paid')
            ->where(function ($query) {
                $query->where('is_draft', false)
                    ->orWhereNull('is_draft');
            })
            ->latest()
            ->get();

        // ✅ Debug: log jumlah penjualan
        Log::info('Jumlah penjualan untuk retur:', ['count' => $penjualans->count()]);

        return view('auth.penjualan.retur-penjualan.create', compact('penjualans'));
    }

    // API: ambil items berdasarkan penjualan id
    public function getItemsByPenjualan($id)
    {
        try {
            // Ambil data penjualan beserta semua relasi penting
            $penjualan = Penjualan::with([
                'pelanggan',
                'itemPenjualans.item',
                'itemPenjualans.gudang',
                'itemPenjualans.satuan'
            ])->findOrFail($id);

            // Mapping data item
            $items = $penjualan->itemPenjualans->map(function ($item) {
                return [
                    'id' => $item->id,
                    'nama_item' => optional($item->item)->nama_item ?? 'Item tidak ditemukan',
                    'gudang' => optional($item->gudang)->nama_gudang ?? '-',
                    'satuan' => optional($item->satuan)->nama_satuan ?? '-', // ✅ ambil nama satuan
                    'jumlah' => (float) $item->jumlah,
                    'harga_jual' => (float) ($item->harga ?? 0),
                    'total' => (float) ($item->total ?? ($item->jumlah * ($item->harga ?? 0))),
                ];
            });

            return response()->json([
                'success' => true,
                'pelanggan' => $penjualan->pelanggan->nama_pelanggan ?? 'Tidak ada pelanggan',
                'items' => $items
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error getItemsByPenjualan:', [
                'penjualan_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat data item',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    // store retur baru
    public function store(Request $request)
    {
        // ✅ Log request data
        Log::info('Store retur penjualan request:', $request->all());

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
                $itemPenjualan = ItemPenjualan::with('item')->findOrFail($row['item_penjualan_id']);
                $jumlah = (float) $row['jumlah'];
                $harga = (float) ($itemPenjualan->harga_jual ?? $itemPenjualan->harga ?? 0);
                $subtotal = $jumlah * $harga;

                // ✅ Validasi jumlah retur
                if ($jumlah > $itemPenjualan->jumlah) {
                    DB::rollBack();

                    Log::warning('Jumlah retur melebihi jumlah penjualan:', [
                        'item' => $itemPenjualan->item->nama_item ?? 'Unknown',
                        'jumlah_retur' => $jumlah,
                        'jumlah_penjualan' => $itemPenjualan->jumlah
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => 'Validasi gagal',
                        'errors' => ['items' => ["Jumlah retur untuk item {$itemPenjualan->item->nama_item} melebihi jumlah penjualan"]]
                    ], 422);
                }

                ItemReturPenjualan::create([
                    'retur_penjualan_id' => $retur->id,
                    'item_penjualan_id' => $itemPenjualan->id,
                    'jumlah' => $jumlah,
                    'harga' => $harga,
                    'sub_total' => $subtotal,
                ]);

                // ✅ Update stok di gudang — barang kembali ke gudang bila retur dari pelanggan
                $itemGudang = ItemGudang::where('item_id', $itemPenjualan->item_id)
                    ->where('gudang_id', $itemPenjualan->gudang_id)
                    ->first();

                if ($itemGudang) {
                    $itemGudang->stok = $itemGudang->stok + $jumlah;
                    $itemGudang->total_stok = $itemGudang->total_stok + $jumlah;
                    $itemGudang->save();

                    Log::info('Stok updated:', [
                        'item' => $itemPenjualan->item->nama_item,
                        'gudang_id' => $itemPenjualan->gudang_id,
                        'jumlah_retur' => $jumlah,
                        'stok_baru' => $itemGudang->stok
                    ]);
                }
            }

            DB::commit();

            Log::info('Retur penjualan berhasil disimpan:', [
                'no_retur' => $noRetur,
                'total' => $request->total
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Retur penjualan berhasil disimpan',
                'data' => $retur
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error store retur penjualan:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
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

    // update
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
                'success' => true,
                'message' => 'Retur berhasil diperbarui',
                'data' => $retur->fresh('items')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error update retur:', [
                'retur_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
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
                return response()->json([
                    'success' => false,
                    'message' => 'Retur sudah diproses, tidak bisa dihapus.'
                ], 400);
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

            Log::info('Retur deleted:', ['retur_id' => $id]);

            return response()->json([
                'success' => true,
                'message' => 'Retur berhasil dihapus.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error delete retur:', [
                'retur_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus retur: ' . $e->getMessage()
            ], 500);
        }
    }
}
