<?php

/**
 * Schedula Frontend Class
 *
 * Handles frontend rendering and asset enqueuing for Schedula plugin.
 *
 * @package Schedula
 * @subpackage Frontend
 */

namespace SCHESAB\Frontend;



if (!defined('ABSPATH')) {
    exit;
}

class SCHESAB_Frontend
{

    private $db;
    // Static property to track if a service form has already been rendered in this request
    private static $rendered_form_ids = [];


    public function __construct()
    {
        $this->db = \SCHESAB\Database\SCHESAB_Database::get_instance();

        // Register shortcodes
        add_shortcode('schesab_reservation_form', [$this, 'render_reservation_form']);
        add_shortcode('schesab_service_form', [$this, 'render_service_form']);


        // Instantiate Stripe shortcodes handler and register its shortcodes
        require_once SCHESAB_PLUGIN_DIR . 'includes/frontend/class-shortcodesstripe.php';
        $stripe_shortcodes = new SCHESAB_ShortcodesStripe();
        add_shortcode('schesab_stripe_return', [$stripe_shortcodes, 'handle_stripe_return_shortcode']);
        add_shortcode('schesab_stripe_cancel', [$stripe_shortcodes, 'handle_stripe_cancel_shortcode']);

        // Enqueue frontend assets on the appropriate hook
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
    }

