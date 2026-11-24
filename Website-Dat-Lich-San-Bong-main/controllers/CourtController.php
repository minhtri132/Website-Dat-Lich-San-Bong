<?php
// Quản lý sân (chỉ admin)
class CourtController
{
    private ?CourtModel $model = null;

    public function index(): void
    {
        requireAdmin();

        $courts = [];
        $pageError = null;
        try {
            $courts = $this->model()->getAll();
        } catch (PDOException $exception) {
            error_log($exception->getMessage());
            $pageError = 'Không thể tải danh sách sân.';
        }

        render('admin/courts', ['courts' => $courts, 'pageError' => $pageError]);
    }

    // Form thêm mới (không có id) hoặc sửa (có id)
    public function form(): void
    {
        requireAdmin();

        $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
        $court = ['id' => '', 'name' => '', 'surface' => 'Cỏ nhân tạo', 'price_per_hour' => '', 'is_active' => 1];

        if ($id) {
            try {
                $found = $this->model()->find($id);
            } catch (PDOException $exception) {
                error_log($exception->getMessage());
                $found = null;
            }
            if (!$found) {
                setFlash('error', 'Không tìm thấy sân.');
                redirect('courts');
            }
            $court = $found;
        }

        render('admin/court_form', ['court' => $court, 'errors' => []]);
    }

    public function save(): void
    {
        requireAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('courts');
        }

        $errors = [];
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT) ?: 0;
        $name = trim((string) ($_POST['name'] ?? ''));
        $surface = trim((string) ($_POST['surface'] ?? ''));
        $priceValue = filter_var($_POST['price_per_hour'] ?? null, FILTER_VALIDATE_INT);
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        $court = [
            'id' => $id ?: '',
            'name' => $name,
            'surface' => $surface,
            'price_per_hour' => $_POST['price_per_hour'] ?? '',
            'is_active' => $isActive,
        ];

        try {
            assertCsrf();

            if (!preg_match('/^.{1,100}$/u', $name)) {
                $errors[] = 'Tên sân không được để trống và tối đa 100 ký tự.';
            }
            if (!preg_match('/^.{1,100}$/u', $surface)) {
                $errors[] = 'Loại mặt sân không được để trống và tối đa 100 ký tự.';
            }
            if ($priceValue === false || $priceValue < 1000 || $priceValue > 100000000) {
                $errors[] = 'Giá thuê phải là số nguyên từ 1.000 đ trở lên.';
            }

            if (!$errors) {
                if ($id) {
                    $this->model()->update($id, $name, $surface, $priceValue, $isActive);
                    setFlash('success', 'Đã cập nhật sân.');
                } else {
                    $this->model()->create($name, $surface, $priceValue, $isActive);
                    setFlash('success', 'Đã thêm sân mới.');
                }
                redirect('courts');
            }
        } catch (DomainException $exception) {
            $errors[] = $exception->getMessage();
        } catch (PDOException $exception) {
            // Mã 23000 = vi phạm ràng buộc (tên sân bị trùng)
            if ($exception->getCode() === '23000') {
                $errors[] = 'Tên sân đã tồn tại, hãy chọn tên khác.';
            } else {
                error_log($exception->getMessage());
                $errors[] = 'Không thể lưu sân. Vui lòng thử lại.';
            }
        }

        render('admin/court_form', ['court' => $court, 'errors' => $errors]);
    }

    public function toggle(): void
    {
        requireAdmin();
        $this->handlePost(function (int $id) {
            $this->model()->toggleActive($id);
            return 'Đã đổi trạng thái hoạt động của sân.';
        });
    }

    public function delete(): void
    {
        requireAdmin();
        $this->handlePost(function (int $id) {
            if ($this->model()->hasBookings($id)) {
                throw new DomainException('Sân đã có lịch đặt nên không thể xóa. Hãy chuyển sang "Ngừng hoạt động".');
            }
            $this->model()->delete($id);
            return 'Đã xóa sân.';
        });
    }

    // Dùng chung cho các thao tác POST theo id (toggle, delete)
    private function handlePost(callable $action): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('courts');
        }

        try {
            assertCsrf();
            $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
            if (!$id) {
                throw new DomainException('Sân không hợp lệ.');
            }
            setFlash('success', $action($id));
        } catch (DomainException $exception) {
            setFlash('error', $exception->getMessage());
        } catch (PDOException $exception) {
            error_log($exception->getMessage());
            setFlash('error', 'Không thể thực hiện thao tác. Vui lòng thử lại.');
        }

        redirect('courts');
    }

    private function model(): CourtModel
    {
        if ($this->model === null) {
            $this->model = new CourtModel();
        }
        return $this->model;
    }
}