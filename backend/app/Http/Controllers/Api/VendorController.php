<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\VendorRequest;
use App\Http\Resources\VendorResource;
use App\Models\Vendor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\Request;

class VendorController extends Controller
{
    public function getNextCode(Request $request): JsonResponse
    {
        $nextCode = Vendor::generateNextCode($request->user()->tenant_id);
        return response()->json(['next_code' => $nextCode]);
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Vendor::class);

        $query = Vendor::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('nama_vendor', 'like', "%{$search}%")
                  ->orWhere('kode_vendor', 'like', "%{$search}%");
            });
        }

        if ($request->filled('kategori')) {
            $query->where('kategori', $request->input('kategori'));
        }

        $vendors = $query->orderBy('nama_vendor', 'asc')->paginate(15);

        return VendorResource::collection($vendors);
    }

    public function store(VendorRequest $request): JsonResponse
    {
        Gate::authorize('create', Vendor::class);

        $vendor = Vendor::create($request->validated());

        return response()->json([
            'message' => 'Vendor created successfully',
            'data' => new VendorResource($vendor)
        ], 201);
    }

    public function show(Vendor $vendor): VendorResource
    {
        Gate::authorize('view', $vendor);

        return new VendorResource($vendor);
    }

    public function update(VendorRequest $request, Vendor $vendor): JsonResponse
    {
        Gate::authorize('update', $vendor);

        $vendor->update($request->validated());

        return response()->json([
            'message' => 'Vendor updated successfully',
            'data' => new VendorResource($vendor)
        ], 200);
    }

    public function destroy(Vendor $vendor): JsonResponse
    {
        Gate::authorize('delete', $vendor);

        $vendor->delete();

        return response()->json([
            'message' => 'Vendor deleted successfully'
        ], 200);
    }
}
