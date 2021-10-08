<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArticleMedia extends Model
{
    use HasFactory;

    protected $connection = 'curator9-tingstar';
    protected $table = 'article_medias';

    protected $primaryKey = 'article_id';

    protected $guarded = [];

    protected $fillable = [
        'article_id', 'type', 'storage_url', 'url', 'width', 'height', 'mime'
    ];

    protected $hidden = [
        'created_at', 'updated_at'
    ];
}
