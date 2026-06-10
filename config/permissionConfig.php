<?php

require_once __DIR__ . '/../models/Role.php';
require_once __DIR__ . '/dbConfig.php';

/**
 * Lấy role_name từ session (cache) hoặc load từ DB
 */
function getUserRoleName()
{
    if (!isset($_SESSION['user']['role_id'])) {
        return '';
    }

    // Cache role_name trong session để tránh query nhiều lần
    if (!isset($_SESSION['user_role_name'])) {
        $roleModel = new Role(getDBConnection());
        $role = $roleModel->getRoleById($_SESSION['user']['role_id']);
        $_SESSION['user_role_name'] = strtolower($role['role_name'] ?? '');
    }

    return $_SESSION['user_role_name'];
}

function hasPermission($permission)
{
    // Admin bypass - kiểm tra theo role_name
    $roleName = getUserRoleName();
    if ($roleName === 'admin') {
        return true;
    }

    if (!isset($_SESSION['permissions'])) {
        return false;
    }

    return in_array($permission, $_SESSION['permissions']);
}

function hasModule($module)
{
    // Admin bypass - kiểm tra theo role_name
    $roleName = getUserRoleName();
    if ($roleName === 'admin') {
        return true;
    }

    if (!isset($_SESSION['permissions'])) {
        return false;
    }

    foreach ($_SESSION['permissions'] as $permission) {

        if (strpos($permission, $module . "_") === 0) {
            return true;
        }
    }

    return false;
}
