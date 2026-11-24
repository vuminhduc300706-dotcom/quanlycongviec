
<?php
require_once "config.php";

$notice = "Database users hiện chỉ lưu tên đăng nhập và mật khẩu, chưa có email hoặc phương thức xác minh danh tính. Vì vậy trang này không tự đổi mật khẩu để tránh người khác chiếm tài khoản. Hãy liên hệ người quản lý website để được hỗ trợ đặt lại mật khẩu.";
?>

<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Quên mật khẩu</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
rel="stylesheet">

<style>
body {
    background: #f4f6fb;
}

.panel {
    max-width: 520px;
    margin: 10vh auto;
    background: #fff;
    padding: 30px;
    border-radius: 18px;
}
</style>
</head>

<body>

<main class="panel">

<h3>Quên mật khẩu?</h3>

<div class="alert alert-info mt-3">
<?= e($notice) ?>
</div>

<a href="dangnhap.php" class="btn btn-primary">
Quay lại đăng nhập
</a>

</main>

</body>
</html>
