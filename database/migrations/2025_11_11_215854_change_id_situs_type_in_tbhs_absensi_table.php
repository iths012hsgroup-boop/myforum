<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbhs_absensi', function (Blueprint $table) {
            // dari INT → VARCHAR
            $table->string('id_situs')->change();
        });
    }

    public function down(): void
    {
        Schema::table('tbhs_absensi', function (Blueprint $table) {
            // rollback: VARCHAR → INT lagi
            $table->integer('id_situs')->change();
        });
    }
};
