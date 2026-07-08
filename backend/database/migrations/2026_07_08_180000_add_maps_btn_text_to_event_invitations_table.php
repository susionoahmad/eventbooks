<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('event_invitations', function (Blueprint $table) {
            $table->string('maps_btn_text', 100)->nullable()->default('Buka Google Maps');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('event_invitations', function (Blueprint $table) {
            $table->dropColumn('maps_btn_text');
        });
    }
};
