<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TransactionRequest;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use App\Models\Tax;
use App\Services\TaxCalculationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Carbon\Carbon;

class TransactionController extends Controller
{
    protected TaxCalculationService $taxService;

    public function __construct(TaxCalculationService $taxService)
    {
        $this->taxService = $taxService;
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Transaction::with(['event', 'vendor']);

        // Filters
        if ($request->filled('tipe')) {
            $query->where('tipe', $request->input('tipe'));
        }
        if ($request->filled('event_id')) {
            $query->where('event_id', $request->input('event_id'));
        }
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('tanggal', [$request->input('start_date'), $request->input('end_date')]);
        }

        $transactions = $query->orderBy('tanggal', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(15);

        return TransactionResource::collection($transactions);
    }

    public function store(TransactionRequest $request): JsonResponse
    {
        $validated = $request->validated();
        
        $transaction = DB::transaction(function () use ($validated, $request) {
            $tenantId = Auth::user()->tenant_id;
            
            // Auto generate transaction number (TRX-YYYYMM-XXXX)
            $date = Carbon::parse($validated['tanggal']);
            $yearMonth = $date->format('Ym');
            
            $lastTrx = Transaction::where('nomor_transaksi', 'like', "TRX-{$yearMonth}-%")
                ->orderBy('nomor_transaksi', 'desc')
                ->first();

            $nextNumber = 1;
            if ($lastTrx) {
                $lastNumber = (int) substr($lastTrx->nomor_transaksi, -4);
                $nextNumber = $lastNumber + 1;
            }
            $nomorTransaksi = "TRX-{$yearMonth}-" . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

            $nominalGross = $validated['nominal'];
            $nominalNet = $nominalGross;
            $pphAmount = 0;
            $ppnAmount = 0;

            $hasNpwp = !empty($validated['pihak_terkait_npwp']);
            if (!$hasNpwp && !empty($validated['vendor_id'])) {
                $vendor = \App\Models\Vendor::find($validated['vendor_id']);
                $hasNpwp = $vendor && !empty($vendor->npwp);
            }
            
            $pphCalc = null;
            $ppnCalc = null;
            $pphType = null;
            $ppnRate = 12.00;

            if ($validated['tipe'] === 'kas_keluar') {
                if ($request->boolean('calculate_pph23')) {
                    $pphCalc = $this->taxService->calculatePPh23($nominalGross, $hasNpwp);
                    $pphAmount = $pphCalc['nominal_pajak'];
                    $pphType = 'pph_23';
                } elseif ($request->boolean('calculate_pph21')) {
                    $pphCalc = $this->taxService->calculatePPh21Freelancer($nominalGross, $hasNpwp);
                    $pphAmount = $pphCalc['nominal_pajak'];
                    $pphType = 'pph_21';
                }

                if ($request->boolean('calculate_ppn_masukan')) {
                    $ppnRate = $this->taxService->getPPNRateForDate($validated['tanggal']);
                    $ppnCalc = $this->taxService->calculatePPN($nominalGross, $ppnRate);
                    $ppnAmount = $ppnCalc['nominal_pajak'];
                }

                // Net Payout Formula
                $nominalNet = $nominalGross + $ppnAmount - $pphAmount;
            }

            // Create Transaction with Net Payout
            $transaction = Transaction::create(array_merge($validated, [
                'tenant_id' => $tenantId,
                'nomor_transaksi' => $nomorTransaksi,
                'nominal' => $nominalNet,
                'nominal_gross' => $nominalGross
            ]));

            // Automatic Withholding Tax & PPN Masukan Calculations
            $masaPajak = $date->format('Y-m');

            if ($pphCalc) {
                Tax::create([
                    'tenant_id' => $tenantId,
                    'transaction_id' => $transaction->id,
                    'event_id' => $transaction->event_id,
                    'tipe_pajak' => $pphType,
                    'dpp' => $pphCalc['dpp'],
                    'tarif' => $pphCalc['tarif'],
                    'nominal_pajak' => $pphCalc['nominal_pajak'],
                    'pihak_terkait_nama' => $validated['pihak_terkait_nama'] ?? $transaction->vendor->nama_vendor ?? null,
                    'pihak_terkait_npwp' => $validated['pihak_terkait_npwp'] ?? $transaction->vendor->npwp ?? null,
                    'nomor_bukti_potong' => $validated['nomor_bukti_potong'] ?? null,
                    'kode_objek_pajak' => $validated['kode_objek_pajak'] ?? ($pphType === 'pph_23' ? '24-104-14' : '21-100-09'),
                    'masa_pajak' => $masaPajak,
                    'status' => 'terutang'
                ]);
            }

            if ($ppnCalc) {
                Tax::create([
                    'tenant_id' => $tenantId,
                    'transaction_id' => $transaction->id,
                    'event_id' => $transaction->event_id,
                    'tipe_pajak' => 'ppn_masukan',
                    'dpp' => $ppnCalc['dpp'],
                    'tarif' => $ppnRate,
                    'nominal_pajak' => $ppnCalc['nominal_pajak'],
                    'pihak_terkait_nama' => $validated['pihak_terkait_nama'] ?? $transaction->vendor->nama_vendor ?? null,
                    'pihak_terkait_npwp' => $validated['pihak_terkait_npwp'] ?? $transaction->vendor->npwp ?? null,
                    'nomor_faktur_pajak' => $validated['nomor_faktur_pajak'] ?? null,
                    'masa_pajak' => $masaPajak,
                    'status' => 'terutang'
                ]);
            }

            return $transaction;
        });

        return response()->json([
            'message' => 'Transaction recorded successfully',
            'data' => new TransactionResource($transaction->load(['event', 'vendor']))
        ], 201);
    }

    public function show(Transaction $transaction): TransactionResource
    {
        return new TransactionResource($transaction->load(['event', 'vendor']));
    }

    public function destroy(Transaction $transaction): JsonResponse
    {
        // For audit trail compliance, transactions usually aren't deleted in SaaS, but we support it for MVP
        $transaction->delete();

        return response()->json([
            'message' => 'Transaction deleted successfully'
        ], 200);
    }
}
