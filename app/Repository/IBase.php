<?php

namespace App\Repository;
use Illuminate\Support\Collection; 
use Illuminate\Database\Eloquent\Model;
interface IBase {
    public function index();
    public function create(array $request): ?Model;
    public function update(array $request, int $id): ?Model;
    public function delete(string $pro,int $id): bool;
    public function findOrFail(int $id): Model;
    public function getAmount(string $propertie,int $id,int $perPage);
}