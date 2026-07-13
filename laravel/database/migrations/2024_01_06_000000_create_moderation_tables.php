<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('warnings')) {
            Schema::create('warnings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('issued_by')->constrained('users')->cascadeOnDelete();
                $table->string('reason');
                $table->text('details')->nullable();
                $table->timestamp('resolved_at')->nullable();
                $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        } elseif (!Schema::hasColumn('warnings', 'resolved_at')) {
            Schema::table('warnings', function (Blueprint $table) {
                $table->timestamp('resolved_at')->nullable()->after('details');
                $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete()->after('resolved_at');
            });
        }

        if (!Schema::hasTable('blacklists')) {
            Schema::create('blacklists', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('banned_by')->constrained('users')->cascadeOnDelete();
                $table->string('reason');
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('blacklists');
        Schema::dropIfExists('warnings');
    }
};
