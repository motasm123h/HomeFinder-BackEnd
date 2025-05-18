<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRealEstateRequestRequest;
use App\Models\User;
use App\Notifications\SendRequestNotification;
use App\Services\OfficeService;
use Illuminate\Http\JsonResponse;
use App\Policies\PostPolicy;


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
        // $user= User::where('id',$validated['user_id'])->first();
        // if ((new PostPolicy)->createRequest($user, $validated)) {
        //     return response()->json(['message' => 'Unauthorized'], 403);
        // }

        try {
            $realEstateRequest = $this->service->createRequest($validated);
            
            $user = User::findOrFail($validated['user_id']);
            
            $user->notify(new SendRequestNotification($realEstateRequest));
            
            return response()->json([
                'data' => $realEstateRequest,
                'message' => 'Request created and notification sent successfully'
            ], 201);
            
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'User not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create request',
                'error' => $e->getMessage() 
            ], 500);
        }
    }
    public function delete(int $id): JsonResponse
    {
        if (!(new PostPolicy)->delete(auth()->user(), (int)$id)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $success = $this->service->deleteRequest($id);

        return response()->json([
            'data' => $success,
        ], $success ? 200 : 404);
    }
}