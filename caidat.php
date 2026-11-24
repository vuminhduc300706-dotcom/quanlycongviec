
<?php
require_once "config.php";
kiemTraDangNhap();

$userId = currentUserId();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';

    if ($action === 'add_project') {

        $name = trim($_POST['project_name'] ?? '');

        if ($name !== '') {

            $s = $conn->prepare(
                "INSERT INTO projects(user_id,name)
                 VALUES(?,?)"
            );

            $s->bind_param("is", $userId, $name);
            $s->execute();
            $s->close();

            thongBao('success', 'Đã thêm dự án.');
        }
    }

    if ($action === 'add_tag') {

        $name = trim($_POST['tag_name'] ?? '');

        if ($name !== '') {

            $s = $conn->prepare(
                "INSERT INTO tags(user_id,name)
                 VALUES(?,?)"
            );

            $s->bind_param("is", $userId, $name);
            $s->execute();
            $s->close();

            thongBao('success', 'Đã thêm nhãn.');
        }
    }

    if ($action === 'delete_project') {

        $id = (int)$_POST['id'];

        $s = $conn->prepare(
            "DELETE p FROM projects p
             WHERE p.id=? AND p.user_id=?"
        );

        $s->bind_param("ii", $id, $userId);
        $s->execute();
        $s->close();
    }

    if ($action === 'delete_tag') {

        $id = (int)$_POST['id'];

        $s = $conn->prepare(
            "DELETE t FROM tags t
             WHERE t.id=? AND t.user_id=?"
        );

        $s->bind_param("ii", $id, $userId);
        $s->execute();
        $s->close();
    }

    header("Location: caidat.php");
    exit;
}

$p = $conn->prepare(
    "SELECT * FROM projects
     WHERE user_id=? ORDER BY name"
);

$p->bind_param("i", $userId);
$p->execute();

$projects = $p->get_result()->fetch_all(MYSQLI_ASSOC);
$p->close();

$t = $conn->prepare(
    "SELECT * FROM tags
     WHERE user_id=? ORDER BY name"
);

$t->bind_param("i", $userId);
$t->execute();

$tags = $t->get_result()->fetch_all(MYSQLI_ASSOC);
$t->close();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Cài đặt</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
rel="stylesheet">

<style>
body {
    background: #f5f7fb;
}

.wrap {
    max-width: 900px;
    margin: 30px auto;
}

.panel {
    background: white;
    border-radius: 16px;
    padding: 24px;
    margin-bottom: 18px;
}
</style>
</head>

<body>

<main class="wrap">

<div class="d-flex justify-content-between mb-3">
<h3>Cài đặt</h3>
<a href="index.php" class="btn btn-light">← Trang chủ</a>
</div>

<?php hienThongBao(); ?>

<section class="panel">

<h5>Giao diện và giọng đọc</h5>

<div class="form-check form-switch mb-2">
<input class="form-check-input"
type="checkbox" id="darkToggle">
<label class="form-check-label" for="darkToggle">
Chế độ tối
</label>
</div>

<div class="form-check form-switch">
<input class="form-check-input"
type="checkbox" id="voiceToggle">
<label class="form-check-label" for="voiceToggle">
Bật nhắc nhở bằng giọng nói
</label>
</div>

</section>

<section class="panel">

<h5>Quản lý dự án</h5>

<form method="post" class="d-flex gap-2 mb-3">

<input type="hidden" name="action" value="add_project">

<input class="form-control"
name="project_name"
placeholder="Tên dự án mới"
required>

<button class="btn btn-primary">Thêm</button>

</form>

<?php foreach ($projects as $x): ?>

<div class="d-flex justify-content-between border-bottom py-2">

<?= e($x['name']) ?>

<form method="post">

<input type="hidden" name="action"
value="delete_project">

<input type="hidden" name="id"
value="<?= $x['id'] ?>">

<button class="btn btn-sm btn-outline-danger">
Xóa
</button>

</form>

</div>

<?php endforeach; ?>

</section>

<section class="panel">

<h5>Quản lý nhãn</h5>

<form method="post" class="d-flex gap-2 mb-3">

<input type="hidden" name="action" value="add_tag">

<input class="form-control"
name="tag_name"
placeholder="Tên nhãn mới"
required>

<button class="btn btn-primary">Thêm</button>

</form>

<?php foreach ($tags as $x): ?>

<div class="d-flex justify-content-between border-bottom py-2">

<?= e($x['name']) ?>

<form method="post">

<input type="hidden" name="action"
value="delete_tag">

<input type="hidden" name="id"
value="<?= $x['id'] ?>">

<button class="btn btn-sm btn-outline-danger">
Xóa
</button>

</form>

</div>

<?php endforeach; ?>

</section>

<section class="panel">

<a href="taikhoan.php">Quản lý tài khoản</a>
·
<a href="dangxuat.php">Đăng xuất</a>

</section>

</main>

<script>

const v = document.getElementById('voiceToggle');
const d = document.getElementById('darkToggle');

v.checked = localStorage.getItem('voiceReminder') === 'on';

v.onchange = () => {
    localStorage.setItem(
        'voiceReminder',
        v.checked ? 'on' : 'off'
    );
};

d.checked = localStorage.getItem('darkMode') === 'on';

d.onchange = () => {

    localStorage.setItem(
        'darkMode',
        d.checked ? 'on' : 'off'
    );

    document.body.classList.toggle('dark', d.checked);
};

if (d.checked) {
    document.body.classList.add('dark');
}

</script>

<style>

.dark {
    background: #111827;
    color: #e5e7eb;
}

.dark .panel {
    background: #1f2937;
    color: #e5e7eb;
}

.dark .form-control {
    background: #374151;
    color: white;
    border-color: #4b5563;
}

</style>

</body>
</html>
