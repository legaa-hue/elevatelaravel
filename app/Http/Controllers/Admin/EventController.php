<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EventController extends Controller
{
    /**
     * Display the calendar page with events.
     */
    public function index()
    {
        $events = Event::where('user_id', auth()->id())
            ->orderBy('date', 'desc')
            ->get()
            ->map(function ($event) {
                return [
                    'id' => $event->id,
                    'title' => $event->title,
                    'date' => $event->date->format('Y-m-d'),
                    'time' => $event->time,
                    'description' => $event->description,
                    'category' => $event->category,
                    'color' => $event->color,
                    'backgroundColor' => $event->background_color,
                    'borderColor' => $event->border_color,
                ];
            });

        return Inertia::render('Admin/Calendar', [
            'events' => $events,
        ]);
    }

    /**
     * Store a newly created event.
     */
    public function store(Request $request)
    {
        $request->merge([
            'time' => $request->time ?: null
        ]);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'date' => 'required|date',
            'time' => 'nullable|date_format:H:i',
            'description' => 'required|string',
            'category' => 'required|in:general,meeting,academic,deadline,maintenance,urgent',
            'color' => 'required|string|max:7',
            'visibility' => 'nullable|in:admin_only,teachers,all',
            'target_audience' => 'nullable|in:teachers,students,both',
        ]);

        $event = Event::create([
            'user_id' => auth()->id(),
            'title' => $validated['title'],
            'date' => $validated['date'],
            'time' => $validated['time'],
            'description' => $validated['description'],
            'category' => $validated['category'],
            'color' => $validated['color'],
            'visibility' => $validated['visibility'] ?? 'admin_only',
            'target_audience' => $validated['target_audience'] ?? 'both',
        ]);

        // Log the action
        $this->logAction('create', $event, "Created calendar event: {$event->title}");

        return redirect()->back();
    }

    /**
     * Update the specified event.
     */
    public function update(Request $request, Event $event)
    {
        // Check if user owns the event
        if ($event->user_id !== auth()->id()) {
            abort(403);
        }

        $request->merge([
            'time' => $request->time ?: null
        ]);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'date' => 'required|date',
            'time' => 'nullable|date_format:H:i',
            'description' => 'required|string',
            'category' => 'required|in:general,meeting,academic,deadline,maintenance,urgent',
            'color' => 'required|string|max:7',
            'visibility' => 'nullable|in:admin_only,teachers,all',
            'target_audience' => 'nullable|in:teachers,students,both',
        ]);

        $oldData = $event->toArray();
        $event->update($validated);

        // Log the action
        $this->logAction('update', $event, "Updated calendar event: {$event->title}", [
            'old' => $oldData,
            'new' => $event->fresh()->toArray()
        ]);

        return redirect()->back();
    }

    /**
     * Update event date (for drag and drop).
     */
    public function updateDate(Request $request, Event $event)
    {
        // Check if user owns the event
        if ($event->user_id !== auth()->id()) {
            abort(403);
        }

        $validated = $request->validate([
            'date' => 'required|date',
        ]);

        $oldDate = $event->date;
        $event->update(['date' => $validated['date']]);

        // Log the action
        $this->logAction('update', $event, "Moved calendar event: {$event->title}", [
            'old' => ['date' => $oldDate],
            'new' => ['date' => $validated['date']]
        ]);

        return redirect()->back();
    }

    /**
     * Remove the specified event.
     */
    public function destroy(Event $event)
    {
        // Check if user owns the event
        if ($event->user_id !== auth()->id()) {
            abort(403);
        }

        $eventTitle = $event->title;
        $eventId = $event->id;
        $event->delete();

        // Log the action
        $this->logAction('delete', null, "Deleted calendar event: {$eventTitle}", null, $eventId);

        return redirect()->back();
    }

    /**
     * Log an action to audit logs.
     */
    private function logAction(string $action, $model = null, string $description, array $changes = null, int $modelId = null)
    {
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'model_type' => 'Calendar',
            'model_id' => $model ? $model->id : $modelId,
            'description' => $description,
            'changes' => $changes,
            'ip_address' => null,
        ]);
    }
}
