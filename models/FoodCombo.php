<?php

class FoodCombo
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function getAllCombos()
    {
        $sql = "SELECT * FROM combos WHERE status = 1 ORDER BY combo_id DESC";
        $result = $this->conn->query($sql);
        if (!$result) {
            throw new Exception("SQL Error: " . $this->conn->error);
        }

        $combos = [];
        while ($row = $result->fetch_assoc()) {
            $combos[] = $row;
        }

        return $combos;
    }

    public function getComboById($comboId)
    {
        $sql = "SELECT * FROM combos WHERE combo_id = ? AND status = 1";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("SQL Prepare Error: " . $this->conn->error);
        }

        $stmt->bind_param("i", $comboId);
        if (!$stmt->execute()) {
            throw new Exception("SQL Execute Error: " . $stmt->error);
        }

        $result = $stmt->get_result();
        if (!$result) {
            throw new Exception("SQL Get Result Error: " . $stmt->error);
        }

        return $result->fetch_assoc();
    }

    public function createCombo($name, $description, $price, $imageUrl = null)
    {
        $sql = "INSERT INTO combos (name, description, image_url, price, status) VALUES (?, ?, ?, ?, 1)";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("SQL Prepare Error: " . $this->conn->error);
        }

        $stmt->bind_param("sssd", $name, $description, $imageUrl, $price);
        if (!$stmt->execute()) {
            throw new Exception("SQL Execute Error: " . $stmt->error);
        }

        return $this->conn->insert_id;
    }

    public function updateCombo($comboId, $name, $description, $price, $imageUrl = null)
    {
        if ($imageUrl !== null) {
            $sql = "UPDATE combos SET name = ?, description = ?, image_url = ?, price = ? WHERE combo_id = ?";
        } else {
            $sql = "UPDATE combos SET name = ?, description = ?, price = ? WHERE combo_id = ?";
        }
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("SQL Prepare Error: " . $this->conn->error);
        }

        if ($imageUrl !== null) {
            $stmt->bind_param("sssdi", $name, $description, $imageUrl, $price, $comboId);
        } else {
            $stmt->bind_param("ssdi", $name, $description, $price, $comboId);
        }
        if (!$stmt->execute()) {
            throw new Exception("SQL Execute Error: " . $stmt->error);
        }

        return $stmt->affected_rows > 0;
    }

    public function deleteCombo($comboId)
    {
        // Soft delete: set status = 0
        $sql = "UPDATE combos SET status = 0 WHERE combo_id = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("SQL Prepare Error: " . $this->conn->error);
        }

        $stmt->bind_param("i", $comboId);
        if (!$stmt->execute()) {
            throw new Exception("SQL Execute Error: " . $stmt->error);
        }

        return $stmt->affected_rows > 0;
    }
}

