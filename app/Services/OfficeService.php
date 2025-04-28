<?php

namespace App\Services;

use App\Repository\Models\OfficeRepository;

class OfficeService
{
    public function __construct(
        private OfficeRepository $repository
    ) {}

    public function getPaginatedRequests(int $perPage = 15)
    {
        return $this->repository->Arrivepaginate($perPage);
    }
    public function getPaginatedSent(int $perPage = 15)
    {
        return $this->repository->Sentpaginate($perPage);
    }

    public function createRequest(array $data)
    {
        return $this->repository->create($data);
    }

    public function deleteRequest(string $pro="sender_id",int $id): bool
    {
        return $this->repository->delete($pro,$id);
    }
}