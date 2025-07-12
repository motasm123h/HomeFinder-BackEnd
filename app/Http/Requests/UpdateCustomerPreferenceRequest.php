<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomerPreferenceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'electricity_status' => ['sometimes', 'in:1,2,3'],
            'water_status' => ['sometimes', 'in:1,2,3'],
            'transportation_status' => ['sometimes', 'in:1,2,3'],
            'water_well' => ['sometimes', 'in:1,2'],
            'solar_energy' => ['sometimes', 'in:1,2'],
            'garage' => ['sometimes', 'in:1,2'],
            'room_no' => ['sometimes', 'integer', 'min:0'],
            'direction' => ['sometimes', 'in:1,2,3'],
            'space_status' => ['sometimes', 'integer', 'min:0'],
            'elevator' => ['sometimes', 'in:1,2'],
            'floor' => ['sometimes', 'integer', 'min:0'],
            'garden_status' => ['sometimes', 'in:1,2'],
            'attired' => ['sometimes', 'in:1,2,3'],
            'ownership_type' => ['sometimes', 'in:Green,Court'],
            'price' => ['sometimes', 'in:1,2,3'],
            'total_weight' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
