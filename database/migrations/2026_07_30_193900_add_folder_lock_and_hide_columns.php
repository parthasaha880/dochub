<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('folders', function (Blueprint $table) {
            $table->boolean('is_locked')->default(false)->after('is_favorite')->index();
            $table->boolean('is_hidden')->default(false)->after('is_locked')->index();
            $table->uuid('locked_by')->nullable()->after('is_hidden');
            $table->timestamp('locked_at')->nullable()->after('locked_by');
        });
    }

    public function down(): void
    {
        Schema::table('folders', function (Blueprint $table) {
            $table->dropColumn(['is_locked', 'is_hidden', 'locked_by', 'locked_at']);
        });
    }
};
