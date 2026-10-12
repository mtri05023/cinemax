<?php

class PermissionService
{
    private $permissionModel;
    private $rolePermissionsModel;
    public function __construct($permissionModel, $rolePermissionsModel)
    {
        $this->permissionModel = $permissionModel;
        $this->rolePermissionsModel = $rolePermissionsModel;
    }

    //Permission methods
    public function getAllPermissions()
    {
        return $this->permissionModel->getAllPermissions();
    }

    public function getPermissionById($permissionId)
    {
        return $this->permissionModel->getPermissionById($permissionId);
    }

    public function getPermissionByCode($permissionCode)
    {
        return $this->permissionModel->getPermissionByCode($permissionCode);
    }

    public function createPermission($permissionCode, $description)
    {
        if (empty($permissionCode)) {
            throw new InvalidArgumentException("All fields are required to create a permission.");
        }

        if ($this->getPermissionByCode($permissionCode)) {
            throw new InvalidArgumentException("Permission code already exists.");
        }

        return $this->permissionModel->createPermission($permissionCode, $description);
    }

    public function updatePermission($permissionId, $permissionCode, $description)
    {
        if (empty($permissionCode)) {
            throw new InvalidArgumentException("All fields are required to update a permission.");
        }

        return $this->permissionModel->updatePermission($permissionId, $permissionCode, $description);
    }

    public function deletePermission($permissionId)
    {
        if($this->rolePermissionsModel->getRoleByPermissionId($permissionId)) {
            throw new InvalidArgumentException("Cannot delete permission that is assigned to a role.");
        }
        return $this->permissionModel->deletePermission($permissionId);
    }

    public function getPaginated($page = 1, $limit = 10)
    {
        $total = $this->permissionModel->getTotalCount();
        $totalPages = max(1, ceil($total / $limit));
        $page = max(1, min($page, $totalPages));
        $data = $this->permissionModel->getPaginated($page, $limit);
        return [
            'data' => $data,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'totalPages' => $totalPages
        ];
    }
}
