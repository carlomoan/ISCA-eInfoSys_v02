<?php
// reports.php

session_start();
require_once __DIR__ . '/../config/db_connect.php';

// Check permission
if (!isset($_SESSION['user_id']) || !in_array('view_reports', $_SESSION['permissions']) && $_SESSION['role_id'] != 1) {
    header('HTTP/1.1 403 Forbidden');
    echo "Access denied.";
    exit;
}

$lang = $_GET['lang'] ?? 'en';
$texts = require __DIR__ . '/../lang/lang.php';
$texts = $texts[$lang] ?? $texts['en'];

$currentTab = $_GET['tab'] ?? 'generated';

?>

<link rel="stylesheet" href="/ISCA-eInfoSys_v02/assets/css/dashboard.css">

<div class="reports-page" style="padding:20px;">
    <h2><?= htmlspecialchars($texts['reports'] ?? 'Reports') ?></h2>

    <!-- Tabs navigation -->
    <div class="tabs">
        <button class="tab-btn <?= $currentTab === 'generated' ? 'active' : '' ?>" data-tab="generated">
            <?= htmlspecialchars($texts['generated_reports'] ?? 'Generated Reports') ?>
        </button>
        <button class="tab-btn <?= $currentTab === 'uploaded' ? 'active' : '' ?>" data-tab="uploaded">
            <?= htmlspecialchars($texts['uploaded_reports'] ?? 'Uploaded Reports') ?>
        </button>
    </div>

    <!-- Tab contents -->
    <div class="tab-content" id="tab-generated" style="<?= $currentTab === 'generated' ? 'display:block;' : 'display:none;' ?>">
        <!-- Placeholder: Generated Reports content -->
        <p>Generated Reports content will appear here.</p>
    </div>

    <div class="tab-content" id="tab-uploaded" style="<?= $currentTab === 'uploaded' ? 'display:block;' : 'display:none;' ?>">
        <!-- Placeholder: Uploaded Reports content -->
        <p>Uploaded Reports content will appear here.</p>
    </div>
</div>

<script>
document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        const tab = btn.getAttribute('data-tab');

        // Hide all tab contents
        document.querySelectorAll('.tab-content').forEach(tc => {
            tc.style.display = 'none';
        });

        // Remove active class on buttons
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));

        // Show clicked tab content & set button active
        document.getElementById('tab-' + tab).style.display = 'block';
        btn.classList.add('active');
    });
});
</script>

<style>
.tabs {
    margin-bottom: 15px;
}
.tab-btn {
    background: #f1f1f1;
    border: none;
    padding: 10px 20px;
    margin-right: 10px;
    cursor: pointer;
    border-radius: 5px 5px 0 0;
    font-weight: bold;
    color: #333;
}
.tab-btn.active {
    background: #007bff;
    color: white;
}
.tab-content {
    border: 1px solid #ddd;
    padding: 15px;
    background: #fff;
    border-radius: 0 5px 5px 5px;
}
</style>
