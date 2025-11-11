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
        Schema::create('tbhs_absensireport', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_admin');
            $table->string('nama_staff', 100);
            $table->unsignedBigInteger('id_situs');
            $table->string('periode', 20);
            $table->integer('sakit')->default(0);
            $table->integer('izin')->default(0);
            $table->integer('telat')->default(0);
            $table->integer('tanpa_kabar')->default(0);
            $table->integer('cuti')->default(0);
            $table->integer('total_absensi')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbhs_absensireport');
    }
};
