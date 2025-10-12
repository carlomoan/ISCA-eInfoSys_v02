<?php
/**
 * ================================================
 *  AUTHENTICATION GUARD
 * ================================================
 * Common authentication and authorization helper
 * Include this at the top of protected API endpoints
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/permission_helper.php';

/**
 * Ensure user is authenticated
 * @param bool $require_admin Optional: require admin role
 * @param string $required_permission Optional: specific permission required
 */
function requireAuth($require_admin = false, $required_permission = null) {
    $userId = $_SESSION['user_id'] ?? null;
    $isAdmin = $_SESSION['is_admin'] ?? false;

    // Check if user is logged in
    if (!$userId) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Authentication required',
            'code' => 401
        ]);
        exit;
    }

    // Check admin requirement
    if ($require_admin && !$isAdmin) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Admin access required',
            'code' => 403
        ]);
        exit;
    }

    // Check specific permission
    if ($required_permission && !checkPermission($required_permission)) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => "Permission denied: {$required_permission} required",
            'code' => 403
        ]);
        exit;
    }

    return $userId;
}

/**
 * Ensure user is verified
 */
function requireVerified() {
    $isVerified = $_SESSION['is_verified'] ?? false;
    if (!$isVerified) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Account verification required',
            'code' => 403
        ]);
        exit;
    }
}

/**
 * Check if current user owns resource or is admin
 */
function requireOwnershipOrAdmin($resourceUserId) {
    $currentUserId = $_SESSION['user_id'] ?? null;
    $isAdmin = $_SESSION['is_admin'] ?? false;

    if (!$isAdmin && $currentUserId != $resourceUserId) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Access denied: insufficient privileges',
            'code' => 403
        ]);
        exit;
    }
}