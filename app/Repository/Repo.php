<?php
namespace App\Repository;

use App\Repository\IBase;
use App\Traits\ResponseTrait;
// use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Collection; 
use Illuminate\Database\Eloquent\Model;

class Repo implements IBase {
    use ResponseTrait;
    private $model;

    public function __construct($model){
        return $this->model = app($model);
    }


    public function index(array $with = [],int $perPage = 10)
    {
        $query = $this->model->newQuery();
        if (!empty($with)) {
            $query->with($with);
        }
        return $query->paginate($perPage);
    }


    public function findOrFail(int $id, array $with = [])
    {
        $query = $this->model->newQuery();
        
        if (!empty($with)) {
            $query->with($with);
        }
        
        return $query->findOrFail($id);
    }

    public function create(array $request): Model {  
        $createObject = $this->model->create($request);
        return $createObject 
            ? $createObject
            :  null;
    }
    
    public function getAmount(string $propertie, int $id,int $perPage){
        return $this->model->where($propertie,$id)->paginate($perPage);
    }

    public function update(array $request, int $id): Model {
        $model = $this->model->where('id', $id)
                         ->where('user_id', auth()->user()->id) // Check ownership
                         ->first();

        if (!$model) {
            return null;
        }

        return $model->update($data) ? $model->fresh() : null;
    }
    
    public function delete(int $id): bool{
        $model = $this->model->where('id', $id)
                        //  ->where($pro, auth()->user()->id) 
                         ->first();
        if(!$model){
            return false;
        }
        if($model->delete()){
            return true;
        }
        return false;
    }

}