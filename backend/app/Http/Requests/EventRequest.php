<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class EventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        $tenantId = Auth::user()->tenant_id;
        $eventId = $this->route('event')?->id; // Handles route model binding

        return [
            'client_id' => [
                'required',
                Rule::exists('clients', 'id')->where('tenant_id', $tenantId)
            ],
            'nomor_event' => [
                'required',
                'string',
                'max:50',
                Rule::unique('events', 'nomor_event')
                    ->where('tenant_id', $tenantId)
                    ->ignore($eventId)
            ],
            'nama_event' => 'required|string|max:255',
            'jenis_event' => 'nullable|string|max:100',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'lokasi' => 'nullable|string',
            'nilai_kontrak' => 'required|numeric|min:0',
            'status' => 'required|in:draft,negosiasi,dp,berjalan,selesai,batal'
        ];
    }
}
