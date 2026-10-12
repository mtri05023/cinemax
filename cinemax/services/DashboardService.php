<?php
class DashboardService
{
    public function __construct(private mysqli $conn) {}

    public static function period(array $input): array
    {
        foreach (['period','date','month','from','to'] as $key) {
            if (isset($input[$key]) && !is_string($input[$key])) throw new InvalidArgumentException('Bộ lọc thống kê không hợp lệ.');
        }
        $mode=(string)($input['period']??'month');
        $date=(string)($input['date']??date('Y-m-d'));
        $month=(string)($input['month']??date('Y-m'));
        $from=(string)($input['from']??date('Y-m-01'));
        $to=(string)($input['to']??date('Y-m-d'));
        $parse=function(string $value): DateTimeImmutable {
            $parsed=DateTimeImmutable::createFromFormat('!Y-m-d',$value);
            if (!$parsed || $parsed->format('Y-m-d')!==$value) throw new InvalidArgumentException('Ngày thống kê không hợp lệ.');
            return $parsed;
        };
        if ($mode==='day') { $start=$parse($date); $end=$start; $label='Ngày '.$start->format('d/m/Y'); }
        elseif ($mode==='month') {
            if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/',$month)) throw new InvalidArgumentException('Tháng thống kê không hợp lệ.');
            $start=$parse($month.'-01'); $end=$start->modify('last day of this month'); $label='Tháng '.$start->format('m/Y');
        } elseif ($mode==='range') {
            $start=$parse($from); $end=$parse($to);
            if ($end<$start) throw new InvalidArgumentException('Ngày kết thúc phải từ ngày bắt đầu trở đi.');
            $label=$start->format('d/m/Y').' – '.$end->format('d/m/Y');
        } else throw new InvalidArgumentException('Kiểu thống kê không hợp lệ.');
        return ['mode'=>$mode,'date'=>$date,'month'=>$month,'from'=>$from,'to'=>$to,'start'=>$start->format('Y-m-d 00:00:00'),'end'=>$end->modify('+1 day')->format('Y-m-d 00:00:00'),'label'=>$label,'monthly'=>$start->diff($end)->days>62];
    }

