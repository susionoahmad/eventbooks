<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class EventTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'nama_task' => 'required|string|max:255',
            'target_date' => 'nullable|date',
            'status' => 'nullable|string|in:pending,in_progress,completed',
            'keterangan' => 'nullable|string'
        ];
    }
}
