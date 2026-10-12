<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__.'/../config/dbConfig.php';
require_once __DIR__.'/../models/Movie.php';
require_once __DIR__.'/../models/Genre.php';
require_once __DIR__.'/../services/MovieService.php';
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
$db=getDBConnection();
$catalog=json_decode(file_get_contents(__DIR__.'/movies_q4_2026.json'),true,512,JSON_THROW_ON_ERROR);
$imported=json_decode(file_get_contents(__DIR__.'/research-2026-q4/import-result.json'),true,512,JSON_THROW_ON_ERROR);
$before=json_decode(file_get_contents(__DIR__.'/research-2026-q4/before-import.json'),true,512,JSON_THROW_ON_ERROR);
$model=new Movie($db);
$genreModel=new Genre($db);
$movieService=new MovieService($model,$genreModel);
$assert=function($ok,$message) { if (!$ok) throw new RuntimeException($message); };
$nowIds=array_column($movieService->listMoviesForUser('now-showing'),'movie_id');
$soonIds=array_column($movieService->listMoviesForUser('coming-soon'),'movie_id');
$adminIds=array_column($movieService->listMoviesAdmin(),'movie_id');
$today=$db->query('SELECT CURDATE()')->fetch_row()[0];
$movies=$db->query('SELECT * FROM movies')->fetch_all(MYSQLI_ASSOC);
$titles=[];
foreach ($movies as $row) {
    $key=mb_strtolower(trim($row['title']),'UTF-8');
    $assert(!isset($titles[$key]),'Duplicate title: '.$row['title']);
    $titles[$key]=$row;
}
$stmt=$db->prepare('SELECT g.name FROM movie_genres mg JOIN genres g ON g.genre_id=mg.genre_id WHERE mg.movie_id=? ORDER BY g.name');
$posterCount=0; $trailerCount=0;
foreach ($catalog as $m) {
    $file=__DIR__.'/../public/'.$m['poster_url'];
    $image=@getimagesize($file);
    $assert($image && in_array($image['mime'],['image/jpeg','image/png','image/webp'],true),'Invalid image');
    $assert($image[0]===$m['poster_width'] && $image[1]===$m['poster_height'],'Wrong image dimensions');
    $assert(hash_file('sha256',$file)===$m['poster_sha256'],'Changed poster');
    $posterCount++;
    $assert($m['trailer_verified'] && $m['trailer_channel']!=='' && $m['trailer_title']!=='','Unverified trailer');
    $trailerCount++;
    if (!$m['import_ready']) {
        $assert(!isset($titles[mb_strtolower($m['title'],'UTF-8')]),'Pending movie was inserted');
        continue;
    }
    $row=$titles[mb_strtolower($m['title'],'UTF-8')]??null;
    $assert($row!==null,'Movie missing');
    foreach (['title','director','actors','description','release_date','poster_url','trailer_url'] as $field) $assert($row[$field]===$m[$field],'Wrong '.$field.': '.$m['title']);
    $assert((int)$row['duration_min']===$m['duration_min'],'Wrong runtime');
    $expected=$m['release_date']>$today?1:2;
    $id=(int)$row['movie_id'];
    $assert((int)$row['status']===$expected,'Wrong status');
    $assert(in_array($id,$expected===1?$soonIds:$nowIds),'Wrong user classification');
    $assert(in_array($id,$adminIds),'Missing admin movie');
    $detail=$movieService->getMovieDetailForUser($id);
    $assert($detail && $detail['trailer_url']===$m['trailer_url'],'Detail trailer mismatch');
    $stmt->bind_param('i',$id); $stmt->execute();
    $actual=array_column($stmt->get_result()->fetch_all(MYSQLI_ASSOC),'name');
    $expectedGenres=$m['genres']; sort($actual); sort($expectedGenres);
    $assert($actual===$expectedGenres,'Wrong genres');
}
foreach ($before['movies'] as $old) {
    $actual=$titles[mb_strtolower(trim($old['title']),'UTF-8')]??null;
    $assert($actual===$old,'Existing movie changed: '.$old['title']);
}
foreach ($before['counts'] as $table=>$count) $assert((int)$db->query("SELECT COUNT(*) FROM `$table`")->fetch_row()[0]===$count,'Unrelated table count changed: '.$table);
foreach ($before['genres'] as $old) {
    $id=(int)$old['genre_id'];
    $assert($db->query('SELECT * FROM genres WHERE genre_id='.$id)->fetch_assoc()===$old,'Existing genre changed');
}
foreach ($before['movie_genres'] as $old) {
    $mid=(int)$old['movie_id']; $gid=(int)$old['genre_id'];
    $assert($db->query("SELECT 1 FROM movie_genres WHERE movie_id=$mid AND genre_id=$gid")->num_rows===1,'Existing genre link missing');
}
foreach ($before['movie_images'] as $old) {
    $id=(int)$old['image_id'];
    $assert($db->query('SELECT * FROM movie_images WHERE image_id='.$id)->fetch_assoc()===$old,'Existing movie image changed');
}
if (in_array('--render-admin',$argv,true)) {
    // Template fixture only: no persistent login session is created.
    $_SESSION['user']=['role_id'=>1]; $_SESSION['user_role_name']='admin';
    require_once __DIR__.'/../config/permissionConfig.php';
    ob_start(); require __DIR__.'/../views/admin/pages/movies.php'; $html=ob_get_clean();
    $assert(!str_contains($html,'Fatal error'),'Admin rendering error');
    $doc=new DOMDocument(); libxml_use_internal_errors(true); $doc->loadHTML('<?xml encoding="UTF-8">'.$html); libxml_clear_errors();
    $xpath=new DOMXPath($doc);
    $buttons=$xpath->query('//button[contains(@onclick,"openEditMovieModal(")]');
    $assert($buttons->length>=count($imported),'Missing edit buttons');
    foreach (['edit_duration_min','edit_trailer_url','edit_poster','edit_existing_poster_url'] as $id) {
        // Poster upload has an alternate ID in some versions; check by name below.
        if ($id==='edit_poster') continue;
        $assert($xpath->query('//*[@id="'.$id.'"]')->length===1,'Missing edit field '.$id);
    }
    $assert($xpath->query('//form[contains(@action,"action=update")]//input[@name="poster" and @type="file"]')->length===1,'Missing poster replacement field');
    file_put_contents(__DIR__.'/research-2026-q4/admin-render.html',$html);
    $js=[];
    foreach ($xpath->query('//script[not(@src)]') as $script) $js[]=$script->textContent;
    foreach ($buttons as $button) $js[]=$button->getAttribute('onclick');
    file_put_contents(__DIR__.'/research-2026-q4/admin-js.json',json_encode($js,JSON_THROW_ON_ERROR));
}
$result=['date'=>$today,'imported'=>count($imported),'october'=>count(array_filter($imported,function($i)use($titles){ return str_starts_with($titles[mb_strtolower($i['title'],'UTF-8')]['release_date'],'2026-10'); })), 'poster_files_valid'=>$posterCount,'official_trailers_verified'=>$trailerCount,'pending'=>count(array_filter($catalog,fn($m)=>!$m['import_ready'])),'preserved_old_movies'=>count($before['movies']),'checks'=>['database_fields','genres','duplicates','statuses','user_model_filters','movie_details','admin_model_list','poster_dimensions_and_hashes','old_movies_genres_links_images','unrelated_table_counts']];
file_put_contents(__DIR__.'/research-2026-q4/verification.json',json_encode($result,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR));
echo json_encode($result,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT)."\n";
