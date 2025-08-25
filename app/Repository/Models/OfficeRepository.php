<?php

namespace App\Repository\Models;

use App\Models\RealEstate_Request;
use App\Repository\Repo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

class OfficeRepository extends Repo
{
    public function __construct(private RealEstate_Request $model)
    {

        parent::__construct(RealEstate_Request::class);
    }

    public function Arrivepaginate(int $perPage = 15): LengthAwarePaginator
    {
        return auth()->user()->realEstateRequests()->paginate($perPage);
    }

    public function Sentpaginate(int $perPage = 15): LengthAwarePaginator
    {
        return auth()->user()->sentRealEstateRequests()->paginate($perPage);
    }

    public function create(array $data): Model
    {
        return parent::create($data);
    }

    public function delete(int $id): bool
    {
        return parent::delete($id);
    }
}
