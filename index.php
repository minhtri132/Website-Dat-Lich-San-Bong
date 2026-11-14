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
require_once __DIR__ . '/controllers/HomeController.php';
require_once __DIR__ . '/controllers/PaymentController.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$authController = new AuthController();
$bookingController = new BookingController();
$courtController = new CourtController();
$adminController = new AdminController();
$homeController = new HomeController();
$paymentController = new PaymentController();

$act = $_GET['act'] ?? '/';

match ($act) {
    // Trang khách
    '/' => $homeController->index(),
    'booking' => $bookingController->index(),
    'booking-form' => $bookingController->form(),
    'booking-create' => $bookingController->create(),
    'booking-cancel' => $bookingController->cancel(),
    'my-bookings' => $bookingController->myBookings(),
    'booking-detail' => $paymentController->detail(),
    'booking-report' => $paymentController->report(),

    // Tài khoản
    'login' => $authController->login(),
    'register' => $authController->register(),
    'logout' => $authController->logout(),

    // Trang admin
    'admin' => $adminController->dashboard(),
    'admin-users' => $adminController->users(),
    'admin-bookings' => $bookingController->adminList(),
    'payment-add' => $paymentController->add(),
    'refund-done' => $paymentController->refund(),
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