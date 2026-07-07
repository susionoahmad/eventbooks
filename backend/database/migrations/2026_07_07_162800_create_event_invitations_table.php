<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->onDelete('cascade');
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('title')->nullable();
            $table->text('date_time_info')->nullable();
            $table->text('maps_url')->nullable();
            $table->boolean('is_custom_template')->default(false);
            $table->string('preset_template')->default('classic');
            $table->string('template_background')->nullable();
            $table->string('background_color', 7)->default('#ffffff');
            $table->string('primary_color', 7)->default('#1a1a1a');
            $table->string('accent_color', 7)->default('#4f46e5');
            $table->string('text_color', 7)->default('#1a1a1a');
            $table->string('button_text_color', 7)->default('#ffffff');
            $table->string('font_family', 50)->default('Inter');
            $table->timestamps();

            $table->unique(['tenant_id', 'event_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_invitations');
    }
};
