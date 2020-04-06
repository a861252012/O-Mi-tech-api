<?php
/**
 * 會員 資源庫
 * @author Weine
 * @date 2020-03-26
 */
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