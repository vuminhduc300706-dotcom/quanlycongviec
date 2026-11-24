
<?php
require_once "config.php";
kiemTraDangNhap();

$userId = currentUserId();

$s = $conn->prepare(
    "SELECT id,username FROM users WHERE id=?"
);

$s->bind_param("i", $userId);
$s->execute();

$user = $s->get_result()->fetch_assoc();
$s->close();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $old = $_POST['old_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    $q = $conn->prepare(
        "SELECT password FROM users WHERE id=?"
    );

    $q->bind_param("i", $userId);
    $q->execute();

    $row = $q->get_result()->fetch_assoc();
    $q->close();

    if (!$row || !password_verify($old, $row['password'])) {

        $error = 'Mật khẩu hiện tại không đúng.';
    }
    elseif (strlen($new) < 6) {

        $error = 'Mật khẩu mới cần ít nhất 6 ký tự.';
    }
    elseif ($new !== $confirm) {

        $error = 'Mật khẩu xác nhận không khớp.';
    }
    else {

        $hash = password_hash($new, PASSWORD_DEFAULT);

        $u = $conn->prepare(
            "UPDATE users SET password=? WHERE id=?"
        );

        $u->bind_param("si", $hash, $userId);
        $u->execute();
        $u->close();

        thongBao('success', 'Đã đổi mật khẩu.');
        redirectHome();
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Tài khoản</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
rel="stylesheet">

<style>
body {
    background: #f5f7fb;
}

.panel {
    max-width: 600px;
    margin: 35px auto;
    background: #fff;
    padding: 28px;
    border-radius: 16px;
}
</style>
</head>

<body>

<main class="panel">

<div class="d-flex justify-content-between">
<h3>Tài khoản</h3>
<a href="index.php" class="btn btn-light">Trang chủ</a>
</div>

<?php if ($error): ?>
<div class="alert alert-danger">
<?= e($error) ?>
</div>
<?php endif; ?>

<p>
<b>Mã người dùng:</b>
<?= e($user['id'] ?? '') ?>
</p>

<p>
<b>Tên đăng nhập:</b>
<?= e($user['username'] ?? '') ?>
</p>

<hr>

<h5>Đổi mật khẩu</h5>

<form method="post">

<input class="form-control mb-3"
type="password"
name="old_password"
placeholder="Mật khẩu hiện tại"
required>

<input class="form-control mb-3"
type="password"
name="new_password"
placeholder="Mật khẩu mới"
required>

<input class="form-control mb-3"
type="password"
name="confirm_password"
placeholder="Nhập lại mật khẩu mới"
required>

<button class="btn btn-primary">
Cập nhật mật khẩu
</button>

</form>

</main>

</body>
</html>
