Hệ thống quản lý và đặt lịch sân bóng
Ứng dụng web hỗ trợ khách hàng đăng ký, đăng nhập và gửi yêu cầu đặt
lịch sân bóng; đồng thời hỗ trợ quản trị viên quản lý sân, lịch đặt và
tài khoản người dùng.
Tên thư mục dự án: mvc-oop-basic-duanmu
Công nghệ nhận diện từ cấu trúc mã nguồn: PHP (MVC/OOP), MySQL.
Các thông tin như tên database, phiên bản PHP và tài khoản demo cần
được cập nhật theo cấu hình thực tế của nhóm.

1. Chức năng chính
Khách hàng
- Đăng ký tài khoản.
- Đăng nhập và đăng xuất.
- Xem thông tin sân bóng.
- Gửi yêu cầu đặt lịch sân.
Quản trị viên
- Xem trang tổng quan.
- Quản lý sân bóng.
- Theo dõi và quản lý lịch đặt sân.
- Quản lý tài khoản người dùng.
2. Công nghệ sử dụng
  Thành phần              Công nghệ
  Backend                 PHP, lập trình hướng đối tượng (OOP)
Kiến trúc               MVC (Model - View - Controller)
Cơ sở dữ liệu           MySQL
Môi trường chạy local   Laragon
Trình soạn thảo         Visual Studio Code
3. Cấu trúc thư mục
mvc-oop-basic-duanmu/
├── commons/          # Thành phần dùng chung
├── controllers/      # Xử lý yêu cầu và nghiệp vụ
├── models/           # Làm việc với dữ liệu
├── uploads/          # Tệp tải lên
├── views/            # Giao diện
│   ├── admin/        # Giao diện quản trị
│   ├── auth/         # Đăng nhập, đăng ký
│   ├── booking/      # Giao diện đặt sân
│   └── layout/       # Header, footer và layout
├── database.sql      # Cấu trúc/dữ liệu khởi tạo database
├── doc.md            # Tài liệu dự án
└── index.php         # Điểm vào ứng dụng
4. Yêu cầu môi trường
- Windows (nếu chạy bằng Laragon).
- Laragon hoặc môi trường Apache/PHP tương đương.
- PHP tương thích với source code.
- MySQL.
- Trình duyệt web.
5. Cài đặt và chạy dự án
Bước 1: Lấy source code
git clone <GITHUB_REPOSITORY_URL>
Hoặc giải nén source code vào thư mục web root của Laragon, ví dụ:
C:\laragon\www\mvc-oop-basic-duanmu
Bước 2: Khởi động dịch vụ
Mở Laragon và khởi động Apache cùng MySQL.
Bước 3: Tạo cơ sở dữ liệu
1. Mở phpMyAdmin hoặc công cụ quản lý MySQL.
2. Tạo database theo tên được cấu hình trong source code.
3. Import file database.sql vào database vừa tạo.
Tên database phải khớp với cấu hình kết nối trong dự án.

Bước 4: Cấu hình kết nối database
Tìm file cấu hình kết nối cơ sở dữ liệu trong source code và cập nhật:
- Host, thường là localhost.
- Port, thường là 3306.
- Tên database.
- Username MySQL.
- Password MySQL.
Không commit mật khẩu thật hoặc thông tin nhạy cảm lên GitHub.
Bước 5: Truy cập website
Nếu dự án nằm trong C:\laragon\www\mvc-oop-basic-duanmu, thử truy cập:
http://localhost/mvc-oop-basic-duanmu/
Đường dẫn thực tế phụ thuộc vào cấu hình Virtual Host và thư mục cài đặt
Laragon.
6. Cơ sở dữ liệu
File database.sql chứa phần khởi tạo cơ sở dữ liệu. Các nhóm dữ liệu
chính thể hiện qua cấu trúc mã nguồn gồm:
- Người dùng (UserModel).
- Sân bóng (CourtModel).
- Đặt lịch (BookingModel).
Xem trực tiếp database.sql để biết chính xác tên bảng, trường dữ liệu,
khóa chính và khóa ngoại.
7. Làm việc với Git
Lấy code mới nhất:
git pull
Kiểm tra thay đổi:
git status
Thêm và commit thay đổi:
git add .
git commit -m "Mo ta noi dung cap nhat"
Đẩy code lên GitHub:
git push
8. Thành viên thực hiện
  Họ và tên                 Vai trò
  Cập nhật tên thành viên   Phân tích / phát triển / kiểm thử
9. Hướng phát triển
- Tìm kiếm và lọc sân theo ngày, giờ và mức giá.
- Hoàn thiện kiểm tra trùng lịch đặt sân.
- Bổ sung quản lý trạng thái đặt sân.
- Tích hợp thanh toán trực tuyến.
- Bổ sung thông báo xác nhận và nhắc lịch.
- Hoàn thiện báo cáo doanh thu và thống kê.
10. Ghi chú
README này được xây dựng dựa trên cấu trúc thư mục và các tệp nhìn thấy
trong dự án. Trước khi công bố repository, nhóm nên cập nhật tên
database, cấu hình, tài khoản demo, danh sách thành viên và các chức
năng đã hoàn thiện thực tế.