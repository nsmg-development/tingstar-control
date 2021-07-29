<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArticleOwner extends Model
{
    use HasFactory;

    protected $fillable = [
        'id', 'platform', 'url', 'name',
        'storage_thumbnail_url', 'thumbnail_url', 'thumbnail_width', 'thumbnail_height'
    ];
}
