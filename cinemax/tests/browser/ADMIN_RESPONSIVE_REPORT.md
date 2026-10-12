# Kiểm tra Responsive Admin Cinemax

Đã sửa trực tiếp giao diện Admin đang chạy tại `http://localhost/cinemax/cinemax/views/admin/index.php`. Giữ theme tối và màu Cinemax; không sửa controller, service, model, database, phân quyền hoặc thao tác nghiệp vụ. Không commit/push Git.

## Nguyên nhân và cách sửa

| Nguyên nhân thực tế | Cách sửa |
|---|---|
| Sidebar cũ bị đẩy sang trái ở breakpoint nhỏ, thiếu cơ chế mở. Bản sửa trước sử dụng breakpoint 1024px nên chưa bao phủ tablet 1025–1199px. | Thống nhất CSS/JS ở 1200px. Desktop dùng Grid với sidebar 260px; tablet/mobile dùng drawer, overlay, nút đóng, Escape và quản lý focus. Hamburger giữ trên màn hình khi cuộn. |
| Grid/Flex có kích thước tối thiểu theo nội dung; một số thanh thao tác không xuống dòng. | `minmax(0, 1fr)`, `min-width: 0`, wrapping, padding co giãn và kiểm soát chiều rộng từng component. |
| `overflow-x: hidden` ở body, admin-main, dashboard-content và trang phim che lỗi tràn. | Admin bỏ việc che tràn toàn trang. Bảng, lịch và sơ đồ ghế có vùng cuộn riêng, hỗ trợ bàn phím. |
| CSS modal đặt vé ghi đè modal chung bằng chiều rộng 600px. CSS quyền, phim và ghế được nạp cả ở trang khác. | Nạp CSS theo trang, bỏ định nghĩa modal đặt vé trùng lặp và giới hạn selector thẻ quyền. |
| Trang đặt vé ép 4 cột thống kê bằng inline CSS. | Bỏ inline CSS, dùng component thống kê chung: 4 cột ở màn hình rộng, 2 cột trung bình, 1 cột dưới 768px. |
| Modal dài có `max-height` chưa trừ padding của overlay. | Chiều cao tối đa tính từ viewport và padding thực tế; form dài cuộn được đến nút lưu/hủy. |
| Trang 404 lồng một tài liệu HTML hoàn chỉnh vào Admin và SVG rộng cố định. | Giữ nội dung 404 trong layout chung, nạp CSS trong head và cho SVG co giãn. |
| Thư viện Chart.js từ CDN không tải được trong môi trường kiểm thử. | Khi mất thư viện, Dashboard hiển thị thông báo thay vì phát sinh lỗi JavaScript. Khi CDN tải được, vẫn dùng biểu đồ hiện tại. |

## File sửa/thêm trong lượt rà soát toàn Admin

| File | Thay đổi |
|---|---|
| `views/admin/index.php` | Thêm class `admin-shell` để giới hạn CSS cho Admin. |
| `views/admin/partials/head.php` | CSS theo trang, CSS responsive chung, tái sử dụng pagination.css và cache version. |
| `views/admin/pages/bookings.php` | Bỏ inline Grid 4 cột. |
| `views/admin/pages/dashboard.php` | Thông báo khi thư viện biểu đồ không tải được. |
| `views/admin/pages/404.php` | Bỏ html/head/body lồng trong layout. |
| `public/assets/css/admin.css` | Layout/sidebar/breakpoint, hamburger sticky, bỏ overflow che lỗi và media query sidebar cũ. |
| `public/assets/css/admin-responsive.css` | Component responsive chung: header, thẻ, bộ lọc, bảng, form, modal, phân trang, sơ đồ ghế, 404. |
| `public/assets/css/bookings_admin.css` | Bỏ modal chung trùng lặp có width 600px. |
| `public/assets/css/movies_admin.css` | Bỏ overflow-x hidden che lỗi. |
| `public/assets/css/permission.css` | Giới hạn style thẻ quyền trong trang permissions. |
| `public/assets/js/admin-layout.js` | Breakpoint 1200px, focus khi resize, vùng cuộn truy cập bằng bàn phím. |
| `tests/browser/admin-responsive.js` | Kiểm thử Playwright trên dữ liệu thật, không gửi form hoặc ghi dữ liệu. |

Các component nút menu, overlay và nút đóng trong `index.php`/`partials/sidebar.php`, cùng các chỉnh sửa lịch chiếu/Dashboard từ lượt trước được tái sử dụng.

## Kết quả kiểm thử

Playwright chạy Microsoft Edge headless trên các trang thực tế: Dashboard, phim, suất chiếu, phòng, rạp, người dùng, đặt vé, combo, khuyến mãi, thể loại, vai trò, quyền, loại ghế, sơ đồ ghế và 404.

| Kiểm tra | Kết quả |
|---|---|
| 320, 375, 430, 768, 1024, 1280, 1440, 1920px | Đạt trên 15 trang. |
| Zoom 80%, 100%, 125% | Mô phỏng bằng CSS viewport và device scale qua CDP để kích hoạt đúng breakpoint. Không thao tác zoom native trên thanh công cụ trình duyệt. |
| Bố cục, lịch ngày/tuần, resize liên tục | 384 lượt kiểm tra đạt, không có tràn ngang toàn trang hoặc sidebar đè nội dung. |
| 23 modal, mỗi modal 8 chiều rộng × 3 mức zoom | 552 lượt kiểm tra đạt; cuộn đến footer, nút thao tác không bị cắt. |
| Lịch có suất chiếu thật, lịch ngày/tuần, popup sửa | Thêm 72 lượt kiểm tra đạt. |
| Sidebar | Click được 13 liên kết trang quản trị tại 375px; nút đóng, overlay, Escape và giữ focus hoạt động. Không click đăng xuất. |
| Drawer đang mở rồi chuyển desktop/mobile | Reset đúng, không để nội dung bị inert hoặc overlay kẹt. |
| Bảng quản trị | Cuộn được tới cột cuối trong vùng bảng; vùng cuộn nhận focus bằng bàn phím. |
| PHP/JavaScript | Các file PHP thay đổi qua `php -l`; JavaScript qua `node --check`. Lượt Playwright cuối không có lỗi JavaScript. |

Tổng cộng 1.008 lượt kiểm tra hình học/bố cục đạt. Xem dữ liệu trong `admin-responsive-results.json` và `admin-responsive-extra-results.json`; ảnh `admin-*.png` lưu cùng thư mục. Kiểm tra ảnh thực tế gồm Dashboard desktop/mobile, sơ đồ ghế mobile, drawer mobile và lịch chiếu mobile.

Giới hạn: CDN Chart.js không truy cập được nên chưa xác nhận hình vẽ biểu đồ khi thư viện tải thành công; giao diện fallback đã kiểm tra. Tài khoản/dữ liệu hiện tại không có nút sửa quyền khả dụng; cả hai form quyền vẫn được kiểm tra bố cục bằng cách mở modal. Không kiểm thử gửi/lưu/xóa dữ liệu vì phạm vi sửa là giao diện.

Chạy lại: `node cinemax/tests/browser/admin-responsive.js` từ thư mục workspace, với Apache/MySQL của XAMPP đang chạy và có tài khoản Admin hoạt động. Token kiểm thử chỉ giữ trong bộ nhớ; không ghi vào báo cáo.
