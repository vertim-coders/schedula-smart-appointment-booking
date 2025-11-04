<?php

/**
 * Plugin Activator for Schedula
 * File: includes/class-activator.php
 */

namespace SCHESAB;

use SCHESAB\Database\SCHESAB_Database;


if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}


/**
 * Handles plugin activation logic for Schedula
 */
class Schesab_Activator
{

    /**
     * Main activation method
     * This method is executed when the plugin is activated
     */
    public static function activate()
    {

        // Check system requirements
        self::check_requirements();

        // Get namespaced Database instance
        $database = \SCHESAB\Database\SCHESAB_Database::get_instance(); // Use namespaced class directly
        $database->create_tables(); // Create all tables
        // $database->seed_data();

        // Update the DB version option AFTER tables are created and seeded
        // Use namespaced Database::DB_VERSION
        update_option('schesab_db_version', \SCHESAB\Database\SCHESAB_Database::DB_VERSION);

        // Define custom roles and capabilities
        self::create_roles_and_capabilities();

        // Schedule cron jobs
        self::schedule_cron_jobs();

        // Create necessary pages (booking, confirmation, etc.)
        self::create_pages();



        // Save plugin version and activation date
        // SCHEDULA_VERSION is defined in the root schedula.php, so it's globally available
        update_option('schesab_version', SCHESAB_VERSION);
        update_option('schesab_activation_date', current_time('mysql'));
    }

    /**
     * Verify system requirements (PHP, WordPress, extensions)
     */
    private static function check_requirements()
    {

        // Check minimum PHP version
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            deactivate_plugins(plugin_basename(SCHESAB_PLUGIN_DIR . 'schedula.php'));
            wp_die(esc_html__('Schedula requires PHP 7.4 or higher. Please upgrade your PHP version.', 'schedula-smart-appointment-booking'));
        }

        // Check minimum WordPress version
        if (version_compare(get_bloginfo('version'), '5.0', '<')) {
            deactivate_plugins(plugin_basename(SCHESAB_PLUGIN_DIR . 'schedula.php'));
            wp_die(esc_html__('Schedula requires WordPress 5.0 or higher. Please upgrade your WordPress version.', 'schedula-smart-appointment-booking'));
        }

