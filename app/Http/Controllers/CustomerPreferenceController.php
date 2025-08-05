<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCustomerPreferenceRequest;
use App\Http\Requests\UpdateCustomerPreferenceRequest;
use App\Models\CustomerPreference;
use App\Models\CustomerPreferences;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerPreferenceController extends Controller
{

    public function index(): JsonResponse
    {
        $preferences = CustomerPreferences::all();
        return response()->json([
            'message' => 'Customer preferences retrieved successfully.',
            'data' => $preferences
        ], 200);
    }

    public function store(StoreCustomerPreferenceRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['user_id'] = auth()->user()->id;
        $preference = CustomerPreferences::create($data);

        return response()->json([
            'message' => 'Customer preference created successfully.',
            'data' => $preference
        ], 201); // 201 Created
    }


    public function show(CustomerPreferences $customerPreference): JsonResponse
    {
        return response()->json([
            'message' => 'Customer preference retrieved successfully.',
            'data' => $customerPreference
        ], 200);
    }


    public function update(UpdateCustomerPreferenceRequest $request, CustomerPreferences $customerPreference): JsonResponse
    {
        $customerPreference->update($request->validated());

        return response()->json([
            'message' => 'Customer preference updated successfully.',
            'data' => $customerPreference
        ], 200);
    }


    public function destroy(CustomerPreferences $customerPreference): JsonResponse
    {
        $customerPreference->delete();

        return response()->json([
            'message' => 'Customer preference deleted successfully.'
        ], 204); // 204 No Content
    }
}
