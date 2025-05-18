<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Post;

class PostPolicy
{

    public function createRequest(User $user, array $data): bool
    {
        return $user->id === $data['user_id'] || $user->role !== 0;
    }


    public function update(User $user, array $data): bool
    {
        return $user->id === $data['user_id'] || $user->role === 0;
    }


    public function delete(User $user, int $id): bool
    {
        return $user->id === $id|| $user->role === 0;
    }
}
