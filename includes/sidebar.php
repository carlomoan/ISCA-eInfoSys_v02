<?php
/**
 * includes/sidebar.php
 * Sidebar menu with active highlight, permission checks, and responsive design.
 * Variables assumed:
 * - $menuItems (array), $texts (array), $fullname, $currentPage, $permissions (array),
 *   $isAdmin (bool), $lang, $roleName, $isVerified (bool);
 */

if (!function_exists('esc')) {
    function esc($string) {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('getCurrentPage')) {
    function getCurrentPage()
    {
        return isset($_GET['page']) ? strtolower(trim($_GET['page'])) : 'dashboard';
    }
}

$currentPage = getCurrentPage();
?>

<aside class="sidebar">
    <!-- Brand Header -->
    <div class="sidebar-header">
        <img src="<?= esc(BASE_URL) ?>/assets/images/NIMR-Logo-Up.png" alt="Brand" class="brand-image-sidebar" />
        <span class="brand-name">AMRC - eDataColls</span>
    </div>

    <div class="system-name">
        <h3>e-Data Collection and Survey Sys</h3>
    </div>

    <!-- User Info -->
    <div class="user-info">
        <div class="user-avatar"><i class="fas fa-user"></i></div>
        <div class="user-details">
            <strong><?= esc($fullname ?? 'User') ?></strong><br />
            <small>
                <i class="fas fa-user-tag"></i> <?= esc(ucfirst($roleName)) ?>
                <?php if (!$isVerified && $isAdmin): ?>
                    (<?= esc($texts['not_verified'] ?? 'Not Verified') ?>)
                <?php endif; ?>
            </small>
        </div>
    </div>

    <!-- Dynamic Menu -->
    <nav class="menu-list main-menu" role="navigation" aria-label="Main menu">
        <?php if (!empty($menuItems)): ?>
            <?php foreach ($menuItems as $item):
                // Determine the 'page' part of the link (e.g., from 'index.php?page=reports' => 'reports')
                $parsedPage = 'dashboard';
                if (strpos($item['link'], 'page=') !== false) {
                    parse_str(parse_url($item['link'], PHP_URL_QUERY), $query);
                    $parsedPage = strtolower(trim($query['page'] ?? 'dashboard'));
                }

                // Check if current page matches
                $isActive = ($parsedPage === $currentPage);
                $activeClass = $isActive ? 'active-link' : '';

                // Permission logic
                $allowed = $isAdmin || empty($item['perm']) || in_array($item['perm'], $permissions);
                $linkHref = $allowed
                    ? ((strpos($item['link'], 'http') === 0) ? $item['link'] : esc(BASE_URL . ltrim($item['link'], '/')))
                    : '#';
                $linkClass = trim($activeClass . ($allowed ? '' : ' disabled-link'));
            ?>
                <a href="<?= $linkHref ?>" class="<?= esc($linkClass) ?>"
                   <?= $allowed ? '' : 'aria-disabled="true" tabindex="-1"' ?>>
                    <i class="fas <?= esc($item['icon']) ?>"></i>
                    <span><?= esc($item['label']) ?></span>
                </a>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="no-menu" role="alert" style="padding:1rem; color:#999;">No menu available</p>
        <?php endif; ?>
    </nav>

    <!-- Admin Section -->
    <div class="sidebar-footer" role="contentinfo">
<?php if (!empty($isAdmin)): ?>
        <h4><?= esc($texts['user_permissions'] ?? 'Permissions Settings') ?></h4>
        <nav class="admin-menu" aria-label="Admin menu">
            <a href="<?= esc(BASE_URL) ?>/index.php?page=user_permissions&lang=<?= esc($lang) ?>">
                <i class="fas fa-key"></i> <?= esc($texts['user_permissions'] ?? 'User Permissions') ?>
            </a>
            <a href="<?= esc(BASE_URL) ?>/index.php?page=role_permissions&lang=<?= esc($lang) ?>">
                <i class="fas fa-user-shield"></i> <?= esc($texts['role_permissions'] ?? 'Role Permissions') ?>
            </a>
            <?php if (($_SESSION['role_name'] ?? '') === 'Superuser'): ?>
            <a href="<?= esc(BASE_URL) ?>/index.php?page=manage_permissions&lang=<?= esc($lang) ?>">
                <i class="fas fa-shield-alt"></i> <?= esc($texts['manage_permissions'] ?? 'Manage Permissions') ?>
            </a>
            <?php endif; ?>
            <a href="<?= esc(BASE_URL) ?>/index.php?page=settings&lang=<?= esc($lang) ?>">
                <i class="fas fa-cog"></i> <?= esc($texts['settings'] ?? 'Clusters and Survey Settings') ?>
            </a>
        </nav>
<?php endif; ?>

        <!-- Logout -->
        <div class="logout-link">
            <a href="<?= esc(BASE_URL) ?>/logout.php" aria-label="Logout">
                <i class="fas fa-sign-out-alt"></i> <?= esc($texts['logout'] ?? 'Logout') ?>
            </a>
        </div>
    </div>
</aside>

