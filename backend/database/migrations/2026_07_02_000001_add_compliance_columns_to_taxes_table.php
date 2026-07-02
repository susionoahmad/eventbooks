<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('taxes', function (Blueprint $table) {
            $table->string('nomor_bukti_potong', 50)->nullable()->after('pihak_terkait_npwp');
            $table->string('nomor_faktur_pajak', 50)->nullable()->after('nomor_bukti_potong');
            $table->string('kode_objek_pajak', 20)->nullable()->after('nomor_faktur_pajak');
        });
    }

    public function down(): void
    {
        Schema::table('taxes', function (Blueprint $table) {
            $table->dropColumn(['nomor_bukti_potong', 'nomor_faktur_pajak', 'kode_objek_pajak']);
        });
    }
};
