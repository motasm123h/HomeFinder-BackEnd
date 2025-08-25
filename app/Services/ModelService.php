<?php

namespace App\services;

use App\DTOs\RealEstate\CreateRealEstateDto;
use App\DTOs\RealEstate\UpdateRealEstateDto;
use App\Repository\Models\RealEstateRepository;
use Illuminate\Support\Facades\DB;

class ModelService
{
    public function __construct(
        private RealEstateRepository $repository,
        private MediaService $mediaService
    ) {}

    public function createRealEstate(CreateRealEstateDto $dto)
    {
        return DB::transaction(function () use ($dto) {
            $data = array_merge($dto->mainData, ['user_id' => $dto->userId]);
            $realEstate = $this->repository->create($data);

            $this->repository->createProperties($realEstate, $dto->properties);

            if ($dto->images) {
                $this->mediaService->handleUploads(
                    $realEstate,
                    'images',
                    $dto->images,
                    'real_estate',
                    config('model_paths.real_estate.image_path')
                );
            }

            return $realEstate->load('properties', 'images', 'user');
        });
    }

    public function updateRealEstate(UpdateRealEstateDto $dto)
    {
        return DB::transaction(function () use ($dto) {
            $realEstate = $this->repository->update(
                $dto->mainData,
                $dto->realEstateId,
            );

            $this->repository->updateProperties($realEstate, $dto->properties);

            if ($dto->images) {
                $this->mediaService->handleUploads(
                    $realEstate,
                    'images',
                    $dto->images,
                    'real_estate',
                    config('model_paths.real_estate.image_path')
                );
            }

            return $realEstate->load('properties', 'images', 'location');
        });
    }

    public function deleteRealEstate(int $id)
    {
        return $this->repository->delete($id);
    }

    public function getDetails(int $id)
    {
        return $this->repository->getDetails($id);
    }
}
