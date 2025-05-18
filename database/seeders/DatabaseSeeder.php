<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Database\Seeders\DamascusLocationsSeeder;
use Database\Seeders\ServicesTypeSeeder;
use Database\Seeders\RealEstateWithPropertiesSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $user = User::factory()->create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        ]);

        // Create token for the user (no need for auth()->user())
        $token = $user->createToken('secret')->plainTextToken;
        
        // Output the token (for testing purposes)
        echo "API Token for test user: " . $token . PHP_EOL;
        $this->call([
            DamascusLocationsSeeder::class,
            ServicesTypeSeeder::class,
            RealEstateWithPropertiesSeeder::class,
        ]);

     
    }
}
