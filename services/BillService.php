<?php

require_once __DIR__ . '/../models/Bill.php';
require_once __DIR__ . '/../services/TicketService.php';

class BillService
{
    private $billModel;
    private $ticketService;


    public function __construct($billModel, $ticketService)
    {
        $this->billModel = $billModel;
        $this->ticketService = $ticketService;
    }

    /**
     * Lấy tất cả bills
     */
    public function getAllBills()
    {
        return $this->billModel->getAllBills();
    }

    /**
     * Lấy bill theo ID
     */
    public function getBillById($billId)
    {
        return $this->billModel->getBillById($billId);
    }

    /**
     * Lấy bills phân trang với filter
     */
    public function getPaginated($page = 1, $limit = 10, $status = null, $search = null)
    {
        $total = $this->billModel->getTotalCount($status, $search);
        $totalPages = max(1, ceil($total / $limit));
        $page = max(1, min($page, $totalPages));
        $data = $this->billModel->getPaginated($page, $limit, $status, $search);

        return [
            'data' => $data,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'totalPages' => $totalPages
        ];
    }

    /**
     * Lấy thống kê số lượng theo status
     */
    public function getStats()
    {
        return $this->billModel->getCountByStatus();
    }

    /**
     * Cập nhật trạng thái bill (xác nhận thanh toán)
     */
    public function confirmPayment($billId)
    {
        $bill = $this->billModel->getBillById($billId);
        if (!$bill) {
            throw new InvalidArgumentException("Đơn hàng không tồn tại.");
        }

        if ($bill['status'] !== 'pending') {
            throw new InvalidArgumentException("Chỉ có thể xác nhận đơn hàng đang chờ thanh toán.");
        }

        $this->billModel->updateStatus($billId, 'paid');

        $this->ticketService->updateStatusByBillId($billId, 'paid');

        return true;
    }

    /**
     * Hủy đơn hàng
     */
    public function cancelBill($billId)
    {
        $bill = $this->billModel->getBillById($billId);
        if (!$bill) {
            throw new InvalidArgumentException("Đơn hàng không tồn tại.");
        }

        if ($bill['status'] === 'cancelled') {
            throw new InvalidArgumentException("Đơn hàng đã bị hủy trước đó.");
        }

        if ($bill['status'] === 'paid') {
            throw new InvalidArgumentException("Không thể hủy đơn hàng đã thanh toán. Vui lòng hoàn tiền.");
        }

        return $this->billModel->updateStatus($billId, 'cancelled');
    }

    /**
     * Hoàn tiền đơn hàng
     */
    public function refundBill($billId)
    {
        $bill = $this->billModel->getBillById($billId);
        if (!$bill) {
            throw new InvalidArgumentException("Đơn hàng không tồn tại.");
        }

        if ($bill['status'] !== 'paid') {
            throw new InvalidArgumentException("Chỉ có thể hoàn tiền đơn hàng đã thanh toán.");
        }

        return $this->billModel->updateStatus($billId, 'refunded');
    }

    /**
     * Lấy chi tiết đơn hàng (tickets + combos)
     */
    public function getBillDetails($billId)
    {
        $bill = $this->billModel->getBillById($billId);
        if (!$bill) {
            return null;
        }

        $bill['tickets'] = $this->billModel->getTicketsByBillId($billId);
        $bill['combos'] = $this->billModel->getCombosByBillId($billId);

        return $bill;
    }

    public function saveBillCombos($billId, $combos)
    {
        if (empty($combos)) return;

        $this->billModel->insertBillCombos($billId, $combos);
    }

    public function getBillsByUserId($userId)
    {
        if (empty($userId)) {
            throw new InvalidArgumentException("User không tồn tại.");
        }
        return $this->billModel->getBillsByUserId($userId);
    }
}
