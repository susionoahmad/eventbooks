<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\InvoiceRequest;
use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\Transaction;
use App\Models\Tax;
use App\Services\TaxCalculationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Carbon\Carbon;

class InvoiceController extends Controller
{
    protected TaxCalculationService $taxService;

    public function __construct(TaxCalculationService $taxService)
    {
        $this->taxService = $taxService;
    }

    public function getNextCode(Request $request): JsonResponse
    {
        $date = $request->query('tanggal', date('Y-m-d'));
        $nextCode = Invoice::generateNextInvoiceNumber($request->user()->tenant_id, $date);
        return response()->json(['next_code' => $nextCode]);
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Invoice::with(['client', 'event']);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('event_id')) {
            $query->where('event_id', $request->input('event_id'));
        }

        $invoices = $query->orderBy('tanggal', 'desc')->paginate(15);

        return InvoiceResource::collection($invoices);
    }

    public function store(InvoiceRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $invoice = DB::transaction(function () use ($validated, $request) {
            $tenantId = Auth::user()->tenant_id;
            
            $subtotal = $validated['subtotal'];
            $ppn = 0.00;

            // Compute PPN if checked
            if ($request->boolean('apply_ppn')) {
                $ppnCalc = $this->taxService->calculatePPN($subtotal, 11.00); // Standard indonesian PPN is 11%
                $ppn = $ppnCalc['nominal_pajak'];
            }

            // Auto generate invoice number if not provided
            if (empty($validated['nomor_invoice'])) {
                $validated['nomor_invoice'] = Invoice::generateNextInvoiceNumber($tenantId, $validated['tanggal']);
            }

            // Create Invoice
            $invoice = Invoice::create(array_merge($validated, [
                'tenant_id' => $tenantId,
                'nomor_invoice' => $validated['nomor_invoice'],
                'ppn' => $ppn,
                'status' => 'belum_bayar'
            ]));

            // Write PPN tax ledger
            if ($ppn > 0) {
                $date = Carbon::parse($validated['tanggal']);
                Tax::create([
                    'tenant_id' => $tenantId,
                    'transaction_id' => null, // Will link when paid
                    'event_id' => $invoice->event_id,
                    'tipe_pajak' => 'ppn_keluaran',
                    'dpp' => $subtotal,
                    'tarif' => 11.00,
                    'nominal_pajak' => $ppn,
                    'pihak_terkait_nama' => $invoice->client->nama,
                    'pihak_terkait_npwp' => $invoice->client->npwp,
                    'masa_pajak' => $date->format('Y-m'),
                    'status' => 'terutang'
                ]);
            }

            return $invoice;
        });

        return response()->json([
            'message' => 'Invoice generated successfully',
            'data' => new InvoiceResource($invoice->load(['client', 'event']))
        ], 201);
    }

    public function show(Invoice $invoice): InvoiceResource
    {
        return new InvoiceResource($invoice->load(['client', 'event', 'payments.transaction']));
    }

    /**
     * Record client payments and link to cash-book
     */
    public function recordPayment(Request $request, Invoice $invoice): JsonResponse
    {
        $request->validate([
            'tanggal' => 'required|date',
            'nominal' => 'required|numeric|min:0.01',
            'metode_pembayaran' => 'required|in:cash,transfer_bank,card,e_wallet',
            'bukti_transfer' => 'nullable|string'
        ]);

        $nominal = $request->input('nominal');
        $outstanding = $invoice->outstanding_amount;

        if ($nominal > $outstanding) {
            return response()->json(['message' => 'Payment amount exceeds outstanding invoices'], 400);
        }

        DB::transaction(function () use ($request, $invoice, $nominal) {
            $tenantId = Auth::user()->tenant_id;
            
            // Auto generate Transaction ID
            $date = Carbon::parse($request->input('tanggal'));
            $yearMonth = $date->format('Ym');
            $lastTrx = Transaction::where('nomor_transaksi', 'like', "TRX-{$yearMonth}-%")->orderBy('nomor_transaksi', 'desc')->first();
            $nextNumber = $lastTrx ? ((int) substr($lastTrx->nomor_transaksi, -4)) + 1 : 1;
            $nomorTransaksi = "TRX-{$yearMonth}-" . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

            // Log Kas Masuk in Bookkeeping
            $kategori = $invoice->jenis_invoice === 'dp' ? 'dp_event' : 'pelunasan_event';
            $transaction = Transaction::create([
                'tenant_id' => $tenantId,
                'event_id' => $invoice->event_id,
                'nomor_transaksi' => $nomorTransaksi,
                'tanggal' => $request->input('tanggal'),
                'tipe' => 'kas_masuk',
                'kategori' => $kategori,
                'deskripsi' => "Pembayaran Invoice {$invoice->nomor_invoice}",
                'nominal' => $nominal,
                'metode_pembayaran' => $request->input('metode_pembayaran')
            ]);

            // Link Invoice Payment
            InvoicePayment::create([
                'tenant_id' => $tenantId,
                'invoice_id' => $invoice->id,
                'transaction_id' => $transaction->id,
                'tanggal' => $request->input('tanggal'),
                'nominal' => $nominal,
                'bukti_transfer' => $request->input('bukti_transfer')
            ]);

            // Update Invoice Status
            $newPaidTotal = $invoice->paid_amount + $nominal;
            if ($newPaidTotal >= $invoice->total) {
                $invoice->status = 'lunas';
            } else {
                $invoice->status = 'sebagian';
            }
            $invoice->save();
        });

        return response()->json([
            'message' => 'Invoice payment recorded successfully',
            'data' => new InvoiceResource($invoice->fresh())
        ], 200);
    }
}
