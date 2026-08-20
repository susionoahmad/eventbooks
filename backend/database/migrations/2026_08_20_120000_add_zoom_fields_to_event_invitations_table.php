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
            $table->text('zoom_url')->nullable()->after('maps_url');
            $table->string('zoom_btn_text', 100)->nullable()->default('Gabung Zoom')->after('maps_btn_height');
            $table->decimal('zoom_btn_top', 5, 2)->nullable()->after('zoom_btn_text');
            $table->decimal('zoom_btn_left', 5, 2)->nullable()->after('zoom_btn_top');
            $table->decimal('zoom_btn_width', 5, 2)->nullable()->after('zoom_btn_left');
            $table->decimal('zoom_btn_height', 5, 2)->nullable()->after('zoom_btn_width');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('event_invitations', function (Blueprint $table) {
            $table->dropColumn([
                'zoom_url',
                'zoom_btn_text',
                'zoom_btn_top',
                'zoom_btn_left',
                'zoom_btn_width',
                'zoom_btn_height',
            ]);
        });
    }
};
