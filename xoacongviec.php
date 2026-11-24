
<?php
require_once "config.php";
kiemTraDangNhap();

$userId = currentUserId();
$id = (int)($_GET['id'] ?? 0);

if ($id > 0) {

    $q = $conn->prepare(
        "DELETE FROM tasks
         WHERE id=? AND user_id=?"
    );

    $q->bind_param("ii", $id, $userId);
    $q->execute();

    if ($q->affected_rows > 0) {
        thongBao('success', 'Đã xóa công việc.');
    } else {
        thongBao('warning', 'Không tìm thấy công việc.');
    }

    $q->close();
}

redirectHome();
