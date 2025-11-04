<?php

/**
 * Appearance Settings API Class
 * Handles appearance customization settings for the booking form
 *
 * @package SCHESAB\Api
 * 
 */


namespace SCHESAB\Api;

use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

if (!defined('ABSPATH')) {
    exit;
}

class SCHESAB_Appearance
{

    private $namespace = 'schesab/v1';
    private $option_name = 'schesab_appearance_settings';

    public function __construct()
    {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    /**
     * Register REST API routes
     */
    public function register_routes()
    {
        // Admin routes (require manage_options capability)
        register_rest_route('schesab/v1', '/appearance-settings', array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_settings'),
                'permission_callback' => array($this, 'check_admin_permissions')
            ),
            array(
                'methods' => 'POST',
                'callback' => array($this, 'save_settings'),
                'permission_callback' => array($this, 'check_admin_permissions'),
                'args' => $this->get_settings_schema() // This is crucial for validation
            ),
            array(
                'methods' => 'DELETE',
                'callback' => array($this, 'reset_settings'),
                'permission_callback' => array($this, 'check_admin_permissions')
            )
        ));

        // Public route for frontend (no authentication required)
        register_rest_route($this->namespace, '/public/appearance-settings', [
            'methods' => WP_REST_Server::READABLE, // GET
            'callback' => [$this, 'get_public_settings'],
            'permission_callback' => '__return_true', // Publicly accessible
            'schema' => [$this, 'get_settings_schema'],
        ]);

