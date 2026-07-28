<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inactivity_settings', function (Blueprint $table) {
            $table->id();
            // Days of no posts/replies before first warning
            $table->unsignedInteger('inactivity_days')->default(30);
            // Days after first warning before second warning
            $table->unsignedInteger('second_warning_days')->default(7);
            // Days after second warning before auto-blacklist
            $table->unsignedInteger('blacklist_after_days')->default(7);
            // How long the auto-blacklist lasts
            $table->unsignedInteger('blacklist_duration_days')->default(30);
            $table->timestamps();
        });

        // Seed default row
        DB::table('inactivity_settings')->insert([
            'inactivity_days'       => 30,
            'second_warning_days'   => 7,
            'blacklist_after_days'  => 7,
            'blacklist_duration_days' => 30,
            'created_at'            => now(),
            'updated_at'            => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('inactivity_settings');
    }
};
