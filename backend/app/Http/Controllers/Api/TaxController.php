<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TaxResource;
use App\Models\Tax;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TaxController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Tax::with(['transaction', 'event', 'invoice']);

        if ($request->filled('tipe_pajak')) {
            $query->where('tipe_pajak', $request->input('tipe_pajak'));
        }
        if ($request->filled('masa_pajak')) {
            $query->where('masa_pajak', $request->input('masa_pajak'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $taxes = $query->orderBy('masa_pajak', 'desc')->paginate(15);

        return TaxResource::collection($taxes);
    }

    /**
     * Group and summarize taxes by tax period (masa_pajak)
     */
    public function summary(Request $request): JsonResponse
    {
        $summary = Tax::select(
            'masa_pajak',
            DB::raw("SUM(CASE WHEN tipe_pajak = 'ppn_keluaran' THEN nominal_pajak ELSE 0 END) as total_ppn_keluaran"),
            DB::raw("SUM(CASE WHEN tipe_pajak = 'ppn_masukan' THEN nominal_pajak ELSE 0 END) as total_ppn_masukan"),
            DB::raw("SUM(CASE WHEN tipe_pajak = 'pph_21' THEN nominal_pajak ELSE 0 END) as total_pph_21"),
            DB::raw("SUM(CASE WHEN tipe_pajak = 'pph_23' THEN nominal_pajak ELSE 0 END) as total_pph_23"),
            DB::raw("SUM(CASE WHEN status = 'terutang' THEN nominal_pajak ELSE 0 END) as total_terutang"),
            DB::raw("SUM(CASE WHEN status = 'dibayar' THEN nominal_pajak ELSE 0 END) as total_dibayar")
        )
        ->groupBy('masa_pajak')
        ->orderBy('masa_pajak', 'desc')
        ->get();

        return response()->json([
            'data' => $summary
        ]);
    }

    /**
     * Mark tax payment status as 'dibayar' and post to general ledger.
     */
    public function updateStatus(Request $request, Tax $tax): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:terutang,dibayar'
        ]);

        $targetStatus = $request->input('status');

        DB::transaction(function () use ($tax, $targetStatus) {
            if ($targetStatus === 'dibayar') {
                if (!$tax->payment_transaction_id) {
                    // Create Kas Keluar Transaction in General Ledger
                    $date = \Carbon\Carbon::now();
                    $yearMonth = $date->format('Ym');

                    $lastTrx = \App\Models\Transaction::where('nomor_transaksi', 'like', "TRX-{$yearMonth}-%")
                        ->orderBy('nomor_transaksi', 'desc')
                        ->first();

                    $nextNumber = 1;
                    if ($lastTrx) {
                        $lastNumber = (int) substr($lastTrx->nomor_transaksi, -4);
                        $nextNumber = $lastNumber + 1;
                    }
                    $nomorTransaksi = "TRX-{$yearMonth}-" . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

                    $labelPajak = match ($tax->tipe_pajak) {
                        'ppn_keluaran' => 'PPN Keluaran',
                        'ppn_masukan' => 'PPN Masukan',
                        'pph_21' => 'PPh 21',
                        'pph_23' => 'PPh 23',
                        default => 'Pajak'
                    };

                    $refDoc = $tax->nomor_bukti_potong ?: ($tax->nomor_faktur_pajak ?: '');
                    $desc = "Penyetoran " . $labelPajak . " Masa " . $tax->masa_pajak;
                    if ($refDoc) {
                        $desc .= " (Ref: " . $refDoc . ")";
                    }

                    $transaction = \App\Models\Transaction::create([
                        'tenant_id' => $tax->tenant_id,
                        'event_id' => $tax->event_id,
                        'nomor_transaksi' => $nomorTransaksi,
                        'tanggal' => $date->format('Y-m-d'),
                        'tipe' => 'kas_keluar',
                        'kategori' => 'pajak',
                        'deskripsi' => $desc,
                        'nominal' => $tax->nominal_pajak,
                        'nominal_gross' => $tax->nominal_pajak,
                        'metode_pembayaran' => 'transfer_bank'
                    ]);

                    $tax->payment_transaction_id = $transaction->id;
                }
            } else {
                // Revert status to 'terutang', delete the ledger entry
                if ($tax->payment_transaction_id) {
                    $transaction = \App\Models\Transaction::find($tax->payment_transaction_id);
                    if ($transaction) {
                        $transaction->delete();
                    }
                    $tax->payment_transaction_id = null;
                }
            }

            $tax->status = $targetStatus;
            $tax->save();
        });

        return response()->json([
            'message' => 'Status pajak berhasil diperbarui dan dicatat ke ledger',
            'data' => new TaxResource($tax->load(['transaction', 'event', 'invoice']))
        ], 200);
    }

    /**
     * Upload file dokumen arsip pajak (Bukti Potong / Faktur Pajak).
     * Tipe dokumen disesuaikan otomatis berdasarkan tipe_pajak.
     */
    public function uploadArsip(Request $request, Tax $tax): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120', // max 5MB
        ]);

        // Hapus file lama jika ada
        if ($tax->file_arsip && Storage::disk('public')->exists($tax->file_arsip)) {
            Storage::disk('public')->delete($tax->file_arsip);
        }

        $file = $request->file('file');
        $filename = time() . '_' . $file->getClientOriginalName();
        $path = $file->storeAs('tax-documents/' . $tax->masa_pajak, $filename, 'public');

        $tax->update([
            'file_arsip' => $path,
            'nama_file_arsip' => $file->getClientOriginalName(),
        ]);

        return response()->json([
            'message' => 'Dokumen berhasil diunggah',
            'data' => new TaxResource($tax->load(['transaction', 'event', 'invoice']))
        ], 200);
    }

    /**
     * Hapus file dokumen arsip pajak.
     */
    public function deleteArsip(Tax $tax): JsonResponse
    {
        if ($tax->file_arsip && Storage::disk('public')->exists($tax->file_arsip)) {
            Storage::disk('public')->delete($tax->file_arsip);
        }

        $tax->update([
            'file_arsip' => null,
            'nama_file_arsip' => null,
        ]);

        return response()->json([
            'message' => 'Dokumen berhasil dihapus',
            'data' => new TaxResource($tax->load(['transaction', 'event', 'invoice']))
        ], 200);
    }
}
