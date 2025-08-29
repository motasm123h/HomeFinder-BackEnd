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
use Illuminate\Support\Arr;


class RealEstateController extends Controller
{
    use ResponseTrait;

    // // Constant maps remain the same
    // private const STATUS_MAP = [
    //     'open' => 'Open',
    //     'closed' => 'Closed',
    // ];

    // private const TYPE_MAP = [
    //     'rental' => 'For Rent',
    //     'sale' => 'For Sale',
    //     'للبيع' => 'For Sale',
    //     'للأيجار' => 'For Rent',
    // ];

    // private const KIND_MAP = [
    //     'apartment' => 'Apartment',
    //     'villa' => 'Villa',
    //     'chalet' => 'Chalet',
    //     'شقة' => 'Apartment',
    //     'فيلا' => 'Villa',
    //     'شاليه' => 'Chalet',
    // ];

    // private const QUALITY_STATUS_MAP = [
    //     '1' => 'Good',
    //     '2' => 'Average',
    //     '3' => 'Bad',
    // ];

    // private const YES_NO_MAP = [
    //     '1' => 'Yes',
    //     '2' => 'No',
    // ];


    // private const DIRECTION_MAP = [
    //     '1' => 'One Direction',
    //     '2' => 'Two Direction',
    //     '3' => 'Three Direction',
    //     '4' => 'Four Direction',
    // ];

    // private const ATTIRED_MAP = [
    //     '1' => 'Fully Furnished/Well-Maintained',
    //     '2' => 'Partially Furnished/Average-Maintained',
    //     '3' => 'Not Furnished/Poorly-Maintained',
    // ];


    // private const OWNERSHIP_TYPE_MAP = [
    //     'green' => 'Green Ownership',
    //     'court' => 'Court Ownership',
    // ];

    // --- MAPS for display and normalization ---
    private const TYPE_MAP = ['rental' => 'For Rent', 'sale' => 'For Sale', 'للبيع' => 'For Sale', 'للايجار' => 'For Rent'];
    private const KIND_MAP = ['apartment' => 'Apartment', 'villa' => 'Villa', 'chalet' => 'Chalet', 'شقة' => 'Apartment', 'فيلا' => 'Villa', 'شاليه' => 'Chalet'];
    private const QUALITY_STATUS_MAP = ['1' => 'Good', '2' => 'Average', '3' => 'Bad'];
    private const ATTIRED_MAP = ['1' => 'Fully Furnished/Well-Maintained', '2' => 'Partially Furnished/Average-Maintained', '3' => 'Not Furnished/Poorly-Maintained'];
    private const OWNERSHIP_TYPE_MAP = ['green' => 'Green Ownership', 'court' => 'Court Ownership'];
    private const DIRECTION_MAP = ['1' => 'One Direction', '2' => 'Two Direction', '3' => 'Three Direction', '4' => 'Four Direction'];

    private const WEIGHTS = [
        'price' => 70,
        'type' => 25,
        'kind' => 30,
        'rooms' => 35,
        'space' => 35,
        'quality' => 15, // Generic weight for electricity, water, transportation
        'boolean' => 20, // Generic weight for garage, garden, solar, etc.
        'attired' => 15,
        'ownership' => 15,
        'direction' => 10,
        'floor' => 8,
    ];

