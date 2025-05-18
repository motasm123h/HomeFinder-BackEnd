<?php
namespace App\Repository\Models;
use App\Models\User;
use App\Repository\Repo;

class AdminRepository extends Repo
{
    public function __construct()
    {
        parent::__construct(User::class);
    }

    public function getAllByRole(int $role, int $perPage = 15)
    {
        return parent::getAmount('role',$role,$perPage);
    }

    public function update(array $data, int $id): User
    {
        $user = parent::findOrFail($id);
        $user->update($data);
        return $user;
    }

    public function delete(int $id): bool
    {
        return parent::delete($id);
    }
}