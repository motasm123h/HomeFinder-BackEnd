<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerPreferenceRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'electricity_status' => ['required', 'in:1,2,3'],
            'water_status' => ['required', 'in:1,2,3'],
            'transportation_status' => ['required', 'in:1,2,3'],
            'water_well' => ['required', 'in:1,2'],
            'solar_energy' => ['required', 'in:1,2'],
            'garage' => ['required', 'in:1,2'],
            'room_no' => ['required', 'integer', 'min:0'],
            'direction' => ['required', 'in:1,2,3'],
            'space_status' => ['required', 'integer', 'min:0'],
            'elevator' => ['required', 'in:1,2'],
            'floor' => ['required', 'integer', 'min:0'],
            'garden_status' => ['required', 'in:1,2'],
            'attired' => ['required', 'in:1,2,3'],
            'ownership_type' => ['required', 'in:Green,Court'],
            'price' => ['required', 'in:1,2,3'],
            'total_weight' => ['required', 'integer', 'min:0'],
        ];
    }
}
