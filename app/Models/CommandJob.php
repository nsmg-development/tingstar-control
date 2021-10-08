<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommandJob extends Model
{
    use HasFactory;

    protected $connection = 'curator9-common-tingstar';

    protected $fillable = [
        'type', 'command', 'created_at', 'updated_at'
    ];
}
