<?php
require_once __DIR__ . '/../includes/auth.php';
$u = require_role('admin'); $layout = 'dash'; $pageTitle = 'Dashboard';
$c = fn($sql) => (int)val($sql);
$stats = [['Total Users', $c("SELECT COUNT(*) FROM users WHERE role='seeker'"), 'people', 'ic-blue'], ['Job Postings', $c('SELECT COUNT(*) FROM jobs'), 'briefcase', 'ic-green'], ['Applications', $c('SELECT COUNT(*) FROM applications'), 'file-earmark-text', 'ic-purple'], ['Staff Accounts', $c("SELECT COUNT(*) FROM users WHERE role='staff'"), 'person-badge', 'ic-blue']];
$growth = rows("SELECT DATE_FORMAT(created_at,'%Y-%m') ym, DATE_FORMAT(created_at,'%b') m, COUNT(*) n FROM users WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 11 MONTH) GROUP BY ym, m ORDER BY ym");
$base = $c("SELECT COUNT(*) FROM users WHERE created_at < DATE_SUB(CURDATE(), INTERVAL 11 MONTH)"); $cum = [];
foreach ($growth as $g) { $base += $g['n']; $cum[] = $base; }
$logs = rows('SELECT l.*, u.full_name FROM activity_logs l LEFT JOIN users u ON u.id=l.user_id ORDER BY l.created_at DESC LIMIT 6');
chart_script('new Chart(document.getElementById("ug"),{type:"line",data:{labels:' . json_encode(array_column($growth, 'm')) . ',datasets:[{label:"Total users",data:' . json_encode($cum) . ',borderColor:"#2563EB",backgroundColor:"rgba(37,99,235,.1)",fill:true,tension:.3}]},options:{plugins:{legend:{display:false}}}});');
include ROOT_PATH . '/includes/header.php';
?>
<h4 class="fw-bold mb-0">Welcome back, <?= e(explode(' ', $u['full_name'])[0]) ?>!</h4><p class="text-muted">Here's what's happening in your system.</p>
<div class="row g-3 mb-4"><?php foreach ($stats as [$l, $n, $i, $ic]): ?><div class="col-6 col-lg-3"><div class="card stat-card"><div class="stat-ic <?= $ic ?>"><i class="bi bi-<?= $i ?>"></i></div><div><div class="n"><?= $n ?></div><div class="small text-muted"><?= $l ?></div></div></div></div><?php endforeach; ?></div>
<div class="row g-4"><div class="col-lg-7"><div class="card p-3"><h6 class="fw-bold">User Growth</h6><canvas id="ug" height="130"></canvas></div></div>
<div class="col-lg-5"><div class="card"><div class="card-header d-flex justify-content-between">Recent Activities <a class="small fw-normal" href="logs.php">View all</a></div>
<?php foreach ($logs as $l): ?><div class="notif-item"><div class="small fw-semibold"><?= e($l['action']) ?></div><div class="small text-muted"><?= e($l['details']) ?> · <?= e($l['full_name'] ?? 'Guest') ?> · <?= time_ago($l['created_at']) ?></div></div><?php endforeach; ?></div></div></div>
<?php include ROOT_PATH . '/includes/footer.php'; ?>
