<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Media extends Model
{
    use HasFactory;

    protected $connection = 'curator9-common-tingstar';
    protected $table = 'medias';

    protected $hidden = [
        'created_at', 'updated_at'
    ];

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }

    public function keywords(): hasMany
    {
        return $this->hasMany(Keyword::class);
    }

    public function channels(): hasMany
    {
        return $this->hasMany(Channel::class);
    }
}
