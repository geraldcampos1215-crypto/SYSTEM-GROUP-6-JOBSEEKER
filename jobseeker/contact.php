<?php
require_once __DIR__ . '/includes/auth.php';
$pageTitle = 'Contact Us';
$err = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? ''); $email = trim($_POST['email'] ?? ''); $msg = trim($_POST['message'] ?? '');
    if ($name === '') $err['name'] = 'Please enter your name.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $err['email'] = 'Enter a valid email.';
    if (strlen($msg) < 10) $err['message'] = 'Message must be at least 10 characters.';
    if (!$err) {
        foreach (rows("SELECT id FROM users WHERE role='admin' AND status='active'") as $a)
            notify($a['id'], 'Contact message from ' . $name, "$email: $msg");
        log_activity('Contact form', $email, null);
        flash('success', 'Thanks! Your message was sent. We will get back to you soon.'); redirect('contact.php');
    }
}
include __DIR__ . '/includes/header.php';
?>
<section class="container py-5"><div class="row g-4">
  <div class="col-lg-5"><h1 class="fw-bold">Contact Us</h1><p class="text-muted">Questions about JobSeeker? Send us a message.</p>
    <p><i class="bi bi-envelope text-primary"></i> <?= e(setting('contact_email')) ?></p>
    <p><i class="bi bi-telephone text-primary"></i> <?= e(setting('contact_phone')) ?></p>
    <p><i class="bi bi-geo-alt text-primary"></i> <?= e(setting('contact_address')) ?></p></div>
  <div class="col-lg-7"><form method="post" class="card p-4" novalidate><?= csrf_field() ?>
    <?php foreach (['name' => 'Your name', 'email' => 'Email address'] as $k => $l): ?>
      <div class="mb-3"><label class="form-label"><?= $l ?></label><input name="<?= $k ?>" class="form-control <?= isset($err[$k]) ? 'is-invalid' : '' ?>" value="<?= e($_POST[$k] ?? '') ?>"><div class="invalid-feedback"><?= e($err[$k] ?? '') ?></div></div>
    <?php endforeach; ?>
    <div class="mb-3"><label class="form-label">Message</label><textarea name="message" rows="5" class="form-control <?= isset($err['message']) ? 'is-invalid' : '' ?>"><?= e($_POST['message'] ?? '') ?></textarea><div class="invalid-feedback"><?= e($err['message'] ?? '') ?></div></div>
    <button class="btn btn-primary">Send Message</button></form></div>
</div></section>
<?php include __DIR__ . '/includes/footer.php'; ?>
