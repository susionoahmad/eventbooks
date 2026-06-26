<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('event_id')->nullable()->constrained('events')->onDelete('cascade');
            $table->string('nama_dokumen');
            $table->enum('tipe_dokumen', ['kontrak', 'invoice', 'kwitansi', 'faktur_pajak', 'bukti_transfer']);
            $table->string('file_path');
            $table->integer('file_size')->nullable();
            $table->string('file_type', 50)->nullable(); // mime-type
            $table->foreignId('uploaded_by')->constrained('users');
            $table->timestamps();

            $table->index(['tenant_id', 'event_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
