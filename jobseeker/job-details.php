<?php
require_once __DIR__ . '/includes/auth.php';
$id = (int)($_GET['id'] ?? 0);
$job = row(JOB_SELECT . ' WHERE j.id=?', [$id]);
$u = user();
$canManage = $u && in_array($u['role'], ['staff', 'admin']);
if (!$job || ($job['status'] === 'draft' && !$canManage)) { http_response_code(404); flash('danger', 'Job not found.'); redirect('jobs.php'); }
$pageTitle = $job['title'];
$isOpen = $job['status'] === 'open' && (!$job['deadline'] || $job['deadline'] >= date('Y-m-d'));
$app = $u && $u['role'] === 'seeker' ? row('SELECT * FROM applications WHERE job_id=? AND user_id=?', [$id, $u['id']]) : null;
$profile = $u && $u['role'] === 'seeker' ? row('SELECT * FROM user_profiles WHERE user_id=?', [$u['id']]) : null;
$err = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'apply') {
    if (!$u || $u['role'] !== 'seeker') redirect('login.php');
    if (!$isOpen) $err = 'This job is no longer accepting applications.';
    elseif ($app) $err = 'You already applied for this job.';
    else {
        [$resume, $uerr] = upload_file('resume', 'resumes', ['pdf', 'docx'], 5);
        $resume = $resume ?: ($profile['resume'] ?? null);
        if ($uerr) $err = $uerr;
        elseif (!$resume) $err = 'Please upload your resume (PDF or DOCX) or add one to your profile.';
        else {
            q('INSERT INTO applications (job_id, user_id, cover_letter, resume) VALUES (?,?,?,?)', [$id, $u['id'], trim($_POST['cover_letter'] ?? ''), $resume]);
            notify($u['id'], 'Application submitted', 'You applied for ' . $job['title'] . ' at ' . $job['company_name'] . '.', true);
            foreach (rows("SELECT u.id FROM users u LEFT JOIN staff s ON s.user_id=u.id WHERE u.status='active' AND (u.id=? OR (u.role='staff' AND s.company_id=?))", [$job['posted_by'] ?? 0, $job['company_id']]) as $s)
                notify($s['id'], 'New application', $u['full_name'] . ' applied for ' . $job['title'] . '.');
            log_activity('Application submitted', $job['title']);
            flash('success', 'Application submitted successfully!'); redirect('job-details.php?id=' . $id);
        }
    }
}
$saved = saved_ids();
$related = rows(JOB_SELECT . " WHERE j.status='open' AND j.category_id <=> ? AND j.id<>? ORDER BY j.created_at DESC LIMIT 3", [$job['category_id'], $id]);
include __DIR__ . '/includes/header.php';
?>
<div class="container py-4"><div class="row g-4">
  <div class="col-lg-8"><div class="card p-4">
    <div class="d-flex gap-3 align-items-start">
      <?= company_logo($job, 56) ?>
      <div class="flex-grow-1"><h2 class="fw-bold mb-1"><?= e($job['title']) ?></h2>
        <div class="text-muted"><?= e($job['company_name']) ?></div>
        <div class="mt-2 d-flex flex-wrap gap-3 small text-muted">
          <span><i class="bi bi-geo-alt"></i> <?= e($job['location']) ?></span><span><i class="bi bi-clock"></i> <?= e($job['job_type']) ?></span>
          <span><i class="bi bi-cash"></i> <?= salary($job) ?></span><?php if ($job['category_name']): ?><span><i class="bi bi-tag"></i> <?= e($job['category_name']) ?></span><?php endif; ?>
          <?php if ($job['deadline']): ?><span><i class="bi bi-calendar-x"></i> Apply by <?= date('M d, Y', strtotime($job['deadline'])) ?></span><?php endif; ?></div></div>
      <?php if ($u && $u['role'] === 'seeker'): ?><button class="btn btn-light btn-save" data-id="<?= $id ?>"><i class="bi <?= in_array($id, $saved) ? 'bi-heart-fill text-danger' : 'bi-heart' ?>"></i></button><?php endif; ?>
    </div>
    <hr><h5 class="fw-bold">Job Description</h5><p class="job-desc"><?= e($job['description']) ?></p>
    <?php if ($job['requirements']): ?><h5 class="fw-bold mt-3">Requirements</h5><ul><?php foreach (preg_split('/\R/', $job['requirements']) as $r) if (trim($r) !== '') echo '<li>' . e($r) . '</li>'; ?></ul><?php endif; ?>
  </div></div>
  <div class="col-lg-4">
    <div class="card p-4" id="apply">
      <h5 class="fw-bold">Apply for this job</h5>
      <?php if ($err): ?><div class="alert alert-danger py-2"><?= e($err) ?></div><?php endif; ?>
      <?php if ($canManage): ?><p class="text-muted small mb-0">You are logged in as <?= e($u['role']) ?>. Only job seekers can apply.</p>
      <?php elseif (!$isOpen): ?><div class="alert alert-secondary mb-0">This job is closed.</div>
      <?php elseif (!$u): ?><p class="text-muted small">Log in as a job seeker to apply.</p><a href="<?= url('login.php') ?>" class="btn btn-primary w-100 mb-2">Login to Apply</a><a href="<?= url('register.php') ?>" class="btn btn-outline-primary w-100">Create Account</a>
      <?php elseif ($app): ?><p class="mb-1">You applied on <?= date('M d, Y', strtotime($app['created_at'])) ?></p><?= status_badge($app['status'], $app['id']) ?><a href="<?= url('seeker/my-applications.php') ?>" class="btn btn-outline-primary w-100 mt-3">Track Application</a>
      <?php else: ?>
        <form method="post" enctype="multipart/form-data"><?= csrf_field() ?><input type="hidden" name="action" value="apply">
          <div class="mb-3"><label class="form-label">Cover letter (optional)</label><textarea name="cover_letter" rows="4" class="form-control"><?= e($_POST['cover_letter'] ?? '') ?></textarea></div>
          <div class="mb-3"><label class="form-label">Resume (PDF / DOCX, max 5MB)</label><input type="file" name="resume" class="form-control" accept=".pdf,.docx">
            <div class="form-text"><?= !empty($profile['resume']) ? 'Leave empty to use the resume saved in your profile.' : 'No resume in your profile yet.' ?></div></div>
          <button class="btn btn-primary w-100">Apply Now</button></form>
      <?php endif; ?>
    </div>
    <?php if ($related): ?><h6 class="fw-bold mt-4">Similar Jobs</h6><?php foreach ($related as $r) echo job_card($r, $saved); endif; ?>
  </div>
</div></div>
<?php include __DIR__ . '/includes/footer.php'; ?>
