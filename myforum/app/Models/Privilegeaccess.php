<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Privilegeaccess extends Model
{
    protected $table = 'tbhs_privilege_access';

    protected $fillable = [
        'id_admin','menu_id'
    ];
}
