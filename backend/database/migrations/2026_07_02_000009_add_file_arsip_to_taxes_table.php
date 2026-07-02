<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('taxes', function (Blueprint $table) {
            $table->string('file_arsip', 500)->nullable()->after('kode_objek_pajak');
            $table->string('nama_file_arsip', 255)->nullable()->after('file_arsip');
        });
    }

    public function down(): void
    {
        Schema::table('taxes', function (Blueprint $table) {
            $table->dropColumn(['file_arsip', 'nama_file_arsip']);
        });
    }
};
