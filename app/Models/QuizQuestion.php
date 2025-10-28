<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuizQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'classwork_id',
        'type',
        'question',
        'options',
        'correct_answer',
        'correct_answers',
        'points',
        'order',
    ];

    protected $casts = [
        'options' => 'array',
        'correct_answers' => 'array',
        'points' => 'integer',
        'order' => 'integer',
    ];

    /**
     * Get the classwork that owns this question.
     */
    public function classwork(): BelongsTo
    {
        return $this->belongsTo(Classwork::class);
    }
}
