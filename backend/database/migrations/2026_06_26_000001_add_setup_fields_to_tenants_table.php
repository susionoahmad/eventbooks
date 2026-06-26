<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('name');
            $table->string('website')->nullable()->after('alamat');
            $table->string('kota', 100)->nullable()->after('alamat');
            $table->string('provinsi', 100)->nullable()->after('kota');
            $table->string('kode_pos', 10)->nullable()->after('provinsi');
            $table->enum('jenis_usaha', [
                'event_organizer',
                'wedding_organizer',
                'production_house',
                'lainnya'
            ])->default('event_organizer')->after('slug');
            $table->boolean('is_setup_complete')->default(false)->after('jenis_usaha');
            $table->enum('subscription_plan', ['trial', 'basic', 'pro'])->default('trial')->after('is_setup_complete');
            $table->timestamp('trial_ends_at')->nullable()->after('subscription_plan');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'slug',
                'website',
                'kota',
                'provinsi',
                'kode_pos',
                'jenis_usaha',
                'is_setup_complete',
                'subscription_plan',
                'trial_ends_at',
            ]);
        });
    }
};