    private const DEFAULT_SCORING_FACTORS = [
        'price_good' => 0.6,      // 60% of weight for a very good price
        'price_average' => 0.4,   // 40% for a good price
        'price_fair' => 0.2,      // 20% for an average price
        'type_sale' => 0.2,       // 20% bonus for being 'for sale'
        'kind_villa' => 0.5,      // 50% bonus for being a villa
        'kind_apartment' => 0.3,  // 30% bonus for being an apartment
        'rooms_divisor' => 5,     // Divisor for room score calculation
        'space_divisor' => 100,   // Divisor for space score calculation
        'boolean_bonus' => 0.5,   // 50% bonus for having a desirable boolean feature
        'attired_full' => 1,      // 100% bonus for being fully furnished
        'attired_partial' => 0.5, // 50% bonus for being partially furnished
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
            'preferences.desired_type' => 'nullable|in:rental,sale,للبيع,للايجار',
            'preferences.desired_kind' => 'nullable|in:apartment,villa,chalet,شقة,فيلا,شاليه',
            'preferences.min_price' => 'nullable|integer|min:0',
            'preferences.max_price' => 'nullable|integer|min:0',
            'preferences.desired_rooms' => 'nullable|integer|min:0',
            'preferences.desired_space' => 'nullable|integer|min:0',
            'preferences.desired_electricity_status' => 'nullable|in:1,2,3',
            'preferences.desired_water_status' => 'nullable|in:1,2,3',
            'preferences.desired_transportation_status' => 'nullable|in:1,2,3',
            'preferences.want_water_well' => 'nullable|boolean',
            'preferences.want_solar_energy' => 'nullable|boolean',
            'preferences.want_garage' => 'nullable|boolean',
            'preferences.want_elevator' => 'nullable|boolean',
            'preferences.want_garden' => 'nullable|boolean',
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

        if (!$realEstate1 || !$realEstate2) {
            return response()->json(['message' => 'One or both RealEstate IDs not found.'], 404);
        }

        $score1 = $this->calculateRealEstateScore($realEstate1, $preferences);
        $score2 = $this->calculateRealEstateScore($realEstate2, $preferences);

        $comparisonResult = $this->getComparisonBreakdown($realEstate1, $realEstate2, $preferences);

        return response()->json([
            'real_estate_1' => ['id' => $realEstate1->id, 'total_score' => $score1],
            'real_estate_2' => ['id' => $realEstate2->id, 'total_score' => $score2],
            'comparison_breakdown' => $comparisonResult,
            'overall_winner' => $score1 > $score2 ? 'RealEstate ' . $id1 : ($score2 > $score1 ? 'RealEstate ' . $id2 : 'Tie'),
            'message' => $score1 > $score2 ? "RealEstate {$id1} scores higher." : ($score2 > $score1 ? "RealEstate {$id2} scores higher." : "RealEstate {$id1} and {$id2} have the same total score."),
        ]);
    }

    private function calculateRealEstateScore(RealEstate $realEstate, array $preferences = []): int
    {
        $score = 0;
        $props = $realEstate->properties;

        $score += $realEstate->total_weight ?? 0;
        if ($props) {
            $score += $props->total_weight ?? 0;
        }

        $minPrice = $preferences['min_price'] ?? null;
        $maxPrice = $preferences['max_price'] ?? null;
        if ($minPrice !== null || $maxPrice !== null) {
            $isInRange = ($minPrice === null || $realEstate->price >= $minPrice) && ($maxPrice === null || $realEstate->price <= $maxPrice);
            $score += $isInRange ? self::WEIGHTS['price'] : -self::WEIGHTS['price'];
        }

        $this->scoreExactMatch($score, self::WEIGHTS['type'], $realEstate->type, $preferences['desired_type'] ?? null);
        $this->scoreExactMatch($score, self::WEIGHTS['kind'], $realEstate->kind, $preferences['desired_kind'] ?? null);

        if ($props) {
            $this->scoreClosestMatch($score, self::WEIGHTS['rooms'], $props->room_no ?? 0, $preferences['desired_rooms'] ?? null, 5);
            $this->scoreClosestMatch($score, self::WEIGHTS['space'], $props->space_status ?? 0, $preferences['desired_space'] ?? null, 100);
            $this->scoreExactMatch($score, self::WEIGHTS['quality'], $props->electricity_status ?? null, $preferences['desired_electricity_status'] ?? null);
            $this->scoreExactMatch($score, self::WEIGHTS['quality'], $props->water_status ?? null, $preferences['desired_water_status'] ?? null);
            $this->scoreExactMatch($score, self::WEIGHTS['quality'], $props->transportation_status ?? null, $preferences['desired_transportation_status'] ?? null);
            $this->scoreBooleanMatch($score, self::WEIGHTS['boolean'], ($props->water_well ?? null) == '1', $preferences['want_water_well'] ?? null);
            $this->scoreBooleanMatch($score, self::WEIGHTS['boolean'], ($props->solar_energy ?? null) == '1', $preferences['want_solar_energy'] ?? null);
            $this->scoreBooleanMatch($score, self::WEIGHTS['boolean'], ($props->garage ?? null) == '1', $preferences['want_garage'] ?? null);
            $this->scoreBooleanMatch($score, self::WEIGHTS['boolean'], ($props->elevator ?? null) == '1', $preferences['want_elevator'] ?? null);
            $this->scoreBooleanMatch($score, self::WEIGHTS['boolean'], ($props->garden_status ?? null) == '1', $preferences['want_garden'] ?? null);
            $this->scoreExactMatch($score, self::WEIGHTS['attired'], $props->attired ?? null, $preferences['desired_attired_status'] ?? null);
            $this->scoreExactMatch($score, self::WEIGHTS['ownership'], $props->ownership_type ?? null, $preferences['desired_ownership_type'] ?? null);
            $this->applyDefaultScores($score, $realEstate, $preferences);
        }

        return (int) round($score);
    }

