<?php
require_once __DIR__ . '/commons/env.php';
require_once __DIR__ . '/commons/function.php';
require_once __DIR__ . '/models/UserModel.php';
require_once __DIR__ . '/models/CourtModel.php';
require_once __DIR__ . '/models/BookingModel.php';
require_once __DIR__ . '/models/ExtraModel.php';
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/BookingController.php';
require_once __DIR__ . '/controllers/CourtController.php';
require_once __DIR__ . '/controllers/AdminController.php';
require_once __DIR__ . '/controllers/HomeController.php';
require_once __DIR__ . '/controllers/PaymentController.php';
require_once __DIR__ . '/controllers/ExtraController.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$authController = new AuthController();
$bookingController = new BookingController();
$courtController = new CourtController();
$adminController = new AdminController();
$homeController = new HomeController();
$paymentController = new PaymentController();
$extraController = new ExtraController();

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
    'booking-add-extras' => $paymentController->addExtras(),

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
    'extras' => $extraController->index(),
    'extra-form' => $extraController->form(),
    'extra-save' => $extraController->save(),
    'extra-toggle' => $extraController->toggle(),
    'extra-delete' => $extraController->delete(),

    default => (function () {
        http_response_code(404);
        render('404');
    })(),
};