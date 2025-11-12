<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('tbhs_absensi', function (Blueprint $table) {
            $table->boolean('soft_delete')->default(0)->after('id'); // sesuaikan posisi
        });
    }

    public function down()
    {
        Schema::table('tbhs_absensi', function (Blueprint $table) {
            $table->dropColumn('soft_delete');
        });
    }
};
