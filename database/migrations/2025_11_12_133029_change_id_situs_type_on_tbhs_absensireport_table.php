<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('tbhs_absensireport', function (Blueprint $table) {
            // ubah dari INT ke VARCHAR(50)
            $table->string('id_situs', 50)->change();
        });
    }

    public function down()
    {
        Schema::table('tbhs_absensireport', function (Blueprint $table) {
            // balik lagi ke INT (sesuaikan unsigned / nullable kalau perlu)
            $table->integer('id_situs')->change();
        });
    }
};
