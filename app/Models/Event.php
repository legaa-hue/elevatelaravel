<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Event extends Model
{
    protected $fillable = [
        'user_id',
        'course_id',
        'title',
        'date',
        'time',
        'description',
        'category',
        'is_deadline',
        'color',
        'visibility',
        'target_audience',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    /**
     * Get the user that created the event.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the course associated with the event.
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Get the background color for the event.
     */
    public function getBackgroundColorAttribute(): string
    {
        return $this->color . '20'; // 20 is 12.5% opacity in hex
    }

    /**
     * Get the border color for the event.
     */
    public function getBorderColorAttribute(): string
    {
        return $this->color;
    }
}
