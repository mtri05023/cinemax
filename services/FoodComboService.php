<?php
require_once __DIR__ . '/../models/FoodCombo.php';

class FoodComboService
{
    private $comboModel;

    public function __construct($comboModel)
    {
        $this->comboModel = $comboModel;
    }

    public function getAllCombos()
    {
        return $this->comboModel->getAllCombos();
    }

    public function getComboById($comboId)
    {
        if (empty($comboId) || !is_numeric($comboId)) {
            throw new InvalidArgumentException("Combo ID không hợp lệ.");
        }

        return $this->comboModel->getComboById($comboId);
    }

    public function createCombo($name, $description, $price, $imageUrl = null)
    {
        $name = trim((string)$name);
        $description = trim((string)$description);

        if ($name === '') {
            throw new InvalidArgumentException("Tên combo không được để trống.");
        }

        if (mb_strlen($name) > 100) {
            throw new InvalidArgumentException("Tên combo không được vượt quá 100 ký tự.");
        }

        if (mb_strlen($description) > 70) {
            throw new InvalidArgumentException("Mô tả đồ ăn không được vượt quá 70 ký tự.");
        }

        if (!is_numeric($price) || $price < 0) {
            throw new InvalidArgumentException("Giá combo phải là số không âm.");
        }

        $imageUrl = $imageUrl !== null ? trim((string)$imageUrl) : null;
        return $this->comboModel->createCombo($name, $description, (float)$price, $imageUrl);
    }

    public function updateCombo($comboId, $name, $description, $price, $imageUrl = null)
    {
        if (empty($comboId) || !is_numeric($comboId)) {
            throw new InvalidArgumentException("Combo ID không hợp lệ.");
        }

        $name = trim((string)$name);
        $description = trim((string)$description);

        if ($name === '') {
            throw new InvalidArgumentException("Tên combo không được để trống.");
        }

        if (mb_strlen($name) > 100) {
            throw new InvalidArgumentException("Tên combo không được vượt quá 100 ký tự.");
        }

        if (mb_strlen($description) > 70) {
            throw new InvalidArgumentException("Mô tả đồ ăn không được vượt quá 70 ký tự.");
        }

        if (!is_numeric($price) || $price < 0) {
            throw new InvalidArgumentException("Giá combo phải là số không âm.");
        }

        $imageUrl = $imageUrl !== null ? trim((string)$imageUrl) : null;
        return $this->comboModel->updateCombo($comboId, $name, $description, (float)$price, $imageUrl);
    }

    public function deleteCombo($comboId)
    {
        if (empty($comboId) || !is_numeric($comboId)) {
            throw new InvalidArgumentException("Combo ID không hợp lệ.");
        }

        return $this->comboModel->deleteCombo($comboId);
    }
}

