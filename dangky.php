
<?php
require_once "config.php";

if (!empty($_SESSION['user_id'])) {
    redirectHome();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Vui lòng nhập đầy đủ thông tin.';
    }
    elseif (mb_strlen($username) > 100) {
        $error = 'Tên đăng nhập tối đa 100 ký tự.';
    }
    elseif (strlen($password) < 6) {
        $error = 'Mật khẩu phải có ít nhất 6 ký tự.';
    }
    elseif ($password !== $confirm) {
        $error = 'Mật khẩu xác nhận không khớp.';
    }
    else {

        $check = $conn->prepare(
            "SELECT id FROM users WHERE username = ?"
        );

        $check->bind_param("s", $username);
        $check->execute();

        $exists = $check->get_result()->num_rows > 0;

        $check->close();

        if ($exists) {
            $error = 'Tên đăng nhập đã tồn tại.';
        }
        else {

            $hash = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $stmt = $conn->prepare(
                "INSERT INTO users (username, password)
                 VALUES (?, ?)"
            );

            $stmt->bind_param(
                "ss",
                $username,
                $hash
            );

            if ($stmt->execute()) {

                thongBao(
                    'success',
                    'Đăng ký thành công. Hãy đăng nhập.'
                );

                header("Location: dangnhap.php");
                exit;
            }

            $error = 'Không thể tạo tài khoản.';

            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Đăng ký</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
rel="stylesheet">

<style>
body {
    background: #f4f6fb;
}

.auth {
    max-width: 430px;
    margin: 7vh auto;
    background: white;
    padding: 32px;
    border-radius: 18px;
    box-shadow: 0 12px 40px #18284812;
}
</style>
</head>

<body>

<main class="auth">

<h3 class="mb-2">Tạo tài khoản</h3>

<p class="text-secondary">
Bắt đầu quản lý công việc cá nhân.
</p>

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
    maxlength="100"
    required
>

<label class="form-label">Mật khẩu</label>

<input
    class="form-control mb-3"
    type="password"
    name="password"
    required
>

<label class="form-label">Nhập lại mật khẩu</label>

<input
    class="form-control mb-3"
    type="password"
    name="confirm_password"
    required
>

<button class="btn btn-primary w-100">
Đăng ký
</button>

</form>

<p class="mt-3 mb-0 text-center">
Đã có tài khoản?
<a href="dangnhap.php">Đăng nhập</a>
</p>

</main>

</body>
</html>
