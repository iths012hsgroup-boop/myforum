<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbhs_absensi', function (Blueprint $table) {
            $table->date('cuti_start')->nullable()->after('tanggal');
            $table->date('cuti_end')->nullable()->after('cuti_start');
        });
    }

    public function down(): void
    {
        Schema::table('tbhs_absensi', function (Blueprint $table) {
            $table->dropColumn(['cuti_start', 'cuti_end']);
        });
    }
};
