<?php

namespace App\Http\Controllers;

use App\DTOs\RealEstate\CreateRealEstateDto;
use App\DTOs\RealEstate\UpdateRealEstateDto;
use App\Exceptions\ApiException;
use App\Helper\RealEstateHelper;
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

class RealEstateController extends Controller
{
    use ResponseTrait;

    private const STATUS_MAP = [
        'open' => 'Open',
        'closed' => 'Closed',
    ];

    private const TYPE_MAP = [
        'rental' => 'For Rent',
        'sale' => 'For Sale',
        'للبيع' => 'For Sale',
        'للأيجار' => 'For Rent',
    ];

    private const KIND_MAP = [
        'apartment' => 'Apartment',
        'villa' => 'Villa',
        'chalet' => 'Chalet',
        'شقة' => 'Apartment',
        'فيلا' => 'Villa',
        'شاليه' => 'Chalet',
    ];

    private const QUALITY_STATUS_MAP = [
        '1' => 'Good',
        '2' => 'Average',
        '3' => 'Bad',
    ];

    private const YES_NO_MAP = [
        '1' => 'Yes',
        '2' => 'No',
    ];

    private const DIRECTION_MAP = [
        '1' => 'One Direction',
        '2' => 'Two Direction',
        '3' => 'Three Direction',
        '4' => 'Four Direction',
    ];

    private const ATTIRED_MAP = [
        '1' => 'Fully Furnished/Well-Maintained',
        '2' => 'Partially Furnished/Average-Maintained',
        '3' => 'Not Furnished/Poorly-Maintained',
    ];

    private const OWNERSHIP_TYPE_MAP = [
        'green' => 'Green Ownership',
        'court' => 'Court Ownership',
    ];

    public function __construct(private ModelService $service) {}

