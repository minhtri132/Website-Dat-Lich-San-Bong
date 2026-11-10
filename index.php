<?php
require_once __DIR__ . '/commons/env.php';
require_once __DIR__ . '/commons/function.php';
require_once __DIR__ . '/models/UserModel.php';
require_once __DIR__ . '/models/CourtModel.php';
require_once __DIR__ . '/models/BookingModel.php';
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/BookingController.php';
require_once __DIR__ . '/controllers/CourtController.php';
require_once __DIR__ . '/controllers/AdminController.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$auth = new AuthController();
$booking = new BookingController();
$courtController = new CourtController();
$admin = new AdminController();

$act = $_GET['act'] ?? '/';

match ($act) {
    // Trang khách
    '/' => $booking->index(),
    'booking-form' => $booking->form(),
    'booking-create' => $booking->create(),
    'booking-cancel' => $booking->cancel(),
    'my-bookings' => $booking->myBookings(),

    // Tài khoản
    'login' => $auth->login(),
    'register' => $auth->register(),
    'logout' => $auth->logout(),

    // Trang admin
    'admin' => $admin->dashboard(),
    'admin-users' => $admin->users(),
    'admin-bookings' => $booking->adminList(),
    'courts' => $courtController->index(),
    'court-form' => $courtController->form(),
    'court-save' => $courtController->save(),
    'court-toggle' => $courtController->toggle(),
    'court-delete' => $courtController->delete(),

    default => (function () {
        http_response_code(404);
        render('404');
    })(),
};