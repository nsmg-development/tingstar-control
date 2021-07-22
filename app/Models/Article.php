<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Article extends Model
{
    use HasFactory;

    protected $fillable = [
        'media_id', 'platform', 'type', 'keyword', 'channel', 'url', 'title', 'contents',
        'thumbnail_url', 'azure_thumbnail_url', 'thumbnail_width', 'thumbnail_height', 'hashtag', 'state', 'date'
    ];

    protected $hidden = [
        'created_at', 'updated_at'
    ];

    public function articleMedias(): HasMany
    {
        return $this->hasMany(ArticleMedia::class, 'article_id', 'id');
    }
}
