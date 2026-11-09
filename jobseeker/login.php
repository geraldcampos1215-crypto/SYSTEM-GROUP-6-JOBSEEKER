<?php
require_once __DIR__ . '/includes/auth.php';
if (user()) redirect(home_for(user()['role']));
$pageTitle = 'Login'; $layout = 'auth'; $err = [];
$id = trim($_POST['identifier'] ?? ($_COOKIE['remember_id'] ?? ''));
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pw = $_POST['password'] ?? '';
    if ($id === '') $err['identifier'] = 'Enter your email or username.';
    if ($pw === '') $err['password'] = 'Enter your password.';
    if (!$err) {
        $u = row('SELECT * FROM users WHERE email=? OR username=?', [$id, $id]);
        if ($u && $u['locked_until'] && strtotime($u['locked_until']) > time()) {
            $err['form'] = 'Too many failed attempts. Try again in ' . ceil((strtotime($u['locked_until']) - time()) / 60) . ' minute(s).';
        } elseif (!$u || !password_verify($pw, $u['password'])) {
            if ($u) {
                $n = $u['failed_attempts'] + 1;
                q('UPDATE users SET failed_attempts=?, locked_until=? WHERE id=?', [$n >= MAX_LOGIN_ATTEMPTS ? 0 : $n, $n >= MAX_LOGIN_ATTEMPTS ? date('Y-m-d H:i:s', time() + LOCK_MINUTES * 60) : null, $u['id']]);
                log_activity('Failed login', $id, $u['id']);
            }
            $err['form'] = 'Invalid email/username or password.';
        } elseif ($u['status'] !== 'active') {
            $err['form'] = 'Your account has been deactivated. Contact the administrator.';
        } else {
            session_regenerate_id(true);
            $_SESSION['uid'] = $u['id']; $_SESSION['role'] = $u['role'];
            q('UPDATE users SET failed_attempts=0, locked_until=NULL, last_login=NOW() WHERE id=?', [$u['id']]);
            log_activity('User login', $u['email'], $u['id']);
            if (!empty($_POST['remember'])) setcookie('remember_id', $id, ['expires' => time() + 2592000, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax']);
            else setcookie('remember_id', '', time() - 3600, '/');
            redirect(home_for($u['role']));
        }
    }
}
include __DIR__ . '/includes/header.php';
?>
<div class="auth-wrap">
  <div class="auth-side"><a href="<?= url('index.php') ?>" class="text-white text-decoration-none fs-3 fw-bold mb-4"><i class="bi bi-briefcase-fill"></i> JobSeeker</a>
    <h1 class="fw-bold">Welcome back!</h1><p class="fs-5 opacity-75">Log in to continue your job search, manage recruitment, or administer the platform.</p></div>
  <div class="auth-form"><div class="auth-card">
    <a href="<?= url('index.php') ?>" class="d-lg-none text-decoration-none fw-bold fs-4 text-primary d-block text-center mb-3"><i class="bi bi-briefcase-fill"></i> JobSeeker</a>
    <h3 class="fw-bold">Welcome Back!</h3><p class="text-muted">Login to your account</p>
    <?php show_flash(); if (!empty($err['form'])) echo '<div class="alert alert-danger">' . e($err['form']) . '</div>'; ?>
    <form method="post" novalidate><?= csrf_field() ?>
      <div class="mb-3"><label class="form-label">Email or Username</label><input name="identifier" class="form-control <?= isset($err['identifier']) ? 'is-invalid' : '' ?>" value="<?= e($id) ?>" autofocus><div class="invalid-feedback"><?= e($err['identifier'] ?? '') ?></div></div>
      <div class="mb-3"><label class="form-label">Password</label><div class="input-group"><input type="password" id="pw" name="password" class="form-control <?= isset($err['password']) ? 'is-invalid' : '' ?>"><button type="button" class="btn btn-outline-secondary" data-toggle-pw="#pw"><i class="bi bi-eye"></i></button><div class="invalid-feedback"><?= e($err['password'] ?? '') ?></div></div></div>
      <div class="d-flex justify-content-between mb-3"><div class="form-check"><input class="form-check-input" type="checkbox" name="remember" id="rm" <?= isset($_COOKIE['remember_id']) ? 'checked' : '' ?>><label class="form-check-label small" for="rm">Remember me</label></div><a class="small" href="<?= url('forgot-password.php') ?>">Forgot password?</a></div>
      <button class="btn btn-primary w-100 py-2">Login</button>
    </form>
    <p class="text-center small mt-3">Don't have an account? <a href="<?= url('register.php') ?>">Sign Up</a></p>
  </div></div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
