<?php

namespace APP\Services;

class Users
{
    private $userModel;
    public function __construct($userModel)
    {
        $this->userModel = $userModel;
    }

    public function getUserById(int $id): ?array
    {
        return $this->userModel->getById($id);
    }
    
}