        // NEW: Route for fetching Google Fonts securely from the backend
        register_rest_route($this->namespace, '/google-fonts', array(
            'methods' => WP_REST_Server::READABLE, // GET
            'callback' => array($this, 'get_google_fonts'),
            'permission_callback' => array($this, 'check_admin_permissions'), // Only accessible by authenticated admins
            'args' => array(
                'search' => array( // Add an optional search parameter for filtering fonts
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => 'rest_validate_request_arg',
                    'required' => false,
                    'type' => 'string',
                ),
            ),
        ));
    }

    /**
     * Check admin permissions
     */
    public function check_admin_permissions()
    {
        return current_user_can('manage_options');
    }

    /**
     * Get default settings structure
     * This should match the default structure in useAppearanceSettings.js
     */
    private function get_default_settings()
    {
        return array(
            'colors' => array(
                'primary' => '#081a30', // Used for header background, buttons, active elements
                'background' => '#f8fafc', // Main page background
                'textColor' => '#1a202c', // General text color
                'headerText' => '#ffffff', // Specific header text color
            ),
            'theme' => array(
                'darkMode' => false,
                'roundedCorners' => true,
                'shadows' => true,
                // 'animations' => true, // REMOVED as per user request
            ),
            'services' => array(
                'showServiceImages' => true,
                'showCategoryDescription' => false,
                'showServiceDescription' => true,
                'showServicePreview' => true,
            ),
            'staff' => array(
                'showStaffInfo' => true,
                'allowAnyEmployee' => true,
            ),
            'calendar' => array(
                'showCalendar' => true,
                'layoutStyle' => 'default',
                'showBlockedTimeslots' => false, // New setting
                'showOnlyNearestTimeslot' => false, // New setting
            ),
            'customer' => array(
                'showNotesField' => false,
                'showFirstNameField' => true,
                'showLastNameField' => true,
                'showEmailField' => true,
                'showPhoneField' => true,
            ),
            'payment' => array(
                'showPaymentStep' => true,
                'allowCashPayment' => true,
                'allowCardPayment' => false,
                'showPriceBreakdown' => true,
                'showTaxes' => false,
                'showPaymentMethodDescription' => true,
            ),
            'confirmation' => array(
                'showSummaryStep' => true,
                'showServiceImage' => true,
                'showStaffInfo' => true,
                'allowEditing' => false,
                'showBookAgainButton' => true,
                'showConfirmationDetails' => true,
            ),
            'layout' => array(
                'formWidth' => '960px',
                'inputSize' => 'small',
                'fontSize' => 'small',
                'borderRadius' => 'medium',
                'fontFamily' => 'Inter, sans-serif',
                'shadowStrength' => 'medium',
            ),
            'forms' => array( // NEW SECTION FOR FORM-SPECIFIC SETTINGS
                'serviceForm' => array(
                    'displayStaffNames' => true, // New setting for service forms
                ),
            ),
            'labels' => array(
                // Header and step descriptions
                'book_appointment' => __('Book Your Appointment', 'schedula-smart-appointment-booking'),
                'booking_steps_description' => __('Complete your booking in these simple steps.', 'schedula-smart-appointment-booking'),
                'step_1_title' => __('Service', 'schedula-smart-appointment-booking'),
                'step_1_subtitle' => __('Choose your service', 'schedula-smart-appointment-booking'),
                'step_2_title' => __('Time Slot', 'schedula-smart-appointment-booking'),
                'step_2_subtitle' => __('Pick date & time', 'schedula-smart-appointment-booking'),
                'step_3_title' => __('Details', 'schedula-smart-appointment-booking'),
                'step_3_subtitle' => __('Your informations', 'schedula-smart-appointment-booking'),
                'step_4_title' => __('Payment', 'schedula-smart-appointment-booking'),
                'step_4_subtitle' => __('Payment method', 'schedula-smart-appointment-booking'),
                'step_5_title' => __('Confirm', 'schedula-smart-appointment-booking'),
                'step_5_subtitle' => __('Review & confirm', 'schedula-smart-appointment-booking'),
                // NEW LABEL
                'service_form_title' => __('Book Your Service', 'schedula-smart-appointment-booking'),

                // Service selection
                'category' => __('Category', 'schedula-smart-appointment-booking'),
                'choose_category' => __('Choose Category', 'schedula-smart-appointment-booking'),
                'about_this_category' => __('About This Category', 'schedula-smart-appointment-booking'),
                'service' => __('Service', 'schedula-smart-appointment-booking'),
                'choose_service' => __('Choose Service', 'schedula-smart-appointment-booking'),
                'employee' => __('Employee', 'schedula-smart-appointment-booking'),
                'any_employee' => __('Any Employee', 'schedula-smart-appointment-booking'),
                'duration_label' => __('Duration', 'schedula-smart-appointment-booking'),
                'minutes_suffix' => __('min', 'schedula-smart-appointment-booking'),
                'price_label' => __('Price', 'schedula-smart-appointment-booking'),
                'service_description_title' => __('Service Description', 'schedula-smart-appointment-booking'),
                'service_preview_title' => __('Service Preview', 'schedula-smart-appointment-booking'),
                'service_image_placeholder' => __('Service image preview', 'schedula-smart-appointment-booking'),

                // Date & Time selection
                'select_date' => __('Select Date', 'schedula-smart-appointment-booking'),
                'available_times_title' => __('Available Times', 'schedula-smart-appointment-booking'),
                'select_date_time_description' => __('Select your preferred date and time', 'schedula-smart-appointment-booking'),
                'no_appointments_available' => __('No available appointments', 'schedula-smart-appointment-booking'),
                'check_back_later' => __('Please check back later.', 'schedula-smart-appointment-booking'),
                'select_a_date' => __('Select a date', 'schedula-smart-appointment-booking'),
                'choose_date_to_see_times' => __('Choose a date to see available times', 'schedula-smart-appointment-booking'),
                'loading_available_times' => __('Loading available times...', 'schedula-smart-appointment-booking'),
                'no_available_times' => __('No available times', 'schedula-smart-appointment-booking'),
                'select_different_date' => __('Please select a different date', 'schedula-smart-appointment-booking'),
                'selected_time_prefix' => __('Selected:', 'schedula-smart-appointment-booking'),

                // Customer details
                'your_information' => __('Your Informations', 'schedula-smart-appointment-booking'),
                'first_name' => __('First Name', 'schedula-smart-appointment-booking'),
                'enter_first_name_placeholder' => __('Enter your first name', 'schedula-smart-appointment-booking'),
                'last_name' => __('Last Name', 'schedula-smart-appointment-booking'),
                'enter_last_name_placeholder' => __('Enter your last name', 'schedula-smart-appointment-booking'),
                'email' => __('Email Address', 'schedula-smart-appointment-booking'),
                'enter_email_placeholder' => __('Enter your email address', 'schedula-smart-appointment-booking'),
                'phone' => __('Phone Number', 'schedula-smart-appointment-booking'),
                'enter_phone_placeholder' => __('Enter your phone number', 'schedula-smart-appointment-booking'),
                'notes_label' => __('Notes', 'schedula-smart-appointment-booking'),
                'notes_placeholder' => __('Any special requests or notes?', 'schedula-smart-appointment-booking'),

                // Payment
                'payment_method' => __('Payment Method', 'schedula-smart-appointment-booking'),
                'pay_with_cash' => __('Pay with Cash', 'schedula-smart-appointment-booking'),
                'pay_at_appointment_description' => __('Pay at your appointment', 'schedula-smart-appointment-booking'),
                'total_amount' => __('Total Amount', 'schedula-smart-appointment-booking'),

                // Confirmation
                'confirm_appointment' => __('Confirm Your Appointment', 'schedula-smart-appointment-booking'),
                'date_time' => __('Date & Time', 'schedula-smart-appointment-booking'),
                'employee_confirmation' => __('Employee', 'schedula-smart-appointment-booking'),
                'duration_confirmation' => __('Duration', 'schedula-smart-appointment-booking'),
                'minutes_suffix_confirmation' => __('minutes', 'schedula-smart-appointment-booking'),
                'total_price_confirmation' => __('Total Price', 'schedula-smart-appointment-booking'),
                'edit_details_button' => __('Edit Details', 'schedula-smart-appointment-booking'),

                // Navigation buttons
                'previous' => __('Previous', 'schedula-smart-appointment-booking'),
                'continue' => __('Continue', 'schedula-smart-appointment-booking'),
                'confirm' => __('Confirm Appointment', 'schedula-smart-appointment-booking'),
                'confirming' => __('Confirming...', 'schedula-smart-appointment-booking'),
                'book_again' => __('Book Another Appointment', 'schedula-smart-appointment-booking'),
            ),
        );
    }

    /**
     * Get appearance settings (admin)
     */
    public function get_settings($request)
    {
        try {
            $default_settings = $this->get_default_settings();
            $saved_settings = get_option($this->option_name, array());

            // Merge with defaults to ensure all keys exist
            $settings = $this->array_merge_recursive_distinct($default_settings, $saved_settings);

            return rest_ensure_response(array(
                'success' => true,
                'data' => $settings,
                'message' => 'Settings retrieved successfully'
            ));
        } catch (\Exception $e) {
            return new \WP_Error(
                'settings_error',
                'Failed to retrieve settings: ' . $e->getMessage(),
                array('status' => 500)
            );
        }
    }

    /**
     * Get appearance settings for public use (frontend)
     */
    public function get_public_settings($request)
    {
        try {
            $default_settings = $this->get_default_settings();
            $saved_settings = get_option($this->option_name, array());

            // Merge with defaults
            $settings = $this->array_merge_recursive_distinct($default_settings, $saved_settings);

            return rest_ensure_response(array(
                'success' => true,
                'data' => $settings
            ));
        } catch (\Exception $e) {
            // Return defaults if there's an error
            return rest_ensure_response(array(
                'success' => true,
                'data' => $this->get_default_settings()
            ));
        }
    }

    /**
     * Save appearance settings
     */
    public function save_settings($request)
    {
        try {
            $settings = $request->get_json_params();
            if (empty($settings)) {
                return new \WP_Error(
                    __('invalid_data', 'schedula-smart-appointment-booking'),
                    __('No settings data provided', 'schedula-smart-appointment-booking'),
                    array('status' => 400)
                );
            }

            // Validate and sanitize settings data using the schema
            // The REST API's args validation already handles the initial validation
            // We can use validate_recursive for deeper sanitization if needed,
            // but for a simple save, the data should already conform to the schema.
            $validated_settings = $settings; // Assuming REST API args validation is sufficient

            // Save settings
            $result = update_option($this->option_name, $validated_settings);

            if ($result !== false) {
                // Clear any caches
                wp_cache_delete($this->option_name, 'options');
                return rest_ensure_response(array(
                    'success' => true,
                    'message' => __('Settings saved successfully', 'schedula-smart-appointment-booking'),
                    'data' => $validated_settings
                ));
            } else {
                return new \WP_Error(
                    __('save_failed', 'schedula-smart-appointment-booking'),
                    __('Failed to save settings', 'schedula-smart-appointment-booking'),
                    array('status' => 500)
                );
            }
        } catch (\Exception $e) {
            return new \WP_Error(
                'save_error',
                // translators: %s is the error message returned by the exception
                sprintf(__('Error saving settings: %s', 'schedula-smart-appointment-booking'), $e->getMessage()),
                array('status' => 500)
            );
        }
    }

    /**
     * Reset settings to defaults
     */
    public function reset_settings($request)
    {
        try {
            $result = delete_option($this->option_name);
            // Clear caches
            wp_cache_delete($this->option_name, 'options');
            return rest_ensure_response(array(
                'success' => true,
                'message' => __('Settings reset to defaults successfully', 'schedula-smart-appointment-booking'),
                'data' => $this->get_default_settings()
            ));
        } catch (\Exception $e) {
            return new \WP_Error(
                'reset_error',
                // translators: %s is the error message returned by the exception
                sprintf(__('Error resetting settings: %s', 'schedula-smart-appointment-booking'), $e->getMessage()),
                array('status' => 500)
            );
        }
    }

    /**
     * NEW: Fetches Google Fonts from the Google Fonts API.
     * The API key is stored securely on the server.
     *
     * @param WP_REST_Request $request The request object.
     * @return WP_REST_Response The response object.
     */
    public function get_google_fonts(WP_REST_Request $request)
    {
        // Use local font list instead of external Google Fonts API to comply with WordPress plugin guidelines
        $search_query = $request->get_param('search'); // Get the optional search query from frontend

        // Local font list to replace external Google Fonts API dependency
        $local_fonts = array(
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
        );

        $fonts = array();
        foreach ($local_fonts as $font_family) {
            // Only include fonts that match the search query if one is provided
            if (empty($search_query) || stripos($font_family, $search_query) !== false) {
                $fonts[] = "{$font_family}, sans-serif"; // Add sans-serif fallback
            }
        }

        return rest_ensure_response(array(
            'success' => true,
            'fonts' => $fonts,
            'message' => __('Local fonts loaded successfully.', 'schedula-smart-appointment-booking')
        ));
    }


    /**
     * Get the JSON schema for appearance settings.
     * This is used for validation in register_rest_route().
     * This must precisely match the structure of the settings object from the frontend.
     */
    private function get_settings_schema()
    {
        return array(
            'colors' => array(
                'type' => 'object',
                'properties' => array(
                    'primary' => array('type' => 'string', 'required' => true),
                    'background' => array('type' => 'string', 'required' => true),
                    'textColor' => array('type' => 'string', 'required' => true),
                    'headerText' => array('type' => 'string', 'required' => true),
                ),
                'required' => true,
            ),
            'theme' => array(
                'type' => 'object',
                'properties' => array(
                    'darkMode' => array('type' => 'boolean', 'required' => true),
                    'roundedCorners' => array('type' => 'boolean', 'required' => true),
                    'shadows' => array('type' => 'boolean', 'required' => true),
                    // 'animations' => array('type' => 'boolean', 'required' => true), // REMOVED
                ),
                'required' => true,
            ),
            'services' => array(
                'type' => 'object',
                'properties' => array(
                    'showServiceImages' => array('type' => 'boolean', 'required' => true),
                    'showCategoryDescription' => array('type' => 'boolean', 'required' => true),
                    'showServiceDescription' => array('type' => 'boolean', 'required' => true),
                    'showServicePreview' => array('type' => 'boolean', 'required' => true),
                ),
                'required' => true,
            ),
            'staff' => array(
                'type' => 'object',
                'properties' => array(
                    'showStaffInfo' => array('type' => 'boolean', 'required' => true),
                    'allowAnyEmployee' => array('type' => 'boolean', 'required' => true),
                ),
                'required' => true,
            ),
            'calendar' => array(
                'type' => 'object',
                'properties' => array(
                    'showCalendar' => array('type' => 'boolean', 'required' => true),
                    'layoutStyle' => array('type' => 'string', 'required' => true),
                    'showBlockedTimeslots' => array('type' => 'boolean', 'required' => true), // New setting
                    'showOnlyNearestTimeslot' => array('type' => 'boolean', 'required' => true), // New setting
                ),
                'required' => true,
            ),
            'customer' => array(
                'type' => 'object',
                'properties' => array(
                    'showNotesField' => array('type' => 'boolean', 'required' => true),
                    'showFirstNameField' => array('type' => 'boolean', 'required' => true),
                    'showLastNameField' => array('type' => 'boolean', 'required' => true),
                    'showEmailField' => array('type' => 'boolean', 'required' => true),
                    'showPhoneField' => array('type' => 'boolean', 'required' => true),
                ),
                'required' => true,
            ),
            'payment' => array(
                'type' => 'object',
                'properties' => array(
                    'showPaymentStep' => array('type' => 'boolean', 'required' => true),
                    'allowCashPayment' => array('type' => 'boolean', 'required' => true),
                    'allowCardPayment' => array('type' => 'boolean', 'required' => true),
                    'showPriceBreakdown' => array('type' => 'boolean', 'required' => true),
                    'showTaxes' => array('type' => 'boolean', 'required' => true),
                    'showPaymentMethodDescription' => array('type' => 'boolean', 'required' => true),
                ),
                'required' => true,
            ),
            'confirmation' => array(
                'type' => 'object',
                'properties' => array(
                    'showSummaryStep' => array('type' => 'boolean', 'required' => true),
                    'showServiceImage' => array('type' => 'boolean', 'required' => true),
                    'showStaffInfo' => array('type' => 'boolean', 'required' => true),
                    'allowEditing' => array('type' => 'boolean', 'required' => true),
                    'showBookAgainButton' => array('type' => 'boolean', 'required' => true),
                    'showConfirmationDetails' => array('type' => 'boolean', 'required' => true),
                ),
                'required' => true,
            ),
            'layout' => array(
                'type' => 'object',
                'properties' => array(
                    'formWidth' => array('type' => 'string', 'required' => true),
                    'inputSize' => array('type' => 'string', 'required' => true),
                    'fontSize' => array('type' => 'string', 'required' => true),
                    'borderRadius' => array('type' => 'string', 'required' => true),
                    'fontFamily' => array('type' => 'string', 'required' => true),
                    'shadowStrength' => array('type' => 'string', 'required' => true),
                ),
                'required' => true,
            ),
            'forms' => array( // NEW SCHEMA FOR FORMS
                'type' => 'object',
                'properties' => array(
                    'serviceForm' => array(
                        'type' => 'object',
                        'properties' => array(
                            'displayStaffNames' => array('type' => 'boolean', 'required' => true),
                        ),
                        'required' => true,
                    ),
                ),
                'required' => true,
            ),
            'labels' => array(
                'type' => 'object',
                'properties' => array(
                    // Header and step descriptions
                    'book_appointment' => array('type' => 'string', 'required' => true),
                    'booking_steps_description' => array('type' => 'string', 'required' => true),
                    'step_1_title' => array('type' => 'string', 'required' => true),
                    'step_1_subtitle' => array('type' => 'string', 'required' => true),
                    'step_2_title' => array('type' => 'string', 'required' => true),
                    'step_2_subtitle' => array('type' => 'string', 'required' => true),
                    'step_3_title' => array('type' => 'string', 'required' => true),
                    'step_3_subtitle' => array('type' => 'string', 'required' => true),
                    'step_4_title' => array('type' => 'string', 'required' => true),
                    'step_4_subtitle' => array('type' => 'string', 'required' => true),
                    'step_5_title' => array('type' => 'string', 'required' => true),
                    'step_5_subtitle' => array('type' => 'string', 'required' => true),
                    // NEW LABEL
                    'service_form_title' => array('type' => 'string', 'required' => true),

                    // Service selection
                    'category' => array('type' => 'string', 'required' => true),
                    'choose_category' => array('type' => 'string', 'required' => true),
                    'about_this_category' => array('type' => 'string', 'required' => true),
                    'service' => array('type' => 'string', 'required' => true),
                    'choose_service' => array('type' => 'string', 'required' => true),
                    'employee' => array('type' => 'string', 'required' => true),
                    'any_employee' => array('type' => 'string', 'required' => true),
                    'duration_label' => array('type' => 'string', 'required' => true),
                    'minutes_suffix' => array('type' => 'string', 'required' => true),
                    'price_label' => array('type' => 'string', 'required' => true),
                    'service_description_title' => array('type' => 'string', 'required' => true),
                    'service_preview_title' => array('type' => 'string', 'required' => true),
                    'service_image_placeholder' => array('type' => 'string', 'required' => true),

                    // Date & Time selection
                    'select_date' => array('type' => 'string', 'required' => true),
                    'available_times_title' => array('type' => 'string', 'required' => true),
                    'select_date_time_description' => array('type' => 'string', 'required' => true),
                    'no_appointments_available' => array('type' => 'string', 'required' => true),
                    'check_back_later' => array('type' => 'string', 'required' => true),
                    'select_a_date' => array('type' => 'string', 'required' => true),
                    'choose_date_to_see_times' => array('type' => 'string', 'required' => true),
                    'loading_available_times' => array('type' => 'string', 'required' => true),
                    'no_available_times' => array('type' => 'string', 'required' => true),
                    'select_different_date' => array('type' => 'string', 'required' => true),
                    'selected_time_prefix' => array('type' => 'string', 'required' => true),

                    // Customer details
                    'your_information' => array('type' => 'string', 'required' => true),
                    'first_name' => array('type' => 'string', 'required' => true),
                    'enter_first_name_placeholder' => array('type' => 'string', 'required' => true),
                    'last_name' => array('type' => 'string', 'required' => true),
                    'enter_last_name_placeholder' => array('type' => 'string', 'required' => true),
                    'email' => array('type' => 'string', 'required' => true),
                    'enter_email_placeholder' => array('type' => 'string', 'required' => true),
                    'phone' => array('type' => 'string', 'required' => true),
                    'enter_phone_placeholder' => array('type' => 'string', 'required' => true),
                    'notes_label' => array('type' => 'string', 'required' => true),
                    'notes_placeholder' => array('type' => 'string', 'required' => true),

                    // Payment
                    'payment_method' => array('type' => 'string', 'required' => true),
                    'pay_with_cash' => array('type' => 'string', 'required' => true),
                    'pay_at_appointment_description' => array('type' => 'string', 'required' => true),
                    'total_amount' => array('type' => 'string', 'required' => true),

                    // Confirmation
                    'confirm_appointment' => array('type' => 'string', 'required' => true),
                    'date_time' => array('type' => 'string', 'required' => true),
                    'employee_confirmation' => array('type' => 'string', 'required' => true),
                    'duration_confirmation' => array('type' => 'string', 'required' => true),
                    'minutes_suffix_confirmation' => array('type' => 'string', 'required' => true),
                    'total_price_confirmation' => array('type' => 'string', 'required' => true),
                    'edit_details_button' => array('type' => 'string', 'required' => true),

                    // Navigation buttons
                    'previous' => array('type' => 'string', 'required' => true),
                    'continue' => array('type' => 'string', 'required' => true),
                    'confirm' => array('type' => 'string', 'required' => true),
                    'confirming' => array('type' => 'string', 'required' => true),
                    'book_again' => array('type' => 'string', 'required' => true),
                ),
                'required' => true,
                'additionalProperties' => true,
            ),
        );
    }

    /**
     * Validate settings data against the schema and sanitize.
     * This method is generally not needed if args validation in register_rest_route is comprehensive.
     * However, it's kept for consistency and potential deeper validation.
     */
    private function validate_settings($settings)
    {
        $schema = $this->get_settings_schema();
        $validated = array();

        // Recursively validate and sanitize based on the schema
        foreach ($schema as $key => $schema_props) {
            if (isset($settings[$key])) {
                $validated[$key] = $this->validate_recursive_by_schema($settings[$key], $schema_props);
            } elseif (isset($schema_props['required']) && $schema_props['required']) {
                $default_settings = $this->get_default_settings();
                if (isset($default_settings[$key])) {
                    $validated[$key] = $default_settings[$key];
                } else {
                    $validated[$key] = null;
                }
            }
        }

        return $validated;
    }

    /**
     * Recursively validate and sanitize data based on schema properties.
     */
    private function validate_recursive_by_schema($data, $schema)
    {
        if (isset($schema['type'])) {
            switch ($schema['type']) {
                case 'boolean':
                    return filter_var($data, FILTER_VALIDATE_BOOLEAN);
                case 'string':
                    return sanitize_text_field($data);
                case 'object':
                    $validated_object = array();
                    if (is_array($data) && isset($schema['properties'])) {
                        foreach ($schema['properties'] as $prop_key => $prop_schema) {
                            if (isset($data[$prop_key])) {
                                $validated_object[$prop_key] = $this->validate_recursive_by_schema($data[$prop_key], $prop_schema);
                            } elseif (isset($prop_schema['required']) && $prop_schema['required']) {
                                $validated_object[$prop_key] = null;
                            }
                        }
                    }
                    if (isset($schema['additionalProperties']) && $schema['additionalProperties'] === true && is_array($data)) {
                        foreach ($data as $key => $value) {
                            if (!isset($validated_object[$key])) {
                                $validated_object[$key] = sanitize_text_field($value);
                            }
                        }
                    }
                    return $validated_object;
                default:
                    return $data;
            }
        }
        return $data;
    }

    /**
     * Recursively merge two arrays.
     */
    private function array_merge_recursive_distinct(array $array1, array $array2)
    {
        $merged = $array1;
        foreach ($array2 as $key => &$value) {
            if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
                $merged[$key] = $this->array_merge_recursive_distinct($merged[$key], $value);
            } else {
                $merged[$key] = $value;
            }
        }
        return $merged;
    }
}
