<?php
ini_set('display_errors', 1); error_reporting(E_ALL);
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/verification.php';
if (user()) redirect(home_for(user()['role']));
$pageTitle = 'Register'; $layout = 'auth'; $err = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['full_name'] ?? ''); $email = strtolower(trim($_POST['email'] ?? '')); $pw = $_POST['password'] ?? ''; $pw2 = $_POST['confirm'] ?? '';
    if (setting('allow_registration', '1') !== '1') $err['form'] = 'Registration is currently closed.';
    if (strlen($name) < 3) $err['full_name'] = 'Enter your full name.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $err['email'] = 'Enter a valid email address.';
    elseif (val('SELECT id FROM users WHERE email=?', [$email])) $err['email'] = 'This email is already registered.';
    if (strlen($pw) < 8 || !preg_match('/[A-Za-z]/', $pw) || !preg_match('/\d/', $pw)) $err['password'] = 'Use at least 8 characters with letters and numbers.';
    if ($pw !== $pw2) $err['confirm'] = 'Passwords do not match.';
    if (!$err) {
        // Do NOT create the user yet – park the data until the email is verified.
        q('DELETE FROM pending_registrations WHERE email=?', [$email]);
        q('INSERT INTO pending_registrations (full_name, email, password, code_hash, expires_at, last_sent) VALUES (?,?,?,?,?,?)',
          [$name, $email, password_hash($pw, PASSWORD_DEFAULT), '', date('Y-m-d H:i:s'), date('Y-m-d H:i:s')]);
        $code = send_verification_code($email, $name);
        $_SESSION['pending_email'] = $email;
        if (defined('SHOW_CODE_ON_SCREEN') && SHOW_CODE_ON_SCREEN) $_SESSION['dev_code'] = $code;
        flash('success', 'We sent a 6-digit verification code to ' . $email . '.');
        redirect('verify-email.php');
    }
}
$f = fn($k, $l, $t = 'text') => '<div class="mb-3"><label class="form-label">' . $l . '</label><input type="' . $t . '" name="' . $k . '" class="form-control ' . (isset($err[$k]) ? 'is-invalid' : '') . '" value="' . ($t === 'password' ? '' : e($_POST[$k] ?? '')) . '"><div class="invalid-feedback">' . e($err[$k] ?? '') . '</div></div>';
include __DIR__ . '/includes/header.php';
?>
<div class="auth-wrap">
  <div class="auth-side"><a href="<?= url('index.php') ?>" class="text-white text-decoration-none fs-3 fw-bold mb-4"><i class="bi bi-briefcase-fill"></i> JobSeeker</a>
    <h1 class="fw-bold">Create an account</h1><p class="fs-5 opacity-75">Join our community. Apply to jobs in minutes and track every application in real time.</p></div>
  <div class="auth-form"><div class="auth-card">
    <h3 class="fw-bold">Create an Account</h3><p class="text-muted">Join our community</p>
    <?php if (!empty($err['form'])) echo '<div class="alert alert-danger">' . e($err['form']) . '</div>'; ?>
    <form method="post" novalidate><?= csrf_field() ?>
      <?= $f('full_name', 'Full Name') . $f('email', 'Email', 'email') . $f('password', 'Password', 'password') . $f('confirm', 'Confirm Password', 'password') ?>
      <button class="btn btn-primary w-100 py-2">Register</button>
    </form>
    <p class="text-center small mt-3">Already have an account? <a href="<?= url('login.php') ?>">Login</a></p>
  </div></div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>