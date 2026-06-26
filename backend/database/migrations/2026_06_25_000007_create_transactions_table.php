<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('event_id')->nullable()->constrained('events')->onDelete('set null');
            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->onDelete('set null');
            $table->string('nomor_transaksi', 50);
            $table->date('tanggal');
            $table->enum('tipe', ['kas_masuk', 'kas_keluar']);
            $table->enum('kategori', [
                'dp_event', 'pelunasan_event', 'pendapatan_lain',
                'pembayaran_vendor', 'transportasi', 'konsumsi', 'operasional', 'marketing'
            ]);
            $table->text('deskripsi');
            $table->decimal('nominal', 15, 2)->default(0.00);
            $table->enum('metode_pembayaran', ['cash', 'transfer_bank', 'card', 'e_wallet'])->default('transfer_bank');
            $table->timestamps();

            $table->unique(['tenant_id', 'nomor_transaksi']);
            $table->index(['tenant_id', 'tipe', 'tanggal']);
            $table->index(['tenant_id', 'event_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
