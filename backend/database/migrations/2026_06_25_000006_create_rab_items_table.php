<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rab_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('event_id')->constrained('events')->onDelete('cascade');
            $table->string('kategori'); // e.g. Dekorasi, Catering, Sound System
            $table->string('deskripsi');
            $table->decimal('qty', 10, 2)->default(1.00);
            $table->decimal('harga', 15, 2)->default(0.00);
            $table->decimal('subtotal', 15, 2)->virtualAs('qty * harga');
            $table->timestamps();

            $table->index(['tenant_id', 'event_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rab_items');
    }
};
