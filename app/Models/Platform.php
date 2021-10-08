<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Platform extends Model
{
    use HasFactory;

    protected $connection = 'curator9-common-tingstar';

    protected $hidden = [
        'created_at', 'updated_at'
    ];
}
