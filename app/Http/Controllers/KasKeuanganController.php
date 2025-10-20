<?php

namespace App\Http\Controllers;

use App\Models\KasKeuangan;
use App\Models\Pembayaran;
use App\Models\LogActivity;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Carbon\Carbon;

class KasKeuanganController extends Controller
{
    use AuthorizesRequests;

    /**
     * Tampilkan halaman kas keuangan
     */
    public function index(Request $request)
    {
        // ✅ Authorization
        $this->authorize('cashflows.view');

        $user = Auth::user();

        // Cek apakah super admin
        $isSuperAdmin = $user->can('users.view'); // super admin pasti punya permission ini

        if ($isSuperAdmin) {
            // 🧾 Super Admin: bisa lihat semua kas dari semua keuangan
            $kasQuery = KasKeuangan::with(['user', 'pembayaran.penjualan'])
                ->latest();

            // 💰 Total kas per kasir (SEMUA METHOD)
            $totalPerKasir = User::whereHas('kasKeuangan')
                ->withCount(['kasKeuangan as total_kas' => function ($q) {
                    $q->select(DB::raw('SUM(CASE WHEN jenis = "masuk" THEN nominal ELSE -nominal END)'));
                }])
                ->get();

            // 💵 Total keseluruhan dari semua kasir (SEMUA METHOD)
            $totalKeseluruhan = KasKeuangan::sum(DB::raw('CASE WHEN jenis = "masuk" THEN nominal ELSE -nominal END'));

            // Nilai-nilai ini tidak relevan untuk superadmin
            $saldoSistem = null;
            $saldoCash = null;
            $pemasukanHariIni = null;
            $pengeluaranHariIni = null;
        } else {
            // 👤 Keuangan biasa: hanya bisa lihat kas miliknya
            $kasQuery = KasKeuangan::with(['user', 'pembayaran.penjualan'])
                ->where('user_id', $user->id)
                ->latest();

            $totalPerKasir = null;
            $totalKeseluruhan = null;

            // ✅ SALDO SISTEM (semua metode)
            $saldoSistem = KasKeuangan::where('user_id', $user->id)
                ->sum(DB::raw('CASE WHEN jenis = "masuk" THEN nominal ELSE -nominal END'));

            // ✅ SALDO CASH (hanya kas manual + pembayaran cash)
            $saldoCash = KasKeuangan::where('user_id', $user->id)
                ->where(function ($q) {
                    $q->whereNull('pembayarans_id')
                        ->orWhereHas('pembayaran', function ($q2) {
                            $q2->where('method', 'cash');
                        });
                })
                ->sum(DB::raw('CASE WHEN jenis = "masuk" THEN nominal ELSE -nominal END'));

            // ✅ Pemasukan dan pengeluaran hari ini (semua metode)
            $today = Carbon::today();
            $pemasukanHariIni = KasKeuangan::where('user_id', $user->id)
                ->whereDate('created_at', $today)
                ->where('jenis', 'masuk')
                ->sum('nominal');

            $pengeluaranHariIni = KasKeuangan::where('user_id', $user->id)
                ->whereDate('created_at', $today)
                ->where('jenis', 'keluar')
                ->sum('nominal');
        }

        // 🗓️ Filter tanggal jika dikirim dari request
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $kasQuery->whereBetween('created_at', [
                $request->start_date . ' 00:00:00',
                $request->end_date . ' 23:59:59'
            ]);
        }

        // 📑 Ambil data paginasi
        $kasKeuangan = $kasQuery->paginate(20);

