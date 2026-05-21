Bước 1: Giao diện (View) gửi yêu cầu
Vị trí code: views/rooms/index.php

Khi bạn nhấn nút "Lưu thay đổi", thẻ <form> trong Modal sửa phòng sẽ gửi một yêu cầu POST kèm theo các dữ liệu bạn đã nhập (mã phòng, tên, mô tả...) lên Server.
Yêu cầu này thường nhắm tới URL có dạng: ?page=rooms&action=edit&id=123.
Bước 2: Bộ điều phối (Entry Point) tiếp nhận
Vị trí code: index.php (ở thư mục gốc)

File index.php đóng vai trò là "lễ tân". Nó nhìn vào biến ?page=rooms trên URL và chuyển hướng xử lý sang file views/rooms/index.php.
Tại đầu file views/rooms/index.php, có đoạn code kiểm tra hành động:
php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'edit') $ctrl->update($id);
}
Nó sẽ gọi hàm update($id) của lớp RoomController.
Bước 3: Bộ điều khiển (Controller) xử lý logic
Vị trí code: controllers/RoomController.php

Tại đây, hàm update($id) sẽ làm các việc:
Nhận dữ liệu từ mảng $_POST['description'], $_POST['name']...
Kiểm tra dữ liệu (ví dụ: tên phòng không được để trống).
Gọi Model để yêu cầu ghi file vào CSDL: $this->model->update($id, $data);.
Tạo thông báo thành công ("Cập nhật phòng thành công!") và điều hướng người dùng quay trở lại trang danh sách.
Bước 4: Mô hình (Model) ghi vào CSDL
Vị trí code: models/RoomModel.php

Hàm update($id, $data) trong Model sẽ thực thi câu lệnh SQL thực sự:
sql
UPDATE rooms SET room_code=?, name=?, description=?, ... WHERE id=?
Dữ liệu "Mô tả" mới của bạn chính thức được ghi xuống ổ cứng trong Database MySQL.
Bước 5: Phản hồi kết quả
Sau khi lưu xong, Controller yêu cầu trình duyệt tải lại trang.
Lúc này, View (views/rooms/index.php) sẽ lại gọi Model để lấy dữ liệu mới nhất từ CSDL và hiển thị mô tả mới lên màn hình mà bạn thấy.
Tóm tắt luồng di chuyển:

Browser (Bạn nhấn Lưu)
→ index.php (Điều hướng)
→ RoomController.php (Xử lý logic mượn/trả/lưu)
→ RoomModel.php (Ghi vào MySQL)
→ Database (Lưu trữ vĩnh viễn)
→ Trả ngược lại giao diện để bạn thấy kết quả.