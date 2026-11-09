<?php
require_once __DIR__ . '/../includes/auth.php';
$u = require_role('seeker'); $layout = 'dash'; $pageTitle = 'Profile';
$err = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['action'] ?? '') === 'profile') {
        $name = trim($_POST['full_name'] ?? '');
        if (strlen($name) < 3) $err[] = 'Please enter your full name.';
        [$pic, $e1] = upload_file('picture', 'profiles', ['jpg', 'jpeg', 'png'], 2);
        [$cv, $e2] = upload_file('resume', 'resumes', ['pdf', 'docx'], 5);
        foreach ([$e1, $e2] as $x) if ($x) $err[] = $x;
        if (!$err) {
            q('UPDATE users SET full_name=? WHERE id=?', [$name, $u['id']]);
            q('INSERT INTO user_profiles (user_id) VALUES (?) ON DUPLICATE KEY UPDATE user_id=user_id', [$u['id']]);
            q('UPDATE user_profiles SET phone=?, location=?, headline=?, skills=?, bio=?, profile_picture=COALESCE(?, profile_picture), resume=COALESCE(?, resume) WHERE user_id=?',
              [trim($_POST['phone']), trim($_POST['location']), trim($_POST['headline']), trim($_POST['skills']), trim($_POST['bio']), $pic, $cv, $u['id']]);
            log_activity('Profile updated', ''); flash('success', 'Profile updated.'); redirect('seeker/profile.php');
        }
    } elseif (($_POST['action'] ?? '') === 'password') {
        $new = $_POST['new_password'] ?? '';
        if (!password_verify($_POST['current_password'] ?? '', $u['password'])) $err[] = 'Current password is incorrect.';
        elseif (strlen($new) < 8 || !preg_match('/[A-Za-z]/', $new) || !preg_match('/\d/', $new)) $err[] = 'New password needs 8+ characters with letters and numbers.';
        elseif ($new !== ($_POST['confirm_password'] ?? '')) $err[] = 'New passwords do not match.';
        else { q('UPDATE users SET password=? WHERE id=?', [password_hash($new, PASSWORD_DEFAULT), $u['id']]); log_activity('Password changed', ''); flash('success', 'Password changed.'); redirect('seeker/profile.php'); }
    }
}
$p = row('SELECT * FROM user_profiles WHERE user_id=?', [$u['id']]) ?: [];
$pc = profile_completion($u['id']);
include ROOT_PATH . '/includes/header.php';
foreach ($err as $x) echo '<div class="alert alert-danger">' . e($x) . '</div>';
?>
<div class="row g-4">
  <div class="col-lg-4"><div class="card p-4 text-center">
    <div class="mx-auto mb-2"><?= avatar($u, 96) ?></div><h5 class="fw-bold mb-0"><?= e($u['full_name']) ?></h5><div class="text-muted small"><?= e($p['headline'] ?? '') ?></div><div class="small text-muted mb-3"><?= e($u['email']) ?></div>
    <div class="d-flex justify-content-between small"><span>Profile completion</span><b><?= $pc ?>%</b></div><div class="progress mb-3" style="height:8px"><div class="progress-bar" style="width:<?= $pc ?>%"></div></div>
    <?php if (!empty($p['resume'])): ?><a class="btn btn-outline-primary btn-sm" href="<?= url('download.php?me=1') ?>"><i class="bi bi-download"></i> My resume</a><?php endif; ?>
  </div>
  <div class="card p-4 mt-4"><h6 class="fw-bold">Change Password</h6><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="password">
    <input type="password" name="current_password" class="form-control mb-2" placeholder="Current password" required><input type="password" name="new_password" class="form-control mb-2" placeholder="New password" required><input type="password" name="confirm_password" class="form-control mb-3" placeholder="Confirm new password" required>
    <button class="btn btn-primary w-100">Update password</button></form></div></div>
  <div class="col-lg-8"><form method="post" enctype="multipart/form-data" class="card p-4"><?= csrf_field() ?><input type="hidden" name="action" value="profile">
    <h5 class="fw-bold mb-3">Personal Information</h5><div class="row g-3">
      <div class="col-md-6"><label class="form-label">Full name</label><input name="full_name" class="form-control" value="<?= e($u['full_name']) ?>" required></div>
      <div class="col-md-6"><label class="form-label">Phone</label><input name="phone" class="form-control" value="<?= e($p['phone'] ?? '') ?>"></div>
      <div class="col-md-6"><label class="form-label">Location</label><input name="location" class="form-control" value="<?= e($p['location'] ?? '') ?>" placeholder="e.g. Manila"></div>
      <div class="col-md-6"><label class="form-label">Headline</label><input name="headline" class="form-control" value="<?= e($p['headline'] ?? '') ?>" placeholder="e.g. Junior Web Developer"></div>
      <div class="col-12"><label class="form-label">Skills (comma separated)</label><input name="skills" class="form-control" value="<?= e($p['skills'] ?? '') ?>"></div>
      <div class="col-12"><label class="form-label">About me</label><textarea name="bio" rows="4" class="form-control"><?= e($p['bio'] ?? '') ?></textarea></div>
      <div class="col-md-6"><label class="form-label">Profile picture (JPG/PNG, 2MB)</label><input type="file" name="picture" accept=".jpg,.jpeg,.png" class="form-control"></div>
      <div class="col-md-6"><label class="form-label">Resume / CV (PDF or DOCX, 5MB)</label><input type="file" name="resume" accept=".pdf,.docx" class="form-control"></div>
    </div><div class="mt-4"><button class="btn btn-primary">Save changes</button></div></form></div>
</div>
<?php include ROOT_PATH . '/includes/footer.php'; ?>
