<?php

namespace App\Http\Controllers;

use App\DTOs\RealEstate\CreateRealEstateDto;
use App\DTOs\RealEstate\UpdateRealEstateDto;
use App\Helper\RealEstateHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRealEstateRequest;
use App\Http\Requests\updateRealEstateRequest;
use App\Models\RealEstate;
use App\Models\RealEstate_images;
use App\Models\RealEstate_Location;
use App\Services\ModelService;
use App\Services\RealEstateQueryFilter;
use App\Traits\ResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Exceptions\ApiException;

class RealEstateController extends Controller
{
    use ResponseTrait;

    public function __construct(private ModelService $service) {}

    public function index(Request $request)
    {
        $query = RealEstate::query()
            ->with(['location', 'images', 'properties', 'user']);

        if ($request->anyFilled(['type', 'kind', 'max_price', 'location'])) {
            (new RealEstateQueryFilter)->apply($query, $request->all());
        } else {

            if (!$query->exists() && !$request->anyFilled(['type', 'kind', 'max_price', 'location'])) {
                $query->inRandomOrder()->limit(12);
            }
        }

        $realEstates = $query->paginate($request->input('per_page', 12))
            ->through(fn($item) => RealEstateHelper::formatRealEstate($item));

        return $this->apiResponse(
            'Real estates retrieved successfully',
            $realEstates,
            200
        );
    }

    public function getStatus()
    {
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

    public function getLocation(): JsonResponse
    {
        $data = RealEstate_Location::all();
        return $this->apiResponse(
            'Real estates location successfully',
            $data,
            200
        );
    }

    public function create(StoreRealEstateRequest $request): JsonResponse
    {
        Log::debug('Files received:', [
            'has_files' => $request->hasFile('images'),
            'file_count' => $request->hasFile('images') ? count($request->file('images')) : 0,
            'file_names' => $request->hasFile('images')
                ? array_map(fn($file) => $file->getClientOriginalName(), $request->file('images'))
                : []
        ]);

        $dto = new CreateRealEstateDto(
            mainData: $request->only($this->getMainFields()),
            properties: $request->only($this->getPropertyFields()),
            images: $request->file('images'),
            userId: auth()->id()
        );

        $realEstate = $this->service->createRealEstate($dto);

        return $this->apiResponse(
            'Real estate created successfully',
            $realEstate,
            201
        );
    }

    public function Add360(Request $request, int $id)
    {
        $disk = 'public';
        $path = 'uploads';
        foreach ($request->file('images') as $file) {
            $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $extension = $file->getClientOriginalExtension();
            $timestamp = now()->timestamp;
            $randomString = Str::random(8);

            $cleanName = Str::slug($originalName);
            $newFilename = "{$cleanName}-{$timestamp}-{$randomString}.{$extension}";
            $filePath = $file->storeAs($path, $newFilename, $disk);

            RealEstate_images::create([
                'name' => $filePath,
                'type' => '360',
                'real_estate_id' => $id,
            ]);
        }
        return $this->apiResponse(
            '360 images added successfully',
            null,
            200
        );
    }

    public function update(updateRealEstateRequest $request, $id)
    {
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
    }

    public function delete(int $id)
    {
        $result = $this->service->deleteRealEstate($id);

        if (!$result) {
            throw new ApiException("Failed to delete real estate or real estate not found", 404);
        }

        return $this->apiResponse(
            'Real estate deleted successfully',
            null,
            200
        );
    }

    public function getDetails(int $id)
    {
        $details = $this->service->getDetails($id);

        if (!$details) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException("Real estate with ID {$id} not found.");
        }

        return $this->apiResponse(
            'Real estate details retrieved successfully',
            $details,
            200
        );
    }

    public function getMainFields(): array
    {
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

    public function getPropertyFields(): array
    {
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
