<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRealEstateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            // 'status' => ['required', Rule::in(['Closed', 'Open'])],
            'type' => ['required', Rule::in(['rental', 'sale'])],
            'price' => 'required|integer|min:0',
            'description' => 'required|string|max:1000',
            'kind' => ['required', Rule::in(['apartment', 'villa', 'chalet'])],
            'user_id' => 'required|exists:users,id',
            'real_estate_location_id' => 'required|exists:real_estate_locations,id',
            
            // Images
            'images' => 'sometimes|array',
            'images.*' => [
                'image',
                'mimes:jpeg,png,jpg,gif',
                'max:' . config('model_paths.real_estate.max_file_size'),
            ],
            
            // Property Fields (updated to match your schema)
            'electricity_status' => ['required', Rule::in(['1', '2', '3'])],
            'water_status' => ['required', Rule::in(['1', '2', '3'])],
            'transportation_status' => ['required', Rule::in(['1', '2', '3'])],
            'water_well' => ['required', Rule::in(['1', '2'])],
            'solar_energy' => ['required', Rule::in(['1', '2'])],
            'garage' => ['required', Rule::in(['1', '2'])],
            'room_no' => 'required|integer',
            'direction' => ['required', Rule::in(['1', '2', '3'])],
            'space_status' => 'required|integer',
            'elevator' => ['required', Rule::in(['1', '2'])],
            'floor' => 'required|integer',
            'garden_status' => ['required', Rule::in(['1', '2'])],
            'attired' => ['required', Rule::in(['1', '2', '3'])],
            'ownership_type' => ['required', Rule::in(['Green', 'Court'])],
            // 'total_weight' => 'required|integer',
        ];
    }
}
