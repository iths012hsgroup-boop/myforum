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
        Schema::create('tbhs_log_jabatan', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_parent_id')->nullable();
            $table->string('id_admin');
            $table->unsignedBigInteger('posisi_kerja')->nullable();
            $table->string('nama_posisi');
            $table->unsignedBigInteger('id_jabatan')->nullable();
            $table->string('nama_jabatan');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbhs_log_jabatan');
    }
};
