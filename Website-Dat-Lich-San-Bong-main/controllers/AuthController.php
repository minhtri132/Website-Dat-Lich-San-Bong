<?php
class AuthController
{
    private ?UserModel $model = null;

    public function register(): void
    {
        if (currentUser()) {
            redirect('/');
        }

        $errors = [];
        $old = ['full_name' => '', 'phone' => '', 'email' => ''];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                assertCsrf();

                $fullName = trim((string) ($_POST['full_name'] ?? ''));
                $phone = preg_replace('/[\s().-]/', '', trim((string) ($_POST['phone'] ?? '')));
                $email = strtolower(trim((string) ($_POST['email'] ?? '')));
                $password = (string) ($_POST['password'] ?? '');
                $confirm = (string) ($_POST['password_confirm'] ?? '');
                $old = ['full_name' => $fullName, 'phone' => $phone, 'email' => $email];

                if (!preg_match('/^.{2,100}$/u', $fullName)) {
                    $errors[] = 'Họ tên phải từ 2 đến 100 ký tự.';
                }
                if (!preg_match('/^\+?\d{9,15}$/D', $phone)) {
                    $errors[] = 'Số điện thoại phải có từ 9 đến 15 chữ số.';
                }
                if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
                    $errors[] = 'Email không hợp lệ.';
                }
                if (strlen($password) < 6) {
                    $errors[] = 'Mật khẩu phải có ít nhất 6 ký tự.';
                }
                if ($password !== $confirm) {
                    $errors[] = 'Mật khẩu nhập lại không khớp.';
                }
                if (!$errors && $this->model()->findByEmail($email)) {
                    $errors[] = 'Email này đã được đăng ký.';
                }

                if (!$errors) {
                    $this->model()->create($fullName, $phone, $email, password_hash($password, PASSWORD_DEFAULT));
                    setFlash('success', 'Đăng ký thành công. Hãy đăng nhập.');
                    redirect('login');
                }
            } catch (DomainException $exception) {
                $errors[] = $exception->getMessage();
            } catch (PDOException $exception) {
                error_log($exception->getMessage());
                $errors[] = 'Không thể đăng ký lúc này. Hãy kiểm tra kết nối database.';
            }
        }

        render('auth/register', ['errors' => $errors, 'old' => $old]);
    }

    public function login(): void
    {
        $user = currentUser();
        if ($user) {
            redirect(($user['role'] ?? '') === 'admin' ? 'admin' : '/');
        }

        $errors = [];
        $email = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                assertCsrf();

                $email = strtolower(trim((string) ($_POST['email'] ?? '')));
                $password = (string) ($_POST['password'] ?? '');
                $user = $this->model()->findByEmail($email);

                if (!$user || !password_verify($password, $user['password'])) {
                    $errors[] = 'Email hoặc mật khẩu không đúng.';
                } else {
                    session_regenerate_id(true);
                    $_SESSION['user'] = [
                        'id' => (int) $user['id'],
                        'full_name' => $user['full_name'],
                        'phone' => $user['phone'],
                        'email' => $user['email'],
                        'role' => $user['role'],
                    ];
                    setFlash('success', 'Xin chào, ' . $user['full_name'] . '!');
                    redirect($user['role'] === 'admin' ? 'admin-bookings' : '/');
                }
            } catch (DomainException $exception) {
                $errors[] = $exception->getMessage();
            } catch (PDOException $exception) {
                error_log($exception->getMessage());
                $errors[] = 'Không thể đăng nhập lúc này. Hãy kiểm tra kết nối database.';
            }
        }

        render('auth/login', ['errors' => $errors, 'email' => $email]);
    }

    public function logout(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrfToken($_POST['csrf_token'] ?? null)) {
            unset($_SESSION['user']);
            session_regenerate_id(true);
            setFlash('success', 'Bạn đã đăng xuất.');
        }
        redirect('/');
    }

    private function model(): UserModel
    {
        if ($this->model === null) {
            $this->model = new UserModel();
        }
        return $this->model;
    }
}