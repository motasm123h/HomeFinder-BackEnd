<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRealEstateRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'phone_number' => ['required', 'string', 'max:20'],
            'username' => ['required', 'string', 'max:20'],
            'user_id' => ['required', 'exists:users,id'],
        ];
    }
}
