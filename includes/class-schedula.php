<?php

/**
 * The core plugin class that runs everything for Schedula.
 *
 * @package Schedula
 */

namespace SCHESAB;

use SCHESAB\Database\SCHESAB_Database as Database; // Alias for the Database class
use SCHESAB\Api\Settings as ApiSettings; // Alias for the Settings API class
use WP_REST_Request; // Import WP_REST_Request for type hinting

if (!defined('ABSPATH')) {
    exit;
}

class Schesab
{
    // Placeholder for developer email for contact form submissions
    const DEV_EMAIL = 'support@vertimcoders.com';


    public function __construct()
    {
        require_once SCHESAB_PLUGIN_DIR . 'includes/utils/class-encryption.php';
        require_once SCHESAB_PLUGIN_DIR . 'includes/database/class-database.php';
        require_once SCHESAB_PLUGIN_DIR . 'includes/api/class-schedula-api.php';

        // Initialize admin functionalities only when in the admin area
        if (is_admin()) {
            require_once SCHESAB_PLUGIN_DIR . 'includes/admin/class-admin.php';
            new \SCHESAB\Admin\SCHESAB_Admin(); // Instantiate the Admin class
        }

        // Initialize frontend functionalities only when not in the admin area
        elseif (!is_admin()) {
            require_once SCHESAB_PLUGIN_DIR . 'includes/frontend/class-frontend.php';
            new \SCHESAB\Frontend\SCHESAB_Frontend(); // Instantiate the Frontend class
        }

        // Shared initialization (hooks that apply to both frontend and backend)
        $this->initialize_shared_hooks();

        // Initialize REST API classes
        $this->initialize_api_classes();

        // Handle database updates on plugin load
        add_action('plugins_loaded', [$this, 'handle_database_updates']);

        // Load plugin text domain for translations
        //add_action('init', [$this, 'localization_setup']);

        // Enqueue Font Awesome (general asset, but hooked to admin_enqueue_scripts)
        // add_action('admin_enqueue_scripts', [$this, 'enqueue_font_awesome']);
        // Also enqueue Font Awesome for frontend, as it's a general asset needed by virtual pages
        add_action('admin_enqueue_scripts', [$this, 'enqueue_global_style']);


        // Add custom cron schedules
        add_filter('cron_schedules', [$this, 'add_custom_cron_schedules']);

        // Run the main setup method
        $this->run();
    }

