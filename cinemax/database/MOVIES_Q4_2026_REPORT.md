# Cinemax — dữ liệu phim tháng 10–12/2026

Đối chiếu ngày 12/10/2026. Đã nhập trực tiếp **13 phim mới**: 10 phim khởi chiếu tháng 10 và 3 phim tháng 11. Có **7 phim chờ thời lượng**, chưa nhập vào `movies`. Vì vậy chưa đạt mục tiêu khoảng 20 phim trong database; không dùng số phút giả để lấp dữ liệu.

## Cấu trúc thực tế

- Kết nối: `config/dbConfig.php`, MariaDB tại localhost:3306, database `cinemax`, utf8mb4.
- `movies`: `movie_id` AUTO_INCREMENT, `title`, `director`, `actors`, `description`, `duration_min`, `release_date`, `poster_url`, `trailer_url`, `status`, `created_at`.
- `duration_min`: INT NOT NULL và CHECK > 0; không thể lưu thời lượng chưa biết bằng SQL NULL. Không thay schema.
- Thể loại: `genres`, bảng liên kết `movie_genres`. Poster được ghi vào `movies.poster_url` và `movie_images`.
- Luồng phim: `models/Movie.php` → `services/MovieService.php` → `controllers/MovieController.php`; admin POST create/update/delete qua `views/admin/index.php`. Không phát hiện API REST phim riêng trong `public/index.php` (router này xử lý xác thực).
- Admin: `views/admin/pages/movies.php`, form thêm/sửa, upload poster và cập nhật trailer đã có sẵn.
- User: `views/user/pages/home.php`, `movies.php`, `movie_detail.php`, `search_results.php`.
- Ảnh: file trong `public/assets/uploads/movies/`, DB dùng đường dẫn `assets/uploads/movies/<ten-phim>-2026.webp`.
- Trạng thái: 0 ẩn, 1 sắp chiếu, 2 đang chiếu, 3 ngừng chiếu. `Movie::syncStatuses()` chỉ tự cập nhật 1/2 theo ngày phát hành. Giữ quy tắc này theo yêu cầu giữ kiến trúc; không suy diễn tất cả phim đã phát hành đều đang chiếu. Với dữ liệu mới, chỉ 7 phim tháng 10 đã ra mắt được phân loại 2; các phim tương lai có trạng thái 1.
- Không tạo suất chiếu, phòng, ghế hay giá vé. Phim mới có thể hiện “chưa có lịch chiếu” ở Cinemax cho tới khi quản trị viên xếp lịch.

## 10 phim tháng 10 đã nhập

Ngày dưới đây là lịch Việt Nam trên nguồn tham khảo, không phải ngày quốc tế. Bảy phim đầu đã ra mắt; ba phim cuối còn sắp chiếu tại ngày đối chiếu.

