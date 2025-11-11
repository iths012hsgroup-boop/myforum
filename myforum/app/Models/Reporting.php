<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reporting extends Model
{
    protected $table = 'tbhs_reporting';

    protected $fillable = [
        'id_staff','nama_staff','periode','site_situs','tidak_bersalah','bersalah_low','bersalah_medium','bersalah_high','total_case'
    ];
}