    /**
     * Enqueues frontend scripts and styles.
     * This method is called on 'wp_enqueue_scripts' hook, but we'll only enqueue
     * assets if our shortcodes are detected on the current page.
     */
    public function enqueue_frontend_assets()
    {
        global $post;

        // Ensure $post is a WP_Post object before checking content
        if (!is_a($post, 'WP_Post')) {
            return;
        }


        $has_stripe_return = has_shortcode($post->post_content, 'schesab_stripe_return');

        if ($has_stripe_return) {
            $payment_status_js_path = SCHESAB_PLUGIN_DIR . 'assets/js/payment-status.js';
            if (file_exists($payment_status_js_path)) {
                wp_enqueue_script(
                    'schedula-payment-status',
                    SCHESAB_PLUGIN_URL . 'assets/js/payment-status.js',
                    [],
                    filemtime($payment_status_js_path),
                    true
                );
            }
        }

        // Check if either shortcode is present in the post content
        $has_reservation_form = has_shortcode($post->post_content, 'schesab_reservation_form');
        $has_service_form = preg_match('/\\[schesab_service_form\\s+id=[\'"]?\d+[\'"]?\\]/', $post->post_content);



        // Only proceed if at least one shortcode is present
        if (!$has_reservation_form && !$has_service_form) {
            return;
        }

        // --- Enqueue Google Fonts (Inter and Roboto) ---
        // Use system fonts instead of external Google Fonts
        // Removed external Google Fonts dependency to comply with WordPress plugin guidelines

        // Define full file paths for compiled assets
        $runtime_js_path = SCHESAB_PLUGIN_DIR . 'assets/js/runtime.js';
        $vendor_js_path = SCHESAB_PLUGIN_DIR . 'assets/js/vendors.js';

        // Enqueue runtime.js (Webpack's runtime code)
        if (file_exists($runtime_js_path)) {
            wp_enqueue_script(
                'schedula-frontend-runtime-js',
                SCHESAB_PLUGIN_URL . 'assets/js/runtime.js',
                [],
                filemtime($runtime_js_path),
                true
            );
        }

        // Enqueue vendors.js (third-party libraries like Vue, Vue Router)
        if (file_exists($vendor_js_path)) {
            wp_enqueue_script(
                'schedula-vendor-js',
                SCHESAB_PLUGIN_URL . 'assets/js/vendors.js',
                ['schedula-frontend-runtime-js'],
                filemtime($vendor_js_path),
                true
            );
        }



        // Enqueue assets for [schesab_reservation_form]
        if ($has_reservation_form) {
            $app_js_path = SCHESAB_PLUGIN_DIR . 'assets/js/frontend.js';
            if (file_exists($app_js_path)) {
                wp_enqueue_script(
                    'schesab-frontend-app-js',
                    SCHESAB_PLUGIN_URL . 'assets/js/frontend.js',
                    ['schedula-vendor-js', 'wp-i18n'],
                    filemtime($app_js_path),
                    true
                );

                require_once SCHESAB_PLUGIN_DIR . 'includes/api/class-schedula-api-appearance.php';
                $appearance_api = new \SCHESAB\Api\SCHESAB_Appearance();
                $request = new \WP_REST_Request('GET', '/schesab/v1/public/appearance-settings');
                $response = $appearance_api->get_public_settings($request);
                $appearance_settings = [];
                if (!is_wp_error($response) && $response->get_status() === 200) {
                    $data = $response->get_data();
                    if (isset($data['data'])) {
                        $appearance_settings = $data['data'];
                    }
                }

                // --- Pre-load all other necessary data ---
                require_once SCHESAB_PLUGIN_DIR . 'includes/api/class-schedula-api-settings.php';
                $settings_api = new \SCHESAB\Api\SCHESAB_Settings();
                $global_settings = $settings_api->get_general_settings(new \WP_REST_Request('GET', '/schesab/v1/general-settings'))->get_data() ?? [];
                $stripe_settings = $settings_api->get_stripe_settings(new \WP_REST_Request('GET', '/schesab/v1/stripe-settings'))->get_data() ?? [];

                require_once SCHESAB_PLUGIN_DIR . 'includes/api/class-schedula-api-appointments.php';
                $appointments_api = new \SCHESAB\Api\SCHESAB_Appointments();
                $booking_form_data = $appointments_api->get_booking_form_data(new \WP_REST_Request('GET', '/schesab/v1/booking-form-data'))->get_data() ?? [];


                // Localize script for the Vue.js app to pass data from PHP to JavaScript
                wp_localize_script('schesab-frontend-app-js', 'schedulaFrontendData', [
                    'apiUrl' => esc_url_raw(rest_url('schesab/v1')),
                    'nonce' => wp_create_nonce('wp_rest'),
                    'settings' => $appearance_settings,
                    'global_settings' => $global_settings,
                    'stripe_settings' => $stripe_settings,
                    'booking_form_data' => $booking_form_data,
                    'pluginUrl' => SCHESAB_PLUGIN_URL,
                ]);
            }

            $frontend_css_path = SCHESAB_PLUGIN_DIR . 'assets/css/frontend.css';
            $vendors_css_path = SCHESAB_PLUGIN_DIR . 'assets/css/vendors.css';

            if (file_exists($frontend_css_path)) {
                wp_enqueue_style(
                    'schedula-frontend-css',
                    SCHESAB_PLUGIN_URL . 'assets/css/frontend.css',
                    [],
                    filemtime($frontend_css_path)
                );
            }
            //vendor.css
            if (file_exists($vendors_css_path)) {
                wp_enqueue_style(
                    'schedula-vendors-css',
                    SCHESAB_PLUGIN_URL . 'assets/css/vendors.css',
                    [],
                    filemtime($vendors_css_path)
                );
            }
        }

        // Enqueue assets for [schesab_service_form id="X"]
        if ($has_service_form) {
            $service_js_path = SCHESAB_PLUGIN_DIR . 'assets/js/serviceformclient.js';
            if (file_exists($service_js_path)) {
                wp_enqueue_script(
                    'schedula-service-form-client',
                    SCHESAB_PLUGIN_URL . 'assets/js/serviceformclient.js',
                    ['schedula-vendor-js', 'wp-i18n'],
                    filemtime($service_js_path),
                    true
                );

                // --- Pre-load all necessary data for the service form ---
                require_once SCHESAB_PLUGIN_DIR . 'includes/api/class-schedula-api-settings.php';
                $settings_api = new \SCHESAB\Api\SCHESAB_Settings();
                $global_settings = $settings_api->get_general_settings(new \WP_REST_Request('GET', '/schesab/v1/general-settings'))->get_data() ?? [];
                $stripe_settings = $settings_api->get_stripe_settings(new \WP_REST_Request('GET', '/schesab/v1/stripe-settings'))->get_data() ?? [];

                require_once SCHESAB_PLUGIN_DIR . 'includes/api/class-schedula-api-appointments.php';
                $appointments_api = new \SCHESAB\Api\SCHESAB_Appointments();
                $booking_form_data = $appointments_api->get_booking_form_data(new \WP_REST_Request('GET', '/schesab/v1/booking-form-data'))->get_data() ?? [];

                wp_localize_script(
                    'schedula-service-form-client',
                    'schedulaServiceFormData',
                    [
                        'apiUrl' => esc_url_raw(rest_url('schesab/v1')),
                        'nonce' => wp_create_nonce('wp_rest'),
                        'global_settings' => $global_settings,
                        'stripe_settings' => $stripe_settings,
                        'booking_form_data' => $booking_form_data,
                    ]
                );
            }

            $service_css_path = SCHESAB_PLUGIN_DIR . 'assets/css/serviceformclient.css';
            $vendors_css_path = SCHESAB_PLUGIN_DIR . 'assets/css/vendors.css';

            if (file_exists($service_css_path)) {
                wp_enqueue_style(
                    'schedula-service-form-css',
                    SCHESAB_PLUGIN_URL . 'assets/css/serviceformclient.css',
                    [],
                    filemtime($service_css_path)
                );
            }

            if (file_exists($vendors_css_path)) {
                wp_enqueue_style(
                    'schedula-vendors-css',
                    SCHESAB_PLUGIN_URL . 'assets/css/vendors.css',
                    [],
                    filemtime($vendors_css_path)
                );
            }
        }
    }

