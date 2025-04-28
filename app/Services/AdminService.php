<?php 
namespace App\services;
use App\Repository\Models\AdminRepository;
class AdminService
{
    public function __construct(
        private AdminRepository $repository,
    ) {}

    public function getAllAdminUsers(int $perPage = 15)
    {
    return $this->repository->getAllByRole(0, $perPage);
    }   

    public function changeActivation(string $status, int $id)
    {
        return $this->repository->update(['status' => $status], $id);
    }

    public function deleteUser(string $pro="user_id",int $id)
    {
        return $this->repository->delete($id);
    }
}