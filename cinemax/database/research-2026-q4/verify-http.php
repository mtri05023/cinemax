<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$catalog=json_decode(file_get_contents(__DIR__.'/../movies_q4_2026.json'),true,512,JSON_THROW_ON_ERROR);
$imported=json_decode(file_get_contents(__DIR__.'/import-result.json'),true,512,JSON_THROW_ON_ERROR);
$base='http://localhost/cinemax/cinemax';
$get=function($url) {
    $curl=curl_init($url);
    curl_setopt_array($curl,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>5,CURLOPT_TIMEOUT=>15]);
    $body=curl_exec($curl); $status=curl_getinfo($curl,CURLINFO_HTTP_CODE); $type=curl_getinfo($curl,CURLINFO_CONTENT_TYPE); $error=curl_error($curl); curl_close($curl);
    if ($body===false || $status!==200) throw new RuntimeException("HTTP $status: $url $error");
    return [$body,$type];
};
$assert=function($ok,$message) { if (!$ok) throw new RuntimeException($message); };
$now=$get($base.'/views/user/index.php?page=movies&filter=now-showing')[0];
$soon=$get($base.'/views/user/index.php?page=movies&filter=coming-soon')[0];
$home=$get($base.'/views/user/index.php')[0];
foreach ([$now,$soon,$home] as $html) $assert(!preg_match('/(?:Fatal error|Warning|Parse error):/',$html),'PHP error in user page');
$posterCount=0; $details=0;
foreach ($catalog as $m) {
    [$body,$type]=$get($base.'/public/'.$m['poster_url']);
    $assert(str_starts_with($type,'image/'),'Poster served as non-image');
    $assert(hash('sha256',$body)===$m['poster_sha256'],'HTTP poster differs from local poster');
    $posterCount++;
    if (!$m['import_ready']) continue;
    $record=null;
    foreach ($imported as $i) if ($i['title']===$m['title']) $record=$i;
    $assert($record!==null,'Missing import result');
    $id=$record['movie_id'];
    $html=$get($base.'/views/user/index.php?page=movie_detail&id='.$id)[0];
    $assert(!preg_match('/(?:Fatal error|Warning|Parse error):/',$html),'PHP error in detail page');
    $assert(str_contains($html,htmlspecialchars($m['title'])),'Detail missing movie title');
    preg_match('/[?&]v=([A-Za-z0-9_-]{11})/',$m['trailer_url'],$video);
    $assert(str_contains($html,'https://www.youtube.com/embed/'.$video[1]),'Wrong trailer iframe');
    $assert(str_contains($html,$m['poster_url']),'Wrong detail poster');
    $assert(str_contains($html,date('d/m/Y',strtotime($m['release_date']))),'Wrong detail date');
    $list=$record['status']===1?$soon:$now;
    $other=$record['status']===1?$now:$soon;
    $assert(str_contains($list,htmlspecialchars($m['title'])),'Movie missing from correct user filter');
    $assert(!str_contains($other,htmlspecialchars($m['title'])),'Movie in wrong filter');
    $details++;
}
$result=['http_posters_200_and_valid'=>$posterCount,'movie_detail_pages_200'=>$details,'trailer_iframes_correct'=>$details,'user_list_and_home_pages_200'=>3,'browser_playback'=>'Not tested: no interactive browser available.','admin_template'=>'Verified separately; authenticated browser editing/upload not tested.'];
file_put_contents(__DIR__.'/http-verification.json',json_encode($result,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR));
echo json_encode($result,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)."\n";
