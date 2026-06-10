<?php

require_once __DIR__ . '/../services/PromotionService.php';
require_once __DIR__ . '/../config/dbConfig.php';

class PromotionController
{
    private PromotionService $promotionService;

    public function __construct(PromotionService $promotionService)
    {
        $this->promotionService = $promotionService;
    }

    public function getAllPromotions($search = '')
    {
        try {
            return $this->promotionService->listPromotions($search);
        } catch (Exception $e) {
            header('Location: /index.php?page=promotions&error=' . urlencode($e->getMessage()));
        }
    }

    public function createPromotion()
    {
        try {
            $data = [
                'code'             => $_POST['code'] ?? '',
                'name'             => $_POST['name'] ?? '',
                'discount_type'    => $_POST['discount_type'] ?? 'percent',
                'discount_value'   => $_POST['discount_value'] ?? 0,
                'discount_percent' => $_POST['discount_percent'] ?? 0,
                'start_date'       => $_POST['start_date'] ?? '',
                'end_date'         => $_POST['end_date'] ?? '',
                'min_amount'       => $_POST['min_amount'] ?? 0,
                'usage_limit'      => $_POST['usage_limit'] ?? 0,
            ];

            $this->promotionService->createPromotion($data);

            header('Location: ../../views/admin/index.php?page=promotions&add=1');
        } catch (Exception $e) {
            header('Location: ../../views/admin/index.php?page=promotions&error=' . urlencode($e->getMessage()));
        }
    }

    public function updatePromotion($id)
    {
        try {
            $data = [
                'code'             => $_POST['code'] ?? '',
                'name'             => $_POST['name'] ?? '',
                'discount_type'    => $_POST['discount_type'] ?? 'percent',
                'discount_value'   => $_POST['discount_value'] ?? 0,
                'discount_percent' => $_POST['discount_percent'] ?? 0,
                'start_date'       => $_POST['start_date'] ?? '',
                'end_date'         => $_POST['end_date'] ?? '',
                'min_amount'       => $_POST['min_amount'] ?? 0,
                'usage_limit'      => $_POST['usage_limit'] ?? 0,
            ];

            $this->promotionService->updatePromotion((int)$id, $data);

            header('Location: ../../views/admin/index.php?page=promotions&update=1');
        } catch (Exception $e) {
            header('Location: ../../views/admin/index.php?page=promotions&error=' . urlencode($e->getMessage()));
        }
    }

    public function deletePromotion($id)
    {
        try {
            $this->promotionService->deletePromotion((int)$id);

            header('Location: ../../views/admin/index.php?page=promotions&delete=1');
        } catch (Exception $e) {
            header('Location: ../../views/admin/index.php?page=promotions&error=' . urlencode($e->getMessage()));
        }
    }

    public function endPromotion($id)
    {
        try {
            $this->promotionService->endPromotion((int)$id);

            header('Location: ../../views/admin/index.php?page=promotions&end=1');
        } catch (Exception $e) {
            header('Location: ../../views/admin/index.php?page=promotions&error=' . urlencode($e->getMessage()));
        }
    }
}
