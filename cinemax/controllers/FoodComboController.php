<?php

require_once __DIR__ . '/../services/FoodComboService.php';

class FoodComboController
{
    private FoodComboService $comboService;
    private string $comboUploadDir;
    private string $comboUploadDbPrefix;

    public function __construct(FoodComboService $comboService)
    {
        $this->comboService = $comboService;
        $this->comboUploadDir = __DIR__ . '/../public/assets/uploads/combos';
        $this->comboUploadDbPrefix = 'public/assets/uploads/combos';
    }

    private function ensureComboUploadDirExists(): void
    {
        if (!is_dir($this->comboUploadDir)) {
            mkdir($this->comboUploadDir, 0775, true);
        }
    }

    private function saveComboImageFromRequest(string $fieldName = 'image_file'): ?string
    {
        if (!isset($_FILES[$fieldName]) || !is_array($_FILES[$fieldName])) {
            return null;
        }

        $file = $_FILES[$fieldName];
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException("Upload ảnh thất bại (mã lỗi: " . (int)$file['error'] . ").");
        }

        $tmpName = (string)($file['tmp_name'] ?? '');
        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            throw new InvalidArgumentException("File upload không hợp lệ.");
        }

        $size = (int)($file['size'] ?? 0);
        if ($size <= 0) {
            throw new InvalidArgumentException("File ảnh rỗng hoặc không hợp lệ.");
        }
        if ($size > 5 * 1024 * 1024) {
            throw new InvalidArgumentException("Ảnh quá lớn (tối đa 5MB).");
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($tmpName);
        $allowed = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            'image/gif'  => 'gif',
        ];
        if (!isset($allowed[$mime])) {
            throw new InvalidArgumentException("Định dạng ảnh không hỗ trợ (chỉ nhận JPG/PNG/WEBP/GIF).");
        }

        $ext = $allowed[$mime];
        $this->ensureComboUploadDirExists();

        $filename = 'combo_' . time() . '_' . random_int(1000, 9999) . '.' . $ext;
        $destAbs = $this->comboUploadDir . '/' . $filename;
        if (!move_uploaded_file($tmpName, $destAbs)) {
            throw new Exception("Không thể lưu ảnh lên server.");
        }

        return $this->comboUploadDbPrefix . '/' . $filename;
    }

    public function getAllCombos()
    {
        try {
            return $this->comboService->getAllCombos();
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            return [];
        }
    }

    public function getComboById($comboId)
    {
        try {
            return $this->comboService->getComboById($comboId);
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            return null;
        }
    }

    public function createCombo()
    {
        try {
            $name = $_POST['name'] ?? '';
            $description = $_POST['description'] ?? '';
            $price = $_POST['price'] ?? 0;
            $imageUrl = $this->saveComboImageFromRequest('image_file');

            if (!$name || $price === '') {
                throw new InvalidArgumentException("Vui lòng nhập đầy đủ Tên combo và Giá.");
            }

            $resultId = $this->comboService->createCombo($name, $description, $price, $imageUrl);

            if ($resultId) {
                $_SESSION['success'] = "Thêm combo thành công!";
                header('Location: ../../views/admin/index.php?page=combos&add=1');
            } else {
                throw new Exception("Không thể thêm combo.");
            }
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            header('Location: ../../views/admin/index.php?page=combos&error=1');
        }
    }

    public function updateCombo($id)
    {
        try {
            $name = $_POST['name'] ?? '';
            $description = $_POST['description'] ?? '';
            $price = $_POST['price'] ?? 0;
            $imageUrl = $this->saveComboImageFromRequest('image_file');

            if (!$id) {
                throw new InvalidArgumentException("Không tìm thấy ID combo cần cập nhật.");
            }

            if (!$name || $price === '') {
                throw new InvalidArgumentException("Vui lòng nhập đầy đủ Tên combo và Giá.");
            }

            $result = $this->comboService->updateCombo($id, $name, $description, $price, $imageUrl);

            if ($result) {
                $_SESSION['success'] = "Cập nhật combo thành công!";
                header('Location: ../../views/admin/index.php?page=combos&update=1');
            } else {
                throw new Exception("Không thể cập nhật combo.");
            }
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            header('Location: ../../views/admin/index.php?page=combos&error=1');
        }
    }

    public function deleteCombo($id)
    {
        try {
            if (!$id) {
                throw new InvalidArgumentException("Không tìm thấy ID combo cần xóa.");
            }

            $result = $this->comboService->deleteCombo($id);

            if ($result) {
                $_SESSION['success'] = "Xóa combo thành công!";
                header('Location: ../../views/admin/index.php?page=combos&delete=1');
            } else {
                throw new Exception("Không thể xóa combo.");
            }
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            header('Location: ../../views/admin/index.php?page=combos&error=1');
        }
    }
}

