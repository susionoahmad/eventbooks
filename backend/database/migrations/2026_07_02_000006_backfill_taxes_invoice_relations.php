<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Backfill invoice_id dan nomor_faktur_pajak untuk data ppn_keluaran lama
        $taxes = DB::table('taxes')
            ->where('tipe_pajak', 'ppn_keluaran')
            ->whereNull('invoice_id')
            ->get();

        foreach ($taxes as $tax) {
            // Cari invoice yang memiliki event_id yang sama
            $invoice = DB::table('invoices')
                ->where('event_id', $tax->event_id)
                ->first();

            if ($invoice) {
                DB::table('taxes')
                    ->where('id', $tax->id)
                    ->update([
                        'invoice_id' => $invoice->id,
                        'nomor_faktur_pajak' => $invoice->nomor_faktur_pajak
                    ]);
            }
        }
    }

    public function down(): void
    {
        // Tidak diperlukan rollback untuk backfill data
    }
};
