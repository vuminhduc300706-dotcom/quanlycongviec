
<?php
require_once "config.php";
kiemTraDangNhap();

$userId = currentUserId();
$username = $_SESSION['username'] ?? 'Người dùng';

$view = $_GET['view'] ?? 'list';

if (!in_array($view, ['list','board','calendar'], true)) {
    $view = 'list';
}

$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? '';
$priority = $_GET['priority'] ?? '';
$dateFilter = $_GET['date'] ?? '';

$sql = "SELECT t.*,
        p.name AS project_name,
        GROUP_CONCAT(DISTINCT g.name SEPARATOR ', ') AS tag_names
        FROM tasks t
        LEFT JOIN task_projects tp ON tp.task_id=t.id
        LEFT JOIN projects p ON p.id=tp.project_id
        LEFT JOIN task_tags tt ON tt.task_id=t.id
        LEFT JOIN tags g ON g.id=tt.tag_id
        WHERE t.user_id=?";

$types = 'i';
$params = [$userId];

if ($search !== '') {

    $sql .= " AND (t.title LIKE ? OR t.description LIKE ?)";

    $types .= 'ss';

    $v = "%$search%";

    $params[] = $v;
    $params[] = $v;
}

if (in_array($status, statusList(), true)) {

    $sql .= " AND t.status=?";

    $types .= 's';
    $params[] = $status;
}

if (in_array($priority, ['Cao','Trung bình','Thấp'], true)) {

    $sql .= " AND t.priority=?";

    $types .= 's';
    $params[] = $priority;
}

if ($dateFilter !== '') {

    $sql .= " AND t.start_date=?";

    $types .= 's';
    $params[] = $dateFilter;
}

$sql .= " GROUP BY t.id
          ORDER BY t.start_date ASC,t.start_time ASC";

$stmt = $conn->prepare($sql);

$stmt->bind_param($types, ...$params);
$stmt->execute();

$tasks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$c = $conn->prepare(
    "SELECT COUNT(*) total,
     SUM(status='Đã hoàn thành') done
     FROM tasks WHERE user_id=?"
);

$c->bind_param("i", $userId);
$c->execute();

$stats = $c->get_result()->fetch_assoc();
$c->close();

$done = (int)($stats['done'] ?? 0);
$total = (int)($stats['total'] ?? 0);

$today = date('Y-m-d');

$upcoming = array_filter(
    $tasks,
    fn($t) => $t['start_date'] > $today &&
              $t['status'] !== 'Đã hoàn thành'
);

$rem = $conn->prepare(
    "SELECT r.id,r.remind_at,t.title
     FROM reminders r
     JOIN tasks t ON t.id=r.task_id
     WHERE t.user_id=?
     AND r.is_sent=0
     AND r.remind_at<=NOW()"
);

$rem->bind_param("i", $userId);
$rem->execute();

$dueReminders = $rem->get_result()->fetch_all(MYSQLI_ASSOC);
$rem->close();

if ($dueReminders) {

    foreach ($dueReminders as $r) {

        $u = $conn->prepare(
            "UPDATE reminders SET is_sent=1 WHERE id=?"
        );

        $u->bind_param("i", $r['id']);
        $u->execute();
        $u->close();
    }
}

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['quick_status'])
) {

    $id = (int)$_POST['task_id'];
    $s = $_POST['new_status'] ?? '';

    if (in_array($s, statusList(), true)) {

        $u = $conn->prepare(
            "UPDATE tasks SET status=?
             WHERE id=? AND user_id=?"
        );

        $u->bind_param("sii", $s, $id, $userId);
        $u->execute();
        $u->close();
    }

    header("Location: index.php?view=" . urlencode($view));
    exit;
}

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['drag_status'])
) {

    $id = (int)$_POST['task_id'];
    $s = $_POST['new_status'] ?? '';

    if (in_array($s, statusList(), true)) {

        $u = $conn->prepare(
            "UPDATE tasks SET status=?
             WHERE id=? AND user_id=?"
        );

        $u->bind_param("sii", $s, $id, $userId);
        $u->execute();
        $u->close();
    }

    http_response_code(204);
    exit;
}

