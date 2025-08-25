<?php

return [
    'weights' => [
        'room_no' => 10,
        'price' => 20, // Keep a weight for price
        'space_status' => 15,
        'electricity_status' => 5,
        'water_status' => 5,
        'transportation_status' => 5,
        'water_well' => 3,
        'solar_energy' => 3,
        'garage' => 3,
        'direction' => 2,
        'elevator' => 2,
        'floor' => 8,
        'garden_status' => 3,
        'attired' => 4,
        'ownership_type' => 7,
    ],

    'flexible_fields' => [
        'room_no' => [
            'range' => 1,
            'weight_reduction_factor' => 0.5,
        ],
        'space_status' => [
            'range' => 50,
            'weight_reduction_factor' => 0.3,
        ],
        'floor' => [
            'range' => 1,
            'weight_reduction_factor' => 0.5,
        ],
        'price' => [
            'range' => 20000,
            'weight_reduction_factor' => 0.4,
        ],
    ],
];
