<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AbsensiReport extends Model
{
    use HasFactory;

    protected $table = 'tbhs_absensireport';

    protected $fillable = [
        'id_admin',
        'nama_staff',
        'id_situs',
        'periode',
        'sakit',
        'izin',
        'telat',
        'tanpa_kabar',
        'cuti',
        'total_absensi',
    ];

    protected $casts = [
        'sakit'         => 'integer',
        'izin'          => 'integer',
        'telat'         => 'integer',
        'tanpa_kabar'   => 'integer',
        'cuti'          => 'integer',
        'total_absensi' => 'integer',
        // JANGAN cast id_admin ke integer
        // 'id_admin'   => 'integer',
    ];
}