function priorityClass($p) {
    return $p === 'Cao'
        ? 'high'
        : ($p === 'Thấp' ? 'low' : 'medium');
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width, initial-scale=1">

<title>Không gian công việc</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
rel="stylesheet">

<style>

:root {
    --bg: #f5f7fb;
    --panel: #fff;
    --ink: #172033;
    --muted: #718096;
    --line: #e8edf4;
    --blue: #4969e8;
}

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    background: var(--bg);
    color: var(--ink);
    font-family: Inter, Arial, sans-serif;
}

.app {
    display: flex;
    min-height: 100vh;
}

.side {
    width: 245px;
    background: var(--panel);
    border-right: 1px solid var(--line);
    padding: 24px 16px;
    flex-shrink: 0;
}

.brand {
    font-weight: 800;
    font-size: 19px;
    margin: 0 10px 28px;
}

.user {
    padding: 12px;
    background: var(--bg);
    border-radius: 12px;
    margin-bottom: 22px;
}

.navitem {
    display: block;
    padding: 11px 13px;
    color: #526078;
    text-decoration: none;
    border-radius: 10px;
    margin: 3px 0;
}

.navitem.active,
.navitem:hover {
    background: #edf1ff;
    color: var(--blue);
}

.side-label {
    font-size: 11px;
    text-transform: uppercase;
    color: #9aa4b5;
    font-weight: 700;
    margin: 22px 12px 8px;
}

.main {
    padding: 30px;
    min-width: 0;
    flex: 1;
    max-width: 1600px;
    margin: auto;
}

.top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    margin-bottom: 24px;
}

.muted {
    color: var(--muted);
}

.cardx {
    background: var(--panel);
    border: 1px solid var(--line);
    border-radius: 16px;
    padding: 18px;
}

.stats {
    display: grid;
    grid-template-columns: repeat(3,minmax(0,1fr));
    gap: 14px;
    margin-bottom: 22px;
}

.statnum {
    font-size: 27px;
    font-weight: 800;
    margin-top: 5px;
}

.toolbar {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin: 18px 0;
}

.toolbar .form-control {
    max-width: 340px;
}

.tabs {
    display: flex;
    gap: 7px;
}

.tabs a {
    padding: 9px 13px;
    border-radius: 9px;
    text-decoration: none;
    color: #667085;
    background: var(--panel);
    border: 1px solid var(--line);
}

.tabs a.active {
    background: #edf1ff;
    color: var(--blue);
    border-color: #dbe3ff;
}

.task {
    background: var(--panel);
    border: 1px solid var(--line);
    border-radius: 13px;
    padding: 16px;
    margin-bottom: 10px;
}

.task h6 {
    font-weight: 700;
    margin: 0 0 6px;
}

.meta {
    font-size: 13px;
    color: var(--muted);
}

.priority {
    font-size: 12px;
    border-radius: 30px;
    padding: 5px 9px;
    font-weight: 700;
}

.high {
    background: #fff0f0;
    color: #d64a4a;
}

.medium {
    background: #fff6df;
    color: #9a6b00;
}

.low {
    background: #e9f8ef;
    color: #23864b;
}

.kanban {
    display: grid;
    grid-template-columns: repeat(3,minmax(230px,1fr));
    gap: 14px;
    overflow: auto;
}

.column {
    background: #edf1f7;
    border-radius: 14px;
    padding: 12px;
    min-height: 260px;
}

.column h6 {
    padding: 8px;
    font-weight: 700;
}

.dropzone {
    min-height: 200px;
}

.calendar {
    display: grid;
    grid-template-columns: repeat(7,minmax(100px,1fr));
    gap: 7px;
    overflow: auto;
}

.day {
    background: var(--panel);
    border: 1px solid var(--line);
    border-radius: 10px;
    min-height: 115px;
    padding: 8px;
    font-size: 12px;
}

.day strong {
    display: block;
    margin-bottom: 7px;
}

