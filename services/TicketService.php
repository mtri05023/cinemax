<?php
require_once __DIR__ . '/../models/Ticket.php';


class TicketService
{
    private $ticketModel;
    private $conn;

    public function __construct($ticketModel)
    {
        $this->ticketModel = $ticketModel;
    }

    public function getAllTickets()
    {
        return $this->ticketModel->getAllTickets();
    }

    public function getTicketByShowId($show_id)
    {
        return $this->ticketModel->getTicketByShowId($show_id);
    }

    public function createTicket($show_id)
    {
        if (empty($show_id)) {
            throw new InvalidArgumentException("Show không tồn tại.");
        }

        $status = "available";
        $bill_id = null;
        $seats = $this->ticketModel->getallSeatsByShow($show_id);

        $count = sizeof($seats);
        if ($count <= 0) {
            throw new InvalidArgumentException("Không có ghế nào trong suất chiếu này.");
        }

        try {
            $this->ticketModel->getConnection()->begin_transaction();

            foreach ($seats as $seat) {
                $price = $seat['base_price'] * $seat['price_multiplier'];

                $this->ticketModel->createTicket($show_id, $seat['seat_id'], $bill_id, $price, $status);
            }
        } catch (Exception $e) {
            $this->ticketModel->getConnection()->rollback();
            throw new Exception($e);
        }
    }

    public function updateStatusTicket($ticket_id, $status)
    {
        return $this->ticketModel->updateStatusTicket($ticket_id, $status);
    }

    public function updateStatusByBillId($bill_id, $status)
    {
        return $this->ticketModel->updateStatusByBillId($bill_id, $status);
    }

    public function getTicketsByBillId($billId)
    {
        return $this->ticketModel->getTicketsByBillId($billId);
    }
}
