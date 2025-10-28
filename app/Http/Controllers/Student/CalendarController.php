<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CalendarController extends Controller
{
    public function index()
    {
        // Get events that are for students or both
        $events = Event::where(function($query) {
                $query->where('target_audience', 'students')
                    ->orWhere('target_audience', 'both');
            })
            ->orderBy('date', 'asc')
            ->orderBy('time', 'asc')
            ->get()
            ->map(function ($event) {
                return [
                    'id' => $event->id,
                    'title' => $event->title,
                    'description' => $event->description,
                    'date' => $event->date,
                    'time' => $event->time,
                    'category' => $event->category,
                    'color' => $event->color,
                    'created_by' => $event->user ? $event->user->first_name . ' ' . $event->user->last_name : 'Admin',
                ];
            });

        return Inertia::render('Student/Calendar', [
            'events' => $events,
        ]);
    }
}