    private function scoreExactMatch(int &$score, int $weight, $propertyValue, $userPreference): void
    {
        if ($userPreference !== null) {
            $normalizedProp = self::getNormalizedValue($propertyValue, self::TYPE_MAP, self::KIND_MAP);
            $normalizedPref = self::getNormalizedValue($userPreference, self::TYPE_MAP, self::KIND_MAP);
            $score += ($normalizedProp === $normalizedPref) ? $weight : -$weight;
        }
    }

    private function scoreClosestMatch(int &$score, int $weight, $propertyValue, $userPreference, int $range): void
    {
        if ($userPreference !== null) {
            $deviation = abs($propertyValue - $userPreference);
            $score += $weight * max(0, 1 - ($deviation / $range));
        }
    }

    private function scoreBooleanMatch(int &$score, int $weight, bool $propertyValue, $userPreference): void
    {
        if ($userPreference !== null) {
            $score += ($propertyValue === $userPreference) ? $weight : -$weight;
        }
    }

    private function applyDefaultScores(int &$score, RealEstate $realEstate, array $preferences): void
    {
        $props = $realEstate->properties;

        if (!isset($preferences['min_price']) && !isset($preferences['max_price'])) {
            $price = $realEstate->price;
            if ($price < 100000000) $score += self::WEIGHTS['price'] * self::DEFAULT_SCORING_FACTORS['price_good'];
            else if ($price < 150000000) $score += self::WEIGHTS['price'] * self::DEFAULT_SCORING_FACTORS['price_average'];
            else if ($price < 3000000000) $score += self::WEIGHTS['price'] * self::DEFAULT_SCORING_FACTORS['price_fair'];
        }

        if (!isset($preferences['desired_type'])) {
            if (in_array($realEstate->type, ['sale', 'للبيع'])) {
                $score += self::WEIGHTS['type'] * self::DEFAULT_SCORING_FACTORS['type_sale'];
            }
        }

        if (!isset($preferences['desired_kind'])) {
            if (in_array($realEstate->kind, ['villa', 'فيلا'])) $score += self::WEIGHTS['kind'] * self::DEFAULT_SCORING_FACTORS['kind_villa'];
            else if (in_array($realEstate->kind, ['apartment', 'شقة'])) $score += self::WEIGHTS['kind'] * self::DEFAULT_SCORING_FACTORS['kind_apartment'];
        }

        if ($props) {
            if (!isset($preferences['desired_rooms'])) {
                $score += ($props->room_no ?? 0) * (self::WEIGHTS['rooms'] / self::DEFAULT_SCORING_FACTORS['rooms_divisor']);
            }
            if (!isset($preferences['desired_space'])) {
                $score += ($props->space_status ?? 0) * (self::WEIGHTS['space'] / self::DEFAULT_SCORING_FACTORS['space_divisor']);
            }

            if (!isset($preferences['desired_electricity_status'])) $score += $this->getQualityDefaultScore($props->electricity_status);
            if (!isset($preferences['desired_water_status'])) $score += $this->getQualityDefaultScore($props->water_status);
            if (!isset($preferences['desired_transportation_status'])) $score += $this->getQualityDefaultScore($props->transportation_status);

            if (!isset($preferences['want_solar_energy']) && ($props->solar_energy ?? '2') == '1') $score += self::WEIGHTS['boolean'] * self::DEFAULT_SCORING_FACTORS['boolean_bonus'];
            if (!isset($preferences['want_garage']) && ($props->garage ?? '2') == '1') $score += self::WEIGHTS['boolean'] * self::DEFAULT_SCORING_FACTORS['boolean_bonus'];
            if (!isset($preferences['want_garden']) && ($props->garden_status ?? '2') == '1') $score += self::WEIGHTS['boolean'] * self::DEFAULT_SCORING_FACTORS['boolean_bonus'];

            if (!isset($preferences['desired_attired_status'])) {
                if (($props->attired ?? '3') == '1') $score += self::WEIGHTS['attired'] * self::DEFAULT_SCORING_FACTORS['attired_full'];
                if (($props->attired ?? '3') == '2') $score += self::WEIGHTS['attired'] * self::DEFAULT_SCORING_FACTORS['attired_partial'];
            }
        }
    }

