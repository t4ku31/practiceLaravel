<?php

namespace App\Repositories;

use App\Models\User;

class UserRepository
{
    public function count(): int
    {
        return User::count();
    }

    public function all()
    {
        return User::all();
    }
}
