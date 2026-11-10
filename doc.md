# Quản lý đặt lịch sân bóng đá 7 người

Ứng dụng PHP thuần theo mô hình MVC, dùng MySQL và PDO.

## Cài đặt

1. Tạo database và các bảng bằng cách chạy `database.sql` trong MySQL/phpMyAdmin.
2. Cập nhật thông tin MySQL trong `commons/env.php` nếu máy của bạn không dùng cấu hình mặc định.
3. Mở dự án qua Laragon/Apache hoặc chạy máy chủ PHP tích hợp từ thư mục dự án:

   ```sh
   php -S localhost:8000
   ```

4. Truy cập `http://localhost:8000`.

## Chức năng hiện tại

- Xem lịch đặt theo ngày.
- Tạo lịch đặt sân, lưu thông tin khách hàng và giá tại thời điểm đặt.
- Ngăn đặt trùng giờ trên cùng một sân; thao tác kiểm tra và ghi lịch được khóa theo sân trong transaction.
- Hủy lịch chưa bắt đầu; lịch đã hủy không chiếm khung giờ.
- Dữ liệu mẫu gồm bốn sân; có thể sửa tên, mặt sân và giá trong `database.sql`.

Lịch hiện có thời lượng cố định 1 giờ, bắt đầu theo giờ tròn từ 06:00 đến 22:00. Bản MVP này dành cho quản lý nội bộ và chưa có đăng nhập/phân quyền.