    private function getQualityDefaultScore(?string $status): int
    {
        switch ($status) {
            case '1':
                return self::WEIGHTS['quality'];
            case '2':
                return self::WEIGHTS['quality'] / 2;
            case '3':
                return -self::WEIGHTS['quality'];
            default:
                return 0;
        }
    }

    private function getComparisonBreakdown(RealEstate $realEstate1, RealEstate $realEstate2, array $preferences = []): array
    {
        $breakdown = [];
        $props1 = $realEstate1->properties;
        $props2 = $realEstate2->properties;

        $minPrice = $preferences['min_price'] ?? null;
        $maxPrice = $preferences['max_price'] ?? null;
        if ($minPrice !== null || $maxPrice !== null) {
            $price1 = $realEstate1->price;
            $price2 = $realEstate2->price;
            $isInRange1 = ($minPrice === null || $price1 >= $minPrice) && ($maxPrice === null || $price1 <= $maxPrice);
            $isInRange2 = ($minPrice === null || $price2 >= $minPrice) && ($maxPrice === null || $price2 <= $maxPrice);
            $winnerPrice = 'Tie';
            if ($isInRange1 && !$isInRange2) $winnerPrice = 'RealEstate 1';
            elseif (!$isInRange1 && $isInRange2) $winnerPrice = 'RealEstate 2';
            elseif ($isInRange1 && $isInRange2) $winnerPrice = ($price1 <= $price2) ? 'RealEstate 1' : 'RealEstate 2';
            else $winnerPrice = ($price1 < $price2) ? 'RealEstate 1' : 'RealEstate 2';

            $breakdown['price'] = [
                'title' => 'Price',
                'value_1' => $price1,
                'value_2' => $price2,
                'winner' => $winnerPrice,
                'description' => "Preference: " . ($minPrice ?? 'any') . " - " . ($maxPrice ?? 'any')
            ];
        } else {
            $breakdown['price'] = $this->compareNumericField('Price', $realEstate1->price, $realEstate2->price, true);
        }

        $breakdown['type'] = $this->compareEnumFieldWithPreference('Type', $realEstate1->type, $realEstate2->type, self::TYPE_MAP, ['sale' => 2, 'للبيع' => 2], $preferences['desired_type'] ?? null);
        $breakdown['kind'] = $this->compareEnumFieldWithPreference('Kind', $realEstate1->kind, $realEstate2->kind, self::KIND_MAP, ['villa' => 3, 'فيلا' => 3], $preferences['desired_kind'] ?? null);

        if ($props1 && $props2) {
            $this->addNumericPreferenceBreakdown($breakdown, 'room_no', 'Number of Rooms', $props1->room_no, $props2->room_no, $preferences['desired_rooms'] ?? null);
            $this->addNumericPreferenceBreakdown($breakdown, 'space_status', 'Space (m²)', $props1->space_status, $props2->space_status, $preferences['desired_space'] ?? null);
            $breakdown['electricity_status'] = $this->compareEnumFieldWithPreference('Electricity', $props1->electricity_status, $props2->electricity_status, self::QUALITY_STATUS_MAP, ['1' => 3, '2' => 2], $preferences['desired_electricity_status'] ?? null);
            $breakdown['water_status'] = $this->compareEnumFieldWithPreference('Water', $props1->water_status, $props2->water_status, self::QUALITY_STATUS_MAP, ['1' => 3, '2' => 2], $preferences['desired_water_status'] ?? null);
            $breakdown['transportation_status'] = $this->compareEnumFieldWithPreference('Transportation', $props1->transportation_status, $props2->transportation_status, self::QUALITY_STATUS_MAP, ['1' => 3, '2' => 2], $preferences['desired_transportation_status'] ?? null);
            $breakdown['water_well'] = $this->compareBooleanFieldWithPreference('Water Well', ($props1->water_well ?? '2') == '1', ($props2->water_well ?? '2') == '1', $preferences['want_water_well'] ?? null);
            $breakdown['solar_energy'] = $this->compareBooleanFieldWithPreference('Solar Energy', ($props1->solar_energy ?? '2') == '1', ($props2->solar_energy ?? '2') == '1', $preferences['want_solar_energy'] ?? null);
            $breakdown['garage'] = $this->compareBooleanFieldWithPreference('Garage', ($props1->garage ?? '2') == '1', ($props2->garage ?? '2') == '1', $preferences['want_garage'] ?? null);
            $breakdown['elevator'] = $this->compareBooleanFieldWithPreference('Elevator', ($props1->elevator ?? '2') == '1', ($props2->elevator ?? '2') == '1', $preferences['want_elevator'] ?? null);
            $breakdown['garden_status'] = $this->compareBooleanFieldWithPreference('Garden', ($props1->garden_status ?? '2') == '1', ($props2->garden_status ?? '2') == '1', $preferences['want_garden'] ?? null);
            $breakdown['attired'] = $this->compareEnumFieldWithPreference('Attired Status', $props1->attired, $props2->attired, self::ATTIRED_MAP, ['1' => 3, '2' => 2], $preferences['desired_attired_status'] ?? null);
            $breakdown['ownership_type'] = $this->compareEnumFieldWithPreference('Ownership', $props1->ownership_type, $props2->ownership_type, self::OWNERSHIP_TYPE_MAP, ['green' => 2], $preferences['desired_ownership_type'] ?? null);
        }

        return $breakdown;
    }

