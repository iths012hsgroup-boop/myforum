<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ViewAbsensi extends Model
{
    protected $table = 'tbhs_viewabsensi';

    protected $fillable = [
        'id_admin',
        'tahun',
        'bulan',
        'keterangan',
    ];

    protected $casts = [
        'keterangan' => 'array', // biar otomatis JSON <-> array
    ];
}