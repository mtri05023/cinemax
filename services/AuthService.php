<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/AuthMiddleware.php';

class AuthService
{
    private $userModel;

    public function __construct($userModel)
    {
        $this->userModel = $userModel;
    }

    /**
     * Đăng ký tài khoản mới
     */
    public function register($fullName, $email, $password, $confirmPassword, $phone)
    {
        // Validate dữ liệu đầu vào
        if (empty($fullName) || empty($email) || empty($password) || empty($confirmPassword) || empty($phone)) {
            throw new InvalidArgumentException("empty");
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("email_invalid");
        }

        if (strlen($password) < 6) {
            throw new InvalidArgumentException("password_short");
        }

        if ($password !== $confirmPassword) {
            throw new InvalidArgumentException("password_mismatch");
        }

        // Kiểm tra email đã tồn tại chưa
        $existingUser = $this->userModel->getUserByEmail($email);
        if ($existingUser) {
            throw new InvalidArgumentException("email_exists");
        }

        // role_id = 2 là user thường (1 = admin)
        $defaultRoleId = 2;

        try {
            $result = $this->userModel->createUser($fullName, $email, $password, $phone, $defaultRoleId);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }

        if (!$result) {
            throw new \Exception("can not create user");
        }

        return true;
    }

    /**
     * Đăng nhập - xác thực và tạo JWT cookie
     */
    public function login($email, $password)
    {
        if (empty($email) || empty($password)) {
            throw new InvalidArgumentException("empty");
        }

        $user = $this->userModel->getUserByEmail($email);

        if (!$user) {
            throw new InvalidArgumentException("invalid");
        }

        // So sánh mật khẩu hash
        if (!password_verify($password, $user['password_hash'])) {
            throw new InvalidArgumentException("invalid");
        }

        // Cập nhật thời gian đăng nhập
        $this->userModel->updateLastLogin($user['user_id']);

        // Tạo JWT và lưu vào HttpOnly Cookie
        AuthMiddleware::setAuthCookie($user);

        return $user;
    }

    /**
     * Đăng xuất - xóa JWT cookie
     */
    public function logout()
    {
        AuthMiddleware::clearAuthCookie();
    }
}

