<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    protected $table = 'tbhs_menu';

    protected $fillable = [
        'menu_id','menu_deskripsi','menu_link'
    ];
}
