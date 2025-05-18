<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRealEstateRequest;
use App\Http\Requests\updateRealEstateRequest;
use App\Models\RealEstate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\DTOs\RealEstate\CreateRealEstateDto;
use App\DTOs\RealEstate\UpdateRealEstateDto;
use App\Services\ModelService;
use App\Traits\ResponseTrait;
use App\Services\RealEstateQueryFilter;
use App\Models\RealEstate_Location;
use App\Helper\RealEstateHelper;

class RealEstateController extends Controller
{
    use ResponseTrait;
    public function __construct(private ModelService $service) {}

    public function index(Request $request) {
        try {
            $query = RealEstate::query()
                ->with(['location', 'images', 'properties', 'user']);
            
                
            if ($request->anyFilled(['type', 'kind', 'max_price', 'location'])) {
                (new RealEstateQueryFilter)->apply($query, $request->all());
                
                $realEstates = $query->paginate($request->input('per_page', 12));
                return $realEstates;
                
            }
            
            if (!$query->exists()) {
                $query = RealEstate::query()
                ->with(['location', 'images', 'properties', 'user'])
                ->inRandomOrder()
                ->limit(12);
            }
            
            $realEstates = $query->paginate($request->input('per_page', 12))
            ->through(fn ($item) => RealEstateHelper::formatRealEstate($item));
                
            return $this->apiResponse(
                'Real estates retrieved successfully',
                $realEstates,
                200
            );
        } catch (\Exception $e) {
            \Log::error("Real estate index error: " . $e->getMessage());
            return $this->apiResponse(
                'Failed to retrieve real estates',
                $e->getMessage(),
                500
            );
        }
    }

    public function getStatus() {
        $counts = RealEstate::selectRaw('
            COUNT(*) as total_count,
            SUM(CASE WHEN type = "rental" THEN 1 ELSE 0 END) as rental_count,
            SUM(CASE WHEN type = "sale" THEN 1 ELSE 0 END) as sale_count
        ')->first();
    
        return $this->apiResponse(
            'Real estate counts retrieved successfully',
            $counts,
            200
        );
    }


    public function getLocation(): JsonResponse {
        $data = RealEstate_Location::all();
        return $this->apiResponse(
            'Real estates location successfully',
            $data,
            200
        );
    }
    public function create(StoreRealEstateRequest $request) : JsonResponse{     
        try {
            \Log::debug('Files received:', [
                'has_files' => $request->hasFile('images'),
                'file_count' => $request->hasFile('images') ? count($request->file('images')) : 0,
                'file_names' => $request->hasFile('images') 
                    ? array_map(fn($file) => $file->getClientOriginalName(), $request->file('images')) 
                    : []
            ]);
    
            $dto = new CreateRealEstateDto(
                mainData: $request->only($this->getMainFields()),
                properties: $request->only($this->getPropertyFields()),
                images: $request->file('images'), // Changed from 'images_' to 'images'
                userId: auth()->id()
            );
    
            $realEstate = $this->service->createRealEstate($dto);
    
            return $this->apiResponse(
                'Real estate created successfully',
                $realEstate,
                201
            );
    
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->apiResponse(
                'Validation failed',
                ['errors' => $e->validator->errors()],
                422
            );
        } catch (\Exception $e) {
            \Log::error("Real estate creation failed: " . $e->getMessage());
            return $this->apiResponse(
                'Failed to create real estate',
                ['error' => $e->getMessage()],
                500
            );
        }

    }

    public function update(updateRealEstateRequest $request, $id)
    {
        try {
            $dto = new UpdateRealEstateDto(
                mainData: $request->only($this->getMainFields()),
                properties: $request->only($this->getPropertyFields()),
                images: $request->file('images_'),
                realEstateId: $id
            );
    
            $updatedRealEstate = $this->service->updateRealEstate($dto);
    
            return $this->apiResponse(
                'Real estate updated successfully',
                $updatedRealEstate,
                200
            );
    
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->apiResponse(
                'Real estate not found',
                null,
                404
            );
        } catch (\Exception $e) {
            \Log::error("Real estate update failed for ID {$id}: " . $e->getMessage());
            return $this->apiResponse(
                'Failed to update real estate',
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    public function delete(int $id){
        
        try {
            $result = $this->service->deleteRealEstate($id);
    
            if (!$result) {
                throw new \Exception("Failed to delete real estate");
            }
    
            return $this->apiResponse(
                'Real estate deleted successfully',
                null,
                200
            );
    
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->apiResponse(
                'Real estate not found',
                null,
                404
            );
        } catch (\Exception $e) {
            \Log::error("Real estate deletion failed for ID {$id}: " . $e->getMessage());
            return $this->apiResponse(
                'Failed to delete real estate',
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    
    public function getDetails(int $id){
        try {
            $details = $this->service->getDetails($id);
    
            if (!$details) {
                throw new \Illuminate\Database\Eloquent\ModelNotFoundException();
            }
    
            return $this->apiResponse(
                'Real estate details retrieved successfully',
                $details,
                200
            );
    
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->apiResponse(
                'Real estate not found',
                null,
                404
            );
        } catch (\Exception $e) {
            \Log::error("Failed to get details for real estate ID {$id}: " . $e->getMessage());
            return $this->apiResponse(
                'Failed to retrieve real estate details',
                ['error' => $e->getMessage()],
                500
            );
        }
    }


    public function getMainFields(): array {
        return [
            'latitude',
            'longitude',
            'type',
            'price',
            'status',
            'description',
            'kind',
            'user_id',
            'real_estate_location_id'
        ];
    }

    public function getPropertyFields(): array {
        return [
            'electricity_status',
            'water_status',
            'transportation_status',
            'water_well',
            'solar_energy',
            'garage',
            'room_no',
            'direction',
            'space_status',
            'elevator',
            'floor',
            'garden_status',
            'attired',
            'ownership_type',
            'real_estate_id'
        ];
    }
}
