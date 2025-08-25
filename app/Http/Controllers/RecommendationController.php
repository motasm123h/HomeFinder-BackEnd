<?php

namespace App\Http\Controllers;

use App\Services\RealEstateRecommendationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RecommendationController extends Controller
{
    protected $recommendationService;

    public function __construct(RealEstateRecommendationService $recommendationService)
    {
        $this->recommendationService = $recommendationService;
    }

    public function getRecommendations(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (! $user) {
            return response()->json([
                'message' => 'Unauthenticated. Please log in to get recommendations.',
            ], 401);
        }

        $customerPreference = $user->customerPreference;
        if (! $customerPreference) {
            return response()->json([
                'message' => 'No customer preferences found for your account. Please set your preferences first.',
                'data' => [],
            ], 404);
        }

        $request->validate([
            'limit' => 'sometimes|integer|min:1|max:50',
        ]);

        $limit = $request->input('limit', 10);
        $filters = $request->only(['real_estate_type', 'real_estate_kind']);

        $recommendations = $this->recommendationService->getRecommendations($customerPreference, $limit, $filters);

        if ($recommendations->isEmpty()) {
            return response()->json([
                'message' => 'No real estate recommendations found matching your preferences.',
                'data' => [],
            ], 404);
        }

        return response()->json([
            'message' => 'Real estate recommendations retrieved successfully based on your preferences.',
            'data' => $recommendations->values()->all(),
        ], 200);
    }
}
