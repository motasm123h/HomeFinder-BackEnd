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

    public function run(): void
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => '123456',
            'role' => '1',
        ]);

        $token = $user->createToken('secret')->plainTextToken;

        echo "API Token for test user: " . $token . PHP_EOL;

        $this->call([
            DamascusLocationsSeeder::class,
            ServicesTypeSeeder::class,
            // RealEstateWithPropertiesSeeder::class,
        ]);
    }
}
