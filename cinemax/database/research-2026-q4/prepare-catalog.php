<?php
// Offline curation: source facts are kept separate from Vietnamese summaries.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$raw = json_decode(ltrim(file_get_contents(__DIR__ . '/selected.json'), "\xEF\xBB\xBF"), true, 512, JSON_THROW_ON_ERROR);
$summaries = [
    'scotty-giai-cuu-hoang-thuong' => 'Mèo hoang Scotty và mèo nhà Katie bất ngờ trở thành bạn đồng hành. Qua những thử thách trên đường trở về, hai chú mèo học cách tin tưởng nhau và tìm hiểu ý nghĩa của một mái ấm.',
    'than-su-chung-quy-linh-gioi-dai-chien' => 'Được Chung Quỳ cứu giúp, Sơ Cửu bước vào thế giới của những linh hồn và yêu quái. Cô mong trở thành học trò của vị thợ săn huyền thoại, cùng anh bảo vệ sự cân bằng giữa hai cõi.',
    'quyet-cua-anh-nay' => 'Henry mất ký ức về ngày hôm trước mỗi khi thức dậy. Lucy phải tìm cách làm quen với anh mỗi ngày, tạo nên một chuyện tình vừa hài hước vừa đầy thử thách.',
    'trai-tim-quai-thu' => 'Sĩ quan James Belmont và chú chó Odin sống sót sau tai nạn máy bay tại Alaska. Giữa vùng thiên nhiên hiểm trở, họ phải dựa vào nhau để tìm đường thoát và vượt qua cuộc chiến sinh tồn.',
    'blue-lock-cuoc-chien-cua-nhung-tien-dao' => 'Ba trăm cầu thủ trẻ bước vào chương trình huấn luyện Blue Lock để tranh vị trí tiền đạo xuất sắc nhất. Các vòng tuyển chọn khốc liệt thử thách tham vọng và bản lĩnh của từng người. Đây là bản chuyển thể người đóng.',
    'an-mang-xem-hoan-hao' => 'Trinh tỉnh dậy tại phim trường, mất trí nhớ và bị nghi liên quan đến một vụ giết người. Cùng thám tử Đại, cô tìm manh mối giữa những bí mật của giới giải trí để làm sáng tỏ sự thật.',
    'nguoi-me-khac' => 'Bela nhận ra một thực thể đáng sợ trong tủ quần áo khi gia đình đang rạn nứt. Mang hình dáng giống mẹ cô, sinh vật ấy biến mái nhà thành nơi mà Bela không còn biết nên tin ai.',
    'shaun-the-sheep-quai-la-ghe-nha' => 'Vườn bí ngô chuẩn bị cho Halloween bị phá hỏng, khiến Shaun thử dùng khoa học để cứu mùa lễ hội. Thí nghiệm gây ra rắc rối mới, một quái thú xuất hiện và đàn cừu phải đi tìm ông chủ mất tích.',
    'quy-an-tang-4-ho-tinh' => 'Trong nhiệm vụ tìm người mất tích, Yak và trung sĩ Paphan tiến vào khu rừng nguy hiểm. Họ phải chống lại một Hổ Tinh có khả năng biến hình, đồng thời tìm hiểu bí mật đằng sau những cuộc săn người.',
    'an-mang-karaoke-1' => 'Một chuyến tu tập trên núi trở thành hiện trường án mạng khi bà Thủy bị sát hại. Giữa nhóm người đều có động cơ, những điều tra viên bất đắc dĩ phải tìm ra hung thủ đang ẩn mình.',
    'bo-sua-bay' => 'Tại một nông trường sữa, Trâu nhận nhiệm vụ đánh cắp công thức bí mật để giúp gia đình thoát nghèo. Tình cảm với San, người nghiên cứu công thức ấy, khiến anh phải lựa chọn giữa phi vụ đổi đời và lòng tin của cô.',
    'chuyen-di-nho-doi' => 'Bị bão cuốn khỏi chuyến đi biển, Hoàng và con trai An mắc kẹt trên một hòn đảo. Hai cha con phải học cách sinh tồn và tìm đường về, đồng thời xây dựng lại sự gắn kết trong gia đình.',
    'ngay-con-con-me' => 'Mây lớn lên với cảm giác mẹ ưu ái anh trai hơn mình. Một biến cố khiến cô nhìn lại những hiểu lầm kéo dài và học cách thấu hiểu tình yêu mà mẹ dành cho các con.',
    'trai-giam-hanh-phuc' => 'Vì bảo vệ em gái, thầy giáo Phúc gây án và phải vào tù. Lòng tốt giúp anh kết nối với những người cùng cảnh ngộ, nhưng các mối thù và cuộc sống khó khăn của em gái vẫn đeo đuổi anh.',
    'focker-in-law' => 'Bạn gái của con trai Greg Focker ra mắt gia đình, kéo theo những phép thử lòng tin quen thuộc. Các thế hệ nhà Focker lại rơi vào một chuỗi tình huống hài hước khi đón thành viên mới.',
    'con-mat-thu-ba' => 'Một người cuồng tín phát hiện con mắt khác thường trên trán sau cơn đau dữ dội. Những vụ giết người và hiện tượng ma quái dần hé lộ mối liên hệ với các ám ảnh từ quá khứ.',
    'avengers-doomsday' => 'Các siêu anh hùng cùng Fantastic Four phải tập hợp trước mối đe dọa từ Victor Von Doom. Cuộc đối đầu mở ra một thử thách mới cho những người bảo vệ thế giới.',
    'dune-part-three' => 'Sau khi giành quyền lực, Paul Atreides phải đối mặt với hậu quả của việc cai trị. Những âm mưu và mối đe dọa mới khiến tương lai của đế chế cùng những người anh yêu thương trở nên bất định.',
    'nguoi-me-xau' => 'Một người con muốn rời bỏ quá khứ bất hảo, nhưng ân oán của mẹ kéo anh trở lại vòng nguy hiểm. Hai mẹ con phải đối mặt với những lựa chọn khắc nghiệt trên hành trình tìm một cuộc sống khác.',
    'godzilla-tru-khong' => 'Nhật Bản đang cố gắng hồi phục sau chiến tranh và cuộc tấn công trước đó của Godzilla. Khi cuộc sống tưởng như bình yên trở lại, một mối đe dọa mới xuất hiện, đặt hy vọng của con người trước thử thách.',
];
$genreMap = ['Adventure'=>'Phiêu lưu','Animation'=>'Hoạt hình','Family'=>'Gia đình','Action'=>'Hành động','Fantasy'=>'Giả tưởng','Comedy'=>'Hài','Romance'=>'Tình cảm','Drama'=>'Tâm lý','Mystery'=>'Bí ẩn','Horror'=>'Kinh dị','Thriller'=>'Giật gân','Science Fiction'=>'Khoa học viễn tưởng'];
$catalog = [];
foreach ($raw as $r) {
    $slug = $r['slug'];
    if (!isset($summaries[$slug])) throw new RuntimeException('Uncurated movie: ' . $slug);
    $runtime = preg_match('/^PT(\d+)M$/', $r['duration'] ?? '', $m) ? (int)$m[1] : null;
    $sources = [$r['source']];
    if ($slug === 'focker-in-law') {
        $runtime = 120;
        $sources[] = 'https://www.bhdstar.vn/phim/gap-go-thong-gia-dau-moi-trinh-lang/';
    }
    $extraSources = [
        'scotty-giai-cuu-hoang-thuong'=>'https://www.cgv.vn/default/scotty.html',
        'bo-sua-bay'=>'https://www.cgv.vn/default/bo-sua-bay.html',
        'ngay-con-con-me'=>'https://www.galaxycine.vn/dat-ve/ngay-con-con-me/',
        'trai-giam-hanh-phuc'=>'https://www.galaxycine.vn/phim/trai-giam-hanh-phuc/',
        'con-mat-thu-ba'=>'https://www.galaxycine.vn/phim/con-mat-thu-ba/',
        'dune-part-three'=>'https://www.galaxycine.vn/phim/dune-3/',
        'nguoi-me-xau'=>'https://www.galaxycine.vn/phim/nguoi-me-xau/',
    ];
    if (isset($extraSources[$slug])) $sources[] = $extraSources[$slug];
    $channel = $r['trailer_metadata']['author_name'] ?? '';
    $official = in_array($channel, ['CGV Cinemas Vietnam','Galaxy Studio','Galaxy Cinema (Official)','BHD Star Cineplex','STUDIOCANAL','Universal Pictures'], true);
    $missing = [];
    if (!$runtime) $missing[] = 'Thời lượng chưa được nguồn tham khảo công bố; schema hiện tại không cho phép NULL.';
    if (!$official) $missing[] = 'Chưa xác minh trailer trên kênh chính thức.';
    $title = $slug === 'dune-part-three' ? 'Dune: Hành Tinh Cát - Phần 3' : $r['title'];
    $original = explode(' - ', $r['original_and_genres'])[0];
    if ($original === $r['genres'] || !$original) $original = null;
    if ($slug === 'dune-part-three') $original = 'Dune: Part Three';
    $poster = $r['poster_url'] ?? null;
    $image = $poster ? @getimagesize(__DIR__ . '/../../public/' . $poster) : false;
    if (!$image || !in_array($image['mime'], ['image/jpeg','image/png','image/webp'], true) || $image[1] <= $image[0]) {
        throw new RuntimeException('Invalid portrait poster: ' . $slug);
    }
    $genres = array_map(function ($g) use ($genreMap) { $g=trim($g); if (!isset($genreMap[$g])) throw new RuntimeException('Unknown genre: '.$g); return $genreMap[$g]; }, explode(',', $r['genres']));
    $catalog[] = [
        'slug'=>$slug, 'title'=>$title, 'international_title'=>$original,
        'aliases'=>array_values(array_unique(array_filter([$r['title'], $original]))),
        'director'=>implode(', ', $r['director']), 'actors'=>implode(', ', array_slice($r['actors'], 0, 6)),
        'description'=>$summaries[$slug], 'duration_min'=>$runtime,
        'release_date'=>substr($r['release_date_vn'],0,10),
        'release_date_scope'=>'Việt Nam, lịch rạp dự kiến, đối chiếu ngày 12/10/2026',
        'genres'=>$genres, 'poster_url'=>$poster, 'poster_source'=>$r['poster_source'],
        'poster_width'=>$image[0], 'poster_height'=>$image[1],
        'poster_sha256'=>hash_file('sha256', __DIR__ . '/../../public/' . $poster),
        'poster_rights'=>'Nguồn quảng bá công khai; chưa xác minh được giấy phép tái sử dụng thương mại. Không coi truy cập công khai là giấy phép.',
        'trailer_url'=>$official ? 'https://www.youtube.com/watch?v='.$r['trailer_id'] : null,
        'trailer_title'=>$r['trailer_metadata']['title'] ?? null, 'trailer_channel'=>$channel,
        'trailer_channel_url'=>$r['trailer_metadata']['author_url'] ?? null,
        'trailer_verified'=>$official, 'sources'=>$sources, 'verified_at'=>'2026-10-12',
        'import_ready'=>!$missing, 'pending_reasons'=>$missing,
        'notes'=>$slug==='blue-lock-cuoc-chien-cua-nhung-tien-dao' ? 'Live action, không phải anime.' : null,
    ];
}
usort($catalog, fn($a,$b)=>strcmp($a['release_date'].$a['title'],$b['release_date'].$b['title']));
file_put_contents(__DIR__.'/../movies_q4_2026.json', json_encode($catalog, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n");
// Retain factual provenance without distributing copied publisher synopses.
foreach ($raw as &$record) unset($record['source_synopsis']);
unset($record);
file_put_contents(__DIR__.'/selected.json',json_encode($raw,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n");
file_put_contents(__DIR__.'/candidates.json',json_encode($raw,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n");
echo count($catalog).' researched; '.count(array_filter($catalog, fn($m)=>$m['import_ready']))." import ready.\n";
