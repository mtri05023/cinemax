<?php

class Role
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function getRoleById($roleId)
    {
        $sql = "SELECT * FROM roles WHERE role_id = ? AND status = 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $roleId);
        $stmt->execute();

        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    public function getAllRoles()
    {
        $sql = "SELECT * FROM roles WHERE status = 1";
        $result = $this->conn->query($sql);

        $roles = [];
        while ($row = $result->fetch_assoc()) {
            $roles[] = $row;
        }

        return $roles;
    }

    public function getRoleByName($roleName)
    {
        $sql = "SELECT * FROM roles WHERE role_name LIKE ? AND status = 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $roleName);
        $stmt->execute();

        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function createRole($roleName, $description)
    {
        $sql = "INSERT INTO roles (role_name, description, status) VALUES (?, ?, 1)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ss", $roleName, $description);
        return $stmt->execute();
    }

    public function updateRole($roleId, $roleName, $description)
    {
        $sql = "UPDATE roles SET role_name = ?, description = ? WHERE role_id = ? AND status = 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ssi", $roleName, $description, $roleId);
        return $stmt->execute();
    }

    public function deleteRole($roleId)
    {
        $sql = "UPDATE roles SET status = 0 WHERE role_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $roleId);
        return $stmt->execute();
    }

    public function getPaginated($page = 1, $limit = 10)
    {
        $offset = ($page - 1) * $limit;
        $sql = "SELECT * FROM roles WHERE status = 1 ORDER BY role_id DESC LIMIT ? OFFSET ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        $roles = [];
        while ($row = $result->fetch_assoc()) {
            $roles[] = $row;
        }
        return $roles;
    }

    public function getTotalCount()
    {
        $sql = "SELECT COUNT(*) as total FROM roles WHERE status = 1";
        $result = $this->conn->query($sql);
        $row = $result->fetch_assoc();
        return (int)$row['total'];
    }

    public function getAllPermissionsByRole($roleId)
    {
        $sql = "SELECT p.* FROM permissions p
                JOIN role_permissions rp ON p.permission_id = rp.permission_id
                WHERE rp.role_id = ? AND p.status = 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $roleId);
        $stmt->execute();
        $result = $stmt->get_result();
        $permissions = [];
        while ($row = $result->fetch_assoc()) {
            $permissions[] = $row;
        }
        return $permissions;
    }
}
