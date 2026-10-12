<?php

require_once __DIR__ . '/../services/UserService.php';
require_once __DIR__ . '/../services/RoleService.php';
require_once __DIR__ . '/../services/PermissionService.php';
require_once __DIR__ . '/../services/BillService.php';
require_once __DIR__ . '/../config/dbConfig.php';

//Xử lý toàn bộ chức năng bên admin 
class AdminController
{
    private UserService $userService;
    private RoleService $roleService;
    private PermissionService $permissionService;
    private ?BillService $billService;

    public function __construct(UserService $userService, RoleService $roleService, PermissionService $permissionService, ?BillService $billService = null)
    {
        $this->userService = $userService;
        $this->roleService = $roleService;
        $this->permissionService = $permissionService;
        $this->billService = $billService;
    }

    //User Management Functions
    public function getAllUsers()
    {
        try {
            $users = $this->userService->getAllUsers();
            return $users;
        } catch (Exception $e) {
            header('Location: /index.php?page=users&error=' . urlencode($e->getMessage()));
        }
    }

    public function createUser()
    {
        try {
            $result = $this->userService->createUser($_POST['full_name'], $_POST['email'], $_POST['password'], $_POST['phone'], $_POST['role_id']);

            header('Location: ../../views/admin/index.php?page=users&add=1');
        } catch (Exception $e) {
            header('Location: ../../views/admin/index.php?page=users&error=' . urlencode($e->getMessage()));
        }
    }

    public function updateUser($id)
    {
        try {
            $result = $this->userService->updateUser($id, $_POST['full_name'], $_POST['email'], $_POST['phone'], $_POST['role_id']);

            header('Location: ../../views/admin/index.php?page=users&update=1');
        } catch (Exception $e) {
            header('Location: ../../views/admin/index.php?page=users&error=' . urlencode($e->getMessage()));
        }
    }

    public function deleteUser($id)
    {
        try {
            $result = $this->userService->deleteUser($id);

            header('Location: ../../views/admin/index.php?page=users&delete=1');
        } catch (Exception $e) {
            header('Location: ../../views/admin/index.php?page=users&error=' . urlencode($e->getMessage()));
        }
    }

    //Role Management Functions
    public function getAllRoles()
    {
        try {
            $roles = $this->roleService->getAllRoles();
            return $roles;
        } catch (Exception $e) {
            header('Location: /index.php?page=roles&error=' . urlencode($e->getMessage()));
        }
    }

    public function getRoleById($RoleID)
    {
        try {
            $role = $this->roleService->getRoleById($RoleID);
            return $role;
        } catch (Exception $e) {
            header('Location: /index.php?page=roles&error=' . urlencode($e->getMessage()));
        }
    }

    public function createRole(): void
    {
        try {
            $result = $this->roleService->createRole($_POST['role_name'], $_POST['description']);

            header('Location: ../../views/admin/index.php?page=roles&add=1');
        } catch (Exception $e) {
            header('Location: ../../views/admin/index.php?page=roles&error=' . urlencode($e->getMessage()));
        }
    }

    public function updateRole($RoleID)
    {
        try {
            $result = $this->roleService->updateRole($RoleID, $_POST['role_name'], $_POST['description']);

            header('Location: ../../views/admin/index.php?page=roles&update=1');
        } catch (Exception $e) {
            header('Location: ../../views/admin/index.php?page=roles&error=' . urlencode($e->getMessage()));
        }
    }

    public function deleteRole($RoleID)
    {
        try {
            $result = $this->roleService->deleteRole($RoleID);

            header('Location: ../../views/admin/index.php?page=roles&delete=1');
        } catch (Exception $e) {
            header('Location: ../../views/admin/index.php?page=roles&error=' . urlencode($e->getMessage()));
        }
    }

    public function getAllPermissionsByRole($roleId)
    {
        try {
            $permissions = $this->roleService->getAllPermissionsByRole($roleId);
            return $permissions;
        } catch (Exception $e) {
            header('Location: /index.php?page=roles&error=' . urlencode($e->getMessage()));
        }
    }

    //Permission Management Functions
    public function getAllPermissions()
    {
        try {
            $permissions = $this->permissionService->getAllPermissions();
            return $permissions;
        } catch (Exception $e) {
            header('Location: /index.php?page=permissions&error=' . urlencode($e->getMessage()));
        }
    }

    public function getPermissionById($permissionId)
    {
        try {
            $permission = $this->permissionService->getPermissionById($permissionId);
            return $permission;
        } catch (Exception $e) {
            header('Location: /index.php?page=permissions&error=' . urlencode($e->getMessage()));
        }
    }

    public function createPermission()
    {
        try {
            $permissionCode = $_POST['module'] . '_' . $_POST['action'];
            $result = $this->permissionService->createPermission($permissionCode, $_POST['description']);

            header('Location: ../../views/admin/index.php?page=permissions&add=1');
        } catch (Exception $e) {
            header('Location: ../../views/admin/index.php?page=permissions&error=' . urlencode($e->getMessage()));
        }
    }

    public function updatePermission($permissionId)
    {
        try {
            $permissionCode = $_POST['module'] . '_' . $_POST['action'];
            $result = $this->permissionService->updatePermission($permissionId, $permissionCode, $_POST['description']);

            header('Location: ../../views/admin/index.php?page=permissions&update=1');
        } catch (Exception $e) {
            header('Location: ../../views/admin/index.php?page=permissions&error=' . urlencode($e->getMessage()));
        }
    }

    public function deletePermission($permissionId)
    {
        try {
            $result = $this->permissionService->deletePermission($permissionId);

            header('Location: ../../views/admin/index.php?page=permissions&delete=1');
        } catch (Exception $e) {
            header('Location: ../../views/admin/index.php?page=permissions&error=' . urlencode($e->getMessage()));
        }
    }

    // ========== Bill/Booking Management Functions ==========

    /**
     * Lấy danh sách bills phân trang
     */
    public function getBillsPaginated($page = 1, $limit = 10, $status = null, $search = null)
    {
        try {
            return $this->billService->getPaginated($page, $limit, $status, $search);
        } catch (Exception $e) {
            return ['data' => [], 'total' => 0, 'page' => 1, 'limit' => $limit, 'totalPages' => 1];
        }
    }

    /**
     * Lấy thống kê bills
     */
    public function getBillStats()
    {
        try {
            return $this->billService->getStats();
        } catch (Exception $e) {
            return ['pending' => 0, 'paid' => 0, 'cancelled' => 0, 'refunded' => 0, 'total' => 0];
        }
    }

    /**
     * Xác nhận thanh toán
     */
    public function confirmPayment($billId)
    {
        try {
            $this->billService->confirmPayment($billId);
            header('Location: ../../views/admin/index.php?page=bookings&success=confirmed');
        } catch (Exception $e) {
            header('Location: ../../views/admin/index.php?page=bookings&error=' . urlencode($e->getMessage()));
        }
        exit;
    }

    /**
     * Hủy đơn hàng
     */
    public function cancelBill($billId)
    {
        try {
            $this->billService->cancelBill($billId);
            header('Location: ../../views/admin/index.php?page=bookings&success=cancelled');
        } catch (Exception $e) {
            header('Location: ../../views/admin/index.php?page=bookings&error=' . urlencode($e->getMessage()));
        }
        exit;
    }

    /**
     * Lấy chi tiết đơn hàng
     */
    public function getBillDetails($billId)
    {
        try {
            return $this->billService->getBillDetails($billId);
        } catch (Exception $e) {
            return null;
        }
    }
}
