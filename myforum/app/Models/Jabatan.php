<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Jabatan extends Model
{
    protected $table = 'tbhs_jabatan';

    protected $fillable = [
        'nama_jabatan', 'bagian_posisi'
    ];
}
