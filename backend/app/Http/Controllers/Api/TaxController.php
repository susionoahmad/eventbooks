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

    /**
     * Get active tax alerts for unpaid taxes approaching due dates.
     */
    public function alerts(): JsonResponse
    {
        $unpaidTaxes = Tax::where('status', 'terutang')->get();
        $alerts = [];
        $today = \Carbon\Carbon::today();

        foreach ($unpaidTaxes as $tax) {
            try {
                $parts = explode('-', $tax->masa_pajak);
                if (count($parts) !== 2) continue;
                
                $year = (int)$parts[0];
                $month = (int)$parts[1];
                $nextMonthDate = \Carbon\Carbon::create($year, $month, 1)->addMonth();

                if (in_array($tax->tipe_pajak, ['pph_21', 'pph_23'])) {
                    // Setor PPh: 10th of next month
                    $payDeadline = \Carbon\Carbon::create($nextMonthDate->year, $nextMonthDate->month, 10);
                } else {
                    // PPN: End of next month (Setor & Lapor)
                    $payDeadline = \Carbon\Carbon::create($nextMonthDate->year, $nextMonthDate->month, 1)->endOfMonth();
                }

                $diffDays = $today->diffInDays($payDeadline, false);

                // Show warning alerts for due dates within 10 days
                if ($diffDays <= 10) {
                    $label = match ($tax->tipe_pajak) {
                        'ppn_keluaran' => 'PPN Keluaran',
                        'ppn_masukan' => 'PPN Masukan',
                        'pph_21' => 'PPh 21',
                        'pph_23' => 'PPh 23',
                        default => 'Pajak'
                    };

                    $dueText = $diffDays < 0 
                        ? 'Terlambat ' . abs($diffDays) . ' hari' 
                        : ($diffDays === 0 ? 'Hari ini!' : $diffDays . ' hari lagi');

                    $type = $diffDays <= 3 ? 'danger' : 'warning';

                    $alerts[] = [
                        'id' => $tax->id,
                        'tipe_pajak' => $tax->tipe_pajak,
                        'masa_pajak' => $tax->masa_pajak,
                        'nominal_pajak' => (float) $tax->nominal_pajak,
                        'deadline_date' => $payDeadline->format('Y-m-d'),
                        'remaining_days' => $diffDays,
                        'due_text' => $dueText,
                        'severity' => $type,
                        'message' => "{$label} Masa {$tax->masa_pajak} senilai " . number_format($tax->nominal_pajak, 0, ',', '.') . " belum disetor. Batas setor: " . $payDeadline->format('d M Y') . " ({$dueText})."
                    ];
                }
            } catch (\Exception $e) {
                // Ignore parsing errors
            }
        }

        // Sort alerts by urgency
        usort($alerts, function ($a, $b) {
            if ($a['remaining_days'] === $b['remaining_days']) {
                return $b['nominal_pajak'] <=> $a['nominal_pajak'];
            }
            return $a['remaining_days'] <=> $b['remaining_days'];
        });

        return response()->json($alerts);
    }

    /**
     * Get tax calendar events.
     */
    public function calendarEvents(Request $request): JsonResponse
    {
        $year = $request->input('year', \Carbon\Carbon::now()->year);
        $month = $request->input('month', \Carbon\Carbon::now()->month);

        $startDate = \Carbon\Carbon::create($year, $month, 1)->startOfMonth();

        // 1. Fetch actual taxes for this tenant
        $taxes = Tax::with(['event', 'invoice', 'transaction'])->get();
        $events = [];

        // General Indonesian tax guidelines
        $prevMonth = $startDate->copy()->subMonth();
        $prevMonthName = $prevMonth->translatedFormat('F Y');

        $events[] = [
            'id' => 'rule-pph-setor',
            'type' => 'guideline',
            'title' => 'Setor PPh Masa ' . $prevMonth->format('Y-m'),
            'description' => 'Batas akhir pembayaran & penyetoran PPh Pasal 21 dan 23 Masa Pajak ' . $prevMonthName,
            'date' => $startDate->copy()->day(10)->format('Y-m-d'),
            'color' => 'bg-amber-100 text-amber-800 border-amber-300 dark:bg-amber-950/40 dark:text-amber-400 dark:border-amber-900',
        ];

        $events[] = [
            'id' => 'rule-pph-lapor',
            'type' => 'guideline',
            'title' => 'Lapor PPh Masa ' . $prevMonth->format('Y-m'),
            'description' => 'Batas akhir pelaporan SPT Masa PPh Pasal 21 dan 23 Masa Pajak ' . $prevMonthName,
            'date' => $startDate->copy()->day(20)->format('Y-m-d'),
            'color' => 'bg-blue-100 text-blue-800 border-blue-300 dark:bg-blue-950/40 dark:text-blue-400 dark:border-blue-900',
        ];

        $events[] = [
            'id' => 'rule-ppn-lapor',
            'type' => 'guideline',
            'title' => 'Setor & Lapor PPN Masa ' . $prevMonth->format('Y-m'),
            'description' => 'Batas akhir penyetoran dan pelaporan SPT Masa PPN Masa Pajak ' . $prevMonthName,
            'date' => $startDate->copy()->endOfMonth()->format('Y-m-d'),
            'color' => 'bg-indigo-100 text-indigo-800 border-indigo-300 dark:bg-indigo-950/40 dark:text-indigo-400 dark:border-indigo-900',
        ];

        // 2. Put dynamic events based on actual tenant taxes
        foreach ($taxes as $tax) {
            try {
                $parts = explode('-', $tax->masa_pajak);
                if (count($parts) !== 2) continue;
                
                $tYear = (int)$parts[0];
                $tMonth = (int)$parts[1];
                $nextMonthDate = \Carbon\Carbon::create($tYear, $tMonth, 1)->addMonth();

                if (in_array($tax->tipe_pajak, ['pph_21', 'pph_23'])) {
                    $deadlineDate = \Carbon\Carbon::create($nextMonthDate->year, $nextMonthDate->month, 10);
                } else {
                    $deadlineDate = \Carbon\Carbon::create($nextMonthDate->year, $nextMonthDate->month, 1)->endOfMonth();
                }

                if ($deadlineDate->year == $year && $deadlineDate->month == $month) {
                    $label = match ($tax->tipe_pajak) {
                        'ppn_keluaran' => 'PPN Keluaran',
                        'ppn_masukan' => 'PPN Masukan',
                        'pph_21' => 'PPh 21',
                        'pph_23' => 'PPh 23',
                        default => 'Pajak'
                    };

                    $statusLabel = $tax->status === 'dibayar' ? 'LUNAS' : 'TERUTANG';
                    $colorClass = $tax->status === 'dibayar'
                        ? 'bg-emerald-100 text-emerald-800 border-emerald-300 dark:bg-emerald-950/40 dark:text-emerald-450 dark:border-emerald-900'
                        : 'bg-rose-100 text-rose-800 border-rose-300 dark:bg-rose-950/40 dark:text-rose-450 dark:border-rose-900';

                    $events[] = [
                        'id' => 'tax-' . $tax->id,
                        'type' => 'tax',
                        'tax_id' => $tax->id,
                        'title' => "{$label} ({$statusLabel})",
                        'description' => "Pajak {$label} Masa {$tax->masa_pajak} senilai Rp " . number_format($tax->nominal_pajak, 0, ',', '.') . " untuk pihak " . ($tax->pihak_terkait_nama ?: '-') . ". Status: {$tax->status}.",
                        'date' => $deadlineDate->format('Y-m-d'),
                        'color' => $colorClass,
                        'status' => $tax->status,
                        'nominal' => (float)$tax->nominal_pajak
                    ];
                }
            } catch (\Exception $e) {
                // Ignore errors
            }
        }

        return response()->json($events);
    }
}
