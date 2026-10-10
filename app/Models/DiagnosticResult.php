<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiagnosticResult extends Model
{
    protected $fillable = [
        'user_id',
        'career_path_id',
        'answers',
        'total_score',
        'max_score',
        'percentage',
        'level',
        'unknown_answers_count',
        'strengths',
        'weaknesses',
        'analysis',
        'recommendation',
    ];

    protected $casts = [
        'answers' => 'array',
        'strengths' => 'array',
        'weaknesses' => 'array',
    ];
}