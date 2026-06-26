<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ClientRequest;
use App\Http\Resources\ClientResource;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function getNextCode(Request $request): JsonResponse
    {
        $nextCode = Client::generateNextCode($request->user()->tenant_id);
        return response()->json(['next_code' => $nextCode]);
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Client::class);

        $query = Client::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('perusahaan', 'like', "%{$search}%")
                  ->orWhere('kode_klien', 'like', "%{$search}%");
            });
        }

        $clients = $query->orderBy('nama', 'asc')->paginate(15);

        return ClientResource::collection($clients);
    }

    public function store(ClientRequest $request): JsonResponse
    {
        Gate::authorize('create', Client::class);

        $client = Client::create($request->validated());

        return response()->json([
            'message' => 'Client created successfully',
            'data' => new ClientResource($client)
        ], 201);
    }

    public function show(Client $client): ClientResource
    {
        Gate::authorize('view', $client);

        return new ClientResource($client);
    }

    public function update(ClientRequest $request, Client $client): JsonResponse
    {
        Gate::authorize('update', $client);

        $client->update($request->validated());

        return response()->json([
            'message' => 'Client updated successfully',
            'data' => new ClientResource($client)
        ], 200);
    }

    public function destroy(Client $client): JsonResponse
    {
        Gate::authorize('delete', $client);

        $client->delete();

        return response()->json([
            'message' => 'Client deleted successfully'
        ], 200);
    }
}
