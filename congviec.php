
<?php
require_once "config.php";
kiemTraDangNhap();

$userId = currentUserId();
$error = '';

$projects = $conn->prepare(
    "SELECT id,name FROM projects
     WHERE user_id=? ORDER BY name"
);
$projects->bind_param("i", $userId);
$projects->execute();
$projectRows = $projects->get_result()->fetch_all(MYSQLI_ASSOC);
$projects->close();

$tags = $conn->prepare(
    "SELECT id,name FROM tags
     WHERE user_id=? ORDER BY name"
);
$tags->bind_param("i", $userId);
$tags->execute();
$tagRows = $tags->get_result()->fetch_all(MYSQLI_ASSOC);
$tags->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $startDate = $_POST['start_date'] ?? date('Y-m-d');
    $startTime = $_POST['start_time'] ?? '09:00';

    $deadline = trim($_POST['deadline'] ?? '');

    $deadline = $deadline !== ''
        ? str_replace('T', ' ', $deadline) .
          (strlen($deadline) === 16 ? ':00' : '')
        : null;

    $priority = $_POST['priority'] ?? 'Trung bình';
    $status = $_POST['status'] ?? 'Chưa bắt đầu';

    $projectId = (int)($_POST['project_id'] ?? 0);
    $tagIds = $_POST['tag_ids'] ?? [];
    $remindAt = trim($_POST['remind_at'] ?? '');

    if ($title === '') {
        $error = 'Vui lòng nhập tên công việc.';
    }
    elseif (!in_array(
        $priority,
        ['Cao', 'Trung bình', 'Thấp'],
        true
    )) {
        $error = 'Mức ưu tiên không hợp lệ.';
    }
    elseif (!in_array($status, statusList(), true)) {
        $error = 'Trạng thái không hợp lệ.';
    }
    else {

        $stmt = $conn->prepare(
            "INSERT INTO tasks
            (user_id,title,description,start_time,
             priority,deadline,status,start_date)
            VALUES (?,?,?,?,?,?,?,?)"
        );

        $stmt->bind_param(
            "isssssss",
            $userId,
            $title,
            $description,
            $startTime,
            $priority,
            $deadline,
            $status,
            $startDate
        );

        if ($stmt->execute()) {

            $taskId = $stmt->insert_id;
            $stmt->close();

            if ($projectId > 0) {

                $p = $conn->prepare(
                    "INSERT IGNORE INTO task_projects
                    (task_id,project_id)
                    SELECT ?,id FROM projects
                    WHERE id=? AND user_id=?"
                );

                $p->bind_param(
                    "iii",
                    $taskId,
                    $projectId,
                    $userId
                );

                $p->execute();
                $p->close();
            }

            if (is_array($tagIds)) {

                foreach ($tagIds as $tid) {

                    $tid = (int)$tid;

                    $t = $conn->prepare(
                        "INSERT IGNORE INTO task_tags
                        (task_id,tag_id)
                        SELECT ?,id FROM tags
                        WHERE id=? AND user_id=?"
                    );

                    $t->bind_param(
                        "iii",
                        $taskId,
                        $tid,
                        $userId
                    );

                    $t->execute();
                    $t->close();
                }
            }

            if ($remindAt !== '') {

                $r = $conn->prepare(
                    "INSERT INTO reminders
                    (task_id,remind_at)
                    VALUES (?,?)"
                );

                $r->bind_param("is", $taskId, $remindAt);
                $r->execute();
                $r->close();
            }

            if (
                !empty($_FILES['attachment']['name']) &&
                $_FILES['attachment']['error'] === UPLOAD_ERR_OK
            ) {

                $allowed = [
                    'pdf','png','jpg','jpeg',
                    'doc','docx','txt'
                ];

                $ext = strtolower(
                    pathinfo(
                        $_FILES['attachment']['name'],
                        PATHINFO_EXTENSION
                    )
                );

                if (
                    in_array($ext, $allowed, true) &&
                    $_FILES['attachment']['size'] <= 5 * 1024 * 1024
                ) {

                    $dir = __DIR__ . '/uploads';

                    if (!is_dir($dir)) {
                        mkdir($dir, 0755, true);
                    }

                    $stored = bin2hex(random_bytes(16)) . '.' . $ext;

                    if (move_uploaded_file(
                        $_FILES['attachment']['tmp_name'],
                        $dir . '/' . $stored
                    )) {

                        $a = $conn->prepare(
                            "INSERT INTO attachments
                            (task_id,original_name,stored_name)
                            VALUES (?,?,?)"
                        );

                        $orig = $_FILES['attachment']['name'];

                        $a->bind_param(
                            "iss",
                            $taskId,
                            $orig,
                            $stored
                        );

                        $a->execute();
                        $a->close();
                    }
                }
            }

            thongBao('success', 'Đã thêm công việc.');
            redirectHome();

        } else {

            $error = 'Không thể thêm công việc: ' . $stmt->error;
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
<title>Thêm công việc</title>

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
    box-shadow: 0 8px 30px #15233b0d;
}
</style>
</head>

<body>

<main class="panel">

<div class="d-flex justify-content-between">
<h3>Thêm công việc</h3>
<a href="index.php" class="btn btn-light">← Quay lại</a>
</div>

<?php if ($error): ?>
<div class="alert alert-danger">
<?= e($error) ?>
</div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data">

<div class="mb-3">
<label class="form-label">Tên công việc *</label>
<input class="form-control" name="title" required autofocus>
</div>

<div class="mb-3">
<label class="form-label">Mô tả</label>
<textarea class="form-control" name="description" rows="3"></textarea>
</div>

<div class="row">

<div class="col-md-6 mb-3">
<label class="form-label">Ngày bắt đầu</label>
<input class="form-control" type="date"
name="start_date" value="<?= date('Y-m-d') ?>" required>
</div>

<div class="col-md-6 mb-3">
<label class="form-label">Giờ bắt đầu</label>
<input class="form-control" type="time"
name="start_time" value="09:00" required>
</div>

<div class="col-md-6 mb-3">
<label class="form-label">Hạn hoàn thành</label>
<input class="form-control" type="datetime-local" name="deadline">
</div>

<div class="col-md-6 mb-3">
<label class="form-label">Mức ưu tiên</label>
<select class="form-select" name="priority">
<option>Trung bình</option>
<option>Cao</option>
<option>Thấp</option>
</select>
</div>

<div class="col-md-6 mb-3">
<label class="form-label">Trạng thái</label>
<select class="form-select" name="status">
<option>Chưa bắt đầu</option>
<option>Đang thực hiện</option>
<option>Đã hoàn thành</option>
</select>
</div>

<div class="col-md-6 mb-3">
<label class="form-label">Dự án</label>
<select class="form-select" name="project_id">
<option value="0">Không thuộc dự án</option>
<?php foreach ($projectRows as $p): ?>
<option value="<?= $p['id'] ?>">
<?= e($p['name']) ?>
</option>
<?php endforeach; ?>
</select>
</div>

<div class="col-md-6 mb-3">
<label class="form-label">Nhãn</label>
<select class="form-select" name="tag_ids[]" multiple>
<?php foreach ($tagRows as $t): ?>
<option value="<?= $t['id'] ?>">
<?= e($t['name']) ?>
</option>
<?php endforeach; ?>
</select>
<small class="text-muted">
Giữ Ctrl để chọn nhiều nhãn.
</small>
</div>

<div class="col-md-6 mb-3">
<label class="form-label">Nhắc nhở</label>
<input class="form-control"
type="datetime-local" name="remind_at">
</div>

<div class="col-md-6 mb-3">
<label class="form-label">Tệp đính kèm</label>
<input class="form-control" type="file"
name="attachment"
accept=".pdf,.png,.jpg,.jpeg,.doc,.docx,.txt">
</div>

</div>

<button class="btn btn-primary">Lưu công việc</button>
<a href="index.php" class="btn btn-light ms-2">Hủy</a>

</form>
</main>

</body>
</html>
