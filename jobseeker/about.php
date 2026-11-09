<?php
require_once __DIR__ . '/includes/auth.php';
$pageTitle = 'About Us';
include __DIR__ . '/includes/header.php';
?>
<section class="container py-5">
  <div class="row align-items-center g-4">
    <div class="col-lg-6"><h1 class="fw-bold">About JobSeeker</h1>
      <p class="text-muted fs-5">JobSeeker is an online job portal and recruitment management system that connects job seekers with employers, and gives recruitment teams the tools to manage hiring from posting to onboarding.</p>
      <p class="text-muted">We believe everyone deserves a clear path to the right career. Job seekers can search and apply in minutes and track every application, while staff and administrators manage jobs, candidates and interviews in one place.</p></div>
    <div class="col-lg-6"><div class="row g-3 text-center">
      <?php foreach ([['bi-search', 'Smart Search', 'Filter by category, location and type.'], ['bi-file-earmark-person', 'Easy Applications', 'Apply with your saved resume.'], ['bi-graph-up', 'Track Progress', 'Live application status.'], ['bi-shield-check', 'Secure', 'Role-based access and protected data.']] as [$i, $t, $d]): ?>
        <div class="col-6"><div class="stat-box h-100"><i class="bi <?= $i ?> fs-2 text-primary"></i><div class="fw-semibold mt-2"><?= $t ?></div><div class="small text-muted"><?= $d ?></div></div></div>
      <?php endforeach; ?></div></div>
  </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
