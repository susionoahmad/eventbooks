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
            $table->decimal('maps_btn_top', 5, 2)->nullable();
            $table->decimal('maps_btn_left', 5, 2)->nullable();
            $table->decimal('maps_btn_width', 5, 2)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('event_invitations', function (Blueprint $table) {
            $table->dropColumn(['maps_btn_top', 'maps_btn_left', 'maps_btn_width']);
        });
    }
};
