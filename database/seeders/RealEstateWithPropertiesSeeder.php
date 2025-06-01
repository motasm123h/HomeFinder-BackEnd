<?php

namespace Database\Seeders;

use App\Models\RealEstate;
use App\Models\RealEstate_Location;
use App\Models\RealEstate_properties;
use App\Models\RealEstate_images;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class RealEstateWithPropertiesSeeder extends Seeder
{
    public function run()
    {
        $user = User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => bcrypt('password')
            ]
        );

        $location = RealEstate_Location::firstOrCreate(
            [
                'city' => 'Metropolis',
                'district' => 'United States',
            ]
        );

        // Create storage directory if it doesn't exist
        if (!Storage::exists('public/real-estate-images')) {
            Storage::makeDirectory('public/real-estate-images');
        }

        $electricityStatuses = ['1', '2', '3'];
        $waterStatuses = ['1', '2', '3'];
        $transportationStatuses = ['1', '2', '3'];
        $yesNoOptions = ['1', '2'];
        $directions = ['1', '2', '3'];
        $ownershipTypes = ['green', 'court'];
        $attiredOptions = ['1', '2', '3'];
        $gardenStatuses = ['1', '2'];
        $realEstateTypes = ['rental', 'sale'];
        $kinds = ['apartment', 'villa', 'chalet'];

        // Sample image URLs to use (or you can use local files)
        $sampleImages = [
            'https://images.unsplash.com/photo-1564013799919-ab600027ffc6?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80',
            'https://images.unsplash.com/photo-1580587771525-78b9dba3b914?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80',
        ];

        for ($i = 1; $i <= 100; $i++) {
            $realEstate = RealEstate::create([
                'latitude' => 40.7128 + ($i * 0.01),
                'longitude' => -74.0060 + ($i * 0.01),
                'type' => $realEstateTypes[array_rand($realEstateTypes)],
                'price' => rand(100000, 5000000),
                'description' => "Beautiful property in excellent location $i",
                'kind' => $kinds[array_rand($kinds)],
                'user_id' => $user->id,
                'real_estate_location_id' => $location->id,
                'total_weight' => rand(1, 100),
            ]);

            RealEstate_properties::create([
                'electricity_status' => $electricityStatuses[array_rand($electricityStatuses)],
                'water_status' => $waterStatuses[array_rand($waterStatuses)],
                'transportation_status' => $transportationStatuses[array_rand($transportationStatuses)],
                'water_well' => $yesNoOptions[array_rand($yesNoOptions)],
                'solar_energy' => $yesNoOptions[array_rand($yesNoOptions)],
                'garage' => $yesNoOptions[array_rand($yesNoOptions)],
                'room_no' => rand(1, 10),
                'direction' => $directions[array_rand($directions)],
                'space_status' => rand(1, 100),
                'elevator' => $yesNoOptions[array_rand($yesNoOptions)],
                'floor' => rand(0, 30),
                'garden_status' => $gardenStatuses[array_rand($gardenStatuses)],
                'attired' => $attiredOptions[array_rand($attiredOptions)],
                'ownership_type' => $ownershipTypes[array_rand($ownershipTypes)],
                'total_weight' => rand(1, 100),
                'real_estate_id' => $realEstate->id
            ]);

            // $imageCount = rand(1, 2);
            // for ($j = 0; $j < $imageCount; $j++) {
            //     $imageUrl = $sampleImages[array_rand($sampleImages)];
            //     $imageName = 'property_' . $realEstate->id . '_' . time() . '_' . $j . '.jpg';

            //     $contents = file_get_contents($imageUrl);
            //     Storage::put('public/real-estate-images/' . $imageName, $contents);

            //     RealEstate_images::create([
            //         'name' => $imageName,
            //         'real_estate_id' => $realEstate->id
            //     ]);
            // }
        }

        $this->command->info('Successfully created 100 real estate records with properties and images!');
    }
}
