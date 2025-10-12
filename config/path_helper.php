<?php
/**
 * Path Helper Functions
 * Provides centralized path resolution for the application
 * Eliminates hardcoded paths and ensures portability
 */

// Define ROOT_PATH if not already defined
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', realpath(__DIR__ . '/../') . DIRECTORY_SEPARATOR);
}

/**
 * Get absolute path relative to project root
 *
 * @param string $path Relative path from project root
 * @return string Absolute path
 */
function app_path(string $path = ''): string
{
    $path = ltrim($path, '/\\');
    return ROOT_PATH . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
}

/**
 * Get config file path
 *
 * @param string $file Config filename
 * @return string Absolute path to config file
 */
function config_path(string $file = ''): string
{
    return app_path('config' . DIRECTORY_SEPARATOR . $file);
}

/**
 * Get helper file path
 *
 * @param string $file Helper filename
 * @return string Absolute path to helper file
 */
function helper_path(string $file = ''): string
{
    return app_path('helpers' . DIRECTORY_SEPARATOR . $file);
}

/**
 * Get vendor file path
 *
 * @param string $file Vendor filename
 * @return string Absolute path to vendor file
 */
function vendor_path(string $file = ''): string
{
    return app_path('vendor' . DIRECTORY_SEPARATOR . $file);
}

/**
 * Get URL path (for HTML links and redirects)
 *
 * @param string $path Relative path from BASE_URL
 * @return string Full URL
 */
function url(string $path = ''): string
{
    // Load config if BASE_URL not defined
    if (!defined('BASE_URL')) {
        if (class_exists('Config')) {
            define('BASE_URL', Config::getString('BASE_URL', ''));
        }
    }

    $path = ltrim($path, '/');
    $baseUrl = rtrim(BASE_URL ?? '', '/');

    return $baseUrl . ($path ? '/' . $path : '');
}

/**
 * Get asset URL (for CSS, JS, images)
 *
 * @param string $path Relative path from assets directory
 * @return string Full URL to asset
 */
function asset(string $path = ''): string
{
    $path = ltrim($path, '/');
    return url('assets/' . $path);
}

/**
 * Redirect to a URL
 *
 * @param string $path Relative path from BASE_URL
 * @param int $code HTTP status code
 */
function redirect(string $path = '', int $code = 302): void
{
    header('Location: ' . url($path), true, $code);
    exit;
}

/**
 * Redirect back to previous page or fallback
 *
 * @param string $fallback Fallback URL if no referrer
 */
function redirect_back(string $fallback = ''): void
{
    $referrer = $_SERVER['HTTP_REFERER'] ?? '';
    if ($referrer) {
        header('Location: ' . $referrer);
    } else {
        redirect($fallback);
    }
    exit;
}
