<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Client;
use App\Models\Invoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function globalSearch(Request $request): JsonResponse
    {
        $query = $request->input('q', '');

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $results = [];

        // 1. Search Events
        $events = Event::where(function ($q) use ($query) {
                $q->where('nama_event', 'like', "%{$query}%")
                  ->orWhere('nomor_event', 'like', "%{$query}%");
            })
            ->limit(5)
            ->get();

        foreach ($events as $event) {
            $results[] = [
                'type' => 'event',
                'id' => $event->id,
                'title' => $event->nama_event,
                'subtitle' => $event->nomor_event,
                'link' => "/events"
            ];
        }

        // 2. Search Clients
        $clients = Client::where(function ($q) use ($query) {
                $q->where('nama', 'like', "%{$query}%")
                  ->orWhere('kode_klien', 'like', "%{$query}%")
                  ->orWhere('perusahaan', 'like', "%{$query}%");
            })
            ->limit(5)
            ->get();

        foreach ($clients as $client) {
            $results[] = [
                'type' => 'client',
                'id' => $client->id,
                'title' => $client->nama,
                'subtitle' => $client->perusahaan ? "{$client->kode_klien} - {$client->perusahaan}" : $client->kode_klien,
                'link' => "/clients"
            ];
        }

        // 3. Search Invoices
        $invoices = Invoice::where(function ($q) use ($query) {
                $q->where('nomor_invoice', 'like', "%{$query}%")
                  ->orWhere('nomor_faktur_pajak', 'like', "%{$query}%");
            })
            ->limit(5)
            ->get();

        foreach ($invoices as $invoice) {
            $results[] = [
                'type' => 'invoice',
                'id' => $invoice->id,
                'title' => $invoice->nomor_invoice,
                'subtitle' => $invoice->nomor_faktur_pajak ? "Faktur: {$invoice->nomor_faktur_pajak}" : "Invoice {$invoice->jenis_invoice}",
                'link' => "/invoices"
            ];
        }

        return response()->json($results);
    }
}