    public function index(Request $request)
    {
        $query = RealEstate::query()
            ->with(['location', 'images', 'properties', 'user']);

        if ($request->anyFilled(['type', 'kind', 'max_price', 'location'])) {
            (new RealEstateQueryFilter)->apply($query, $request->all());
        } else {

            if (! $query->exists() && ! $request->anyFilled(['type', 'kind', 'max_price', 'location'])) {
                $query->inRandomOrder()->limit(12);
            }
        }

        $realEstates = $query->paginate($request->input('per_page', 12))
            ->through(fn ($item) => RealEstateHelper::formatRealEstate($item));

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
                ? array_map(fn ($file) => $file->getClientOriginalName(), $request->file('images'))
                : [],
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
        $disk = 'real_estate';
        $path = 'real-estate/images';
        if (! $request->hasFile('images')) {
            return $this->apiResponse(
                'images is required',
                null,
                422
            );
        }
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
        $syncFiles = shell_exec('rsync -av --delete /home/bookus/repositories/mot/public/storage /home/bookus/public_html/mot.4bookus.com/');
        Log::info($syncFiles, ['message' => 'from media service']);

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

        if (! $result) {
            throw new ApiException('Failed to delete real estate or real estate not found', 404);
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

        if (! $details) {
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
            'real_estate_location_id',
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
            'real_estate_id',
        ];
    }

    public function compare(Request $request): JsonResponse
    {
        $request->validate([
            'real_estate_id_1' => 'required|integer|exists:real_estates,id',
            'real_estate_id_2' => 'required|integer|exists:real_estates,id',
            'preferences' => 'nullable|array',
            'preferences.desired_type' => 'nullable|in:rental,sale,للبيع,للأيجار',
            'preferences.desired_kind' => 'nullable|in:apartment,villa,chalet,شقة,فيلا,شاليه',
            'preferences.min_price' => 'nullable|integer|min:0',
            'preferences.max_price' => 'nullable|integer|min:0',
            'preferences.min_rooms' => 'nullable|integer|min:0',
            'preferences.desired_electricity_status' => 'nullable|in:1,2,3',
            'preferences.desired_water_status' => 'nullable|in:1,2,3',
            'preferences.desired_transportation_status' => 'nullable|in:1,2,3',
            'preferences.want_water_well' => 'nullable|boolean',
            'preferences.want_solar_energy' => 'nullable|boolean',
            'preferences.want_garage' => 'nullable|boolean',
            'preferences.want_elevator' => 'nullable|boolean',
            'preferences.want_garden' => 'nullable|boolean',
            'preferences.min_space_status' => 'nullable|integer|min:0',
            'preferences.desired_attired_status' => 'nullable|in:1,2,3',
            'preferences.desired_ownership_type' => 'nullable|in:green,court',
        ]);

        if ($request->has('preferences.min_price') && $request->has('preferences.max_price')) {
            if ($request->input('preferences.min_price') > $request->input('preferences.max_price')) {
                return response()->json(['message' => 'Min price cannot be greater than max price.'], 422);
            }
        }

        $id1 = $request->input('real_estate_id_1');
        $id2 = $request->input('real_estate_id_2');
        $preferences = $request->input('preferences', []);
        $realEstate1 = RealEstate::with('properties', 'location')->find($id1);
        $realEstate2 = RealEstate::with('properties', 'location')->find($id2);

        if (! $realEstate1 || ! $realEstate2) {
            return response()->json(['message' => 'One or both RealEstate IDs not found.'], 404);
        }

        $score1 = $this->calculateRealEstateScore($realEstate1, $preferences);
        $score2 = $this->calculateRealEstateScore($realEstate2, $preferences);

        $comparisonResult = $this->getComparisonBreakdown($realEstate1, $realEstate2, $preferences);

        return response()->json([
            'real_estate_1' => [
                'id' => $realEstate1->id,
                'total_score' => $score1,
            ],
            'real_estate_2' => [
                'id' => $realEstate2->id,
                'total_score' => $score2,
            ],
            'comparison_breakdown' => $comparisonResult,
            'overall_winner' => $score1 > $score2 ? 'RealEstate '.$id1 : ($score2 > $score1 ? 'RealEstate '.$id2 : 'Tie'),
            'message' => $score1 > $score2 ? "RealEstate {$id1} scores higher." : ($score2 > $score1 ? "RealEstate {$id2} scores higher." : "RealEstate {$id1} and {$id2} have the same total score."),
        ]);
    }

    private function calculateRealEstateScore(RealEstate $realEstate, array $preferences = []): int
    {
        $score = 0;

        $score += $realEstate->total_weight ?? 0;

        $minPrice = $preferences['min_price'] ?? null;
        $maxPrice = $preferences['max_price'] ?? null;

        if ($minPrice !== null || $maxPrice !== null) {
            $price = $realEstate->price;
            $isInRange = true;

            if ($minPrice !== null && $price < $minPrice) {
                $isInRange = false;
                $score -= 30;
            }
            if ($maxPrice !== null && $price > $maxPrice) {
                $isInRange = false;
                $score -= 50;
            }

            if ($isInRange) {
                $score += 40; // Significant bonus for being within the desired range
                // Further refine: add bonus for being closer to a preferred point in the range
                if ($minPrice !== null && $maxPrice !== null && ($maxPrice - $minPrice) > 0) {
                    $midPrice = ($minPrice + $maxPrice) / 2;
                    $deviation = abs($price - $midPrice);
                    $rangeSize = $maxPrice - $minPrice;
                    $score += (1 - ($deviation / $rangeSize)) * 20; // Max 20 bonus for being exactly at midpoint
                }
            } else {
                // If not in range, still give a small base score if somewhat close (within 10% outside range)
                $lowerBoundCheck = ($minPrice !== null) ? $price >= $minPrice - ($minPrice * 0.1) : true;
                $upperBoundCheck = ($maxPrice !== null) ? $price <= $maxPrice + ($maxPrice * 0.1) : true;
                if ($lowerBoundCheck && $upperBoundCheck) {
                    $score += 5; // Small consolation score
                }
            }
        } else {
            if ($realEstate->price < 100000000) {
                $score += 30;
            } elseif ($realEstate->price >= 100000000 && $realEstate->price < 150000000) {
                $score += 20;
            } elseif ($realEstate->price >= 150000000 && $realEstate->price < 3000000000) {
                $score += 10;
            } else {
                $score -= 5;
            }
        }

        $desiredType = $preferences['desired_type'] ?? null;
        if ($desiredType !== null) {
            $normalizedRealEstateType = self::TYPE_MAP[$realEstate->type] ?? $realEstate->type;
            $normalizedDesiredType = self::TYPE_MAP[$desiredType] ?? $desiredType;

            if (strtolower($normalizedRealEstateType) === strtolower($normalizedDesiredType)) {
                $score += 20;
            } else {
                $score -= 10;
            }
        } else {
            if (in_array($realEstate->type, ['sale', 'للبيع'])) {
                $score += 5;
            }
        }

        $desiredKind = $preferences['desired_kind'] ?? null;
        if ($desiredKind !== null) {
            $normalizedRealEstateKind = self::KIND_MAP[$realEstate->kind] ?? $realEstate->kind;
            $normalizedDesiredKind = self::KIND_MAP[$desiredKind] ?? $desiredKind;

            if (strtolower($normalizedRealEstateKind) === strtolower($normalizedDesiredKind)) {
                $score += 25;
            } else {
                $score -= 15;
            }
        } else {
            if (in_array($realEstate->kind, ['villa', 'فيلا'])) {
                $score += 15;
            } elseif (in_array($realEstate->kind, ['apartment', 'شقة'])) {
                $score += 10;
            } elseif (in_array($realEstate->kind, ['chalet', 'شاليه'])) {
                $score += 8;
            }
        }

        if ($realEstate->properties) {
            $properties = $realEstate->properties;

            $desiredElecStatus = $preferences['desired_electricity_status'] ?? null;
            if ($desiredElecStatus !== null) {
                if (($properties->electricity_status ?? null) === $desiredElecStatus) {
                    $score += 10;
                } else {
                    $score -= 5;
                }
            } else {
                $score += $this->getQualityScore($properties->electricity_status ?? null);
            }

            $desiredWaterStatus = $preferences['desired_water_status'] ?? null;
            if ($desiredWaterStatus !== null) {
                if (($properties->water_status ?? null) === $desiredWaterStatus) {
                    $score += 10;
                } else {
                    $score -= 5;
                }
            } else {
                $score += $this->getQualityScore($properties->water_status ?? null);
            }

            // Transportation Status
            $desiredTransStatus = $preferences['desired_transportation_status'] ?? null;
            if ($desiredTransStatus !== null) {
                if (($properties->transportation_status ?? null) === $desiredTransStatus) {
                    $score += 10;
                } else {
                    $score -= 5;
                }
            } else {
                $score += $this->getQualityScore($properties->transportation_status ?? null);
            }

            // Want Water Well
            $wantWaterWell = $preferences['want_water_well'] ?? null;
            if ($wantWaterWell !== null) {
                if ($wantWaterWell === true && ($properties->water_well ?? null) == '1') {
                    $score += 20;
                } elseif ($wantWaterWell === false && ($properties->water_well ?? null) == '2') {
                    $score += 5;
                } elseif ($wantWaterWell === true && ($properties->water_well ?? null) == '2') {
                    $score -= 15;
                }
            } else {
                if (($properties->water_well ?? null) == '1') {
                    $score += 10;
                }
            }

            $wantSolarEnergy = $preferences['want_solar_energy'] ?? null;
            if ($wantSolarEnergy !== null) {
                if ($wantSolarEnergy === true && ($properties->solar_energy ?? null) == '1') {
                    $score += 30;
                } elseif ($wantSolarEnergy === false && ($properties->solar_energy ?? null) == '2') {
                    $score += 5;
                } elseif ($wantSolarEnergy === true && ($properties->solar_energy ?? null) == '2') {
                    $score -= 20;
                }
            } else {
                if (($properties->solar_energy ?? null) == '1') {
                    $score += 20;
                }
            }

            $wantGarage = $preferences['want_garage'] ?? null;
            if ($wantGarage !== null) {
                if ($wantGarage === true && ($properties->garage ?? null) == '1') {
                    $score += 20;
                } elseif ($wantGarage === false && ($properties->garage ?? null) == '2') {
                    $score += 5;
                } elseif ($wantGarage === true && ($properties->garage ?? null) == '2') {
                    $score -= 15;
                }
            } else {
                if (($properties->garage ?? null) == '1') {
                    $score += 15;
                }
            }

            $wantElevator = $preferences['want_elevator'] ?? null;
            if ($wantElevator !== null) {
                if ($wantElevator === true && ($properties->elevator ?? null) == '1') {
                    $score += 15;
                } elseif ($wantElevator === false && ($properties->elevator ?? null) == '2') {
                    $score += 5;
                } elseif ($wantElevator === true && ($properties->elevator ?? null) == '2') {
                    $score -= 10;
                }
            } else {
                if (($properties->elevator ?? null) == '1') {
                    $score += 10;
                }
            }

            $wantGarden = $preferences['want_garden'] ?? null;
            if ($wantGarden !== null) {
                if ($wantGarden === true && ($properties->garden_status ?? null) == '1') {
                    $score += 20;
                } elseif ($wantGarden === false && ($properties->garden_status ?? null) == '2') {
                    $score += 5;
                } elseif ($wantGarden === true && ($properties->garden_status ?? null) == '2') {
                    $score -= 15;
                }
            } else {
                if (($properties->garden_status ?? null) == '1') {
                    $score += 15;
                }
            }

            $minRooms = $preferences['min_rooms'] ?? null;
            if ($minRooms !== null) {
                if (($properties->room_no ?? 0) >= $minRooms) {
                    $score += 15;
                } else {
                    $score -= 10;
                }
            } else {
                $score += ($properties->room_no ?? 0) * 5;
            }

            $score += $this->getQualityScore($properties->direction ?? null);

            $minSpaceStatus = $preferences['min_space_status'] ?? null;
            if ($minSpaceStatus !== null) {
                if (($properties->space_status ?? 0) >= $minSpaceStatus) {
                    $score += 15;
                } else {
                    $score -= 10;
                }
            } else {
                $score += ($properties->space_status ?? 0) * 2;
            }

            if (($properties->floor ?? 0) > 0 && ($properties->floor ?? 0) <= 5) {
                $score += 8;
            } elseif (($properties->floor ?? 0) > 5) {
                $score += 3;
            }

            $desiredAttiredStatus = $preferences['desired_attired_status'] ?? null;
            if ($desiredAttiredStatus !== null) {
                if (($properties->attired ?? null) === $desiredAttiredStatus) {
                    $score += 10;
                } else {
                    $score -= 5;
                }
            } else {
                switch ($properties->attired ?? null) {
                    case '1':
                        $score += 10;
                        break;
                    case '2':
                        $score += 5;
                        break;
                    case '3':
                        $score -= 5;
                        break;
                }
            }

            $desiredOwnershipType = $preferences['desired_ownership_type'] ?? null;
            if ($desiredOwnershipType !== null) {
                if (($properties->ownership_type ?? null) === $desiredOwnershipType) {
                    $score += 10;
                } else {
                    $score -= 5;
                }
            } else {
                if (($properties->ownership_type ?? null) === 'green') {
                    $score += 10;
                }
            }

            $score += $properties->total_weight ?? 0;
        }

        return $score;
    }

    private function getQualityScore(?string $status): int
    {
        switch ($status) {
            case '1':
                return 10;
            case '2':
                return 5;
            case '3':
                return -5;
            default:
                return 0;
        }
    }

    private function getComparisonBreakdown(RealEstate $realEstate1, RealEstate $realEstate2, array $preferences = []): array
    {
        $breakdown = [];

        $minPrice = $preferences['min_price'] ?? null;
        $maxPrice = $preferences['max_price'] ?? null;

        if ($minPrice !== null || $maxPrice !== null) {
            $price1 = $realEstate1->price;
            $price2 = $realEstate2->price;

            $isInRange1 = ($minPrice === null || $price1 >= $minPrice) && ($maxPrice === null || $price1 <= $maxPrice);
            $isInRange2 = ($minPrice === null || $price2 >= $minPrice) && ($maxPrice === null || $price2 <= $maxPrice);

            $winnerPrice = 'Tie';
            $descriptionPrice = '';

            if ($isInRange1 && ! $isInRange2) {
                $winnerPrice = 'RealEstate 1';
                $descriptionPrice = "RealEstate {$realEstate1->id} is within your preferred price range ({$minPrice}-{$maxPrice}), while RealEstate {$realEstate2->id} is not.";
            } elseif (! $isInRange1 && $isInRange2) {
                $winnerPrice = 'RealEstate 2';
                $descriptionPrice = "RealEstate {$realEstate2->id} is within your preferred price range ({$minPrice}-{$maxPrice}), while RealEstate {$realEstate1->id} is not.";
            } elseif ($isInRange1 && $isInRange2) {
                $winnerPrice = ($realEstate1->price <= $realEstate2->price) ? 'RealEstate 1' : 'RealEstate 2';
                $descriptionPrice = 'Both are in range. RealEstate '.($winnerPrice === 'RealEstate 1' ? $realEstate1->id : $realEstate2->id).' has a slightly better price within range.';
            } else {
                $winnerPrice = ($realEstate1->price < $realEstate2->price) ? 'RealEstate 1' : 'RealEstate 2';
                $descriptionPrice = 'Neither is in your preferred price range. RealEstate '.($winnerPrice === 'RealEstate 1' ? $realEstate1->id : $realEstate2->id).' has a lower price.';
            }

            $breakdown['price'] = [
                'title' => 'Price',
                'value_1' => $realEstate1->price,
                'value_2' => $realEstate2->price,
                'user_preferred_range' => ($minPrice ?? 'N/A').'-'.($maxPrice ?? 'N/A'),
                'RealEstate 1 In Range' => $isInRange1 ? 'Yes' : 'No',
                'RealEstate 2 In Range' => $isInRange2 ? 'Yes' : 'No',
                'winner' => $winnerPrice,
                'description' => $descriptionPrice,
            ];
        } else {
            $breakdown['price'] = $this->compareNumericField(
                'Price',
                $realEstate1->price,
                $realEstate2->price,
                true
            );
        }

        // $desiredStatus = $preferences['desired_status'] ?? null;
        // $breakdown['status'] = $this->compareEnumFieldWithPreference(
        //     'Status',
        //     $realEstate1->status,
        //     $realEstate2->status,
        //     self::STATUS_MAP,
        //     ['open' => 2, 'closed' => 1],
        //     $desiredStatus
        // );

        $desiredType = $preferences['desired_type'] ?? null;
        $breakdown['type'] = $this->compareEnumFieldWithPreference(
            'Type',
            $realEstate1->type,
            $realEstate2->type,
            self::TYPE_MAP,
            ['sale' => 2, 'للبيع' => 2, 'rental' => 1, 'للأيجار' => 1],
            $desiredType
        );

        // $breakdown['hidden'] = $this->compareBooleanField(
        //     'Visibility',
        //     $realEstate1->hidden == 0,
        //     $realEstate2->hidden == 0,
        //     'Visible',
        //     'Hidden'
        // );

        $desiredKind = $preferences['desired_kind'] ?? null;
        $breakdown['kind'] = $this->compareEnumFieldWithPreference(
            'Kind',
            $realEstate1->kind,
            $realEstate2->kind,
            self::KIND_MAP,
            ['villa' => 3, 'فيلا' => 3, 'apartment' => 2, 'شقة' => 2, 'chalet' => 1, 'شاليه' => 1], // Default priority
            $desiredKind
        );

        $breakdown['main_total_weight'] = $this->compareNumericField(
            'Main Total Weight',
            $realEstate1->total_weight ?? 0,
            $realEstate2->total_weight ?? 0,
            false
        );

        $props1 = $realEstate1->properties;
        $props2 = $realEstate2->properties;

        if ($props1 && $props2) {
            $desiredElecStatus = $preferences['desired_electricity_status'] ?? null;
            $breakdown['electricity_status'] = $this->compareEnumFieldWithPreference(
                'Electricity Status',
                $props1->electricity_status ?? null,
                $props2->electricity_status ?? null,
                self::QUALITY_STATUS_MAP,
                ['1' => 3, '2' => 2, '3' => 1],
                $desiredElecStatus
            );

            $desiredWaterStatus = $preferences['desired_water_status'] ?? null;
            $breakdown['water_status'] = $this->compareEnumFieldWithPreference(
                'Water Status',
                $props1->water_status ?? null,
                $props2->water_status ?? null,
                self::QUALITY_STATUS_MAP,
                ['1' => 3, '2' => 2, '3' => 1],
                $desiredWaterStatus
            );

            $desiredTransStatus = $preferences['desired_transportation_status'] ?? null;
            $breakdown['transportation_status'] = $this->compareEnumFieldWithPreference(
                'Transportation Status',
                $props1->transportation_status ?? null,
                $props2->transportation_status ?? null,
                self::QUALITY_STATUS_MAP,
                ['1' => 3, '2' => 2, '3' => 1],
                $desiredTransStatus
            );

            $wantWaterWell = $preferences['want_water_well'] ?? null;
            $breakdown['water_well'] = $this->compareBooleanFieldWithPreference(
                'Water Well',
                ($props1->water_well ?? null) == '1',
                ($props2->water_well ?? null) == '1',
                'Has Water Well',
                'No Water Well',
                $wantWaterWell
            );

            $wantSolarEnergy = $preferences['want_solar_energy'] ?? null;
            $breakdown['solar_energy'] = $this->compareBooleanFieldWithPreference(
                'Solar Energy',
                ($props1->solar_energy ?? null) == '1',
                ($props2->solar_energy ?? null) == '1',
                'Has Solar Energy',
                'No Solar Energy',
                $wantSolarEnergy
            );

            $wantGarage = $preferences['want_garage'] ?? null;
            $breakdown['garage'] = $this->compareBooleanFieldWithPreference(
                'Garage',
                ($props1->garage ?? null) == '1',
                ($props2->garage ?? null) == '1',
                'Has Garage',
                'No Garage',
                $wantGarage
            );

            $wantElevator = $preferences['want_elevator'] ?? null;
            $breakdown['elevator'] = $this->compareBooleanFieldWithPreference(
                'Elevator',
                ($props1->elevator ?? null) == '1',
                ($props2->elevator ?? null) == '1',
                'Has Elevator',
                'No Elevator',
                $wantElevator
            );

            $wantGarden = $preferences['want_garden'] ?? null;
            $breakdown['garden_status'] = $this->compareBooleanFieldWithPreference(
                'Garden',
                ($props1->garden_status ?? null) == '1',
                ($props2->garden_status ?? null) == '1',
                'Has Garden',
                'No Garden',
                $wantGarden
            );

            $minRooms = $preferences['min_rooms'] ?? null;
            if ($minRooms !== null) {
                $rooms1Meet = ($props1->room_no ?? 0) >= $minRooms;
                $rooms2Meet = ($props2->room_no ?? 0) >= $minRooms;

                $winnerRooms = 'Tie';
                $descriptionRooms = '';

                if ($rooms1Meet && ! $rooms2Meet) {
                    $winnerRooms = 'RealEstate 1';
                    $descriptionRooms = "RealEstate {$realEstate1->id} meets your minimum room requirement ({$minRooms}), while RealEstate {$realEstate2->id} does not.";
                } elseif (! $rooms1Meet && $rooms2Meet) {
                    $winnerRooms = 'RealEstate 2';
                    $descriptionRooms = "RealEstate {$realEstate2->id} meets your minimum room requirement ({$minRooms}), while RealEstate {$realEstate1->id} does not.";
                } elseif ($rooms1Meet && $rooms2Meet) {
                    $winnerRooms = (($props1->room_no ?? 0) > ($props2->room_no ?? 0)) ? 'RealEstate 1' : (($props2->room_no ?? 0) > ($props1->room_no ?? 0) ? 'RealEstate 2' : 'Tie');
                    $descriptionRooms = 'Both meet your minimum. '.($winnerRooms === 'Tie' ? 'Both have the same number of rooms.' : $winnerRooms.' has more rooms.');
                } else {
                    $winnerRooms = (($props1->room_no ?? 0) > ($props2->room_no ?? 0)) ? 'RealEstate 1' : (($props2->room_no ?? 0) > ($props1->room_no ?? 0) ? 'RealEstate 2' : 'Tie');
                    $descriptionRooms = 'Neither meets your minimum. '.($winnerRooms === 'Tie' ? 'Both have the same number of rooms.' : $winnerRooms.' has more rooms.');
                }
                $breakdown['room_no'] = [
                    'title' => 'Number of Rooms',
                    'value_1' => $props1->room_no ?? 0,
                    'value_2' => $props2->room_no ?? 0,
                    'user_min_rooms' => $minRooms ?? 'N/A',
                    'winner' => $winnerRooms,
                    'description' => $descriptionRooms,
                ];
            } else {
                $breakdown['room_no'] = $this->compareNumericField(
                    'Number of Rooms',
                    $props1->room_no ?? 0,
                    $props2->room_no ?? 0,
                    false
                );
            }

            $breakdown['direction'] = $this->compareEnumField(
                'Direction',
                $props1->direction ?? null,
                $props2->direction ?? null,
                self::DIRECTION_MAP,
                ['1' => 3, '2' => 2, '3' => 1]
            );

            $minSpaceStatus = $preferences['min_space_status'] ?? null;
            if ($minSpaceStatus !== null) {
                $space1Meet = ($props1->space_status ?? 0) >= $minSpaceStatus;
                $space2Meet = ($props2->space_status ?? 0) >= $minSpaceStatus;

                $winnerSpace = 'Tie';
                $descriptionSpace = '';

                if ($space1Meet && ! $space2Meet) {
                    $winnerSpace = 'RealEstate 1';
                    $descriptionSpace = "RealEstate {$realEstate1->id} meets your minimum space requirement ({$minSpaceStatus}), while RealEstate {$realEstate2->id} does not.";
                } elseif (! $space1Meet && $space2Meet) {
                    $winnerSpace = 'RealEstate 2';
                    $descriptionSpace = "RealEstate {$realEstate2->id} meets your minimum space requirement ({$minSpaceStatus}), while RealEstate {$realEstate1->id} does not.";
                } elseif ($space1Meet && $space2Meet) {
                    $winnerSpace = (($props1->space_status ?? 0) > ($props2->space_status ?? 0)) ? 'RealEstate 1' : (($props2->space_status ?? 0) > ($props1->space_status ?? 0) ? 'RealEstate 2' : 'Tie');
                    $descriptionSpace = 'Both meet your minimum. '.($winnerSpace === 'Tie' ? 'Both have the same space.' : $winnerSpace.' has more space.');
                } else {
                    $winnerSpace = (($props1->space_status ?? 0) > ($props2->space_status ?? 0)) ? 'RealEstate 1' : (($props2->space_status ?? 0) > ($props1->space_status ?? 0) ? 'RealEstate 2' : 'Tie');
                    $descriptionSpace = 'Neither meets your minimum. '.($winnerSpace === 'Tie' ? 'Both have the same space.' : $winnerSpace.' has more space.');
                }
                $breakdown['space_status'] = [
                    'title' => 'Space Status',
                    'value_1' => $props1->space_status ?? 0,
                    'value_2' => $props2->space_status ?? 0,
                    'user_min_space_status' => $minSpaceStatus ?? 'N/A',
                    'winner' => $winnerSpace,
                    'description' => $descriptionSpace,
                ];
            } else {
                $breakdown['space_status'] = $this->compareNumericField(
                    'Space Status',
                    $props1->space_status ?? 0,
                    $props2->space_status ?? 0,
                    false
                );
            }

            $breakdown['floor'] = $this->compareNumericField(
                'Floor',
                $props1->floor ?? 0,
                $props2->floor ?? 0,
                false,
                fn ($val1, $val2) => "RealEstate {$realEstate1->id}: Floor {$val1}, RealEstate {$realEstate2->id}: Floor {$val2}"
            );

            $desiredAttiredStatus = $preferences['desired_attired_status'] ?? null;
            $breakdown['attired'] = $this->compareEnumFieldWithPreference(
                'Attired Status',
                $props1->attired ?? null,
                $props2->attired ?? null,
                self::ATTIRED_MAP,
                ['1' => 3, '2' => 2, '3' => 1],
                $desiredAttiredStatus
            );

            $desiredOwnershipType = $preferences['desired_ownership_type'] ?? null;
            $breakdown['ownership_type'] = $this->compareEnumFieldWithPreference(
                'Ownership Type',
                $props1->ownership_type ?? null,
                $props2->ownership_type ?? null,
                self::OWNERSHIP_TYPE_MAP,
                ['green' => 2, 'court' => 1],
                $desiredOwnershipType
            );

            $breakdown['properties_total_weight'] = $this->compareNumericField(
                'Properties Total Weight',
                $props1->total_weight ?? 0,
                $props2->total_weight ?? 0,
                false
            );
        } else {
            $breakdown['properties_availability'] = [
                'title' => 'Detailed Property Features',
                'value_1' => $props1 ? 'Available' : 'Not Available',
                'value_2' => $props2 ? 'Available' : 'Not Available',
                'winner' => ($props1 && ! $props2) ? 'RealEstate 1' : (($props2 && ! $props1) ? 'RealEstate 2' : 'Tie'),
                'description' => 'Detailed features comparison is limited as one or both properties are missing their associated RealEstate_properties data.',
            ];
        }

        return $breakdown;
    }

    private function compareNumericField(string $title, $val1, $val2, bool $lowerIsBetter, ?callable $descriptionLogic = null): array
    {
        $winner = 'Tie';
        if ($val1 === $val2) {
            $winner = 'Tie';
        } elseif ($lowerIsBetter) {
            $winner = ($val1 < $val2) ? 'RealEstate 1' : 'RealEstate 2';
        } else {
            $winner = ($val1 > $val2) ? 'RealEstate 1' : 'RealEstate 2';
        }

        $defaultDescription = '';
        if ($val1 === $val2) {
            $defaultDescription = "Both have the same {$title} ({$val1}).";
        } elseif ($lowerIsBetter) {
            $defaultDescription = ($winner === 'RealEstate 1' ? 'RealEstate 1 has lower ' : 'RealEstate 2 has lower ').$title." ({$val1} vs {$val2}).";
        } else {
            $defaultDescription = ($winner === 'RealEstate 1' ? 'RealEstate 1 has higher ' : 'RealEstate 2 has higher ').$title." ({$val1} vs {$val2}).";
        }

        return [
            'title' => $title,
            'value_1' => $val1,
            'value_2' => $val2,
            'winner' => $winner,
            'description' => $descriptionLogic ? $descriptionLogic($val1, $val2) : $defaultDescription,
        ];
    }

    private function compareBooleanField(string $title, bool $val1, bool $val2, string $trueDesc, string $falseDesc): array
    {
        $winner = 'Tie';
        if ($val1 && ! $val2) {
            $winner = 'RealEstate 1';
        } elseif ($val2 && ! $val1) {
            $winner = 'RealEstate 2';
        }

        $description = '';
        if ($val1 && $val2) {
            $description = 'Both '.strtolower($trueDesc);
        } elseif ($val1) {
            $description = 'Only RealEstate 1 '.strtolower($trueDesc);
        } elseif ($val2) {
            $description = 'Only RealEstate 2 '.strtolower($trueDesc);
        } else {
            $description = 'Neither '.strtolower($falseDesc);
        }

        return [
            'title' => $title,
            'value_1' => $val1 ? 'Yes' : 'No',
            'value_2' => $val2 ? 'Yes' : 'No',
            'winner' => $winner,
            'description' => $description,
            'user_desired_value' => 'N/A (No specific user preference for this field)',
        ];
    }

    private function compareBooleanFieldWithPreference(string $title, bool $val1, bool $val2, string $trueDesc, string $falseDesc, ?bool $userPreference = null): array
    {
        $winner = 'Tie';
        $description = '';

        if ($userPreference !== null) {
            $val1MatchesPref = ($val1 === $userPreference); // True if property 1 has (or doesn't have) what user wants
            $val2MatchesPref = ($val2 === $userPreference); // True if property 2 has (or doesn't have) what user wants

            if ($val1MatchesPref && ! $val2MatchesPref) {
                $winner = 'RealEstate 1';
                $description = 'RealEstate 1 '.($userPreference ? 'has ' : "doesn't have ").strtolower($trueDesc).' as preferred, while RealEstate 2 does not.';
            } elseif (! $val1MatchesPref && $val2MatchesPref) {
                $winner = 'RealEstate 2';
                $description = 'RealEstate 2 '.($userPreference ? 'has ' : "doesn't have ").strtolower($trueDesc).' as preferred, while RealEstate 1 does not.';
            } elseif ($val1MatchesPref && $val2MatchesPref) {
                $winner = 'Tie';
                $description = 'Both RealEstate 1 and RealEstate 2 '.($userPreference ? 'match your preference for ' : 'match your preference against ').strtolower($trueDesc).'.';
            } else {
                if ($val1 && ! $val2) {
                    $winner = 'RealEstate 1';
                    $description = 'Neither perfectly matches your preference. RealEstate 1 '.strtolower($trueDesc).', RealEstate 2 does not.';
                } elseif (! $val1 && $val2) {
                    $winner = 'RealEstate 2';
                    $description = 'Neither perfectly matches your preference. RealEstate 2 '.strtolower($trueDesc).', RealEstate 1 does not.';
                } else {
                    $winner = 'Tie';
                    $description = 'Neither perfectly matches your preference. Both '.($val1 ? 'have '.strtolower($trueDesc) : "don't have ".strtolower($trueDesc)).'.';
                }
            }
        } else {
            return $this->compareBooleanField($title, $val1, $val2, $trueDesc, $falseDesc);
        }

        return [
            'title' => $title,
            'value_1' => $val1 ? 'Yes' : 'No',
            'value_2' => $val2 ? 'Yes' : 'No',
            'winner' => $winner,
            'description' => $description,
            'user_preference' => $userPreference !== null ? ($userPreference ? 'Desired' : 'Not Desired') : 'N/A',
        ];
    }

    private function compareEnumField(string $title, ?string $enumVal1, ?string $enumVal2, array $displayMap, array $priorityMap): array
    {
        $displayVal1 = $displayMap[$enumVal1] ?? 'N/A';
        $displayVal2 = $displayMap[$enumVal2] ?? 'N/A';

        $p1 = $priorityMap[strtolower($enumVal1 ?? '')] ?? 0;
        $p2 = $priorityMap[strtolower($enumVal2 ?? '')] ?? 0;

        $winner = 'Tie';
        if ($p1 > $p2) {
            $winner = 'RealEstate 1';
        } elseif ($p2 > $p1) {
            $winner = 'RealEstate 2';
        }

        $description = "RealEstate 1: {$displayVal1}, RealEstate 2: {$displayVal2}.";
        if ($winner !== 'Tie') {
            $description .= ' '.$winner." has a better {$title}.";
        } else {
            $description .= " Both have similar {$title}.";
        }

        return [
            'title' => $title,
            'value_1' => $displayVal1,
            'value_2' => $displayVal2,
            'winner' => $winner,
            'description' => $description,
            'user_desired_value' => 'N/A (No specific user preference for this field)',
        ];
    }

    private function compareEnumFieldWithPreference(
        string $title,
        ?string $enumVal1,
        ?string $enumVal2,
        array $displayMap,
        array $priorityMap,
        ?string $userDesiredValue = null
    ): array {
        $displayVal1 = $displayMap[$enumVal1] ?? 'N/A';
        $displayVal2 = $displayMap[$enumVal2] ?? 'N/A';

        $winner = 'Tie';
        $description = '';

        if ($userDesiredValue !== null) {
            $normalizedUserDesired = strtolower($userDesiredValue);

            $normalizedEnumVal1 = strtolower($enumVal1 ?? '');
            $normalizedEnumVal2 = strtolower($enumVal2 ?? '');

            $val1MatchesPref = ($normalizedEnumVal1 === $normalizedUserDesired);
            $val2MatchesPref = ($normalizedEnumVal2 === $normalizedUserDesired);

            if ($val1MatchesPref && ! $val2MatchesPref) {
                $winner = 'RealEstate 1';
                $description = "RealEstate 1 matches your desired '{$displayMap[$userDesiredValue]}' {$title}, while RealEstate 2 does not.";
            } elseif (! $val1MatchesPref && $val2MatchesPref) {
                $winner = 'RealEstate 2';
                $description = "RealEstate 2 matches your desired '{$displayMap[$userDesiredValue]}' {$title}, while RealEstate 1 does not.";
            } elseif ($val1MatchesPref && $val2MatchesPref) {
                $winner = 'Tie';
                $description = "Both RealEstate 1 and 2 match your desired '{$displayMap[$userDesiredValue]}' {$title}.";
            } else {
                $p1 = $priorityMap[$normalizedEnumVal1] ?? 0;
                $p2 = $priorityMap[$normalizedEnumVal2] ?? 0;
                $winner = ($p1 > $p2) ? 'RealEstate 1' : (($p2 > $p1) ? 'RealEstate 2' : 'Tie');
                $description = "Neither matches your desired '{$displayMap[$userDesiredValue]}' {$title}. ".($winner === 'Tie' ? "Both have similar default {$title}." : $winner." has a slightly better default {$title}.");
            }
        } else {
            return $this->compareEnumField($title, $enumVal1, $enumVal2, $displayMap, $priorityMap);
        }

        return [
            'title' => $title,
            'value_1' => $displayVal1,
            'value_2' => $displayVal2,
            'winner' => $winner,
            'description' => $description,
            'user_desired_value' => $userDesiredValue !== null ? ($displayMap[$userDesiredValue] ?? $userDesiredValue) : 'N/A',
        ];
    }
}