.daytask {
    background: #edf1ff;
    color: #344fc2;
    padding: 5px;
    border-radius: 6px;
    margin: 4px 0;
    overflow-wrap: anywhere;
}

.empty {
    padding: 40px;
    text-align: center;
    color: var(--muted);
}

.dark {
    --bg: #111827;
    --panel: #1f2937;
    --ink: #f3f4f6;
    --muted: #aab4c3;
    --line: #374151;
}

.dark .user,
.dark .column {
    background: #273449;
}

.dark .navitem {
    color: #cbd5e1;
}

.dark .navitem.active {
    background: #303e63;
    color: #c7d2fe;
}

.dark .tabs a {
    background: #1f2937;
    color: #d1d5db;
}

.dark .tabs a.active {
    background: #303e63;
}

.dark .daytask {
    background: #303e63;
    color: #e0e7ff;
}

@media(max-width:900px) {

    .side {
        width: 190px;
    }

    .main {
        padding: 20px;
    }

    .kanban {
        grid-template-columns: repeat(3,270px);
    }
}

@media(max-width:650px) {

    .app {
        display: block;
    }

    .side {
        width: 100%;
        padding: 12px;
        border-right: 0;
        border-bottom: 1px solid var(--line);
    }

    .brand {
        margin: 5px 8px 10px;
    }

    .user {
        display: none;
    }

    .side nav {
        display: flex;
        overflow: auto;
        gap: 4px;
    }

    .navitem {
        white-space: nowrap;
        margin: 0;
        padding: 8px;
    }

    .side-label {
        display: none;
    }

    .main {
        padding: 16px;
    }

    .stats {
        grid-template-columns: 1fr;
    }

    .top {
        align-items: flex-start;
    }

    .calendar {
        grid-template-columns: repeat(7,120px);
    }
}

</style>

</head>

<body>

<div class="app">

<aside class="side">

<div class="brand">◈ TASKFLOW</div>

<div class="user">

<div class="small muted">Đang đăng nhập</div>

<strong><?= e($username) ?></strong>

</div>

<nav>

<a class="navitem <?= $view === 'list' ? 'active' : '' ?>"
href="index.php?view=list">
☷　Tất cả công việc
</a>

<a class="navitem"
href="index.php?date=<?= $today ?>">
☀　Hôm nay
</a>

<a class="navitem"
href="index.php?view=list&status=Chưa bắt đầu">
◷　Sắp tới
</a>

<a class="navitem"
href="index.php?status=Đã hoàn thành">
✓　Đã hoàn thành
</a>

<div class="side-label">Không gian</div>

<a class="navitem" href="caidat.php">
▦　Dự án & nhãn
</a>

<a class="navitem" href="caidat.php">
⚙　Cài đặt
</a>

<a class="navitem" href="taikhoan.php">
♙　Tài khoản
</a>

<a class="navitem" href="dangxuat.php">
↪　Đăng xuất
</a>

</nav>

<a href="congviec.php" class="btn btn-primary w-100 mt-3">
＋ Thêm việc nhanh
</a>

</aside>

<main class="main">

<div class="top">

<div>

<div class="muted small">
<?= date('l, d/m/Y') ?>
</div>

<h2 class="fw-bold mb-1">
Không gian làm việc
</h2>

<div class="muted">
Tập trung vào những việc quan trọng nhất.
</div>

</div>

<button class="btn btn-outline-secondary" id="themeBtn">
☾ Giao diện
</button>

</div>

<?php hienThongBao(); ?>

<div class="stats">

<div class="cardx">
<div class="muted">Tổng công việc</div>
<div class="statnum"><?= $total ?></div>
</div>

<div class="cardx">
<div class="muted">Đã hoàn thành</div>
<div class="statnum text-success"><?= $done ?></div>
</div>

<div class="cardx">
<div class="muted">Sắp tới</div>
<div class="statnum"><?= count($upcoming) ?></div>
</div>

</div>

<form class="toolbar" method="get">

<input type="hidden" name="view"
value="<?= e($view) ?>">

