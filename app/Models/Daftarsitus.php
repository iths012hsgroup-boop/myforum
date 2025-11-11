<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Daftarsitus extends Model
{
    protected $table = 'tbhs_situs';

    protected $fillable = [
       'id','nama_situs'
    ];
}
