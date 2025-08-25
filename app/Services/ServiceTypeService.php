<?php

namespace App\Services;

use App\Repository\Models\ServiceTypeRepository;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ServiceTypeService
{
    public function __construct(private ServiceTypeRepository $repository) {}

    public function create(array $data)
    {
        $validator = Validator::make($data, [
            // 'type' => 'required|string|max:255|unique:services_types,type'
            'type' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $this->repository->create($data);
    }

    public function delete(int $id)
    {
        if (! $this->repository->delete($id)) {
            throw new \Exception('Failed to delete service type', 500);
        }
    }
}
