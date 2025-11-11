<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('tbhs_topik')) {
            Schema::create('tbhs_topik', function (Blueprint $t) {
                $t->id();
                $t->string('topik_title',150);
                $t->tinyInteger('status')->default(1);     // 1=Aktif,0=Nonaktif
                $t->tinyInteger('soft_delete')->default(0); // 0=tidak dihapus
                $t->timestamps();
            });
        } else {
            Schema::table('tbhs_topik', function (Blueprint $t) {
                if (!Schema::hasColumn('tbhs_topik','topik_title')) $t->string('topik_title',150);
                if (!Schema::hasColumn('tbhs_topik','status')) $t->tinyInteger('status')->default(1);
                if (!Schema::hasColumn('tbhs_topik','soft_delete')) $t->tinyInteger('soft_delete')->default(0);
                if (!Schema::hasColumn('tbhs_topik','created_at')) $t->timestamps();
            });
        }
    }

    public function down(): void
    {
        // rollback hanya kolom yang ditambahkan (jaga-jaga)
        Schema::table('tbhs_topik', function (Blueprint $t) {
            // biarkan tabelnya ada
        });
    }
};
