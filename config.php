
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Asia/Ho_Chi_Minh');

$conn = new mysqli(
    "localhost",
    "root",
    "",
    "quanlycongviec"
);

if ($conn->connect_error) {
    die("Kết nối database thất bại: " .
        htmlspecialchars($conn->connect_error));
}

$conn->set_charset("utf8mb4");

function e($value) {
    return htmlspecialchars(
        (string)($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

function kiemTraDangNhap() {
    if (empty($_SESSION['user_id'])) {
        header("Location: dangnhap.php");
        exit;
    }
}

function thongBao($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

function hienThongBao() {
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);

        $type = in_array(
            $f['type'],
            ['success', 'danger', 'warning', 'info']
        ) ? $f['type'] : 'info';

        echo '<div class="alert alert-' . $type .
             ' alert-dismissible fade show">';

        echo e($f['message']);

        echo '<button type="button" class="btn-close"
              data-bs-dismiss="alert"></button>';

        echo '</div>';
    }
}

function redirectHome() {
    header("Location: index.php");
    exit;
}

function statusList() {
    return [
        'Chưa bắt đầu',
        'Đang thực hiện',
        'Đã hoàn thành'
    ];
}

function currentUserId() {
    return (int)($_SESSION['user_id'] ?? 0);
}