    private function query(string $sql,array $period): array
    {
        $stmt=$this->conn->prepare($sql); $stmt->bind_param('ss',$period['start'],$period['end']); $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function details(array $period, string $metric, int $page = 1): array
    {
        $time='COALESCE(b.paid_at,b.created_at)';
        if ($metric==='members') {
            $source='FROM users u WHERE u.created_at>=? AND u.created_at<?';
            $columns='u.user_id AS id,u.full_name,u.email,u.phone,u.created_at AS event_time';
            $order='u.created_at DESC,u.user_id DESC';
        } elseif ($metric==='tickets') {
            $source="FROM tickets t JOIN bills b ON b.bill_id=t.bill_id JOIN users u ON u.user_id=b.user_id LEFT JOIN shows sh ON sh.show_id=t.show_id LEFT JOIN movies m ON m.movie_id=sh.movie_id LEFT JOIN halls h ON h.hall_id=sh.hall_id LEFT JOIN cinemas c ON c.cinema_id=h.cinema_id LEFT JOIN seats s ON s.seat_id=t.seat_id WHERE b.status='paid' AND $time>=? AND $time<?";
            $columns="t.ticket_id AS id,b.bill_id,u.full_name,m.title,c.name AS cinema,h.name AS hall,CONCAT(s.row_name,s.seat_number) AS seat,t.price,$time AS event_time";
            $order="$time DESC,t.ticket_id DESC";
        } elseif (in_array($metric,['revenue','orders'],true)) {
            $source="FROM bills b JOIN users u ON u.user_id=b.user_id WHERE b.status='paid' AND $time>=? AND $time<?";
            $columns="b.bill_id AS id,u.full_name,b.total_tickets,b.final_amount,$time AS event_time";
            $order="$time DESC,b.bill_id DESC";
        } else throw new InvalidArgumentException('Mục thống kê không hợp lệ.');
        $total=(int)$this->query("SELECT COUNT(*) AS total $source",$period)[0]['total'];
        $pages=max(1,(int)ceil($total/10)); $page=max(1,min($page,$pages)); $offset=($page-1)*10;
        return ['rows'=>$this->query("SELECT $columns $source ORDER BY $order LIMIT 10 OFFSET $offset",$period),'total'=>$total,'pages'=>$pages,'page'=>$page];
    }

    public function report(array $period): array
    {
        $time='COALESCE(b.paid_at,b.created_at)';
        $where="b.status='paid' AND $time>=? AND $time<?";
        $summary=$this->query("SELECT COALESCE(SUM(b.final_amount),0) AS revenue,COUNT(*) AS orders,COALESCE(SUM(b.total_tickets),0) AS tickets FROM bills b WHERE $where",$period)[0];
        $newUsers=$this->query('SELECT COUNT(*) AS count FROM users WHERE created_at>=? AND created_at<?',$period)[0]['count'];
        // Attribute bill revenue once, split across its ticket locations. Movie
        // revenue excludes combos and takes its share of the order discount.
        $net="t.price * CASE WHEN b.total_amount>0 THEN LEAST(1,GREATEST(0,b.final_amount/b.total_amount)) ELSE 0 END";
        $billShare="CASE WHEN bt.gross>0 THEN b.final_amount*t.price/bt.gross ELSE b.final_amount/bt.ticket_count END";
        $join="FROM tickets t JOIN bills b ON b.bill_id=t.bill_id JOIN (SELECT bill_id,SUM(price) AS gross,COUNT(*) AS ticket_count FROM tickets WHERE bill_id IS NOT NULL GROUP BY bill_id) bt ON bt.bill_id=b.bill_id JOIN shows sh ON sh.show_id=t.show_id JOIN halls h ON h.hall_id=sh.hall_id JOIN cinemas c ON c.cinema_id=h.cinema_id JOIN movies m ON m.movie_id=sh.movie_id WHERE $where";
        $cinemas=$this->query("SELECT c.cinema_id AS id,c.name AS name,COUNT(*) AS tickets,SUM($billShare) AS revenue $join GROUP BY c.cinema_id,c.name ORDER BY revenue DESC,tickets DESC,c.cinema_id LIMIT 5",$period);
        $halls=$this->query("SELECT h.hall_id AS id,h.name AS name,c.name AS cinema_name,COUNT(*) AS tickets,SUM($billShare) AS revenue $join GROUP BY h.hall_id,h.name,c.name ORDER BY revenue DESC,tickets DESC,h.hall_id LIMIT 5",$period);
        $movies=$this->query("SELECT m.movie_id AS MovieID,m.title AS Title,m.poster_url,COUNT(*) AS TicketsSold,SUM($net) AS Revenue $join GROUP BY m.movie_id,m.title,m.poster_url ORDER BY Revenue DESC,TicketsSold DESC,m.movie_id LIMIT 5",$period);
        $hourly=$period['mode']==='day';
        $format=$hourly?'%Y-%m-%d %H':($period['monthly']?'%Y-%m':'%Y-%m-%d');
        $series=$this->query("SELECT DATE_FORMAT($time,'$format') AS bucket,SUM(b.final_amount) AS revenue FROM bills b WHERE $where GROUP BY bucket ORDER BY bucket",$period);
        $seriesMap=array_column($series,'revenue','bucket'); $labels=[]; $values=[];
        $cursor=new DateTimeImmutable($period['start']); $end=new DateTimeImmutable($period['end']);
        if ($period['monthly']) $cursor=$cursor->modify('first day of this month');
        while ($cursor<$end) {
            $key=$cursor->format($hourly?'Y-m-d H':($period['monthly']?'Y-m':'Y-m-d'));
            $labels[]=$cursor->format($hourly?'H:00':($period['monthly']?'m/Y':'d/m')); $values[]=(float)($seriesMap[$key]??0);
            $cursor=$cursor->modify($hourly?'+1 hour':($period['monthly']?'+1 month':'+1 day'));
        }
        $orders=$this->query("SELECT b.bill_id,u.full_name,b.final_amount,b.status,$time AS created_at FROM bills b JOIN users u ON u.user_id=b.user_id WHERE $where ORDER BY $time DESC,b.bill_id DESC LIMIT 5",$period);
        return ['summary'=>$summary,'newUsers'=>(int)$newUsers,'cinemas'=>$cinemas,'halls'=>$halls,'movies'=>$movies,'labels'=>$labels,'values'=>$values,'orders'=>$orders];
    }
}
