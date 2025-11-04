<?php

/**
 * Schedula Uninstall Script
 *
 * Handles cleanup when the plugin is uninstalled.
 *
 * @package Schedula
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

/**
 * Recursively delete a directory and its contents using WP_Filesystem.
 *
 * @param string $dir The directory to delete.
 * @return bool True on success, false on failure.
 */
function schesab_recursive_delete($dir)
{
    if (!is_dir($dir)) {
        return false;
    }

    // Initialize WP_Filesystem
    if (!function_exists('WP_Filesystem')) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
    }

    WP_Filesystem();
    global $wp_filesystem;

    if (!$wp_filesystem) {
        return false;
    }

    return $wp_filesystem->rmdir($dir, true);
}

/**
 * Delete a single file using WordPress methods.
 *
 * @param string $file_path The file path to delete.
 * @return bool True on success, false on failure.
 */
function schesab_delete_file($file_path)
{
    if (!file_exists($file_path)) {
        return false;
    }

    return wp_delete_file($file_path);
}

// Check if data should be deleted based on settings
// Fetch the general settings option
$settings_option = get_option('schesab_general_settings');
$settings = [];

// The option should be a serialized array, but could be a JSON string.
if (is_string($settings_option)) {
    // Try to decode as JSON first
    $decoded = json_decode($settings_option, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        $settings = $decoded;
    } else {
        // Fallback to unserialize
        $unserialized = @unserialize($settings_option);
        if ($unserialized !== false) {
            $settings = $unserialized;
        }
    }
} elseif (is_array($settings_option)) {
    $settings = $settings_option;
}

// Only proceed with deletion if 'deleteDataOnUninstall' is explicitly set to 'delete'
if (!empty($settings) && isset($settings['deleteDataOnUninstall']) && $settings['deleteDataOnUninstall'] === 'delete') {
    // List of Schedula tables
    $tables = [
        'schesab_forms',
        'schesab_appointments',
        'schesab_customers',
        'schesab_services',
        'schesab_categories',
        'schesab_staff',
        'schesab_staff_services',
        'schesab_staff_schedule',
        'schesab_holidays',
        'schesab_payments',
        'schesab_newsletter_subscribers',
        // Add any other custom tables created by your plugin here
    ];

    // Drop each table
    foreach ($tables as $table) {
        $table_name = $wpdb->prefix . $table;
        // Use direct query for identifiers; do not prepare table names
        $wpdb->query("DROP TABLE IF EXISTS `{$table_name}`");
    }

    // Delete options related to Schedula
    $options_to_delete = [
        'schesab_general_settings',
        'schesab_company_settings',
        'schesab_paypal_settings',
        'schesab_stripe_settings',
        'schesab_appearance_settings',
        'schesab_db_version',
        'schesab_onboarding_complete',
        // Add other options if they exist, e.g., for analytics
    ];

    foreach ($options_to_delete as $option_name) {
        delete_option($option_name);
    }

    // Delete the assets directory, which may contain generated files.
    $assets_dir = plugin_dir_path(__FILE__) . 'assets';
    if (is_dir($assets_dir)) {
        schesab_recursive_delete($assets_dir);
    }
}

// Clear any scheduled cron jobs associated with the plugin
wp_clear_scheduled_hook('schesab_newsletter_cron');
// Clear any other custom scheduled hooks