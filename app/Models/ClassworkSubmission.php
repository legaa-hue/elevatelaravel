<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassworkSubmission extends Model
{
    protected $fillable = [
        'classwork_id',
        'student_id',
        'submission_content',
        'link',
        'attachments',
        'quiz_answers',
        'rubric_scores',
        'grade',
        'feedback',
        'status',
        'submitted_at',
        'graded_at',
        'graded_by',
    ];

    protected $casts = [
        'attachments' => 'array',
        'quiz_answers' => 'array',
        'rubric_scores' => 'array',
        'grade' => 'decimal:2',
        'submitted_at' => 'datetime',
        'graded_at' => 'datetime',
    ];

    public function classwork(): BelongsTo
    {
        return $this->belongsTo(Classwork::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function gradedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'graded_by');
    }
}
