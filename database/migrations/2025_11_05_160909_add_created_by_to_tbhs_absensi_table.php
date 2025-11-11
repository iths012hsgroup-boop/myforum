<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCreatedByToTbhsAbsensiTable extends Migration
{
    public function up()
    {
        Schema::table('tbhs_absensi', function (Blueprint $table) {
            $table->string('created_by', 100)->nullable()->after('remarks');
            // kalau edited_by belum ada, bisa sekalian di sini juga
            // $table->string('edited_by', 100)->nullable()->after('created_by');
        });
    }

    public function down()
    {
        Schema::table('tbhs_absensi', function (Blueprint $table) {
            $table->dropColumn('created_by');
            // $table->dropColumn('edited_by');
        });
    }
}
