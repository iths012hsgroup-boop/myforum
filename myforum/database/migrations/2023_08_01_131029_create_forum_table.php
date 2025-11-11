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
        Schema::create('tbhs_forum', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('case_id');
            $table->string('topik_title');
            $table->string('link_gambar');
            $table->longtext('topik_deskripsi');
            $table->string('created_for');
            $table->string('created_for_name');
            $table->string('created_by');
            $table->string('created_by_name');
            $table->unsignedBigInteger('status_case');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbhs_forum');
    }
};
