<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SettingPeriode extends Model
{

    protected $table = 'tbhs_setting_periode';

    protected $fillable = [
        'bulan_dari', 'bulan_ke', 'tahun', 'periode'
    ];
}
