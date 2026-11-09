<?php
require_once __DIR__ . '/includes/auth.php';
$layout = !empty($forceDash) ? 'dash' : 'public';
if ($layout === 'dash') require_role('seeker');
$pageTitle = $layout === 'dash' ? 'Find Jobs' : 'Browse Jobs';

$qv = trim($_GET['q'] ?? ''); $loc = trim($_GET['location'] ?? ''); $cat = (int)($_GET['category'] ?? 0);
$types = array_values(array_intersect($_GET['type'] ?? [], JOB_TYPES));
$w = ["j.status='open'", '(j.deadline IS NULL OR j.deadline >= CURDATE())']; $p = [];
if ($qv !== '') { $w[] = '(j.title LIKE ? OR c.name LIKE ? OR j.description LIKE ?)'; array_push($p, "%$qv%", "%$qv%", "%$qv%"); }
if ($loc !== '') { $w[] = 'j.location LIKE ?'; $p[] = "%$loc%"; }
if ($cat) { $w[] = 'j.category_id=?'; $p[] = $cat; }
if ($types) { $w[] = 'j.job_type IN (' . implode(',', array_fill(0, count($types), '?')) . ')'; $p = array_merge($p, $types); }
$where = ' WHERE ' . implode(' AND ', $w);
$per = max(3, (int)setting('jobs_per_page', 6)); $page = page_no();
$total = (int)val('SELECT COUNT(*) FROM jobs j JOIN companies c ON c.id=j.company_id' . $where, $p);
$jobs = rows(JOB_SELECT . $where . ' ORDER BY j.is_featured DESC, j.created_at DESC LIMIT ' . $per . ' OFFSET ' . (($page - 1) * $per), $p);
$saved = saved_ids();

ob_start();
echo '<div class="text-muted small mb-3">' . $total . ' job(s) found</div>';
foreach ($jobs as $j) echo job_card($j, $saved);
if (!$jobs) echo '<div class="text-center text-muted py-5"><i class="bi bi-search fs-1"></i><p>No jobs match your search.</p></div>';
echo pager($total, $per, $page);
$listHtml = ob_get_clean();
if (isset($_GET['ajax'])) { echo $listHtml; exit; }

$cats = rows('SELECT id, name FROM job_categories ORDER BY name');
$locs = rows("SELECT DISTINCT location FROM jobs WHERE status='open' ORDER BY location");
include ROOT_PATH . '/includes/header.php';
?>
<div class="<?= $layout === 'public' ? 'container py-4' : '' ?>">
<form id="filterForm" method="get">
  <div class="card p-3 mb-3"><div class="row g-2">
    <div class="col-md-4"><input type="search" name="q" class="form-control" placeholder="Search jobs, skills, company" value="<?= e($qv) ?>"></div>
    <div class="col-md-3"><input type="search" name="location" class="form-control" placeholder="Location" value="<?= e($loc) ?>" list="locList"><datalist id="locList"><?php foreach ($locs as $l) echo '<option value="' . e($l['location']) . '">'; ?></datalist></div>
    <div class="col-6 col-md-3"><select name="category" class="form-select"><option value="0">All Categories</option><?php foreach ($cats as $c) echo '<option value="' . $c['id'] . '"' . ($cat == $c['id'] ? ' selected' : '') . '>' . e($c['name']) . '</option>'; ?></select></div>
    <div class="col-6 col-md-2 d-grid"><button class="btn btn-primary"><i class="bi bi-search"></i> Search</button></div>
  </div></div>
  <div class="row">
    <div class="col-lg-3 mb-3">
      <button class="btn btn-outline-primary w-100 d-lg-none mb-2" type="button" data-bs-toggle="collapse" data-bs-target="#filters"><i class="bi bi-funnel"></i> Filters</button>
      <div class="collapse d-lg-block" id="filters"><div class="filter-box">
        <h6 class="fw-bold">Job Type</h6>
        <?php foreach (JOB_TYPES as $t): ?><div class="form-check"><input class="form-check-input" type="checkbox" name="type[]" value="<?= $t ?>" id="t<?= md5($t) ?>" <?= in_array($t, $types) ? 'checked' : '' ?>><label class="form-check-label" for="t<?= md5($t) ?>"><?= $t ?></label></div><?php endforeach; ?>
        <h6 class="fw-bold mt-3">Category</h6>
        <?php foreach ($cats as $c): ?><a class="d-block text-decoration-none small py-1 <?= $cat == $c['id'] ? 'fw-bold' : 'text-muted' ?>" href="?category=<?= $c['id'] ?>"><?= e($c['name']) ?></a><?php endforeach; ?>
        <a href="<?= e($self = basename($_SERVER['SCRIPT_NAME'])) ?>" class="btn btn-sm btn-light w-100 mt-3">Clear filters</a>
      </div></div>
    </div>
    <div class="col-lg-9"><h5 class="fw-bold">Featured Jobs</h5><div id="jobList"><?= $listHtml ?></div></div>
  </div>
</form></div>
<?php include ROOT_PATH . '/includes/footer.php'; ?>
