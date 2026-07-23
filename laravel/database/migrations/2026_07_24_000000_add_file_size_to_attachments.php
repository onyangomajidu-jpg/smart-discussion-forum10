<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->unsignedBigInteger('file_size')->nullable()->after('file_name');
        });
        Schema::table('private_messages', function (Blueprint $table) {
            $table->unsignedBigInteger('file_size')->nullable()->after('file_name');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn('file_size');
        });
        Schema::table('private_messages', function (Blueprint $table) {
            $table->dropColumn('file_size');
        });
    }
};
