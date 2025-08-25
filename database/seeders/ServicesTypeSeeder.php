<?php

namespace Database\Seeders;

use App\Models\Services;
use App\Models\Services_Type;
use App\Models\User;
use Illuminate\Database\Seeder;

class ServicesTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $serviceTypes = [
            ['type' => 'أعمال الإكساء والتشطيب'],
            ['type' => 'صيانة عامة'],
            ['type' => 'خدمات التنظيف'],
            ['type' => 'نقل وتخزين الأثاث'],
            ['type' => 'تنسيق الحدائق'],
            ['type' => 'صيانة الأنظمة الكهربائية'],
            ['type' => 'خدمات السباكة'],
            ['type' => 'تركيب وتصليح التكييف'],
            ['type' => 'مكافحة الحشرات'],
            ['type' => 'استشارات هندسية'],
            ['type' => 'تقييم عقاري'],
        ];

        foreach ($serviceTypes as $type) {
            Services_Type::create($type);
        }

        // $this->createSampleServices();
    }

    protected function createSampleServices()
    {
        $user = User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => bcrypt('password'),
            ]
        );

        $serviceTypes = Services_Type::all();

        $services = [
            [
                'title' => 'House Cleaning',
                'description' => 'Professional deep cleaning for your entire home',
                'services_type_id' => $serviceTypes->where('type', 'Cleaning')->first()->id,
                'user_id' => $user->id,
            ],
            [
                'title' => 'Plumbing Repair',
                'description' => 'Fix leaks and plumbing issues in your home',
                'services_type_id' => $serviceTypes->where('type', 'Maintenance')->first()->id,
                'user_id' => $user->id,
            ],
            [
                'title' => 'Local Moving Help',
                'description' => 'Assistance with packing and moving within the city',
                'services_type_id' => $serviceTypes->where('type', 'Moving')->first()->id,
                'user_id' => $user->id,
            ],
            [
                'title' => 'Lawn Mowing',
                'description' => 'Weekly lawn maintenance and grass cutting',
                'services_type_id' => $serviceTypes->where('type', 'Landscaping')->first()->id,
                'user_id' => $user->id,
            ],
            [
                'title' => 'Math Tutoring',
                'description' => 'Private math lessons for high school students',
                'services_type_id' => $serviceTypes->where('type', 'Tutoring')->first()->id,
                'user_id' => $user->id,
            ],
        ];

        foreach ($services as $service) {
            Services::create($service);
        }
    }
}