    private function addNumericPreferenceBreakdown(&$breakdown, $key, $title, $val1, $val2, $preference)
    {
        if ($preference !== null) {
            $dev1 = abs($val1 - $preference);
            $dev2 = abs($val2 - $preference);
            $winner = $dev1 < $dev2 ? 'RealEstate 1' : ($dev2 < $dev1 ? 'RealEstate 2' : 'Tie');
            $breakdown[$key] = [
                'title' => $title,
                'value_1' => $val1,
                'value_2' => $val2,
                'winner' => $winner,
                'description' => "Preference: {$preference}. " . ($winner !== 'Tie' ? "{$winner} is closer." : "Both are equally close.")
            ];
        } else {
            $breakdown[$key] = $this->compareNumericField($title, $val1, $val2, false);
        }
    }

    private function compareNumericField(string $title, $val1, $val2, bool $lowerIsBetter): array
    {
        $winner = 'Tie';
        if ($val1 != $val2) {
            $winner = ($lowerIsBetter ? $val1 < $val2 : $val1 > $val2) ? 'RealEstate 1' : 'RealEstate 2';
        }
        return ['title' => $title, 'value_1' => $val1, 'value_2' => $val2, 'winner' => $winner, 'description' => $winner !== 'Tie' ? "{$winner} is better." : "Both are the same."];
    }

    private function compareBooleanFieldWithPreference(string $title, bool $val1, bool $val2, ?bool $preference): array
    {
        $winner = 'Tie';
        if ($preference !== null) {
            $match1 = $val1 === $preference;
            $match2 = $val2 === $preference;
            if ($match1 !== $match2) $winner = $match1 ? 'RealEstate 1' : 'RealEstate 2';
        } else {
            if ($val1 !== $val2) $winner = $val1 ? 'RealEstate 1' : 'RealEstate 2';
        }
        return ['title' => $title, 'value_1' => $val1 ? 'Yes' : 'No', 'value_2' => $val2 ? 'Yes' : 'No', 'winner' => $winner, 'description' => 'Preference: ' . ($preference === null ? 'Any' : ($preference ? 'Yes' : 'No'))];
    }

    private function compareEnumFieldWithPreference(string $title, ?string $val1, ?string $val2, array $map, array $priorityMap, ?string $preference): array
    {
        $winner = 'Tie';
        if ($preference !== null) {
            $normPref = self::getNormalizedValue($preference, self::TYPE_MAP, self::KIND_MAP);
            $normVal1 = self::getNormalizedValue($val1, self::TYPE_MAP, self::KIND_MAP);
            $normVal2 = self::getNormalizedValue($val2, self::TYPE_MAP, self::KIND_MAP);
            $match1 = $normVal1 === $normPref;
            $match2 = $normVal2 === $normPref;
            if ($match1 !== $match2) $winner = $match1 ? 'RealEstate 1' : 'RealEstate 2';
        } else {
            $p1 = $priorityMap[strtolower($val1 ?? '')] ?? 0;
            $p2 = $priorityMap[strtolower($val2 ?? '')] ?? 0;
            if ($p1 !== $p2) $winner = $p1 > $p2 ? 'RealEstate 1' : 'RealEstate 2';
        }
        return ['title' => $title, 'value_1' => $map[$val1] ?? 'N/A', 'value_2' => $map[$val2] ?? 'N/A', 'winner' => $winner, 'description' => 'Preference: ' . ($map[$preference] ?? 'Any')];
    }

    private static function getNormalizedValue(?string $key, array $typeMap, array $kindMap): ?string
    {
        if ($key === null) return null;
        $keyLower = strtolower($key);
        return $typeMap[$keyLower] ?? $kindMap[$keyLower] ?? $key;
    }
}
