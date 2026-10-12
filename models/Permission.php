<?php

class Permission
{
    private $conn;
    public function __construct($dbConnection)
    {
        $this->conn = $dbConnection;
    }

    public function getAllPermissions()
    {
        $sql = "SELECT * FROM permissions WHERE status = 1";
        $result = $this->conn->query($sql);

        $permissions = [];
        while ($row = $result->fetch_assoc()) {
            $permissions[] = $row;
        }

        return $permissions;
    }

    public function getPermissionById($permissionId)
    {
        $sql = "SELECT * FROM permissions WHERE permission_id = ? AND status = 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $permissionId);
        $stmt->execute();

        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function getPermissionByCode($permissionCode)
    {
        $sql = "SELECT * FROM permissions WHERE permission_code = ? AND status = 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $permissionCode);
        $stmt->execute();

        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function createPermission($permissionCode, $description)
    {
        $sql = "INSERT INTO permissions (permission_code, description, status) VALUES (?, ?, 1)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ss", $permissionCode, $description);
        return $stmt->execute();
    }

    public function updatePermission($permissionId, $permissionCode, $description)
    {
        $sql = "UPDATE permissions SET permission_code = ?, description = ? WHERE permission_id = ? AND status = 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ssi", $permissionCode, $description, $permissionId);
        return $stmt->execute();
    }

    public function deletePermission($permissionId)
    {
        $sql = "UPDATE permissions SET status = 0 WHERE permission_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $permissionId);
        return $stmt->execute();
    }

    public function getPaginated($page = 1, $limit = 10)
    {
        $offset = ($page - 1) * $limit;
        $sql = "SELECT * FROM permissions WHERE status = 1 ORDER BY permission_id DESC LIMIT ? OFFSET ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        $items = [];
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
        return $items;
    }

    public function getTotalCount()
    {
        $sql = "SELECT COUNT(*) as total FROM permissions WHERE status = 1";
        $result = $this->conn->query($sql);
        $row = $result->fetch_assoc();
        return (int)$row['total'];
    }
}
