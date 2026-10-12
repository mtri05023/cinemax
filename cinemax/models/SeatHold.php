<?php
class SeatHold
{
    public function __construct(private mysqli $conn) {}

    public function expire(): void
    {
        // NOW() is authoritative. Old bills without a deadline are untouched.
        $this->conn->query("UPDATE bills SET status='cancelled' WHERE status='pending' AND payment_expires_at IS NOT NULL AND payment_expires_at<=NOW()");
        $this->conn->query("UPDATE tickets t JOIN bills b ON b.bill_id=t.bill_id SET t.status='available', t.bill_id=NULL WHERE b.status='cancelled' AND t.status='booked'");
        $this->conn->query('DELETE FROM seat_holds WHERE expires_at<=NOW()');
        $this->conn->query("DELETE h FROM seat_holds h JOIN bills b ON b.bill_id=h.bill_id WHERE b.status<>'pending'");
    }
}
