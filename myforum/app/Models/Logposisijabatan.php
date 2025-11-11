<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Logposisijabatan extends Model
{
    protected $table = 'tbhs_log_jabatan';

    protected $fillable = [
        'user_parent_id', 'id_admin', 'posisi_kerja', 'nama_posisi', 'id_jabatan', 'nama_jabatan', 'created_at', 'updated_at'
    ];
}
