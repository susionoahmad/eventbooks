<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Set nominal_gross = nominal untuk data transaksi lama
        DB::table('transactions')
            ->whereNull('nominal_gross')
            ->update([
                'nominal_gross' => DB::raw('nominal')
            ]);
    }

    public function down(): void
    {
        // Tidak perlu rollback untuk data backfill
    }
};