| Phim | Ngày Việt Nam | Phút | Trạng thái | Nguồn |
|---|---|---:|---|---|
| Quyết Cua Anh Này | 02/10/2026 | 115 | Đang chiếu | [Moveek](https://moveek.com/phim/quyet-cua-anh-nay/) |
| Scotty Giải Cứu Hoàng Thượng | 02/10/2026 | 80 | Đang chiếu | [Moveek](https://moveek.com/phim/scotty-giai-cuu-hoang-thuong/) |
| Thần Sư Chung Quỳ: Linh Giới Đại Chiến | 02/10/2026 | 96 | Đang chiếu | [Moveek](https://moveek.com/phim/than-su-chung-quy-linh-gioi-dai-chien/) |
| Trái Tim Quái Thú | 02/10/2026 | 101 | Đang chiếu | [Moveek](https://moveek.com/phim/trai-tim-quai-thu/) |
| Blue Lock: Cuộc Chiến Của Những Tiền Đạo | 09/10/2026 | 127 | Đang chiếu | [Moveek](https://moveek.com/phim/blue-lock-cuoc-chien-cua-nhung-tien-dao/) |
| Người Mẹ Khác | 09/10/2026 | 93 | Đang chiếu | [Moveek](https://moveek.com/phim/nguoi-me-khac/) |
| Án Mạng Xém Hoàn Hảo | 09/10/2026 | 124 | Đang chiếu | [Moveek](https://moveek.com/phim/an-mang-xem-hoan-hao/) |
| Quỷ Ăn Tạng 4: Hổ Tinh | 16/10/2026 | 111 | Sắp chiếu | [Moveek](https://moveek.com/phim/quy-an-tang-4-ho-tinh/) |
| Shaun the Sheep: Quái Lạ Ghé Nhà | 16/10/2026 | 81 | Sắp chiếu | [Moveek](https://moveek.com/phim/shaun-the-sheep-quai-la-ghe-nha/) |
| Án Mạng Karaoke | 30/10/2026 | 96 | Sắp chiếu | [Moveek](https://moveek.com/phim/an-mang-karaoke-1/) |

Blue Lock trong danh sách là **live action**, không phải anime. Thần Sư Chung Quỳ là hoạt hình Trung Quốc; không đổi nhãn thành anime Nhật.

Một số trailer vẫn ghi lịch ban đầu. Lịch cập nhật trên trang phim được ưu tiên: Trái Tim Quái Thú 02/10 thay vì 25/09; Quỷ Ăn Tạng 4 ngày 16/10 thay vì 09/10; Án Mạng Karaoke ngày 30/10 thay vì 02/10. [Bài cập nhật lịch Việt Nam ngày 08/10](https://moveek.com/bai-viet/phim-chieu-rap-thang-10-2026-trai-buon-nguoi-khien-nhieu-phim-viet-doi-lich-lien-tuc/34544) xác nhận việc dời lịch các phim Việt và Quỷ Ăn Tạng 4.

## 10 phim tháng 11–12 đã nghiên cứu

Đây là lịch dự kiến tại Việt Nam được nguồn trong nước công bố, có thể thay đổi. Không coi là cam kết phát hành bất biến.

| Phim | Ngày Việt Nam | Phút | Kết quả | Nguồn |
|---|---|---:|---|---|
| Godzilla Trừ Không | 06/11/2026 | 135 | Đã nhập — Sắp chiếu | [Moveek](https://moveek.com/phim/godzilla-tru-khong/) |
| Bò Sữa Bay | 06/11/2026 | Chưa công bố | Chờ thời lượng | [CGV](https://www.cgv.vn/default/bo-sua-bay.html) |
| Chuyến Đi Nhớ Đời | 06/11/2026 | Chưa công bố | Chờ thời lượng | [Moveek](https://moveek.com/phim/chuyen-di-nho-doi/) |
| Ngày Con Còn Mẹ | 11/11/2026 | 110 | Đã nhập — Sắp chiếu | [Moveek](https://moveek.com/phim/ngay-con-con-me/) |
| Trại Giam Hạnh Phúc | 20/11/2026 | Chưa công bố | Chờ thời lượng | [Galaxy](https://www.galaxycine.vn/phim/trai-giam-hanh-phuc/) |
| Gặp Gỡ Thông Gia: Dâu Mới Trình Làng | 27/11/2026 | 120 | Đã nhập — Sắp chiếu | [BHD](https://www.bhdstar.vn/phim/gap-go-thong-gia-dau-moi-trinh-lang/) |
| Con Mắt Thứ Ba | 04/12/2026 | Chưa công bố | Chờ thời lượng | [Moveek](https://moveek.com/phim/con-mat-thu-ba/) |
| Avengers: Ngày Tận Thế | 18/12/2026 | Chưa công bố | Chờ thời lượng | [Moveek](https://moveek.com/phim/avengers-doomsday/) |
| Dune: Hành Tinh Cát - Phần 3 | 18/12/2026 | Chưa công bố | Chờ thời lượng | [Galaxy](https://www.galaxycine.vn/phim/dune-3/) |
| Người Mẹ Xấu | 31/12/2026 | Chưa công bố | Chờ thời lượng | [Moveek](https://moveek.com/phim/nguoi-me-xau/) |

Thời lượng 110 phút của Ngày Con Còn Mẹ lấy từ Moveek; Galaxy còn ghi 0 phút (được hiểu là chưa công bố, không nhập 0). Thời lượng 120 phút của Gặp Gỡ Thông Gia lấy từ bản trang BHD được công cụ tìm kiếm trả về; yêu cầu tải trực tiếp trang BHD trả 403 nên không vượt hạn chế đó. Thời lượng này nên được đối chiếu lại gần ngày phát hành.

## Poster, trailer và dữ liệu thiếu

- **20 poster được chọn tải thành công**, định dạng WebP, kích thước 400×600, tỷ lệ 2:3. Đã kiểm tra MIME, giải mã ảnh, kích thước, SHA-256 và HTTP 200 cho từng file. Poster từ CDN công khai được trang phim Moveek chỉ rõ, không tạo AI, không dùng placeholder.
- **20 trailer xác minh bằng YouTube oEmbed**: tên phim và kênh chính thức CGV Cinemas Vietnam, Galaxy Studio, Galaxy Cinema (Official), BHD Star Cineplex, STUDIOCANAL hoặc Universal Pictures. Chỉ dùng link video watch với ID 11 ký tự; không dùng link kết quả tìm kiếm/Bing.
- **13 trailer đã lưu DB**, 7 trailer còn lại lưu trong hồ sơ chờ. Trailer/teaser/first-look được phân biệt bằng tên video trong JSON; không gọi mọi video là main trailer.
- Hồ sơ `movies_q4_2026.json` chứa tên quốc tế khi nguồn cung cấp, đạo diễn, diễn viên chính, tóm tắt tiếng Việt đã viết lại, thể loại, ngày Việt Nam, nguồn poster, tên/kênh trailer và lý do chờ. Tên quốc tế không được tự nhận là tên gốc theo ngôn ngữ bản địa; schema không có cột tên gốc nên giữ ở hồ sơ đối chiếu.
- Bảy phim chờ chỉ thiếu thời lượng bắt buộc cho schema. Không gán 90/120 phút mặc định và không tự đổi schema để nhập.
- **Quyền ảnh:** chưa tìm thấy giấy phép công khai cho việc tái sử dụng thương mại. Việc ảnh tải được không chứng minh quyền sử dụng. Hồ sơ ghi rõ nguồn và tình trạng quyền; cần xác nhận với chủ sở hữu/nhà phát hành trước khi sử dụng công khai cho hoạt động thương mại.

## Thay đổi và nhập lại

Không sửa source của các module đang chạy. Các file mới phục vụ dữ liệu và kiểm tra:

- `database/movies_q4_2026.json`: hồ sơ 20 phim.
- `database/import_movies_q4_2026.php`: công cụ CLI, prepared statements, transaction, backup, AUTO_INCREMENT, tránh trùng theo tên và tên quốc tế; giữ phim cũ.
- `database/seed_movies_q4_2026.sql`: seed 13 phim đủ điều kiện, dùng được với phpMyAdmin. Đã thử chạy lại sau nhập, không tạo trùng.
- `database/verify_movies_q4_2026.php`: kiểm tra dữ liệu, model user/admin, genre, bảo toàn dữ liệu cũ, render template admin.
- `database/research-2026-q4/`: nguồn đối chiếu, công cụ thu thập, kết quả nhập/kiểm tra và backup trước nhập.
- `public/assets/uploads/movies/*-2026.webp`: 20 ảnh cho danh sách được chọn.

Lệnh chạy từ thư mục ngoài cùng của dự án:

```powershell
C:/xampp/php/php.exe cinemax/database/import_movies_q4_2026.php
C:/xampp/php/php.exe cinemax/database/import_movies_q4_2026.php --apply
C:/xampp/php/php.exe cinemax/database/verify_movies_q4_2026.php --render-admin
C:/xampp/php/php.exe cinemax/database/research-2026-q4/verify-http.php
node cinemax/database/research-2026-q4/verify-admin-js.js
```

Lệnh mặc định chỉ kiểm tra, `--apply` mới ghi DB. Sau khi thời lượng được nguồn đáng tin xác nhận, cập nhật `duration_min`, thêm nguồn và ngày đối chiếu, bỏ lý do chờ, đặt `import_ready=true` trong JSON rồi chạy lại importer. Không cần tự đặt movie_id. Công cụ sẽ bỏ qua 13 phim đã có và giữ backup riêng cho đợt bổ sung.

## Kết quả test và giới hạn

- PHP syntax: **96 file, 0 lỗi**.
- DB: 13 phim mới đúng trường, ngày, trạng thái, genre, poster và trailer; không có tên phim trùng.
- Chạy lại PHP importer và seed SQL: không nhập trùng.
- Bảo toàn: **8 phim cũ**, các thể loại/liên kết/ảnh cũ giữ nguyên; số dòng `shows`, `tickets`, `bills` không đổi.
- User: trang chủ và hai bộ lọc HTTP 200; 13 trang chi tiết HTTP 200, đúng tên, ngày, poster và iframe; 20 poster HTTP 200 và nội dung đúng hash.
- Admin: cả 13 phim mới có trong model; template render có nút sửa và trường thay poster/cập nhật trailer. **21 đoạn JavaScript/handler mở form sửa parse thành công**.
- Không có trình duyệt tương tác trong phiên này: chưa chạy phát video YouTube, bấm lưu form admin hoặc upload ảnh qua trình duyệt. Không báo các thao tác đó đã test thành công.

## Cần thực hiện thủ công

1. Xem lại giao diện và phát trailer trong trình duyệt; đăng nhập admin thử sửa một phim mới, đổi poster rồi lưu.
2. Xác minh thời lượng 7 phim chờ và cập nhật hồ sơ theo hướng dẫn. Chưa có phim tháng 12 mới được nhập vì toàn bộ nhóm tháng 12 được chọn chưa công bố thời lượng trên các nguồn đã đối chiếu.
3. Xác nhận quyền sử dụng poster nếu website triển khai công khai cho hoạt động thương mại.
4. Chỉ xếp suất chiếu/phòng/giá vé khi có kế hoạch vận hành thực tế; đợt bổ sung này không tự tạo lịch.

Không cần import SQL thêm trên máy hiện tại: database đã được cập nhật trực tiếp. Không git commit hoặc push.
