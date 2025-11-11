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
        Schema::table('tbhs_forum', function (Blueprint $table) {
            $table->unsignedBigInteger('status_kesalahan')->after('created_by_name')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbhs_forum', function (Blueprint $table) {
            $table->dropColumn('status_kesalahan');
        });
    }
};
