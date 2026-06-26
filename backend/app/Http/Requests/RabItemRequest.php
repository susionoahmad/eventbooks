<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class RabItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'kategori' => 'required|string|max:100',
            'deskripsi' => 'required|string|max:255',
            'qty' => 'required|numeric|min:0.01',
            'harga' => 'required|numeric|min:0'
        ];
    }
}
