<?php

class RoleService
{
    private $roleModel;

    public function __construct($roleModel)
    {
        $this->roleModel = $roleModel;
    }

    // Role methods
    public function getAllRoles()
    {
        return $this->roleModel->getAllRoles();
    }

    public function getRoleByName($roleName)
    {
        if (empty($roleName)) {
            throw new InvalidArgumentException("Role name cannot be empty.");
        }

        return $this->roleModel->getRoleByName($roleName);
    }
    public function getRoleById($RoleID)
    {
        return $this->roleModel->getRoleById($RoleID);
    }

    public function createRole($roleName, $description)
    {
        if (empty($roleName)) {
            throw new InvalidArgumentException("All fields are required to create a role.");
        }

        $existingRole = $this->roleModel->getRoleByName($roleName);
        if ($existingRole) {
            throw new Exception("Role already exists.");
        }

        return $this->roleModel->createRole(strtoupper($roleName), $description);
    }

    public function updateRole($RoleID, $roleName, $description)
    {
        if (empty($roleName)) {
            throw new InvalidArgumentException("All fields are required to update a role.");
        }

        $existingRole = $this->roleModel->getRoleByName($roleName);
        if ($existingRole && $existingRole['role_id'] != $RoleID) {
            throw new InvalidArgumentException("Role name already exists.");
        }

        return $this->roleModel->updateRole($RoleID, $roleName, $description);
    }

    public function deleteRole($RoleID)
    {
        return $this->roleModel->deleteRole($RoleID);
    }

    public function getPaginated($page = 1, $limit = 10)
    {
        $total = $this->roleModel->getTotalCount();
        $totalPages = max(1, ceil($total / $limit));
        $page = max(1, min($page, $totalPages));
        $data = $this->roleModel->getPaginated($page, $limit);
        return [
            'data' => $data,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'totalPages' => $totalPages
        ];
    }

    public function getAllPermissionsByRole($roleId)
    {
        return $this->roleModel->getAllPermissionsByRole($roleId);
    }
}
