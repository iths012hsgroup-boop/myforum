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
        Schema::create('tbhs_reporting', function (Blueprint $table) {
            $table->id();
            $table->text('id_staff');
            $table->string('nama_staff');
            $table->string('periode');
            $table->string('site_situs');
            $table->integer('tidak_bersalah');
            $table->integer('bersalah_low');
            $table->integer('bersalah_medium');
            $table->integer('bersalah_high');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbhs_reporting');
    }
};
