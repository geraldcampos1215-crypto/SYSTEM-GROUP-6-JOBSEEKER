<?php
require_once __DIR__ . '/../includes/auth.php';
$u = require_role('seeker'); $layout = 'dash'; $pageTitle = 'Saved Jobs';
$jobs = rows(JOB_SELECT . ' JOIN saved_jobs s ON s.job_id=j.id WHERE s.user_id=? ORDER BY s.created_at DESC', [$u['id']]);
$saved = array_column($jobs, 'id');
include ROOT_PATH . '/includes/header.php';
foreach ($jobs as $j) echo str_replace('btn-save"', 'btn-save" data-remove="1"', job_card($j, $saved));
if (!$jobs) echo '<div class="text-center text-muted py-5"><i class="bi bi-heart fs-1"></i><p>You have no saved jobs yet. <a href="browse-jobs.php">Browse jobs</a></p></div>';
include ROOT_PATH . '/includes/footer.php';
