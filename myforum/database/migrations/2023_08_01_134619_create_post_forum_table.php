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
        Schema::create('tbhs_forum_post', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parent_forum_id');
            $table->unsignedBigInteger('parent_case_id');
            $table->longtext('deskripsi_post');
            $table->string('updated_by');
            $table->string('updated_by_name');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbhs_forum_post');
    }
};
