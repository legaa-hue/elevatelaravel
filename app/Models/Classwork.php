<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Classwork extends Model
{
    use HasFactory;

    protected $table = 'classwork';

    protected $fillable = [
        'course_id',
        'type',
        'title',
        'description',
        'due_date',
        'points',
        'attachments',
        'has_submission',
        'show_correct_answers',
        'status',
        'color_code',
        'created_by',
        'grading_period',
        'grade_table_name',
        'grade_main_column',
        'grade_sub_column',
    ];

    protected $casts = [
        'due_date' => 'datetime',
        'attachments' => 'array',
        'has_submission' => 'boolean',
        'show_correct_answers' => 'boolean',
    ];

    /**
     * Get the course that owns this classwork.
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Get the teacher who created this classwork.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get all file uploads for this classwork.
     */
    public function fileUploads(): MorphMany
    {
        return $this->morphMany(FileUpload::class, 'uploadable');
    }

    /**
     * Get the rubric criteria for this classwork.
     */
    public function rubricCriteria(): HasMany
    {
        return $this->hasMany(RubricCriteria::class)->orderBy('order');
    }

    /**
     * Get the quiz questions for this classwork.
     */
    public function quizQuestions(): HasMany
    {
        return $this->hasMany(QuizQuestion::class)->orderBy('order');
    }

    /**
     * Get the submissions for this classwork.
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(ClassworkSubmission::class);
    }

    /**
     * Get color code based on type if not explicitly set.
     */
    public function getColorAttribute(): string
    {
        if ($this->color_code) {
            return $this->color_code;
        }

        return match($this->type) {
            'lesson' => '#3b82f6', // blue-500
            'assignment' => '#eab308',     // yellow-500
            'quiz' => '#ef4444',     // red-500
            'activity' => '#10b981', // green-500
            default => '#3b82f6',
        };
    }
}
