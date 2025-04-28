<?php
namespace App\DTOs\RealEstate;


class UpdateRealEstateDto  {
    public function __construct(
        public readonly array $mainData,
        public readonly array $properties,
        public readonly ?array $images,
        public readonly int $realEstateId
    ){}
}