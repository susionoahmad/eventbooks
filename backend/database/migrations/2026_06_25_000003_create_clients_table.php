<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('kode_klien', 50);
            $table->string('nama');
            $table->string('perusahaan')->nullable();
            $table->string('npwp', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('telepon', 50)->nullable();
            $table->text('alamat')->nullable();
            $table->timestamps();

            // Enforce unique client codes per tenant
            $table->unique(['tenant_id', 'kode_klien']);
            $table->index(['tenant_id', 'nama']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