<input class="form-control"
name="search"
value="<?= e($search) ?>"
placeholder="Tìm kiếm công việc...">

<select class="form-select"
name="priority"
style="max-width:160px">

<option value="">Mọi ưu tiên</option>

<?php foreach (['Cao','Trung bình','Thấp'] as $p): ?>

<option <?= $priority === $p ? 'selected' : '' ?>>
<?= $p ?>
</option>

<?php endforeach; ?>

</select>

<select class="form-select"
name="status"
style="max-width:190px">

<option value="">Mọi trạng thái</option>

<?php foreach (statusList() as $s): ?>

<option <?= $status === $s ? 'selected' : '' ?>>
<?= e($s) ?>
</option>

<?php endforeach; ?>

</select>

<button class="btn btn-primary">Lọc</button>

<a class="btn btn-light"
href="index.php?view=<?= e($view) ?>">
Đặt lại
</a>

</form>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">

<div class="tabs">

<a class="<?= $view === 'list' ? 'active' : '' ?>"
href="?view=list">
☷ Danh sách
</a>

<a class="<?= $view === 'board' ? 'active' : '' ?>"
href="?view=board">
▦ Kanban
</a>

<a class="<?= $view === 'calendar' ? 'active' : '' ?>"
href="?view=calendar">
▣ Lịch
</a>

</div>

<a href="congviec.php" class="btn btn-primary">
＋ Thêm công việc
</a>

</div>

<?php if (!$tasks): ?>

<div class="cardx empty">

<div style="font-size:34px">☀</div>

<h5>Chưa có công việc phù hợp</h5>

<p>Thêm một công việc để bắt đầu kế hoạch của bạn.</p>

<a class="btn btn-primary" href="congviec.php">
Thêm công việc
</a>

</div>

<?php elseif ($view === 'list'): ?>

<?php foreach ($tasks as $t): ?>

<article class="task">

<div class="d-flex justify-content-between gap-3 flex-wrap">

<div class="flex-grow-1">

<h6>
<a class="text-decoration-none text-reset"
href="suacongviec.php?id=<?= $t['id'] ?>">
<?= e($t['title']) ?>
</a>
</h6>

<div class="meta mb-2">
<?= e($t['description']) ?>
</div>

<div class="meta">

📅 <?= e(date('d/m/Y', strtotime($t['start_date']))) ?>

　◷ <?= e(substr($t['start_time'], 0, 5)) ?>

　<?= !empty($t['deadline'])
    ? '⏳ ' . e(date('d/m/Y H:i', strtotime($t['deadline'])))
    : '' ?>

</div>

<div class="meta mt-1">
<?= e($t['project_name'] ?? '') ?>
</div>

<?php if ($t['tag_names']): ?>

<small class="text-primary">
<?= e($t['tag_names']) ?>
</small>

<?php endif; ?>

</div>

<div class="text-end">

<span class="priority <?= priorityClass($t['priority']) ?>">
<?= e($t['priority']) ?>
</span>

<form method="post" class="mt-2">

<input type="hidden"
name="task_id"
value="<?= $t['id'] ?>">

<select name="new_status"
class="form-select form-select-sm"
onchange="this.form.submit()">

<?php foreach (statusList() as $s): ?>

<option <?= $t['status'] === $s ? 'selected' : '' ?>>
<?= e($s) ?>
</option>

<?php endforeach; ?>

</select>

<input type="hidden"
name="quick_status"
value="1">

</form>

<div class="mt-2">

<a href="suacongviec.php?id=<?= $t['id'] ?>"
class="small">Sửa</a>

·

<a href="xoacongviec.php?id=<?= $t['id'] ?>"
class="small text-danger"
onclick="return confirm('Xóa công việc này?')">
Xóa
</a>

</div>

</div>

</div>

</article>

<?php endforeach; ?>

<?php elseif ($view === 'board'): ?>

<div class="kanban">

<?php foreach (statusList() as $s): ?>

<section class="column">

<h6><?= e($s) ?></h6>

