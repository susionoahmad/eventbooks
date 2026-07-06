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
use Illuminate\Support\Facades\Storage;

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

        $validatedData = $request->validated();

        if ($request->hasFile('file_ktp')) {
            $fileKtp = $request->file('file_ktp');
            $filenameKtp = time() . '_ktp_' . $fileKtp->getClientOriginalName();
            $pathKtp = $fileKtp->storeAs('clients/ktp', $filenameKtp, 'public');
            $validatedData['file_ktp'] = $pathKtp;
        }

        if ($request->hasFile('file_npwp')) {
            $fileNpwp = $request->file('file_npwp');
            $filenameNpwp = time() . '_npwp_' . $fileNpwp->getClientOriginalName();
            $pathNpwp = $fileNpwp->storeAs('clients/npwp', $filenameNpwp, 'public');
            $validatedData['file_npwp'] = $pathNpwp;
        }

        $client = Client::create($validatedData);

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

        $validatedData = $request->validated();

        if ($request->hasFile('file_ktp')) {
            if ($client->file_ktp && Storage::disk('public')->exists($client->file_ktp)) {
                Storage::disk('public')->delete($client->file_ktp);
            }
            $fileKtp = $request->file('file_ktp');
            $filenameKtp = time() . '_ktp_' . $fileKtp->getClientOriginalName();
            $pathKtp = $fileKtp->storeAs('clients/ktp', $filenameKtp, 'public');
            $validatedData['file_ktp'] = $pathKtp;
        }

        if ($request->hasFile('file_npwp')) {
            if ($client->file_npwp && Storage::disk('public')->exists($client->file_npwp)) {
                Storage::disk('public')->delete($client->file_npwp);
            }
            $fileNpwp = $request->file('file_npwp');
            $filenameNpwp = time() . '_npwp_' . $fileNpwp->getClientOriginalName();
            $pathNpwp = $fileNpwp->storeAs('clients/npwp', $filenameNpwp, 'public');
            $validatedData['file_npwp'] = $pathNpwp;
        }

        $client->update($validatedData);

        return response()->json([
            'message' => 'Client updated successfully',
            'data' => new ClientResource($client)
        ], 200);
    }

    public function destroy(Client $client): JsonResponse
    {
        Gate::authorize('delete', $client);

        // Delete files from storage
        if ($client->file_ktp && Storage::disk('public')->exists($client->file_ktp)) {
            Storage::disk('public')->delete($client->file_ktp);
        }
        if ($client->file_npwp && Storage::disk('public')->exists($client->file_npwp)) {
            Storage::disk('public')->delete($client->file_npwp);
        }

        $client->delete();

        return response()->json([
            'message' => 'Client deleted successfully'
        ], 200);
    }

    public function showKtp(Request $request, Client $client)
    {
        // Try authenticating using token query parameter if not already authenticated
        if (!\Illuminate\Support\Facades\Auth::check() && $request->filled('token')) {
            $accessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($request->query('token'));
            if ($accessToken && $accessToken->tokenable) {
                \Illuminate\Support\Facades\Auth::login($accessToken->tokenable);
            }
        }

        Gate::authorize('view', $client);

        if (!$client->file_ktp || !Storage::disk('public')->exists($client->file_ktp)) {
            return response()->json(['message' => 'Berkas KTP tidak ditemukan'], 404);
        }

        return Storage::disk('public')->response($client->file_ktp);
    }

    public function showNpwp(Request $request, Client $client)
    {
        // Try authenticating using token query parameter if not already authenticated
        if (!\Illuminate\Support\Facades\Auth::check() && $request->filled('token')) {
            $accessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($request->query('token'));
            if ($accessToken && $accessToken->tokenable) {
                \Illuminate\Support\Facades\Auth::login($accessToken->tokenable);
            }
        }

        Gate::authorize('view', $client);

        if (!$client->file_npwp || !Storage::disk('public')->exists($client->file_npwp)) {
            return response()->json(['message' => 'Berkas NPWP tidak ditemukan'], 404);
        }

        return Storage::disk('public')->response($client->file_npwp);
    }
}
