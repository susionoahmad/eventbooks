<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('kode_vendor', 50);
            $table->string('nama_vendor');
            $table->enum('kategori', [
                'dekorasi', 'sound_system', 'lighting', 'catering',
                'venue', 'talent', 'mc', 'dokumentasi', 'transportasi', 'lainnya'
            ])->default('lainnya');
            $table->string('npwp', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('telepon', 50)->nullable();
            $table->text('alamat')->nullable();
            $table->timestamps();

            // Enforce unique vendor codes per tenant
            $table->unique(['tenant_id', 'kode_vendor']);
            $table->index(['tenant_id', 'kategori']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendors');
    }
};
