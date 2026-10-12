<?php
require_once __DIR__ . '/../models/User.php';

class UserService
{
    private $userModel;

    public function __construct($userModel)
    {
        $this->userModel = $userModel;
    }

    public function getAllUsers()
    {
        return $this->userModel->getAllUsers();
    }

    public function getUserByName($fullName)
    {
        if (empty($fullName)) {
            throw new InvalidArgumentException("Full name cannot be empty.");
        }

        return $this->userModel->getUserByName($fullName);
    }

    public function getUserById($UserID)
    {
        return $this->userModel->getUserById($UserID);
    }

    public function createUser($fullName, $email, $password, $phone, $role_id)
    {
        if (empty($fullName) || empty($email) || empty($password) || empty($phone) || empty($role_id)) {
            throw new InvalidArgumentException("All fields are required to create a user.");
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Invalid email format.");
        }

        if (!preg_match('/^0[0-9]{9}$/', $phone)) {
            throw new InvalidArgumentException("Invalid phone number.");
        }

        $existingEmail = $this->userModel->getUserByEmail($email);
        if ($existingEmail) {
            throw new InvalidArgumentException("Email already exists.");
        }

        return $this->userModel->createUser($fullName, $email, $password, $phone, $role_id);
    }

    public function updateUser($UserID, $fullName, $email, $phone, $role_id)
    {
        if (empty($fullName) || empty($email) || empty($phone) || empty($role_id)) {
            throw new InvalidArgumentException("All fields are required to create a user.");
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Invalid email format.");
        }

        $existingEmail = $this->userModel->getUserByEmail($email);
        if ($existingEmail && $existingEmail['user_id'] != $UserID) {
            throw new InvalidArgumentException("Email already exists.");
        }

        if (!preg_match('/^0[0-9]{9}$/', $phone)) {
            throw new InvalidArgumentException("Invalid phone number.");
        }

        return $this->userModel->updateUser($UserID, $fullName, $email, $phone, $role_id);
    }

    public function getPaginated($page = 1, $limit = 10)
    {
        $total = $this->userModel->getTotalCount();
        $totalPages = max(1, ceil($total / $limit));
        $page = max(1, min($page, $totalPages));
        $data = $this->userModel->getPaginated($page, $limit);
        return [
            'data' => $data,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'totalPages' => $totalPages
        ];
    }

    public function deleteUser($UserID)
    {
        return $this->userModel->deleteUser($UserID);
    }
}
