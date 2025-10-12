<?php
$deniedPage = $_SESSION['denied_page'] ?? 'this page';
$requiredPermission = $_SESSION['required_permission'] ?? 'unknown permission';
$userRole = $_SESSION['role_name'] ?? 'User';
?>

<div class="access-denied-container">
    <div class="access-denied-card">
        <div class="icon-wrapper">
            <i class="fas fa-lock"></i>
        </div>
        <h1>Access Denied</h1>
        <p class="main-message">You don't have permission to access this page</p>

        <div class="details-box">
            <div class="detail-item">
                <strong><i class="fas fa-exclamation-circle"></i> Required Permission:</strong>
                <code><?= htmlspecialchars($requiredPermission, ENT_QUOTES, 'UTF-8') ?></code>
            </div>
            <div class="detail-item">
                <strong><i class="fas fa-user-tag"></i> Your Role:</strong>
                <span><?= htmlspecialchars($userRole, ENT_QUOTES, 'UTF-8') ?></span>
            </div>
        </div>

        <p class="help-text">
            <i class="fas fa-info-circle"></i>
            Contact your administrator to request access to this page.
        </p>

        <div class="action-buttons">
            <a href="<?= BASE_URL ?>?page=dashboard" class="btn-primary">
                <i class="fas fa-home"></i> Go to Dashboard
            </a>
            <button onclick="history.back()" class="btn-secondary">
                <i class="fas fa-arrow-left"></i> Go Back
            </button>
        </div>
    </div>
</div>

<style>
.access-denied-container {
    min-height: 60vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem;
}

.access-denied-card {
    max-width: 600px;
    width: 100%;
    text-align: center;
    background: #ffffff;
    border-radius: 16px;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
    padding: 3rem 2rem;
}

.icon-wrapper {
    width: 100px;
    height: 100px;
    margin: 0 auto 2rem;
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 8px 24px rgba(220, 53, 69, 0.3);
}

.icon-wrapper i {
    font-size: 3rem;
    color: #ffffff;
}

.access-denied-card h1 {
    font-size: 2rem;
    font-weight: 700;
    color: #212529;
    margin-bottom: 1rem;
}

.main-message {
    font-size: 1.1rem;
    color: #6c757d;
    margin-bottom: 2rem;
}

.details-box {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    text-align: left;
}

.detail-item {
    padding: 0.75rem 0;
    border-bottom: 1px solid #e9ecef;
}

.detail-item:last-child {
    border-bottom: none;
}

.detail-item strong {
    display: block;
    font-size: 0.9rem;
    color: #495057;
    margin-bottom: 0.5rem;
}

.detail-item strong i {
    color: #dc3545;
    margin-right: 0.5rem;
}

.detail-item code {
    background: #ffffff;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    color: #dc3545;
    font-weight: 600;
    border: 1px solid #dee2e6;
    display: inline-block;
}

.detail-item span {
    color: #212529;
    font-weight: 600;
}

.help-text {
    font-size: 0.95rem;
    color: #6c757d;
    margin-bottom: 2rem;
}

.help-text i {
    color: #00aced;
    margin-right: 0.5rem;
}

.action-buttons {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
}

@media (max-width: 768px) {
    .access-denied-card {
        padding: 2rem 1.5rem;
    }

    .icon-wrapper {
        width: 80px;
        height: 80px;
    }

    .icon-wrapper i {
        font-size: 2.5rem;
    }

    .access-denied-card h1 {
        font-size: 1.5rem;
    }

    .action-buttons {
        flex-direction: column;
    }

    .action-buttons a,
    .action-buttons button {
        width: 100%;
    }
}
</style>
