<?php
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
require_once __DIR__.'/../../config/dbConfig.php';
require_once __DIR__.'/../../config/JwtHelper.php';
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
$db=getDBConnection(); $path=__DIR__.'/fixture.json'; $mode=$argv[1]??'';
if ($mode==='create') {
    if (is_file($path)) throw new RuntimeException('Clean up previous browser fixtures first.');
    $tag='E2E'.bin2hex(random_bytes(5)); $data=['tag'=>$tag,'created'=>[]];
    $save=function()use(&$data,$path) { file_put_contents($path,json_encode($data,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE),LOCK_EX); };
    $insert=function(string $table,array $values)use($db,&$data,$save) {
        $sql='INSERT INTO `'.$table.'` (`'.implode('`,`',array_keys($values)).'`) VALUES ('.implode(',',array_fill(0,count($values),'?')).')';
        $stmt=$db->prepare($sql); $params=array_values($values); $stmt->bind_param(str_repeat('s',count($params)),...$params); $stmt->execute(); $id=(int)$db->insert_id;
        $data['created'][$table][]=$id; $save(); return $id;
    };
    $location=(int)$db->query('SELECT location_id FROM locations LIMIT 1')->fetch_row()[0];
    $seatType=(int)$db->query('SELECT seat_type_id FROM seat_types WHERE status=1 LIMIT 1')->fetch_row()[0];
    $movie=(int)$db->query('SELECT movie_id FROM movies WHERE status=2 LIMIT 1')->fetch_row()[0];
    $roles=[]; foreach ($db->query('SELECT role_id,LOWER(role_name) AS name FROM roles') as $role) $roles[$role['name']]=(int)$role['role_id'];
    foreach (['admin','a','b'] as $who) {
        $roleId=$roles[$who==='admin'?'admin':'user'];
        $fullName=$tag.' '.$who;
        $id=$insert('users',['role_id'=>$roleId,'full_name'=>$fullName,'email'=>strtolower($tag.$who).'@example.invalid','password_hash'=>password_hash(bin2hex(random_bytes(32)),PASSWORD_DEFAULT),'status'=>1]);
        $data['actors'][$who]=['id'=>$id,'token'=>JwtHelper::encode(['user_id'=>$id,'role_id'=>$roleId,'full_name'=>$fullName,'email'=>strtolower($tag.$who).'@example.invalid'],1800)]; $save();
    }
    $cinema=$insert('cinemas',['name'=>$tag.' Rạp "Đêm" O\'Brien','address'=>$tag.' 123 Nguyễn Du','location_id'=>$location,'status'=>1]);
    $closedCinema=$insert('cinemas',['name'=>$tag.' Rạp đóng','address'=>$tag.' Địa chỉ cũ','location_id'=>$location,'status'=>0]);
    $emptyCinema=$insert('cinemas',['name'=>$tag.' Rạp chưa có phòng','address'=>$tag.' Empty','location_id'=>$location,'status'=>1]);
    $hall=$insert('halls',['cinema_id'=>$cinema,'name'=>$tag.' Phòng "Một"','total_seats'=>12,'status'=>1]);
    $closedHall=$insert('halls',['cinema_id'=>$cinema,'name'=>$tag.' Phòng đóng','total_seats'=>1,'status'=>0]);
    $tomorrow=date('Y-m-d',strtotime('+1 day'));
    $show=$insert('shows',['movie_id'=>$movie,'hall_id'=>$hall,'show_date'=>$tomorrow,'start_time'=>'12:00:00','end_time'=>'15:00:00','base_price'=>50000,'status'=>1]);
    $ticketIds=[];
    for ($number=1;$number<=12;$number++) {
        $seat=$insert('seats',['hall_id'=>$hall,'seat_type_id'=>$seatType,'row_name'=>'A','seat_number'=>$number,'status'=>1]);
        $ticketIds[]=$insert('tickets',['show_id'=>$show,'seat_id'=>$seat,'price'=>50000,'status'=>'available']);
    }
    $promoCode=$tag.'NEW';
    $promo=$insert('promotions',['code'=>$promoCode,'name'=>$tag.' Ưu đãi mới','discount_type'=>'percent','discount_value'=>20,'discount_percent'=>20,'start_date'=>date('Y-m-d').' 00:00:00','end_date'=>date('Y-m-d',strtotime('+2 days')).' 23:59:59','min_amount'=>0,'usage_limit'=>20,'audience'=>'new_member','per_user_limit'=>1,'status'=>1]);
    $insert('permissions',['permission_code'=>$tag.'_update','description'=>$tag.' quyền kiểm thử','status'=>1]);
    $generalCode=$tag.'ALL'; $minimumCode=$tag.'MIN';
    foreach ([$generalCode=>0,$minimumCode=>100000] as $code=>$minimum) $insert('promotions',['code'=>$code,'name'=>$tag.' General '.$minimum,'discount_type'=>'fixed','discount_value'=>5000,'discount_percent'=>0,'start_date'=>date('Y-m-d').' 00:00:00','end_date'=>date('Y-m-d',strtotime('+2 days')).' 23:59:59','min_amount'=>$minimum,'usage_limit'=>20,'audience'=>'all','per_user_limit'=>0,'status'=>1]);
    $data+=['cinema_id'=>$cinema,'closed_cinema_id'=>$closedCinema,'empty_cinema_id'=>$emptyCinema,'hall_id'=>$hall,'closed_hall_id'=>$closedHall,'show_id'=>$show,'movie_id'=>$movie,'ticket_ids'=>$ticketIds,'promo_code'=>$promoCode,'promo_id'=>$promo,'general_code'=>$generalCode,'minimum_code'=>$minimumCode,'tomorrow'=>$tomorrow]; $save();
    echo "Created temporary browser fixtures.\n"; exit;
}
if (!is_file($path)) throw new RuntimeException('No browser fixtures.');
$data=json_decode(file_get_contents($path),true,512,JSON_THROW_ON_ERROR);
$tag=$db->real_escape_string($data['tag']);
if (!preg_match('/^E2E[a-f0-9]{10}$/',$data['tag'])) throw new RuntimeException('Invalid fixture marker.');
$ids=function($table)use(&$data){return implode(',',array_map('intval',$data['created'][$table]??[]))?:'0';};
$users=$ids('users');
if ($mode==='refresh-auth') {
    foreach ($data['actors'] as &$actor) {
        $id=(int)$actor['id'];
        $user=$db->query('SELECT user_id,role_id,full_name,email FROM users WHERE user_id='.$id)->fetch_assoc();
        if (!$user || !str_starts_with($user['full_name'],$data['tag'])) throw new RuntimeException('Fixture actor missing.');
        $user['user_id']=(int)$user['user_id']; $user['role_id']=(int)$user['role_id'];
        $actor['token']=JwtHelper::encode($user,1800);
    }
    unset($actor);
    file_put_contents($path,json_encode($data,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE),LOCK_EX);
    echo "Fixture authentication refreshed.\n"; exit;
}
if ($mode==='mark-existing-member') {
    $uid=(int)$data['actors']['b']['id'];
    $stmt=$db->prepare("INSERT INTO bills(user_id,total_tickets,total_amount,discount_amount,final_amount,status,paid_at) VALUES(?,0,0,0,0,'paid',NOW())");
    $stmt->bind_param('i',$uid); $stmt->execute();
    echo "Temporary customer marked as existing member.\n"; exit;
}
if ($mode==='create-license-movie') {
    $title=$data['tag'].' Phim bản quyền';
    $stmt=$db->prepare("INSERT INTO movies(title,director,duration_min,release_date,status) VALUES(?,'Test director',90,CURDATE(),2)");
    $stmt->bind_param('s',$title); $stmt->execute(); $movieId=(int)$db->insert_id;
    $data['created']['movies'][]=$movieId; $data['movie_id']=$movieId;
    file_put_contents($path,json_encode($data,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE),LOCK_EX);
    $db->query('UPDATE shows SET movie_id='.$movieId.' WHERE show_id IN ('.$ids('shows').') AND hall_id IN ('.$ids('halls').')');
    echo "Temporary licensed movie created.\n"; exit;
}
if ($mode==='dashboard-facts') {
    $db->begin_transaction();
    try {
        $bill=$db->prepare("INSERT INTO bills(user_id,total_tickets,total_amount,final_amount,status,created_at,paid_at) VALUES(?,?,?,?,'paid','2004-01-31 10:00:00',?)");
        $assign=$db->prepare("UPDATE tickets SET bill_id=?,status='paid' WHERE ticket_id=? AND show_id=?");
        foreach ([['actor'=>'a','ids'=>[$data['ticket_ids'][0]],'paid'=>'2004-02-10 10:00:00'],['actor'=>'b','ids'=>[$data['ticket_ids'][1],$data['ticket_ids'][2]],'paid'=>'2004-02-11 10:00:00']] as $item) {
            $uid=(int)$data['actors'][$item['actor']]['id']; $count=count($item['ids']); $total=50000*$count; $paid=$item['paid'];
            $bill->bind_param('iidds',$uid,$count,$total,$total,$paid); $bill->execute(); $billId=(int)$db->insert_id; $show=(int)$data['show_id'];
            foreach ($item['ids'] as $ticketId) { $assign->bind_param('iii',$billId,$ticketId,$show); $assign->execute(); }
        }
        $db->commit(); echo "Temporary dashboard payments created.\n"; exit;
    } catch (Throwable $e) { $db->rollback(); throw $e; }
}
if ($mode==='expire') {
    $user=(int)($argv[2]??$data['actors']['a']['id']);
    if (!in_array($user,$data['created']['users'],true)) throw new RuntimeException('Not a fixture user.');
    $stmt=$db->prepare('UPDATE seat_holds SET expires_at=DATE_SUB(NOW(),INTERVAL 1 SECOND) WHERE user_id=?'); $stmt->bind_param('i',$user); $stmt->execute();
    $stmt=$db->prepare("UPDATE bills SET payment_expires_at=DATE_SUB(NOW(),INTERVAL 1 SECOND) WHERE user_id=? AND status='pending'"); $stmt->bind_param('i',$user); $stmt->execute();
    echo "Expired fixture checkout only.\n"; exit;
}
if ($mode==='inspect') {
    $tickets=$db->query('SELECT ticket_id,status,bill_id FROM tickets WHERE show_id IN ('.$ids('shows').')')->fetch_all(MYSQLI_ASSOC);
    $bills=$db->query("SELECT bill_id,user_id,status,total_amount,discount_amount,payment_expires_at FROM bills WHERE user_id IN ($users)")->fetch_all(MYSQLI_ASSOC);
    echo json_encode(['tickets'=>$tickets,'bills'=>$bills],JSON_UNESCAPED_UNICODE); exit;
}
if ($mode==='auth-debug') {
    $actor=$data['actors']['admin'];
    $decoded=JwtHelper::decode($actor['token']);
    $curl=curl_init('http://localhost/cinemax/cinemax/views/admin/index.php?page=halls&cinema_id='.$data['closed_cinema_id']);
    curl_setopt_array($curl,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_HEADER=>true,CURLOPT_COOKIE=>'cinemax_token='.$actor['token']]);
    $body=curl_exec($curl); $status=curl_getinfo($curl,CURLINFO_HTTP_CODE); curl_close($curl);
    preg_match('/Location: ([^\r\n]+)/i',$body,$location);
    echo json_encode(['jwt_valid'=>$decoded!==null,'actor_id'=>$decoded['user_id']??null,'status'=>$status,'location'=>$location[1]??null,'fatal'=>str_contains($body,'Fatal error')]); exit;
}
if ($mode==='cleanup') {
    foreach (['users'=>'full_name','cinemas'=>'name','halls'=>'name','promotions'=>'code','permissions'=>'permission_code','movies'=>'title'] as $table=>$column) {
        $primary=['users'=>'user_id','cinemas'=>'cinema_id','halls'=>'hall_id','promotions'=>'promotion_id','permissions'=>'permission_id','movies'=>'movie_id'][$table];
        $unexpected=$db->query("SELECT 1 FROM `$table` WHERE `$primary` IN (".$ids($table).") AND `$column` NOT LIKE '$tag%' LIMIT 1");
        if ($unexpected->num_rows) throw new RuntimeException('Fixture marker changed; refusing cleanup.');
    }
    // Also include schedules created through the UI inside verified fixture halls.
    $fixtureShows=$db->query('SELECT show_id FROM shows WHERE hall_id IN ('.$ids('halls').')')->fetch_all(MYSQLI_ASSOC);
    $data['created']['shows']=array_values(array_unique(array_merge($data['created']['shows']??[],array_column($fixtureShows,'show_id'))));
    $foreign=$db->query("SELECT 1 FROM tickets t JOIN bills b ON b.bill_id=t.bill_id WHERE t.show_id IN (".$ids('shows').") AND b.user_id NOT IN ($users) LIMIT 1");
    if ($foreign->num_rows) throw new RuntimeException('Another user booked a fixture show; refusing cleanup.');
    $db->begin_transaction();
    try {
        $db->query("DELETE bc FROM bill_combos bc JOIN bills b ON b.bill_id=bc.bill_id WHERE b.user_id IN ($users)");
        $db->query("DELETE FROM seat_holds WHERE user_id IN ($users)");
        $db->query('DELETE FROM tickets WHERE show_id IN ('.$ids('shows').')');
        $db->query("DELETE FROM bills WHERE user_id IN ($users)");
        $db->query('DELETE FROM movie_genres WHERE movie_id IN ('.$ids('movies').')');
        foreach (['shows'=>'show_id','seats'=>'seat_id','halls'=>'hall_id','cinemas'=>'cinema_id','promotions'=>'promotion_id','permissions'=>'permission_id','users'=>'user_id','movies'=>'movie_id'] as $table=>$primary) $db->query("DELETE FROM `$table` WHERE `$primary` IN (".$ids($table).')');
        $db->commit(); unlink($path); echo "Temporary fixtures and tokens removed.\n";
    } catch (Throwable $e) { $db->rollback(); throw $e; }
    exit;
}
throw new RuntimeException('Unknown fixture mode.');
