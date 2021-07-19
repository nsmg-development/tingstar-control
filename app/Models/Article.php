<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    use HasFactory;

    protected $fillable = [
        'media_id', 'platform', 'type', 'keyword', 'channel', 'url', 'title', 'contents', 'thumbnail_url', 'hashtag', 'state', 'date'
    ];

    protected $hidden = [
        'created_at', 'updated_at'
    ];
}
