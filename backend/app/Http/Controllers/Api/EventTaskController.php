<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\EventTaskRequest;
use App\Http\Resources\EventTaskResource;
use App\Models\Event;
use App\Models\EventTask;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class EventTaskController extends Controller
{
    public function index(Event $event): JsonResponse
    {
        Gate::authorize('view', $event);

        $tasks = $event->tasks()->orderBy('target_date', 'asc')->orderBy('id', 'asc')->get();

        return response()->json([
            'items' => EventTaskResource::collection($tasks)
        ]);
    }

    public function store(EventTaskRequest $request, Event $event): JsonResponse
    {
        Gate::authorize('update', $event);

        $validatedData = $request->validated();
        
        $task = $event->tasks()->create($validatedData);

        return response()->json([
            'message' => 'Task added successfully',
            'data' => new EventTaskResource($task)
        ], 201);
    }

    public function update(EventTaskRequest $request, Event $event, EventTask $task): JsonResponse
    {
        Gate::authorize('update', $event);

        if ($task->event_id !== $event->id) {
            return response()->json(['message' => 'Task does not belong to this event'], 400);
        }

        $task->update($request->validated());

        return response()->json([
            'message' => 'Task updated successfully',
            'data' => new EventTaskResource($task)
        ], 200);
    }

    public function destroy(Event $event, EventTask $task): JsonResponse
    {
        Gate::authorize('update', $event);

        if ($task->event_id !== $event->id) {
            return response()->json(['message' => 'Task does not belong to this event'], 400);
        }

        $task->delete();

        return response()->json([
            'message' => 'Task deleted successfully'
        ], 200);
    }
}
