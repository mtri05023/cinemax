<?php
class SeatSelectionRules
{
    public static function isolatedSeats(array $seats, array $selectedIds): array
    {
        $selected=array_fill_keys(array_map('intval',$selectedIds),true);
        $rows=[];
        foreach ($seats as $seat) $rows[(string)$seat['row_name']][(int)$seat['seat_number']]=$seat;
        $isolated=[];
        foreach ($rows as $row) {
            foreach ($row as $number=>$seat) {
                if (!$seat['available'] || isset($selected[(int)$seat['ticket_id']])) continue;
                $freeNeighbor=false; $selectedNeighbor=false;
                foreach ([$number-1,$number+1] as $neighborNumber) {
                    $neighbor=$row[$neighborNumber]??null;
                    if (!$neighbor) continue;
                    if (isset($selected[(int)$neighbor['ticket_id']])) $selectedNeighbor=true;
                    elseif ($neighbor['available']) $freeNeighbor=true;
                }
                if ($selectedNeighbor && !$freeNeighbor) $isolated[]=(string)$seat['row_name'].$number;
            }
        }
        return $isolated;
    }
}
