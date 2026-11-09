<?php
require_once __DIR__ . '/../includes/auth.php';
$u = require_role('admin'); $layout = 'dash'; $pageTitle = 'Users Management';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['action'] ?? ''; $id = (int)($_POST['id'] ?? 0); $t = $id ? row('SELECT * FROM users WHERE id=?', [$id]) : null;
    if ($act === 'create') {
        [$nid, $e] = create_user($_POST['full_name'] ?? '', $_POST['email'] ?? '', $_POST['password'] ?? '', $_POST['role'] ?? 'seeker');
        if ($e) flash('danger', $e); else { log_activity('User created', $_POST['email']); flash('success', 'User created.'); }
    } elseif ($t && $t['id'] == $u['id']) flash('warning', 'You cannot modify your own account here.');
    elseif ($t) {
        if ($act === 'toggle') { $ns = $t['status'] === 'active' ? 'inactive' : 'active'; q('UPDATE users SET status=? WHERE id=?', [$ns, $id]); log_activity("Account $ns", $t['email']); flash('success', "Account is now $ns."); }
        elseif ($act === 'role' && in_array($_POST['role'], ['seeker', 'staff', 'admin'])) {
            q('UPDATE users SET role=? WHERE id=?', [$_POST['role'], $id]);
            if ($_POST['role'] === 'staff') q('INSERT IGNORE INTO staff (user_id) VALUES (?)', [$id]); else q('DELETE FROM staff WHERE user_id=?', [$id]);
            log_activity('Role changed', $t['email'] . ' → ' . $_POST['role']); flash('success', 'Role updated.');
        } elseif ($act === 'delete') { q('DELETE FROM users WHERE id=?', [$id]); log_activity('User deleted', $t['email']); flash('success', 'User deleted.'); }
    }
    redirect('admin/users.php');
}
$qv = trim($_GET['q'] ?? ''); $rl = $_GET['role'] ?? ''; $st = $_GET['status'] ?? '';
$w = '1=1'; $p = [];
if ($qv !== '') { $w .= ' AND (full_name LIKE ? OR email LIKE ? OR username LIKE ?)'; array_push($p, "%$qv%", "%$qv%", "%$qv%"); }
if (in_array($rl, ['seeker', 'staff', 'admin'])) { $w .= ' AND role=?'; $p[] = $rl; }
if (in_array($st, ['active', 'inactive'])) { $w .= ' AND status=?'; $p[] = $st; }
$per = 10; $page = page_no(); $total = (int)val("SELECT COUNT(*) FROM users WHERE $w", $p);
$users = rows("SELECT * FROM users WHERE $w ORDER BY created_at DESC LIMIT $per OFFSET " . (($page - 1) * $per), $p);
include ROOT_PATH . '/includes/header.php';
?>
<div class="d-flex flex-wrap gap-2 justify-content-between mb-3">
  <form class="d-flex gap-2 flex-wrap"><input name="q" class="form-control" placeholder="Search" value="<?= e($qv) ?>"><select name="role" class="form-select"><option value="">All roles</option><?php foreach (['seeker', 'staff', 'admin'] as $r) echo '<option ' . ($rl === $r ? 'selected' : '') . '>' . $r . '</option>'; ?></select><select name="status" class="form-select"><option value="">Any status</option><option <?= $st === 'active' ? 'selected' : '' ?>>active</option><option <?= $st === 'inactive' ? 'selected' : '' ?>>inactive</option></select><button class="btn btn-outline-primary">Filter</button></form>
  <button class="btn btn-primary" data-bs-toggle="collapse" data-bs-target="#newUser"><i class="bi bi-person-plus"></i> Add user</button></div>
<div class="collapse mb-3" id="newUser"><form method="post" class="card p-3 row g-2 flex-row"><?= csrf_field() ?><input type="hidden" name="action" value="create">
  <div class="col-md-3"><input name="full_name" class="form-control" placeholder="Full name" required></div><div class="col-md-3"><input type="email" name="email" class="form-control" placeholder="Email" required></div>
  <div class="col-md-2"><input type="password" name="password" class="form-control" placeholder="Password" required></div><div class="col-md-2"><select name="role" class="form-select"><option>seeker</option><option>staff</option><option>admin</option></select></div><div class="col-md-2 d-grid"><button class="btn btn-success">Create</button></div></form></div>
<div class="card"><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>User</th><th>Role</th><th>Status</th><th>Joined</th><th>Last login</th><th></th></tr></thead><tbody>
<?php foreach ($users as $r): $self = $r['id'] == $u['id']; ?><tr><td><div class="fw-semibold"><?= e($r['full_name']) ?></div><div class="small text-muted"><?= e($r['email']) ?></div></td>
  <td><form method="post" class="d-flex gap-1"><?= csrf_field() ?><input type="hidden" name="action" value="role"><input type="hidden" name="id" value="<?= $r['id'] ?>"><select name="role" class="form-select form-select-sm" style="width:100px" <?= $self ? 'disabled' : '' ?> onchange="this.form.submit()"><?php foreach (['seeker', 'staff', 'admin'] as $o) echo '<option ' . ($r['role'] === $o ? 'selected' : '') . '>' . $o . '</option>'; ?></select></form></td>
  <td><span class="badge text-bg-<?= $r['status'] === 'active' ? 'success' : 'secondary' ?>"><?= $r['status'] ?></span></td><td class="small"><?= date('M d, Y', strtotime($r['created_at'])) ?></td><td class="small"><?= $r['last_login'] ? time_ago($r['last_login']) : 'Never' ?></td>
  <td class="text-end text-nowrap"><?php if (!$self): ?><form method="post" class="d-inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $r['id'] ?>">
    <button name="action" value="toggle" class="btn btn-sm btn-outline-<?= $r['status'] === 'active' ? 'warning' : 'success' ?>"><?= $r['status'] === 'active' ? 'Deactivate' : 'Activate' ?></button>
    <button name="action" value="delete" class="btn btn-sm btn-outline-danger" data-confirm="Delete this user permanently?"><i class="bi bi-trash"></i></button></form><?php endif; ?></td></tr>
<?php endforeach; if (!$users) echo '<tr><td colspan="6" class="text-center text-muted py-4">No users found.</td></tr>'; ?></tbody></table></div></div>
<div class="mt-3"><?= pager($total, $per, $page) ?></div>
<?php include ROOT_PATH . '/includes/footer.php'; ?>
