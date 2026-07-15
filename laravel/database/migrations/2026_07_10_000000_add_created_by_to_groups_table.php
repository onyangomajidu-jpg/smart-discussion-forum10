<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('groups', 'created_by')) {
            Schema::table('groups', function (Blueprint $table) {
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            });
        }
        if (!Schema::hasColumn('groups', 'is_private')) {
            Schema::table('groups', function (Blueprint $table) {
                $table->boolean('is_private')->default(false);
            });
        }
    }

    public function down(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->dropColumn(['created_by', 'is_private']);
        });
    }
};
