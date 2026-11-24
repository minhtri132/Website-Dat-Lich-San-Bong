<?php
class ExtraController
{
    private ?ExtraModel $model = null;

    public function index(): void
    {
        requireAdmin();
        $extras = [];
        $pageError = null;
        try {
            $extras = $this->model()->getAll();
        } catch (PDOException $exception) {
            error_log($exception->getMessage());
            $pageError = 'Không thể tải danh sách option.';
        }
        render('admin/extras', ['extras' => $extras, 'pageError' => $pageError]);
    }

    public function form(): void
    {
        requireAdmin();
        $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
        $extra = [
            'id' => '',
            'category' => 'water',
            'name' => '',
            'price' => '',
            'is_active' => 1,
        ];

        if ($id) {
            try {
                $found = $this->model()->find($id);
            } catch (PDOException $exception) {
                error_log($exception->getMessage());
                $found = null;
            }
            if (!$found) {
                setFlash('error', 'Không tìm thấy option.');
                redirect('extras');
            }
            $extra = $found;
        }

        render('admin/extra_form', ['extra' => $extra, 'errors' => []]);
    }

    public function save(): void
    {
        requireAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('extras');
        }

        $errors = [];
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT) ?: 0;
        $category = $_POST['category'] ?? '';
        $name = trim((string) ($_POST['name'] ?? ''));
        $price = filter_var($_POST['price'] ?? null, FILTER_VALIDATE_INT);
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $extra = [
            'id' => $id ?: '',
            'category' => $category,
            'name' => $name,
            'price' => $_POST['price'] ?? '',
            'is_active' => $isActive,
        ];

        try {
            assertCsrf();
            if (!in_array($category, ['water', 'gloves', 'shoes'], true)) {
                $errors[] = 'Loại option không hợp lệ.';
            }
            if (!preg_match('/^.{1,100}$/u', $name)) {
                $errors[] = 'Tên option không được để trống và tối đa 100 ký tự.';
            }
            if ($price === false || $price < 1000 || $price > 100000000) {
                $errors[] = 'Giá phải là số nguyên từ 1.000 đ trở lên.';
            }

            if (!$errors) {
                if ($id) {
                    $this->model()->update($id, $category, $name, $price, $isActive);
                    setFlash('success', 'Đã cập nhật option.');
                } else {
                    $this->model()->create($category, $name, $price, $isActive);
                    setFlash('success', 'Đã thêm option mới.');
                }
                redirect('extras');
            }
        } catch (DomainException $exception) {
            $errors[] = $exception->getMessage();
        } catch (PDOException $exception) {
            error_log($exception->getMessage());
            $errors[] = 'Không thể lưu option. Vui lòng thử lại.';
        }

        render('admin/extra_form', ['extra' => $extra, 'errors' => $errors]);
    }

    public function toggle(): void
    {
        requireAdmin();
        $this->handlePost(function (int $id) {
            $this->model()->toggleActive($id);
            return 'Đã đổi trạng thái option.';
        });
    }

    public function delete(): void
    {
        requireAdmin();
        $this->handlePost(function (int $id) {
            if ($this->model()->hasOrders($id)) {
                throw new DomainException('Option đã được khách mua nên không thể xóa. Hãy chuyển sang "Ngừng hoạt động".');
            }
            $this->model()->delete($id);
            return 'Đã xóa option.';
        });
    }

    private function handlePost(callable $action): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('extras');
        }
        try {
            assertCsrf();
            $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
            if (!$id) {
                throw new DomainException('Option không hợp lệ.');
            }
            setFlash('success', $action($id));
        } catch (DomainException $exception) {
            setFlash('error', $exception->getMessage());
        } catch (PDOException $exception) {
            error_log($exception->getMessage());
            setFlash('error', 'Không thể thực hiện thao tác. Vui lòng thử lại.');
        }
        redirect('extras');
    }

    private function model(): ExtraModel
    {
        if ($this->model === null) {
            $this->model = new ExtraModel();
        }
        return $this->model;
    }
}
