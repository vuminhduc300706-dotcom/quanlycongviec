
<?php
require_once "config.php";

if (!empty($_SESSION['user_id'])) {
    redirectHome();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare(
        "SELECT id, username, password
         FROM users
         WHERE username = ?"
    );

    $stmt->bind_param("s", $username);
    $stmt->execute();

    $user = $stmt->get_result()->fetch_assoc();

    $stmt->close();

    if (
        $user &&
        password_verify($password, $user['password'])
    ) {

        session_regenerate_id(true);

        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['username'] = $user['username'];

        redirectHome();
    }

    $error = 'Tên đăng nhập hoặc mật khẩu không đúng.';
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Đăng nhập</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
rel="stylesheet">

<style>
body {
    background: #f4f6fb;
}

.auth {
    max-width: 430px;
    margin: 9vh auto;
    background: white;
    padding: 32px;
    border-radius: 18px;
    box-shadow: 0 12px 40px #18284812;
}
</style>
</head>

<body>

<main class="auth">

<h3>Chào mừng trở lại</h3>

<p class="text-secondary">
Đăng nhập để tiếp tục công việc của bạn.
</p>

<?php hienThongBao(); ?>

<?php if ($error): ?>
<div class="alert alert-danger">
<?= e($error) ?>
</div>
<?php endif; ?>

<form method="post">

<label class="form-label">Tên đăng nhập</label>

<input
    class="form-control mb-3"
    name="username"
    required
    autofocus
>

<label class="form-label">Mật khẩu</label>

<input
    class="form-control mb-3"
    type="password"
    name="password"
    required
>

<button class="btn btn-primary w-100">
Đăng nhập
</button>

</form>

<div class="d-flex justify-content-between mt-3">

<a href="quenmatkhau.php">Quên mật khẩu?</a>

<a href="dangky.php">Tạo tài khoản</a>

</div>

</main>

</body>
</html>