    /**
     * Render the main reservation form shortcode.
     * Use: [schesab_reservation_form]
     *
     * @param array $atts Shortcode attributes.
     * @return string HTML output for the reservation form.
     */
    public function render_reservation_form($atts)
    {
        $atts = shortcode_atts([], $atts, 'schesab_reservation_form');
        ob_start();
        ?>
        <div id="schedula-frontend-container">
            <div id="schedula-skeleton" class="schedula-wrapper">
                <!-- Header -->
                <div class="schedula-header schedula-skeleton"></div>

                <!-- Progress -->
                <div class="schedula-progress schedula-skeleton"></div>

                <!-- Form Body -->
                <div class="schedula-form-grid">
                    <div class="schedula-left">
                        <div class="schedula-input schedula-skeleton"></div>
                        <div class="schedula-input schedula-skeleton"></div>
                        <div class="schedula-grid2">
                            <div class="schedula-box schedula-skeleton"></div>
                            <div class="schedula-box schedula-skeleton"></div>
                        </div>
                    </div>
                    <div class="schedula-right schedula-skeleton"></div>
                </div>

                <!-- Buttons -->
                <div class="schedula-buttons">
                    <div class="schedula-btn schedula-skeleton"></div>
                    <div class="schedula-btn schedula-skeleton"></div>
                </div>
            </div>
            <div id="schesab-frontend-app" v-cloak></div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render a service-specific form using its ID.
     * Use: [schesab_service_form id="YOUR_FORM_ID"]
     *
     * @param array $atts Shortcode attributes.
     * @return string HTML output for the service-specific form.
     */
    public function render_service_form($atts)
    {
        // Log every time this function is called


        $atts = shortcode_atts(
            [
                'id' => '',
            ],
            $atts,
            'schesab_service_form'
        );

        if (empty($atts['id'])) {

            return '<p>' . esc_html__('Error: Form ID is required for [schesab_service_form].', 'schedula-smart-appointment-booking') . '</p>';
        }

        // Prevent rendering the same form multiple times in one request if the ID is the same
        if (in_array($atts['id'], self::$rendered_form_ids)) {
            return ''; // Return empty string to prevent duplicate output
        }
        self::$rendered_form_ids[] = $atts['id'];


        global $wpdb;
        $table_name = $this->db->get_table_name('forms');
        $form = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table_name} WHERE id = %d",
                (int) $atts['id']
            ),
            ARRAY_A
        );

        if (!$form) {

            return '<p>' . esc_html__('Error: Custom service form not found with ID ', 'schedula-smart-appointment-booking') . esc_html($atts['id']) . '</p>';
        }

        // Ensure form_data is always a valid JSON string
        $form_data = !empty($form['form_data']) ? json_encode(json_decode($form['form_data'], true)) : '{}';



        $custom_css = "
            .calendar-date[disabled] {
                background-color: #f3f4f6 !important;
                color: #9ca3af !important;
                cursor: not-allowed !important;
                min-width: 40px !important;
            }

            .calendar-date.selected {
                background-color: var(--primary-color) !important;
                color: var(--header-text-color) !important;
            }

            .time-slot.selected,
            .time-slot-btn.selected {
                background-color: var(--primary-color) !important;
                color: var(--header-text-color) !important;
                border-color: var(--primary-color) !important;
            }

            .calendar-date:not([disabled]):not(.selected):hover {
                background-color: #eff6ff !important;
            }

            .time-slot:not(.selected):hover,
            .time-slot-btn:not(.selected):hover {
                border-color: #93c5fd !important;
                background-color: #eff6ff !important;
            }

            .calendar-grid {
                display: grid !important;
                grid-template-columns: repeat(7, minmax(0, 1fr)) !important;
            }

            .time-slots-container .schedula-grid {
                display: grid !important;
            }

            .schedula-header-section h1 {
                font-size: 1rem !important;
            }

            @media (min-width: 640px) {
                .schedula-header-section h1 {
                    font-size: 1.125rem !important;
                }
            }

            @media (min-width: 1024px) {
                .schedula-header-section h1 {
                    font-size: 1.25rem !important;
                }
            }

            .schedula-header-section p {
                font-size: 0.75rem !important;
            }

            @media (min-width: 640px) {
                .schedula-header-section p {
                    font-size: 0.875rem !important;
                }
            }

            .schedula-header-section {
                padding-left: 1rem !important;
                padding-right: 1rem !important;
                padding-top: 0.5rem !important;
                padding-bottom: 0.5rem !important;
            }

            @media (min-width: 640px) {
                .schedula-header-section {
                    padding-left: 1.5rem !important;
                    padding-right: 1.5rem !important;
                    padding-top: 0.75rem !important;
                    padding-bottom: 0.75rem !important;
                }
            }

            .schedula-absolute.schedula-bottom-4 h2 {
                font-size: 0.75rem !important;
            }

            @media (min-width: 640px) {
                .schedula-absolute.schedula-bottom-4 h2 {
                    font-size: 1rem !important;
                }
            }

            .schedula-skeleton {
                background: #e5e7eb;
                border-radius: 6px;
                animation: schedula-pulse 1.5s ease-in-out infinite;
            }

            @keyframes schedula-pulse {
                0%, 100% { opacity: 1; }
                50% { opacity: .5; }
            }

            .schedula-wrapper {
                width: 100%;
                padding: 1.5rem;
                display: flex;
                flex-direction: column;
                gap: 1.5rem;
            }

            .schedula-header {
                height: 4rem;
                width: 75%;
                margin: auto;
            }

            .schedula-body {
                display: flex;
                flex-direction: column;
                gap: 1rem;
            }

            .schedula-subtitle {
                height: 2rem;
                width: 50%;
                border-radius: 6px;
            }

            .schedula-textarea {
                height: 8rem;
                border-radius: 8px;
            }

            .schedula-box {
                height: 6rem;
                border-radius: 8px;
            }

            .schedula-buttons {
                display: flex;
                justify-content: flex-end;
                padding-top: 1rem;
            }

            .schedula-btn {
                height: 2.5rem;
                width: 6rem;
                border-radius: 8px;
            }
        ";
        wp_add_inline_style('schedula-service-form-css', $custom_css);

        ob_start();
        ?>
        <div id="schedula-frontend-container">
            <div id="schedula-skeleton" class="schedula-wrapper">
                <!-- Header -->
                <div class="schedula-header schedula-skeleton"></div>

                <!-- Body -->
                <div class="schedula-body">
                    <div class="schedula-subtitle schedula-skeleton"></div>
                    <div class="schedula-textarea schedula-skeleton"></div>
                    <div class="schedula-box schedula-skeleton"></div>
                </div>

                <!-- Buttons -->
                <div class="schedula-buttons">
                    <div class="schedula-btn schedula-skeleton"></div>
                </div>
            </div>
            <div id="schesab-service-form-<?php echo esc_attr($atts['id']); ?>"
                data-service-id="<?php echo esc_attr($form['service_id']); ?>"
                data-category-id="<?php echo esc_attr($form['category_id']); ?>"
                data-staff-id="<?php echo esc_attr($form['staff_id']); ?>" data-form-data="<?php echo esc_attr($form_data); ?>"
                class="schedula-service-form-container"></div>
        </div>
        <?php
        return ob_get_clean();
    }
}
