<?php

namespace App\Repositories;

use App\User;
use App\Contracts\IUserRepository;

class UserRepository implements IUserRepository
{
    private $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function getAll()
    {
        return $this->user->all();
    }

    public function add($user)
    {
        $this->user->fill([
            'name' => $user['name'],
            'email' => $user['email'],
            'password' => bcrypt($user['password']),
        ]);
        $this->user->save();
    }

    public function delete($id)
    {
        $this->user = $this->user->find('id', '=', $id);
        $this->user->delete();
    }

    public function update($userId, $userData)
    {
        // TODO: Implement update() method.
    }
}