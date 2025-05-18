<?php
namespace App\Repository\Models;
use App\Models\Services;
use App\Repository\Repo;
use Illuminate\Database\Eloquent\Model;

class ServiceRepository extends Repo {
    public function __construct()
    {
        parent::__construct(Services::class);
    }
    
    public function allPaginated(int $perPage = 10)
    {
        return parent::index(['servicesType', 'usersInfo'],10);
    }

    public function findById(int $id)
    {
        return parent::findOrFail($id,['servicesType', 'usersInfo']);
    }

    public function create(array $data): Model
    {
        return parent::create($data);
    }

    public function update(array $data, int $id): Model
    {
        $model = parent::findOrFail($id);
        $model->update($data);
        return $model->fresh();
    }

    public function delete(int $id): bool
    {
        return parent::findOrFail($id)->delete();
    }
}