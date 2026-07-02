<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Modify enum column 'kategori' in transactions table to include 'pajak'
        DB::statement("ALTER TABLE transactions MODIFY COLUMN kategori ENUM(
            'dp_event', 'pelunasan_event', 'pendapatan_lain',
            'pembayaran_vendor', 'transportasi', 'konsumsi', 'operasional', 'marketing', 'pajak'
        ) NOT NULL");
    }

    public function down(): void
    {
        // Revert enum column 'kategori'
        DB::statement("ALTER TABLE transactions MODIFY COLUMN kategori ENUM(
            'dp_event', 'pelunasan_event', 'pendapatan_lain',
            'pembayaran_vendor', 'transportasi', 'konsumsi', 'operasional', 'marketing'
        ) NOT NULL");
    }
};
