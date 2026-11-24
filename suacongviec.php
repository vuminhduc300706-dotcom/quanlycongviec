
<?php
require_once "config.php";
kiemTraDangNhap();

$userId = currentUserId();
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

$q = $conn->prepare(
    "SELECT * FROM tasks WHERE id=? AND user_id=?"
);

$q->bind_param("ii", $id, $userId);
$q->execute();

$task = $q->get_result()->fetch_assoc();
$q->close();

if (!$task) {
    thongBao('warning', 'Không tìm thấy công việc.');
    redirectHome();
}

$error = '';

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    !isset($_POST['add_subtask']) &&
    !isset($_POST['toggle_subtask']) &&
    !isset($_POST['delete_subtask'])
) {

    $title = trim($_POST['title'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $date = $_POST['start_date'] ?? $task['start_date'];
    $time = $_POST['start_time'] ?? $task['start_time'];

    $deadline = trim($_POST['deadline'] ?? '');

    $deadline = $deadline !== ''
        ? str_replace('T', ' ', $deadline) .
          (strlen($deadline) === 16 ? ':00' : '')
        : null;

    $priority = $_POST['priority'] ?? 'Trung bình';
    $status = $_POST['status'] ?? 'Chưa bắt đầu';

    if ($title === '') {
        $error = 'Tên công việc không được để trống.';
    }
    elseif (
        !in_array($priority, ['Cao','Trung bình','Thấp'], true) ||
        !in_array($status, statusList(), true)
    ) {
        $error = 'Dữ liệu không hợp lệ.';
    }
    else {

        $u = $conn->prepare(
            "UPDATE tasks
             SET title=?, description=?, start_date=?,
                 start_time=?, priority=?, deadline=?, status=?
             WHERE id=? AND user_id=?"
        );

        $u->bind_param(
            "sssssssii",
            $title,
            $desc,
            $date,
            $time,
            $priority,
            $deadline,
            $status,
            $id,
            $userId
        );

        if ($u->execute()) {
            $u->close();
            thongBao('success', 'Đã cập nhật công việc.');
            redirectHome();
        }

        $error = 'Không thể cập nhật.';
        $u->close();
    }
}

if (isset($_POST['add_subtask'])) {

    $st = trim($_POST['subtask_title'] ?? '');

    if ($st !== '') {

        $a = $conn->prepare(
            "INSERT INTO subtasks(task_id,title)
             VALUES(?,?)"
        );

        $a->bind_param("is", $id, $st);
        $a->execute();
        $a->close();
    }

    header("Location: suacongviec.php?id=$id");
    exit;
}

if (isset($_POST['toggle_subtask'])) {

    $sid = (int)$_POST['subtask_id'];

    $a = $conn->prepare(
        "UPDATE subtasks
         SET is_done=1-is_done
         WHERE id=? AND task_id=?"
    );

    $a->bind_param("ii", $sid, $id);
    $a->execute();
    $a->close();

    header("Location: suacongviec.php?id=$id");
    exit;
}

if (isset($_POST['delete_subtask'])) {

    $sid = (int)$_POST['subtask_id'];

    $a = $conn->prepare(
        "DELETE FROM subtasks
         WHERE id=? AND task_id=?"
    );

    $a->bind_param("ii", $sid, $id);
    $a->execute();
    $a->close();

    header("Location: suacongviec.php?id=$id");
    exit;
}

$subs = $conn->prepare(
    "SELECT * FROM subtasks WHERE task_id=? ORDER BY id"
);

$subs->bind_param("i", $id);
$subs->execute();

$subRows = $subs->get_result()->fetch_all(MYSQLI_ASSOC);
$subs->close();

$att = $conn->prepare(
    "SELECT * FROM attachments WHERE task_id=?"
);

$att->bind_param("i", $id);
$att->execute();

$attRows = $att->get_result()->fetch_all(MYSQLI_ASSOC);
$att->close();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sửa công việc</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
rel="stylesheet">

<style>
body {
    background: #f5f7fb;
}

.panel {
    max-width: 850px;
    margin: 35px auto;
    background: white;
    padding: 28px;
    border-radius: 18px;
}
</style>
</head>

<body>

<main class="panel">

<div class="d-flex justify-content-between">
<h3>Chi tiết công việc</h3>
<a href="index.php" class="btn btn-light">← Quay lại</a>
</div>

<?php if ($error): ?>
<div class="alert alert-danger"><?= e($error) ?></div>
<?php endif; ?>

<form method="post">

<input type="hidden" name="id" value="<?= $id ?>">

<label class="form-label">Tên công việc</label>
<input class="form-control mb-3"
name="title" value="<?= e($task['title']) ?>" required>

<label class="form-label">Mô tả</label>
<textarea class="form-control mb-3"
name="description" rows="3"><?= e($task['description']) ?></textarea>

<div class="row">

<div class="col-md-6 mb-3">
<label>Ngày bắt đầu</label>
<input class="form-control" type="date"
name="start_date" value="<?= e($task['start_date']) ?>">
</div>

<div class="col-md-6 mb-3">
<label>Giờ bắt đầu</label>
<input class="form-control" type="time"
name="start_time" value="<?= e($task['start_time']) ?>">
</div>

<div class="col-md-6 mb-3">
<label>Hạn hoàn thành</label>
<input class="form-control" type="datetime-local"
name="deadline"
value="<?= !empty($task['deadline'])
    ? e(date('Y-m-d\TH:i', strtotime($task['deadline'])))
    : '' ?>">
</div>

<div class="col-md-6 mb-3">
<label>Mức ưu tiên</label>
<select class="form-select" name="priority">
<?php foreach (['Cao','Trung bình','Thấp'] as $p): ?>
<option <?= $task['priority'] === $p ? 'selected' : '' ?>>
<?= $p ?>
</option>
<?php endforeach; ?>
</select>
</div>

<div class="col-md-6 mb-3">
<label>Trạng thái</label>
<select class="form-select" name="status">
<?php foreach (statusList() as $s): ?>
<option <?= $task['status'] === $s ? 'selected' : '' ?>>
<?= e($s) ?>
</option>
<?php endforeach; ?>
</select>
</div>

</div>

<button class="btn btn-primary">Lưu thay đổi</button>

</form>

<hr>

<h5>Checklist công việc con</h5>

<form method="post" class="d-flex gap-2 mb-3">

<input class="form-control"
name="subtask_title"
placeholder="Tên việc nhỏ..."
required>

<button class="btn btn-outline-primary"
name="add_subtask">Thêm</button>

</form>

<?php foreach ($subRows as $s): ?>

<div class="d-flex align-items-center gap-2 border-bottom py-2">

<form method="post">
<input type="hidden" name="subtask_id"
value="<?= $s['id'] ?>">
<button class="btn btn-sm btn-outline-success"
name="toggle_subtask">
<?= $s['is_done'] ? '✓' : '○' ?>
</button>
</form>

<span class="<?= $s['is_done']
    ? 'text-decoration-line-through text-muted'
    : '' ?>">
<?= e($s['title']) ?>
</span>

<form method="post" class="ms-auto">
<input type="hidden" name="subtask_id"
value="<?= $s['id'] ?>">
<button class="btn btn-sm btn-outline-danger"
name="delete_subtask">Xóa</button>
</form>

</div>

<?php endforeach; ?>

<hr>

<h5>Tệp đính kèm</h5>

<?php if (!$attRows): ?>
<p class="text-muted">Chưa có tệp đính kèm.</p>
<?php endif; ?>

<?php foreach ($attRows as $a): ?>
<p>
<a href="uploads/<?= e($a['stored_name']) ?>" target="_blank">
<?= e($a['original_name']) ?>
</a>
</p>
<?php endforeach; ?>

</main>

</body>
</html>
