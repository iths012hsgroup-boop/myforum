<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Forumaudit extends Model
{
    use HasFactory;

    protected $table = 'tbhs_forum';

    protected $fillable = [
        'slug','case_id','topik_title','link_gambar','topik_deskripsi',
        'created_for','created_for_name','created_by','created_by_name',
        'site_situs','periode','status_kesalahan','status_case','soft_delete','recovery_by'
    ];

    // Pastikan kolom numerik selalu diperlakukan sebagai integer
    protected $casts = [
        'status_case'      => 'integer',
        'status_kesalahan' => 'integer',
        'created_for' => 'string',
        'created_by'  => 'string',
        'soft_delete'      => 'integer',
        'recovery_by'      => 'integer',
        'created_at'       => 'datetime',
        'updated_at'       => 'datetime',
    ];

    // Nilai default yang aman
    protected $attributes = [
        'status_case'      => 1, // Open
        'status_kesalahan' => 0, // belum ditentukan
        'soft_delete'      => 0,
    ];
}