    /**
     * Initializes shared WordPress hooks (excluding virtual page setup, which is in run()).
     */
    private function initialize_shared_hooks()
    {
        add_action('init', [$this, 'register_custom_post_types']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        add_action('schesab_cleanup_pending_payments', [$this, 'cleanup_pending_payments']);
    }

    /**
     * Registers custom post types for the plugin.
     */
    public function register_custom_post_types()
    {
        // CPT registration code will go here, e.g., for services, staff, etc.
    }

    /**
     * Initializes and registers REST API classes.
     * This method ensures our custom API endpoints are available.
     */
    private function initialize_api_classes()
    {
        new \SCHESAB\Api\SCHESAB_Api(); // Instantiate the namespaced Api class
    }

    /**
     * Handles database updates by checking the DB version.
     * This method is called on 'plugins_loaded' action.
     */
    public function handle_database_updates()
    {
        $database = \SCHESAB\Database\SCHESAB_Database::get_instance(); // Use namespaced class directly
        $database->update_database();
    }




    /**
     * Enqueues frontend scripts.
     */
    public function enqueue_frontend_assets()
    {
        // Frontend specific scripts will go here.
        // Example: wp_enqueue_script('schedula-frontend-script', SCHESAB_PLUGIN_URL . 'public/js/frontend.js', array('jquery'), SCHESAB_VERSION, true);
    }

    /** Global styles */
    public function enqueue_global_style()
    {

        wp_enqueue_style(
            'schesab-style-css',
            SCHESAB_PLUGIN_URL . 'assets/css/style.css',
            [],
            SCHESAB_VERSION
        );
    }



    /**
     * Main run method for the plugin.
     * This is where setup for public-facing pages and other core functionalities
     * that don't need to be in the shared_hooks for other reasons are placed.
     */
    public function run()
    {
        $this->setup_virtual_pages();
    }

    /**
     * Sets up the rewrite rules and template handling for virtual pages.
     */
    public function setup_virtual_pages()
    {
        add_action('init', array($this, 'add_cancellation_rewrite_rule'));
        add_filter('query_vars', array($this, 'add_cancellation_query_vars'));
        add_filter('template_include', array($this, 'include_cancellation_template'));
    }

    /**
     * Adds the rewrite rule for the cancellation page.
     * The regex `([^/]+)` captures any string that is not a slash, for the token.
     */
    public function add_cancellation_rewrite_rule()
    {
        add_rewrite_rule('^schesab/cancel/([^/]+)/?$', 'index.php?is_schesab_cancel_page=1&token=$matches[1]', 'top');
        // Important: Flush rewrite rules after adding a new one, usually done on plugin activation/deactivation
        // flush_rewrite_rules(); // Call this only on activation/deactivation, not every 'init'
    }

    /**
     * Adds the token to the list of allowed query variables.
     */
    public function add_cancellation_query_vars($vars)
    {
        $vars[] = 'is_schesab_cancel_page';
        $vars[] = 'token';
        return $vars;
    }

    /**
     * Loads the cancellation page template if our query variable is set.
     */
    public function include_cancellation_template($template)
    {
        if (get_query_var('is_schesab_cancel_page')) {
            $plugin_template = SCHESAB_PLUGIN_DIR . 'public/cancellation-page-template.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        return $template;
    }

    /**
     * Adds custom cron schedules.
     * @param array $schedules
     * @return array
     */
    public function add_custom_cron_schedules($schedules)
    {
        $schedules['fifteen_minutes'] = array(
            'interval' => 15 * 60, // 15 minutes in seconds
            'display' => __('Every 15 Minutes', 'schedula-smart-appointment-booking'),
        );
        return $schedules;
    }

    /**
     * Cleans up expired pending payments.
     * This is triggered by a cron job.
     */
    public function cleanup_pending_payments()
    {
        $settings_api = new ApiSettings();
        $request = new WP_REST_Request();

        // Fetch PayPal settings
        $paypal_settings_response = $settings_api->get_paypal_settings($request);
        $paypal_settings = is_wp_error($paypal_settings_response) ? [] : $paypal_settings_response->get_data();

        // Fetch Stripe settings
        $stripe_settings_response = $settings_api->get_stripe_settings($request);
        $stripe_settings = is_wp_error($stripe_settings_response) ? [] : $stripe_settings_response->get_data();

        $paypal_interval = (int) ($paypal_settings['timeIntervalPaymentGateway'] ?? 0);
        $stripe_interval = (int) ($stripe_settings['timeIntervalPaymentGateway'] ?? 0);

        // Use the smallest positive interval as the cleanup time, or the larger one if one is zero.
        $cleanup_interval = 0;
        if ($paypal_interval > 0 && $stripe_interval > 0) {
            $cleanup_interval = min($paypal_interval, $stripe_interval);
        } else {
            $cleanup_interval = max($paypal_interval, $stripe_interval);
        }

        if ($cleanup_interval <= 0) {
            return; // Cleanup is disabled.
        }

        global $wpdb;
        $db = \SCHESAB\Database\SCHESAB_Database::get_instance();
        $appointments_table = $db->get_table_name('appointments');

        $cutoff_time = gmdate('Y-m-d H:i:s', time() - ($cleanup_interval * 60));


        // Find all 'incomplete' appointments older than the cutoff time.
        $incomplete_appointments = $wpdb->get_results($wpdb->prepare(
            "SELECT id FROM {$appointments_table} WHERE status = %s AND created_at < %s",
            'incomplete',
            $cutoff_time
        ));

        if (!empty($incomplete_appointments)) {
            $this->cancel_appointments($incomplete_appointments, 'Online Payment');
        }
    }

    private function cancel_appointments($appointments, $gateway_name)
    {
        if (empty($appointments)) {
            return;
        }

        global $wpdb;
        $db = \SCHESAB\Database\SCHESAB_Database::get_instance();
        $appointments_table = $db->get_table_name('appointments');

        $appointment_ids_to_cancel = wp_list_pluck($appointments, 'id');

        if (!empty($appointment_ids_to_cancel)) {
            $ids_placeholder = implode(',', array_fill(0, count($appointment_ids_to_cancel), '%d'));

            $result = $wpdb->query(
                $wpdb->prepare(
                    "UPDATE {$appointments_table} 
                     SET status = 'cancelled', payment_status = 'cancelled' 
                     WHERE id IN ($ids_placeholder)",
                    $appointment_ids_to_cancel
                )
            );

            if ($result !== false) {
                error_log(sprintf(
                    // translators: %1$d number of appointments, %2$s gateway name
                    __('Schedula Cron: Cancelled %1$d expired pending %2$s appointments.', 'schedula-smart-appointment-booking'),
                    count($appointment_ids_to_cancel),
                    $gateway_name
                ));
            } else {
                error_log(sprintf(
                    // translators: %1$s gateway name, %2$s database error message
                    __('Schedula Cron: DB error while cancelling expired %1$s appointments. %2$s', 'schedula-smart-appointment-booking'),
                    $gateway_name,
                    $wpdb->last_error
                ));
            }
        }
    }
}
