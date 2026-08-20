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
            $table->string('zoom_meeting_id', 100)->nullable()->after('zoom_url');
            $table->string('zoom_passcode', 100)->nullable()->after('zoom_meeting_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('event_invitations', function (Blueprint $table) {
            $table->dropColumn(['zoom_meeting_id', 'zoom_passcode']);
        });
    }
};
