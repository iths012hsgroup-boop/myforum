<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Forumauditpost extends Model
{
    protected $table = 'tbhs_forum_post';

    protected $fillable = [
       'slug','parent_forum_id','parent_case_id','deskripsi_post','updated_by','updated_by_name'
    ];
}
