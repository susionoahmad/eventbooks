<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TaxResource;
use App\Models\Tax;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TaxController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Tax::with(['transaction', 'event', 'invoice']);

        if ($request->filled('tipe_pajak')) {
            $query->where('tipe_pajak', $request->input('tipe_pajak'));
        }
        if ($request->filled('masa_pajak')) {
            $query->where('masa_pajak', $request->input('masa_pajak'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $taxes = $query->orderBy('masa_pajak', 'desc')->paginate(15);

        return TaxResource::collection($taxes);
    }

    /**
     * Group and summarize taxes by tax period (masa_pajak)
     */
    public function summary(Request $request): JsonResponse
    {
        $summary = Tax::select(
            'masa_pajak',
            DB::raw("SUM(CASE WHEN tipe_pajak = 'ppn_keluaran' THEN nominal_pajak ELSE 0 END) as total_ppn_keluaran"),
            DB::raw("SUM(CASE WHEN tipe_pajak = 'ppn_masukan' THEN nominal_pajak ELSE 0 END) as total_ppn_masukan"),
            DB::raw("SUM(CASE WHEN tipe_pajak = 'pph_21' THEN nominal_pajak ELSE 0 END) as total_pph_21"),
            DB::raw("SUM(CASE WHEN tipe_pajak = 'pph_23' THEN nominal_pajak ELSE 0 END) as total_pph_23"),
            DB::raw("SUM(CASE WHEN status = 'terutang' THEN nominal_pajak ELSE 0 END) as total_terutang"),
            DB::raw("SUM(CASE WHEN status = 'dibayar' THEN nominal_pajak ELSE 0 END) as total_dibayar")
        )
        ->groupBy('masa_pajak')
        ->orderBy('masa_pajak', 'desc')
        ->get();

        return response()->json([
            'data' => $summary
        ]);
    }

    /**
     * Mark tax payment status as 'dibayar'
     */
    public function updateStatus(Request $request, Tax $tax): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:terutang,dibayar'
        ]);

        $tax->update([
            'status' => $request->input('status')
        ]);

        return response()->json([
            'message' => 'Tax status updated successfully',
            'data' => new TaxResource($tax)
        ], 200);
    }
}
