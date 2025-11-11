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
        Schema::create('tbhs_absensi', function (Blueprint $table) {
            $table->id(); 
            $table->string('id_admin', 50);
            $table->integer('id_situs');
            $table->date('tanggal');
            
            // --- Kolom Baru untuk Data Modal ---
            $table->string('status', 50)->nullable()->comment('Status kehadiran: TELAT, SAKIT, IZIN, TANPA KABAR, CUTI, atau HADIR');
            $table->text('remarks')->nullable()->comment('Catatan atau keterangan tambahan');
            // ------------------------------------
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbhs_absensi');
    }
};