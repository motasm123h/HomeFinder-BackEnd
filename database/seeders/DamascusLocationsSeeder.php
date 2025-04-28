<?php

namespace Database\Seeders;

use App\Models\RealEstate_Location;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DamascusLocationsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $damascusDistricts = [
            // دمشق القديمة (داخل السور)
            ['city' => 'دمشق', 'district' => 'العباسيين'],
            ['city' => 'دمشق', 'district' => 'باب شرقي'],
            ['city' => 'دمشق', 'district' => 'باب توما'],
            ['city' => 'دمشق', 'district' => 'باب السلام'],
            ['city' => 'دمشق', 'district' => 'الحريقة'],
            
            // غرب دمشق (المناطق الراقية)
            ['city' => 'دمشق', 'district' => 'المالكي'],
            ['city' => 'دمشق', 'district' => 'أبو رمانة'],
            ['city' => 'دمشق', 'district' => 'الزهراء'],
            ['city' => 'دمشق', 'district' => 'الكواكب'],
            ['city' => 'دمشق', 'district' => 'الروضة'],
            
            // جنوب دمشق
            ['city' => 'دمشق', 'district' => 'مشروع دمر'],
            ['city' => 'دمشق', 'district' => 'القدم'],
            ['city' => 'دمشق', 'district' => 'المزة غرب'],
            ['city' => 'دمشق', 'district' => 'المزة شرق'],
            ['city' => 'دمشق', 'district' => 'كفر سوسة'],
            ['city' => 'دمشق', 'district' => 'داريا'],
            
            // شرق دمشق
            ['city' => 'دمشق', 'district' => 'الزاهرة'],
            ['city' => 'دمشق', 'district' => 'باب مصلى'],
            ['city' => 'دمشق', 'district' => 'ركن الدين'],
            ['city' => 'دمشق', 'district' => 'القابون'],
            
            // شمال دمشق
            ['city' => 'دمشق', 'district' => 'برزة'],
            ['city' => 'دمشق', 'district' => 'تضامن (العدوي)'],
            ['city' => 'دمشق', 'district' => 'الميدان'],
            ['city' => 'دمشق', 'district' => 'الحجر الأسود'],
            
            // ضواحي دمشق
            ['city' => 'دمشق', 'district' => 'دوما'],
            ['city' => 'دمشق', 'district' => 'حرستا'],
            ['city' => 'دمشق', 'district' => 'معضمية الشام'],
            ['city' => 'دمشق', 'district' => 'سقبا'],
            
            // مناطق تنظيمية حديثة
            ['city' => 'دمشق', 'district' => 'مدينة دمشق الجديدة'],
            ['city' => 'دمشق', 'district' => 'معامل حلب'],
            ['city' => 'دمشق', 'district' => 'الهامة'],
            ['city' => 'دمشق', 'district' => 'العبادة'],
        ];

        // إدخال البيانات بطريقة فعالة
        foreach (array_chunk($damascusDistricts, 10) as $chunk) {
            RealEstate_Location::insert($chunk);
        }
    }
}
