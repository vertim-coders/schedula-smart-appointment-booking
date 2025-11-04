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

        // Booking page
        $booking_page_id = get_option('schesab_booking_page_id');
        if (!$booking_page_id || !get_post($booking_page_id)) {
            // Vérifier aussi par slug au cas où l'option serait perdue
            $existing_page = get_page_by_path('schedula-booking');
            if (!$existing_page) {
                $booking_page = array(
                    'post_title' => __('Booking', 'schedula-smart-appointment-booking'),
                    'post_name' => 'schedula-booking', // slug
                    'post_content' => '[schesab_booking_form]',
                    'post_status' => 'publish',
                    'post_type' => 'page',
                    'post_author' => 1
                );
                $booking_page_id = wp_insert_post($booking_page);
                if ($booking_page_id) {
                    update_option('schesab_booking_page_id', $booking_page_id);
                }
            } else {
                // La page existe, mettre à jour l'option
                update_option('schesab_booking_page_id', $existing_page->ID);
            }
        }

        // My Appointments page
        $appointments_page_id = get_option('schesab_appointments_page_id');
        if (!$appointments_page_id || !get_post($appointments_page_id)) {
            // Vérifier aussi par slug au cas où l'option serait perdue
            $existing_page = get_page_by_path('schedula-my-appointments');
            if (!$existing_page) {
                $appointments_page = array(
                    'post_title' => __('My Appointments', 'schedula-smart-appointment-booking'),
                    'post_name' => 'schedula-my-appointments', // slug
                    'post_content' => '[schesab_customer_appointments]',
                    'post_status' => 'publish',
                    'post_type' => 'page',
                    'post_author' => 1
                );
                $appointments_page_id = wp_insert_post($appointments_page);
                if ($appointments_page_id) {
                    update_option('schesab_appointments_page_id', $appointments_page_id);
                }
            } else {
                // La page existe, mettre à jour l'option
                update_option('schesab_appointments_page_id', $existing_page->ID);
            }
        }

        // Booking Confirmation page
        $confirmation_page_id = get_option('schesab_confirmation_page_id');
        if (!$confirmation_page_id || !get_post($confirmation_page_id)) {
            // Vérifier aussi par slug au cas où l'option serait perdue
            $existing_page = get_page_by_path('schedula-booking-confirmation');
            if (!$existing_page) {
                $confirmation_page = array(
                    'post_title' => __('Booking Confirmation', 'schedula-smart-appointment-booking'),
                    'post_name' => 'schedula-booking-confirmation', // slug
                    'post_content' => '[schesab_booking_confirmation]',
                    'post_status' => 'publish',
                    'post_type' => 'page',
                    'post_author' => 1
                );
                $confirmation_page_id = wp_insert_post($confirmation_page);
                if ($confirmation_page_id) {
                    update_option('schesab_confirmation_page_id', $confirmation_page_id);
                }
            } else {
                // La page existe, mettre à jour l'option
                update_option('schesab_confirmation_page_id', $existing_page->ID);
            }
        }

      

        // Stripe Return Page
        $stripe_return_page_id = get_option('schesab_stripe_return_page_id');
        if (!$stripe_return_page_id || !get_post($stripe_return_page_id)) {
            // Vérifier aussi par slug au cas où l'option serait perdue
            $existing_page = get_page_by_path('schedula-stripe-return');
            if (!$existing_page) {
                $stripe_return_page = array(
                    'post_title' => __('Stripe Payment Confirmation', 'schedula-smart-appointment-booking'),
                    'post_name' => 'schedula-stripe-return', // slug
                    'post_content' => '[schesab_stripe_return]',
                    'post_status' => 'publish',
                    'post_type' => 'page',
                    'post_author' => 1
                );
                $stripe_return_page_id = wp_insert_post($stripe_return_page);
                if ($stripe_return_page_id) {
                    update_option('schesab_stripe_return_page_id', $stripe_return_page_id);
                }
            } else {
                // La page existe, mettre à jour l'option
                update_option('schesab_stripe_return_page_id', $existing_page->ID);
            }
        }

        // Stripe Cancel Page
        $stripe_cancel_page_id = get_option('schesab_stripe_cancel_page_id');
        if (!$stripe_cancel_page_id || !get_post($stripe_cancel_page_id)) {
            // Vérifier aussi par slug au cas où l'option serait perdue
            $existing_page = get_page_by_path('schedula-stripe-cancel');
            if (!$existing_page) {
                $stripe_cancel_page = array(
                    'post_title' => __('Stripe Payment Cancelled', 'schedula-smart-appointment-booking'),
                    'post_name' => 'schedula-stripe-cancel', // slug
                    'post_content' => '[schesab_stripe_cancel]',
                    'post_status' => 'publish',
                    'post_type' => 'page',
                    'post_author' => 1
                );
                $stripe_cancel_page_id = wp_insert_post($stripe_cancel_page);
                if ($stripe_cancel_page_id) {
                    update_option('schesab_stripe_cancel_page_id', $stripe_cancel_page_id);
                }
            } else {
                // La page existe, mettre à jour l'option
                update_option('schesab_stripe_cancel_page_id', $existing_page->ID);
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