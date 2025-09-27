<?php

namespace App\Http\Controllers;

use App\Models\Gudang;
use App\Models\Item;
use App\Models\ItemGudang;
use App\Models\ItemPenjualan;
use App\Models\Pelanggan;
use App\Models\Penjualan;
use App\Models\Satuan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PenjualanController extends Controller
{
    //
    public function index()
    {
        $penjualans = Penjualan::with('pelanggan')->orderBy('tanggal', 'desc')->paginate(10);
        return view('auth.penjualan.index', compact('penjualans'));
    }

    public function create()
    {
        $pelanggans = Pelanggan::orderBy('nama_pelanggan')->get();
        $gudangs = Gudang::orderBy('nama_gudang')->get();
        $items = Item::orderBy('nama_item')->get();

        // preview no faktur (sama seperti yang sudah Anda punya)
        $today = now()->format('dmy');
        $last = DB::table('penjualans')
            ->whereDate('tanggal', now()->toDateString())
            ->where('no_faktur', 'like', "JL{$today}%")
            ->orderByDesc('no_faktur')
            ->first();

        if ($last) {
            $suffix = substr($last->no_faktur, strlen("JL{$today}"));
            $next = ((int) $suffix) + 1;
        } else {
            $next = 1;
        }
        $noFakturPreview = "JL{$today}" . str_pad($next, 3, '0', STR_PAD_LEFT);

        return view('auth.penjualan.create', compact('pelanggans', 'gudangs', 'items', 'noFakturPreview'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'pelanggan_id' => 'nullable|exists:pelanggans,id',
            'no_faktur'    => 'required|string|max:191',
            'tanggal'      => 'required|date',
            'deskripsi'    => 'nullable|string',
            'is_walkin'    => 'nullable|boolean', // frontend must send 1/0
            'force_walkin' => 'nullable|boolean', // optional override
            'biaya_transport' => 'nullable|numeric',
            'sub_total'    => 'required|numeric',
            'total'        => 'required|numeric',
            'items'        => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:items,id',
            'items.*.gudang_id' => 'required|exists:gudangs,id',
            'items.*.satuan_id' => 'required|exists:satuans,id',
            'items.*.jumlah' => 'required|numeric|min:0.01',
            'items.*.harga' => 'required|numeric|min:0',
            'items.*.total' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $isWalkin = (bool) ($request->input('force_walkin') || $request->input('is_walkin'));
            $pelangganId = $request->input('pelanggan_id');

            // create penjualan
            $penjualan = Penjualan::create([
                'no_faktur' => $data['no_faktur'],
                'tanggal' => $data['tanggal'],
                'pelanggan_id' => $pelangganId,
                'deskripsi' => $data['deskripsi'] ?? null,
                'sub_total' => $data['sub_total'],
                'biaya_transport' => $data['biaya_transport'] ?? 0,
                'total' => $data['total'],
                'status_bayar' => 'belum lunas',
                'status_kirim' => '-',
                'created_by' => Auth::id() ?? null,
            ]);

            foreach ($data['items'] as $it) {
                // server-side resolve price for security:
                $satuan = Satuan::find($it['satuan_id']);
                $hargaRetail = (float) ($satuan->harga_retail ?? 0);
                $partaiKecil = (float) ($satuan->partai_kecil ?? 0);
                $hargaGrosir = (float) ($satuan->harga_grosir ?? 0);

                if ($isWalkin) {
                    $resolved = $partaiKecil ?: $hargaRetail ?: $hargaGrosir;
                } else {
                    if ($pelangganId) {
                        $resolved = $hargaGrosir ?: $partaiKecil ?: $hargaRetail;
                    } else {
                        $resolved = $hargaRetail ?: $partaiKecil ?: $hargaGrosir;
                    }
                }

                // optionally you can force server price resolution (recommended):
                $finalHarga = $resolved;

                // create item_penjualan
                $itemPenjualan = ItemPenjualan::create([
                    'penjualan_id' => $penjualan->id,
                    'item_id' => $it['item_id'],
                    'gudang_id' => $it['gudang_id'],
                    'satuan_id' => $it['satuan_id'],
                    'jumlah' => $it['jumlah'],
                    'harga' => $finalHarga,
                    'total' => $finalHarga * $it['jumlah'],
                    'created_by' => Auth::id() ?? null,
                ]);

                // update stok di ItemGudang
                $ig = ItemGudang::where('item_id', $it['item_id'])
                    ->where('gudang_id', $it['gudang_id'])
                    ->where('satuan_id', $it['satuan_id'])
                    ->first();

                if ($ig) {
                    $ig->stok = max(0, ($ig->stok ?? 0) - $it['jumlah']);
                    $ig->save();
                } else {
                    // Jika tidak ada record, buat dengan stok 0 (atau - jumlah tergantung kebijakan).
                    ItemGudang::create([
                        'item_id' => $it['item_id'],
                        'gudang_id' => $it['gudang_id'],
                        'satuan_id' => $it['satuan_id'],
                        'stok' => 0,
                    ]);
                    Log::warning("ItemGudang not found when selling, created empty: item {$it['item_id']} gudang {$it['gudang_id']} satuan {$it['satuan_id']}");
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Penjualan tersimpan',
                'id' => $penjualan->id,
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Penjualan store error: ' . $e->getMessage(), ['payload' => $request->all()]);
            return response()->json(['message' => 'Gagal menyimpan penjualan', 'error' => $e->getMessage()], 500);
        }
    }
}
