<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('npwp', 30)->nullable()->change();
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->string('npwp', 30)->nullable()->change();
        });

        Schema::table('vendors', function (Blueprint $table) {
            $table->string('npwp', 30)->nullable()->change();
        });

        Schema::table('taxes', function (Blueprint $table) {
            $table->string('pihak_terkait_npwp', 30)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('npwp', 20)->nullable()->change();
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->string('npwp', 20)->nullable()->change();
        });

        Schema::table('vendors', function (Blueprint $table) {
            $table->string('npwp', 20)->nullable()->change();
        });

        Schema::table('taxes', function (Blueprint $table) {
            $table->string('pihak_terkait_npwp', 20)->nullable()->change();
        });
    }
};