        // 🧩 Kirim ke view
        return view('auth.keuangan.kas-keuangan.index', compact(
            'kasKeuangan',
            'saldoSistem',
            'saldoCash',
            'pemasukanHariIni',
            'pengeluaranHariIni',
            'totalPerKasir',
            'totalKeseluruhan'
        ));
    }



    /**
     * Form tambah kas manual (pemasukan/pengeluaran internal)
     */
    public function create()
    {
        // ✅ Authorization
        $this->authorize('cashflows.create');

        return view('auth.keuangan.kas-keuangan.create');
    }

    /**
     * Simpan data kas manual (BUKAN dari pembayaran)
     */
    public function store(Request $request)
    {
        // ✅ Authorization
        $this->authorize('cashflows.create');

        $request->validate([
            'jenis' => 'required|in:masuk,keluar',
            'nominal' => 'required|numeric|min:0',
            'keterangan' => 'required|string|max:255',
        ]);

        try {
            $kas = KasKeuangan::create([
                'user_id' => Auth::id(),
                'jenis' => $request->jenis,
                'nominal' => $request->nominal,
                'keterangan' => $request->keterangan,
                // pembayarans_id = NULL (karena ini kas manual)
            ]);

            LogActivity::create([
                'user_id' => Auth::id(),
                'activity_type' => 'create_kas_keuangan',
                'description' => 'Menambah kas ' . ($request->jenis === 'masuk' ? 'pemasukan' : 'pengeluaran') . ' sebesar Rp ' . number_format($request->nominal, 0, ',', '.'),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Kas berhasil ditambahkan.',
                    'data' => $kas
                ]);
            }

            return redirect()->route('kas-keuangan.index')
                ->with('success', 'Kas berhasil ditambahkan.');
        } catch (\Exception $e) {
            Log::error('Store Kas Keuangan error: ' . $e->getMessage());

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal menambahkan kas.'
                ], 500);
            }

            return back()->withInput()
                ->withErrors(['error' => 'Gagal menambahkan kas.']);
        }
    }

    /**
     * Hitung saldo kas (bandingkan fisik vs sistem)
     * ✅ HANYA CASH yang dihitung fisik
     */
    public function hitungSaldo(Request $request)
    {
        // ✅ Authorization (harus bisa create untuk bisa hitung)
        $this->authorize('cashflows.create');

        $request->validate([
            'nominal_fisik' => 'required|numeric|min:0',
        ]);

        $user = Auth::user();

        // ✅ Hitung saldo CASH di laci (bukan semua method!)
        $saldoCashSistem = KasKeuangan::where('user_id', $user->id)
            ->where(function ($q) {
                // Kas manual = cash
                $q->whereNull('pembayarans_id')
                    // Pembayaran cash
                    ->orWhereHas('pembayaran', function ($q2) {
                        $q2->where('method', 'cash');
                    });
            })
            ->sum(DB::raw('CASE WHEN jenis = "masuk" THEN nominal ELSE -nominal END'));

        $nominalFisik = $request->nominal_fisik;
        $selisih = $nominalFisik - $saldoCashSistem;

        // Jika ada selisih, catat sebagai penyesuaian
        if ($selisih != 0) {
            $jenis = $selisih > 0 ? 'masuk' : 'keluar';
            $nominal = abs($selisih);

            KasKeuangan::create([
                'user_id' => $user->id,
                'jenis' => $jenis,
                'nominal' => $nominal,
                'keterangan' => 'Penyesuaian kas cash - Selisih perhitungan fisik. ' .
                    ($selisih > 0 ? 'Kelebihan' : 'Kekurangan') . ' Rp ' .
                    number_format($nominal, 0, ',', '.'),
            ]);

            LogActivity::create([
                'user_id' => Auth::id(),
                'activity_type' => 'hitung_kas_keuangan',
                'description' => 'Perhitungan kas dengan selisih Rp ' . number_format(abs($selisih), 0, ',', '.'),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        }

        return response()->json([
            'success' => true,
            'saldo_sistem' => $saldoCashSistem, // saldo cash di sistem
            'nominal_fisik' => $nominalFisik,
            'selisih' => $selisih,
            'message' => $selisih == 0 ?
                'Kas sudah sesuai!' :
                'Penyesuaian kas telah dilakukan.',
        ]);
    }

    /**
     * Detail kas keuangan
     */
    public function show($id)
    {
        // ✅ Authorization
        $this->authorize('cashflows.view');

        $kas = KasKeuangan::with(['user', 'pembayaran.penjualan', 'penjualan'])
            ->findOrFail($id);

        // Cek akses: keuangan hanya bisa lihat kas sendiri
        $isSuperAdmin = Auth::user()->can('users.view');
        if (!$isSuperAdmin && $kas->user_id !== Auth::id()) {
            abort(403, 'Anda tidak memiliki akses ke data ini.');
        }

        return view('auth.keuangan.kas-keuangan.show', compact('kas'));
    }

    /**
     * Hapus data kas (HANYA kas manual yang bisa dihapus)
     */
    public function destroy($id)
    {
        // ✅ Authorization
        $this->authorize('cashflows.delete');

        try {
            $kas = KasKeuangan::findOrFail($id);

            // Cek akses: keuangan hanya bisa hapus kas sendiri
            $isSuperAdmin = Auth::user()->can('users.view');
            if (!$isSuperAdmin && $kas->user_id !== Auth::id()) {
                abort(403, 'Anda tidak memiliki akses untuk menghapus data ini.');
            }

            // ✅ Hanya bisa hapus kas manual (yang tidak terkait pembayaran)
            if ($kas->pembayarans_id !== null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kas dari pembayaran tidak dapat dihapus secara manual.'
                ], 400);
            }

            $kas->delete();

            LogActivity::create([
                'user_id' => Auth::id(),
                'activity_type' => 'delete_kas_keuangan',
                'description' => 'Menghapus kas ' . $kas->jenis_label . ' sebesar Rp ' . number_format($kas->nominal, 0, ',', '.'),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Kas berhasil dihapus.'
            ]);
        } catch (\Exception $e) {
            Log::error('Delete Kas Keuangan error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus kas.'
            ], 500);
        }
    }

    /**
     * Laporan kas keuangan (per periode)
     */
    public function laporan(Request $request)
    {
        // ✅ Authorization
        $this->authorize('cashflows.view');

        $user = Auth::user();
        $isSuperAdmin = $user->can('users.view');

        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));

        $query = KasKeuangan::with(['user', 'pembayaran.penjualan'])
            ->whereBetween('created_at', [
                $startDate . ' 00:00:00',
                $endDate . ' 23:59:59'
            ]);

        // Filter untuk keuangan: hanya data sendiri
        if (!$isSuperAdmin) {
            $query->where('user_id', $user->id);
        }

        $kasData = $query->get();

        // Hitung ringkasan (SEMUA METHOD)
        $totalMasuk = $kasData->where('jenis', 'masuk')->sum('nominal');
        $totalKeluar = $kasData->where('jenis', 'keluar')->sum('nominal');
        $saldoAkhir = $totalMasuk - $totalKeluar;

        // Hitung ringkasan CASH ONLY
        $totalMasukCash = $kasData->where('jenis', 'masuk')
            ->filter(function ($kas) {
                return $kas->pembayarans_id === null ||
                    optional($kas->pembayaran)->method === 'cash';
            })
            ->sum('nominal');

        $totalKeluarCash = $kasData->where('jenis', 'keluar')
            ->filter(function ($kas) {
                return $kas->pembayarans_id === null ||
                    optional($kas->pembayaran)->method === 'cash';
            })
            ->sum('nominal');

        $saldoAkhirCash = $totalMasukCash - $totalKeluarCash;

        return view('auth.keuangan.kas-keuangan.laporan', compact(
            'kasData',
            'totalMasuk',
            'totalKeluar',
            'saldoAkhir',
            'totalMasukCash',
            'totalKeluarCash',
            'saldoAkhirCash',
            'startDate',
            'endDate'
        ));
    }
}
