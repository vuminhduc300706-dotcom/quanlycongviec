
<?php
require_once "config.php";
kiemTraDangNhap();

$userId = currentUserId();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $password = $_POST['password'] ?? '';

    $q = $conn->prepare(
        "SELECT password FROM users WHERE id=?"
    );

    $q->bind_param("i", $userId);
    $q->execute();

    $row = $q->get_result()->fetch_assoc();
    $q->close();

    if (!$row || !password_verify($password, $row['password'])) {

        $error = 'Mật khẩu không đúng.';
    }
    else {

        $d = $conn->prepare(
            "DELETE FROM users WHERE id=?"
        );

        $d->bind_param("i", $userId);
        $d->execute();
        $d->close();

        $_SESSION = [];
        session_destroy();

        header("Location: dangky.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Xóa tài khoản</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
rel="stylesheet">

<style>
body {
    background: #f5f7fb;
}

.panel {
    max-width: 550px;
    margin: 10vh auto;
    background: white;
    padding: 30px;
    border-radius: 16px;
}
</style>
</head>

<body>

<main class="panel">

<h3 class="text-danger">Xóa tài khoản</h3>

<p>
Thao tác này không thể hoàn tác.
Hãy xác nhận mật khẩu để tiếp tục.
</p>

<?php if ($error): ?>
<div class="alert alert-danger">
<?= e($error) ?>
</div>
<?php endif; ?>

<form method="post">

<input
    type="password"
    name="password"
    class="form-control mb-3"
    placeholder="Mật khẩu hiện tại"
    required
>

<button
    class="btn btn-danger"
    onclick="return confirm('Bạn chắc chắn muốn xóa tài khoản?')"
>
Xác nhận xóa tài khoản
</button>

<a href="taikhoan.php" class="btn btn-light">
Hủy
</a>

</form>

</main>

</body>
</html>