<div class="dropzone"
data-status="<?= e($s) ?>">

<?php foreach ($tasks as $t): ?>

<?php if ($t['status'] !== $s) continue; ?>

<article class="task"
draggable="true"
data-id="<?= $t['id'] ?>">

<h6>
<a class="text-reset text-decoration-none"
href="suacongviec.php?id=<?= $t['id'] ?>">
<?= e($t['title']) ?>
</a>
</h6>

<div class="meta">
<?= e($t['start_date']) ?>
·
<?= e(substr($t['start_time'], 0, 5)) ?>
</div>

<span class="priority <?= priorityClass($t['priority']) ?>">
<?= e($t['priority']) ?>
</span>

</article>

<?php endforeach; ?>

</div>

</section>

<?php endforeach; ?>

</div>

<?php else: ?>

<?php

$month = date('Y-m');
$first = strtotime($month . '-01');
$offset = (int)date('N', $first) - 1;
$days = (int)date('t', $first);

?>

<div class="cardx">

<div class="d-flex justify-content-between mb-3">

<h5><?= e(date('m/Y', $first)) ?></h5>

<span class="muted">Lịch theo ngày bắt đầu</span>

</div>

<div class="calendar">

<?php foreach (['T2','T3','T4','T5','T6','T7','CN'] as $wd): ?>

<div class="muted fw-bold">
<?= $wd ?>
</div>

<?php endforeach; ?>

<?php for ($i = 0; $i < $offset; $i++): ?>
<div></div>
<?php endfor; ?>

<?php for ($d = 1; $d <= $days; $d++): ?>

<?php

$day = $month . '-' .
       str_pad((string)$d, 2, '0', STR_PAD_LEFT);

?>

<div class="day">

<strong><?= $d ?></strong>

<?php foreach ($tasks as $t): ?>

<?php if ($t['start_date'] === $day): ?>

<a class="daytask d-block text-decoration-none"
href="suacongviec.php?id=<?= $t['id'] ?>">

<?= e($t['title']) ?>

</a>

<?php endif; ?>

<?php endforeach; ?>

</div>

<?php endfor; ?>

</div>

</div>

<?php endif; ?>

<footer class="text-center muted small mt-4">
TASKFLOW · Quản lý công việc cá nhân
</footer>

</main>

</div>

<script>

const dark = localStorage.getItem('darkMode') === 'on';

if (dark) {
    document.body.classList.add('dark');
}

document.getElementById('themeBtn').onclick = () => {

    document.body.classList.toggle('dark');

    localStorage.setItem(
        'darkMode',
        document.body.classList.contains('dark') ? 'on' : 'off'
    );

};

let dragged = null;

document.querySelectorAll('[draggable=true]').forEach(card => {

    card.addEventListener('dragstart', () => {
        dragged = card;
    });

});

document.querySelectorAll('.dropzone').forEach(zone => {

    zone.addEventListener('dragover', e => {
        e.preventDefault();
    });

    zone.addEventListener('drop', async e => {

        e.preventDefault();

        if (!dragged) return;

        const data = new FormData();

        data.append('drag_status', '1');
        data.append('task_id', dragged.dataset.id);
        data.append('new_status', zone.dataset.status);

        await fetch('index.php', {
            method: 'POST',
            body: data
        });

        location.reload();

    });

});

const reminders = <?= json_encode(
    $dueReminders,
    JSON_UNESCAPED_UNICODE |
    JSON_HEX_TAG |
    JSON_HEX_APOS |
    JSON_HEX_QUOT |
    JSON_HEX_AMP
) ?>;

if (
    reminders.length &&
    localStorage.getItem('voiceReminder') === 'on' &&
    'speechSynthesis' in window
) {

    const msg = reminders
        .map(r => 'Đã đến giờ nhắc công việc ' + r.title)
        .join('. ');

    const u = new SpeechSynthesisUtterance(msg);

    u.lang = 'vi-VN';
    u.rate = 0.88;
    u.pitch = 0.9;

    window.speechSynthesis.speak(u);
}

</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
