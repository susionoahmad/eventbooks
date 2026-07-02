<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\Tax;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function profitLoss(Request $request): JsonResponse
    {
        $year = $request->input('tahun', Carbon::now()->year);
        
        $pendapatanKontrak = (float) Transaction::whereYear('tanggal', $year)
            ->whereIn('kategori', ['dp_event', 'pelunasan_event'])
            ->sum('nominal');
            
        $pendapatanLain = (float) Transaction::whereYear('tanggal', $year)
            ->where('kategori', 'pendapatan_lain')
            ->sum('nominal');
            
        $biayaVendor = (float) Transaction::whereYear('tanggal', $year)
            ->where('kategori', 'pembayaran_vendor')
            ->sum('nominal');
            
        $biayaOperasional = (float) Transaction::whereYear('tanggal', $year)
            ->whereIn('kategori', ['operasional', 'transportasi'])
            ->sum('nominal');
            
        $biayaMarketing = (float) Transaction::whereYear('tanggal', $year)
            ->where('kategori', 'marketing')
            ->sum('nominal');
            
        $biayaKonsumsi = (float) Transaction::whereYear('tanggal', $year)
            ->where('kategori', 'konsumsi')
            ->sum('nominal');
            
        // PPh 21/23 are withholding taxes, already included in gross expenses (vendor/freelancer payouts).
        // Net PPN expense is PPN Keluaran - PPN Masukan.
        $ppnKeluaran = (float) Tax::whereYear('created_at', $year)
            ->where('tipe_pajak', 'ppn_keluaran')
            ->sum('nominal_pajak');
            
        $ppnMasukan = (float) Tax::whereYear('created_at', $year)
            ->where('tipe_pajak', 'ppn_masukan')
            ->sum('nominal_pajak');

        $bebanPajak = $ppnKeluaran - $ppnMasukan;

        return response()->json([
            'pendapatanKontrak' => $pendapatanKontrak,
            'pendapatanLain' => $pendapatanLain,
            'biayaVendor' => $biayaVendor,
            'biayaOperasional' => $biayaOperasional,
            'biayaMarketing' => $biayaMarketing,
            'biayaKonsumsi' => $biayaKonsumsi,
            'bebanPajak' => $bebanPajak
        ]);
    }

    public function cashFlow(Request $request): JsonResponse
    {
        $year = $request->input('tahun', Carbon::now()->year);

        // Group inflows by category
        $inflowCategories = Transaction::whereYear('tanggal', $year)
            ->where('tipe', 'kas_masuk')
            ->selectRaw('kategori, SUM(nominal) as total')
            ->groupBy('kategori')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->kategori => (float) $item->total];
            });

        // Group outflows by category
        $outflowCategories = Transaction::whereYear('tanggal', $year)
            ->where('tipe', 'kas_keluar')
            ->selectRaw('kategori, SUM(nominal) as total')
            ->groupBy('kategori')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->kategori => (float) $item->total];
            });

        // Monthly cash flow data
        $monthlyInflows = Transaction::whereYear('tanggal', $year)
            ->where('tipe', 'kas_masuk')
            ->selectRaw('MONTH(tanggal) as bulan, SUM(nominal) as total')
            ->groupBy('bulan')
            ->pluck('total', 'bulan')
            ->toArray();

        $monthlyOutflows = Transaction::whereYear('tanggal', $year)
            ->where('tipe', 'kas_keluar')
            ->selectRaw('MONTH(tanggal) as bulan, SUM(nominal) as total')
            ->groupBy('bulan')
            ->pluck('total', 'bulan')
            ->toArray();

        $monthlyData = [];
        for ($m = 1; $m <= 12; $m++) {
            $in = (float) ($monthlyInflows[$m] ?? 0);
            $out = (float) ($monthlyOutflows[$m] ?? 0);
            
            // Get Indonesian month name
            $monthName = match ($m) {
                1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
            };
            
            $monthlyData[] = [
                'bulan' => $monthName,
                'inflow' => $in,
                'outflow' => $out,
                'net' => $in - $out
            ];
        }

        $totalInflow = (float) Transaction::whereYear('tanggal', $year)->where('tipe', 'kas_masuk')->sum('nominal');
        $totalOutflow = (float) Transaction::whereYear('tanggal', $year)->where('tipe', 'kas_keluar')->sum('nominal');

        return response()->json([
            'inflowCategories' => $inflowCategories,
            'outflowCategories' => $outflowCategories,
            'monthly' => $monthlyData,
            'totalInflow' => $totalInflow,
            'totalOutflow' => $totalOutflow,
            'netCashFlow' => $totalInflow - $totalOutflow
        ]);
    }

    public function ledger(Request $request): JsonResponse
    {
        $year = $request->input('tahun', Carbon::now()->year);

        // Get all transactions for the year, grouped by category
        $transactions = Transaction::whereYear('tanggal', $year)
            ->with(['event', 'vendor'])
            ->orderBy('tanggal', 'asc')
            ->get();

        $grouped = $transactions->groupBy('kategori');

        $ledgerData = [];
        foreach ($grouped as $kategori => $items) {
            $totalMasuk = (float) $items->where('tipe', 'kas_masuk')->sum('nominal');
            $totalKeluar = (float) $items->where('tipe', 'kas_keluar')->sum('nominal');

            $itemsList = $items->map(function ($item) {
                return [
                    'tanggal' => $item->tanggal ? $item->tanggal->format('Y-m-d') : null,
                    'nomor_transaksi' => $item->nomor_transaksi,
                    'deskripsi' => $item->deskripsi,
                    'tipe' => $item->tipe,
                    'nominal' => (float) $item->nominal,
                    'event' => $item->event ? $item->event->nama_event : null,
                    'vendor' => $item->vendor ? $item->vendor->nama : null
                ];
            });

            $ledgerData[] = [
                'kategori' => $kategori,
                'totalMasuk' => $totalMasuk,
                'totalKeluar' => $totalKeluar,
                'saldoAkhir' => $totalMasuk - $totalKeluar,
                'transactions' => $itemsList
            ];
        }

        return response()->json([
            'ledger' => $ledgerData
        ]);
    }
}
