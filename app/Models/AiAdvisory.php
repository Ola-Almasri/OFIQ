<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiAdvisory extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'prompt_asked',
        'ai_response',
    ];
}