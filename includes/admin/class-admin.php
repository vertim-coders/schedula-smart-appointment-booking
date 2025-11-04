<?php

/**
 * Schedula Admin Class.
 * Handles admin menu and asset enqueuing for the Schedula plugin.
 *
 * @package Schedula
 * @subpackage Admin
 */

namespace SCHESAB\Admin;

use SCHESAB\Schesab;

if (!defined('ABSPATH')) {
    exit;
}

class SCHESAB_Admin
{

    public function __construct()
    {
        add_action('admin_menu', [$this, 'add_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function add_menu()
    {
        // Main top-level menu page for Schedula
        add_menu_page(
            __('Schedula Dashboard', 'schedula-smart-appointment-booking'),
            __('Schedula', 'schedula-smart-appointment-booking'),
            'manage_options',
            'schesab',
            [$this, 'render_admin_page'],
            'dashicons-calendar-alt',
            30
        );

        add_submenu_page(
            'schesab',
            __('Dashboard', 'schedula-smart-appointment-booking'),
            __('Dashboard', 'schedula-smart-appointment-booking'),
            'manage_options',
            'schesab',
            [$this, 'render_admin_page']
        );

        add_submenu_page(
            'schesab',
            __('Appointments', 'schedula-smart-appointment-booking'),
            __('Appointments', 'schedula-smart-appointment-booking'),
            'manage_options',
            'schesab#/appointments',
            [$this, 'render_admin_page']
        );

        add_submenu_page(
            'schesab',
            __('Agenda', 'schedula-smart-appointment-booking'),
            __('Agenda', 'schedula-smart-appointment-booking'),
            'manage_options',
            'schesab#/agenda',
            [$this, 'render_admin_page']
        );

        add_submenu_page(
            'schesab',
            __('Staff', 'schedula-smart-appointment-booking'),
            __('Staff', 'schedula-smart-appointment-booking'),
            'manage_options',
            'schesab#/staff',
            [$this, 'render_admin_page']
        );

        add_submenu_page(
            'schesab',
            __('Services & Categories', 'schedula-smart-appointment-booking'),
            __('Services & Categories', 'schedula-smart-appointment-booking'),
            'manage_options',
            'schesab#/services-categories',
            [$this, 'render_admin_page']
        );

        add_submenu_page(
            'schesab',
            __('Clients', 'schedula-smart-appointment-booking'),
            __('Clients', 'schedula-smart-appointment-booking'),
            'manage_options',
            'schesab#/clients',
            [$this, 'render_admin_page']
        );

        add_submenu_page(
            'schesab',
            __('Email Notifications', 'schedula-smart-appointment-booking'),
            __('Email Notifications', 'schedula-smart-appointment-booking'),
            'manage_options',
            'schesab#/notifications',
            [$this, 'render_admin_page']
        );

        add_submenu_page(
            'schesab',
            __('Custom Forms', 'schedula-smart-appointment-booking'),
            __('Custom Forms', 'schedula-smart-appointment-booking'),
            'manage_options',
            'schesab#/appearance',
            [$this, 'render_admin_page']
        );

        add_submenu_page(
            'schesab',
            __('Payments', 'schedula-smart-appointment-booking'),
            __('Payments', 'schedula-smart-appointment-booking'),
            'manage_options',
            'schesab#/payments',
            [$this, 'render_admin_page']
        );

        add_submenu_page(
            'schesab',
            __('Settings', 'schedula-smart-appointment-booking'),
            __('Settings', 'schedula-smart-appointment-booking'),
            'manage_options',
            'schesab#/settings',
            [$this, 'render_admin_page']
        );

        add_submenu_page(
            'schesab',
            __('Go Pro', 'schedula-smart-appointment-booking'),
            __('Go Pro', 'schedula-smart-appointment-booking'),
            'manage_options',
            'schesab#/go-pro',
            [$this, 'render_admin_page']
        );
    }

    /**
     * Enqueue admin scripts and styles.
     */
    public function enqueue_assets($hook)
    {
        $schesab_pages = [
            'toplevel_page_schesab',
            'schesab_page_schesab-appointments',
            'schesab_page_schesab-agenda',
            'schesab_page_schesab-staff',
            'schesab_page_schesab-services-categories',
            'schesab_page_schesab-clients',
            'schesab_page_schesab-notifications',
            'schesab_page_schesab-appearance',
            'schesab_page_schesab-payments',
            'schesab_page_schesab-settings',
            'schesab_page_schesab-go-pro',
        ];

        if (!in_array($hook, $schesab_pages)) {
            return;
        }

        $runtime_js_path = SCHESAB_PLUGIN_DIR . 'assets/js/runtime.js';
        $vendor_js_path = SCHESAB_PLUGIN_DIR . 'assets/js/vendors.js';
        $app_js_path = SCHESAB_PLUGIN_DIR . 'assets/js/admin.js';
        $admin_css_path = SCHESAB_PLUGIN_DIR . 'assets/css/admin.css';
        $vendors_css_path = SCHESAB_PLUGIN_DIR . 'assets/css/vendors.css';

        wp_enqueue_media();

        // Enqueue jsPDF from local file (WordPress guideline compliance)
        $jspdf_path = SCHESAB_PLUGIN_DIR . 'assets/utils/jspdf.umd.min.js';
        if (file_exists($jspdf_path)) {
            wp_enqueue_script(
                'jspdf',
                SCHESAB_PLUGIN_URL . 'assets/utils/jspdf.umd.min.js',
                [],
                filemtime($jspdf_path),
                true
            );
        } else {
            error_log('Schedula: jspdf.umd.min.js not found at ' . $jspdf_path);
        }

        if (file_exists($runtime_js_path)) {
            wp_enqueue_script(
                'schedula-runtime-js',
                SCHESAB_PLUGIN_URL . 'assets/js/runtime.js',
                [],
                filemtime($runtime_js_path),
                true
            );
        } else {
            error_log('Schedula: runtime.js not found at ' . $runtime_js_path);
        }

        if (file_exists($vendor_js_path)) {
            wp_enqueue_script(
                'schedula-vendor-js',
                SCHESAB_PLUGIN_URL . 'assets/js/vendors.js',
                ['schedula-runtime-js'],
                filemtime($vendor_js_path),
                true
            );
        } else {
            error_log('Schedula: vendors.js not found at ' . $vendor_js_path);
        }

        if (file_exists($app_js_path)) {
            wp_enqueue_script(
                'schedula-app-js',
                SCHESAB_PLUGIN_URL . 'assets/js/admin.js',
                ['schedula-vendor-js', 'jquery', 'wp-i18n', 'jspdf'], // Ajoutez 'jspdf' dans les dépendances
                filemtime($app_js_path),
                true
            );
        } else {
            error_log('Schedula: admin.js not found at ' . $app_js_path);
        }

        wp_enqueue_script(
            'schedula-menu-fix-js',
            SCHESAB_PLUGIN_URL . 'assets/utils/schedula-menu-fix.js',
            ['jquery'],
            SCHESAB_VERSION,
            true
        );

        if (file_exists($admin_css_path)) {
            wp_enqueue_style(
                'schedula-admin-css',
                SCHESAB_PLUGIN_URL . 'assets/css/admin.css',
                [],
                filemtime($admin_css_path)
            );
        } else {
            error_log('Schedula: admin.css not found at ' . $admin_css_path);
        }

        if (file_exists($vendors_css_path)) {
            wp_enqueue_style(
                'schedula-vendors-css',
                SCHESAB_PLUGIN_URL . 'assets/css/vendors.css',
                [],
                filemtime($vendors_css_path)
            );
        } else {
            error_log('Schedula: vendors.css not found at ' . $vendors_css_path);
        }

        if (wp_script_is('schedula-app-js', 'enqueued')) {
            wp_localize_script('schedula-app-js', 'schedulaData', [
                'apiUrl' => rest_url('schesab/v1'),
                'nonce' => wp_create_nonce('wp_rest'),
                'pluginUrl' => SCHESAB_PLUGIN_URL,
                'currentAdminPage' => isset($_GET['page']) ? sanitize_text_field(wp_unslash($_GET['page'])) : 'schesab',
                'onboardingComplete' => (bool) get_option('schesab_onboarding_complete', false),
            ]);
        }
    }

    public function render_admin_page()
    {
        ?>
        <div class="wrap">
            <div id="schesab-admin-app"></div>
        </div>
        <?php
    }
}