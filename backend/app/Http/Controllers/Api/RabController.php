<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RabItemRequest;
use App\Http\Resources\RabItemResource;
use App\Models\Event;
use App\Models\RabItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class RabController extends Controller
{
    public function index(Event $event): JsonResponse
    {
        Gate::authorize('view', $event);

        $items = $event->rabItems()->orderBy('kategori', 'asc')->get();

        return response()->json([
            'event_summary' => [
                'id' => $event->id,
                'nama_event' => $event->nama_event,
                'nilai_kontrak' => (float) $event->nilai_kontrak,
                'total_anggaran_rab' => $event->total_rab_budget,
                'realisasi_biaya_aktual' => $event->total_actual_cost,
                'selisih_sisa_anggaran' => $event->nilai_kontrak - $event->total_rab_budget,
            ],
            'items' => RabItemResource::collection($items)
        ]);
    }

    public function store(RabItemRequest $request, Event $event): JsonResponse
    {
        Gate::authorize('update', $event);

        // Standard checks: staff can add rab items but can't approve budgets
        $validatedData = $request->validated();
        
        $item = $event->rabItems()->create($validatedData);

        return response()->json([
            'message' => 'RAB budget item added successfully',
            'data' => new RabItemResource($item)
        ], 201);
    }

    public function update(RabItemRequest $request, Event $event, RabItem $rabItem): JsonResponse
    {
        Gate::authorize('update', $event);

        if ($rabItem->event_id !== $event->id) {
            return response()->json(['message' => 'RAB item does not belong to this event'], 400);
        }

        $rabItem->update($request->validated());

        return response()->json([
            'message' => 'RAB budget item updated successfully',
            'data' => new RabItemResource($rabItem)
        ], 200);
    }

    public function destroy(Event $event, RabItem $rabItem): JsonResponse
    {
        Gate::authorize('update', $event);

        if ($rabItem->event_id !== $event->id) {
            return response()->json(['message' => 'RAB item does not belong to this event'], 400);
        }

        $rabItem->delete();

        return response()->json([
            'message' => 'RAB budget item deleted successfully'
        ], 200);
    }
}
