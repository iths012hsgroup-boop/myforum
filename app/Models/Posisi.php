<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Posisi extends Model
{
    protected $table = 'tbhs_posisi';

    protected $fillable = [
        'nama_posisi'
    ];
}
