<?php


namespace App\Repositories;


use App\Models\Users;

class UsersRepository
{
    protected $users;

    public function __construct(Users $users)
    {
        $this->users = $users;
    }

    public function getUserById($id)
    {
        return $this->users->find($id);
    }
}