<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\EventRequest;
use App\Http\Resources\EventResource;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class EventController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Event::class);

        $events = Event::with('client')
            ->orderBy('tanggal_mulai', 'desc')
            ->paginate(15);

        return EventResource::collection($events);
    }

    public function store(EventRequest $request): JsonResponse
    {
        Gate::authorize('create', Event::class);

        $event = Event::create($request->validated());

        return response()->json([
            'message' => 'Event created successfully',
            'data' => new EventResource($event->load('client'))
        ], 201);
    }

    public function show(Event $event): EventResource
    {
        // Handled via Route Model Binding
        Gate::authorize('view', $event);

        return new EventResource($event->load(['client', 'rabItems']));
    }

    public function update(EventRequest $request, Event $event): JsonResponse
    {
        Gate::authorize('update', $event);

        // Special authorization for status transitions
        if ($request->has('status') && $request->input('status') !== $event->status) {
            Gate::authorize('updateStatus', $event);
        }

        $event->update($request->validated());

        return response()->json([
            'message' => 'Event updated successfully',
            'data' => new EventResource($event->load('client'))
        ], 200);
    }

    public function destroy(Event $event): JsonResponse
    {
        Gate::authorize('delete', $event);

        $event->delete();

        return response()->json([
            'message' => 'Event deleted successfully'
        ], 200);
    }
}
