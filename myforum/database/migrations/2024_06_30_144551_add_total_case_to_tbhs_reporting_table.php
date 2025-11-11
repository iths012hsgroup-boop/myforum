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
        Schema::table('tbhs_reporting', function (Blueprint $table) {
            $table->integer('total_case')->after('bersalah_high')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbhs_reporting', function (Blueprint $table) {
            $table->dropColumn('total_case');
        });
    }
};
