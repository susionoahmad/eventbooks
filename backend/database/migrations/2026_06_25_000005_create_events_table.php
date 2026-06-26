<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('client_id')->constrained('clients');
            $table->string('nomor_event', 50);
            $table->string('nama_event');
            $table->string('jenis_event', 100)->nullable();
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->text('lokasi')->nullable();
            $table->decimal('nilai_kontrak', 15, 2)->default(0.00);
            $table->enum('status', ['draft', 'negosiasi', 'dp', 'berjalan', 'selesai', 'batal'])->default('draft');
            $table->timestamps();

            // Enforce unique event numbering per tenant
            $table->unique(['tenant_id', 'nomor_event']);
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
