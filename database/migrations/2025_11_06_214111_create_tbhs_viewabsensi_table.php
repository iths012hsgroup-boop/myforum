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
        Schema::create('tbhs_viewabsensi', function (Blueprint $table) {
            $table->id();
            $table->string('id_admin', 50);
            $table->integer('tahun');
            $table->integer('bulan'); // 1–12
            $table->json('keterangan')->nullable(); // JSON: { "01": "HADIR", ... }
            $table->timestamps();

            $table->unique(['id_admin', 'tahun', 'bulan']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbhs_viewabsensi');
    }
};
