<?php

namespace App\Http\Controllers;

use App\Exceptions\ApiException; // Assuming this is correct request for OfficeController
use App\Http\Requests\StoreRealEstateRequestRequest;
use App\Models\User;
use App\Notifications\SendRequestNotification;
use App\Policies\PostPolicy;
use App\Services\OfficeService;
use App\Traits\ResponseTrait;
use Illuminate\Database\Eloquent\ModelNotFoundException; // Import your custom exception
use Illuminate\Http\JsonResponse; // Keep this to throw explicitly if needed

class OfficeController extends Controller
{
    use ResponseTrait;

    public function __construct(private OfficeService $service) {}

    public function getPaginatedRequests(): JsonResponse
    {
        return $this->apiResponse(
            'Data retrieved',
            $this->service->getPaginatedRequests(),
            200
        );
    }

    public function getPaginatedSent(): JsonResponse
    {
        return $this->apiResponse(
            'Data retrieved',
            $this->service->getPaginatedSent(),
            200
        );
    }

    public function details(int $id): JsonResponse
    {
        // Using findOrFail will automatically throw ModelNotFoundException if not found,
        // which your Handler will catch and turn into a 404.
        $data = User::findOrFail($id);

        return $this->apiResponse(
            'Data retrieved',
            $data->load('contact', 'address', 'service', 'realEstate', 'verification'),
            200
        );
    }

    public function create(StoreRealEstateRequestRequest $request): JsonResponse
    {
        $validated = $request->validated(); // Validation handled by Form Request

        // The service should ideally throw exceptions for specific errors
        $realEstateRequest = $this->service->createRequest($validated);

        // This findOrFail will throw ModelNotFoundException if user is not found,
        // caught by the Handler.
        $user = User::findOrFail($validated['user_id']);
        $user->notify(new SendRequestNotification($realEstateRequest));

        return $this->apiResponse(
            'Request created and notification sent successfully',
            $realEstateRequest,
            201
        );
    }

    public function delete(int $id): JsonResponse
    {
        // Policy check remains here as it's authorization logic
        if (! (new PostPolicy)->delete(auth()->user(), $id)) {
            // Throwing an ApiException for an unauthorized scenario
            throw new ApiException('Unauthorized', 403);
        }

        // The service should ideally throw ModelNotFoundException if the request to delete doesn't exist.
        // If it returns false for other reasons, you might need a custom exception from the service.
        $success = $this->service->deleteRequest($id);

        if (! $success) {
            // If deleteRequest returns false because the item wasn't found,
            // or if it should throw a ModelNotFoundException internally, that's better.
            // For now, assuming false means "not found" or "failed deletion".
            throw new ApiException('Request not found or failed to delete', 404);
        }

        return $this->apiResponse(
            'Request deleted successfully',
            null,
            200
        );
    }
}
