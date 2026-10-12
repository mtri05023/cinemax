-- Verified Cinemax catalog as of 2026-10-12. No schema/showtime changes.
-- Pending movies remain in movies_q4_2026.json until mandatory data is available.
SET NAMES utf8mb4;
START TRANSACTION;
-- Source: https://moveek.com/phim/quyet-cua-anh-nay/
INSERT INTO movies (title,director,actors,description,duration_min,release_date,poster_url,trailer_url,status) SELECT 'Quyết Cua Anh Này','Mez Tharatorn','Nadech Kugimiya, Minnie, Kiat Kitjaroen, Arthit Somnoi, Tipargon Chaiprasit','Henry mất ký ức về ngày hôm trước mỗi khi thức dậy. Lucy phải tìm cách làm quen với anh mỗi ngày, tạo nên một chuyện tình vừa hài hước vừa đầy thử thách.','115','2026-10-02','assets/uploads/movies/quyet-cua-anh-nay-2026.webp','https://www.youtube.com/watch?v=XKdEiTx9W68', IF('2026-10-02'>CURDATE(),1,2) WHERE NOT EXISTS (SELECT 1 FROM movies WHERE TRIM(title) IN ('Quyết Cua Anh Này','Henry\'s First Dates'));
SET @q4_movie_id = IF(ROW_COUNT()=1,LAST_INSERT_ID(),NULL);
INSERT INTO genres (name,status) SELECT 'Hài',1 WHERE @q4_movie_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM genres WHERE name='Hài');
INSERT INTO movie_genres (movie_id,genre_id) SELECT @q4_movie_id,genre_id FROM genres WHERE @q4_movie_id IS NOT NULL AND name='Hài' AND status=1;
INSERT INTO genres (name,status) SELECT 'Tình cảm',1 WHERE @q4_movie_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM genres WHERE name='Tình cảm');
INSERT INTO movie_genres (movie_id,genre_id) SELECT @q4_movie_id,genre_id FROM genres WHERE @q4_movie_id IS NOT NULL AND name='Tình cảm' AND status=1;
INSERT INTO movie_images (movie_id,image_url,is_poster,status) SELECT @q4_movie_id,'assets/uploads/movies/quyet-cua-anh-nay-2026.webp',1,1 WHERE @q4_movie_id IS NOT NULL;
-- Source: https://moveek.com/phim/scotty-giai-cuu-hoang-thuong/ | https://www.cgv.vn/default/scotty.html
INSERT INTO movies (title,director,actors,description,duration_min,release_date,poster_url,trailer_url,status) SELECT 'Scotty Giải Cứu Hoàng Thượng','Mohammad Pirzadi','William Hope, Stuart Milligan, Phil Cornwell, Walles Hamonde, Jay Rincon, Dario Coates','Mèo hoang Scotty và mèo nhà Katie bất ngờ trở thành bạn đồng hành. Qua những thử thách trên đường trở về, hai chú mèo học cách tin tưởng nhau và tìm hiểu ý nghĩa của một mái ấm.','80','2026-10-02','assets/uploads/movies/scotty-giai-cuu-hoang-thuong-2026.webp','https://www.youtube.com/watch?v=B6z2QdwrJ8A', IF('2026-10-02'>CURDATE(),1,2) WHERE NOT EXISTS (SELECT 1 FROM movies WHERE TRIM(title) IN ('Scotty Giải Cứu Hoàng Thượng','Scotty'));
SET @q4_movie_id = IF(ROW_COUNT()=1,LAST_INSERT_ID(),NULL);
INSERT INTO genres (name,status) SELECT 'Phiêu lưu',1 WHERE @q4_movie_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM genres WHERE name='Phiêu lưu');
INSERT INTO movie_genres (movie_id,genre_id) SELECT @q4_movie_id,genre_id FROM genres WHERE @q4_movie_id IS NOT NULL AND name='Phiêu lưu' AND status=1;
INSERT INTO genres (name,status) SELECT 'Hoạt hình',1 WHERE @q4_movie_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM genres WHERE name='Hoạt hình');
INSERT INTO movie_genres (movie_id,genre_id) SELECT @q4_movie_id,genre_id FROM genres WHERE @q4_movie_id IS NOT NULL AND name='Hoạt hình' AND status=1;
INSERT INTO genres (name,status) SELECT 'Gia đình',1 WHERE @q4_movie_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM genres WHERE name='Gia đình');
INSERT INTO movie_genres (movie_id,genre_id) SELECT @q4_movie_id,genre_id FROM genres WHERE @q4_movie_id IS NOT NULL AND name='Gia đình' AND status=1;
INSERT INTO movie_images (movie_id,image_url,is_poster,status) SELECT @q4_movie_id,'assets/uploads/movies/scotty-giai-cuu-hoang-thuong-2026.webp',1,1 WHERE @q4_movie_id IS NOT NULL;
-- Source: https://moveek.com/phim/than-su-chung-quy-linh-gioi-dai-chien/
INSERT INTO movies (title,director,actors,description,duration_min,release_date,poster_url,trailer_url,status) SELECT 'Thần Sư Chung Quỳ: Linh Giới Đại Chiến','Yuxi Wang, 黄山川','Duan Yixuan, 余昊威, Liangwei Hu, Zhao Chengchen, Ba He, Zhengjian Hu','Được Chung Quỳ cứu giúp, Sơ Cửu bước vào thế giới của những linh hồn và yêu quái. Cô mong trở thành học trò của vị thợ săn huyền thoại, cùng anh bảo vệ sự cân bằng giữa hai cõi.','96','2026-10-02','assets/uploads/movies/than-su-chung-quy-linh-gioi-dai-chien-2026.webp','https://www.youtube.com/watch?v=KEBDVoBTeTk', IF('2026-10-02'>CURDATE(),1,2) WHERE NOT EXISTS (SELECT 1 FROM movies WHERE TRIM(title) IN ('Thần Sư Chung Quỳ: Linh Giới Đại Chiến','Master Zhong'));
SET @q4_movie_id = IF(ROW_COUNT()=1,LAST_INSERT_ID(),NULL);
INSERT INTO genres (name,status) SELECT 'Hành động',1 WHERE @q4_movie_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM genres WHERE name='Hành động');
INSERT INTO movie_genres (movie_id,genre_id) SELECT @q4_movie_id,genre_id FROM genres WHERE @q4_movie_id IS NOT NULL AND name='Hành động' AND status=1;
INSERT INTO genres (name,status) SELECT 'Hoạt hình',1 WHERE @q4_movie_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM genres WHERE name='Hoạt hình');
INSERT INTO movie_genres (movie_id,genre_id) SELECT @q4_movie_id,genre_id FROM genres WHERE @q4_movie_id IS NOT NULL AND name='Hoạt hình' AND status=1;
INSERT INTO genres (name,status) SELECT 'Giả tưởng',1 WHERE @q4_movie_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM genres WHERE name='Giả tưởng');
INSERT INTO movie_genres (movie_id,genre_id) SELECT @q4_movie_id,genre_id FROM genres WHERE @q4_movie_id IS NOT NULL AND name='Giả tưởng' AND status=1;
INSERT INTO movie_images (movie_id,image_url,is_poster,status) SELECT @q4_movie_id,'assets/uploads/movies/than-su-chung-quy-linh-gioi-dai-chien-2026.webp',1,1 WHERE @q4_movie_id IS NOT NULL;
-- Source: https://moveek.com/phim/trai-tim-quai-thu/
INSERT INTO movies (title,director,actors,description,duration_min,release_date,poster_url,trailer_url,status) SELECT 'Trái Tim Quái Thú','David Ayer','Brad Pitt, J.K. Simmons, Anna Lambe','Sĩ quan James Belmont và chú chó Odin sống sót sau tai nạn máy bay tại Alaska. Giữa vùng thiên nhiên hiểm trở, họ phải dựa vào nhau để tìm đường thoát và vượt qua cuộc chiến sinh tồn.','101','2026-10-02','assets/uploads/movies/trai-tim-quai-thu-2026.webp','https://www.youtube.com/watch?v=_1F2MrfQLjo', IF('2026-10-02'>CURDATE(),1,2) WHERE NOT EXISTS (SELECT 1 FROM movies WHERE TRIM(title) IN ('Trái Tim Quái Thú','Heart of the Beast'));
SET @q4_movie_id = IF(ROW_COUNT()=1,LAST_INSERT_ID(),NULL);
INSERT INTO genres (name,status) SELECT 'Hành động',1 WHERE @q4_movie_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM genres WHERE name='Hành động');
INSERT INTO movie_genres (movie_id,genre_id) SELECT @q4_movie_id,genre_id FROM genres WHERE @q4_movie_id IS NOT NULL AND name='Hành động' AND status=1;
INSERT INTO genres (name,status) SELECT 'Phiêu lưu',1 WHERE @q4_movie_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM genres WHERE name='Phiêu lưu');
INSERT INTO movie_genres (movie_id,genre_id) SELECT @q4_movie_id,genre_id FROM genres WHERE @q4_movie_id IS NOT NULL AND name='Phiêu lưu' AND status=1;
INSERT INTO movie_images (movie_id,image_url,is_poster,status) SELECT @q4_movie_id,'assets/uploads/movies/trai-tim-quai-thu-2026.webp',1,1 WHERE @q4_movie_id IS NOT NULL;
-- Source: https://moveek.com/phim/blue-lock-cuoc-chien-cua-nhung-tien-dao/
INSERT INTO movies (title,director,actors,description,duration_min,release_date,poster_url,trailer_url,status) SELECT 'Blue Lock: Cuộc Chiến Của Những Tiền Đạo','Yusuke Taki','Fumiya Takahashi, Kaito Sakurai, Kyohei Takahashi, Kouta Nomura, Yuzu Aoki, Sho Nishigaki','Ba trăm cầu thủ trẻ bước vào chương trình huấn luyện Blue Lock để tranh vị trí tiền đạo xuất sắc nhất. Các vòng tuyển chọn khốc liệt thử thách tham vọng và bản lĩnh của từng người. Đây là bản chuyển thể người đóng.','127','2026-10-09','assets/uploads/movies/blue-lock-cuoc-chien-cua-nhung-tien-dao-2026.webp','https://www.youtube.com/watch?v=9spa9_P9dRY', IF('2026-10-09'>CURDATE(),1,2) WHERE NOT EXISTS (SELECT 1 FROM movies WHERE TRIM(title) IN ('Blue Lock: Cuộc Chiến Của Những Tiền Đạo','Blue Lock (2026)'));
SET @q4_movie_id = IF(ROW_COUNT()=1,LAST_INSERT_ID(),NULL);
INSERT INTO genres (name,status) SELECT 'Hành động',1 WHERE @q4_movie_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM genres WHERE name='Hành động');
INSERT INTO movie_genres (movie_id,genre_id) SELECT @q4_movie_id,genre_id FROM genres WHERE @q4_movie_id IS NOT NULL AND name='Hành động' AND status=1;
INSERT INTO genres (name,status) SELECT 'Tâm lý',1 WHERE @q4_movie_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM genres WHERE name='Tâm lý');
INSERT INTO movie_genres (movie_id,genre_id) SELECT @q4_movie_id,genre_id FROM genres WHERE @q4_movie_id IS NOT NULL AND name='Tâm lý' AND status=1;
INSERT INTO movie_images (movie_id,image_url,is_poster,status) SELECT @q4_movie_id,'assets/uploads/movies/blue-lock-cuoc-chien-cua-nhung-tien-dao-2026.webp',1,1 WHERE @q4_movie_id IS NOT NULL;
-- Source: https://moveek.com/phim/nguoi-me-khac/
INSERT INTO movies (title,director,actors,description,duration_min,release_date,poster_url,trailer_url,status) SELECT 'Người Mẹ Khác','Rob Savage','Jessica Chastain, Arabella Olivia Clark, Jay Duplass, Dichen Lachman, Arian Moayed, Karen Allen','Bela nhận ra một thực thể đáng sợ trong tủ quần áo khi gia đình đang rạn nứt. Mang hình dáng giống mẹ cô, sinh vật ấy biến mái nhà thành nơi mà Bela không còn biết nên tin ai.','93','2026-10-09','assets/uploads/movies/nguoi-me-khac-2026.webp','https://www.youtube.com/watch?v=8qy8tHRKQoA', IF('2026-10-09'>CURDATE(),1,2) WHERE NOT EXISTS (SELECT 1 FROM movies WHERE TRIM(title) IN ('Người Mẹ Khác','Other Mommy'));
SET @q4_movie_id = IF(ROW_COUNT()=1,LAST_INSERT_ID(),NULL);
INSERT INTO genres (name,status) SELECT 'Kinh dị',1 WHERE @q4_movie_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM genres WHERE name='Kinh dị');
INSERT INTO movie_genres (movie_id,genre_id) SELECT @q4_movie_id,genre_id FROM genres WHERE @q4_movie_id IS NOT NULL AND name='Kinh dị' AND status=1;
INSERT INTO movie_images (movie_id,image_url,is_poster,status) SELECT @q4_movie_id,'assets/uploads/movies/nguoi-me-khac-2026.webp',1,1 WHERE @q4_movie_id IS NOT NULL;
-- Source: https://moveek.com/phim/an-mang-xem-hoan-hao/
INSERT INTO movies (title,director,actors,description,duration_min,release_date,poster_url,trailer_url,status) SELECT 'Án Mạng Xém Hoàn Hảo','Đức Nguyễn','Anh Tú Atus, DJ Mie, Vũ Ngọc Anh, Huy Khánh, Long Đẹp Trai, Sỹ Toàn','Trinh tỉnh dậy tại phim trường, mất trí nhớ và bị nghi liên quan đến một vụ giết người. Cùng thám tử Đại, cô tìm manh mối giữa những bí mật của giới giải trí để làm sáng tỏ sự thật.','124','2026-10-09','assets/uploads/movies/an-mang-xem-hoan-hao-2026.webp','https://www.youtube.com/watch?v=IW-k9cMySWg', IF('2026-10-09'>CURDATE(),1,2) WHERE NOT EXISTS (SELECT 1 FROM movies WHERE TRIM(title) IN ('Án Mạng Xém Hoàn Hảo','The Almost Perfect Murder'));
SET @q4_movie_id = IF(ROW_COUNT()=1,LAST_INSERT_ID(),NULL);
INSERT INTO genres (name,status) SELECT 'Hài',1 WHERE @q4_movie_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM genres WHERE name='Hài');
INSERT INTO movie_genres (movie_id,genre_id) SELECT @q4_movie_id,genre_id FROM genres WHERE @q4_movie_id IS NOT NULL AND name='Hài' AND status=1;
INSERT INTO genres (name,status) SELECT 'Bí ẩn',1 WHERE @q4_movie_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM genres WHERE name='Bí ẩn');
INSERT INTO movie_genres (movie_id,genre_id) SELECT @q4_movie_id,genre_id FROM genres WHERE @q4_movie_id IS NOT NULL AND name='Bí ẩn' AND status=1;
INSERT INTO movie_images (movie_id,image_url,is_poster,status) SELECT @q4_movie_id,'assets/uploads/movies/an-mang-xem-hoan-hao-2026.webp',1,1 WHERE @q4_movie_id IS NOT NULL;
-- Source: https://moveek.com/phim/quy-an-tang-4-ho-tinh/
INSERT INTO movies (title,director,actors,description,duration_min,release_date,poster_url,trailer_url,status) SELECT 'Quỷ Ăn Tạng 4: Hổ Tinh','Narit Yuvaboon, Thanadet Pradit, Chalit Krileadmongkon','Nadech Kugimiya, Ong-art Cheamcharoenpornkul, Maylada Susri, Wichapas Sumettikul, Sahajak Boonthanakit, Akarin Akaranitimaytharatt','Trong nhiệm vụ tìm người mất tích, Yak và trung sĩ Paphan tiến vào khu rừng nguy hiểm. Họ phải chống lại một Hổ Tinh có khả năng biến hình, đồng thời tìm hiểu bí mật đằng sau những cuộc săn người.','111','2026-10-16','assets/uploads/movies/quy-an-tang-4-ho-tinh-2026.webp','https://www.youtube.com/watch?v=0t29-MGiths', IF('2026-10-16'>CURDATE(),1,2) WHERE NOT EXISTS (SELECT 1 FROM movies WHERE TRIM(title) IN ('Quỷ Ăn Tạng 4: Hổ Tinh','Death Whisperer: Saming the Werebeast'));
SET @q4_movie_id = IF(ROW_COUNT()=1,LAST_INSERT_ID(),NULL);
INSERT INTO genres (name,status) SELECT 'Hành động',1 WHERE @q4_movie_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM genres WHERE name='Hành động');
INSERT INTO movie_genres (movie_id,genre_id) SELECT @q4_movie_id,genre_id FROM genres WHERE @q4_movie_id IS NOT NULL AND name='Hành động' AND status=1;
INSERT INTO genres (name,status) SELECT 'Giật gân',1 WHERE @q4_movie_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM genres WHERE name='Giật gân');
INSERT INTO movie_genres (movie_id,genre_id) SELECT @q4_movie_id,genre_id FROM genres WHERE @q4_movie_id IS NOT NULL AND name='Giật gân' AND status=1;
INSERT INTO genres (name,status) SELECT 'Kinh dị',1 WHERE @q4_movie_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM genres WHERE name='Kinh dị');
INSERT INTO movie_genres (movie_id,genre_id) SELECT @q4_movie_id,genre_id FROM genres WHERE @q4_movie_id IS NOT NULL AND name='Kinh dị' AND status=1;
INSERT INTO movie_images (movie_id,image_url,is_poster,status) SELECT @q4_movie_id,'assets/uploads/movies/quy-an-tang-4-ho-tinh-2026.webp',1,1 WHERE @q4_movie_id IS NOT NULL;
-- Source: https://moveek.com/phim/shaun-the-sheep-quai-la-ghe-nha/
INSERT INTO movies (title,director,actors,description,duration_min,release_date,poster_url,trailer_url,status) SELECT 'Shaun the Sheep: Quái Lạ Ghé Nhà','Steve Cox, Matthew Walker','Justin Fletcher, John Sparkes, Nina Sosanya, Kate Harbour, Andy Nyman, Simon Greenall','Vườn bí ngô chuẩn bị cho Halloween bị phá hỏng, khiến Shaun thử dùng khoa học để cứu mùa lễ hội. Thí nghiệm gây ra rắc rối mới, một quái thú xuất hiện và đàn cừu phải đi tìm ông chủ mất tích.','81','2026-10-16','assets/uploads/movies/shaun-the-sheep-quai-la-ghe-nha-2026.webp','https://www.youtube.com/watch?v=UZ46Smi6mOw', IF('2026-10-16'>CURDATE(),1,2) WHERE NOT EXISTS (SELECT 1 FROM movies WHERE TRIM(title) IN ('Shaun the Sheep: Quái Lạ Ghé Nhà','Shaun the Sheep: The Beast of Mossy Bottom'));
SET @q4_movie_id = IF(ROW_COUNT()=1,LAST_INSERT_ID(),NULL);
INSERT INTO genres (name,status) SELECT 'Hài',1 WHERE @q4_movie_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM genres WHERE name='Hài');
INSERT INTO movie_genres (movie_id,genre_id) SELECT @q4_movie_id,genre_id FROM genres WHERE @q4_movie_id IS NOT NULL AND name='Hài' AND status=1;
INSERT INTO genres (name,status) SELECT 'Phiêu lưu',1 WHERE @q4_movie_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM genres WHERE name='Phiêu lưu');
INSERT INTO movie_genres (movie_id,genre_id) SELECT @q4_movie_id,genre_id FROM genres WHERE @q4_movie_id IS NOT NULL AND name='Phiêu lưu' AND status=1;
INSERT INTO genres (name,status) SELECT 'Hoạt hình',1 WHERE @q4_movie_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM genres WHERE name='Hoạt hình');
INSERT INTO movie_genres (movie_id,genre_id) SELECT @q4_movie_id,genre_id FROM genres WHERE @q4_movie_id IS NOT NULL AND name='Hoạt hình' AND status=1;
INSERT INTO genres (name,status) SELECT 'Gia đình',1 WHERE @q4_movie_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM genres WHERE name='Gia đình');
INSERT INTO movie_genres (movie_id,genre_id) SELECT @q4_movie_id,genre_id FROM genres WHERE @q4_movie_id IS NOT NULL AND name='Gia đình' AND status=1;
INSERT INTO genres (name,status) SELECT 'Kinh dị',1 WHERE @q4_movie_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM genres WHERE name='Kinh dị');
INSERT INTO movie_genres (movie_id,genre_id) SELECT @q4_movie_id,genre_id FROM genres WHERE @q4_movie_id IS NOT NULL AND name='Kinh dị' AND status=1;
INSERT INTO movie_images (movie_id,image_url,is_poster,status) SELECT @q4_movie_id,'assets/uploads/movies/shaun-the-sheep-quai-la-ghe-nha-2026.webp',1,1 WHERE @q4_movie_id IS NOT NULL;
-- Source: https://moveek.com/phim/an-mang-karaoke-1/
INSERT INTO movies (title,director,actors,description,duration_min,release_date,poster_url,trailer_url,status) SELECT 'Án Mạng Karaoke','Hoàng Lê','Vân Dung, Đoàn Thiên Ân, Đại Nghĩa','Một chuyến tu tập trên núi trở thành hiện trường án mạng khi bà Thủy bị sát hại. Giữa nhóm người đều có động cơ, những điều tra viên bất đắc dĩ phải tìm ra hung thủ đang ẩn mình.','96','2026-10-30','assets/uploads/movies/an-mang-karaoke-1-2026.webp','https://www.youtube.com/watch?v=UOjRGIatX38', IF('2026-10-30'>CURDATE(),1,2) WHERE NOT EXISTS (SELECT 1 FROM movies WHERE TRIM(title) IN ('Án Mạng Karaoke','Karaokill'));
SET @q4_movie_id = IF(ROW_COUNT()=1,LAST_INSERT_ID(),NULL);
INSERT INTO genres (name,status) SELECT 'Hài',1 WHERE @q4_movie_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM genres WHERE name='Hài');
INSERT INTO movie_genres (movie_id,genre_id) SELECT @q4_movie_id,genre_id FROM genres WHERE @q4_movie_id IS NOT NULL AND name='Hài' AND status=1;
INSERT INTO genres (name,status) SELECT 'Bí ẩn',1 WHERE @q4_movie_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM genres WHERE name='Bí ẩn');
INSERT INTO movie_genres (movie_id,genre_id) SELECT @q4_movie_id,genre_id FROM genres WHERE @q4_movie_id IS NOT NULL AND name='Bí ẩn' AND status=1;
INSERT INTO genres (name,status) SELECT 'Giật gân',1 WHERE @q4_movie_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM genres WHERE name='Giật gân');
INSERT INTO movie_genres (movie_id,genre_id) SELECT @q4_movie_id,genre_id FROM genres WHERE @q4_movie_id IS NOT NULL AND name='Giật gân' AND status=1;
INSERT INTO movie_images (movie_id,image_url,is_poster,status) SELECT @q4_movie_id,'assets/uploads/movies/an-mang-karaoke-1-2026.webp',1,1 WHERE @q4_movie_id IS NOT NULL;
-- Source: https://moveek.com/phim/godzilla-tru-khong/
INSERT INTO movies (title,director,actors,description,duration_min,release_date,poster_url,trailer_url,status) SELECT 'Godzilla Trừ Không','Takashi Yamazaki','Ryûnosuke Kamiki, Minami Hamabe, Yuki Yamada, Kuranosuke Sasaki, Jun Kunimura, Miou Tanaka','Nhật Bản đang cố gắng hồi phục sau chiến tranh và cuộc tấn công trước đó của Godzilla. Khi cuộc sống tưởng như bình yên trở lại, một mối đe dọa mới xuất hiện, đặt hy vọng của con người trước thử thách.','135','2026-11-06','assets/uploads/movies/godzilla-tru-khong-2026.webp','https://www.youtube.com/watch?v=-HhqsE1gK38', IF('2026-11-06'>CURDATE(),1,2) WHERE NOT EXISTS (SELECT 1 FROM movies WHERE TRIM(title) IN ('Godzilla Trừ Không','Godzilla Minus Zero (ゴジラ-0.0)'));
SET @q4_movie_id = IF(ROW_COUNT()=1,LAST_INSERT_ID(),NULL);
INSERT INTO genres (name,status) SELECT 'Hành động',1 WHERE @q4_movie_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM genres WHERE name='Hành động');
INSERT INTO movie_genres (movie_id,genre_id) SELECT @q4_movie_id,genre_id FROM genres WHERE @q4_movie_id IS NOT NULL AND name='Hành động' AND status=1;
INSERT INTO genres (name,status) SELECT 'Kinh dị',1 WHERE @q4_movie_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM genres WHERE name='Kinh dị');
INSERT INTO movie_genres (movie_id,genre_id) SELECT @q4_movie_id,genre_id FROM genres WHERE @q4_movie_id IS NOT NULL AND name='Kinh dị' AND status=1;
INSERT INTO genres (name,status) SELECT 'Khoa học viễn tưởng',1 WHERE @q4_movie_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM genres WHERE name='Khoa học viễn tưởng');
INSERT INTO movie_genres (movie_id,genre_id) SELECT @q4_movie_id,genre_id FROM genres WHERE @q4_movie_id IS NOT NULL AND name='Khoa học viễn tưởng' AND status=1;
INSERT INTO movie_images (movie_id,image_url,is_poster,status) SELECT @q4_movie_id,'assets/uploads/movies/godzilla-tru-khong-2026.webp',1,1 WHERE @q4_movie_id IS NOT NULL;
-- Source: https://moveek.com/phim/ngay-con-con-me/ | https://www.galaxycine.vn/dat-ve/ngay-con-con-me/
INSERT INTO movies (title,director,actors,description,duration_min,release_date,poster_url,trailer_url,status) SELECT 'Ngày Con Còn Mẹ','Vũ Ngọc Đãng','Phương Anh Đào, Hồng Đào, Ma Ran Đô, Nguyên Thảo, Trần Huy Anh','Mây lớn lên với cảm giác mẹ ưu ái anh trai hơn mình. Một biến cố khiến cô nhìn lại những hiểu lầm kéo dài và học cách thấu hiểu tình yêu mà mẹ dành cho các con.','110','2026-11-11','assets/uploads/movies/ngay-con-con-me-2026.webp','https://www.youtube.com/watch?v=LiOffO3x6nw', IF('2026-11-11'>CURDATE(),1,2) WHERE NOT EXISTS (SELECT 1 FROM movies WHERE TRIM(title) IN ('Ngày Con Còn Mẹ'));
SET @q4_movie_id = IF(ROW_COUNT()=1,LAST_INSERT_ID(),NULL);
INSERT INTO genres (name,status) SELECT 'Tâm lý',1 WHERE @q4_movie_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM genres WHERE name='Tâm lý');
INSERT INTO movie_genres (movie_id,genre_id) SELECT @q4_movie_id,genre_id FROM genres WHERE @q4_movie_id IS NOT NULL AND name='Tâm lý' AND status=1;
INSERT INTO genres (name,status) SELECT 'Gia đình',1 WHERE @q4_movie_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM genres WHERE name='Gia đình');
INSERT INTO movie_genres (movie_id,genre_id) SELECT @q4_movie_id,genre_id FROM genres WHERE @q4_movie_id IS NOT NULL AND name='Gia đình' AND status=1;
INSERT INTO movie_images (movie_id,image_url,is_poster,status) SELECT @q4_movie_id,'assets/uploads/movies/ngay-con-con-me-2026.webp',1,1 WHERE @q4_movie_id IS NOT NULL;
-- Source: https://moveek.com/phim/focker-in-law/ | https://www.bhdstar.vn/phim/gap-go-thong-gia-dau-moi-trinh-lang/
INSERT INTO movies (title,director,actors,description,duration_min,release_date,poster_url,trailer_url,status) SELECT 'Gặp Gỡ Thông Gia: Dâu Mới Trình Làng','John Hamburg','Robert De Niro, Ben Stiller, Ariana Grande, Skyler Gisondo, Owen Wilson, Teri Polo','Bạn gái của con trai Greg Focker ra mắt gia đình, kéo theo những phép thử lòng tin quen thuộc. Các thế hệ nhà Focker lại rơi vào một chuỗi tình huống hài hước khi đón thành viên mới.','120','2026-11-27','assets/uploads/movies/focker-in-law-2026.webp','https://www.youtube.com/watch?v=vyISuWUWcFs', IF('2026-11-27'>CURDATE(),1,2) WHERE NOT EXISTS (SELECT 1 FROM movies WHERE TRIM(title) IN ('Gặp Gỡ Thông Gia: Dâu Mới Trình Làng','Focker-In-Law'));
SET @q4_movie_id = IF(ROW_COUNT()=1,LAST_INSERT_ID(),NULL);
INSERT INTO genres (name,status) SELECT 'Hài',1 WHERE @q4_movie_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM genres WHERE name='Hài');
INSERT INTO movie_genres (movie_id,genre_id) SELECT @q4_movie_id,genre_id FROM genres WHERE @q4_movie_id IS NOT NULL AND name='Hài' AND status=1;
INSERT INTO genres (name,status) SELECT 'Tình cảm',1 WHERE @q4_movie_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM genres WHERE name='Tình cảm');
INSERT INTO movie_genres (movie_id,genre_id) SELECT @q4_movie_id,genre_id FROM genres WHERE @q4_movie_id IS NOT NULL AND name='Tình cảm' AND status=1;
INSERT INTO movie_images (movie_id,image_url,is_poster,status) SELECT @q4_movie_id,'assets/uploads/movies/focker-in-law-2026.webp',1,1 WHERE @q4_movie_id IS NOT NULL;
COMMIT;