        // Ensure required PHP extensions are available
        $required_extensions = array('mysqli', 'json', 'curl');
        foreach ($required_extensions as $extension) {
            if (!extension_loaded($extension)) {
                deactivate_plugins(plugin_basename(SCHESAB_PLUGIN_DIR . 'schedula.php'));
                // translators: %s is the name of the required PHP extension
                $message = __('Schedula requires the %s PHP extension. Please install it.', 'schedula-smart-appointment-booking');
                wp_die(esc_html(sprintf($message, $extension)));
            }
        }
    }

    /**
     * Create custom roles and assign capabilities
     */
    private static function create_roles_and_capabilities()
    {

        // List of custom capabilities for the plugin
        $capabilities = array(
            'manage_schedula',
            'manage_schesab_appointments',
            'manage_schesab_staff',
            'manage_schesab_services',
            'manage_schesab_customers',
            'view_schesab_reports',
            'edit_schesab_settings'
        );

        // Add capabilities to administrator role
        $admin_role = get_role('administrator');
        if ($admin_role) {
            foreach ($capabilities as $capability) {
                $admin_role->add_cap($capability);
            }
        }

        // Create Staff role
        add_role(
            'schesab_staff',
            __('Staff Schedula', 'schedula-smart-appointment-booking'),
            array(
                'read' => true,
                'manage_schesab_appointments' => true,
                'view_schesab_reports' => true
            )
        );

        // Create Manager role
        add_role(
            'schesab_manager',
            __('Manager Schedula', 'schedula-smart-appointment-booking'),
            array(
                'read' => true,
                'manage_schedula' => true,
                'manage_schesab_appointments' => true,
                'manage_schesab_staff' => true,
                'manage_schesab_services' => true,
                'manage_schesab_customers' => true,
                'view_schesab_reports' => true
            )
        );
    }

    /**
     * Schedule recurring cron jobs for the plugin
     */
    private static function schedule_cron_jobs()
    {

        // Hourly reminders for upcoming appointments
        if (!wp_next_scheduled('schesab_send_reminders')) {
            wp_schedule_event(time(), 'hourly', 'schesab_send_reminders');
        }

        // Daily cleanup tasks (temporary data)
        if (!wp_next_scheduled('schesab_cleanup')) {
            wp_schedule_event(time(), 'daily', 'schesab_cleanup');
        }

        // Twice daily sync with external calendars
        if (!wp_next_scheduled('schesab_sync_calendars')) {
            wp_schedule_event(time(), 'twicedaily', 'schesab_sync_calendars');
        }

        // Weekly report generation
        if (!wp_next_scheduled('schesab_generate_reports')) {
            wp_schedule_event(time(), 'weekly', 'schesab_generate_reports');
        }
    }

    /**
     * Create required pages like booking form, confirmation, etc.
     */
    private static function create_pages()
    {

        // Stripe Return Page
        $stripe_return_page_id = get_option('schesab_stripe_return_page_id');
        
        // Check if page exists by ID
        $page_exists_by_id = false;
        if ($stripe_return_page_id) {
            $page_by_id = get_post($stripe_return_page_id);
            if ($page_by_id && $page_by_id->post_type === 'page') {
                $page_exists_by_id = true;
            }
        }
        
        // Also check for existing system pages by meta
        global $wpdb;
        $existing_by_meta = $wpdb->get_var(
            "SELECT post_id FROM {$wpdb->postmeta} 
             WHERE meta_key = '_schesab_page_type' 
             AND meta_value = 'stripe_return' 
             LIMIT 1"
        );
        
        if ($existing_by_meta && !$page_exists_by_id) {
            // Found by meta but option not set, update option
            update_option('schesab_stripe_return_page_id', $existing_by_meta);
            $page_exists_by_id = true;
        }
        
        if (!$page_exists_by_id && !$existing_by_meta) {
            // Check for existing page by both old and new slugs (for migration)
            $existing_page = get_page_by_path('schesab-stripe-return');
            if (!$existing_page) {
                $existing_page = get_page_by_path('schedula-stripe-return'); // Check old slug
            }
            if (!$existing_page) {
                $stripe_return_page = array(
                    'post_title' => __('Stripe Payment Confirmation', 'schedula-smart-appointment-booking'),
                    'post_name' => 'schesab-stripe-return', // slug
                    'post_content' => '[schesab_stripe_return]',
                    'post_status' => 'publish',
                    'post_type' => 'page',
                    'post_author' => 1,
                    'comment_status' => 'closed',
                    'ping_status' => 'closed',
                    'meta_input' => array(
                        '_schesab_system_page' => '1',
                        '_schesab_page_type' => 'stripe_return'
                    )
                );
                $stripe_return_page_id = wp_insert_post($stripe_return_page);
                if ($stripe_return_page_id) {
                    update_option('schesab_stripe_return_page_id', $stripe_return_page_id);
                }
            } else {
                // Page exists, update option and fix slug/shortcode if needed
                update_option('schesab_stripe_return_page_id', $existing_page->ID);
                
                // Ensure system metadata is set
                update_post_meta($existing_page->ID, '_schesab_system_page', '1');
                update_post_meta($existing_page->ID, '_schesab_page_type', 'stripe_return');
                
                $needs_update = false;
                $update_data = array('ID' => $existing_page->ID);
                
                // Fix slug if it's the old one
                if ($existing_page->post_name === 'schedula-stripe-return') {
                    $update_data['post_name'] = 'schesab-stripe-return';
                    $needs_update = true;
                }
                
                // Fix shortcode if it's the old one
                if (strpos($existing_page->post_content, '[schedula_stripe_return]') !== false) {
                    $update_data['post_content'] = str_replace('[schedula_stripe_return]', '[schesab_stripe_return]', $existing_page->post_content);
                    $needs_update = true;
                }
                
                if ($needs_update) {
                    wp_update_post($update_data);
                }
            }
        } else {
            // Fix existing page if it has the wrong shortcode name or slug
            $stripe_return_page = get_post($stripe_return_page_id);
            if ($stripe_return_page) {
                // Ensure system metadata is set
                update_post_meta($stripe_return_page_id, '_schesab_system_page', '1');
                update_post_meta($stripe_return_page_id, '_schesab_page_type', 'stripe_return');
                
                $needs_update = false;
                $update_data = array('ID' => $stripe_return_page_id);
                
                // Fix slug if it's the old one
                if ($stripe_return_page->post_name === 'schedula-stripe-return') {
                    $update_data['post_name'] = 'schesab-stripe-return';
                    $needs_update = true;
                }
                
                // Fix shortcode if it's the old one
                if (strpos($stripe_return_page->post_content, '[schedula_stripe_return]') !== false) {
                    $update_data['post_content'] = str_replace('[schedula_stripe_return]', '[schesab_stripe_return]', $stripe_return_page->post_content);
                    $needs_update = true;
                }
                
                if ($needs_update) {
                    wp_update_post($update_data);
                }
            }
        }

        // Stripe Cancel Page
        $stripe_cancel_page_id = get_option('schesab_stripe_cancel_page_id');
        
        // Check if page exists by ID
        $page_exists_by_id = false;
        if ($stripe_cancel_page_id) {
            $page_by_id = get_post($stripe_cancel_page_id);
            if ($page_by_id && $page_by_id->post_type === 'page') {
                $page_exists_by_id = true;
            }
        }
        
        // Also check for existing system pages by meta
        $existing_by_meta = $wpdb->get_var(
            "SELECT post_id FROM {$wpdb->postmeta} 
             WHERE meta_key = '_schesab_page_type' 
             AND meta_value = 'stripe_cancel' 
             LIMIT 1"
        );
        
        if ($existing_by_meta && !$page_exists_by_id) {
            // Found by meta but option not set, update option
            update_option('schesab_stripe_cancel_page_id', $existing_by_meta);
            $page_exists_by_id = true;
        }
        
        if (!$page_exists_by_id && !$existing_by_meta) {
            // Check for existing page by both old and new slugs (for migration)
            $existing_page = get_page_by_path('schesab-stripe-cancel');
            if (!$existing_page) {
                $existing_page = get_page_by_path('schedula-stripe-cancel'); // Check old slug
            }
            if (!$existing_page) {
                $stripe_cancel_page = array(
                    'post_title' => __('Stripe Payment Cancelled', 'schedula-smart-appointment-booking'),
                    'post_name' => 'schesab-stripe-cancel', // slug
                    'post_content' => '[schesab_stripe_cancel]',
                    'post_status' => 'publish',
                    'post_type' => 'page',
                    'post_author' => 1,
                    'comment_status' => 'closed',
                    'ping_status' => 'closed',
                    'meta_input' => array(
                        '_schesab_system_page' => '1',
                        '_schesab_page_type' => 'stripe_cancel'
                    )
                );
                $stripe_cancel_page_id = wp_insert_post($stripe_cancel_page);
                if ($stripe_cancel_page_id) {
                    update_option('schesab_stripe_cancel_page_id', $stripe_cancel_page_id);
                }
            } else {
                // Page exists, update option and fix slug/shortcode if needed
                update_option('schesab_stripe_cancel_page_id', $existing_page->ID);
                
                // Ensure system metadata is set
                update_post_meta($existing_page->ID, '_schesab_system_page', '1');
                update_post_meta($existing_page->ID, '_schesab_page_type', 'stripe_cancel');
                
                $needs_update = false;
                $update_data = array('ID' => $existing_page->ID);
                
                // Fix slug if it's the old one
                if ($existing_page->post_name === 'schedula-stripe-cancel') {
                    $update_data['post_name'] = 'schesab-stripe-cancel';
                    $needs_update = true;
                }
                
                // Fix shortcode if it's the old one
                if (strpos($existing_page->post_content, '[schedula_stripe_cancel]') !== false) {
                    $update_data['post_content'] = str_replace('[schedula_stripe_cancel]', '[schesab_stripe_cancel]', $existing_page->post_content);
                    $needs_update = true;
                }
                
                if ($needs_update) {
                    wp_update_post($update_data);
                }
            }
        } else {
            // Fix existing page if it has the wrong shortcode name or slug
            $stripe_cancel_page = get_post($stripe_cancel_page_id);
            if ($stripe_cancel_page) {
                // Ensure system metadata is set
                update_post_meta($stripe_cancel_page_id, '_schesab_system_page', '1');
                update_post_meta($stripe_cancel_page_id, '_schesab_page_type', 'stripe_cancel');
                
                $needs_update = false;
                $update_data = array('ID' => $stripe_cancel_page_id);
                
                // Fix slug if it's the old one
                if ($stripe_cancel_page->post_name === 'schedula-stripe-cancel') {
                    $update_data['post_name'] = 'schesab-stripe-cancel';
                    $needs_update = true;
                }
                
                // Fix shortcode if it's the old one
                if (strpos($stripe_cancel_page->post_content, '[schedula_stripe_cancel]') !== false) {
                    $update_data['post_content'] = str_replace('[schedula_stripe_cancel]', '[schesab_stripe_cancel]', $stripe_cancel_page->post_content);
                    $needs_update = true;
                }
                
                if ($needs_update) {
                    wp_update_post($update_data);
                }
            }
        }
    }

    /**
     * Define default plugin options/settings
     */
    private static function set_default_options()
    {

        $default_options = array(
            'schesab_general_settings' => array(
                'time_slot_length' => 30,
                'booking_window' => 30,
                'min_time_before_booking' => 24,
                'max_time_before_booking' => 365,
                'default_appointment_status' => 'pending',
                'currency' => 'EUR',
                'date_format' => 'Y-m-d',
                'time_format' => 'H:i'
            ),
            'schesab_notification_settings' => array(
                'send_confirmation_email' => true,
                'send_reminder_email' => true,
                'reminder_time' => 24,
                'admin_notifications' => true
            ),
            'schesab_appearance_settings' => array(
                'theme' => 'default',
                'primary_color' => '#007cba',
                'secondary_color' => '#50575e'
            )
        );

        foreach ($default_options as $option_name => $option_value) {
            if (get_option($option_name) === false) {
                update_option($option_name, $option_value);
            }
        }
    }
}