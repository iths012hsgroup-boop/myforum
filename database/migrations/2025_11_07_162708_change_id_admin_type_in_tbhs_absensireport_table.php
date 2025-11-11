<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbhs_absensireport', function (Blueprint $table) {
            // ubah dari INT / unsignedBigInteger jadi VARCHAR(50)
            $table->string('id_admin', 50)->change();
        });
    }

    public function down(): void
    {
        Schema::table('tbhs_absensireport', function (Blueprint $table) {
            // kalau mau rollback, balik lagi ke integer (sesuaikan dengan tipe awalmu)
            $table->unsignedBigInteger('id_admin')->change();
        });
    }
};
