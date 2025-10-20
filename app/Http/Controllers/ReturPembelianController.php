<?php

namespace App\Http\Controllers;

use App\Models\ItemGudang;
use App\Models\ReturPembelian;
use App\Models\ItemReturPembelian;
use App\Models\Pembelian;
use App\Models\ItemPembelian;
use App\Models\LogActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ReturPembelianController extends Controller
{
    use AuthorizesRequests;

    // 📌 list retur pembelian
    public function index()
    {
        // ✅ Check permission view
        $this->authorize('retur_pembelian.view');

        $returs = ReturPembelian::with('pembelian.supplier', 'creator', 'updater')
            ->latest()
            ->paginate(20);

        return view('auth.pembelian.retur-pembelian.index', compact('returs'));
    }

    // 📌 form tambah retur
    public function create()
    {
        // ✅ Check permission create
        $this->authorize('retur_pembelian.create');

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
        // ✅ Check permission view
        $this->authorize('retur_pembelian.view');

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

    // 📌 simpan retur baru (TIDAK mengurangi stok, hanya catat)
    public function store(Request $request)
    {
        // ✅ Check permission create
        $this->authorize('retur_pembelian.create');

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
                $lastNumber = (int) substr($lastRetur->no_retur, -3);
                $nextNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
            } else {
                $nextNumber = '001';
            }

            $nomor = 'RB' . $tanggal . $nextNumber;

            // Simpan retur pembelian dengan status pending
            $retur = ReturPembelian::create([
                'pembelian_id' => $request->pembelian_id,
                'no_retur'     => $nomor,
                'tanggal'      => now(),
                'catatan'      => $request->catatan,
                'total'        => $request->total,
                'status'       => 'pending', // ✅ Status awal: pending
                'created_by'   => Auth::id(),
            ]);

            // Simpan detail item retur
            foreach ($request->items as $row) {
                $itemPembelian = ItemPembelian::findOrFail($row['item_pembelian_id']);
                $jumlah = (float) $row['jumlah'];
                $harga = (float) $itemPembelian->harga_beli;
                $subtotal = $jumlah * $harga;

                // Validasi: jumlah retur tidak boleh melebihi jumlah pembelian
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
                    'sub_total'          => $subtotal,
                ]);

                // ⚠️ TIDAK mengurangi stok di sini!
                // Stok baru dikurangi ketika status berubah ke 'taken'
            }

            Log::info('✅ Retur pembelian berhasil dibuat (status: pending)', [
                'retur_id' => $retur->id,
                'no_retur' => $nomor,
                'pembelian_id' => $request->pembelian_id,
                'total_items' => count($request->items),
            ]);

            LogActivity::create([
                'user_id'       => Auth::id(),
                'activity_type' => 'create_retur_pembelian',
                'description'   => 'Created retur pembelian ID: ' . $retur->id . ' (No Retur: ' . $nomor . ')',
                'ip_address'    => $request->ip(),
                'user_agent'    => $request->userAgent(),
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Retur pembelian berhasil disimpan',
                'data' => $retur
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌ Gagal menyimpan retur pembelian', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Gagal menyimpan retur pembelian',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // 📌 detail retur
    public function show($id)
    {
        // ✅ Check permission view
        $this->authorize('retur_pembelian.view');

        $retur = ReturPembelian::with('pembelian.supplier', 'items.itemPembelian.item')->findOrFail($id);
        return view('auth.pembelian.retur-pembelian.show', compact('retur'));
    }

    // 📌 update retur (FIXED VERSION - dengan handling stok yang BENAR)
    public function update(Request $request, $id)
    {
        $this->authorize('retur_pembelian.update');

        $retur = ReturPembelian::with('items.itemPembelian')->findOrFail($id);

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

            $statusLama = $retur->status;
            $statusBaru = $request->status;

            Log::info('=== [UPDATE RETUR PEMBELIAN] Mulai update ===', [
                'retur_id' => $retur->id,
                'status_lama' => $statusLama,
                'status_baru' => $statusBaru,
            ]);

            // 🔹 STEP 1: ROLLBACK STOK dari items LAMA jika status lama = 'taken'
            if ($statusLama === 'taken') {
                foreach ($retur->items as $oldItem) {
                    $this->restoreStock($oldItem);
                }
            }

            // 🔹 STEP 2: Update header retur
            $retur->update([
                'tanggal'    => $request->tanggal,
                'catatan'    => $request->catatan,
                'total'      => $request->total,
                'status'     => $statusBaru,
                'updated_by' => Auth::id(),
            ]);

            // 🔹 STEP 3: Hapus items lama
            $retur->items()->delete();

            // 🔹 STEP 4: Insert items BARU
            $newItems = [];
            foreach ($request->items as $row) {
                $itemPembelian = ItemPembelian::findOrFail($row['item_pembelian_id']);
                $jumlah   = (float) $row['jumlah'];
                $harga    = (float) $itemPembelian->harga_beli;
                $subtotal = $jumlah * $harga;

                // Validasi: jumlah retur tidak boleh melebihi jumlah pembelian
                if ($jumlah > $itemPembelian->jumlah) {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'Validasi gagal',
                        'errors'  => [
                            'items' => ["Jumlah retur untuk item {$itemPembelian->item->nama_item} melebihi jumlah pembelian"]
                        ]
                    ], 422);
                }

                $newItem = ItemReturPembelian::create([
                    'retur_pembelian_id' => $retur->id,
                    'item_pembelian_id'  => $itemPembelian->id,
                    'jumlah'             => $jumlah,
                    'harga_beli'         => $harga,
                    'sub_total'          => $subtotal,
                ]);

                $newItems[] = $newItem;
            }

            // 🔹 STEP 5: KURANGI STOK untuk items BARU jika status baru = 'taken'
            if ($statusBaru === 'taken') {
                foreach ($newItems as $newItem) {
                    $this->reduceStock($newItem);
                }
            }

            Log::info('=== [UPDATE RETUR PEMBELIAN] Update selesai ===', [
                'retur_id' => $retur->id,
                'status_final' => $statusBaru,
            ]);

            LogActivity::create([
                'user_id'       => Auth::id(),
                'activity_type' => 'update_retur_pembelian',
                'description'   => 'Updated retur pembelian ID: ' . $id . ' to status: ' . $statusBaru,
                'ip_address'    => $request->ip(),
                'user_agent'    => $request->userAgent(),
            ]);

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => 'Retur berhasil diperbarui.',
                'data'    => $retur->fresh('items')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌ Gagal update retur pembelian', [
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

    // 📌 Helper: Kembalikan stok (dipanggil saat rollback dari status 'taken')
    private function restoreStock($itemRetur)
    {
        $itemPembelian = $itemRetur->itemPembelian;

        $itemGudang = ItemGudang::where('item_id', $itemPembelian->item_id)
            ->where('gudang_id', $itemPembelian->gudang_id)
            ->where('satuan_id', $itemPembelian->satuan_id)
            ->first();

        if ($itemGudang) {
            $stokLama = $itemGudang->stok;
            $itemGudang->stok += $itemRetur->jumlah; // Kembalikan stok
            $itemGudang->total_stok = $itemGudang->stok * ($itemGudang->satuan->jumlah ?? 1);
            $itemGudang->save();

            Log::info('📦 Stok dikembalikan', [
                'item_id' => $itemPembelian->item_id,
                'gudang_id' => $itemPembelian->gudang_id,
                'jumlah_dikembalikan' => $itemRetur->jumlah,
                'stok_lama' => $stokLama,
                'stok_baru' => $itemGudang->stok,
            ]);
        }
    }

    // 📌 Helper: Kurangi stok (dipanggil saat status berubah ke 'taken')
    private function reduceStock($itemRetur)
    {
        $itemPembelian = $itemRetur->itemPembelian;

        $itemGudang = ItemGudang::where('item_id', $itemPembelian->item_id)
            ->where('gudang_id', $itemPembelian->gudang_id)
            ->where('satuan_id', $itemPembelian->satuan_id)
            ->first();

        if ($itemGudang) {
            $stokLama = $itemGudang->stok;

            // Validasi: stok tidak boleh negatif
            if ($itemGudang->stok < $itemRetur->jumlah) {
                throw new \Exception("Stok tidak cukup untuk item {$itemPembelian->item->nama_item}. Stok tersedia: {$itemGudang->stok}, Dibutuhkan: {$itemRetur->jumlah}");
            }

            $itemGudang->stok -= $itemRetur->jumlah; // Kurangi stok
            $itemGudang->total_stok = $itemGudang->stok * ($itemGudang->satuan->jumlah ?? 1);
            $itemGudang->save();

            Log::info('📉 Stok dikurangi', [
                'item_id' => $itemPembelian->item_id,
                'gudang_id' => $itemPembelian->gudang_id,
                'jumlah_retur' => $itemRetur->jumlah,
                'stok_lama' => $stokLama,
                'stok_baru' => $itemGudang->stok,
            ]);
        }
    }

    // 📌 hapus retur
    public function destroy($id)
    {
        // ✅ Check permission delete
        $this->authorize('retur_pembelian.delete');

        try {
            DB::beginTransaction();

            $retur = ReturPembelian::with('items.itemPembelian.item')->findOrFail($id);

            // Hanya bisa dihapus jika status = pending
            if ($retur->status !== 'pending') {
                return redirect()->route('retur-pembelian.index')
                    ->with('error', 'Retur sudah diproses, tidak bisa dihapus. Hanya retur dengan status "Pending" yang bisa dihapus.');
            }

            // Karena status pending, stok tidak pernah dikurangi
            // Jadi tidak perlu mengembalikan stok

            Log::info('✅ Menghapus retur pembelian (status: pending)', [
                'retur_id' => $retur->id,
                'no_retur' => $retur->no_retur,
            ]);

            // Hapus items dan retur
            $retur->items()->delete();
            $retur->delete();

            LogActivity::create([
                'user_id'       => Auth::id(),
                'activity_type' => 'delete_retur_pembelian',
                'description'   => 'Deleted retur pembelian ID: ' . $id . ' (No Retur: ' . $retur->no_retur . ')',
                'ip_address'    => request()->ip(),
                'user_agent'    => request()->userAgent(),
            ]);

            DB::commit();

            return redirect()->route('retur-pembelian.index')
                ->with('success', 'Retur berhasil dihapus.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('❌ Gagal menghapus retur pembelian', [
                'retur_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('retur-pembelian.index')
                ->with('error', 'Gagal menghapus retur: ' . $e->getMessage());
        }
    }
}
