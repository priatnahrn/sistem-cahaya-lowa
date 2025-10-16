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
    // 📌 Daftar retur penjualan
    public function index()
    {
        $returs = ReturPenjualan::with('penjualan.pelanggan', 'creator', 'updater')
            ->latest()
            ->paginate(20);

        return view('auth.penjualan.retur-penjualan.index', compact('returs'));
    }

    // 📌 Form create
    public function create()
    {
        $penjualans = Penjualan::with('pelanggan')
            ->where('status_bayar', 'paid')
            ->where(function ($query) {
                $query->where('is_draft', false)
                    ->orWhereNull('is_draft');
            })
            ->latest()
            ->get();

        Log::info('Jumlah penjualan untuk retur:', ['count' => $penjualans->count()]);

        return view('auth.penjualan.retur-penjualan.create', compact('penjualans'));
    }

    // 📌 API: ambil items berdasarkan penjualan id
    public function getItemsByPenjualan($id)
    {
        try {
            $penjualan = Penjualan::with([
                'pelanggan',
                'itemPenjualans.item',
                'itemPenjualans.gudang',
                'itemPenjualans.satuan'
            ])->findOrFail($id);

            $items = $penjualan->itemPenjualans->map(function ($item) {
                return [
                    'id' => $item->id,
                    'nama_item' => optional($item->item)->nama_item ?? 'Item tidak ditemukan',
                    'gudang' => optional($item->gudang)->nama_gudang ?? '-',
                    'satuan' => optional($item->satuan)->nama_satuan ?? '-',
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

    // 📌 Store retur baru (status awal: pending, TIDAK menambah stok dulu)
    public function store(Request $request)
    {
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

            // Generate nomor: RJ + ddmmyy + 3digit
            $tanggal = now()->format('dmy');
            $last = ReturPenjualan::whereDate('tanggal', now())->orderBy('no_retur', 'desc')->first();

            if ($last) {
                $lastNumber = (int) substr($last->no_retur, -3);
                $nextNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
            } else {
                $nextNumber = '001';
            }

            $noRetur = 'RJ' . $tanggal . $nextNumber;

            // Simpan header dengan status pending
            $retur = ReturPenjualan::create([
                'penjualan_id' => $request->penjualan_id,
                'no_retur' => $noRetur,
                'tanggal' => now(),
                'catatan' => $request->catatan,
                'total' => $request->total,
                'status' => 'pending', // ✅ Status awal: pending
                'created_by' => Auth::id(),
            ]);

            // Simpan detail items
            foreach ($request->items as $row) {
                $itemPenjualan = ItemPenjualan::with('item')->findOrFail($row['item_penjualan_id']);
                $jumlah = (float) $row['jumlah'];
                $harga = (float) ($itemPenjualan->harga_jual ?? $itemPenjualan->harga ?? 0);
                $subtotal = $jumlah * $harga;

                // Validasi jumlah retur
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

                // ⚠️ TIDAK menambah stok di sini!
                // Stok baru ditambah ketika status berubah ke 'taken'
            }

            Log::info('✅ Retur penjualan berhasil dibuat (status: pending)', [
                'retur_id' => $retur->id,
                'no_retur' => $noRetur,
                'penjualan_id' => $request->penjualan_id,
                'total_items' => count($request->items),
            ]);

            DB::commit();

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

    // 📌 Show detail
    public function show($id)
    {
        $retur = ReturPenjualan::with('penjualan.pelanggan', 'items.itemPenjualan.item')->findOrFail($id);
        return view('auth.penjualan.retur-penjualan.show', compact('retur'));
    }

    // 📌 Edit form
    public function edit($id)
    {
        $retur = ReturPenjualan::with('penjualan.pelanggan', 'items.itemPenjualan')->findOrFail($id);
        return view('auth.penjualan.retur-penjualan.show', compact('retur'));
    }

    // 📌 Update (dengan handling stok yang benar)
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

            $statusLama = $retur->status;
            $statusBaru = $request->status;

            Log::info('=== [UPDATE RETUR PENJUALAN] Mulai update ===', [
                'retur_id' => $retur->id,
                'status_lama' => $statusLama,
                'status_baru' => $statusBaru,
            ]);

            // 🔹 ROLLBACK STOK jika status lama = 'taken'
            // Ini handle case: taken → pending atau taken → refund
            if ($statusLama === 'taken') {
                foreach ($retur->items as $oldItem) {
                    $itemGudang = ItemGudang::where('item_id', $oldItem->itemPenjualan->item_id)
                        ->where('gudang_id', $oldItem->itemPenjualan->gudang_id)
                        ->where('satuan_id', $oldItem->itemPenjualan->satuan_id)
                        ->first();

                    if ($itemGudang) {
                        $stokLama = $itemGudang->stok;
                        // Kurangi stok (karena sebelumnya kita tambah saat taken)
                        $itemGudang->stok = max(0, $itemGudang->stok - $oldItem->jumlah);
                        $itemGudang->total_stok = $itemGudang->stok * ($itemGudang->satuan->jumlah ?? 1);
                        $itemGudang->save();

                        Log::info('📦 Rollback stok (status lama: taken → ' . $statusBaru . ')', [
                            'item_id' => $oldItem->itemPenjualan->item_id,
                            'gudang_id' => $oldItem->itemPenjualan->gudang_id,
                            'jumlah_dikurangi' => $oldItem->jumlah,
                            'stok_lama' => $stokLama,
                            'stok_baru' => $itemGudang->stok,
                        ]);
                    }
                }
            }

            // 🔹 Update header retur
            $retur->update([
                'tanggal'    => $request->tanggal,
                'catatan'    => $request->catatan,
                'total'      => $request->total,
                'status'     => $statusBaru,
                'updated_by' => Auth::id(),
            ]);

            // 🔹 Hapus items lama dan insert ulang
            $retur->items()->delete();

            foreach ($request->items as $row) {
                $itemPenjualan = ItemPenjualan::findOrFail($row['item_penjualan_id']);
                $jumlah   = (float) $row['jumlah'];
                $harga    = (float) ($itemPenjualan->harga_jual ?? $itemPenjualan->harga ?? 0);
                $subtotal = $jumlah * $harga;

                if ($jumlah > $itemPenjualan->jumlah) {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'Validasi gagal',
                        'errors'  => [
                            'items' => ["Jumlah retur untuk item {$itemPenjualan->item->nama_item} melebihi jumlah penjualan"]
                        ]
                    ], 422);
                }

                ItemReturPenjualan::create([
                    'retur_penjualan_id' => $retur->id,
                    'item_penjualan_id'  => $itemPenjualan->id,
                    'jumlah'             => $jumlah,
                    'harga'              => $harga,
                    'sub_total'          => $subtotal,
                ]);
            }

            // 🔹 TAMBAH STOK jika status baru = 'taken'
            // Ini handle case: pending → taken atau refund → taken
            if ($statusBaru === 'taken') {
                foreach ($request->items as $row) {
                    $itemPenjualan = ItemPenjualan::findOrFail($row['item_penjualan_id']);
                    $jumlah = (float) $row['jumlah'];

                    $itemGudang = ItemGudang::where('item_id', $itemPenjualan->item_id)
                        ->where('gudang_id', $itemPenjualan->gudang_id)
                        ->where('satuan_id', $itemPenjualan->satuan_id)
                        ->first();

                    if ($itemGudang) {
                        $stokLama = $itemGudang->stok;
                        $itemGudang->stok = $itemGudang->stok + $jumlah; // Tambah stok
                        $itemGudang->total_stok = $itemGudang->stok * ($itemGudang->satuan->jumlah ?? 1);
                        $itemGudang->save();

                        Log::info('📈 Stok ditambah (status baru: taken)', [
                            'item_id' => $itemPenjualan->item_id,
                            'gudang_id' => $itemPenjualan->gudang_id,
                            'jumlah_retur' => $jumlah,
                            'stok_lama' => $stokLama,
                            'stok_baru' => $itemGudang->stok,
                        ]);
                    }
                }
            }

            Log::info('=== [UPDATE RETUR PENJUALAN] Update selesai ===', [
                'retur_id' => $retur->id,
                'status_final' => $statusBaru,
            ]);

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => 'Retur berhasil diperbarui.',
                'data'    => $retur->fresh('items')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌ Gagal update retur penjualan', [
                'retur_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Gagal update retur',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    // 📌 Delete
    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $retur = ReturPenjualan::with('items.itemPenjualan.item')->findOrFail($id);

            // Hanya bisa dihapus jika status = pending
            if ($retur->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Retur sudah diproses, tidak bisa dihapus. Hanya retur dengan status "Pending" yang bisa dihapus.'
                ], 400);
            }

            // Karena status pending, stok tidak pernah ditambah
            // Jadi tidak perlu mengurangi stok

            Log::info('✅ Menghapus retur penjualan (status: pending)', [
                'retur_id' => $retur->id,
                'no_retur' => $retur->no_retur,
            ]);

            $retur->items()->delete();
            $retur->delete();

            DB::commit();

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