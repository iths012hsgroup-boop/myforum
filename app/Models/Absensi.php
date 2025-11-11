<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Absensi extends Model
{
    use HasFactory;

    protected $table = 'tbhs_absensi'; // Menghubungkan ke tabel yang benar

    protected $fillable = [
        'id_admin',
        'nama_staff',
        'id_situs',
        'tanggal',
        'status', 
        'remarks',
        'cuti_start',
        'cuti_end',
        'created_by',
        'edited_by',


    ];
}