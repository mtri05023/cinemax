<?php

/**
 * AuthMiddleware - Kiểm tra JWT từ Cookie
 * Include file này ở đầu mỗi trang cần bảo vệ
 */

require_once __DIR__ . '/../config/JwtHelper.php';
require_once __DIR__ . '/../config/dbConfig.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Role_permissions.php';
require_once __DIR__ . '/../models/Role.php';

class AuthMiddleware
{
    private const COOKIE_NAME = 'cinemax_token';
    private const PERMISSION_REFRESH_TIME = 300; // 5 phút

    /**
     * Đặt JWT cookie sau khi đăng nhập
     *
     * @param array $user Thông tin user từ DB
     */
    public static function setAuthCookie(array $user): void
    {
        $payload = [
            'user_id'   => (int)$user['user_id'],
            'full_name' => $user['full_name'],
            'email'     => $user['email'],
            'role_id'   => (int)$user['role_id'],
        ];

        $token = JwtHelper::encode($payload);
        $ttl = JwtHelper::getDefaultTTL();

        setcookie(self::COOKIE_NAME, $token, [
            'expires'  => time() + $ttl,
            'path'     => '/',
            'httponly'  => true,    // Không cho JS truy cập → chống XSS
            'samesite' => 'Lax',   // Chống CSRF cơ bản
            'secure'   => false,   // Đặt true nếu dùng HTTPS
        ]);
    }

    /**
     * Xóa JWT cookie khi đăng xuất
     */
    public static function clearAuthCookie(): void
    {
        setcookie(self::COOKIE_NAME, '', [
            'expires'  => time() - 3600,
            'path'     => '/',
            'httponly'  => true,
            'samesite' => 'Lax',
            'secure'   => false,
        ]);
    }

    /**
     * Đọc và xác thực JWT từ cookie
     *
     * @return array|null Payload user nếu hợp lệ, null nếu không
     */
    public static function getAuthUser(): ?array
    {
        $token = $_COOKIE[self::COOKIE_NAME] ?? null;

        if (!$token) {
            return null;
        }

        $user = JwtHelper::decode($token);

        if (!$user) {
            return null;
        }

        // Nếu session chưa có permissions thì load
        $shouldReload = false;

        if (!isset($_SESSION['permissions'])) {
            $shouldReload = true;
        }

        if (!isset($_SESSION['permission_last_reload'])) {
            $shouldReload = true;
        }

        if (
            isset($_SESSION['permission_last_reload']) &&
            time() - $_SESSION['permission_last_reload'] > self::PERMISSION_REFRESH_TIME
        ) {
            $shouldReload = true;
        }

        if ($shouldReload) {

            $rolePermissionModel = new Role_permissions(getDBConnection());
            $permissions = $rolePermissionModel->getPermissionsByRoleId($user['role_id']);

            $_SESSION['permissions'] = $permissions;
            $_SESSION['permission_last_reload'] = time();
        }

        // lưu user vào session luôn
        $_SESSION['user'] = $user;

        return $user;
    }

    /**
     * Yêu cầu đăng nhập - redirect nếu chưa đăng nhập
     *
     * @param string $redirectUrl URL redirect khi chưa đăng nhập
     * @return array Thông tin user đã xác thực
     */
    public static function requireLogin(string $redirectUrl = '/cinemax/cinemax/views/auth/login.php?error=required'): array
    {
        $user = self::getAuthUser();

        if (!$user) {
            self::clearAuthCookie(); // Xóa cookie lỗi/hết hạn nếu có
            header('Location: ' . $redirectUrl);
            exit;
        }

        return $user;
    }

    /**
     * Yêu cầu quyền Admin - redirect nếu không phải admin
     *
     * @return array Thông tin user admin đã xác thực
     */
    public static function requireAdmin(): array
    {
        $user = self::requireLogin();

        // Lấy role_name từ bảng roles dựa vào role_id
        $roleModel = new Role(getDBConnection());
        $role = $roleModel->getRoleById($user['role_id']);
        
        // Chuyển về lowercase để so sánh chính xác
        $roleName = strtolower($role['role_name'] ?? '');
        
        // Nếu là user thường thì không cho vào admin
        if ($roleName === 'user') {
            header('Location: /cinemax/cinemax/views/auth/login.php?error=unauthorized');
            exit;
        }

        return $user;
    }

    /**
     * Lấy tên cookie
     */
    public static function getCookieName(): string
    {
        return self::COOKIE_NAME;
    }
}
