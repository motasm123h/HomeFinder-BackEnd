<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class updateRealEstateRequest extends FormRequest
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
            'latitude' => 'numeric',
            'longitude' => 'numeric',
            // 'status' => [ Rule::in(['Closed', 'Open'])],
            'type' => [Rule::in(['Rental', 'Sale'])],
            'price' => 'integer|min:0',
            'description' => 'string|max:1000',
            'kind' => [Rule::in(['apartment', 'villa', 'chalet'])],
            'real_estate_location_id' => 'exists:real_estate_locations,id',

            // Property Fields (updated to match your schema)
            'electricity_status' => [Rule::in(['1', '2', '3'])],
            'water_status' => [Rule::in(['1', '2', '3'])],
            'transportation_status' => [Rule::in(['1', '2', '3'])],
            'water_well' => [Rule::in(['1', '2'])],
            'solar_energy' => [Rule::in(['1', '2'])],
            'garage' => [Rule::in(['1', '2'])],
            'room_no' => 'integer',
            'direction' => [Rule::in(['1', '2', '3'])],
            'space_status' => 'integer',
            'elevator' => [Rule::in(['1', '2'])],
            'floor' => 'integer',
            'garden_status' => [Rule::in(['1', '2'])],
            'attired' => [Rule::in(['1', '2', '3'])],
            'ownership_type' => [Rule::in(['Green', 'Court'])],
            // 'total_weight' => 'integer',
        ];
    }
}
