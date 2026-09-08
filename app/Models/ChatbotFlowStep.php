<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatbotFlowStep extends Model
{
    protected $fillable = [
        'step_key',
        'step_name',
        'description',
        'next_steps',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'next_steps' => 'array',
            'is_active' => 'boolean',
        ];
    }
}
