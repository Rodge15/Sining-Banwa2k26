<?php
require_once dirname(__DIR__) . '/app/auth.php';
$user = require_login();

$dashboardViews = [
    'customer' => 'customer.php',
    'admin' => 'admin.php',
    'staff' => 'staff.php',
    'venue' => 'venue.php',
];
$view = $dashboardViews[$user['role']] ?? null;
if ($view === null) {
    http_response_code(403);
    require PROJECT_ROOT . '/app/views/forbidden.php';
    exit;
}

$pageTitle = 'Dashboard';
require PROJECT_ROOT . '/app/views/header.php';
?>
<section class="container content-section">
    <?php require PROJECT_ROOT . '/app/views/flash.php'; ?>
    <?php require PROJECT_ROOT . '/app/views/dashboards/' . $view; ?>
</section>
<?php require PROJECT_ROOT . '/app/views/footer.php'; ?>
