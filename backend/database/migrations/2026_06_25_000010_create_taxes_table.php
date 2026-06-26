<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('taxes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('transaction_id')->nullable()->constrained('transactions')->onDelete('cascade');
            $table->foreignId('event_id')->nullable()->constrained('events')->onDelete('cascade');
            $table->enum('tipe_pajak', ['ppn_keluaran', 'ppn_masukan', 'pph_21', 'pph_23']);
            $table->decimal('dpp', 15, 2)->default(0.00);
            $table->decimal('tarif', 5, 2)->default(0.00);
            $table->decimal('nominal_pajak', 15, 2)->default(0.00);
            $table->string('pihak_terkait_nama')->nullable();
            $table->string('pihak_terkait_npwp', 20)->nullable();
            $table->string('masa_pajak', 7); // Format: YYYY-MM
            $table->enum('status', ['terutang', 'dibayar'])->default('terutang');
            $table->timestamps();

            $table->index(['tenant_id', 'tipe_pajak', 'masa_pajak', 'status']);
            $table->index(['tenant_id', 'event_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taxes');
    }
};
