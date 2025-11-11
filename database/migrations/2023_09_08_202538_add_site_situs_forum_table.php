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
        Schema::table('tbhs_forum', function (Blueprint $table) {
            $table->string('site_situs')->after('created_by_name')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbhs_forum', function (Blueprint $table) {
            $table->dropColumn('site_situs');
        });
    }
};
