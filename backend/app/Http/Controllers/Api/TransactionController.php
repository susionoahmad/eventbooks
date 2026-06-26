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

            // Create Transaction
            $transaction = Transaction::create(array_merge($validated, [
                'tenant_id' => $tenantId,
                'nomor_transaksi' => $nomorTransaksi
            ]));

            // Automatic Withholding Tax Calculations
            $masaPajak = $date->format('Y-m');

            if ($request->boolean('calculate_pph23')) {
                // Determine NPWP status
                $hasNpwp = !empty($validated['pihak_terkait_npwp']);
                
                $pphCalc = $this->taxService->calculatePPh23($transaction->nominal, $hasNpwp);
                
                Tax::create([
                    'tenant_id' => $tenantId,
                    'transaction_id' => $transaction->id,
                    'event_id' => $transaction->event_id,
                    'tipe_pajak' => 'pph_23',
                    'dpp' => $pphCalc['dpp'],
                    'tarif' => $pphCalc['tarif'],
                    'nominal_pajak' => $pphCalc['nominal_pajak'],
                    'pihak_terkait_nama' => $validated['pihak_terkait_nama'] ?? $transaction->vendor->nama_vendor ?? null,
                    'pihak_terkait_npwp' => $validated['pihak_terkait_npwp'] ?? $transaction->vendor->npwp ?? null,
                    'masa_pajak' => $masaPajak,
                    'status' => 'terutang'
                ]);
            } elseif ($request->boolean('calculate_pph21')) {
                $hasNpwp = !empty($validated['pihak_terkait_npwp']);
                
                $pphCalc = $this->taxService->calculatePPh21Freelancer($transaction->nominal, $hasNpwp);
                
                Tax::create([
                    'tenant_id' => $tenantId,
                    'transaction_id' => $transaction->id,
                    'event_id' => $transaction->event_id,
                    'tipe_pajak' => 'pph_21',
                    'dpp' => $pphCalc['dpp'],
                    'tarif' => $pphCalc['tarif'],
                    'nominal_pajak' => $pphCalc['nominal_pajak'],
                    'pihak_terkait_nama' => $validated['pihak_terkait_nama'] ?? null,
                    'pihak_terkait_npwp' => $validated['pihak_terkait_npwp'] ?? null,
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
