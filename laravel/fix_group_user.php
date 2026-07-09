<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

if (!Schema::hasColumn('group_user', 'created_at')) {
    Schema::table('group_user', function (Blueprint $table) {
        $table->timestamps();
    });
    echo "Added timestamps to group_user.\n";
} else {
    echo "Timestamps already exist.\n";
}
