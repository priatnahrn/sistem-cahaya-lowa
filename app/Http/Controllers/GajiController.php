<?php

namespace App\Http\Controllers;

use App\Models\Gaji;
use Illuminate\Http\Request;

class GajiController extends Controller
{
    // Tampilkan halaman index
    public function index()
    {
        return view('auth.keuangan.gaji-karyawan.index');
    }
    
    // API: Get semua data gaji (untuk frontend)
    public function getData()
    {
        $data = Gaji::orderBy('nama_karyawan')
            ->orderBy('tanggal')
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }
    
    // Simpan data gaji harian
    public function simpan(Request $request)
    {
        $request->validate([
            'nama_karyawan' => 'required|string',
            'tanggal' => 'required|date',
            'upah_harian' => 'nullable|numeric|min:0',
            'utang' => 'nullable|numeric|min:0',
            'keterangan' => 'nullable|string'
        ]);

        // Hitung saldo sebelumnya
        $saldoSebelumnya = Gaji::where('nama_karyawan', $request->nama_karyawan)
            ->orderBy('tanggal', 'desc')
            ->first();
        
        $saldoLama = $saldoSebelumnya ? $saldoSebelumnya->saldo : 0;
        
        // Saldo baru = saldo lama + upah + utang
        $saldoBaru = $saldoLama + ($request->upah_harian ?? 0) + ($request->utang ?? 0);
        
        // Simpan ke database
        $gaji = Gaji::create([
            'nama_karyawan' => $request->nama_karyawan,
            'tanggal' => $request->tanggal,
            'upah_harian' => $request->upah_harian ?? 0,
            'utang' => $request->utang ?? 0,
            'saldo' => $saldoBaru,
            'keterangan' => $request->keterangan
        ]);
        
        return response()->json([
            'success' => true,
            'data' => $gaji,
            'message' => 'Data berhasil disimpan'
        ]);
    }
    
    // Hapus data
    public function destroy($id)
    {
        try {
            $gaji = Gaji::findOrFail($id);
            $namaKaryawan = $gaji->nama_karyawan;
            $tanggal = $gaji->tanggal;
            
            $gaji->delete();
            
            // Update saldo untuk transaksi setelahnya
            $this->recalculateSaldo($namaKaryawan, $tanggal);
            
            return response()->json([
                'success' => true,
                'message' => 'Data berhasil dihapus'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus data'
            ], 500);
        }
    }
    
    // Recalculate saldo setelah delete/update
    private function recalculateSaldo($namaKaryawan, $fromDate)
    {
        $transactions = Gaji::where('nama_karyawan', $namaKaryawan)
            ->where('tanggal', '>=', $fromDate)
            ->orderBy('tanggal')
            ->get();
        
        $saldoBefore = Gaji::where('nama_karyawan', $namaKaryawan)
            ->where('tanggal', '<', $fromDate)
            ->orderBy('tanggal', 'desc')
            ->first();
        
        $currentSaldo = $saldoBefore ? $saldoBefore->saldo : 0;
        
        foreach ($transactions as $trans) {
            $currentSaldo += $trans->upah_harian + $trans->utang;
            $trans->update(['saldo' => $currentSaldo]);
        }
    }
    
    // Lihat detail per karyawan
    public function show($namaKaryawan)
    {
        $data = Gaji::where('nama_karyawan', $namaKaryawan)
            ->orderBy('tanggal', 'desc')
            ->get();
        
        return view('auth.keuangan.gaji-karyawan.show', compact('data', 'namaKaryawan'));
    }
    
    // Lihat data per minggu
    public function laporanMingguan(Request $request)
    {
        $tanggalMulai = $request->tanggal_mulai;
        $tanggalAkhir = date('Y-m-d', strtotime($tanggalMulai . ' +6 days'));
        
        $data = Gaji::where('nama_karyawan', $request->nama_karyawan)
            ->whereBetween('tanggal', [$tanggalMulai, $tanggalAkhir])
            ->orderBy('tanggal')
            ->get();
        
        return response()->json($data);
    }
}