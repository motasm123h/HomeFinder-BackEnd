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


    public function index() 
    {
        return $this->model->all();
    }

    public function findOrFail(int $id): Model{
        return $this->model->findOrFail($id);
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
    
    public function delete(string $pro,int $id): bool{
        $model = $this->model->where('id', $id)
                         ->where($pro, auth()->user()->id) // Check ownership
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