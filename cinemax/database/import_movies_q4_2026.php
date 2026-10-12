<?php
// Run with XAMPP PHP from CLI. No HTTP endpoint, no schema or showtime writes.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../config/dbConfig.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$db = getDBConnection();
$catalog = json_decode(file_get_contents(__DIR__.'/movies_q4_2026.json'), true, 512, JSON_THROW_ON_ERROR);
$apply = in_array('--apply', $argv, true);
$export = in_array('--export-sql', $argv, true);
if ($apply && $export) throw new RuntimeException('Use --apply or --export-sql separately.');
$schema = $db->query('SHOW COLUMNS FROM movies')->fetch_all(MYSQLI_ASSOC);
$columns = array_column($schema, 'Field');
foreach (['movie_id','title','director','actors','description','duration_min','release_date','poster_url','trailer_url','status'] as $column) {
    if (!in_array($column,$columns,true)) throw new RuntimeException('Missing movies column: '.$column);
}
if (!str_contains($schema[array_search('movie_id',$columns)]['Extra'],'auto_increment')) throw new RuntimeException('Expected AUTO_INCREMENT movie_id.');
$existing = $db->query('SELECT movie_id,title FROM movies')->fetch_all(MYSQLI_ASSOC);
$normalize = fn($s)=>mb_strtolower(preg_replace('/\s+/u',' ',trim($s)), 'UTF-8');
$known = [];
foreach ($existing as $row) $known[$normalize($row['title'])] = (int)$row['movie_id'];
$ready = []; $pending = []; $skipped = [];
foreach ($catalog as $m) {
    if (!$m['import_ready']) { $pending[]=$m['title']; continue; }
    if (!is_int($m['duration_min']) || $m['duration_min']<=0) throw new RuntimeException('Unverified runtime: '.$m['title']);
    if (!$m['trailer_verified'] || !preg_match('~^https://www\.youtube\.com/watch\?v=[A-Za-z0-9_-]{11}$~',$m['trailer_url'])) throw new RuntimeException('Invalid trailer.');
    $date = DateTimeImmutable::createFromFormat('!Y-m-d',$m['release_date']);
    if (!$date || $date->format('Y-m-d')!==$m['release_date'] || $m['release_date']<'2026-10-01' || $m['release_date']>'2026-12-31') throw new RuntimeException('Invalid VN date.');
    $path = __DIR__.'/../public/'.$m['poster_url'];
    $image = @getimagesize($path);
    if (!$image || !in_array($image['mime'],['image/jpeg','image/png','image/webp'],true) || hash_file('sha256',$path)!==$m['poster_sha256']) throw new RuntimeException('Invalid poster: '.$path);
    $duplicate = false;
    foreach (array_merge([$m['title']], $m['aliases']) as $alias) if (isset($known[$normalize($alias)])) $duplicate=true;
    if ($duplicate) { $skipped[]=$m['title']; continue; }
    foreach (array_merge([$m['title']], $m['aliases']) as $alias) $known[$normalize($alias)]=-1;
    $ready[]=$m;
}
echo count($ready)." ready; ".count($skipped)." already present; ".count($pending)." pending verification.\n";
foreach ($pending as $title) echo "PENDING: $title\n";
if ($export) {
    $quote = fn($v)=>$v===null?'NULL':"'".$db->real_escape_string((string)$v)."'";
    $sql = ["-- Verified Cinemax catalog as of 2026-10-12. No schema/showtime changes.","-- Pending movies remain in movies_q4_2026.json until mandatory data is available.","SET NAMES utf8mb4;",'START TRANSACTION;'];
    foreach ($ready as $m) {
        $title=$quote($m['title']);
        $aliases=implode(',',array_map($quote,array_unique(array_merge([$m['title']],$m['aliases']))));
        $sql[]='-- Source: '.implode(' | ',$m['sources']);
        $fields = array_map($quote,[$m['title'],$m['director'],$m['actors'],$m['description'],$m['duration_min'],$m['release_date'],$m['poster_url'],$m['trailer_url']]);
        $sql[]='INSERT INTO movies (title,director,actors,description,duration_min,release_date,poster_url,trailer_url,status) SELECT '.implode(',',$fields).", IF(".$quote($m['release_date']).">CURDATE(),1,2) WHERE NOT EXISTS (SELECT 1 FROM movies WHERE TRIM(title) IN ($aliases));";
        $sql[]='SET @q4_movie_id = IF(ROW_COUNT()=1,LAST_INSERT_ID(),NULL);';
        foreach ($m['genres'] as $genre) {
            $g=$quote($genre);
            $sql[]="INSERT INTO genres (name,status) SELECT $g,1 WHERE @q4_movie_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM genres WHERE name=$g);";
            $sql[]="INSERT INTO movie_genres (movie_id,genre_id) SELECT @q4_movie_id,genre_id FROM genres WHERE @q4_movie_id IS NOT NULL AND name=$g AND status=1;";
        }
        $sql[]="INSERT INTO movie_images (movie_id,image_url,is_poster,status) SELECT @q4_movie_id,".$quote($m['poster_url']).",1,1 WHERE @q4_movie_id IS NOT NULL;";
    }
    $sql[]='COMMIT;';
    file_put_contents(__DIR__.'/seed_movies_q4_2026.sql',implode("\n",$sql)."\n");
    echo "SQL exported.\n";
    exit;
}
if (!$apply || !$ready) exit;
$lock = $db->query("SELECT GET_LOCK('cinemax_movies_q4_2026',10)")->fetch_row()[0];
if ((int)$lock!==1) throw new RuntimeException('Import lock unavailable.');
try {
    $db->begin_transaction();
    $snapshot = ['captured_at'=>date(DATE_ATOM)];
    foreach (['movies','genres','movie_genres','movie_images'] as $table) $snapshot[$table]=$db->query("SELECT * FROM `$table`")->fetch_all(MYSQLI_ASSOC);
    foreach (['shows','tickets','bills'] as $table) $snapshot['counts'][$table]=(int)$db->query("SELECT COUNT(*) FROM `$table`")->fetch_row()[0];
    $backup=__DIR__.'/research-2026-q4/before-import.json';
    if (is_file($backup)) $backup=__DIR__.'/research-2026-q4/before-import-'.date('Ymd-His').'-'.bin2hex(random_bytes(3)).'.json';
    if (file_put_contents($backup,json_encode($snapshot,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR))===false) throw new RuntimeException('Backup failed.');
    $insert=$db->prepare('INSERT INTO movies (title,director,actors,description,duration_min,release_date,poster_url,trailer_url,status) VALUES (?,?,?,?,?,?,?,?,?)');
    $check=$db->prepare('SELECT movie_id FROM movies WHERE TRIM(title)=? LIMIT 1');
    $getGenre=$db->prepare('SELECT genre_id,status FROM genres WHERE name=? LIMIT 1');
    $insertGenre=$db->prepare('INSERT INTO genres (name,status) VALUES (?,1)');
    $link=$db->prepare('INSERT INTO movie_genres (movie_id,genre_id) VALUES (?,?)');
    $image=$db->prepare('INSERT INTO movie_images (movie_id,image_url,is_poster,status) VALUES (?,?,1,1)');
    $today=$db->query('SELECT CURDATE()')->fetch_row()[0];
    $imported=[];
    foreach ($ready as $m) {
        foreach (array_merge([$m['title']],$m['aliases']) as $alias) {
            $check->bind_param('s',$alias); $check->execute();
            if ($check->get_result()->num_rows) continue 2;
        }
        // Existing application rule: future=1, released=2. No showtimes are invented.
        $status=$m['release_date']>$today?1:2;
        $values=[$m['title'],$m['director'],$m['actors'],$m['description'],$m['duration_min'],$m['release_date'],$m['poster_url'],$m['trailer_url'],$status];
        $insert->bind_param('ssssisssi',...$values); $insert->execute();
        $id=(int)$db->insert_id;
        foreach ($m['genres'] as $name) {
            $getGenre->bind_param('s',$name); $getGenre->execute(); $genre=$getGenre->get_result()->fetch_assoc();
            if ($genre && (int)$genre['status']!==1) throw new RuntimeException('Existing genre is disabled: '.$name);
            if (!$genre) { $insertGenre->bind_param('s',$name); $insertGenre->execute(); $gid=(int)$db->insert_id; }
            else $gid=(int)$genre['genre_id'];
            $link->bind_param('ii',$id,$gid); $link->execute();
        }
        $image->bind_param('is',$id,$m['poster_url']); $image->execute();
        $imported[]=['movie_id'=>$id,'title'=>$m['title'],'status'=>$status];
    }
    $db->commit();
    $resultFile=__DIR__.'/research-2026-q4/import-result.json';
    $previous=is_file($resultFile)?json_decode(file_get_contents($resultFile),true,512,JSON_THROW_ON_ERROR):[];
    file_put_contents($resultFile,json_encode(array_merge($previous,$imported),JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR));
    echo count($imported)." movies inserted; old rows preserved.\n";
} catch (Throwable $e) {
    $db->rollback();
    throw $e;
} finally {
    $db->query("SELECT RELEASE_LOCK('cinemax_movies_q4_2026')");
}
