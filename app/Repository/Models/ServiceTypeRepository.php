<?php

namespace App\Repository\Models;

use App\Models\Services_Type;
use App\Repository\Repo;
use Illuminate\Database\Eloquent\Model;
class ServiceTypeRepository extends Repo
{
    public function __construct()
    {
        parent::__construct(Services_Type::class);
    }
    public function create(array $data): Model
    {
        return parent::create($data);
    }

    public function delete(int $id): bool
    {
        return parent::findOrFail($id)->delete();
    }

    public function hasServices(int $id)
    {
        return parent::findOrFail($id)->servicesInfo()->exists();
    }
}