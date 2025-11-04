<?php

/**
 * Schedula Settings API.
 * Handles REST API endpoints for general plugin settings.
 *
 * @package SCHESAB\Api
 */

namespace SCHESAB\Api;

use SCHESAB\Utils\SCHESAB_Encryption;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

if (!defined('ABSPATH')) {
    exit;
}

class SCHESAB_Settings
{
    private $namespace = 'schesab/v1';

    public function __construct()
    {
        $this->namespace = 'schesab/v1';
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes()
    {
        // General Settings Routes
        register_rest_route($this->namespace, '/general-settings', [
            [
                'methods' => WP_REST_Server::READABLE, // GET
                'callback' => [$this, 'get_general_settings'],
                'permission_callback' => [$this, 'get_settings_permissions_check'],
            ],
            [
                'methods' => WP_REST_Server::EDITABLE, // POST, PUT, PATCH
                'callback' => [$this, 'update_general_settings'],
                'permission_callback' => [$this, 'update_settings_permissions_check'],
                'args' => $this->get_settings_args('general'), // Pass type 'general'
            ],
            'schema' => [$this, 'get_item_schema_for_general'], // Specific schema for general
        ]);

        // Company Settings Routes
        register_rest_route($this->namespace, '/company-settings', [
            [
                'methods' => WP_REST_Server::READABLE, // GET
                'callback' => [$this, 'get_company_settings'],
                'permission_callback' => [$this, 'get_settings_permissions_check'],
            ],
            [
                'methods' => WP_REST_Server::EDITABLE, // POST, PUT, PATCH
                'callback' => [$this, 'update_company_settings'],
                'permission_callback' => [$this, 'update_settings_permissions_check'],
                'args' => $this->get_settings_args('company'), // Pass type 'company'
            ],
            'schema' => [$this, 'get_item_schema_for_company'], // Specific schema for company
        ]);



        // Utility Data Route (for timezones, industries, company sizes, and currencies)
        register_rest_route($this->namespace, '/utility-data', [
            'methods' => WP_REST_Server::READABLE, // GET
            'callback' => [$this, 'get_utility_data'],
            'permission_callback' => '__return_true', // Publicly accessible as it's just data lists
        ]);

        // Stripe Settings Routes
        register_rest_route($this->namespace, '/stripe-settings', [
            [
                'methods' => WP_REST_Server::READABLE, // GET
                'callback' => [$this, 'get_stripe_settings'],
                'permission_callback' => [$this, 'get_settings_permissions_check'],
            ],
            [
                'methods' => WP_REST_Server::EDITABLE, // POST, PUT, PATCH
                'callback' => [$this, 'update_stripe_settings'],
                'permission_callback' => [$this, 'update_settings_permissions_check'],
                'args' => $this->get_settings_args('stripe'), // Pass type 'stripe'
            ],
            'schema' => [$this, 'get_item_schema_for_stripe'], // Specific schema for Stripe
        ]);

        register_rest_route($this->namespace, '/onboarding/complete', [
            [
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => [$this, 'complete_onboarding'],
                'permission_callback' => [$this, 'update_settings_permissions_check'],
            ],
        ]);

        // Update WordPress timezone endpoint
        register_rest_route($this->namespace, '/update-wordpress-timezone', [
            [
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => [$this, 'update_wordpress_timezone'],
                'permission_callback' => [$this, 'update_settings_permissions_check'],
                'args' => [
                    'timezone' => [
                        'required' => true,
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                        'validate_callback' => function ($param, $request, $key) {
                            return !empty($param) && in_array($param, timezone_identifiers_list());
                        }
                    ]
                ]
            ],
        ]);
    }

    /**
     * Get general settings.
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     */
    public function get_general_settings(WP_REST_Request $request)
    {
        $saved_settings = get_option('schesab_general_settings', []); // Get saved settings, default to empty array
        $defaults = $this->get_default_settings('general'); // Get hardcoded defaults

        $settings = []; // Initialize an empty array for the final settings to return

        // Iterate through all default settings to ensure all keys are present and correctly typed
        foreach ($defaults as $key => $default_value) {
            // If the setting exists in saved_settings, use it. Otherwise, use the default.
            // Crucially, check for isset() and not empty() to correctly handle 0.
            $settings[$key] = isset($saved_settings[$key]) ? $saved_settings[$key] : $default_value;
        }

        // Explicitly cast these values to integer to ensure frontend binding works
        // This is still needed because saved_settings might contain strings or other types
        $settings['timeSlotLength'] = (int) $settings['timeSlotLength'];
        $settings['minTimeBooking'] = (int) $settings['minTimeBooking'];
        $settings['minTimeCanceling'] = (int) $settings['minTimeCanceling'];
        $settings['bookingBufferTime'] = (int) $settings['bookingBufferTime'];
        $settings['maxPersonsPerBooking'] = (int) $settings['maxPersonsPerBooking']; // Cast new setting

        // Ensure groupBookingPriceLogic is an object and its amount is an integer
        if (!isset($settings['groupBookingPriceLogic']) || !is_array($settings['groupBookingPriceLogic'])) {
            $settings['groupBookingPriceLogic'] = $defaults['groupBookingPriceLogic'];
        } else {
            $settings['groupBookingPriceLogic']['amount'] = (float) ($settings['groupBookingPriceLogic']['amount'] ?? 0);
            $settings['groupBookingPriceLogic']['type'] = sanitize_text_field($settings['groupBookingPriceLogic']['type'] ?? 'per_person_multiply');
        }

        // Ensure recurrence settings are an object and its maxRecurrences is an integer
        // Note: 'type' is removed, so we only handle maxRecurrences
        if (!isset($settings['recurrence']) || !is_array($settings['recurrence'])) {
            $settings['recurrence'] = $defaults['recurrence'];
        } else {
            // Ensure maxRecurrences is treated as an integer, allowing 0
            $settings['recurrence']['maxRecurrences'] = (int) ($settings['recurrence']['maxRecurrences'] ?? 0);
            // Remove 'type' if it exists from older settings to avoid conflicts
            unset($settings['recurrence']['type']);
        }

        // Ensure defaultBusinessSchedule structure
        if (empty($settings['defaultBusinessSchedule']) && !empty($defaults['defaultBusinessSchedule'])) {
            $settings['defaultBusinessSchedule'] = $defaults['defaultBusinessSchedule'];
        }

        // Enhance settings with the actual currency symbol based on the stored code
        $selected_currency_code = $settings['currencyCode'] ?? $defaults['currencyCode'];
        $currency_symbol = $this->get_currency_symbol_from_code($selected_currency_code);
        $settings['currencySymbol'] = $currency_symbol; // Add currencySymbol for frontend consumption

        return new WP_REST_Response($settings, 200);
    }

    /**
     * Update general settings.
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     */
    public function update_general_settings(WP_REST_Request $request)
    {
        error_log('Settings API: update_general_settings: Currency Code received: ' . $request->get_param('currencyCode')); // NEW: Log currency code
        return $this->update_settings_by_type('general', $request);
    }

    /**
     * Get company settings.
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     */
    public function get_company_settings(WP_REST_Request $request)
    {
        $settings = get_option('schesab_company_settings', []);

        // Apply default values if not set
        $defaults = $this->get_default_settings('company');
        $settings = array_merge($defaults, $settings);

        return new WP_REST_Response($settings, 200);
    }

    /**
     * Update company settings.
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     */
    public function update_company_settings(WP_REST_Request $request)
    {
        return $this->update_settings_by_type('company', $request);
    }

    /**
     * Get Stripe settings.
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     */
    public function get_stripe_settings(WP_REST_Request $request)
    {
        $saved_settings = get_option('schesab_stripe_settings', []);
        $defaults = $this->get_default_settings('stripe');
        $settings = array_merge($defaults, $saved_settings);

        // Decrypt sensitive fields for display in the admin
        $settings['publishableKey'] = SCHESAB_Encryption::decrypt_data($settings['publishableKey']);
        $settings['secretKey'] = SCHESAB_Encryption::decrypt_data($settings['secretKey']);
        $settings['webhookSecret'] = SCHESAB_Encryption::decrypt_data($settings['webhookSecret']);

        // Ensure priceCorrectionAmount and timeIntervalPaymentGateway are integers
        if (isset($settings['priceCorrection']) && is_array($settings['priceCorrection'])) {
            $settings['priceCorrection']['amount'] = (int) ($settings['priceCorrection']['amount'] ?? 0);
        } else {
            $settings['priceCorrection'] = $defaults['priceCorrection']; // Fallback to default if corrupted
        }
        $settings['timeIntervalPaymentGateway'] = (int) ($settings['timeIntervalPaymentGateway'] ?? 0);


        //turn new WP_REST_Response($settings, 200);
        return new WP_REST_Response($settings, 200);
    }

    /**
     * Update Stripe settings.
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     */
    public function update_stripe_settings(WP_REST_Request $request)
    {
        $option_key = 'schesab_stripe_settings';
        $current_settings = get_option($option_key, []);
        $schema = $this->get_item_schema_for_stripe();

        $settings_to_save = [];

        // Start with current settings to preserve any not explicitly handled by schema or request
        foreach ($current_settings as $key => $value) {
            $settings_to_save[$key] = $value;
        }

        $enable_stripe = $request->get_param('enableStripe'); // Get enableStripe from request

        foreach ($schema['properties'] as $field => $properties) {
            // Check if the parameter is present in the request body
            if ($request->has_param($field)) {
                $value = $request->get_param($field);

                // Skip validation for API credentials if Stripe is disabled
                if (
                    !$enable_stripe &&
                    in_array($field, ['publishableKey', 'secretKey', 'webhookSecret'])
                ) {
                    // If Stripe is disabled, allow these fields to be empty/invalid, but still encrypt if not empty
                    $settings_to_save[$field] = empty($value) ? '' : SCHESAB_Encryption::encrypt_data($value);
                    continue; // Skip further validation for this field
                }

                $sanitized_value = $this->sanitize_setting_value($value, $properties);
                $validation_result = $this->validate_setting_value($sanitized_value, $properties, $field);
                if (is_wp_error($validation_result)) {
                    return $validation_result;
                }

                // Encrypt sensitive fields before saving
                if (in_array($field, ['publishableKey', 'secretKey', 'webhookSecret'])) {
                    $settings_to_save[$field] = SCHESAB_Encryption::encrypt_data($sanitized_value);
                } else {
                    $settings_to_save[$field] = $sanitized_value;
                }
            } else {
                // For boolean fields not explicitly sent in the request, set to false
                if (($properties['type'] ?? '') === 'boolean') {
                    $settings_to_save[$field] = false;
                }
            }
        }

        update_option($option_key, $settings_to_save);

        return new WP_REST_Response(['message' => __('Stripe settings updated successfully.', 'schedula-smart-appointment-booking')], 200);
    }

    /**
     * Marks the onboarding process as complete.
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function complete_onboarding(WP_REST_Request $request)
    {
        update_option('schesab_onboarding_complete', true);
        return new WP_REST_Response(['success' => true], 200);
    }

    /**
     * Update WordPress timezone.
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function update_wordpress_timezone(WP_REST_Request $request)
    {
        $timezone = $request->get_param('timezone');

        if (empty($timezone) || !in_array($timezone, timezone_identifiers_list())) {
            return new WP_Error('invalid_timezone', __('Invalid timezone provided.', 'schedula-smart-appointment-booking'), ['status' => 400]);
        }

        // Update WordPress timezone
        $updated = update_option('timezone_string', $timezone);

        if ($updated) {
            return new WP_REST_Response([
                'success' => true,
                // translators: %s is the timezone string
                'message' => sprintf(__('WordPress timezone updated to %s', 'schedula-smart-appointment-booking'), $timezone),
                'timezone' => $timezone
            ], 200);
        } else {
            return new WP_Error('update_failed', __('Failed to update WordPress timezone.', 'schedula-smart-appointment-booking'), ['status' => 500]);
        }
    }

    /**
     * Get utility data for select lists (timezones, industries, company sizes, and currencies).
     * This endpoint is publicly accessible.
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_REST_Response A response object with all utility data.
     */
    public function get_utility_data(WP_REST_Request $request)
    {
        $data = [
            'timezones' => timezone_identifiers_list(), // Comprehensive list of PHP timezones
            'company_sizes' => $this->get_company_size_options(),
            'industries' => $this->get_industry_options(),
            'currencies' => $this->get_currency_options(), // NEW: Add currencies to utility data
        ];
        return new WP_REST_Response($data, 200);
    }

    /**
     * Helper method to get company size options.
     *
     * @return array
     */
    protected function get_company_size_options()
    {
        return [
            ['value' => '1-9 employees', 'label' => __('1 - 9 employees', 'schedula-smart-appointment-booking')],
            ['value' => '10-19 employees', 'label' => __('10 - 19 employees', 'schedula-smart-appointment-booking')],
            ['value' => '20-49 employees', 'label' => __('20 - 49 employees', 'schedula-smart-appointment-booking')],
            ['value' => '50-249 employees', 'label' => __('50 - 249 employees', 'schedula-smart-appointment-booking')],
            ['value' => '250+ employees', 'label' => __('250 or more employees', 'schedula-smart-appointment-booking')],
        ];
    }

    /**
     * Helper method to get industry options.
     *
     * @return array
     */
    protected function get_industry_options()
    {
        return [
            ["value" => "software-development", "label" => __("Software Development", 'schedula-smart-appointment-booking'), "group" => __("Technology", 'schedula-smart-appointment-booking')],
            ["value" => "it-consulting", "label" => __("IT Consulting", 'schedula-smart-appointment-booking'), "group" => __("Technology", 'schedula-smart-appointment-booking')],
            ["value" => "cloud-services", "label" => __("Cloud Services", 'schedula-smart-appointment-booking'), "group" => __("Technology", 'schedula-smart-appointment-booking')],
            ["value" => "cybersecurity", "label" => __("Cybersecurity", 'schedula-smart-appointment-booking'), "group" => __("Technology", 'schedula-smart-appointment-booking')],
            ["value" => "real-estate-agencies", "label" => __("Real Estate Agencies", 'schedula-smart-appointment-booking'), "group" => __("Real Estate", 'schedula-smart-appointment-booking')],
            ["value" => "property-management", "label" => __("Property Management", 'schedula-smart-appointment-booking'), "group" => __("Real Estate", 'schedula-smart-appointment-booking')],
            ["value" => "construction-services", "label" => __("Construction Services", 'schedula-smart-appointment-booking'), "group" => __("Real Estate", 'schedula-smart-appointment-booking')],
            ["value" => "telecommunications", "label" => __("Telecommunications", 'schedula-smart-appointment-booking'), "group" => __("Media & Communications", 'schedula-smart-appointment-booking')],
            ["value" => "publishing", "label" => __("Publishing", 'schedula-smart-appointment-booking'), "group" => __("Media & Communications", 'schedula-smart-appointment-booking')],
            ["value" => "broadcasting", "label" => __("Broadcasting", 'schedula-smart-appointment-booking'), "group" => __("Media & Communications", 'schedula-smart-appointment-booking')],
            ["value" => "schools", "label" => __("Schools", 'schedula-smart-appointment-booking'), "group" => __("Education", 'schedula-smart-appointment-booking')],
            ["value" => "tutoring", "label" => __("Tutoring", 'schedula-smart-appointment-booking'), "group" => __("Education", 'schedula-smart-appointment-booking')],
            ["value" => "online-courses", "label" => __("Online Courses", 'schedula-smart-appointment-booking'), "group" => __("Education", 'schedula-smart-appointment-booking')],
            ["value" => "bookstores", "label" => __("Bookstores", 'schedula-smart-appointment-booking'), "group" => __("Education", 'schedula-smart-appointment-booking')],
            ["value" => "libraries", "label" => __("Libraries", 'schedula-smart-appointment-booking'), "group" => __("Education", 'schedula-smart-appointment-booking')],
            ["value" => "banking", "label" => __("Banking", 'schedula-smart-appointment-booking'), "group" => __("Financial Services", 'schedula-smart-appointment-booking')],
            ["value" => "insurance", "label" => __("Insurance", 'schedula-smart-appointment-booking'), "group" => __("Financial Services", 'schedula-smart-appointment-booking')],
            ["value" => "investment", "label" => __("Investment Services", 'schedula-smart-appointment-booking'), "group" => __("Financial Services", 'schedula-smart-appointment-booking')],
            ["value" => "tax-preparation", "label" => __("Tax Preparation", 'schedula-smart-appointment-booking'), "group" => __("Financial Services", 'schedula-smart-appointment-booking')],
            ["value" => "retailers", "label" => __("Retailers", 'schedula-smart-appointment-booking'), "group" => __("Retail", 'schedula-smart-appointment-booking')],
            ["value" => "supermarket", "label" => __("Supermarket", 'schedula-smart-appointment-booking'), "group" => __("Retail", 'schedula-smart-appointment-booking')],
            ["value" => "retail-finance", "label" => __("Retail Finance", 'schedula-smart-appointment-booking'), "group" => __("Retail", 'schedula-smart-appointment-booking')],
            ["value" => "e-commerce", "label" => __("E-commerce", 'schedula-smart-appointment-booking'), "group" => __("Retail", 'schedula-smart-appointment-booking')],
            ["value" => "specialty-stores", "label" => __("Specialty Stores", 'schedula-smart-appointment-booking'), "group" => __("Retail", 'schedula-smart-appointment-booking')],
            ["value" => "other-retailers", "label" => __("Other retailers", 'schedula-smart-appointment-booking'), "group" => __("Retail", 'schedula-smart-appointment-booking')],
            ["value" => "sport", "label" => __("Sport", 'schedula-smart-appointment-booking'), "group" => __("Sport & Fitness", 'schedula-smart-appointment-booking')],
            ["value" => "personal-trainers", "label" => __("Personal Trainers", 'schedula-smart-appointment-booking'), "group" => __("Sport & Fitness", 'schedula-smart-appointment-booking')],
            ["value" => "gyms", "label" => __("Gyms", 'schedula-smart-appointment-booking'), "group" => __("Sport & Fitness", 'schedula-smart-appointment-booking')],
            ["value" => "fitness-classes", "label" => __("Fitness Classes", 'schedula-smart-appointment-booking'), "group" => __("Sport & Fitness", 'schedula-smart-appointment-booking')],
            ["value" => "yoga-classes", "label" => __("Yoga Classes", 'schedula-smart-appointment-booking'), "group" => __("Sport & Fitness", 'schedula-smart-appointment-booking')],
            ["value" => "golf-classes", "label" => __("Golf Classes", 'schedula-smart-appointment-booking'), "group" => __("Sport & Fitness", 'schedula-smart-appointment-booking')],
            ["value" => "sport-items-renting", "label" => __("Sport Items Renting", 'schedula-smart-appointment-booking'), "group" => __("Sport & Fitness", 'schedula-smart-appointment-booking')],
            ["value" => "other-sport", "label" => __("Other Sport & Fitness", 'schedula-smart-appointment-booking'), "group" => __("Sport & Fitness", 'schedula-smart-appointment-booking')],
            ["value" => "restaurants", "label" => __("Restaurants", 'schedula-smart-appointment-booking'), "group" => __("Food & Beverage", 'schedula-smart-appointment-booking')],
            ["value" => "cafes", "label" => __("Cafes", 'schedula-smart-appointment-booking'), "group" => __("Food & Beverage", 'schedula-smart-appointment-booking')],
            ["value" => "bars", "label" => __("Bars", 'schedula-smart-appointment-booking'), "group" => __("Food & Beverage", 'schedula-smart-appointment-booking')],
            ["value" => "food-delivery", "label" => __("Food Delivery", 'schedula-smart-appointment-booking'), "group" => __("Food & Beverage", 'schedula-smart-appointment-booking')],
            ["value" => "catering", "label" => __("Catering", 'schedula-smart-appointment-booking'), "group" => __("Food & Beverage", 'schedula-smart-appointment-booking')],
            ["value" => "health-clinics", "label" => __("Health Clinics", 'schedula-smart-appointment-booking'), "group" => __("Health & Wellness", 'schedula-smart-appointment-booking')],
            ["value" => "pharmacies", "label" => __("Pharmacies", 'schedula-smart-appointment-booking'), "group" => __("Health & Wellness", 'schedula-smart-appointment-booking')],
            ["value" => "dentists", "label" => __("Dentists", 'schedula-smart-appointment-booking'), "group" => __("Health & Wellness", 'schedula-smart-appointment-booking')],
            ["value" => "therapists", "label" => __("Therapists", 'schedula-smart-appointment-booking'), "group" => __("Health & Wellness", 'schedula-smart-appointment-booking')],
            ["value" => "spas", "label" => __("Spas", 'schedula-smart-appointment-booking'), "group" => __("Health & Wellness", 'schedula-smart-appointment-booking')],
            ["value" => "marketing-agencies", "label" => __("Marketing Agencies", 'schedula-smart-appointment-booking'), "group" => __("Professional Services", 'schedula-smart-appointment-booking')],
            ["value" => "legal-services", "label" => __("Legal Services", 'schedula-smart-appointment-booking'), "group" => __("Professional Services", 'schedula-smart-appointment-booking')],
            ["value" => "accounting-services", "label" => __("Accounting Services", 'schedula-smart-appointment-booking'), "group" => __("Professional Services", 'schedula-smart-appointment-booking')],
            ["value" => "consulting", "label" => __("Consulting", 'schedula-smart-appointment-booking'), "group" => __("Professional Services", 'schedula-smart-appointment-booking')],
            ["value" => "other-professional-services", "label" => __("Other Professional Services", 'schedula-smart-appointment-booking'), "group" => __("Professional Services", 'schedula-smart-appointment-booking')],
            ["value" => "hotels", "label" => __("Hotels", 'schedula-smart-appointment-booking'), "group" => __("Travel & Hospitality", 'schedula-smart-appointment-booking')],
            ["value" => "travel-agencies", "label" => __("Travel Agencies", 'schedula-smart-appointment-booking'), "group" => __("Travel & Hospitality", 'schedula-smart-appointment-booking')],
            ["value" => "tourism", "label" => __("Tourism", 'schedula-smart-appointment-booking'), "group" => __("Travel & Hospitality", 'schedula-smart-appointment-booking')],
            ["value" => "car-rental", "label" => __("Car Rental", 'schedula-smart-appointment-booking'), "group" => __("Automotive", 'schedula-smart-appointment-booking')],
            ["value" => "mechanic-services", "label" => __("Mechanic Services", 'schedula-smart-appointment-booking'), "group" => __("Automotive", 'schedula-smart-appointment-booking')],
            ["value" => "car-dealerships", "label" => __("Car Dealerships", 'schedula-smart-appointment-booking'), "group" => __("Automotive", 'schedula-smart-appointment-booking')],
            ["value" => "hair-salons", "label" => __("Hair Salons", 'schedula-smart-appointment-booking'), "group" => __("Beauty & Personal Care", 'schedula-smart-appointment-booking')],
            ["value" => "barbershops", "label" => __("Barbershops", 'schedula-smart-appointment-booking'), "group" => __("Beauty & Personal Care", 'schedula-smart-appointment-booking')],
            ["value" => "nail-salons", "label" => __("Nail Salons", 'schedula-smart-appointment-booking'), "group" => __("Beauty & Personal Care", 'schedula-smart-appointment-booking')],
            ["value" => "other", "label" => __("Other", 'schedula-smart-appointment-booking'), "group" => __("Other", 'schedula-smart-appointment-booking')], // This 'Other' is from the Beauty & Personal Care context
            ["value" => "landscaping", "label" => __("Landscaping", 'schedula-smart-appointment-booking'), "group" => __("Home Services", 'schedula-smart-appointment-booking')],
            ["value" => "cleaning-services", "label" => __("Cleaning Services", 'schedula-smart-appointment-booking'), "group" => __("Home Services", 'schedula-smart-appointment-booking')],
            ["value" => "home-repair", "label" => __("Home Repair", 'schedula-smart-appointment-booking'), "group" => __("Home Services", 'schedula-smart-appointment-booking')],
            ["value" => "plumbing", "label" => __("Plumbing", 'schedula-smart-appointment-booking'), "group" => __("Home Services", 'schedula-smart-appointment-booking')],
            ["value" => "electrical", "label" => __("Electrical Services", 'schedula-smart-appointment-booking'), "group" => __("Home Services", 'schedula-smart-appointment-booking')],
            ["value" => "pest-control", "label" => __("Pest Control", 'schedula-smart-appointment-booking'), "group" => __("Home Services", 'schedula-smart-appointment-booking')],
            ["value" => "interior-design", "label" => __("Interior Design", 'schedula-smart-appointment-booking'), "group" => __("Home Services", 'schedula-smart-appointment-booking')],
            ["value" => "furniture-stores", "label" => __("Furniture Stores", 'schedula-smart-appointment-booking'), "group" => __("Home Services", 'schedula-smart-appointment-booking')],
            ["value" => "music-venues", "label" => __("Music Venues", 'schedula-smart-appointment-booking'), "group" => __("Arts & Entertainment", 'schedula-smart-appointment-booking')],
            ["value" => "art-galleries", "label" => __("Art Galleries", 'schedula-smart-appointment-booking'), "group" => __("Arts & Entertainment", 'schedula-smart-appointment-booking')],
            ["value" => "movie-theaters", "label" => __("Movie Theaters", 'schedula-smart-appointment-booking'), "group" => __("Arts & Entertainment", 'schedula-smart-appointment-booking')],
            ["value" => "gaming-centers", "label" => __("Gaming Centers", 'schedula-smart-appointment-booking'), "group" => __("Arts & Entertainment", 'schedula-smart-appointment-booking')],
            ["value" => "museums", "label" => __("Museums", 'schedula-smart-appointment-booking'), "group" => __("Arts & Entertainment", 'schedula-smart-appointment-booking')],

        ];
    }

    /**
     * Helper method to get the full list of currencies.
     * This data is hardcoded from your provided 'json ca.txt' file.
     *
     * @return array
     */
    protected function get_currency_options()
    {
        return [
            ["code" => "USD", "name" => "United States Dollar", "symbol" => "$"],
            ["code" => "EUR", "name" => "Euro", "symbol" => "€"],
            ["code" => "JPY", "name" => "Japanese Yen", "symbol" => "¥"],
            ["code" => "GBP", "name" => "British Pound Sterling", "symbol" => "£"],
            ["code" => "AUD", "name" => "Australian Dollar", "symbol" => "$"],
            ["code" => "CAD", "name" => "Canadian Dollar", "symbol" => "CA$"],
            ["code" => "CHF", "name" => "Swiss Franc", "symbol" => "CHF"],
            ["code" => "CNY", "name" => "Chinese Yuan", "symbol" => "¥"],
            ["code" => "SEK", "name" => "Swedish Krona", "symbol" => "kr"],
            ["code" => "NZD", "name" => "New Zealand Dollar", "symbol" => "$"],
            ["code" => "KRW", "name" => "South Korean Won", "symbol" => "₩"],
            ["code" => "SGD", "name" => "Singapore Dollar", "symbol" => "S$"],
            ["code" => "NOK", "name" => "Norwegian Krone", "symbol" => "kr"],
            ["code" => "MXN", "name" => "Mexican Peso", "symbol" => "$"],
            ["code" => "INR", "name" => "Indian Rupee", "symbol" => "₹"],
            ["code" => "RUB", "name" => "Russian Ruble", "symbol" => "₽"],
            ["code" => "ZAR", "name" => "South African Rand", "symbol" => "R"],
            ["code" => "BRL", "name" => "Brazilian Real", "symbol" => "R$"],
            ["code" => "HKD", "name" => "Hong Kong Dollar", "symbol" => "HK$"],
            ["code" => "IDR", "name" => "Indonesian Rupiah", "symbol" => "Rp"],
            ["code" => "MYR", "name" => "Malaysian Ringgit", "symbol" => "RM"],
            ["code" => "PHP", "name" => "Philippine Peso", "symbol" => "₱"],
            ["code" => "THB", "name" => "Thai Baht", "symbol" => "฿"],
            ["code" => "AED", "name" => "United Arab Emirates Dirham", "symbol" => "د.إ"],
            ["code" => "AFN", "name" => "Afghan Afghani", "symbol" => "؋"],
            ["code" => "ALL", "name" => "Albanian Lek", "symbol" => "Lek"],
            ["code" => "AMD", "name" => "Armenian Dram", "symbol" => "֏"],
            ["code" => "ANG", "name" => "Netherlands Antillean Guilder", "symbol" => "ƒ"],
            ["code" => "AOA", "name" => "Angolan Kwanza", "symbol" => "Kz"],
            ["code" => "ARS", "name" => "Argentine Peso", "symbol" => "$"],
            ["code" => "AWG", "name" => "Aruban Florin", "symbol" => "ƒ"],
            ["code" => "AZN", "name" => "Azerbaijani Manat", "symbol" => "₼"],
            ["code" => "BAM", "name" => "Bosnia-Herzegovina Convertible Mark", "symbol" => "KM"],
            ["code" => "BBD", "name" => "Barbadian Dollar", "symbol" => "Bds$"],
            ["code" => "BDT", "name" => "Bangladeshi Taka", "symbol" => "৳"],
            ["code" => "BGN", "name" => "Bulgarian Lev", "symbol" => "лв"],
            ["code" => "BHD", "name" => "Bahraini Dinar", "symbol" => ".د.ب"],
            ["code" => "BIF", "name" => "Burundian Franc", "symbol" => "FBu"],
            ["code" => "BMD", "name" => "Bermudan Dollar", "symbol" => "BD$"],
            ["code" => "BND", "name" => "Brunei Dollar", "symbol" => "B$"],
            ["code" => "BOB", "name" => "Bolivian Boliviano", "symbol" => "Bs."],
            ["code" => "BSD", "name" => "Bahamian Dollar", "symbol" => "B$"],
            ["code" => "BTN", "name" => "Bhutanese Ngultrum", "symbol" => "Nu."],
            ["code" => "BWP", "name" => "Botswanan Pula", "symbol" => "P"],
            ["code" => "BYN", "name" => "Belarusian Ruble", "symbol" => "Br"],
            ["code" => "BZD", "name" => "Belize Dollar", "symbol" => "BZ$"],
            ["code" => "CDF", "name" => "Congolese Franc", "symbol" => "FC"],
            ["code" => "CLP", "name" => "Chilean Peso", "symbol" => "$"],
            ["code" => "COP", "name" => "Colombian Peso", "symbol" => "$"],
            ["code" => "CRC", "name" => "Costa Rican Colón", "symbol" => "₡"],
            ["code" => "CUP", "name" => "Cuban Peso", "symbol" => "₱"],
            ["code" => "CVE", "name" => "Cape Verdean Escudo", "symbol" => "Esc"], // Corrected symbol
            ["code" => "CZK", "name" => "Czech Koruna", "symbol" => "Kč"],
            ["code" => "DJF", "name" => "Djiboutian Franc", "symbol" => "Fdj"],
            ["code" => "DKK", "name" => "Danish Krone", "symbol" => "kr."], // Corrected symbol
            ["code" => "DOP", "name" => "Dominican Peso", "symbol" => "RD$"],
            ["code" => "DZD", "name" => "Algerian Dinar", "symbol" => "د.ج"],
            ["code" => "EGP", "name" => "Egyptian Pound", "symbol" => "£"],
            ["code" => "ERN", "name" => "Eritrean Nakfa", "symbol" => "Nfk"],
            ["code" => "ETB", "name" => "Ethiopian Birr", "symbol" => "Br"],
            ["code" => "FJD", "name" => "Fijian Dollar", "symbol" => "FJ$"],
            ["code" => "FKP", "name" => "Falkland Islands Pound", "symbol" => "£"],
            ["code" => "GEL", "name" => "Georgian Lari", "symbol" => "₾"],
            ["code" => "GHS", "name" => "Ghanaian Cedi", "symbol" => "₵"],
            ["code" => "GIP", "name" => "Gibraltar Pound", "symbol" => "£"],
            ["code" => "GMD", "name" => "Gambian Dalasi", "symbol" => "D"],
            ["code" => "GNF", "name" => "Guinean Franc", "symbol" => "FG"],
            ["code" => "GTQ", "name" => "Guatemalan Quetzal", "symbol" => "Q"],
            ["code" => "GYD", "name" => "Guyanese Dollar", "symbol" => "GY$"],
            ["code" => "HNL", "name" => "Honduran Lempira", "symbol" => "L"],
            ["code" => "HRK", "name" => "Croatian Kuna", "symbol" => "kn"],
            ["code" => "HTG", "name" => "Haitian Gourde", "symbol" => "G"],
            ["code" => "HUF", "name" => "Hungarian Forint", "symbol" => "Ft"],
            ["code" => "ISK", "name" => "Icelandic Króna", "symbol" => "kr"],
            ["code" => "IRR", "name" => "Iranian Rial", "symbol" => "﷼"],
            ["code" => "IQD", "name" => "Iraqi Dinar", "symbol" => "ع.د"],
            ["code" => "JMD", "name" => "Jamaican Dollar", "symbol" => "J$"],
            ["code" => "JOD", "name" => "Jordanian Dinar", "symbol" => "د.ا"],
            ["code" => "KES", "name" => "Kenyan Shilling", "symbol" => "KSh"],
            ["code" => "KGS", "name" => "Kyrgyzstani Som", "symbol" => "с"],
            ["code" => "KHR", "name" => "Cambodian Riel", "symbol" => "៛"],
            ["code" => "KMF", "name" => "Comorian Franc", "symbol" => "CF"],
            ["code" => "KWD", "name" => "Kuwaiti Dinar", "symbol" => "د.ك"],
            ["code" => "KZT", "name" => "Kazakhstani Tenge", "symbol" => "₸"],
            ["code" => "LAK", "name" => "Lao Kip", "symbol" => "₭"],
            ["code" => "LBP", "name" => "Lebanese Pound", "symbol" => "ل.ل"],
            ["code" => "LKR", "name" => "Sri Lankan Rupee", "symbol" => "Rs"],
            ["code" => "LRD", "name" => "Liberian Dollar", "symbol" => "L$"],
            ["code" => "LSL", "name" => "Lesotho Loti", "symbol" => "L"],
            ["code" => "LYD", "name" => "Libyan Dinar", "symbol" => "ل.د"],
            ["code" => "MAD", "name" => "Moroccan Dirham", "symbol" => "د.م."],
            ["code" => "MDL", "name" => "Moldovan Leu", "symbol" => "L"],
            ["code" => "MGA", "name" => "Malagasy Ariary", "symbol" => "Ar"],
            ["code" => "MKD", "name" => "Macedonian Denar", "symbol" => "ден"],
            ["code" => "MMK", "name" => "Myanmar Kyat", "symbol" => "K"],
            ["code" => "MNT", "name" => "Mongolian Tögrög", "symbol" => "₮"],
            ["code" => "MOP", "name" => "Macanese Pataca", "symbol" => "P"],
            ["code" => "MRO", "name" => "Mauritanian Ouguiya (pre-2018)", "symbol" => "UM"],
            ["code" => "MRU", "name" => "Mauritanian Ouguiya", "symbol" => "UM"],
            ["code" => "MUR", "name" => "Mauritian Rupee", "symbol" => "₨"],
            ["code" => "MVR", "name" => "Maldivian Rufiyaa", "symbol" => "ރ"],
            ["code" => "MWK", "name" => "Malawian Kwacha", "symbol" => "MK"],
            ["code" => "MZN", "name" => "Mozambican Metical", "symbol" => "MT"],
            ["code" => "NAD", "name" => "Namibian Dollar", "symbol" => "N$"],
            ["code" => "NGN", "name" => "Nigerian Naira", "symbol" => "₦"],
            ["code" => "NIO", "name" => "Nicaraguan Córdoba", "symbol" => "C$"],
            ["code" => "NPR", "name" => "Nepalese Rupee", "symbol" => "₨"],
            ["code" => "OMR", "name" => "Omani Rial", "symbol" => "ر.ع."],
            ["code" => "PAB", "name" => "Panamanian Balboa", "symbol" => "B/."],
            ["code" => "PEN", "name" => "Peruvian Sol", "symbol" => "S/."],
            ["code" => "PGK", "name" => "Papua New Guinean Kina", "symbol" => "K"],
            ["code" => "PKR", "name" => "Pakistani Rupee", "symbol" => "₨"],
            ["code" => "PLN", "name" => "Polish Złoty", "symbol" => "zł"],
            ["code" => "PYG", "name" => "Paraguayan Guaraní", "symbol" => "₲"],
            ["code" => "QAR", "name" => "Qatari Riyal", "symbol" => "ر.ق"],
            ["code" => "RON", "name" => "Romanian Leu", "symbol" => "lei"],
            ["code" => "RSD", "name" => "Serbian Dinar", "symbol" => "дин"],
            ["code" => "RWF", "name" => "Rwandan Franc", "symbol" => "FRw"],
            ["code" => "SAR", "name" => "Saudi Riyal", "symbol" => "ر.س"],
            ["code" => "SBD", "name" => "Solomon Islands Dollar", "symbol" => "SI$"],
            ["code" => "SCR", "name" => "Seychellois Rupee", "symbol" => "₨"],
            ["code" => "SDG", "name" => "Sudanese Pound", "symbol" => "ج.س."],
            ["code" => "SLL", "name" => "Sierra Leonean Leone", "symbol" => "Le"],
            ["code" => "SOS", "name" => "Somali Shilling", "symbol" => "Sh"],
            ["code" => "SRD", "name" => "Surinamese Dollar", "symbol" => "$"],
            ["code" => "SSP", "name" => "South Sudanese Pound", "symbol" => "£"],
            ["code" => "SVC", "name" => "Salvadoran Colón", "symbol" => "₡"],
            ["code" => "SYP", "name" => "Syrian Pound", "symbol" => "£"],
            ["code" => "SZL", "name" => "Swazi Lilangeni", "symbol" => "E"],
            ["code" => "TJS", "name" => "Tajikistani Somoni", "symbol" => "ЅМ"],
            ["code" => "TMT", "name" => "Turkmenistani Manat", "symbol" => "m"],
            ["code" => "TND", "name" => "Tunisian Dinar", "symbol" => "د.ت"],
            ["code" => "TOP", "name" => "Tongan Paʻanga", "symbol" => "T$"],
            ["code" => "TRY", "name" => "Turkish Lira", "symbol" => "₺"],
            ["code" => "TTD", "name" => "Trinidad and Tobago Dollar", "symbol" => "TT$"],
            ["code" => "TWD", "name" => "New Taiwan Dollar", "symbol" => "NT$"],
            ["code" => "TZS", "name" => "Tanzanian Shilling", "symbol" => "TSh"],
            ["code" => "UAH", "name" => "Ukrainian Hryvnia", "symbol" => "₴"],
            ["code" => "UGX", "name" => "Ugandan Shilling", "symbol" => "USh"],
            ["code" => "UYU", "name" => "Uruguayan Peso", "symbol" => "$"],
            ["code" => "UZS", "name" => "Uzbekistani Som", "symbol" => "Soʻm"],
            ["code" => "VND", "name" => "Vietnamese Đồng", "symbol" => "₫"],
            ["code" => "VUV", "name" => "Vanuatu Vatu", "symbol" => "Vt"],
            ["code" => "WST", "name" => "Samoan Tālā", "symbol" => "WS$"],
            ["code" => "XAF", "name" => "Central African CFA Franc", "symbol" => "FCFA"],
            ["code" => "XCD", "name" => "East Caribbean Dollar", "symbol" => "$"],
            ["code" => "XOF", "name" => "West African CFA Franc", "symbol" => "CFA"],
            ["code" => "XPF", "name" => "CFP Franc", "symbol" => "CFPF"],
            ["code" => "YER", "name" => "Yemeni Rial", "symbol" => "﷼"],
            ["code" => "ZMW", "name" => "Zambian Kwacha", "symbol" => "ZK"]
        ];
    }

    /**
     * Helper to get currency symbol from code.
     *
     * @param string $currency_code The 3-letter currency code (e.g., USD, EUR).
     * @return string The currency symbol, or a default '$' if not found.
     */
    public function get_currency_symbol_from_code($currency_code)
    { // Changed to public
        $currencies = $this->get_currency_options();
        foreach ($currencies as $currency) {
            if ($currency['code'] === $currency_code) {
                return $currency['symbol'];
            }
        }
        return '$'; // Default fallback
    }

    /**
     * Generic update function for different setting types.
     *
     * @param string $type The type of settings ('general', 'company', etc.).
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     */
    private function update_settings_by_type($type, WP_REST_Request $request)
    {
        $option_key = 'schesab_' . $type . '_settings';
        $current_settings = get_option($option_key, []);
        $schema_method = 'get_item_schema_for_' . $type;
        if (!method_exists($this, $schema_method)) {
            return new WP_Error('invalid_settings_type', __('Invalid settings type specified.', 'schedula-smart-appointment-booking'), ['status' => 400]);
        }
        $schema = $this->$schema_method();

        $settings_to_save = [];

        // Start with current settings to preserve any not explicitly handled by schema or request
        // Also merge with defaults to ensure new fields get a default if not provided in request
        $defaults = $this->get_default_settings($type);
        $merged_settings = array_merge($defaults, $current_settings);


        foreach ($schema['properties'] as $field => $properties) {
            if ($request->has_param($field)) {
                $value = $request->get_param($field);

                // Special handling for 'groupBookingPriceLogic' object
                if ($field === 'groupBookingPriceLogic' && is_array($value)) {
                    $settings_to_save[$field] = $this->sanitize_group_booking_price_logic($value);
                }
                // Special handling for 'recurrence' object (now only has maxRecurrences)
                elseif ($field === 'recurrence' && is_array($value)) {
                    $settings_to_save[$field] = $this->sanitize_recurrence_object($value);
                } else {
                    $sanitized_value = $this->sanitize_setting_value($value, $properties);
                    $validation_result = $this->validate_setting_value($sanitized_value, $properties, $field);
                    if (is_wp_error($validation_result)) {
                        return $validation_result;
                    }
                    $settings_to_save[$field] = $sanitized_value;
                }
            } else {
                // If a parameter is not explicitly in the request, we use its value from $merged_settings
                // This handles cases where a checkbox is unchecked (not sent in request)
                // or if a field is simply not present but has a default/previous value.
                if (isset($merged_settings[$field])) {
                    $settings_to_save[$field] = $merged_settings[$field];
                } else if (($properties['type'] ?? '') === 'boolean') {
                    // Default booleans to false if not explicitly set and no prior value
                    $settings_to_save[$field] = false;
                } else {
                    // For other types, if not in request and no prior/default value, set to null or empty string
                    $settings_to_save[$field] = ($properties['type'] ?? 'string') === 'string' ? '' : null;
                }
            }
        }

        update_option($option_key, $settings_to_save);

        return new WP_REST_Response(['message' => __('Settings updated successfully.', 'schedula-smart-appointment-booking')], 200);
    }

    /**
     * Helper to sanitize setting values based on schema properties.
     *
     * @param mixed $value The raw value from the request.
     * @param array $properties Schema properties for the field.
     * @return mixed Sanitized value.
     */
    private function sanitize_setting_value($value, $properties)
    {
        $type = $properties['type'] ?? 'string';

        // If the value is empty and the field is not required, return an empty string/null immediately
        // This prevents format validation on empty optional fields.
        if (empty($value) && !($properties['required'] ?? false) && ($properties['type'] ?? 'string') !== 'boolean') {
            // For strings, return empty string. For other types, return null.
            return ($type === 'string' || $type === 'number') ? '' : null; // Also for number to allow empty input fields
        }

        switch ($type) {
            case 'integer':
                // Special handling for minTimeBooking and minTimeCanceling etc.
                if (
                    (
                        isset($properties['context']) &&
                        (
                            in_array('timeSlotLength', $properties['context']) ||
                            in_array('minTimeBooking', $properties['context']) ||
                            in_array('minTimeCanceling', $properties['context']) ||
                            in_array('bookingBufferTime', $properties['context']) ||
                            in_array('timeIntervalPaymentGateway', $properties['context']) ||
                            in_array('maxPersonsPerBooking', $properties['context']) ||
                            in_array('maxRecurrences', $properties['context']) // Added for new setting
                        )
                    ) &&
                    ($value === '' || $value === null)
                ) {
                    return 0; // Explicitly return 0 for these specific fields if empty
                }
                return absint($value);
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
            case 'string':
                if (($properties['format'] ?? '') === 'email') {
                    return sanitize_email($value);
                } elseif (($properties['format'] ?? '') === 'hex-color') {
                    return sanitize_hex_color($value);
                } elseif (($properties['format'] ?? '') === 'url' || ($properties['format'] ?? '') === 'uri') {
                    return esc_url_raw($value);
                }
                return sanitize_text_field($value);
            case 'array':
                if ($properties['items']['type'] === 'object' && ($properties['items']['properties']['day_of_week'] ?? false)) {
                    // This is defaultBusinessSchedule
                    return $this->sanitize_schedule_array($value);
                } elseif ($properties['items']['type'] === 'object' && ($properties['items']['properties']['start_date'] ?? false)) {
                    // This is defaultBusinessHolidays
                    return $this->sanitize_holidays_array($value);
                }
                // Fallback for generic arrays
                if (is_array($value)) {
                    return array_map('sanitize_text_field', $value);
                }
                return [];
            case 'object': // For priceCorrection when it's directly an object
                if ($properties['title'] === 'priceCorrection') { // Check title to confirm it's the priceCorrection object
                    return $this->sanitize_price_correction_object($value);
                } elseif ($properties['title'] === 'groupBookingPriceLogic') { // Handle groupBookingPriceLogic object
                    return $this->sanitize_group_booking_price_logic($value);
                } elseif ($properties['title'] === 'recurrence') { // Handle recurrence object
                    return $this->sanitize_recurrence_object($value);
                }
                return $value;
            default:
                return sanitize_text_field($value);
        }
    }

    /**
     * Helper to validate setting values based on schema properties.
     *
     * @param mixed $value The sanitized value.
     * @param array $properties Schema properties for the field.
     * @param string $field The field name.
     * @return bool|WP_Error True if valid, WP_Error object otherwise.
     */
    private function validate_setting_value($value, $properties, $field)
    {
        // If the value is empty (or null for non-string types) and the field is NOT required, it's valid.
        // Special handling for integer 0: if the value is 0 and it's an integer field, consider it not empty.
        if (
            ((empty($value) && is_string($value)) || is_null($value))
            && !($properties['required'] ?? false)
            && !($properties['type'] === 'integer' && $value === 0) // Allow 0 for optional integers
            && !($properties['type'] === 'boolean') // Booleans handled by default true/false
        ) {
            return true;
        }

        // If value is empty/null but required, return error
        if ((empty($value) && is_string($value)) && ($properties['required'] ?? false)) {
            // translators: %s is the field name
            return new WP_Error('rest_invalid_param', sprintf(__('Field %s cannot be empty.', 'schedula-smart-appointment-booking'), $field), ['status' => 400]);
        }
        if (is_null($value) && ($properties['required'] ?? false) && ($properties['type'] ?? 'string') !== 'string') {
            // translators: %s is the field name
            return new WP_Error('rest_invalid_param', sprintf(__('Missing required parameter: %s', 'schedula-smart-appointment-booking'), $field), ['status' => 400]);
        }


        $type = $properties['type'] ?? 'string';

        switch ($type) {
            case 'integer':
                // Allow empty string for integer fields if not required, and sanitize_setting_value makes it 0 or null
                if ($value === '' || $value === null) {
                    return true;
                }

                if (!is_numeric($value)) {
                    // translators: %s is the field name
                    return new WP_Error('rest_invalid_param', sprintf(__('Invalid integer value for %s.', 'schedula-smart-appointment-booking'), $field), ['status' => 400]);
                }
                $intValue = (int) $value;

                if (isset($properties['enum'])) {
                    if (!in_array($intValue, $properties['enum'], true)) { // Use strict comparison
                        // translators: %1$s is the field name, %2$s is a comma-separated list of allowed values
                        return new WP_Error('rest_invalid_param', sprintf(__('Invalid value for %1$s. Must be one of: %2$s', 'schedula-smart-appointment-booking'), $field, implode(', ', $properties['enum'])), ['status' => 400]);
                    }
                }
                // For maxRecurrences, allow 0
                if ($field === 'maxRecurrences' && $intValue < 0) { // Specific validation for maxRecurrences
                    // translators: %1$s is the field name, %2$d is the minimum allowed value
                    return new WP_Error('rest_invalid_param', sprintf(__('Value for %1$s must be at least %2$d.', 'schedula-smart-appointment-booking'), $field, 0), ['status' => 400]);
                }
                if (isset($properties['minimum']) && $field !== 'maxRecurrences' && $intValue < $properties['minimum']) { // Apply minimum for other integer fields
                    // translators: %1$s is the field name, %2$d is the minimum allowed value
                    return new WP_Error('rest_invalid_param', sprintf(__('Value for %1$s must be at least %2$d.', 'schedula-smart-appointment-booking'), $field, $properties['minimum']), ['status' => 400]);
                }
                break;
            case 'boolean':
                // `filter_var` already handles this, but adding an explicit check just in case.
                if (!is_bool($value)) {
                    // translators: %s is the field name
                    return new WP_Error('rest_invalid_param', sprintf(__('Invalid boolean value for %s.', 'schedula-smart-appointment-booking'), $field), ['status' => 400]);
                }
                break;
            case 'string':
                if (!is_string($value)) {
                    // translators: %s is the field name
                    return new WP_Error('rest_invalid_param', sprintf(__('Invalid string value for %s.', 'schedula-smart-appointment-booking'), $field), ['status' => 400]);
                }
                // String length validation
                if (isset($properties['minLength']) && strlen($value) < $properties['minLength']) {
                    // translators: %1$s is the field name, %2$d is the minimum allowed characters
                    return new WP_Error('rest_invalid_param', sprintf(__('%1$s must be at least %2$d characters.', 'schedula-smart-appointment-booking'), $field, $properties['minLength']), ['status' => 400]);
                }
                if (isset($properties['maxLength']) && strlen($value) > $properties['maxLength']) {
                    // translators: %1$s is the field name, %2$d is the maximum allowed characters
                    return new WP_Error('rest_invalid_param', sprintf(__('%1$s cannot exceed %2$d characters.', 'schedula-smart-appointment-booking'), $field, $properties['maxLength']), ['status' => 400]);
                }
                // Format validation (only if value is NOT empty)
                if (!empty($value)) { // Apply format validation only if the string is not empty
                    if (($properties['format'] ?? '') === 'hex-color') {
                        if (!preg_match('/^#([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/', $value)) {
                            // translators: %s is the field name
                            return new WP_Error('rest_invalid_param', sprintf(__('Invalid hex color format for %s.', 'schedula-smart-appointment-booking'), $field), ['status' => 400]);
                        }
                    }
                    if (($properties['format'] ?? '') === 'email' && !is_email($value)) {
                        // translators: %s is the field name
                        return new WP_Error('rest_invalid_param', sprintf(__('Invalid email format for %s.', 'schedula-smart-appointment-booking'), $field), ['status' => 400]);
                    }
                    if (($properties['format'] ?? '') === 'url' || ($properties['format'] ?? '') === 'uri') {
                        if (!filter_var($value, FILTER_VALIDATE_URL)) {
                            // translators: %s is the field name
                            return new WP_Error('rest_invalid_param', sprintf(__('Invalid URL format for %s.', 'schedula-smart-appointment-booking'), $field), ['status' => 400]);
                        }
                    }
                }
                // Custom validation for 'enum' on string fields
                if (isset($properties['enum'])) {
                    $valid_options = array_column($this->get_enum_options_for_field($field), 'value');
                    if (!in_array($value, $valid_options, true)) { // Use strict comparison
                        // translators: %s is the field name
                        return new WP_Error('rest_invalid_param', sprintf(__('Invalid value for %s. Must be one of the predefined options.', 'schedula-smart-appointment-booking'), $field), ['status' => 400]);
                    }
                }
                break;
            case 'array':
                if (!is_array($value)) {
                    // translators: %s is the field name
                    return new WP_Error('rest_invalid_param', sprintf(__('%s must be an array.', 'schedula-smart-appointment-booking'), $field), ['status' => 400]);
                }
                if ($field === 'defaultBusinessSchedule') {
                    $validation_result = $this->validate_schedule_array($value);
                    if (is_wp_error($validation_result))
                        return $validation_result;
                } elseif ($field === 'defaultBusinessHolidays') {
                    $validation_result = $this->validate_holidays_array($value);
                    if (is_wp_error($validation_result))
                        return $validation_result;
                }
                break;
            case 'object':
                if ($field === 'priceCorrection') {
                    $validation_result = $this->validate_price_correction_object($value);
                    if (is_wp_error($validation_result))
                        return $validation_result;
                } elseif ($field === 'groupBookingPriceLogic') { // Validate groupBookingPriceLogic object
                    $validation_result = $this->validate_group_booking_price_logic($value);
                    if (is_wp_error($validation_result))
                        return $validation_result;
                } elseif ($field === 'recurrence') { // Validate recurrence object
                    $validation_result = $this->validate_recurrence_object($value);
                    if (is_wp_error($validation_result))
                        return $validation_result;
                }
                break;
        }
        return true;
    }

    /**
     * Helper to get enum options for a specific string field.
     * Used for validation of dynamic select options like industry or companySize.
     *
     * @param string $field The field name (e.g., 'industry', 'companySize').
     * @return array The array of options (value, label, group) or just values for specific enums.
     */
    private function get_enum_options_for_field($field)
    {
        switch ($field) {
            case 'industry':
                return $this->get_industry_options();
            case 'companySize':
                return $this->get_company_size_options();
            case 'businessTimezone':
                $timezones = timezone_identifiers_list();
                array_unshift($timezones, 'UTC'); // Add 'UTC' if not already present
                return array_map(function ($tz) {
                    return ['value' => $tz];
                }, array_unique($timezones));
            case 'deleteDataOnUninstall':
                return [['value' => 'dont_delete'], ['value' => 'delete']];
            case 'currencyCode':
                // Remap to have a 'value' key for the generic validation function
                $currencies = $this->get_currency_options();
                return array_map(function ($currency) {
                    return ['value' => $currency['code']];
                }, $currencies);
            case 'adminFontFamily':
                // Return font names directly as values for enum validation
                return array_map(function ($font) {
                    return ['value' => $font];
                }, $this->get_supported_google_fonts());
            case 'priceCorrection.type': // Specific for nested property validation
            case 'groupBookingPriceLogic.type': // Specific for nested group booking price logic type
                return [['value' => 'none'], ['value' => 'increase_percent'], ['value' => 'discount_percent'], ['value' => 'addition'], ['value' => 'deduction'], ['value' => 'per_person_multiply'], ['value' => 'fixed_discount_per_person'], ['value' => 'percentage_discount_total']]; // Corrected enum values and added new ones
            case 'timeIntervalPaymentGateway': // For enum on integer
                return [['value' => 0], ['value' => 5], ['value' => 10], ['value' => 15], ['value' => 20], ['value' => 30], ['value' => 45], ['value' => 60], ['value' => 120], ['value' => 240], ['value' => 360], ['value' => 720], ['value' => 1440]];
            case 'recurrence.type': // Removed from frontend, but kept here for backward compatibility with schema if needed.
                return []; // No specific enum values anymore for flexibility
            case 'timeFormat':
                return [['value' => '12h'], ['value' => '24h']];
            default:
                return [];
        }
    }


    /**
     * Check permissions for getting settings.
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return bool|WP_Error True if the request has read access, WP_Error object otherwise.
     */
    public function get_settings_permissions_check(WP_REST_Request $request)
    {
        if (!current_user_can('manage_options')) {
            return new WP_Error('rest_forbidden', __('You do not have permission to view settings.', 'schedula-smart-appointment-booking'), ['status' => 403]);
        }
        return true;
    }

    /**
     * Check permissions for updating settings.
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return bool|WP_Error True if the request has edit access, WP_Error object otherwise.
     */
    public function update_settings_permissions_check(WP_REST_Request $request)
    {
        if (!current_user_can('manage_options')) {
            return new WP_Error('rest_forbidden', __('You do not have permission to update settings.', 'schedula-smart-appointment-booking'), ['status' => 403]);
        }
        return true;
    }

    /**
     * Get default settings for a given type.
     *
     * @param string $type The type of settings ('general', 'company').
     * @return array Default settings values.
     */
    public function get_default_settings($type) // Changed from protected to public
    {
        switch ($type) {
            case 'general':
                return [
                    'timeFormat' => '24h',
                    'timeSlotLength' => 30,
                    'minTimeBooking' => 0,
                    'minTimeCanceling' => 0,
                    'bookingBufferTime' => 0,
                    'daysAvailableBooking' => 365,
                    'displayTimezone' => true,
                    'deleteDataOnUninstall' => 'dont_delete',
                    'businessTimezone' => 'Africa/Lagos',
                    'followAdminTimezone' => false, // NEW: Follow admin's current timezone
                    'currencyCode' => 'USD',
                    'enableLocalPayment' => true,
                    'instantBookingEnabled' => false,
                    'enableGroupBooking' => false, // NEW: Enable Group Booking
                    'maxPersonsPerBooking' => 1,   // NEW: Max persons for group booking
                    'groupBookingPriceLogic' => [ // NEW: Default for group booking price logic
                        'type' => 'per_person_multiply',
                        'amount' => 0,
                    ],
                    'enableRecurringAppointments' => false, // Enable Recurring Appointments
                    'recurrence' => [ // Default recurrence settings (removed type, maxRecurrences default to 0 for no limit)
                        'maxRecurrences' => 0,
                        'paymentBehavior' => 'charge_all',
                    ],
                    'enableDefaultBusinessSchedule' => true,
                    'defaultBusinessSchedule' => [
                        ['day_of_week' => 0, 'start_time' => '', 'end_time' => '', 'breaks' => []], // Sunday
                        ['day_of_week' => 1, 'start_time' => '09:00', 'end_time' => '17:00', 'breaks' => []], // Monday
                        ['day_of_week' => 2, 'start_time' => '09:00', 'end_time' => '17:00', 'breaks' => []], // Tuesday
                        ['day_of_week' => 3, 'start_time' => '09:00', 'end_time' => '17:00', 'breaks' => []], // Wednesday
                        ['day_of_week' => 4, 'start_time' => '09:00', 'end_time' => '17:00', 'breaks' => []], // Thursday
                        ['day_of_week' => 5, 'start_time' => '09:00', 'end_time' => '17:00', 'breaks' => []], // Friday
                        ['day_of_week' => 6, 'start_time' => '', 'end_time' => '', 'breaks' => []], // Saturday
                    ],
                    'enableDefaultBusinessHolidays' => true,
                    'defaultBusinessHolidays' => [],
                    // ADMIN APPEARANCE SETTINGS - SIMPLIFIED
                    'adminDarkModeEnabled' => false, // Default to light mode
                    'adminFontFamily' => 'Inter', // Default font family
                    'headerBackgroundColor' => '#081a30',
                    'headerTextColor' => '#ffffff',
                    'sBackgroundColor' => '#ffffff',
                    'sTextColor' => '#081a30',
                ];
            case 'company':
                return [
                    'companyName' => '',
                    'address' => '',
                    'phone' => '',
                    'website' => '',
                    'industry' => '',
                    'servicesOffered' => '', // This was the "services" property, now named more clearly
                    'companySize' => '',
                    'email' => '',
                    'companyLogoUrl' => '',
                ];
            case 'stripe':
                return [
                    'enableStripe' => false,
                    'publishableKey' => '',
                    'secretKey' => '',
                    'webhookSecret' => '',
                    'sandboxMode' => true, // Default to sandbox for safety
                    'priceCorrection' => [
                        'type' => 'none',
                        'amount' => 0,
                    ],
                    'timeIntervalPaymentGateway' => 0, // 0 means OFF
                ];
            default:
                return [];
        }
    }

    /**
     * Get the JSON schema for 'general' settings.
     *
     * @return array
     */
    public function get_item_schema_for_general()
    {
        // Define and return schema for general settings
        return [
            '$schema' => 'http://json-schema.org/draft-04/schema#',
            'title' => 'general_settings',
            'type' => 'object',
            'properties' => [
                'timeFormat' => [
                    'type' => 'string',
                    'description' => __('The format for displaying time.', 'schedula-smart-appointment-booking'),
                    'enum' => ['12h', '24h'],
                    'default' => '24h',
                    'context' => ['view', 'edit'],
                ],
                'timeSlotLength' => [
                    'type' => 'integer',
                    'description' => __('Length of each time slot in minutes.', 'schedula-smart-appointment-booking'),
                    'enum' => [0, 15, 30, 45, 60], // ADDED 0
                    'default' => 30,
                    'context' => ['view', 'edit', 'timeSlotLength'], // Added 'timeSlotLength' context
                ],
                'minTimeBooking' => [
                    'type' => 'integer',
                    'description' => __('Minimum time required prior to booking in minutes.', 'schedula-smart-appointment-booking'),
                    'enum' => [0, 30, 60, 120, 240, 720, 1440, 2880], // ADDED 0
                    'default' => 60,
                    'context' => ['view', 'edit', 'minTimeBooking'], // Added 'minTimeBooking' context
                ],
                'minTimeCanceling' => [
                    'type' => 'integer',
                    'description' => __('Minimum time required prior to canceling in minutes.', 'schedula-smart-appointment-booking'),
                    'enum' => [0, 30, 60, 120, 240, 720, 1440, 2880], // ADDED 0
                    'default' => 0,
                    'context' => ['view', 'edit', 'minTimeCanceling'], // Added 'minTimeCanceling' context
                ],
                'daysAvailableBooking' => [
                    'type' => 'integer',
                    'description' => __('Number of days available for booking in the future.', 'schedula-smart-appointment-booking'),
                    'minimum' => 1,
                    'default' => 365,
                    'context' => ['view', 'edit'],
                ],
                'bookingBufferTime' => [
                    'type' => 'integer',
                    'description' => __('Buffer time between appointments in minutes.', 'schedula-smart-appointment-booking'),
                    'enum' => [0, 5, 10, 15, 20, 30, 45, 60],
                    'default' => 0,
                    'context' => ['view', 'edit', 'bookingBufferTime'], // Added 'bookingBufferTime' context
                ],
                'displayTimezone' => [
                    'type' => 'boolean',
                    'description' => __('Whether to display available time slots in the client\'s time zone.', 'schedula-smart-appointment-booking'),
                    'default' => true,
                    'context' => ['view', 'edit'],
                ],
                'businessTimezone' => [
                    'type' => 'string',
                    'description' => __('The business timezone for scheduling.', 'schedula-smart-appointment-booking'),
                    'enum' => array_values(array_unique(array_merge(['UTC'], timezone_identifiers_list()))),
                    'default' => 'Africa/Lagos',
                    'context' => ['view', 'edit'],
                ],
                'followAdminTimezone' => [
                    'type' => 'boolean',
                    'description' => __('If enabled, appointments will follow the admin\'s current timezone. If disabled, appointments will use the fixed business timezone.', 'schedula-smart-appointment-booking'),
                    'default' => false,
                    'context' => ['view', 'edit'],
                ],
                'deleteDataOnUninstall' => [
                    'type' => 'string',
                    'description' => __('Whether to delete all Schedula data on plugin uninstall.', 'schedula-smart-appointment-booking'),
                    'enum' => ['dont_delete', 'delete'],
                    'default' => 'dont_delete',
                    'context' => ['view', 'edit'],
                ],
                'currencyCode' => [
                    'type' => 'string',
                    'description' => __('Code for the currency used in prices (e.g., USD, EUR, GBP).', 'schedula-smart-appointment-booking'),
                    'default' => 'USD',
                    'context' => ['view', 'edit'],
                    'maxLength' => 3,
                    'enum' => array_column($this->get_currency_options(), 'code'),
                ],
                'enableLocalPayment' => [
                    'type' => 'boolean',
                    'description' => __('Enable or disable the "Pay in cash" payment option for services.', 'schedula-smart-appointment-booking'),
                    'default' => true,
                    'context' => ['view', 'edit'],
                ],
                'instantBookingEnabled' => [
                    'type' => 'boolean',
                    'description' => __('If enabled, new bookings will be instantly confirmed. Otherwise, they will be marked as pending.', 'schedula-smart-appointment-booking'),
                    'default' => false,
                    'context' => ['view', 'edit'],
                ],
                'enableGroupBooking' => [ // Schema for Enable Group Booking
                    'type' => 'boolean',
                    'description' => __('Allow customers to book for more than one person at a time.', 'schedula-smart-appointment-booking'),
                    'default' => false,
                    'context' => ['view', 'edit'],
                ],
                'maxPersonsPerBooking' => [ // Schema for Max Persons Per Booking
                    'type' => 'integer',
                    'description' => __('Set the maximum number of people that can be booked in a single appointment.', 'schedula-smart-appointment-booking'),
                    'minimum' => 1,
                    'default' => 1,
                    'context' => ['view', 'edit', 'maxPersonsPerBooking'], // Added 'maxPersonsPerBooking' context
                ],
                'groupBookingPriceLogic' => [ // Schema for Group Booking Price Logic
                    'type' => 'object',
                    'title' => 'groupBookingPriceLogic',
                    'description' => __('Logic for calculating price in group bookings.', 'schedula-smart-appointment-booking'),
                    'properties' => [
                        'type' => [
                            'type' => 'string',
                            'description' => __('Type of price calculation for group bookings.', 'schedula-smart-appointment-booking'),
                            'enum' => ['per_person_multiply', 'fixed_discount_per_person', 'percentage_discount_total'],
                            'default' => 'per_person_multiply',
                            'context' => ['view', 'edit', 'groupBookingPriceLogic.type'],
                        ],
                        'amount' => [
                            'type' => 'number', // Changed to number to allow for decimals (e.g., percentages)
                            'description' => __('Adjustment amount for group booking price (fixed value or percentage).', 'schedula-smart-appointment-booking'),
                            'minimum' => 0,
                            'default' => 0,
                            'context' => ['view', 'edit'],
                        ],
                    ],
                    'required' => ['type', 'amount'],
                    'default' => ['type' => 'per_person_multiply', 'amount' => 0],
                    'context' => ['view', 'edit'],
                ],
                'enableRecurringAppointments' => [ // Schema for Enable Recurring Appointments
                    'type' => 'boolean',
                    'description' => __('Allow clients to book appointments that repeat regularly.', 'schedula-smart-appointment-booking'),
                    'default' => false,
                    'context' => ['view', 'edit'],
                ],
                'recurrence' => [ // Schema for Recurrence settings (simplified)
                    'type' => 'object',
                    'title' => 'recurrence',
                    'description' => __('Settings for recurring appointments.', 'schedula-smart-appointment-booking'),
                    'properties' => [
                        'maxRecurrences' => [
                            'type' => 'integer',
                            'description' => __('Maximum number of times a recurring appointment can repeat. Set to 0 for no limit.', 'schedula-smart-appointment-booking'),
                            'minimum' => 0, // Changed to 0
                            'default' => 0, // Changed to 0
                            'context' => ['view', 'edit', 'maxRecurrences'],
                        ],
                        'paymentBehavior' => [
                            'type' => 'string',
                            'description' => __('How to handle payments for recurring appointments.', 'schedula-smart-appointment-booking'),
                            'enum' => ['charge_all', 'charge_first', 'manual'],
                            'default' => 'charge_all',
                            'context' => ['view', 'edit'],
                        ],
                    ],
                    'required' => ['maxRecurrences', 'paymentBehavior'], // 'type' is no longer required
                    'default' => ['maxRecurrences' => 0], // Simplified default
                    'context' => ['view', 'edit'],
                ],
                // Default business schedule
                'enableDefaultBusinessSchedule' => [
                    'type' => 'boolean',
                    'description' => __('Enable this schedule as a template for new staff members.', 'schedula-smart-appointment-booking'),
                    'default' => false,
                    'context' => ['view', 'edit'],
                ],
                'defaultBusinessSchedule' => [
                    'type' => 'array',
                    'description' => __('Default working hours and breaks for new staff members.', 'schedula-smart-appointment-booking'),
                    'default' => [], // Default to empty, actual default populated in get_default_settings
                    'context' => ['view', 'edit'],
                    'items' => [ // Define structure for each schedule item
                        'type' => 'object',
                        'properties' => [
                            'day_of_week' => ['type' => 'integer', 'minimum' => 0, 'maximum' => 6],
                            'start_time' => ['type' => 'string', 'pattern' => '^(?:[01]\d|2[0-3]):[0-5]\d$'], // HH:MM format
                            'end_time' => ['type' => 'string', 'pattern' => '^(?:[01]\d|2[0-3]):[0-5]\d$'], // HH:MM format
                            'breaks' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'start_time' => ['type' => 'string', 'pattern' => '^(?:[01]\d|2[0-3]):[0-5]\d$'],
                                        'end_time' => ['type' => 'string', 'pattern' => '^(?:[01]\d|2[0-3]):[0-5]\d$'],
                                        'description' => ['type' => 'string', 'maxLength' => 255],
                                    ],
                                    'required' => ['start_time', 'end_time'],
                                ],
                            ],
                        ],
                        'required' => ['day_of_week'], // start_time/end_time can be empty for 'off' days
                    ],
                ],
                // Default business holidays
                'enableDefaultBusinessHolidays' => [
                    'type' => 'boolean',
                    'description' => __('Enable these holidays as a template for new staff members.', 'schedula-smart-appointment-booking'),
                    'default' => false,
                    'context' => ['view', 'edit'],
                ],
                'defaultBusinessHolidays' => [
                    'type' => 'array',
                    'description' => __('Default holiday periods for new staff members.', 'schedula-smart-appointment-booking'),
                    'default' => [],
                    'context' => ['view', 'edit'],
                    'items' => [ // Define structure for each holiday item
                        'type' => 'object',
                        'properties' => [
                            'start_date' => ['type' => 'string', 'format' => 'date'], // YYYY-MM-DD
                            'end_date' => ['type' => 'string', 'format' => 'date'],   // YYYY-MM-DD
                            'description' => ['type' => 'string', 'maxLength' => 255],
                        ],
                        'required' => ['start_date', 'end_date'],
                    ],
                ],
                // ADMIN APPEARANCE SETTINGS - SIMPLIFIED
                'adminDarkModeEnabled' => [
                    'type' => 'boolean',
                    'description' => __('Enable dark mode for the admin interface.', 'schedula-smart-appointment-booking'),
                    'default' => false,
                    'context' => ['view', 'edit'],
                ],
                'adminFontFamily' => [
                    'type' => 'string',
                    'description' => __('Font family for the admin interface.', 'schedula-smart-appointment-booking'),
                    'default' => 'Inter',
                    'context' => ['view', 'edit'],
                    'enum' => $this->get_supported_google_fonts(),
                ],
                'headerBackgroundColor' => [
                    'type' => 'string',
                    'description' => __('Header background color.', 'schedula-smart-appointment-booking'),
                    'default' => '#081a30',
                    'context' => ['view', 'edit'],
                    'format' => 'hex-color',
                ],
                'headerTextColor' => [
                    'type' => 'string',
                    'description' => __('Header text color.', 'schedula-smart-appointment-booking'),
                    'default' => '#ffffff',
                    'context' => ['view', 'edit'],
                    'format' => 'hex-color',
                ],
                'sBackgroundColor' => [
                    'type' => 'string',
                    'description' => __('Logo S background color.', 'schedula-smart-appointment-booking'),
                    'default' => '#ffffff',
                    'context' => ['view', 'edit'],
                    'format' => 'hex-color',
                ],
                'sTextColor' => [
                    'type' => 'string',
                    'description' => __('Logo S text color.', 'schedula-smart-appointment-booking'),
                    'default' => '#081a30',
                    'context' => ['view', 'edit'],
                    'format' => 'hex-color',
                ],
            ],
        ];
    }

    /**
     * Get the JSON schema for 'stripe' settings.
     *
     * @return array
     */
    public function get_item_schema_for_stripe()
    {
        return [
            '$schema' => 'http://json-schema.org/draft-04/schema#',
            'title' => 'stripe_settings',
            'type' => 'object',
            'properties' => [
                'enableStripe' => [
                    'type' => 'boolean',
                    'description' => __('Enable or disable Stripe as a payment method.', 'schedula-smart-appointment-booking'),
                    'default' => false,
                    'context' => ['view', 'edit'],
                ],
                'publishableKey' => [
                    'type' => 'string',
                    'description' => __('Stripe Publishable Key.', 'schedula-smart-appointment-booking'),
                    'default' => '',
                    'context' => ['view', 'edit'],
                    'maxLength' => 255,
                ],
                'secretKey' => [
                    'type' => 'string',
                    'description' => __('Stripe Secret Key (KEEP THIS SECURE).', 'schedula-smart-appointment-booking'),
                    'default' => '',
                    'context' => ['view', 'edit'],
                    'maxLength' => 255,
                ],
                'webhookSecret' => [
                    'type' => 'string',
                    'description' => __('Stripe Webhook Signing Secret.', 'schedula-smart-appointment-booking'),
                    'default' => '',
                    'context' => ['view', 'edit'],
                    'maxLength' => 255,
                ],
                'sandboxMode' => [
                    'type' => 'boolean',
                    'description' => __('Use Stripe test mode for testing payments.', 'schedula-smart-appointment-booking'),
                    'default' => true,
                    'context' => ['view', 'edit'],
                ],
                'priceCorrection' => [
                    'type' => 'object',
                    'title' => 'priceCorrection',
                    'description' => __('Adjust the price for Stripe payments.', 'schedula-smart-appointment-booking'),
                    'properties' => [
                        'type' => [
                            'type' => 'string',
                            'description' => __('Type of price correction.', 'schedula-smart-appointment-booking'),
                            'enum' => ['none', 'increase_percent', 'discount_percent', 'addition', 'deduction'],
                            'default' => 'none',
                            'context' => ['view', 'edit', 'priceCorrection.type'],
                        ],
                        'amount' => [
                            'type' => 'integer',
                            'description' => __('Amount for price correction (percentage or fixed value).', 'schedula-smart-appointment-booking'),
                            'minimum' => 0,
                            'default' => 0,
                            'context' => ['view', 'edit'],
                        ],
                    ],
                    'required' => ['type', 'amount'],
                    'default' => ['type' => 'none', 'amount' => 0],
                    'context' => ['view', 'edit'],
                ],
                'timeIntervalPaymentGateway' => [
                    'type' => 'integer',
                    'description' => __('Time in minutes to hold a booking while payment is pending (0 for OFF).', 'schedula-smart-appointment-booking'),
                    'minimum' => 0,
                    'default' => 0,
                    'context' => ['view', 'edit', 'timeIntervalPaymentGateway'],
                    'enum' => [0, 5, 10, 15, 20, 30, 45, 60, 120, 240, 360, 720, 1440], // Example options
                ],
            ],
        ];
    }

    /**
     * Returns a list of supported Google Fonts for the admin interface.
     * This list can be expanded as needed.
     *
     * @return array
     */
    private function get_supported_google_fonts()
    {
        return [
            'Inter',
            'Roboto',
            'Open Sans',
            'Lato',
            'Montserrat',
            'Oswald',
            'Source Sans Pro',
            'Poppins',
            'Noto Sans',
            'Raleway',
            'Ubuntu',
            'Merriweather',
            'Playfair Display',
            'Lora',
            'Exo 2',
            'PT Sans',
            'Fira Sans',
            'Work Sans',
            'Nunito',
            'Rubik',
        ];
    }

    /**
     * Get the JSON schema for 'company' settings.
     *
     * @return array
     */
    public function get_item_schema_for_company()
    {
        // Define and return schema for company settings
        return [
            '$schema' => 'http://json-schema.org/draft-04/schema#',
            'title' => 'company_settings',
            'type' => 'object',
            'properties' => [
                'companyName' => [
                    'type' => 'string',
                    'description' => __('Official company name.', 'schedula-smart-appointment-booking'),
                    'default' => '',
                    'context' => ['view', 'edit'],
                    'minLength' => 1,
                    'maxLength' => 255,
                    'required' => true,
                ],
                'address' => [
                    'type' => 'string',
                    'description' => __('Company physical address.', 'schedula-smart-appointment-booking'),
                    'default' => '',
                    'context' => ['view', 'edit'],
                    'maxLength' => 500,
                ],
                'phone' => [
                    'type' => 'string',
                    'description' => __('Company main phone number.', 'schedula-smart-appointment-booking'),
                    'default' => '',
                    'context' => ['view', 'edit'],
                    'format' => 'tel',
                    'maxLength' => 50,
                ],
                'website' => [
                    'type' => 'string',
                    'description' => __('Company website URL.', 'schedula-smart-appointment-booking'),
                    'default' => '',
                    'context' => ['view', 'edit'],
                    'format' => 'url',
                    'maxLength' => 255,
                ],
                'industry' => [
                    'type' => 'string',
                    'description' => __('Industry your business operates in.', 'schedula-smart-appointment-booking'),
                    'default' => '',
                    'context' => ['view', 'edit'],
                    'enum' => array_column($this->get_industry_options(), 'value'),
                    'maxLength' => 100,
                ],
                'servicesOffered' => [ // Renamed from 'services' for clarity
                    'type' => 'string',
                    'description' => __('Brief description of services provided.', 'schedula-smart-appointment-booking'),
                    'default' => '',
                    'context' => ['view', 'edit'],
                    'maxLength' => 500,
                ],
                'companySize' => [
                    'type' => 'string',
                    'description' => __('Approximate number of employees or company scale.', 'schedula-smart-appointment-booking'),
                    'default' => '',
                    'context' => ['view', 'edit'],
                    'enum' => array_column($this->get_company_size_options(), 'value'),
                    'maxLength' => 50,
                ],
                'email' => [
                    'type' => 'string',
                    'description' => __('Company primary contact email address.', 'schedula-smart-appointment-booking'),
                    'default' => '',
                    'context' => ['view', 'edit'],
                    'format' => 'email',
                    'minLength' => 1,
                    'maxLength' => 255,
                    'required' => true,
                ],
                'companyLogoUrl' => [ // NEW: Company Logo URL
                    'type' => 'string',
                    'description' => __('URL of the company logo to display in the admin area.', 'schedula-smart-appointment-booking'),
                    'default' => '',
                    'context' => ['view', 'edit'],
                    'format' => 'uri', // Using 'uri' to indicate it's a URL
                    'maxLength' => 2048,
                ],
            ],
        ];
    }

    /**
     * Get arguments for settings update based on type.
     *
     * @param string $type The type of settings ('general', 'company').
     * @return array
     */
    protected function get_settings_args($type)
    {
        $schema_method = 'get_item_schema_for_' . $type;
        if (!method_exists($this, $schema_method)) {
            return [];
        }
        $schema = $this->$schema_method();
        $args = [];

        foreach ($schema['properties'] as $field => $properties) {
            $args[$field] = [
                'description' => $properties['description'] ?? '',
                'type' => $properties['type'] ?? 'string',
                'sanitize_callback' => function ($value) use ($properties) {
                    return $this->sanitize_setting_value($value, $properties);
                },
                'validate_callback' => function ($value) use ($properties, $field) {
                    // Pass the full field name for nested properties for correct enum lookup
                    $field_for_validation = $field; // Default to current field
                    if (isset($properties['context']) && in_array('priceCorrection.type', $properties['context'])) {
                        $field_for_validation = 'priceCorrection.type';
                    } elseif (isset($properties['context']) && in_array('groupBookingPriceLogic.type', $properties['context'])) {
                        $field_for_validation = 'groupBookingPriceLogic.type';
                    }
                    // For recurrence, no 'type' to check anymore, only 'maxRecurrences'
                    // The 'recurrence' field itself is handled as an object.
                    return $this->validate_setting_value($value, $properties, $field_for_validation);
                },
                'required' => $properties['required'] ?? false,
            ];
            // Handle nested properties for 'object' type arguments
            if ($properties['type'] === 'object' && isset($properties['properties'])) {
                foreach ($properties['properties'] as $nested_field => $nested_properties) {
                    // Create a sub-array for nested object properties within args
                    if (!isset($args[$field]['properties'])) {
                        $args[$field]['properties'] = [];
                    }
                    $args[$field]['properties'][$nested_field] = [
                        'type' => $nested_properties['type'] ?? 'string',
                        'sanitize_callback' => function ($value) use ($nested_properties) {
                            return $this->sanitize_setting_value($value, $nested_properties);
                        },
                        'validate_callback' => function ($value) use ($nested_properties, $nested_field, $field) {
                            // Pass the correct field name for nested properties
                            return $this->validate_setting_value($value, $nested_properties, $field . '.' . $nested_field);
                        },
                        'required' => $nested_properties['required'] ?? false,
                    ];
                }
            }
        }

        return $args;
    }

    /**
     * Sanitizes an array of schedule items.
     *
     * @param array $schedule_array The array of schedule items.
     * @return array Sanitized schedule array.
     */
    private function sanitize_schedule_array($schedule_array)
    {
        $sanitized = [];
        if (!is_array($schedule_array))
            return [];

        foreach ($schedule_array as $item) {
            if (!is_array($item))
                continue;
            $sanitized_item = [
                'day_of_week' => absint($item['day_of_week'] ?? 0),
                'start_time' => sanitize_text_field($item['start_time'] ?? ''),
                'end_time' => sanitize_text_field($item['end_time'] ?? ''),
                'breaks' => [],
            ];
            if (isset($item['breaks']) && is_array($item['breaks'])) {
                foreach ($item['breaks'] as $break) {
                    if (!is_array($break))
                        continue;
                    $sanitized_item['breaks'][] = [
                        'start_time' => sanitize_text_field($break['start_time'] ?? ''),
                        'end_time' => sanitize_text_field($break['end_time'] ?? ''),
                        'description' => sanitize_text_field($break['description'] ?? ''),
                    ];
                }
            }
            $sanitized[] = $sanitized_item;
        }
        return $sanitized;
    }

    /**
     * Validates an array of schedule items.
     *
     * @param array $schedule_array The array of schedule items.
     * @return true|WP_Error True if valid, WP_Error otherwise.
     */
    private function validate_schedule_array($schedule_array)
    {
        if (!is_array($schedule_array)) {
            return new WP_Error('rest_invalid_param', __('Default business schedule must be an array.', 'schedula-smart-appointment-booking'), ['status' => 400]);
        }

        foreach ($schedule_array as $index => $item) {
            if (!is_array($item)) {
                // translators: %d is the index of the schedule item
                return new WP_Error('rest_invalid_param', sprintf(__('Schedule item at index %d must be an object.', 'schedula-smart-appointment-booking'), $index), ['status' => 400]);
            }

            $day_of_week = $item['day_of_week'] ?? null;
            $start_time = $item['start_time'] ?? '';
            $end_time = $item['end_time'] ?? '';
            $breaks = $item['breaks'] ?? [];

            if (!is_numeric($day_of_week) || $day_of_week < 0 || $day_of_week > 6) {
                // translators: %d is the schedule index
                return new WP_Error('rest_invalid_param', sprintf(__('Invalid day of week for schedule item at index %d.', 'schedula-smart-appointment-booking'), $index), ['status' => 400]);
            }

            // Validate time format (HH:MM)
            $time_pattern = '/^(?:[01]\d|2[0-3]):[0-5]\d$/';
            if (!empty($start_time) && !preg_match($time_pattern, $start_time)) {
                // translators: %d is the schedule index
                return new WP_Error('rest_invalid_param', sprintf(__('Invalid start time format for schedule item at index %d.', 'schedula-smart-appointment-booking'), $index), ['status' => 400]);
            }
            if (!empty($end_time) && !preg_match($time_pattern, $end_time)) {
                // translators: %d is the schedule index
                return new WP_Error('rest_invalid_param', sprintf(__('Invalid end time format for schedule item at index %d.', 'schedula-smart-appointment-booking'), $index), ['status' => 400]);
            }

            // Validate start and end times for the day
            if (!empty($start_time) && !empty($end_time)) {
                if (strtotime($start_time) >= strtotime($end_time)) {
                    // translators: %d is the schedule index
                    return new WP_Error('rest_invalid_param', sprintf(__('Start time must be before end time for schedule item at index %d.', 'schedula-smart-appointment-booking'), $index), ['status' => 400]);
                }
            } elseif ((empty($start_time) && !empty($end_time)) || (!empty($start_time) && empty($end_time))) {
                // translators: %d is the schedule index
                return new WP_Error('rest_invalid_param', sprintf(__('Both start and end times must be provided for schedule item at index %d, or both must be empty for "off" days.', 'schedula-smart-appointment-booking'), $index), ['status' => 400]);
            }

            // Validate breaks
            if (!is_array($breaks)) {
                // translators: %d is the schedule index
                return new WP_Error('rest_invalid_param', sprintf(__('Breaks for schedule item at index %d must be an array.', 'schedula-smart-appointment-booking'), $index), ['status' => 400]);
            }
            $prev_break_end_time = null;
            foreach ($breaks as $b_index => $b) {
                if (!is_array($b) || !isset($b['start_time']) || !isset($b['end_time'])) {
                    // translators: %1$d is the schedule index, %2$d is the break index
                    return new WP_Error('rest_invalid_param', sprintf(__('Break item at schedule index %1$d, break index %2$d is invalid.', 'schedula-smart-appointment-booking'), $index, $b_index), ['status' => 400]);
                }

                $break_start = $b['start_time'];
                $break_end = $b['end_time'];
                $break_description = $b['description'] ?? '';

                if (!preg_match($time_pattern, $break_start) || !preg_match($time_pattern, $break_end)) {
                    // translators: %1$d is the schedule index, %2$d is the break index
                    return new WP_Error('rest_invalid_param', sprintf(__('Invalid time format for break at schedule index %1$d, break index %2$d.', 'schedula-smart-appointment-booking'), $index, $b_index), ['status' => 400]);
                }
                if (strtotime($break_start) >= strtotime($break_end)) {
                    // translators: %1$d is the schedule index, %2$d is the break index
                    return new WP_Error('rest_invalid_param', sprintf(__('Break start time must be before end time for break at schedule index %1$d, break index %2$d.', 'schedula-smart-appointment-booking'), $index, $b_index), ['status' => 400]);
                }

                // Check if break is within day's working hours (if working hours are set)
                if (!empty($start_time) && !empty($end_time)) {
                    if (strtotime($break_start) < strtotime($start_time) || strtotime($break_end) > strtotime($end_time)) {
                        // translators: %1$d is the schedule index, %2$d is the break index
                        return new WP_Error('rest_invalid_param', sprintf(__('Break at schedule index %1$d, break index %2$d must be within working hours.', 'schedula-smart-appointment-booking'), $index, $b_index), ['status' => 400]);
                    }
                }

                // Check for overlaps with other breaks within the same day
                if ($prev_break_end_time && strtotime($break_start) < strtotime($prev_break_end_time)) {
                    // translators: %1$d is the schedule index, %2$d is the break index
                    return new WP_Error('rest_invalid_param', sprintf(__('Break at schedule index %1$d, break index %2$d overlaps with a previous break.', 'schedula-smart-appointment-booking'), $index, $b_index), ['status' => 400]);
                }
                $prev_break_end_time = $break_end;
            }
        }
        return true;
    }

    /**
     * Sanitizes an array of holiday items.
     *
     * @param array $holidays_array The array of holiday items.
     * @return array Sanitized holidays array.
     */
    private function sanitize_holidays_array($holidays_array)
    {
        $sanitized = [];
        if (!is_array($holidays_array))
            return [];

        foreach ($holidays_array as $item) {
            if (!is_array($item))
                continue;
            $sanitized_item = [
                'start_date' => sanitize_text_field($item['start_date'] ?? ''),
                'end_date' => sanitize_text_field($item['end_date'] ?? ''),
                'description' => sanitize_text_field($item['description'] ?? ''),
            ];
            $sanitized[] = $sanitized_item;
        }
        return $sanitized;
    }

    /**
     * Validates an array of holiday items.
     *
     * @param array $holidays_array The array of holiday items.
     * @return true|WP_Error True if valid, WP_Error otherwise.
     */
    private function validate_holidays_array($holidays_array)
    {
        if (!is_array($holidays_array)) {
            return new WP_Error('rest_invalid_param', __('Default business holidays must be an array.', 'schedula-smart-appointment-booking'), ['status' => 400]);
        }

        foreach ($holidays_array as $index => $item) {
            if (!is_array($item)) {
                // translators: %d is the index of the holiday item
                return new WP_Error('rest_invalid_param', sprintf(__('Holiday item at index %d must be an object.', 'schedula-smart-appointment-booking'), $index), ['status' => 400]);
            }

            $start_date_str = $item['start_date'] ?? '';
            $end_date_str = $item['end_date'] ?? '';
            $description = $item['description'] ?? '';

            // Validate date format (YYYY-MM-DD)
            $date_pattern = '/^\d{4}-\d{2}-\d{2}$/';
            if (!preg_match($date_pattern, $start_date_str) || !preg_match($date_pattern, $end_date_str)) {
                // translators: %d is the index of the holiday item
                return new WP_Error('rest_invalid_param', sprintf(__('Invalid date format (YYYY-MM-DD) for holiday item at index %d.', 'schedula-smart-appointment-booking'), $index), ['status' => 400]);
            }

            // Validate date validity and range
            $start_date = strtotime($start_date_str);
            $end_date = strtotime($end_date_str);

            if (!$start_date || !$end_date) {
                // translators: %d is the index of the holiday item
                return new WP_Error('rest_invalid_param', sprintf(__('Invalid date values for holiday item at index %d.', 'schedula-smart-appointment-booking'), $index), ['status' => 400]);
            }
            if ($start_date > $end_date) {
                // translators: %d is the index of the holiday item
                return new WP_Error('rest_invalid_param', sprintf(__('Holiday start date must be before or on end date for item at index %d.', 'schedula-smart-appointment-booking'), $index), ['status' => 400]);
            }
        }
        return true;
    }

    /**
     * Sanitizes the price correction object.
     *
     * @param array $price_correction_object The price correction object.
     * @return array Sanitized price correction object.
     */
    private function sanitize_price_correction_object($price_correction_object)
    {
        if (!is_array($price_correction_object)) {
            return ['type' => 'none', 'amount' => 0]; // Default fallback
        }
        return [
            'type' => sanitize_text_field($price_correction_object['type'] ?? 'none'),
            'amount' => absint($price_correction_object['amount'] ?? 0),
        ];
    }

    /**
     * Validates the price correction object.
     *
     * @param array $price_correction_object The price correction object.
     * @return true|WP_Error True if valid, WP_Error otherwise.
     */
    private function validate_price_correction_object($price_correction_object)
    {
        if (!is_array($price_correction_object)) {
            return new WP_Error('rest_invalid_param', __('Price correction must be an object.', 'schedula-smart-appointment-booking'), ['status' => 400]);
        }

        $type = $price_correction_object['type'] ?? '';
        $amount = $price_correction_object['amount'] ?? null;

        if (!in_array($type, ['none', 'increase_percent', 'discount_percent', 'addition', 'deduction'])) {
            return new WP_Error('rest_invalid_param', __('Invalid price correction type.', 'schedula-smart-appointment-booking'), ['status' => 400]);
        }

        if ($type !== 'none' && (!is_numeric($amount) || $amount < 0)) {
            return new WP_Error('rest_invalid_param', __('Price correction amount must be a non-negative number.', 'schedula-smart-appointment-booking'), ['status' => 400]);
        }

        return true;
    }

    private function sanitize_recurrence_object($recurrence_object)
    {
        if (!is_array($recurrence_object)) {
            return ['maxRecurrences' => 0, 'paymentBehavior' => 'charge_all']; // Default fallback
        }
        return [
            'maxRecurrences' => absint($recurrence_object['maxRecurrences'] ?? 0),
            'paymentBehavior' => sanitize_text_field($recurrence_object['paymentBehavior'] ?? 'charge_all'),
        ];
    }

    private function validate_recurrence_object($recurrence_object)
    {
        if (!is_array($recurrence_object)) {
            return new WP_Error('rest_invalid_param', __('Recurrence setting must be an object.', 'schedula-smart-appointment-booking'), ['status' => 400]);
        }

        $maxRecurrences = $recurrence_object['maxRecurrences'] ?? null;
        $paymentBehavior = $recurrence_object['paymentBehavior'] ?? '';

        if (!is_numeric($maxRecurrences) || (int) $maxRecurrences < 0) {
            return new WP_Error('rest_invalid_param', __('maxRecurrences must be a non-negative integer.', 'schedula-smart-appointment-booking'), ['status' => 400]);
        }

        $allowed_behaviors = ['charge_all', 'charge_first', 'manual'];
        if (!in_array($paymentBehavior, $allowed_behaviors, true)) {
            return new WP_Error('rest_invalid_param', __('Invalid paymentBehavior for recurrence.', 'schedula-smart-appointment-booking'), ['status' => 400]);
        }

        return true;
    }



    /**
     * Sanitizes the group booking price logic object.
     *
     * @param array $group_booking_price_logic_object The group booking price logic object.
     * @return array Sanitized group booking price logic object.
     */
    private function sanitize_group_booking_price_logic($group_booking_price_logic_object)
    {
        if (!is_array($group_booking_price_logic_object)) {
            return ['type' => 'per_person_multiply', 'amount' => 0]; // Default fallback
        }
        return [
            'type' => sanitize_text_field($group_booking_price_logic_object['type'] ?? 'per_person_multiply'),
            // Use floatval for amount to handle percentages (e.g., 5.5 for 5.5%)
            'amount' => floatval($group_booking_price_logic_object['amount'] ?? 0),
        ];
    }

    /**
     * Validates the group booking price logic object.
     *
     * @param array $group_booking_price_logic_object The group booking price logic object.
     * @return true|WP_Error True if valid, WP_Error otherwise.
     */
    private function validate_group_booking_price_logic($group_booking_price_logic_object)
    {
        if (!is_array($group_booking_price_logic_object)) {
            return new WP_Error('rest_invalid_param', __('Group booking price logic must be an object.', 'schedula-smart-appointment-booking'), ['status' => 400]);
        }

        $type = $group_booking_price_logic_object['type'] ?? '';
        $amount = $group_booking_price_logic_object['amount'] ?? null;

        $valid_types = array_column($this->get_enum_options_for_field('groupBookingPriceLogic.type'), 'value');
        if (!in_array($type, $valid_types, true)) {
            return new WP_Error('rest_invalid_param', __('Invalid group booking price logic type.', 'schedula-smart-appointment-booking'), ['status' => 400]);
        }

        if (!is_numeric($amount) || (float) $amount < 0) {
            return new WP_Error('rest_invalid_param', __('Group booking price adjustment amount must be a non-negative number.', 'schedula-smart-appointment-booking'), ['status' => 400]);
        }

        return true;
    }
}
