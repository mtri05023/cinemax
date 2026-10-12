<?php

require_once __DIR__ . '/../models/Show.php';
require_once __DIR__ . '/../services/TicketService.php';

class ShowService
{
    private $showModel;
    private $ticketService;


    public function __construct($showModel, $ticketService)
    {
        $this->showModel = $showModel;
        $this->ticketService = $ticketService;
    }

    public function getAllShows()
    {
        return $this->showModel->getAllShows();
    }

    public function getShowById($id)
    {
        return $this->showModel->getShowById($id);
    }

    public function getShowsByMovieId($movie_id)
    {
        return $this->showModel->getShowsByMovieId($movie_id);
    }

    public function createShow($movie_id, $hall_id, $show_date, $start_time, $end_time, $base_price, ?int $cinemaId=null)
    {
        $this->assertHallActive((int)$hall_id,$cinemaId);
        $this->assertMovieAvailable((int)$movie_id);
        date_default_timezone_set('Asia/Ho_Chi_Minh');
        //Format thoi gian
        $show_date  = date('Y-m-d', strtotime($show_date));
        $start_time = date('H:i', strtotime($start_time));
        $end_time   = date('H:i', strtotime($end_time));


        $now = new DateTime();
        $showDateTime = new DateTime($show_date . ' ' . $start_time);

        if ($showDateTime <= $now) {
            throw new Exception("Không thể tạo suất chiếu trong quá khứ.");
        }

        if ($end_time < $start_time) {
            throw new Exception("End time must be greater than start time");
        }

        if (empty($movie_id) || empty($base_price) || empty($hall_id) || empty($show_date) || empty($start_time) || empty($end_time)) {
            throw new InvalidArgumentException("All fields are required to create a show.");
        }

        $conn = $this->showModel->getConnection();

        try {
            $conn->begin_transaction();
            $this->assertHallActive((int)$hall_id,$cinemaId,true);
            $this->assertMovieAvailable((int)$movie_id,true);

            $show_id = $this->showModel->createShow($movie_id, $hall_id, $show_date, $start_time, $end_time, $base_price);

            $this->ticketService->createTicket($show_id,false);

            $conn->commit();

            return $show_id;
        } catch (Throwable $e) {
            $conn->rollback();
            throw $e;
        }
    }

    public function updateShow($id, $movie_id, $hall_id, $show_date, $start_time, $end_time, $base_price, $status, ?int $cinemaId=null)
    {
        $existing=$this->showModel->getShowById($id);
        if (!$existing) throw new RuntimeException('Suất chiếu không tồn tại.');
        $this->assertHallActive((int)$existing['hall_id']);
        $this->assertHallActive((int)$hall_id,$cinemaId);
        $this->assertMovieAvailable((int)$movie_id);

        date_default_timezone_set('Asia/Ho_Chi_Minh');
        //Format thoi gian
        $show_date  = date('Y-m-d', strtotime($show_date));
        $start_time = date('H:i', strtotime($start_time));
        $end_time   = date('H:i', strtotime($end_time));


        // $now = new DateTime();
        // $showDateTime = new DateTime($show_date . ' ' . $start_time);

        // if ($showDateTime <= $now) {
        //     throw new Exception("Không thể cập nhật suất chiếu đã chiếu.");
        // }

        if ($end_time < $start_time) {
            throw new Exception("End time must be greater than start time");
        }

        if (empty($movie_id) || empty($base_price) || empty($hall_id) || empty($show_date) || empty($start_time) || empty($end_time)) {
            throw new InvalidArgumentException("All fields are required to create a show.");
        }

        return $this->showModel->updateShow($id, $movie_id, $hall_id, $show_date, $start_time, $end_time, $base_price, $status);
    }

    public function deleteShow($id)
    {
        $conn=$this->showModel->getConnection();
        $conn->begin_transaction();
        try {
            $stmt=$conn->prepare('SELECT status FROM shows WHERE show_id=? FOR UPDATE');
            $stmt->bind_param('i',$id); $stmt->execute(); $existing=$stmt->get_result()->fetch_assoc();
            if (!$existing) throw new RuntimeException('Suất chiếu không tồn tại.');
            if ((int)$existing['status']===-1) throw new RuntimeException('Không thể xóa suất chiếu đã kết thúc.');
            $tickets=$conn->prepare('SELECT t.ticket_id,t.status,t.bill_id,h.ticket_id AS held FROM tickets t LEFT JOIN seat_holds h ON h.ticket_id=t.ticket_id AND h.expires_at>NOW() WHERE t.show_id=? FOR UPDATE');
            $tickets->bind_param('i',$id); $tickets->execute();
            foreach ($tickets->get_result() as $ticket) {
                if ($ticket['status']!=='available' || $ticket['bill_id']!==null || $ticket['held']!==null) throw new RuntimeException('Không thể xóa lịch chiếu có vé đã đặt, đã thanh toán hoặc ghế đang được giữ.');
            }
            $delete=$conn->prepare('DELETE FROM tickets WHERE show_id=?');
            $delete->bind_param('i',$id); $delete->execute();
            $result=$this->showModel->deleteShow($id);
            $conn->commit(); return $result;
        } catch (Throwable $e) { $conn->rollback(); throw $e; }
    }

    //Ham lay ten rap va ten phong
    public function getAllHalls_CinemasByShow()
    {
        return $this->showModel->getAllHalls_CinemasByShow();
    }

    private function assertMovieAvailable(int $id, bool $lock=false): void
    {
        $stmt=$this->showModel->getConnection()->prepare('SELECT movie_id FROM movies WHERE movie_id=? AND status IN (1,2)'.($lock?' FOR UPDATE':''));
        $stmt->bind_param('i',$id); $stmt->execute();
        if (!$stmt->get_result()->fetch_assoc()) throw new RuntimeException('Phim đang ẩn hoặc ngừng khai thác. Vui lòng gia hạn bản quyền và kích hoạt lại trước khi tạo lịch chiếu.');
    }

    private function assertHallActive(int $id, ?int $cinemaId=null, bool $lock=false): void
    {
        if ($id<=0) throw new RuntimeException('Rạp chưa có phòng chiếu hợp lệ. Vui lòng tạo phòng trước khi thêm lịch chiếu.');
        $stmt=$this->showModel->getConnection()->prepare('SELECT h.cinema_id FROM halls h JOIN cinemas c ON c.cinema_id=h.cinema_id WHERE h.hall_id=? AND h.status=1 AND c.status=1'.($lock?' FOR UPDATE':''));
        $stmt->bind_param('i',$id); $stmt->execute();
        $hall=$stmt->get_result()->fetch_assoc();
        if (!$hall) throw new RuntimeException('Phòng không tồn tại hoặc rạp/phòng đã ngừng hoạt động. Vui lòng chọn phòng đang hoạt động.');
        if ($cinemaId!==null && (int)$hall['cinema_id']!==$cinemaId) throw new RuntimeException('Phòng chiếu không thuộc rạp đang chọn.');
    }

    public function getTicketByShowId($show_id)
    {
        if (empty($show_id)) {
            throw new InvalidArgumentException("Show ID không hợp lệ.");
        }
        return $this->ticketService->getTicketByShowId($show_id);
    }
}
