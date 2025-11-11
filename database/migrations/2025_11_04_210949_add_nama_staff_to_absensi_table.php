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
        Schema::table('tbhs_absensi', function (Blueprint $table) {
            // Menambahkan kolom nama_staff setelah id_admin
            $table->string('nama_staff', 255)->after('id_admin')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbhs_absensi', function (Blueprint $table) {
            // Menghapus kolom jika rollback
            $table->dropColumn('nama_staff');
        });
    }
};