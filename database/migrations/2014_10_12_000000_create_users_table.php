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
        Schema::create('tbhs_users', function (Blueprint $table) {
            $table->id();
            $table->string('id_admin');
            $table->string('password');
            $table->unsignedBigInteger('id_situs')->default(0);
            $table->string('nama_staff')->nullable();
            $table->string('email')->unique();
            $table->string('nomor_paspor')->nullable();
            $table->date('masa_aktif_paspor')->nullable();
            $table->date('tanggal_join')->nullable();
            $table->unsignedBigInteger('posisi_kerja')->nullable();
            $table->unsignedBigInteger('id_jabatan')->nullable();
            $table->unsignedBigInteger('status')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbhs_users');
    }
};
