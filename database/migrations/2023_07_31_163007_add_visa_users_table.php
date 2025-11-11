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
        Schema::table('tbhs_users', function (Blueprint $table) {
            $table->string('nomor_visa')->after('tanggal_join')->nullable();
            $table->date('masa_aktif_visa')->after('nomor_visa')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbhs_users', function (Blueprint $table) {
            $table->dropColumn('nomor_visa');
            $table->dropColumn('masa_aktif_visa');
        });
    }
};
