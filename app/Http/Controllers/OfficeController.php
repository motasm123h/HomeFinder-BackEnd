<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRealEstateRequestRequest;
use App\Services\OfficeService;
use Illuminate\Http\JsonResponse;

class OfficeController extends Controller
{
    public function __construct(private OfficeService $service) {}

    public function getPaginatedRequests(): JsonResponse
    {
        return response()->json([
            'data' => $this->service->getPaginatedRequests(),
        ]);
    }
    public function getPaginatedSent(): JsonResponse
    {
        return response()->json([
            'data' => $this->service->getPaginatedSent(),
        ]);
    }
    public function details(int $id){
        $data = User::where('id',$id)->frist();

        return respnse()->json([
            'data' => $data->load('contact','address','service','realEstate','verification'),
        ]);
    }

    public function create(StoreRealEstateRequestRequest $request): JsonResponse
    {
        $validated = $request->validated();
        // $validated['sender_id'] = auth()->id();

        return response()->json([
            'data' => $this->service->createRequest($validated),
        ], 201);
    }

    public function delete(int $id): JsonResponse
    {
        $success = $this->service->deleteRequest("sender_id",$id);

        return response()->json([
            'data' => $success,
        ], $success ? 200 : 404);
    }
}