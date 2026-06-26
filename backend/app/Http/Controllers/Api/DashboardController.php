<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Transaction;
use App\Models\Invoice;
use App\Models\RabItem;
use App\Models\Tax;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function summary(): JsonResponse
    {
        $now = Carbon::now();
        
        $totalEvent = Event::count();
        $eventAktif = Event::whereIn('status', ['negosiasi', 'dp', 'berjalan'])->count();
        
        $pendapatanBulanIni = (float) Transaction::where('tipe', 'kas_masuk')
            ->whereMonth('tanggal', $now->month)
            ->whereYear('tanggal', $now->year)
            ->sum('nominal');
            
        $pengeluaranBulanIni = (float) Transaction::where('tipe', 'kas_keluar')
            ->whereMonth('tanggal', $now->month)
            ->whereYear('tanggal', $now->year)
            ->sum('nominal');
            
        $labaBersih = $pendapatanBulanIni - $pengeluaranBulanIni;
        
        // Sum of all outstanding invoice amounts
        $invoices = Invoice::get();
        $piutangKlien = (float) $invoices->sum(fn($inv) => $inv->outstanding_amount);
        
        // Hutang Vendor = Total RAB Budget - Total Paid to Vendors
        $totalRab = (float) RabItem::sum('subtotal');
        $paidVendor = (float) Transaction::where('tipe', 'kas_keluar')
            ->where('kategori', 'pembayaran_vendor')
            ->sum('nominal');
        $hutangVendor = max(0.00, $totalRab - $paidVendor);
        
        $pajakTerutang = (float) Tax::where('status', 'terutang')->sum('nominal_pajak');

        return response()->json([
            'total_event' => $totalEvent,
            'event_aktif' => $eventAktif,
            'pendapatan_bulan_ini' => $pendapatanBulanIni,
            'pengeluaran_bulan_ini' => $pengeluaranBulanIni,
            'laba_bersih' => $labaBersih,
            'piutang_klien' => $piutangKlien,
            'hutang_vendor' => $hutangVendor,
            'pajak_terutang' => $pajakTerutang
        ]);
    }

    public function eventProfitability(): JsonResponse
    {
        $events = Event::get();
        
        $profitability = $events->map(function ($event) {
            $totalCost = (float) Transaction::where('event_id', $event->id)
                ->where('tipe', 'kas_keluar')
                ->sum('nominal');
                
            $taxPaid = (float) Tax::where('event_id', $event->id)
                ->sum('nominal_pajak');
                
            $labaBersih = (float) $event->nilai_kontrak - $totalCost - $taxPaid;
            $margin = $event->nilai_kontrak > 0 ? ($labaBersih / (float) $event->nilai_kontrak) * 100 : 0.00;
            
            return [
                'event_id' => $event->id,
                'nama_event' => $event->nama_event,
                'jenis_event' => $event->jenis_event,
                'nilai_kontrak' => (float) $event->nilai_kontrak,
                'total_biaya' => $totalCost,
                'pajak_dikeluarkan' => $taxPaid,
                'laba_bersih' => $labaBersih,
                'margin_persentase' => round($margin, 2)
            ];
        })->sortByDesc('laba_bersih')->values()->toArray();
        
        // Add ranking index
        foreach ($profitability as $index => &$item) {
            $item['ranking'] = $index + 1;
        }

        return response()->json($profitability);
    }

    public function cashFlowByMethod(): JsonResponse
    {
        $methods = ['cash', 'transfer_bank', 'card', 'e_wallet'];
        $methodLabels = [
            'cash'          => 'Tunai',
            'transfer_bank' => 'Transfer Bank',
            'card'          => 'Kartu Debit/Kredit',
            'e_wallet'      => 'Dompet Digital',
        ];

        // Grand totals for percentage calculation
        $totalMasuk  = (float) Transaction::where('tipe', 'kas_masuk')->sum('nominal');
        $totalKeluar = (float) Transaction::where('tipe', 'kas_keluar')->sum('nominal');

        $byMethod = [];
        foreach ($methods as $method) {
            $masuk  = (float) Transaction::where('tipe', 'kas_masuk')
                ->where('metode_pembayaran', $method)
                ->sum('nominal');
            $keluar = (float) Transaction::where('tipe', 'kas_keluar')
                ->where('metode_pembayaran', $method)
                ->sum('nominal');

            $byMethod[] = [
                'metode'     => $method,
                'label'      => $methodLabels[$method],
                'kas_masuk'  => $masuk,
                'kas_keluar' => $keluar,
                'net'        => $masuk - $keluar,
                'pct_masuk'  => $totalMasuk  > 0 ? round(($masuk  / $totalMasuk)  * 100, 1) : 0,
                'pct_keluar' => $totalKeluar > 0 ? round(($keluar / $totalKeluar) * 100, 1) : 0,
            ];
        }

        // Monthly trend — last 6 months (all methods combined)
        $trend = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $masuk  = (float) Transaction::where('tipe', 'kas_masuk')
                ->whereMonth('tanggal', $month->month)
                ->whereYear('tanggal', $month->year)
                ->sum('nominal');
            $keluar = (float) Transaction::where('tipe', 'kas_keluar')
                ->whereMonth('tanggal', $month->month)
                ->whereYear('tanggal', $month->year)
                ->sum('nominal');

            $trend[] = [
                'bulan'      => $month->locale('id')->isoFormat('MMM YY'),
                'bulan_num'  => $month->format('Y-m'),
                'kas_masuk'  => $masuk,
                'kas_keluar' => $keluar,
                'net'        => $masuk - $keluar,
            ];
        }

        // Current-month breakdown by method
        $now = Carbon::now();
        $bulanIniByMethod = [];
        foreach ($methods as $method) {
            $masuk  = (float) Transaction::where('tipe', 'kas_masuk')
                ->where('metode_pembayaran', $method)
                ->whereMonth('tanggal', $now->month)
                ->whereYear('tanggal', $now->year)
                ->sum('nominal');
            $keluar = (float) Transaction::where('tipe', 'kas_keluar')
                ->where('metode_pembayaran', $method)
                ->whereMonth('tanggal', $now->month)
                ->whereYear('tanggal', $now->year)
                ->sum('nominal');

            $bulanIniByMethod[] = [
                'metode'     => $method,
                'label'      => $methodLabels[$method],
                'kas_masuk'  => $masuk,
                'kas_keluar' => $keluar,
                'net'        => $masuk - $keluar,
            ];
        }

        return response()->json([
            'total_masuk'         => $totalMasuk,
            'total_keluar'        => $totalKeluar,
            'net_total'           => $totalMasuk - $totalKeluar,
            'by_method'           => $byMethod,
            'trend_6_bulan'       => $trend,
            'bulan_ini_by_method' => $bulanIniByMethod,
        ]);
    }
}
