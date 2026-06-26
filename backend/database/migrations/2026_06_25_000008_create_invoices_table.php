<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('client_id')->constrained('clients');
            $table->foreignId('event_id')->constrained('events')->onDelete('cascade');
            $table->string('nomor_invoice', 50);
            $table->date('tanggal');
            $table->date('jatuh_tempo');
            $table->enum('jenis_invoice', ['dp', 'termin', 'pelunasan'])->default('termin');
            $table->decimal('subtotal', 15, 2)->default(0.00);
            $table->decimal('ppn', 15, 2)->default(0.00);
            $table->decimal('total', 15, 2)->virtualAs('subtotal + ppn');
            $table->enum('status', ['belum_bayar', 'sebagian', 'lunas', 'batal'])->default('belum_bayar');
            $table->timestamps();

            $table->unique(['tenant_id', 'nomor_invoice']);
            $table->index(['tenant_id', 'status', 'jatuh_tempo']);
            $table->index(['tenant_id', 'event_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
