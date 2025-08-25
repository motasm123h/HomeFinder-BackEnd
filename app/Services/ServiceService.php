<?php

namespace App\Services;

use App\Repository\Models\ServiceRepository;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ServiceService
{
    public function __construct(private ServiceRepository $repository) {}

    public function getAllPaginated(int $perPage = 10)
    {
        return $this->repository->allPaginated($perPage);
    }

    public function findById(int $id)
    {
        $service = $this->repository->findById($id);

        if (! $service) {
            throw new \Exception('Service not found', 404);
        }

        return $service;
    }

    public function create(array $data)
    {
        $data['user_id'] = auth()->id();

        $validator = Validator::make($data, [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'services_type_id' => 'required|exists:services_types,id',
            'user_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $this->repository->create($data);
    }

    public function update(int $id, array $data)
    {
        $validator = Validator::make($data, [
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'services_type_id' => 'sometimes|exists:services_types,id',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        if (! $this->repository->update($data, $id)) {
            throw new \Exception('Failed to update service', 500);
        }

        return $this->repository->findById($id);
    }

    public function delete(int $id)
    {
        if (! $this->repository->delete($id)) {
            throw new \Exception('Failed to delete service', 500);
        }
    }
}
