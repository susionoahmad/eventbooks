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
            $table->decimal('maps_btn_height', 5, 2)->nullable()->default(6.00);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('event_invitations', function (Blueprint $table) {
            $table->dropColumn('maps_btn_height');
        });
    }
};
