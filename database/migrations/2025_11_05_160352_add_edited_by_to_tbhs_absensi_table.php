<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEditedByToTbhsAbsensiTable extends Migration
{
    public function up()
    {
        Schema::table('tbhs_absensi', function (Blueprint $table) {
            $table->string('edited_by', 100)->nullable()->after('remarks');
        });
    }

    public function down()
    {
        Schema::table('tbhs_absensi', function (Blueprint $table) {
            $table->dropColumn('edited_by');
        });
    }
}
