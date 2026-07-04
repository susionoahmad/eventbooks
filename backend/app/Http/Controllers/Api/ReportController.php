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
            
        // ─── BEBAN PERPAJAKAN ───────────────────────────────────────────────
        // PPN Net = Keluaran - Masukan (kewajiban setor ke DJP atas selisih PPN)
        $ppnKeluaran = (float) Tax::whereYear('created_at', $year)
            ->where('tipe_pajak', 'ppn_keluaran')
            ->sum('nominal_pajak');

        $ppnMasukan = (float) Tax::whereYear('created_at', $year)
            ->where('tipe_pajak', 'ppn_masukan')
            ->sum('nominal_pajak');

        // PPh 21 & PPh 23: perusahaan MEMOTONG dari pembayaran vendor/freelancer.
        // Nilai ini TIDAK ikut keluar ke vendor (sudah di-net di Net Payout Ledger),
        // sehingga harus diakui di sini sebagai beban pajak tersendiri agar L/R akurat.
        $pph21 = (float) Tax::whereYear('created_at', $year)
            ->where('tipe_pajak', 'pph_21')
            ->sum('nominal_pajak');

        $pph23 = (float) Tax::whereYear('created_at', $year)
            ->where('tipe_pajak', 'pph_23')
            ->sum('nominal_pajak');

        $bebanPajakPpn  = $ppnKeluaran - $ppnMasukan;
        $bebanPajakPph  = $pph21 + $pph23;
        $bebanPajakTotal = $bebanPajakPpn + $bebanPajakPph;

        return response()->json([
            'pendapatanKontrak'  => $pendapatanKontrak,
            'pendapatanLain'     => $pendapatanLain,
            'biayaVendor'        => $biayaVendor,
            'biayaOperasional'   => $biayaOperasional,
            'biayaMarketing'     => $biayaMarketing,
            'biayaKonsumsi'      => $biayaKonsumsi,
            // Breakdown beban perpajakan
            'bebanPajak'         => $bebanPajakTotal,
            'bebanPajakPpn'      => $bebanPajakPpn,
            'bebanPajakPph'      => $bebanPajakPph,
            'ppnKeluaran'        => $ppnKeluaran,
            'ppnMasukan'         => $ppnMasukan,
            'pph21'              => $pph21,
            'pph23'              => $pph23,
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

    public function eventsList(Request $request): JsonResponse
    {
        $year = $request->input('tahun', Carbon::now()->year);

        $events = \App\Models\Event::with('client')
            ->whereYear('tanggal_mulai', $year)
            ->get();

        $data = $events->map(function ($event) {
            $totalCost = (float) $event->transactions()->where('tipe', 'kas_keluar')->sum('nominal');
            $totalAnggaran = (float) $event->rabItems()->sum('subtotal');
            $labaBersih = (float) $event->nilai_kontrak - $totalCost;
            $margin = $event->nilai_kontrak > 0 ? ($labaBersih / (float) $event->nilai_kontrak) * 100 : 0.00;

            return [
                'id' => $event->id,
                'nomor_event' => $event->nomor_event,
                'nama_event' => $event->nama_event,
                'client_name' => $event->client->nama ?? '-',
                'tanggal_mulai' => $event->tanggal_mulai->format('Y-m-d'),
                'tanggal_selesai' => $event->tanggal_selesai->format('Y-m-d'),
                'nilai_kontrak' => (float) $event->nilai_kontrak,
                'total_anggaran' => $totalAnggaran,
                'total_realisasi' => $totalCost,
                'laba_bersih' => $labaBersih,
                'margin_persentase' => round($margin, 2),
                'status' => $event->status
            ];
        });

        return response()->json($data);
    }

    public function eventDetail(\App\Models\Event $event): JsonResponse
    {
        // Must belong to user's tenant
        $user = auth()->user();
        if ($event->tenant_id !== $user->tenant_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Event summary
        $totalCost = (float) $event->transactions()->where('tipe', 'kas_keluar')->sum('nominal');
        $totalAnggaran = (float) $event->rabItems()->sum('subtotal');
        $labaBersih = (float) $event->nilai_kontrak - $totalCost;
        $margin = $event->nilai_kontrak > 0 ? ($labaBersih / (float) $event->nilai_kontrak) * 100 : 0.00;

        $summary = [
            'id' => $event->id,
            'nomor_event' => $event->nomor_event,
            'nama_event' => $event->nama_event,
            'jenis_event' => $event->jenis_event,
            'client_name' => $event->client->nama ?? '-',
            'client_perusahaan' => $event->client->perusahaan ?? '-',
            'tanggal_mulai' => $event->tanggal_mulai->format('Y-m-d'),
            'tanggal_selesai' => $event->tanggal_selesai->format('Y-m-d'),
            'nilai_kontrak' => (float) $event->nilai_kontrak,
            'total_anggaran' => $totalAnggaran,
            'total_realisasi' => $totalCost,
            'laba_bersih' => $labaBersih,
            'margin_persentase' => round($margin, 2),
            'status' => $event->status
        ];

        // Detail Anggaran (RAB Items)
        $budgetDetails = $event->rabItems()
            ->orderBy('kategori', 'asc')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'kategori' => $item->kategori,
                    'deskripsi' => $item->deskripsi,
                    'qty' => (float) $item->qty,
                    'harga' => (float) $item->harga,
                    'subtotal' => (float) $item->subtotal,
                    'realisasi' => (float) $item->aktual_terbayar
                ];
            });

        // Detail Realisasi Biaya (Transactions of type kas_keluar)
        $realizationDetails = $event->transactions()
            ->where('tipe', 'kas_keluar')
            ->with('vendor')
            ->orderBy('tanggal', 'asc')
            ->get()
            ->map(function ($t) {
                return [
                    'id' => $t->id,
                    'tanggal' => $t->tanggal ? $t->tanggal->format('Y-m-d') : null,
                    'nomor_transaksi' => $t->nomor_transaksi,
                    'kategori' => $t->kategori,
                    'deskripsi' => $t->deskripsi,
                    'vendor_name' => $t->vendor->nama_vendor ?? '-',
                    'nominal' => (float) $t->nominal,
                    'metode_pembayaran' => $t->metode_pembayaran
                ];
            });

        // Detail Pendapatan Masuk (Transactions of type kas_masuk)
        $revenueDetails = $event->transactions()
            ->where('tipe', 'kas_masuk')
            ->orderBy('tanggal', 'asc')
            ->get()
            ->map(function ($t) {
                return [
                    'id' => $t->id,
                    'tanggal' => $t->tanggal ? $t->tanggal->format('Y-m-d') : null,
                    'nomor_transaksi' => $t->nomor_transaksi,
                    'kategori' => $t->kategori,
                    'deskripsi' => $t->deskripsi,
                    'nominal' => (float) $t->nominal,
                    'metode_pembayaran' => $t->metode_pembayaran
                ];
            });

        return response()->json([
            'summary' => $summary,
            'budget_details' => $budgetDetails,
            'realization_details' => $realizationDetails,
            'revenue_details' => $revenueDetails
        ]);
    }
}
