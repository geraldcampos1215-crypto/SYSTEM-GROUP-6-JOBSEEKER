<?php
require_once __DIR__ . '/includes/auth.php';
$pageTitle = 'Home';
$stats = [
  ['Job Listings', (int)val("SELECT COUNT(*) FROM jobs WHERE status='open'")],
  ['Companies', (int)val('SELECT COUNT(*) FROM companies')],
  ['Registered Users', (int)val("SELECT COUNT(*) FROM users WHERE role='seeker'")],
];
$featured = rows(JOB_SELECT . " WHERE j.status='open' AND j.is_featured=1 ORDER BY j.created_at DESC LIMIT 6");
$cats = rows("SELECT cat.id, cat.name, COUNT(j.id) n FROM job_categories cat LEFT JOIN jobs j ON j.category_id=cat.id AND j.status='open' GROUP BY cat.id ORDER BY n DESC LIMIT 8");
$saved = saved_ids();
include __DIR__ . '/includes/header.php';
?>
<section class="hero">
  <div class="container">
    <div class="col-lg-7">
      <h1>Your Next Opportunity Awaits</h1>
      <p class="lead opacity-75 mb-4">Find the right job, build your career, and achieve your dreams with us.</p>
      <form action="<?= url('jobs.php') ?>" class="search-box">
        <div class="row g-2 align-items-center">
          <div class="col-md-5"><input name="q" class="form-control" placeholder="Job title, skills, or company"></div>
          <div class="col-md-4"><input name="location" class="form-control" placeholder="Location"></div>
          <div class="col-md-3 d-grid"><button class="btn btn-primary"><i class="bi bi-search"></i> Search</button></div>
        </div>
      </form>
    </div>
  </div>
</section>
<div class="container stats"><div class="row g-3">
  <?php foreach ($stats as [$l, $n]): ?><div class="col-4"><div class="stat-box"><div class="n"><?= number_format($n) ?></div><div class="text-muted small"><?= $l ?></div></div></div><?php endforeach; ?>
</div></div>
<section class="container mt-5">
  <div class="d-flex justify-content-between align-items-center mb-3"><h3 class="fw-bold mb-0">Featured Jobs</h3><a href="<?= url('jobs.php') ?>">View all jobs <i class="bi bi-arrow-right"></i></a></div>
  <div class="row"><div class="col-lg-8">
    <?php foreach ($featured as $j) echo job_card($j, $saved); if (!$featured) echo '<p class="text-muted">No featured jobs yet.</p>'; ?>
  </div>
  <div class="col-lg-4"><div class="filter-box"><h5 class="fw-bold">Browse by Category</h5>
    <?php foreach ($cats as $c): ?><a class="d-flex justify-content-between text-decoration-none py-2 border-bottom" href="<?= url('jobs.php?category=' . $c['id']) ?>"><span><?= e($c['name']) ?></span><span class="badge bg-primary-subtle text-primary"><?= $c['n'] ?></span></a><?php endforeach; ?>
  </div></div></div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
