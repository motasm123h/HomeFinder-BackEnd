<?php

namespace App\Helper;

use App\Models\RealEstate;

class RealEstateHelper
{
    public static function formatRealEstate(RealEstate $item): array
    {
        return [
            'id' => $item->id,
            'price' => $item->price,
            'images' => $item->images->map(function ($image) {
                return ['name' => $image->name, 'type' => $image->type];
            }),
            'location' => $item->location ? [
                'city' => $item->location->city,
                'district' => $item->location->district,
            ] : null,
            'properties' => $item->properties ? [
                'room_no' => $item->properties->room_no,
                'space_status' => $item->properties->space_status,
                'kind' => $item->kind,
                'floor' => $item->properties->floor,
                'description' => $item->description,
            ] : null,
        ];
    }
}
