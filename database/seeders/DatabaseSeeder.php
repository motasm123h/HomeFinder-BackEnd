<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::factory()->create([
            'name' => 'admin',
            'email' => 'admin@gmail.com',
            'password' => '123456789',
            'role' => '1',
        ]);

        $token = $user->createToken('secret')->plainTextToken;

        echo 'API Token for test user: '.$token.PHP_EOL;

        $this->call([
            DamascusLocationsSeeder::class,
            ServicesTypeSeeder::class,
            RealEstateWithPropertiesSeeder::class,
        ]);
    }
}
