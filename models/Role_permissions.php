<?php

class Role_permissions
{
    private $conn;
    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function getAll()
    {
        $sql = "SELECT * FROM role_permissions";
        $result = $this->conn->query($sql);
        $data = [];

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }

        return $data;
    }

    public function getPermissionsByRoleId($roleId)
    {
        $sql = "SELECT p.permission_code FROM permissions p
            JOIN role_permissions rp 
            ON p.permission_id = rp.permission_id
            WHERE rp.role_id = ? AND p.status = 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $roleId);
        $stmt->execute();

        $result = $stmt->get_result();
        $permissions = [];

        while ($row = $result->fetch_assoc()) {
            $permissions[] = $row['permission_code'];
        }

        return $permissions;
    }

    public function getRoleByPermissionId($permissionId)
    {
        $sql = "SELECT role_id FROM role_permissions WHERE permission_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $permissionId);
        $stmt->execute();
        $result = $stmt->get_result();
        $roleIds = [];

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $roleIds[] = $row['role_id'];
            }
        }

        return $roleIds;
    }

    public function truncate()
    {
        $this->conn->query("DELETE FROM role_permissions");
    }

    public function insert($roleId, $permissionId)
    {
        $sql = "INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $roleId, $permissionId);
        $stmt->execute();
    }

    public function save($rolePermissions)
    {
        $this->truncate();

        foreach ($rolePermissions as $roleId => $permissionIds) {
            foreach ($permissionIds as $permissionId) {
                $this->insert($roleId, $permissionId);
            }
        }
    }
}
