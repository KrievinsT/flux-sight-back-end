<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Storage extends Model
{
    use HasFactory;

    protected $table = 'storage';

    protected $fillable = [
        'user_id',
        'url',
        'seo',
        'page_speed',
        'is_active',
        'role_id',
    ];
}
