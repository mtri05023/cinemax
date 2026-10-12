<?php
require_once __DIR__.'/../services/SeatSelectionRules.php';
$seats=[];
for ($i=1;$i<=7;$i++) $seats[]=['ticket_id'=>$i,'row_name'=>'A','seat_number'=>$i,'available'=>true];
$checks=0;
$check=function(array $rows,array $ids,array $expected)use(&$checks){
    $actual=SeatSelectionRules::isolatedSeats($rows,$ids);sort($actual);sort($expected);
    if ($actual!==$expected) throw new RuntimeException(json_encode(['ids'=>$ids,'expected'=>$expected,'actual'=>$actual]));
    $checks++;
};
$check($seats,[2],['A1']);
$check($seats,[6],['A7']);
$check($seats,[1],[]);
$check($seats,[4],[]);
$check($seats,[1,3],['A2']);
$check($seats,[2,3,4,5,6],['A1','A7']);
$check($seats,range(1,7),[]);
$blocked=$seats;$blocked[0]['available']=false;
$check($blocked,[3],['A2']);
$check($blocked,[2],[]);
$preexisting=$seats;$preexisting[1]['available']=false;
$check($preexisting,[5],[]); // A1 was already isolated; do not block another group.
$gap=$seats;unset($gap[2]);
$check(array_values($gap),[2],['A1']);
$check(array_values($gap),[5],['A4']);
$second=$seats;foreach($second as &$seat){$seat['row_name']='B';$seat['ticket_id']+=7;}unset($seat);
$check(array_merge($seats,$second),[2,11],['A1']);
echo "PASS: $checks seat adjacency rule checks\n";
