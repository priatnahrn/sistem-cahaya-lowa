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

        // preview no faktur
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
            'is_walkin'    => 'nullable|boolean',
            'force_walkin' => 'nullable|boolean',
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
                // server-side resolve price for security
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

                $finalHarga = $resolved;

                // create item_penjualan
                ItemPenjualan::create([
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

    /**
     * API: Search items by nama or kode
     */
    public function searchItems(Request $request)
    {
        $query = $request->get('q', '');

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $items = Item::where(function ($q) use ($query) {
            $q->where('nama_item', 'like', "%{$query}%")
                ->orWhere('kode_item', 'like', "%{$query}%");
        })
            ->with('satuans')
            ->limit(15)
            ->get()
            ->map(function ($item) {
                // Get all satuans for this item
                $satuans = Satuan::whereIn('id', function ($q) use ($item) {
                    $q->select('satuan_id')
                        ->from('item_gudangs')
                        ->where('item_id', $item->id);
                })->get();

                return [
                    'id' => $item->id,
                    'nama_item' => $item->nama_item,
                    'kode_item' => $item->kode_item,
                    'barcode' => $item->barcode,
                    'satuan_default' => $item->satuan_id,
                    'satuans' => $satuans->map(fn($s) => [
                        'id' => $s->id,
                        'nama_satuan' => $s->nama_satuan,
                        'harga_retail' => $s->harga_retail ?? 0,
                        'partai_kecil' => $s->partai_kecil ?? 0,
                        'harga_grosir' => $s->harga_grosir ?? 0,
                    ])
                ];
            });

        return response()->json($items);
    }

    /**
     * API: Get item by barcode (untuk scanner)
     */
    public function getItemByBarcode($barcode)
    {
        $item = Item::where('barcode', $barcode)
            ->orWhere('kode_item', $barcode)
            ->first();

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Item tidak ditemukan'
            ], 404);
        }

        // Ambil semua gudang & stok yang terkait item ini
        $gudangs = ItemGudang::where('item_id', $item->id)
            ->with('gudang', 'satuan')
            ->get()
            ->map(function ($ig) {
                return [
                    'gudang_id'   => $ig->gudang_id,
                    'nama_gudang' => $ig->gudang->nama_gudang ?? '-',
                    'satuan_id'   => $ig->satuan_id,
                    'nama_satuan' => $ig->satuan->nama_satuan ?? '-',
                    'stok'        => $ig->stok ?? 0,
                    'harga_retail'   => $ig->satuan->harga_retail ?? 0,
                    'partai_kecil'   => $ig->satuan->partai_kecil ?? 0,
                    'harga_grosir'   => $ig->satuan->harga_grosir ?? 0,
                ];
            });

        return response()->json([
            'id' => $item->id,
            'nama_item' => $item->nama_item,
            'kode_item' => $item->kode_item,
            'barcode' => $item->barcode,
            'satuan_default' => $item->satuan_id,
            'gudangs' => $gudangs, // <---- Tambahkan ini
        ]);
    }

    /**
     * API: Get stock for specific item, gudang, satuan
     */
    public function getStock(Request $request)
    {
        $itemId = $request->get('item_id');
        $gudangId = $request->get('gudang_id');
        $satuanId = $request->get('satuan_id');

        if (!$itemId || !$gudangId || !$satuanId) {
            return response()->json([
                'jumlah' => 0,
                'satuan_nama' => ''
            ]);
        }

        $ig = ItemGudang::where('item_id', $itemId)
            ->where('gudang_id', $gudangId)
            ->where('satuan_id', $satuanId)
            ->first();

        $satuan = Satuan::find($satuanId);

        return response()->json([
            'jumlah' => $ig ? ($ig->stok ?? 0) : 0,
            'satuan_nama' => $satuan ? $satuan->nama_satuan : ''
        ]);
    }

    /**
     * API: Get price for item based on satuan and level
     */
    public function getPrice(Request $request)
    {
        $satuanId = $request->get('satuan_id');
        $level = $request->get('level', 'retail'); // retail, partai_kecil, grosir
        $isWalkin = $request->get('is_walkin', false);

        if (!$satuanId) {
            return response()->json(['harga' => 0]);
        }

        $satuan = Satuan::find($satuanId);
        if (!$satuan) {
            return response()->json(['harga' => 0]);
        }

        $hargaRetail = (float) ($satuan->harga_retail ?? 0);
        $partaiKecil = (float) ($satuan->partai_kecil ?? 0);
        $hargaGrosir = (float) ($satuan->harga_grosir ?? 0);

        // Logic sama seperti di store
        if ($isWalkin) {
            $harga = $partaiKecil ?: $hargaRetail ?: $hargaGrosir;
        } else {
            if ($level === 'grosir') {
                $harga = $hargaGrosir ?: $partaiKecil ?: $hargaRetail;
            } elseif ($level === 'partai_kecil') {
                $harga = $partaiKecil ?: $hargaRetail ?: $hargaGrosir;
            } else {
                $harga = $hargaRetail ?: $partaiKecil ?: $hargaGrosir;
            }
        }

        return response()->json([
            'harga' => $harga,
            'harga_retail' => $hargaRetail,
            'partai_kecil' => $partaiKecil,
            'harga_grosir' => $hargaGrosir,
        ]);
    }
}
