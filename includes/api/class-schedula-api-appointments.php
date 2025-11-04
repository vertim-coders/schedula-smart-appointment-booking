<?php

/**
 * REST API Endpoints for Schedula Appointments.
 * Handles fetching dashboard data like appointment counts and earnings,
 * and now also smart suggestions data.
 *
 * @package SCHESAB\Api
 * 
 */

namespace SCHESAB\Api;

use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use SCHESAB\Database\SCHESAB_Database; // Correctly import the Database class
use SCHESAB\Api\SCHESAB_Settings; // Import the Settings class to get general settings
use SCHESAB\Api\SCHESAB_Notifications;

if (!defined('ABSPATH')) {
    exit;
}

class SCHESAB_Appointments
{

    private $namespace = 'schesab/v1';
    private $db;
    private $settings_api; // Added to access general settings
    private $notifications_api;

    public function __construct()
    {
        // Ensure Schedula_Database is loaded and get its instance
        $this->db = \SCHESAB\Database\SCHESAB_Database::get_instance();
        // Initialize Settings API to retrieve plugin settings
        $this->settings_api = new SCHESAB_Settings();
        $this->notifications_api = new SCHESAB_Notifications();

        add_action('schesab_delete_incomplete_appointment', array($this, 'delete_incomplete_appointment_callback'), 10, 1);
    }

    /**
     * Get business timezone from settings.
     * If followAdminTimezone is enabled, returns the admin's current timezone.
     * Otherwise, returns the fixed business timezone.
     *
     * @return string Business timezone identifier.
     */
    private function get_business_timezone()
    {
        $general_settings = $this->settings_api->get_default_settings('general');
        $saved_general_settings = get_option('schesab_general_settings', []);
        $general_settings = array_merge($general_settings, $saved_general_settings);

        // Check if admin wants to follow their current timezone
        if (isset($general_settings['followAdminTimezone']) && $general_settings['followAdminTimezone']) {
            // Get admin's current timezone (WordPress timezone setting)
            $wp_timezone = get_option('timezone_string');
            if (!empty($wp_timezone)) {
                return $wp_timezone;
            }
            // Fallback to UTC offset if timezone string is not set
            $gmt_offset = get_option('gmt_offset');
            if ($gmt_offset !== false) {
                $offset_hours = $gmt_offset;
                $offset_minutes = abs($gmt_offset - floor($gmt_offset)) * 60;
                $sign = $gmt_offset >= 0 ? '+' : '-';
                return sprintf('UTC%s%02d:%02d', $sign, abs($offset_hours), $offset_minutes);
            }
        }

        // Use fixed business timezone
        return isset($general_settings['businessTimezone']) ? $general_settings['businessTimezone'] : 'UTC';
    }

    /**
     * Store datetime directly in business timezone (no conversion).
     * This approach stores times as the business sees them.
     *
     * @param string $datetime Business timezone datetime (Y-m-d H:i:s).
     * @return string Business timezone datetime string (unchanged).
     */
    private function store_business_datetime($datetime)
    {
        // No conversion needed - store as-is in business timezone
        return $datetime;
    }

    /**
     * Get datetime from database (already in business timezone).
     *
     * @param string $datetime Business timezone datetime from database.
     * @return string Business timezone datetime string (unchanged).
     */
    private function get_business_datetime($datetime)
    {
        // No conversion needed - already in business timezone
        return $datetime;
    }

    public function register_routes()
    {
        // NEW: Endpoint to fetch all data needed for the booking form at once.
        register_rest_route($this->namespace, '/booking-form-data', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_booking_form_data'),
            'permission_callback' => '__return_true', // Publicly accessible for the form
        ));

        // Dashboard Summary Endpoint (Admin only)
        register_rest_route($this->namespace, '/dashboard-summary', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_dashboard_summary'),
            'permission_callback' => array($this, 'check_admin_permissions'),
            'args' => array(
                'start_date' => array(
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => function ($param) {
                        return empty($param) || (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $param);
                    },
                    'required' => false,
                ),
                'end_date' => array(
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => function ($param) {
                        return empty($param) || (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $param);
                    },
                    'required' => false,
                ),
                'customer_name' => array(
                    'sanitize_callback' => 'sanitize_text_field',
                    'required' => false,
                ),
                'staff_name' => array(
                    'sanitize_callback' => 'sanitize_text_field',
                    'required' => false,
                ),
                'service_title' => array(
                    'sanitize_callback' => 'sanitize_text_field',
                    'required' => false,
                ),
                'payment_type' => array( // Added filter for payment type
                    'sanitize_callback' => 'sanitize_text_field',
                    'required' => false,
                ),
            ),
        ));

        // Next Upcoming Appointment (Admin only)
        register_rest_route($this->namespace, '/dashboard/next-appointment', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_next_upcoming_appointment'),
            'permission_callback' => array($this, 'check_admin_permissions'),
        ));

        // Appointment Load Analysis (Admin only)
        register_rest_route($this->namespace, '/dashboard/appointment-load-analysis', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_appointment_load_analysis'),
            'permission_callback' => array($this, 'check_admin_permissions'),
        ));

        // Get all appointments (Admin only, with filters and pagination)
        register_rest_route($this->namespace, '/appointments', array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_appointments'),
                'permission_callback' => array($this, 'check_admin_permissions'),
                'args' => array(
                    'start_date' => array(
                        'sanitize_callback' => 'sanitize_text_field',
                        'required' => false,
                    ),
                    'end_date' => array(
                        'sanitize_callback' => 'sanitize_text_field',
                        'required' => false,
                    ),
                    'status' => array(
                        'sanitize_callback' => 'sanitize_text_field',
                        'required' => false,
                    ),
                    'customer_name' => array(
                        'sanitize_callback' => 'sanitize_text_field',
                        'required' => false,
                    ),
                    'staff_name' => array(
                        'sanitize_callback' => 'sanitize_text_field',
                        'required' => false,
                    ),
                    'page' => array( // NEW: Pagination argument
                        'sanitize_callback' => 'absint',
                        'validate_callback' => function ($param) {
                            return is_numeric($param) && $param > 0;
                        },
                        'required' => false,
                        'default' => 1,
                        'description' => __('Current page number.', 'schedula-smart-appointment-booking'),
                    ),
                    'per_page' => array( // NEW: Pagination argument
                        'sanitize_callback' => 'absint',
                        'validate_callback' => function ($param) {
                            return is_numeric($param) && $param > 0;
                        },
                        'required' => false,
                        'default' => 10,
                        'description' => __('Number of items per page.', 'schedula-smart-appointment-booking'),
                    ),
                    'sort_by' => array( // NEW: Sorting argument
                        'sanitize_callback' => 'sanitize_text_field',
                        'validate_callback' => function ($param) {
                            return in_array($param, ['id', 'start_datetime', 'customer_name', 'staff_name', 'service_title', 'price', 'duration', 'status']);
                        },
                        'required' => false,
                        'default' => 'start_datetime', // Default sort column
                        'description' => __('Column to sort by (id, start_datetime, customer_name, staff_name, service_title, price, duration, status).', 'schedula-smart-appointment-booking'),
                    ),
                    'sort_direction' => array( // NEW: Sorting argument
                        'sanitize_callback' => 'sanitize_text_field',
                        'validate_callback' => function ($param) {
                            return in_array(strtolower($param), ['asc', 'desc']);
                        },
                        'required' => false,
                        'default' => 'desc', // Default sort direction
                        'description' => __('Sorting direction (asc or desc).', 'schedula-smart-appointment-booking'),
                    ),
                ),
            ),
            array(
                'methods' => 'POST',
                'callback' => array($this, 'create_appointment'),
                'permission_callback' => '__return_true', // PUBLIC: Frontend booking form needs to create appointments
                'args' => $this->get_appointment_args(),
            ),
        ));

        // Get, Update, Delete single appointment (Admin only)
        register_rest_route($this->namespace, '/appointments/(?P<id>\d+)', array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_appointment'),
                'permission_callback' => array($this, 'check_admin_permissions'),
            ),
            array(
                'methods' => 'PUT',
                'callback' => array($this, 'update_appointment'),
                'permission_callback' => array($this, 'check_admin_permissions'),
                'args' => $this->get_appointment_args(), // Pass true for update args
            ),
            array(
                'methods' => 'DELETE',
                'callback' => array($this, 'delete_appointment'),
                'permission_callback' => array($this, 'check_admin_permissions'),
            ),
        ));

        // Check Staff Availability (PUBLIC: Crucial for frontend validation)
        register_rest_route($this->namespace, '/staff-availability', array(
            'methods' => 'GET',
            'callback' => array($this, 'check_staff_availability_endpoint'),
            'permission_callback' => '__return_true', // PUBLIC
            'args' => array(
                'staff_id' => array(
                    'sanitize_callback' => function ($param) {
                        return absint($param);
                    },
                    'validate_callback' => function ($param) {
                        return is_numeric($param) && $param >= 0;
                    }, // Allow 0 for 'Any Staff'
                    'required' => true,
                ),
                'appointment_date' => array(
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => function ($param) {
                        return (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $param);
                    },
                    'required' => true,
                ),
                'appointment_time' => array(
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => function ($param) {
                        return (bool) preg_match('/^\d{2}:\d{2}$/', $param);
                    },
                    'required' => true,
                ),
                'duration' => array(
                    'sanitize_callback' => function ($param) {
                        return absint($param);
                    },
                    'validate_callback' => function ($param) {
                        return is_numeric($param);
                    },
                    'required' => true,
                ),
                'exclude_appointment_id' => array( // For updates, exclude current appointment from conflict check
                    'sanitize_callback' => function ($param) {
                        return absint($param);
                    },
                    'required' => false,
                    'default' => 0,
                ),
            ),
        ));

        // Services Endpoints (PUBLIC: needed for appointment form lookups)
        register_rest_route($this->namespace, '/services', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_services'),
            'permission_callback' => '__return_true', // PUBLIC
        ));
        register_rest_route($this->namespace, '/services/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_service'),
            'permission_callback' => '__return_true', // PUBLIC
        ));

        register_rest_route($this->namespace, '/services/(?P<service_id>\d+)/staff', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_staff_for_service'),
            'permission_callback' => '__return_true', // PUBLIC
            'args' => array(
                'service_id' => array(
                    'sanitize_callback' => function ($param) {
                        return absint($param);
                    },
                    'validate_callback' => function ($param) {
                        return is_numeric($param) && $param > 0;
                    },
                    'required' => true,
                ),
            ),
        ));

        // Categories Endpoints (PUBLIC: for frontend category dropdown)
        register_rest_route($this->namespace, '/categories', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_categories'),
            'permission_callback' => '__return_true', // PUBLIC
        ));

        // Staff Endpoints (PUBLIC: needed for general staff list if 'Any Employee' needs to fetch all)
        register_rest_route($this->namespace, '/staff', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_staff_members'),
            'permission_callback' => '__return_true', // PUBLIC
            'args' => array(
                'search' => array(
                    'sanitize_callback' => 'sanitize_text_field',
                    'required' => false,
                    'description' => __('Search term for name, email, or phone.', 'schedula-smart-appointment-booking'),
                ),
                'status' => array(
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => function ($param) {
                        return in_array($param, ['active', 'inactive', 'all']);
                    },
                    'required' => false,
                    'default' => 'all',
                ),
                'page' => array(
                    'sanitize_callback' => 'absint',
                    'default' => 1,
                    'required' => false,
                    'description' => __('Current page number.', 'schedula-smart-appointment-booking'),
                ),
                'per_page' => array(
                    'sanitize_callback' => 'absint',
                    'default' => 10,
                    'required' => false,
                    'description' => __('Number of items per page.', 'schedula-smart-appointment-booking'),
                ),
                'sort_by' => array(
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => function ($param, $request, $key) {
                        return in_array($param, ['id', 'name', 'email', 'phone']);
                    },
                    'default' => 'name',
                    'required' => false,
                    'description' => __('Column to sort by (id, name, email, phone).', 'schedula-smart-appointment-booking'),
                ),
                'sort_direction' => array(
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => function ($param, $request, $key) {
                        return in_array(strtolower($param), ['asc', 'desc']);
                    },
                    'default' => 'asc',
                    'required' => false,
                    'description' => __('Sorting direction (asc or desc).', 'schedula-smart-appointment-booking'),
                ),
            ),
        ));

        // NEW: Endpoint to get available time slots (PUBLIC)
        register_rest_route($this->namespace, '/available-time-slots', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_available_time_slots_endpoint'),
            'permission_callback' => '__return_true', // PUBLIC
            'args' => array(
                'service_id' => array(
                    'validate_callback' => function ($param) {
                        return is_numeric($param);
                    },
                    'required' => true,
                ),
                'date' => array(
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => function ($param) {
                        return (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $param);
                    },
                    'required' => true,
                ),
                'staff_id' => array(
                    'sanitize_callback' => 'absint',
                    'required' => false,
                    'default' => 0, // 0 means any staff
                ),
                'persons' => array( // NEW: For group booking slot checking (not capacity based here, but could be)
                    'sanitize_callback' => 'absint',
                    'required' => false,
                    'default' => 1,
                    'validate_callback' => function ($param) {
                        $general_settings = get_option('schesab_general_settings', []);
                        $enable_group_booking = isset($general_settings['enableGroupBooking']) ? (bool) $general_settings['enableGroupBooking'] : false;
                        $max_persons_per_booking = isset($general_settings['maxPersonsPerBooking']) ? absint($general_settings['maxPersonsPerBooking']) : 1;

                        if (!$enable_group_booking && (int) $param > 1) {
                            return new \WP_Error('rest_invalid_param', __('Group booking is not enabled.', 'schedula-smart-appointment-booking'), ['status' => 400]);
                        }
                        if ((int) $param < 1 || (int) $param > $max_persons_per_booking) {
                            // translators: %d is the maximum number of persons allowed per booking
                            return new \WP_Error('rest_invalid_param', sprintf(__('Number of persons must be between 1 and %d.', 'schedula-smart-appointment-booking'), $max_persons_per_booking), ['status' => 400]);
                        }
                        return true;
                    },
                ),
            ),
        ));

        // NEW: Endpoint to cancel an appointment using a token
        register_rest_route($this->namespace, '/appointments/cancel-by-token', array(
            'methods' => 'POST',
            'callback' => array($this, 'cancel_appointment_by_token'),
            'permission_callback' => '__return_true', // Public endpoint
            'args' => array(
                'token' => array(
                    'required' => true,
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ));

        // NEW: Endpoint to check for conflicts in recurring appointments
        register_rest_route($this->namespace, '/appointments/check-recurrence-conflicts', array(
            'methods' => 'POST',
            'callback' => array($this, 'check_recurrence_conflicts'),
            'permission_callback' => '__return_true', // Public
            'args' => array(
                'service_id' => array('required' => true, 'sanitize_callback' => 'absint'),
                'staff_id' => array('required' => true, 'sanitize_callback' => 'absint'),
                'appointment_date' => array('required' => true, 'sanitize_callback' => 'sanitize_text_field'),
                'appointment_time' => array('required' => true, 'sanitize_callback' => 'sanitize_text_field'),
                'recurrence_frequency' => array('required' => true, 'sanitize_callback' => 'sanitize_text_field'),
                'recurrence_interval' => array('required' => true, 'sanitize_callback' => 'absint'),
                'recurrence_count' => array('required' => true, 'sanitize_callback' => 'absint'),
            ),
        ));

        // NEW: Endpoint to get customer details by email for form autofill
        register_rest_route($this->namespace, '/customer-by-email', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_customer_by_email'),
            'permission_callback' => '__return_true', // Publicly accessible for the form
            'args' => array(
                'email' => array(
                    'sanitize_callback' => 'sanitize_email',
                    'validate_callback' => function ($param) {
                        return is_email($param);
                    },
                    'required' => true,
                ),
            ),
        ));
    }

    public function get_customer_by_email(WP_REST_Request $request)
    {
        global $wpdb;
        $customers_table = $this->db->get_table_name('customers');
        $email = $request->get_param('email');

        $customer = $wpdb->get_row($wpdb->prepare(
            "SELECT first_name, last_name, phone FROM {$customers_table} WHERE email = %s",
            $email
        ), ARRAY_A);

        if ($customer) {
            return new WP_REST_Response($customer, 200);
        } else {
            return new WP_REST_Response(['message' => 'Customer not found.'], 404);
        }
    }


    /**
     * Check if the current user has admin capabilities.
     */
    public function check_admin_permissions(WP_REST_Request $request)
    {
        return current_user_can('manage_options'); // Or a more specific capability
    }

    /**
     * Get arguments for appointment creation/update.
     */
    private function get_appointment_args()
    {
        // Fetch general settings to determine validation rules for number_of_persons
        $general_settings = $this->settings_api->get_default_settings('general'); // Always get defaults first
        $saved_general_settings = get_option('schesab_general_settings', []);
        $general_settings = array_merge($general_settings, $saved_general_settings); // Merge saved over defaults

        $enable_group_booking = isset($general_settings['enableGroupBooking']) ? (bool) $general_settings['enableGroupBooking'] : false;
        $max_persons_per_booking = isset($general_settings['maxPersonsPerBooking']) ? absint($general_settings['maxPersonsPerBooking']) : 1;

        return array(
            'service_id' => array('sanitize_callback' => 'absint', 'required' => true),
            'staff_id' => array('sanitize_callback' => 'absint', 'required' => false),
            'customer_id' => array('sanitize_callback' => 'absint', 'required' => false), // Allow passing existing customer ID
            'customer_first_name' => array('sanitize_callback' => 'sanitize_text_field', 'required' => true), // For new customer creation
            'customer_last_name' => array('sanitize_callback' => 'sanitize_text_field', 'required' => true),  // For new customer creation
            'customer_email' => array('sanitize_callback' => 'sanitize_email', 'required' => true),
            'customer_phone' => array('sanitize_callback' => 'sanitize_text_field', 'required' => false),
            'appointment_date' => array(
                'sanitize_callback' => 'sanitize_text_field',
                'required' => true,
                'validate_callback' => function ($param) {
                    return (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $param);
                }
            ), // Date part from form
            'appointment_time' => array(
                'sanitize_callback' => 'sanitize_text_field',
                'required' => true,
                'validate_callback' => function ($param) {
                    return (bool) preg_match('/^\d{2}:\d{2}$/', $param);
                }
            ), // Time part from form
            'status' => array('sanitize_callback' => 'sanitize_text_field', 'required' => false, 'default' => 'pending'),
            'notes' => array('sanitize_callback' => 'sanitize_textarea_field', 'required' => false),
            'number_of_persons' => array( // NEW: Group booking argument with validation
                'sanitize_callback' => 'absint',
                'required' => false,
                'default' => 1,
                'validate_callback' => function ($param) use ($enable_group_booking, $max_persons_per_booking) {
                    $persons_count = absint($param);
                    if ($persons_count < 1) {
                        return new \WP_Error('rest_invalid_param', __('Number of persons must be at least 1.', 'schedula-smart-appointment-booking'), ['status' => 400]);
                    }
                    if (!$enable_group_booking && $persons_count > 1) {
                        return new \WP_Error('rest_invalid_param', __('Group booking is not enabled, so only one person can be booked.', 'schedula-smart-appointment-booking'), ['status' => 400]);
                    }
                    if ($enable_group_booking && $persons_count > $max_persons_per_booking) {
                        // translators: %d is the maximum number of persons allowed per booking
                        return new \WP_Error('rest_invalid_param', sprintf(__('Number of persons cannot exceed the maximum of %d for group bookings.', 'schedula-smart-appointment-booking'), $max_persons_per_booking), ['status' => 400]);
                    }
                    return true;
                },
            ),
            'payment_method' => array('sanitize_callback' => 'sanitize_text_field', 'required' => false, 'default' => 'cash'), // Added payment_method

            // RECURRENCE ARGUMENTS
            'is_recurring' => array(
                'description' => __('Flag to indicate if the appointment is recurring.', 'schedula-smart-appointment-booking'),
                'type' => 'boolean',
                'sanitize_callback' => 'rest_sanitize_boolean',
                'default' => false,
                'required' => false,
            ),
            'recurrence_frequency' => array(
                'description' => __('Frequency of the recurrence.', 'schedula-smart-appointment-booking'),
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => function ($param, $request, $key) {
                    $is_recurring = $request->get_param('is_recurring');
                    if ($is_recurring) {
                        return in_array($param, ['daily', 'weekly', 'monthly', 'yearly']);
                    }
                    return true; // Not required if not recurring
                },
                'required' => false,
            ),
            'recurrence_interval' => array(
                'description' => __('Interval of the recurrence.', 'schedula-smart-appointment-booking'),
                'type' => 'integer',
                'sanitize_callback' => 'absint',
                'validate_callback' => function ($param, $request, $key) {
                    $is_recurring = $request->get_param('is_recurring');
                    if ($is_recurring) {
                        return is_numeric($param) && $param >= 1;
                    }
                    return true; // Not required if not recurring
                },
                'default' => 1,
                'required' => false,
            ),
            'recurrence_end_date' => array(
                'description' => __('End date of the recurrence.', 'schedula-smart-appointment-booking'),
                'type' => 'string',
                'format' => 'date',
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => function ($param, $request, $key) {
                    $is_recurring = $request->get_param('is_recurring');
                    if ($is_recurring) {
                        // Either empty or matches date format
                        return empty($param) || (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $param);
                    }
                    return true; // Not required if not recurring
                },
                'required' => false,
            ),
            'recurrence_count' => array(
                'description' => __('Number of times the appointment should recur.', 'schedula-smart-appointment-booking'),
                'type' => 'integer',
                'sanitize_callback' => 'absint',
                'validate_callback' => function ($param, $request, $key) {
                    $is_recurring = $request->get_param('is_recurring');
                    if ($is_recurring) {
                        // Must be at least 1 if recurring
                        return is_numeric($param) && $param >= 1;
                    }
                    return true; // Not required if not recurring
                },
                'required' => false,
            ),
        );
    }

    /**
     * Helper function to check staff availability.
     *
     * @param int    $staff_id The ID of the staff member.
     * @param string $start_datetime The proposed start datetime (Y-m-d H:i:s).
     * @param string $end_datetime The proposed end datetime (Y-m-d H:i:s).
     * @param int    $exclude_appointment_id Optional. An appointment ID to exclude from conflict checks (for updates).
     * @return array An associative array with 'available' (bool) and 'message' (string).
     */
    private function is_staff_available($staff_id, $start_datetime, $end_datetime, $exclude_appointment_id = 0)
    {
        global $wpdb;

        $staff_schedule_table = $this->db->get_table_name('staff_schedule');
        $schedule_item_breaks_table = $this->db->get_table_name('schedule_item_breaks');
        $holidays_table = $this->db->get_table_name('holidays');
        $appointments_table = $this->db->get_table_name('appointments');

        // --- Fetch buffer setting ---
        $general_settings = $this->settings_api->get_default_settings('general');
        $saved_general_settings = get_option('schesab_general_settings', []);
        $general_settings = array_merge($general_settings, $saved_general_settings);
        $buffer_minutes = isset($general_settings['bookingBufferTime']) ? absint($general_settings['bookingBufferTime']) : 0;

        // Get business timezone and create DateTime objects
        $business_timezone = $this->get_business_timezone();
        $business_tz = new \DateTimeZone($business_timezone);

        $start_dt_business = new \DateTime($start_datetime, $business_tz);
        $end_dt_business = new \DateTime($end_datetime, $business_tz);

        $appointment_date = $start_dt_business->format('Y-m-d');
        $appointment_time = $start_dt_business->format('H:i:s');
        $day_of_week = $start_dt_business->format('w'); // 0 for Sunday, 6 for Saturday

        // 1. Check Staff Working Hours (Schedule)
        $schedule_item = $wpdb->get_row($wpdb->prepare(
            "SELECT id, start_time, end_time FROM {$staff_schedule_table} WHERE staff_id = %d AND day_of_week = %d",
            $staff_id,
            $day_of_week
        ), ARRAY_A);

        if (empty($schedule_item)) {
            return ['available' => false, 'message' => __('Staff member is not scheduled to work on this day.', 'schedula-smart-appointment-booking')];
        }

        // Create schedule times in business timezone
        $schedule_start_dt = new \DateTime($appointment_date . ' ' . $schedule_item['start_time'], $business_tz);
        $schedule_end_dt = new \DateTime($appointment_date . ' ' . $schedule_item['end_time'], $business_tz);

        // If schedule end time is before start time (e.g., overnight shift), adjust end_time for comparison
        if ($schedule_item['end_time'] < $schedule_item['start_time']) {
            $schedule_end_dt->modify('+1 day');
            if ($start_dt_business < $schedule_start_dt && $start_dt_business < new \DateTime($appointment_date, $business_tz)) {
                return ['available' => false, 'message' => __('Appointment starts before staff\'s scheduled work time.', 'schedula-smart-appointment-booking')];
            }
        }

        // Reject if starts before schedule start, or ends after schedule end
        if ($start_dt_business < $schedule_start_dt || $end_dt_business > $schedule_end_dt) {
            return [
                'available' => false,
                'message' => sprintf(
                    // translators: %1$s is start time, %2$s is end time, %3$s is the day of the week
                    __('Appointment is outside staff working hours (%1$s - %2$s) for %3$s.', 'schedula-smart-appointment-booking'),
                    $schedule_start_dt->format('H:i'),
                    $schedule_end_dt->format('H:i'),
                    $start_dt_business->format('l')
                ),
            ];
        }

        // 2. Check Staff Breaks
        $breaks = $wpdb->get_results($wpdb->prepare(
            "SELECT start_time, end_time FROM {$schedule_item_breaks_table} WHERE schedule_item_id = %d",
            $schedule_item['id']
        ), ARRAY_A);

        foreach ($breaks as $break) {
            $break_start_dt = new \DateTime($appointment_date . ' ' . $break['start_time'], $business_tz);
            $break_end_dt = new \DateTime($appointment_date . ' ' . $break['end_time'], $business_tz);

            // Check for overlap: (StartA < EndB) and (EndA > StartB)
            if ($start_dt_business < $break_end_dt && $end_dt_business > $break_start_dt) {
                return [
                    'available' => false,
                    'message' => sprintf(
                        /* translators: %1$s is start time, %2$s is end time */
                        __('Appointment overlaps with staff\'s break (%1$s - %2$s).', 'schedula-smart-appointment-booking'),
                        $break_start_dt->format('H:i'),
                        $break_end_dt->format('H:i')
                    ),
                ];
            }
        }

        // 3. Check Staff Holidays
        $overlapping_holidays = $wpdb->get_results($wpdb->prepare(
            "SELECT start_date, end_date FROM {$holidays_table}
             WHERE staff_id = %d
             AND (
                 (start_date <= %s AND end_date >= %s)
             )",
            $staff_id,
            $appointment_date,
            $appointment_date
        ), ARRAY_A);

        foreach ($overlapping_holidays as $holiday) {
            $holiday_start = new \DateTime($holiday['start_date'], $business_tz);
            $holiday_end = new \DateTime($holiday['end_date'], $business_tz);
            $appointment_date_dt = new \DateTime($appointment_date, $business_tz);

            // Check if the appointment date is within the holiday range (inclusive)
            if ($appointment_date_dt >= $holiday_start && $appointment_date_dt <= $holiday_end) {
                // translators: %1$s is start date, %2$s is end date
                return ['available' => false, 'message' => sprintf(__('Staff member is on holiday from %1$s to %2$s.', 'schedula-smart-appointment-booking'), $holiday['start_date'], $holiday['end_date'])];
            }
        }

        // 4. Check for existing appointments (double-booking) with buffer
        // Use business timezone datetime strings for database comparison
        $start_datetime_db = $this->store_business_datetime($start_datetime);
        $end_datetime_db = $this->store_business_datetime($end_datetime);

        $conflicting_appointment = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$appointments_table}
             WHERE staff_id = %d
             AND id != %d -- Exclude current appointment if updating
             AND status NOT IN ('cancelled', 'incomplete')
             AND (
                -- New appointment [S, E] conflicts with existing [S', E'] if S < (E' + buffer) AND E > S'
                %s < DATE_ADD(end_datetime, INTERVAL %d MINUTE) 
                AND 
                %s > start_datetime
             )
            ",
            $staff_id,
            $exclude_appointment_id,
            $start_datetime_db,
            $buffer_minutes,
            $end_datetime_db
        ));

        if ($conflicting_appointment) {
            return ['available' => false, 'message' => __('Staff member has a conflicting appointment or buffer time.', 'schedula-smart-appointment-booking')];
        }

        return ['available' => true, 'message' => __('Staff is available.', 'schedula-smart-appointment-booking')];
    }

    /**
     * Endpoint to check staff availability.
     */
    public function check_staff_availability_endpoint(WP_REST_Request $request)
    {
        $staff_id = $request->get_param('staff_id');
        $appointment_date = $request->get_param('appointment_date');
        $appointment_time = $request->get_param('appointment_time');
        $duration = $request->get_param('duration');
        $exclude_appointment_id = $request->get_param('exclude_appointment_id');
        // $persons = $request->get_param('persons'); // This is validated at the arg level if group booking is enabled

        // Combine date and time to create full datetime strings
        $start_datetime_str = $appointment_date . ' ' . $appointment_time . ':00'; // Assuming HH:MM format from frontend
        $start_timestamp = strtotime($start_datetime_str);
        $end_timestamp = $start_timestamp + ($duration * 60);
        $end_datetime_str = gmdate('Y-m-d H:i:s', $end_timestamp);

        $availability = $this->is_staff_available(
            $staff_id,
            $start_datetime_str,
            $end_datetime_str,
            $exclude_appointment_id
        );

        return new WP_REST_Response($availability, 200);
    }

    /**
     * NEW: Endpoint to get available time slots for a given service, date, and optional staff.
     * This method will generate time slots based on staff schedules and then filter out
     * unavailable slots due to breaks, holidays, or existing appointments.
     */
    public function get_available_time_slots_endpoint(WP_REST_Request $request)
    {
        global $wpdb;

        $service_id = (int) $request->get_param('service_id');
        $requested_date_str = sanitize_text_field($request->get_param('date'));
        $staff_id_filter = (int) $request->get_param('staff_id'); // 0 for 'Any Staff'
        // $persons_filter = (int)$request->get_param('persons'); // This is validated at the arg level

        if (!$service_id || !$requested_date_str) {
            return new WP_REST_Response(['message' => __('Service ID and date are required.', 'schedula-smart-appointment-booking')], 400);
        }

        $services_table = $this->db->get_table_name('services');
        $staff_table = $this->db->get_table_name('staff');
        $staff_services_table = $this->db->get_table_name('staff_services');
        $staff_schedule_table = $this->db->get_table_name('staff_schedule');
        $schedule_item_breaks_table = $this->db->get_table_name('schedule_item_breaks');
        $holidays_table = $this->db->get_table_name('holidays');
        $appointments_table = $this->db->get_table_name('appointments');

        // --- Fetch timeSlotLength and minimumBookingBuffer from general settings ---
        // Use the settings_api instance to get the default settings
        $general_settings = $this->settings_api->get_default_settings('general'); // Get default to ensure all keys are present
        $saved_general_settings = get_option('schesab_general_settings', []); // Get saved settings
        $general_settings = array_merge($general_settings, $saved_general_settings); // Merge them

        $time_slot_length = isset($general_settings['timeSlotLength']) ? absint($general_settings['timeSlotLength']) : 30; // Default to 30 minutes
        if ($time_slot_length <= 0) {
            $time_slot_length = 30; // Ensure a positive interval
        }

        $minimum_booking_buffer_minutes = isset($general_settings['minTimeBooking']) ? absint($general_settings['minTimeBooking']) : 0; // Corrected to use minTimeBooking
        $minimum_booking_buffer_seconds = $minimum_booking_buffer_minutes * 60;
        // --- End fetch settings ---

        // Get service duration
        $service = $wpdb->get_row($wpdb->prepare("SELECT duration FROM {$services_table} WHERE id = %d", $service_id), ARRAY_A);
        if (!$service) {
            return new WP_REST_Response(['message' => __('Service not found.', 'schedula-smart-appointment-booking')], 404);
        }
        $service_duration = (int) $service['duration'];

        $available_slots = [];
        $requested_date_timestamp = strtotime($requested_date_str);
        $day_of_week = gmdate('w', $requested_date_timestamp); // 0 (Sun) to 6 (Sat)

        // Determine which staff members to consider
        $staff_members_to_check = [];
        if ($staff_id_filter > 0) {
            // If a specific staff is selected, only check that staff
            $staff_members_to_check = $wpdb->get_results($wpdb->prepare("SELECT id, name FROM {$staff_table} WHERE id = %d AND status = 'active'", $staff_id_filter), ARRAY_A);
        } else {
            // If 'Any Employee' is selected, get all active staff who offer this service
            $staff_members_to_check = $wpdb->get_results($wpdb->prepare("
                SELECT s.id, s.name
                FROM {$staff_table} s
                JOIN {$staff_services_table} ss ON s.id = ss.staff_id
                WHERE ss.service_id = %d AND s.status = 'active'
                ORDER BY s.name ASC
            ", $service_id), ARRAY_A);
        }

        if (empty($staff_members_to_check)) {
            return new WP_REST_Response([], 200); // No staff available for this service/filter
        }

        // Collect all potential available slots across all relevant staff
        $all_potential_slots = [];

        $now_timestamp = current_time('timestamp', 0); // Local time timestamp
        $earliest_bookable_timestamp = $now_timestamp + $minimum_booking_buffer_seconds;


        foreach ($staff_members_to_check as $staff) {
            $staff_id = (int) $staff['id'];

            // 1. Get staff's schedule for the requested day
            $schedule_item = $wpdb->get_row($wpdb->prepare(
                "SELECT id, start_time, end_time FROM {$staff_schedule_table} WHERE staff_id = %d AND day_of_week = %d",
                $staff_id,
                $day_of_week
            ), ARRAY_A);

            if (empty($schedule_item)) {
                continue; // Staff not scheduled to work on this day
            }

            $schedule_start_time_str = $schedule_item['start_time'];
            $schedule_end_time_str = $schedule_item['end_time'];
            $schedule_item_id = $schedule_item['id'];

            // Generate time slots based on staff's schedule and service duration
            $current_slot_start_timestamp = strtotime($requested_date_str . ' ' . $schedule_start_time_str);
            $schedule_end_timestamp = strtotime($requested_date_str . ' ' . $schedule_end_time_str);

            // Handle overnight shifts for schedule end time
            if ($schedule_end_time_str < $schedule_start_time_str) {
                $schedule_end_timestamp = strtotime($requested_date_str . ' ' . $schedule_end_time_str . ' +1 day');
            }

            // Adjust starting point to be no earlier than earliest_bookable_timestamp
            if ($current_slot_start_timestamp < $earliest_bookable_timestamp) {
                $current_slot_start_timestamp = $earliest_bookable_timestamp;
                // Round up to the next 'time_slot_length' interval from this new starting point
                $minutes_since_midnight = (int) gmdate('H', $current_slot_start_timestamp) * 60 + (int) gmdate('i', $current_slot_start_timestamp);
                $remainder = $minutes_since_midnight % $time_slot_length;
                if ($remainder > 0) {
                    $minutes_to_add = $time_slot_length - $remainder;
                    $current_slot_start_timestamp += $minutes_to_add * 60;
                }
            }


            while (($current_slot_start_timestamp + ($service_duration * 60)) <= $schedule_end_timestamp) {
                $slot_start_datetime_str = gmdate('Y-m-d H:i:s', $current_slot_start_timestamp);
                $slot_end_datetime_str = gmdate('Y-m-d H:i:s', $current_slot_start_timestamp + ($service_duration * 60));

                // Check if this specific slot is available for this staff member
                $availability = $this->is_staff_available(
                    $staff_id,
                    $slot_start_datetime_str,
                    $slot_end_datetime_str,
                    0 // No appointment to exclude for new bookings
                );

                if ($availability['available']) {
                    // Store the time slot and the staff member who can provide it
                    $all_potential_slots[gmdate('H:i', $current_slot_start_timestamp)][] = $staff_id;
                }

                // Move to next interval based on timeSlotLength
                $current_slot_start_timestamp += ($time_slot_length * 60);
            }
        }

        // Filter for unique time slots where at least one staff member is available
        $final_available_slots = [];
        foreach ($all_potential_slots as $time_slot => $staff_ids) {
            if (!empty($staff_ids)) {
                $final_available_slots[] = $time_slot;
            }
        }

        sort($final_available_slots); // Sort chronologically

        return new WP_REST_Response($final_available_slots, 200);
    }


    /**
     * Get chart data for the dashboard.
     */
    private function get_chart_data($start_date, $end_date)
    {
        global $wpdb;
        $appointments_table = $this->db->get_table_name('appointments');
        $payments_table = $this->db->get_table_name('payments');

        // Ensure start_date and end_date are valid. If not provided, use current date.
        $start_date = !empty($start_date) ? $start_date : gmdate('Y-m-d');
        $end_date = !empty($end_date) ? $end_date : gmdate('Y-m-d');

        $start = new \DateTime($start_date);
        $end = new \DateTime($end_date);
        $diff = $end->diff($start)->days;

        $labels = [];
        $appointments = [];
        $earnings = [];

        if ($diff > 31) {
            // Group by month
            $start->modify('first day of this month');
            $end->modify('last day of this month');
            $interval = new \DateInterval('P1M');
            $date_range = new \DatePeriod($start, $interval, $end);

            foreach ($date_range as $date) {
                $month_start = $date->format('Y-m-01');
                $month_end = $date->format('Y-m-t');
                $labels[] = $date->format('F Y');

                $appointments[] = (int) $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$appointments_table} WHERE DATE(start_datetime) BETWEEN %s AND %s AND status != 'cancelled'",
                    $month_start,
                    $month_end
                ));

                $monthly_earnings = $wpdb->get_var($wpdb->prepare(
                    "SELECT SUM(p.amount) FROM {$payments_table} AS p
                     INNER JOIN {$appointments_table} AS a ON p.appointment_id = a.id
                     WHERE DATE(a.start_datetime) BETWEEN %s AND %s AND p.status = 'completed'",
                    $month_start,
                    $month_end
                ));
                $earnings[] = $monthly_earnings ? (float) $monthly_earnings : 0;
            }
        } else {
            // Group by day
            $end->modify('+1 day');
            $interval = new \DateInterval('P1D');
            $date_range = new \DatePeriod($start, $interval, $end);

            foreach ($date_range as $date) {
                $current_date = $date->format('Y-m-d');
                $labels[] = $date->format('M d');

                $appointments[] = (int) $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$appointments_table} WHERE DATE(start_datetime) = %s AND status != 'cancelled'",
                    $current_date
                ));

                $daily_earnings = $wpdb->get_var($wpdb->prepare(
                    "SELECT SUM(p.amount) FROM {$payments_table} AS p
                     INNER JOIN {$appointments_table} AS a ON p.appointment_id = a.id
                     WHERE DATE(a.start_datetime) = %s AND p.status = 'completed'",
                    $current_date
                ));
                $earnings[] = $daily_earnings ? (float) $daily_earnings : 0;
            }
        }

        return [
            'labels' => $labels,
            'appointments' => $appointments,
            'earnings' => $earnings,
        ];
    }

    /**
     * Get dashboard summary data.
     * Includes filtering by start_date and end_date.
     */
    public function get_dashboard_summary(WP_REST_Request $request)
    {
        global $wpdb;
        $appointments_table = $this->db->get_table_name('appointments');
        $payments_table = $this->db->get_table_name('payments');

        $start_date_param = $request->get_param('start_date');
        $end_date_param = $request->get_param('end_date');

        $date_filter_params = [];
        $has_date_filter = !empty($start_date_param) && !empty($end_date_param);

        if ($has_date_filter) {
            $date_filter_params = [$start_date_param, $end_date_param];
        }

        // Accepted/Completed Appointments
        $q1 = "SELECT COUNT(*) FROM {$appointments_table} AS a WHERE (a.status = 'confirmed' OR a.status = 'completed')";
        if ($has_date_filter) {
            $q1 .= " AND DATE(a.start_datetime) BETWEEN %s AND %s";
        }
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        $accepted_completed_appointments = $wpdb->get_var($wpdb->prepare($q1, ...$date_filter_params));

        // Pending Appointments
        $q2 = "SELECT COUNT(*) FROM {$appointments_table} AS a WHERE a.status = 'pending'";
        if ($has_date_filter) {
            $q2 .= " AND DATE(a.start_datetime) BETWEEN %s AND %s";
        }
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        $pending_appointments = $wpdb->get_var($wpdb->prepare($q2, ...$date_filter_params));

        // Total Appointments
        $q3 = "SELECT COUNT(*) FROM {$appointments_table} AS a WHERE a.status != 'cancelled'";
        if ($has_date_filter) {
            $q3 .= " AND DATE(a.start_datetime) BETWEEN %s AND %s";
        }
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        $total_appointments = $wpdb->get_var($wpdb->prepare($q3, ...$date_filter_params));

        // Total Earnings
        $q4 = "SELECT SUM(p.amount) FROM {$payments_table} AS p INNER JOIN {$appointments_table} AS a ON p.appointment_id = a.id WHERE p.status = 'completed'";
        if ($has_date_filter) {
            $q4 .= " AND DATE(a.start_datetime) BETWEEN %s AND %s";
        }
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        $total_earnings = $wpdb->get_var($wpdb->prepare($q4, ...$date_filter_params));
        $total_earnings = $total_earnings ? (float) $total_earnings : 0.00;

        // Chart Data
        $chart_data = $this->get_chart_data($start_date_param, $end_date_param);

        return new WP_REST_Response(array(
            'accepted_completed_appointments' => (int) $accepted_completed_appointments,
            'pending_appointments' => (int) $pending_appointments,
            'total_appointments' => (int) $total_appointments,
            'total_earnings' => $total_earnings,
            'chart_data' => $chart_data,
        ), 200);
    }

    /**
     * NEW: Get the next upcoming appointment.
     */
    public function get_next_upcoming_appointment(WP_REST_Request $request)
    {
        global $wpdb;
        $appointments_table = $this->db->get_table_name('appointments');
        $services_table = $this->db->get_table_name('services');
        $staff_table = $this->db->get_table_name('staff');
        $customers_table = $this->db->get_table_name('customers');
        $customer_appointments_table = $this->db->get_table_name('customer_appointments');

        $now = current_time('mysql', 1); // Get current time in GMT (database time)

        $next_appointment = $wpdb->get_row($wpdb->prepare("
            SELECT
                a.id,
                a.start_datetime,
                a.customer_name,
                s.title AS service_title,
                st.name AS staff_name,
                cust.first_name AS customer_first_name,
                cust.last_name AS customer_last_name
            FROM {$appointments_table} AS a
            LEFT JOIN {$services_table} AS s ON a.service_id = s.id
            LEFT JOIN {$staff_table} AS st ON a.staff_id = st.id
            LEFT JOIN {$customer_appointments_table} AS ca ON a.id = ca.appointment_id
            LEFT JOIN {$customers_table} AS cust ON ca.customer_id = cust.id
            WHERE a.start_datetime >= %s AND a.status IN ('pending', 'confirmed') -- Only future, non-cancelled appointments
            ORDER BY a.start_datetime ASC
            LIMIT 1
        ", $now), ARRAY_A);

        if ($next_appointment) {
            // Combine first and last name for convenience
            if (!empty($next_appointment['customer_name'])) {
                $next_appointment['customer_full_name'] = trim($next_appointment['customer_name']);
            } else {
                $next_appointment['customer_full_name'] = trim($next_appointment['customer_first_name'] . ' ' . $next_appointment['customer_last_name']);
            }
            return new WP_REST_Response($next_appointment, 200);
        } else {
            return new WP_REST_Response(null, 200); // No upcoming appointment found
        }
    }

    /**
     * NEW: Get appointment load analysis (top 3 busiest days with busiest hour).
     */
    public function get_appointment_load_analysis(WP_REST_Request $request)
    {
        global $wpdb;
        $appointments_table = $this->db->get_table_name('appointments');

        $today = current_time('mysql', 0); // Local time
        $future_date_limit = gmdate('Y-m-d H:i:s', strtotime('+30 days', strtotime($today))); // Look 30 days ahead

        // Step 1: Get the top 3 busiest days by total appointment count
        $busiest_days_raw = $wpdb->get_results($wpdb->prepare("
            SELECT
                DATE(start_datetime) AS appointment_date,
                COUNT(id) AS total_count
            FROM {$appointments_table}
            WHERE start_datetime >= %s AND start_datetime <= %s AND status IN ('pending', 'confirmed', 'completed')
            GROUP BY appointment_date
            ORDER BY total_count DESC, appointment_date ASC
            LIMIT 3
        ", $today, $future_date_limit), ARRAY_A);

        $busiest_days_with_times = [];

        // Step 2: For each of the top busiest days, find the busiest hour
        foreach ($busiest_days_raw as $day_data) {
            $current_date = $day_data['appointment_date'];
            $total_count = (int) $day_data['total_count'];

            // Find the busiest hour for this specific date
            $busiest_hour_data = $wpdb->get_row($wpdb->prepare("
                SELECT
                    HOUR(start_datetime) AS busiest_hour,
                    COUNT(id) AS hourly_count
                FROM {$appointments_table}
                WHERE DATE(start_datetime) = %s AND status IN ('pending', 'confirmed', 'completed')
                GROUP BY busiest_hour
                ORDER BY hourly_count DESC, busiest_hour ASC
                LIMIT 1
            ", $current_date), ARRAY_A);

            $busiest_time_slot = null;
            if ($busiest_hour_data) {
                // Format hour to HH:00 (e.g., 9 -> 09:00, 14 -> 14:00)
                $busiest_time_slot = sprintf('%02d:00', (int) $busiest_hour_data['busiest_hour']);
            }

            $busiest_days_with_times[] = [
                'date' => $current_date,
                'total_count' => $total_count,
                'busiest_time_slot' => $busiest_time_slot,
            ];
        }

        return new WP_REST_Response([
            'busiest_days_with_times' => $busiest_days_with_times, // Array of objects with date, count, and busiest_time_slot
            // 'low_load_suggestions' => [ // This would be static for now, or require advanced logic
            //     ['day' => 'Wednesday', 'time' => '10h'],
            //     ['day' => 'Thursday', 'time' => '14h'],
            //     ['day' => 'Friday', 'time' => '9h'],
            // ]
        ], 200);
    }


    /**
     * Get all appointments with filters and pagination.
     */
    public function get_appointments(WP_REST_Request $request)
    {
        global $wpdb;
        $appointments_table = $this->db->get_table_name('appointments');
        $services_table = $this->db->get_table_name('services');
        $staff_table = $this->db->get_table_name('staff');
        $customers_table = $this->db->get_table_name('customers');
        $customer_appointments_table = $this->db->get_table_name('customer_appointments');

        $start_date_filter = $request->get_param('start_date');
        $end_date_filter = $request->get_param('end_date');
        $status = $request->get_param('status');
        $customer_name_search = $request->get_param('customer_name');
        $staff_name_search = $request->get_param('staff_name');

        // Pagination parameters
        $page = $request->get_param('page');
        $per_page = $request->get_param('per_page');
        $offset = ($page - 1) * $per_page;

        // Sorting parameters
        $sort_by = $request->get_param('sort_by');
        $sort_direction = strtoupper($request->get_param('sort_direction')); // Ensure uppercase for SQL

        // Validate sort_by column to prevent SQL injection
        $allowed_sort_columns = [
            'id' => 'a.id',
            'start_datetime' => 'a.start_datetime',
            'customer_name' => 'a.customer_name',
            'staff_name' => 'st.name',
            'service_title' => 's.title',
            'price' => 'a.price',
            'duration' => 'a.duration',
            'status' => 'a.status',
            'number_of_persons' => 'ca.number_of_persons',
        ];

        // Default to 'start_datetime' if an invalid column is provided
        $sort_column_sql = $allowed_sort_columns[$sort_by] ?? $allowed_sort_columns['start_datetime'];
        // Default direction
        $sort_direction_sql = in_array($sort_direction, ['ASC', 'DESC']) ? $sort_direction : 'DESC';


        // Base query for appointments
        $query = "SELECT
                    a.id,
                    a.service_id,
                    a.staff_id,
                    a.start_datetime,
                    a.end_datetime,
                    a.duration,
                    a.price,
                    a.status,
                    a.notes,
                    a.customer_name,
                    s.title AS service_title,
                    st.name AS staff_name,
                    cust.first_name AS customer_first_name,
                    cust.last_name AS customer_last_name,
                    cust.email AS customer_email,
                    cust.phone AS customer_phone,
                    ca.number_of_persons,
                    ser.frequency AS recurrence_frequency,
                    ser.interval AS recurrence_interval
                  FROM {$appointments_table} AS a
                  LEFT JOIN {$services_table} AS s ON a.service_id = s.id
                  LEFT JOIN {$staff_table} AS st ON a.staff_id = st.id
                  LEFT JOIN {$customer_appointments_table} AS ca ON a.id = ca.appointment_id
                  LEFT JOIN {$customers_table} AS cust ON ca.customer_id = cust.id
                  LEFT JOIN {$this->db->get_table_name('series')} AS ser ON a.series_id = ser.id
                  WHERE 1=1";

        $params = array();

        if ($start_date_filter) {
            $query .= " AND DATE(a.start_datetime) >= %s";
            $params[] = $start_date_filter;
        }
        if ($end_date_filter) {
            $query .= " AND DATE(a.start_datetime) <= %s";
            $params[] = $end_date_filter;
        }
        if ($status) {
            if ($status === 'accepted') {
                $query .= " AND (a.status = %s OR a.status = %s)";
                $params[] = 'confirmed';
                $params[] = 'completed';
            } else {
                $query .= " AND a.status = %s";
                $params[] = $status;
            }
        } else {
            $query .= " AND a.status != 'incomplete'";
        }

        if ($customer_name_search) {
            $search_like = '%' . $wpdb->esc_like($customer_name_search) . '%';
            $query .= " AND (cust.first_name LIKE %s OR cust.last_name LIKE %s OR cust.email LIKE %s)";
            $params[] = $search_like;
            $params[] = $search_like;
            $params[] = $search_like;
        }

        if ($staff_name_search) {
            $query .= " AND st.name LIKE %s";
            $params[] = '%' . $wpdb->esc_like($staff_name_search) . '%';
        }

        // Query to get total count before LIMIT/OFFSET
        $total_query = "SELECT COUNT(*) FROM ({$query}) AS subquery";
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        $total_appointments = $wpdb->get_var($wpdb->prepare($total_query, ...$params));

        // Add dynamic ORDER BY clause
        $query .= " ORDER BY {$sort_column_sql} {$sort_direction_sql}";
        $query .= " LIMIT %d OFFSET %d"; // Add LIMIT and OFFSET for pagination
        $params[] = $per_page;
        $params[] = $offset;

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        $appointments = $wpdb->get_results($wpdb->prepare($query, ...$params), ARRAY_A);

        // Format customer name and recurrence text for the frontend
        foreach ($appointments as &$appointment) {
            if (!empty($appointment['customer_name'])) {
                $appointment['customer_name'] = trim($appointment['customer_name']);
            } else {
                $appointment['customer_name'] = trim($appointment['customer_first_name'] . ' ' . $appointment['customer_last_name']);
            }
            $appointment['number_of_persons'] = isset($appointment['number_of_persons']) ? (int) $appointment['number_of_persons'] : 1;

            // Format recurrence text
            $appointment['recurrence_text'] = '';
            if (!empty($appointment['recurrence_frequency'])) {
                $interval = (int) $appointment['recurrence_interval'];
                $freq_text = $appointment['recurrence_frequency'];
                $freq_text_singular = rtrim($freq_text, 'ly');
                if ($freq_text_singular === 'dai') { // Fix for 'daily'
                    $freq_text_singular = 'day';
                }


                if ($interval > 1) {
                    switch ($freq_text_singular) {
                        case 'day':
                            // translators: %d is the number of days
                            $appointment['recurrence_text'] = sprintf(__('Every %d days', 'schedula-smart-appointment-booking'), $interval);
                            break;
                        case 'week':
                            // translators: %d is the number of weeks
                            $appointment['recurrence_text'] = sprintf(__('Every %d weeks', 'schedula-smart-appointment-booking'), $interval);
                            break;
                        case 'month':
                            // translators: %d is the number of months
                            $appointment['recurrence_text'] = sprintf(__('Every %d months', 'schedula-smart-appointment-booking'), $interval);
                            break;
                    }
                } else {
                    switch ($freq_text) {
                        case 'daily':
                            $appointment['recurrence_text'] = ucfirst(__('daily', 'schedula-smart-appointment-booking'));
                            break;
                        case 'weekly':
                            $appointment['recurrence_text'] = ucfirst(__('weekly', 'schedula-smart-appointment-booking'));
                            break;
                        case 'monthly':
                            $appointment['recurrence_text'] = ucfirst(__('monthly', 'schedula-smart-appointment-booking'));
                            break;
                    }
                }
            }
        }

        return new WP_REST_Response([
            'appointments' => $appointments,
            'total_items' => (int) $total_appointments,
            'per_page' => (int) $per_page,
            'current_page' => (int) $page,
        ], 200);
    }

    /**
     * Get a single appointment by ID.
     */
    public function get_appointment(WP_REST_Request $request)
    {
        global $wpdb;
        $appointments_table = $this->db->get_table_name('appointments');
        $services_table = $this->db->get_table_name('services');
        $staff_table = $this->db->get_table_name('staff');
        $customers_table = $this->db->get_table_name('customers');
        $customer_appointments_table = $this->db->get_table_name('customer_appointments');

        $id = (int) $request['id'];

        $appointment = $wpdb->get_row($wpdb->prepare("
            SELECT
                a.id,
                a.service_id,
                a.staff_id,
                a.start_datetime, -- Select new datetime column
                a.end_datetime,   -- Select new datetime column
                a.duration,
                a.price,
                a.status,
                a.notes,
                a.cancellation_token,
                a.customer_name,
                s.title AS service_title,
                st.name AS staff_name,
                cust.first_name AS customer_first_name,
                cust.last_name AS customer_last_name,
                cust.email AS customer_email,
                cust.phone AS customer_phone,
                ca.number_of_persons,
                ser.frequency AS recurrence_frequency,
                ser.interval AS recurrence_interval,
                ser.occurrence_count AS recurrence_occurrence_count,
                ser.end_date AS recurrence_end_date
            FROM {$appointments_table} AS a
            LEFT JOIN {$services_table} AS s ON a.service_id = s.id
            LEFT JOIN {$staff_table} AS st ON a.staff_id = st.id
            LEFT JOIN {$customer_appointments_table} AS ca ON a.id = ca.appointment_id
            LEFT JOIN {$customers_table} AS cust ON ca.customer_id = cust.id
            LEFT JOIN {$this->db->get_table_name('series')} AS ser ON a.series_id = ser.id
            WHERE a.id = %d
        ", $id), ARRAY_A);

        if (empty($appointment)) {
            return new WP_REST_Response(array('message' => __('Appointment not found', 'schedula-smart-appointment-booking')), 404);
        }

        if (!empty($appointment['customer_name'])) {
            $appointment['customer_name'] = trim($appointment['customer_name']);
        } else {
            $appointment['customer_name'] = trim($appointment['customer_first_name'] . ' ' . $appointment['customer_last_name']);
        }
        $appointment['number_of_persons'] = isset($appointment['number_of_persons']) ? (int) $appointment['number_of_persons'] : 1;

        return new WP_REST_Response($appointment, 200);
    }

    /**
     * Helper to calculate the adjusted price for group bookings.
     *
     * @param float $base_price The base price of the service.
     * @param int $number_of_persons The number of people booking.
     * @param array $group_booking_logic The group booking price logic settings.
     * @return float The adjusted price.
     */
    private function calculate_group_booking_price($base_price, $number_of_persons, $group_booking_logic)
    {
        $adjusted_price = $base_price;
        $type = $group_booking_logic['type'] ?? 'per_person_multiply';
        $amount = (float) ($group_booking_logic['amount'] ?? 0); // Ensure amount is float

        switch ($type) {
            case 'per_person_multiply':
                $adjusted_price = $base_price * $number_of_persons;
                break;
            case 'fixed_discount_per_person':
                // Discount per person is applied to the base price before multiplying by persons
                $price_after_discount_per_person = $base_price - $amount;
                if ($price_after_discount_per_person < 0)
                    $price_after_discount_per_person = 0; // Price can't be negative
                $adjusted_price = $price_after_discount_per_person * $number_of_persons;
                break;
            case 'percentage_discount_total':
                // Calculate total price first, then apply percentage discount
                $total_initial_price = $base_price * $number_of_persons;
                $discount_amount = ($total_initial_price * $amount) / 100;
                $adjusted_price = $total_initial_price - $discount_amount;
                if ($adjusted_price < 0)
                    $adjusted_price = 0; // Price can't be negative
                break;
            default:
                // Fallback to per_person_multiply if type is unknown or not set
                $adjusted_price = $base_price * $number_of_persons;
                break;
        }

        return round($adjusted_price, 2); // Round to 2 decimal places for currency
    }

    /**
     * Create a new appointment.
     */
    public function create_appointment(WP_REST_Request $request)
    {
        global $wpdb;

        // --- Recurring Appointment Logic ---
        $is_recurring = $request->get_param('is_recurring');
        $general_settings = get_option('schesab_general_settings', []);
        $enable_recurring = isset($general_settings['enableRecurringAppointments']) ? (bool) $general_settings['enableRecurringAppointments'] : false;

        if ($is_recurring && !$enable_recurring) {
            return new \WP_Error('recurring_disabled', __('Recurring appointments are not enabled.', 'schedula-smart-appointment-booking'), ['status' => 400]);
        }

        if ($is_recurring) {
            return $this->create_recurring_appointments($request);
        } else {
            return $this->create_single_appointment($request);
        }
    }


    /**
     * Update an existing appointment.
     */
    /**
     * Update an existing appointment.
     */
    private function get_or_create_customer($customer_id, $first_name, $last_name, $email, $phone)
    {
        global $wpdb;
        $customers_table = $this->db->get_table_name('customers');

        if ($customer_id) {
            $wpdb->update(
                $customers_table,
                array(
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'email' => $email,
                    'phone' => $phone,
                    'updated_at' => current_time('mysql'),
                ),
                array('id' => $customer_id)
            );
            return $customer_id;
        }

        $existing_customer_by_email = $wpdb->get_row($wpdb->prepare("SELECT id, first_name, last_name FROM {$customers_table} WHERE email = %s", $email), ARRAY_A);
        if ($existing_customer_by_email) {
            $existing_name = trim($existing_customer_by_email['first_name'] . ' ' . $existing_customer_by_email['last_name']);
            $provided_name = trim($first_name . ' ' . $last_name);

            if (strcasecmp($existing_name, $provided_name) !== 0) {
                return $existing_customer_by_email['id'];
            }

            $customer_id = $existing_customer_by_email['id'];
            $wpdb->update(
                $customers_table,
                array(
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'phone' => $phone,
                    'updated_at' => current_time('mysql'),
                ),
                array('id' => $customer_id)
            );
            return $customer_id;
        }

        $wpdb->insert($customers_table, array(
            'first_name' => $first_name,
            'last_name' => $last_name,
            'email' => $email,
            'phone' => $phone,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ));
        return $wpdb->insert_id;
    }

    public function create_appointment_from_payment($form_data, $payment_info)
    {
        global $wpdb;
        $appointments_table = $this->db->get_table_name('appointments');
        $customers_table = $this->db->get_table_name('customers');
        $customer_appointments_table = $this->db->get_table_name('customer_appointments');
        $services_table = $this->db->get_table_name('services');
        $payments_table = $this->db->get_table_name('payments');

        // Extract data from form_data
        $service_id = isset($form_data['service_id']) ? absint($form_data['service_id']) : 0;
        $staff_id = isset($form_data['staff_id']) ? absint($form_data['staff_id']) : 0;
        $customer_id = isset($form_data['customer_id']) ? absint($form_data['customer_id']) : null;
        $customer_first_name = isset($form_data['customer_first_name']) ? sanitize_text_field($form_data['customer_first_name']) : '';
        $customer_last_name = isset($form_data['customer_last_name']) ? sanitize_text_field($form_data['customer_last_name']) : '';
        $customer_email = isset($form_data['customer_email']) ? sanitize_email($form_data['customer_email']) : '';
        $customer_phone = isset($form_data['customer_phone']) ? sanitize_text_field($form_data['customer_phone']) : '';
        $appointment_date = isset($form_data['appointment_date']) ? sanitize_text_field($form_data['appointment_date']) : '';
        $appointment_time = isset($form_data['appointment_time']) ? sanitize_text_field($form_data['appointment_time']) : '';
        $notes = isset($form_data['notes']) ? sanitize_textarea_field($form_data['notes']) : '';
        $number_of_persons = isset($form_data['number_of_persons']) ? absint($form_data['number_of_persons']) : 1;

        $payment_method = isset($payment_info['method']) ? $payment_info['method'] : 'unknown';
        $transaction_id = isset($payment_info['transaction_id']) ? $payment_info['transaction_id'] : null;
        $payment_amount = isset($payment_info['amount']) ? (float) $payment_info['amount'] : 0.0;

        // Server-side validation
        if (empty($service_id) || empty($customer_email) || empty($appointment_date) || empty($appointment_time)) {
            return new \WP_Error('invalid_data', 'Required appointment data is missing.', ['status' => 400]);
        }

        // IMPORTANT: Check if payment with this transaction_id already exists (last defense against duplicates)
        if (!empty($transaction_id)) {
            global $wpdb;
            $payments_table = $this->db->get_table_name('payments');
            $existing_payment_appointment_id = $wpdb->get_var($wpdb->prepare(
                "SELECT appointment_id FROM {$payments_table} WHERE transaction_id = %s",
                $transaction_id
            ));
            
            if ($existing_payment_appointment_id) {
                // Payment and appointment already exist, return existing appointment ID
                return (int) $existing_payment_appointment_id;
            }
        }

        $general_settings = $this->settings_api->get_default_settings('general');
        $saved_general_settings = get_option('schesab_general_settings', []);
        $general_settings = array_merge($general_settings, $saved_general_settings);

        $enable_group_booking = isset($general_settings['enableGroupBooking']) ? (bool) $general_settings['enableGroupBooking'] : false;
        $max_persons_per_booking = isset($general_settings['maxPersonsPerBooking']) ? absint($general_settings['maxPersonsPerBooking']) : 1;
        $group_booking_price_logic = isset($general_settings['groupBookingPriceLogic']) ? $general_settings['groupBookingPriceLogic'] : ['type' => 'per_person_multiply', 'amount' => 0];
        $instant_booking_enabled = isset($general_settings['instantBookingEnabled']) ? (bool) $general_settings['instantBookingEnabled'] : false;

        // Check if payment method is online (not cash or local)
        $is_online_payment = !in_array($payment_method, ['cash', 'local'], true);
        
        // Online payments always get confirmed status, regardless of instant booking setting
        if ($is_online_payment) {
            $status = 'confirmed';
        } else {
            $status = $instant_booking_enabled ? 'confirmed' : 'pending';
        }
        
        // Respect explicit status provided from admin context (but not for online payments)
        if (isset($form_data['status']) && !$is_online_payment) {
            $requested_status = sanitize_text_field($form_data['status']);
            $allowed_statuses = ['pending', 'confirmed', 'completed', 'cancelled'];
            if (in_array($requested_status, $allowed_statuses, true)) {
                $status = $requested_status;
            }
        }

        if (!$enable_group_booking) {
            $number_of_persons = 1;
        } elseif ($number_of_persons < 1 || $number_of_persons > $max_persons_per_booking) {
            // translators: %d is the maximum number of persons allowed
            return new \WP_Error('invalid_persons', sprintf(__('Number of persons must be between 1 and %d.', 'schedula-smart-appointment-booking'), $max_persons_per_booking), ['status' => 400]);
        }

        $service = $wpdb->get_row($wpdb->prepare("SELECT duration, price FROM {$services_table} WHERE id = %d", $service_id), ARRAY_A);
        if (!$service) {
            return new \WP_Error('service_not_found', __('Service not found.', 'schedula-smart-appointment-booking'), ['status' => 404]);
        }
        $duration_minutes = (int) $service['duration'];
        $base_service_price = (float) $service['price'];

        $final_service_price_per_person = $base_service_price;
        if ($staff_id) {
            $staff_services_table = $this->db->get_table_name('staff_services');
            $staff_service_override = $wpdb->get_row($wpdb->prepare("SELECT price, duration FROM {$staff_services_table} WHERE staff_id = %d AND service_id = %d", $staff_id, $service_id), ARRAY_A);
            if ($staff_service_override) {
                if ($staff_service_override['price'] !== null) {
                    $final_service_price_per_person = (float) $staff_service_override['price'];
                }
                if ($staff_service_override['duration'] !== null) {
                    $duration_minutes = (int) $staff_service_override['duration'];
                }
            }
        }

        $final_appointment_price = $final_service_price_per_person;
        if ($enable_group_booking) {
            $final_appointment_price = $this->calculate_group_booking_price($final_service_price_per_person, $number_of_persons, $group_booking_price_logic);
        }

        $start_datetime_str = $appointment_date . ' ' . $appointment_time . ':00';
        $start_timestamp = strtotime($start_datetime_str);
        $end_datetime_str = gmdate('Y-m-d H:i:s', $start_timestamp + ($duration_minutes * 60));

        if (!empty($staff_id) && $staff_id !== 0) {
            $availability = $this->is_staff_available($staff_id, $start_datetime_str, $end_datetime_str);
            if (!$availability['available']) {
                return new \WP_Error('staff_unavailable', $availability['message'], ['status' => 409]);
            }
        }

        $actual_customer_id = $this->get_or_create_customer($customer_id, $customer_first_name, $customer_last_name, $customer_email, $customer_phone);
        if (is_wp_error($actual_customer_id)) {
            return $actual_customer_id;
        }

        $appointment_data = array(
            'service_id' => $service_id,
            'staff_id' => ($staff_id === 0) ? null : $staff_id,
            'customer_name' => $customer_first_name . ' ' . $customer_last_name,
            'customer_email' => $customer_email,
            'customer_phone' => $customer_phone,
            'start_datetime' => $start_datetime_str,
            'end_datetime' => $end_datetime_str,
            'duration' => $duration_minutes,
            'price' => $final_appointment_price,
            'status' => $status,
            'payment_status' => 'paid',
            'cancellation_token' => wp_generate_uuid4(),
            'notes' => $notes,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        );

        $inserted = $wpdb->insert($appointments_table, $appointment_data);

        if ($inserted === false) {
            return new \WP_Error('db_error', __('Failed to create appointment', 'schedula-smart-appointment-booking'), ['error' => $wpdb->last_error]);
        }

        $appointment_id = $wpdb->insert_id;

        $customer_appointment_data = array(
            'customer_id' => $actual_customer_id,
            'appointment_id' => $appointment_id,
            'number_of_persons' => $number_of_persons,
        );
        $wpdb->insert($customer_appointments_table, $customer_appointment_data);

        // Create the payment record
        $wpdb->insert($payments_table, array(
            'appointment_id' => $appointment_id,
            'amount' => $payment_amount,
            'payment_type' => $payment_method,
            'status' => 'completed',
            'transaction_id' => $transaction_id,
            'payment_date' => current_time('mysql'),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ));

        // Fetch full appointment data for notifications
        $new_request = new WP_REST_Request('GET');
        $new_request->set_param('id', $appointment_id);
        $get_response = $this->get_appointment($new_request);
        $appointment = $get_response->get_data();

        if ($get_response->get_status() === 200) {
            $is_group_booking = isset($appointment['number_of_persons']) && $appointment['number_of_persons'] > 1;

            $template_type = $is_group_booking ? 'confirmed_group_booking' : 'confirmed_booking';

            $this->notifications_api->send_notification($template_type . '_client', $appointment);
            $this->notifications_api->send_notification($template_type . '_staff', $appointment);
            $this->notifications_api->send_notification($template_type . '_admin', $appointment);
        }

        return $appointment_id;
    }

    private function create_single_appointment(WP_REST_Request $request)
    {
        global $wpdb;
        $appointments_table = $this->db->get_table_name('appointments');
        $customers_table = $this->db->get_table_name('customers');
        $customer_appointments_table = $this->db->get_table_name('customer_appointments');
        $services_table = $this->db->get_table_name('services');
        $payments_table = $this->db->get_table_name('payments');

        $service_id = $request->get_param('service_id');
        $staff_id = $request->get_param('staff_id');
        $customer_id = $request->get_param('customer_id');
        $customer_first_name = $request->get_param('customer_first_name');
        $customer_last_name = $request->get_param('customer_last_name');
        $customer_email = $request->get_param('customer_email');
        $customer_phone = $request->get_param('customer_phone');
        $appointment_date = $request->get_param('appointment_date');
        $appointment_time = $request->get_param('appointment_time');
        $notes = $request->get_param('notes');
        $number_of_persons = $request->get_param('number_of_persons') ? absint($request->get_param('number_of_persons')) : 1;
        $payment_method = $request->get_param('payment_method') ? sanitize_text_field($request->get_param('payment_method')) : 'cash';

        // This endpoint now only supports non-gateway payments.
        if ($payment_method === 'stripe' || $payment_method === 'paypal') {
            return new \WP_Error('payment_gateway_not_supported', __('This endpoint does not support Stripe or PayPal payments. Please use the dedicated payment gateway endpoints.', 'schedula-smart-appointment-booking'), ['status' => 400]);
        }

        $general_settings = $this->settings_api->get_default_settings('general');
        $saved_general_settings = get_option('schesab_general_settings', []);
        $general_settings = array_merge($general_settings, $saved_general_settings);

        $enable_group_booking = isset($general_settings['enableGroupBooking']) ? (bool) $general_settings['enableGroupBooking'] : false;
        $max_persons_per_booking = isset($general_settings['maxPersonsPerBooking']) ? absint($general_settings['maxPersonsPerBooking']) : 1;
        $group_booking_price_logic = isset($general_settings['groupBookingPriceLogic']) ? $general_settings['groupBookingPriceLogic'] : ['type' => 'per_person_multiply', 'amount' => 0];
        $instant_booking_enabled = isset($general_settings['instantBookingEnabled']) ? (bool) $general_settings['instantBookingEnabled'] : false;
        $status = $instant_booking_enabled ? 'confirmed' : 'pending';
        // Allow explicit status override from admin form
        $requested_status = $request->get_param('status');
        if ($requested_status) {
            $requested_status = sanitize_text_field($requested_status);
            $allowed_statuses = ['pending', 'confirmed', 'completed', 'cancelled'];
            if (in_array($requested_status, $allowed_statuses, true)) {
                $status = $requested_status;
            }
        }

        if (!$enable_group_booking) {
            $number_of_persons = 1;
        } elseif ($number_of_persons < 1 || $number_of_persons > $max_persons_per_booking) {
            // translators: %d is the maximum number of persons allowed
            return new WP_REST_Response(array('message' => sprintf(__('Number of persons must be between 1 and %d.', 'schedula-smart-appointment-booking'), $max_persons_per_booking)), 400);
        }

        $service = $wpdb->get_row($wpdb->prepare("SELECT duration, price FROM {$services_table} WHERE id = %d", $service_id), ARRAY_A);
        if (!$service) {
            return new WP_REST_Response(array('message' => __('Service not found.', 'schedula-smart-appointment-booking')), 400);
        }
        $duration_minutes = (int) $service['duration'];
        $base_service_price = (float) $service['price'];

        $final_service_price_per_person = $base_service_price;
        if ($staff_id) {
            $staff_services_table = $this->db->get_table_name('staff_services');
            $staff_service_override = $wpdb->get_row($wpdb->prepare("SELECT price, duration FROM {$staff_services_table} WHERE staff_id = %d AND service_id = %d", $staff_id, $service_id), ARRAY_A);
            if ($staff_service_override) {
                if ($staff_service_override['price'] !== null) {
                    $final_service_price_per_person = (float) $staff_service_override['price'];
                }
                if ($staff_service_override['duration'] !== null) {
                    $duration_minutes = (int) $staff_service_override['duration'];
                }
            }
        }

        $final_appointment_price = $final_service_price_per_person;
        if ($enable_group_booking) {
            $final_appointment_price = $this->calculate_group_booking_price($final_service_price_per_person, $number_of_persons, $group_booking_price_logic);
        }

        $start_datetime_str = $appointment_date . ' ' . $appointment_time . ':00';

        // Calculate end time in business timezone
        $business_tz = new \DateTimeZone($this->get_business_timezone());
        $start_dt = new \DateTime($start_datetime_str, $business_tz);
        $end_dt = clone $start_dt;
        $end_dt->modify('+' . $duration_minutes . ' minutes');
        $end_datetime_str = $end_dt->format('Y-m-d H:i:s');

        if (empty($staff_id) || $staff_id === 0) {
            $staff_table = $this->db->get_table_name('staff');
            $staff_services_table = $this->db->get_table_name('staff_services');
            $possible_staff = $wpdb->get_results($wpdb->prepare("
                SELECT s.id FROM {$staff_table} s
                JOIN {$staff_services_table} ss ON s.id = ss.staff_id
                WHERE ss.service_id = %d AND s.status = 'active'
            ", $service_id), ARRAY_A);

            $found_staff_id = 0;
            foreach ($possible_staff as $staff) {
                $availability = $this->is_staff_available($staff['id'], $start_datetime_str, $end_datetime_str);
                if ($availability['available']) {
                    $found_staff_id = (int) $staff['id'];
                    break;
                }
            }

            if ($found_staff_id === 0) {
                return new WP_REST_Response(array('message' => __('No staff members are available at the selected time.', 'schedula-smart-appointment-booking')), 409);
            }
            $staff_id = $found_staff_id;
        } else {
            $availability = $this->is_staff_available($staff_id, $start_datetime_str, $end_datetime_str);
            if (!$availability['available']) {
                return new WP_REST_Response(array('message' => $availability['message']), 409);
            }
        }

        $actual_customer_id = $this->get_or_create_customer($customer_id, $customer_first_name, $customer_last_name, $customer_email, $customer_phone);

        if (is_wp_error($actual_customer_id)) {
            return $actual_customer_id;
        }

        $appointment_data = array(
            'service_id' => $service_id,
            'staff_id' => ($staff_id === 0) ? null : $staff_id,
            'customer_name' => $customer_first_name . ' ' . $customer_last_name,
            'customer_email' => $customer_email,
            'customer_phone' => $customer_phone,
            'start_datetime' => $this->store_business_datetime($start_datetime_str),
            'end_datetime' => $end_datetime_str,
            'duration' => $duration_minutes,
            'price' => $final_appointment_price,
            'status' => $status,
            'payment_status' => 'unpaid',
            'cancellation_token' => wp_generate_uuid4(),
            'notes' => $notes,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        );

        $inserted = $wpdb->insert($appointments_table, $appointment_data);

        if ($inserted === false) {
            return new WP_REST_Response(array('message' => __('Failed to create appointment', 'schedula-smart-appointment-booking'), 'error' => $wpdb->last_error), 500);
        }

        $appointment_id = $wpdb->insert_id;

        $customer_appointment_data = array(
            'customer_id' => $actual_customer_id,
            'appointment_id' => $appointment_id,
            'number_of_persons' => $number_of_persons,
        );
        $wpdb->insert($customer_appointments_table, $customer_appointment_data);

        $new_request = new WP_REST_Request('GET');
        $new_request->set_param('id', $appointment_id);
        $get_response = $this->get_appointment($new_request);
        $appointment = $get_response->get_data();

        if ($get_response->get_status() === 200) {
            $is_group_booking = isset($appointment['number_of_persons']) && $appointment['number_of_persons'] > 1;
            $is_confirmed = $appointment['status'] === 'confirmed';

            $template_type = '';
            if ($is_group_booking) {
                $template_type = $is_confirmed ? 'confirmed_group_booking' : 'pending_group_booking';
            } else {
                $template_type = $is_confirmed ? 'confirmed_booking' : 'new_booking'; // 'new_booking' is for pending single appointments
            }

            $this->notifications_api->send_notification($template_type . '_client', $appointment);
            $this->notifications_api->send_notification($template_type . '_staff', $appointment);
            $this->notifications_api->send_notification($template_type . '_admin', $appointment);
        }

        return new WP_REST_Response($appointment, 201);
    }

    private function create_recurring_appointments(WP_REST_Request $request)
    {
        global $wpdb;

        $series_table = $this->db->get_table_name('series');
        $appointments_table = $this->db->get_table_name('appointments');
        $customer_appointments_table = $this->db->get_table_name('customer_appointments');
        $services_table = $this->db->get_table_name('services');

        $service_id = $request->get_param('service_id');
        $staff_id = $request->get_param('staff_id');
        $appointment_date = $request->get_param('appointment_date');
        $appointment_time = $request->get_param('appointment_time');

        $frequency = $request->get_param('recurrence_frequency');
        $interval = $request->get_param('recurrence_interval');
        $count = $request->get_param('recurrence_count');
        $end_date = $request->get_param('recurrence_end_date');

        $customer_id = $request->get_param('customer_id');
        $customer_first_name = $request->get_param('customer_first_name');
        $customer_last_name = $request->get_param('customer_last_name');
        $customer_email = $request->get_param('customer_email');
        $customer_phone = $request->get_param('customer_phone');
        $number_of_persons = $request->get_param('number_of_persons') ? absint($request->get_param('number_of_persons')) : 1;

        $actual_customer_id = $this->get_or_create_customer($customer_id, $customer_first_name, $customer_last_name, $customer_email, $customer_phone);
        if (is_wp_error($actual_customer_id)) {
            return $actual_customer_id;
        }

        $general_settings = get_option('schesab_general_settings', []);
        $max_recurrences = isset($general_settings['recurrence']['maxRecurrences']) ? (int) $general_settings['recurrence']['maxRecurrences'] : 0;
        $enable_group_booking = isset($general_settings['enableGroupBooking']) ? (bool) $general_settings['enableGroupBooking'] : false;
        $group_booking_price_logic = isset($general_settings['groupBookingPriceLogic']) ? $general_settings['groupBookingPriceLogic'] : ['type' => 'per_person_multiply', 'amount' => 0];

        // --- Validation ---
        if (empty($frequency)) {
            return new \WP_Error('missing_recurrence_params', __('Missing recurrence frequency.', 'schedula-smart-appointment-booking'), ['status' => 400]);
        }
        if (empty($count) && empty($end_date)) {
            return new \WP_Error('missing_recurrence_params', __('An end date or a number of occurrences is required for recurring appointments.', 'schedula-smart-appointment-booking'), ['status' => 400]);
        }
        if ($max_recurrences > 0 && !empty($count) && $count > $max_recurrences) {
            // translators: %d: Maximum number of recurrences allowed
            return new \WP_Error('max_recurrences_exceeded', sprintf(__('The number of recurrences cannot exceed the maximum of %d.', 'schedula-smart-appointment-booking'), $max_recurrences), ['status' => 400]);
        }
        $service = $wpdb->get_row($wpdb->prepare("SELECT title, duration, price FROM {$services_table} WHERE id = %d", $service_id), ARRAY_A);
        if (!$service) {
            return new WP_REST_Response(array('message' => __('Service not found.', 'schedula-smart-appointment-booking')), 400);
        }

        $base_service_price = (float) $service['price'];
        $base_duration_minutes = (int) $service['duration'];

        $series_data = [
            'title' => $service['title'] . ' (' . __('Recurring', 'schedula-smart-appointment-booking') . ')',
            'frequency' => $frequency,
            'interval' => $interval,
            'start_date' => $appointment_date,
            'end_date' => $end_date,
            'occurrence_count' => $count,
            'service_id' => $service_id,
            'staff_id' => $staff_id,
        ];
        $wpdb->insert($series_table, $series_data);
        $series_id = $wpdb->insert_id;

        $created_appointments = [];
        $skipped_dates = [];
        $total_price = 0;
        $current_date = new \DateTime($appointment_date);
        $end_date_obj = $end_date ? new \DateTime($end_date) : null;
        $created_count = 0;
        $safety_loop_limit = 500;

        while ($created_count < $safety_loop_limit) {
            if ((!empty($count) && $created_count >= (int) $count) || ($end_date_obj && $current_date > $end_date_obj) || ($max_recurrences > 0 && $created_count >= $max_recurrences) || (empty($count) && empty($end_date))) {
                break;
            }

            $date_str = $current_date->format('Y-m-d');
            $start_datetime_str = $date_str . ' ' . $appointment_time . ':00';

            $assigned_staff_id = $staff_id;
            $availability_check = ['available' => false];

            if (!empty($staff_id) && $staff_id !== 0) {
                $availability_check = $this->is_staff_available($staff_id, $start_datetime_str, gmdate('Y-m-d H:i:s', strtotime($start_datetime_str) + $base_duration_minutes * 60));
            } else {
                $staff_table = $this->db->get_table_name('staff');
                $staff_services_table = $this->db->get_table_name('staff_services');
                $possible_staff = $wpdb->get_results($wpdb->prepare("
                    SELECT s.id FROM {$staff_table} s
                    JOIN {$staff_services_table} ss ON s.id = ss.staff_id
                    WHERE ss.service_id = %d AND s.status = 'active'
                ", $service_id), ARRAY_A);

                foreach ($possible_staff as $staff) {
                    $availability_check = $this->is_staff_available($staff['id'], $start_datetime_str, gmdate('Y-m-d H:i:s', strtotime($start_datetime_str) + $base_duration_minutes * 60));
                    if ($availability_check['available']) {
                        $assigned_staff_id = (int) $staff['id'];
                        break;
                    }
                }
            }

            if ($availability_check['available']) {
                $final_service_price_per_person = $base_service_price;
                $duration_minutes = $base_duration_minutes;

                if ($assigned_staff_id) {
                    $staff_services_table = $this->db->get_table_name('staff_services');
                    $staff_service_override = $wpdb->get_row($wpdb->prepare("SELECT price, duration FROM {$staff_services_table} WHERE staff_id = %d AND service_id = %d", $assigned_staff_id, $service_id), ARRAY_A);
                    if ($staff_service_override) {
                        if ($staff_service_override['price'] !== null) {
                            $final_service_price_per_person = (float) $staff_service_override['price'];
                        }
                        if ($staff_service_override['duration'] !== null) {
                            $duration_minutes = (int) $staff_service_override['duration'];
                        }
                    }
                }

                $appointment_price = $final_service_price_per_person;
                if ($enable_group_booking && $number_of_persons > 1) {
                    $appointment_price = $this->calculate_group_booking_price($final_service_price_per_person, $number_of_persons, $group_booking_price_logic);
                }
                $total_price += $appointment_price;

                $end_datetime_str = gmdate('Y-m-d H:i:s', strtotime($start_datetime_str) + $duration_minutes * 60);

                $notes = $request->get_param('notes');
                $payment_method = $request->get_param('payment_method') ? sanitize_text_field($request->get_param('payment_method')) : 'cash';
                
                // Check if payment method is online (not cash or local)
                $is_online_payment = !in_array($payment_method, ['cash', 'local'], true);
                
                // Online payments always get confirmed status, regardless of instant booking setting
                if ($is_online_payment) {
                    $status = 'confirmed';
                } else {
                    $status = (isset($general_settings['instantBookingEnabled']) && $general_settings['instantBookingEnabled']) ? 'confirmed' : 'pending';
                }
                
                // Allow explicit status override from admin form for recurring (but not for online payments)
                $requested_status = $request->get_param('status');
                if ($requested_status && !$is_online_payment) {
                    $requested_status = sanitize_text_field($requested_status);
                    $allowed_statuses = ['pending', 'confirmed', 'completed', 'cancelled'];
                    if (in_array($requested_status, $allowed_statuses, true)) {
                        $status = $requested_status;
                    }
                }

                $appointment_data = array(
                    'service_id' => $service_id,
                    'staff_id' => ($assigned_staff_id === 0) ? null : $assigned_staff_id,
                    'series_id' => $series_id,
                    'customer_name' => $customer_first_name . ' ' . $customer_last_name,
                    'customer_email' => $customer_email,
                    'customer_phone' => $customer_phone,
                    'start_datetime' => $start_datetime_str,
                    'end_datetime' => $end_datetime_str,
                    'duration' => $duration_minutes,
                    'price' => $appointment_price,
                    'status' => $status,
                    'payment_status' => ($payment_method === 'cash' || $payment_method === 'local') ? 'unpaid' : 'pending',
                    'cancellation_token' => wp_generate_uuid4(),
                    'notes' => $notes,
                );
                $wpdb->insert($appointments_table, $appointment_data);
                $appointment_id = $wpdb->insert_id;

                $wpdb->insert($customer_appointments_table, [
                    'customer_id' => $actual_customer_id,
                    'appointment_id' => $appointment_id,
                    'series_id' => $series_id,
                    'number_of_persons' => $number_of_persons,
                ]);

                $created_appointments[] = $appointment_id;
                $created_count++;
            } else {
                $skipped_dates[] = $date_str;
            }

            $freq_unit = rtrim($frequency, 'ly');
            if ($freq_unit === 'dai')
                $freq_unit = 'day';
            if (empty($freq_unit))
                $freq_unit = 'week';
            $current_date->modify('+' . $interval . ' ' . $freq_unit);
        }

        if (empty($created_appointments)) {
            return new \WP_Error('recurring_creation_failed', __('Could not create any appointments for the recurring series. All selected dates might be unavailable.', 'schedula-smart-appointment-booking'), ['status' => 409]);
        }

        if (!empty($skipped_dates)) {
            // translators: %1$s is customer full name, %2$s is appointment start date, %3$s is skipped dates list
            $message = __('For the recurring booking for %1$s starting on %2$s, the following dates were skipped due to unavailability: %3$s', 'schedula-smart-appointment-booking');

            $this->notifications_api->log_system_notice(
                __('Recurring Booking Conflict', 'schedula-smart-appointment-booking'),
                sprintf(
                    $message,
                    $request->get_param('customer_first_name') . ' ' . $request->get_param('customer_last_name'),
                    $request->get_param('appointment_date'),
                    implode(', ', $skipped_dates)
                )
            );
        }

        if (!empty($created_appointments)) {
            $first_appointment_id = $created_appointments[0];
            $new_request = new WP_REST_Request('GET');
            $new_request->set_param('id', $first_appointment_id);
            $get_response = $this->get_appointment($new_request);
            $first_appointment_details = $get_response->get_data();

            if ($get_response->get_status() === 200) {
                $is_group_booking = isset($first_appointment_details['number_of_persons']) && $first_appointment_details['number_of_persons'] > 1;
                $is_confirmed = $first_appointment_details['status'] === 'confirmed';

                $template_type = '';
                if ($is_group_booking) {
                    $template_type = $is_confirmed ? 'confirmed_recurring_group_booking' : 'pending_recurring_group_booking';
                } else {
                    $template_type = $is_confirmed ? 'confirmed_recurring_booking' : 'pending_recurring_booking';
                }

                $this->notifications_api->send_notification($template_type . '_client', $first_appointment_details);
                $this->notifications_api->send_notification($template_type . '_staff', $first_appointment_details);
                $this->notifications_api->send_notification($template_type . '_admin', $first_appointment_details);
            }
        }

        return new WP_REST_Response([
            'message' => __('Recurring appointment series created.', 'schedula-smart-appointment-booking'),
            'created_appointments' => $created_appointments,
            'skipped_dates' => $skipped_dates,
            'series_id' => $series_id,
            'total_price' => $total_price,
        ], 201);
    }

    public function update_appointment(WP_REST_Request $request)
    {
        global $wpdb;
        $appointments_table = $this->db->get_table_name('appointments');
        $customers_table = $this->db->get_table_name('customers');
        $customer_appointments_table = $this->db->get_table_name('customer_appointments');
        $services_table = $this->db->get_table_name('services');
        $payments_table = $this->db->get_table_name('payments'); // Corrected table name

        $id = (int) $request['id'];
        $existing_appointment = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$appointments_table} WHERE id = %d", $id), ARRAY_A);

        if (empty($existing_appointment)) {
            return new WP_REST_Response(array('message' => __('Appointment not found', 'schedula-smart-appointment-booking')), 404);
        }

        $old_status = $existing_appointment['status'];

        $service_id = $request->get_param('service_id');
        $staff_id = $request->get_param('staff_id');
        $customer_id = $request->get_param('customer_id');
        $customer_first_name = $request->get_param('customer_first_name');
        $customer_last_name = $request->get_param('customer_last_name');
        $customer_email = $request->get_param('customer_email');
        $customer_phone = $request->get_param('customer_phone');
        $appointment_date = $request->get_param('appointment_date'); // Date part from form
        $appointment_time = $request->get_param('appointment_time'); // Time part from form
        $new_status = $request->get_param('status'); // Get the new status
        $notes = $request->get_param('notes');
        $number_of_persons = $request->get_param('number_of_persons') ? absint($request->get_param('number_of_persons')) : 1;
        $payment_method = $request->get_param('payment_method') ? sanitize_text_field($request->get_param('payment_method')) : 'cash';

        // --- Fetch General Settings for Group Booking ---
        $general_settings = $this->settings_api->get_default_settings('general'); // Get default to ensure all keys are present
        $saved_general_settings = get_option('schesab_general_settings', []); // Get saved settings
        $general_settings = array_merge($general_settings, $saved_general_settings); // Merge them

        $enable_group_booking = isset($general_settings['enableGroupBooking']) ? (bool) $general_settings['enableGroupBooking'] : false;
        $max_persons_per_booking = isset($general_settings['maxPersonsPerBooking']) ? absint($general_settings['maxPersonsPerBooking']) : 1;
        $group_booking_price_logic = isset($general_settings['groupBookingPriceLogic']) ? $general_settings['groupBookingPriceLogic'] : ['type' => 'per_person_multiply', 'amount' => 0];
        // --- End Fetch General Settings ---

        // --- Validate number_of_persons based on settings ---
        if (!$enable_group_booking) {
            $number_of_persons = 1; // Force to 1 if group booking is disabled
        } elseif ($number_of_persons < 1 || $number_of_persons > $max_persons_per_booking) {
            $message = sprintf(
                // translators: %d: Maximum number of persons allowed per booking
                __('Number of persons must be between 1 and %d.', 'schedula-smart-appointment-booking'),
                $max_persons_per_booking
            );
            return new WP_REST_Response(array('message' => $message), 400);
        }
        // --- End Validation ---

        // Get service duration and base price from the services table
        $service = $wpdb->get_row($wpdb->prepare("SELECT duration, price FROM {$services_table} WHERE id = %d", $service_id), ARRAY_A);
        if (!$service) {
            return new WP_REST_Response(array('message' => __('Service not found.', 'schedula-smart-appointment-booking')), 400);
        }
        $duration_minutes = (int) $service['duration'];
        $base_service_price = (float) $service['price']; // Store as base price before adjustments

        // Check for staff-specific price override
        $final_service_price_per_person = $base_service_price;
        if ($staff_id) {
            $staff_services_table = $this->db->get_table_name('staff_services');
            // Use 'price' and 'duration' from staff_services
            $staff_service_override = $wpdb->get_row($wpdb->prepare("SELECT price, duration FROM {$staff_services_table} WHERE staff_id = %d AND service_id = %d", $staff_id, $service_id), ARRAY_A);
            if ($staff_service_override) {
                if ($staff_service_override['price'] !== null) {
                    $final_service_price_per_person = (float) $staff_service_override['price'];
                }
                if ($staff_service_override['duration'] !== null) {
                    $duration_minutes = (int) $staff_service_override['duration'];
                }
            }
        }

        // --- Conditional: Apply Group Booking Price Logic only if enabled ---
        $final_appointment_price = $final_service_price_per_person; // Default to per-person price
        if ($enable_group_booking) {
            $final_appointment_price = $this->calculate_group_booking_price($final_service_price_per_person, $number_of_persons, $group_booking_price_logic);
        } else {
            // If group booking is NOT enabled, the price is simply the per-person price (no multiplier, no discount)
            $final_appointment_price = $final_service_price_per_person;
        }
        // --- End Conditional Group Booking Price Logic ---

        // Calculate start_datetime and end_datetime
        $start_datetime_str = $appointment_date . ' ' . $appointment_time . ':00'; // Assuming HH:MM format from frontend
        $start_timestamp = strtotime($start_datetime_str);
        $end_timestamp = $start_timestamp + ($duration_minutes * 60);
        $end_datetime_str = gmdate('Y-m-d H:i:s', $end_timestamp);

        // --- STAFF AVAILABILITY CHECK (IMPROVED) ---
        // Check if critical booking details have changed before running the check
        $details_changed = false;
        if (
            strtotime($existing_appointment['start_datetime']) !== strtotime($start_datetime_str) ||
            (int) $existing_appointment['staff_id'] !== (int) $staff_id ||
            (int) $existing_appointment['service_id'] !== (int) $service_id
        ) {
            $details_changed = true;
        }

        // Only run the check if a specific staff member is assigned AND critical details have changed
        if ($details_changed && !empty($staff_id) && $staff_id !== 0) {
            $availability = $this->is_staff_available($staff_id, $start_datetime_str, $end_datetime_str, $id); // Pass current appointment ID to exclude
            if (!$availability['available']) {
                return new WP_REST_Response(array('message' => $availability['message']), 409); // 409 Conflict
            }
        }
        // --- END STAFF AVAILABILITY CHECK ---

        // Handle customer: update existing or create new if email changed
        $actual_customer_id = null;
        if ($customer_id) {
            $actual_customer_id = absint($customer_id);
            $wpdb->update(
                $customers_table,
                array(
                    'first_name' => $customer_first_name,
                    'last_name' => $customer_last_name,
                    'email' => $customer_email,
                    'phone' => $customer_phone,
                    'updated_at' => current_time('mysql'),
                ),
                array('id' => $actual_customer_id)
            );
        } else {
            $existing_customer_by_email = $wpdb->get_row($wpdb->prepare("SELECT id FROM {$customers_table} WHERE email = %s", $customer_email));

            if ($existing_customer_by_email) {
                $current_linked_customer_id = $wpdb->get_var($wpdb->prepare("SELECT customer_id FROM {$customer_appointments_table} WHERE appointment_id = %d", $id));
                if ($existing_customer_by_email->id != $current_linked_customer_id) {
                    return new WP_REST_Response(array('message' => __('A customer with this email already exists. Please select them from the list or use a different email.', 'schedula-smart-appointment-booking')), 409);
                } else {
                    $actual_customer_id = $existing_customer_by_email->id;
                    $wpdb->update(
                        $customers_table,
                        array(
                            'first_name' => $customer_first_name,
                            'last_name' => $customer_last_name,
                            'phone' => $customer_phone,
                            'updated_at' => current_time('mysql'),
                        ),
                        array('id' => $actual_customer_id)
                    );
                }
            } else {
                $wpdb->insert($customers_table, array(
                    'first_name' => $customer_first_name,
                    'last_name' => $customer_last_name,
                    'email' => $customer_email,
                    'phone' => $customer_phone,
                    'created_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql'),
                ));
                $actual_customer_id = $wpdb->insert_id;
            }
        }

        // Prepare data for 'appointments' table
        $appointment_data = array(
            'service_id' => $service_id,
            'staff_id' => ($staff_id === 0) ? null : $staff_id, // Store null if 'Any Employee'
            'customer_name' => $customer_first_name . ' ' . $customer_last_name, // Store full name for easy lookup
            'customer_email' => $customer_email,
            'customer_phone' => $customer_phone,
            'start_datetime' => $start_datetime_str,
            'end_datetime' => $end_datetime_str,
            'duration' => $duration_minutes,
            'price' => $final_appointment_price, // Use calculated price
            'status' => $new_status, // Use the new status
            'notes' => $notes,
            'updated_at' => current_time('mysql'),
        );

        // Determine payment_status for the appointments table
        if ($new_status === 'completed') {
            $appointment_data['payment_status'] = 'paid';
        } else {
            if ($payment_method === 'cash' || $payment_method === 'local') {
                $appointment_data['payment_status'] = 'unpaid';
            } else {
                $appointment_data['payment_status'] = 'pending';
            }
        }
        $appointment_data = array_filter($appointment_data, function ($value) {
            return !is_null($value);
        });

        $updated = $wpdb->update($appointments_table, $appointment_data, array('id' => $id));

        if ($updated === false) {
            return new WP_REST_Response(array('message' => __('Failed to update appointment', 'schedula-smart-appointment-booking'), 'error' => $wpdb->last_error), 500);
        }

        // Update 'customer_appointments' table
        $existing_ca = $wpdb->get_row($wpdb->prepare("SELECT id FROM {$customer_appointments_table} WHERE appointment_id = %d", $id));

        $customer_appointment_data = array(
            'customer_id' => $actual_customer_id,
            'number_of_persons' => $number_of_persons,
        );

        if ($existing_ca) {
            $wpdb->update($customer_appointments_table, $customer_appointment_data, array('appointment_id' => $id));
        } else {
            $customer_appointment_data['appointment_id'] = $id;
            $wpdb->insert($customer_appointments_table, $customer_appointment_data);
        }

        // --- REFINED: Handle Payment Record based on Appointment Status ---
        // Fetch existing payment status from the database to avoid overwriting
        $existing_payment_status = $wpdb->get_var($wpdb->prepare("SELECT status FROM {$payments_table} WHERE appointment_id = %d", $id));

        if ($new_status === 'completed') {
            // Only create/update payment if it's not already completed
            if ($existing_payment_status !== 'completed') {
                $payment_data = array(
                    'appointment_id' => $id,
                    'payment_date' => current_time('mysql'),
                    'amount' => $final_appointment_price, // Use calculated price for payment
                    'payment_type' => $payment_method, // Use the payment method from the request
                    'status' => 'completed',
                    'updated_at' => current_time('mysql'),
                );

                $existing_payment_record = $wpdb->get_row($wpdb->prepare("SELECT id FROM {$payments_table} WHERE appointment_id = %d", $id));

                if ($existing_payment_record) {
                    // Update existing payment record
                    $updated_payment = $wpdb->update($payments_table, $payment_data, array('id' => $existing_payment_record->id));
                    if ($updated_payment === false) {
                        error_log('Schedula Debug: FAILED to update payment for appointment ID ' . $id . '. DB Error: ' . $wpdb->last_error);
                    } else {
                        error_log('Schedula Debug: Successfully updated payment for appointment ID ' . $id);
                    }
                } else {
                    // Insert new payment record
                    $payment_data['created_at'] = current_time('mysql');
                    $inserted_payment = $wpdb->insert($payments_table, $payment_data);
                    if ($inserted_payment === false) {
                        error_log('Schedula Debug: FAILED to insert new payment for appointment ID ' . $id . '. DB Error: ' . $wpdb->last_error);
                    } else {
                        error_log('Schedula Debug: Successfully inserted new payment for appointment ID ' . $id . '. New payment ID: ' . $wpdb->insert_id);
                    }
                }
            }
        } else {
            // If status is changed FROM completed to something else, or just not completed, revert the payment to 'pending' or 'unpaid'.
            $existing_payment_record = $wpdb->get_row($wpdb->prepare("SELECT id FROM {$payments_table} WHERE appointment_id = %d", $id));
            if ($existing_payment_record && $existing_payment_status === 'completed') {
                $wpdb->update(
                    $payments_table,
                    array('status' => 'pending', 'updated_at' => current_time('mysql')), // Revert to pending
                    array('id' => $existing_payment_record->id)
                );
            }
        }
        // --- END REFINED: Handle Payment Record ---


        // Fetch the updated appointment with joined data for a complete response
        $new_request = new WP_REST_Request('GET');
        $new_request->set_param('id', $id);
        $response = $this->get_appointment($new_request);
        $appointment = $response->get_data();

        if ($response->get_status() === 200) {
            $this->trigger_notifications_on_status_change($old_status, $new_status, $appointment);
        }

        return new WP_REST_Response($appointment, 200);
    }

    private function trigger_notifications_on_status_change($old_status, $new_status, $appointment)
    {
        if ($old_status === $new_status) {
            return;
        }

        $is_group_booking = isset($appointment['number_of_persons']) && $appointment['number_of_persons'] > 1;
        $is_recurring = !empty($appointment['recurrence_frequency']);

        $base_template_type = null;

        if ($new_status === 'confirmed' && $old_status === 'pending') {
            if ($is_recurring && $is_group_booking) {
                $base_template_type = 'confirmed_recurring_group_booking';
            } elseif ($is_recurring) {
                $base_template_type = 'confirmed_recurring_booking';
            } elseif ($is_group_booking) {
                $base_template_type = 'confirmed_group_booking';
            } else {
                $base_template_type = 'confirmed_booking';
            }
        } elseif ($new_status === 'cancelled') {
            $base_template_type = 'cancelled_booking';
        } elseif ($new_status === 'rejected' && $old_status === 'pending') {
            $base_template_type = 'rejected_booking';
        }

        if ($base_template_type) {
            $this->notifications_api->send_notification($base_template_type . '_client', $appointment);
            $this->notifications_api->send_notification($base_template_type . '_staff', $appointment);
            $this->notifications_api->send_notification($base_template_type . '_admin', $appointment);
        }
    }

    /**
     * Delete an appointment.
     */
    public function delete_appointment(WP_REST_Request $request)
    {
        global $wpdb;
        $appointments_table = $this->db->get_table_name('appointments');
        $customer_appointments_table = $this->db->get_table_name('customer_appointments');
        $payments_table = $this->db->get_table_name('payments'); // Corrected table name

        $id = (int) $request['id'];

        // Fetch the appointment to get its start_datetime
        $get_request = new WP_REST_Request('GET');
        $get_request->set_param('id', $id);
        $appointment = $this->get_appointment($get_request)->get_data();

        if (empty($appointment)) {
            return new WP_REST_Response(array('message' => __('Appointment not found.', 'schedula-smart-appointment-booking')), 404);
        }

        // --- Cancellation Buffer Check ---
        $appointment_start_timestamp = strtotime($appointment['start_datetime']);
        $now_timestamp = current_time('timestamp', 0); // Local time

        // Only apply cancellation buffer check for appointments that are in the future.
        if ($appointment_start_timestamp > $now_timestamp) {
            $general_settings = $this->settings_api->get_default_settings('general');
            $saved_general_settings = get_option('schesab_general_settings', []);
            $general_settings = array_merge($general_settings, $saved_general_settings);

            $minimum_cancellation_buffer_minutes = isset($general_settings['minTimeCanceling']) ? absint($general_settings['minTimeCanceling']) : 0;

            if ($minimum_cancellation_buffer_minutes > 0) {
                $cancellation_cutoff_timestamp = $appointment_start_timestamp - ($minimum_cancellation_buffer_minutes * 60);


                if ($now_timestamp >= $cancellation_cutoff_timestamp) {

                    $message = sprintf(
                        /* translators: %d is the minimum cancellation buffer in minutes */
                        __('This appointment cannot be cancelled. Cancellations must be made at least %d minutes before the appointment start time.', 'schedula-smart-appointment-booking'),
                        $minimum_cancellation_buffer_minutes
                    );

                    return new WP_REST_Response(
                        array(
                            'message' => $message
                        ),
                        403 // 403 Forbidden
                    );
                }
            }
        }
        // --- End Cancellation Buffer Check ---

        // Pass the full appointment data to the hook since the record will be deleted.
        $notification_args = ['appointment_details' => $appointment];
        wp_schedule_single_event(time() + 5, 'schesab_send_notification_hook', ['type' => 'cancelled_booking_client'] + $notification_args);
        wp_schedule_single_event(time() + 5, 'schesab_send_notification_hook', ['type' => 'cancelled_booking_staff'] + $notification_args);
        wp_schedule_single_event(time() + 5, 'schesab_send_notification_hook', ['type' => 'cancelled_booking_admin'] + $notification_args);

        // Delete associated payment record first
        $wpdb->delete($payments_table, array('appointment_id' => $id));
        if ($wpdb->last_error) {
            error_log('Schedula API Error: Failed to delete associated payment for appointment ID ' . $id . '. DB Error: ' . $wpdb->last_error);
        }

        // Delete from customer_appointments first due to foreign key constraints if they existed
        $wpdb->delete($customer_appointments_table, array('appointment_id' => $id));
        if ($wpdb->last_error) {
            error_log('Schedula API Error: Failed to delete associated customer_appointment for appointment ID ' . $id . '. DB Error: ' . $wpdb->last_error);
        }

        $deleted = $wpdb->delete($appointments_table, array('id' => $id));

        if ($deleted === false) {
            return new WP_REST_Response(array('message' => __('Failed to delete appointment', 'schedula-smart-appointment-booking'), 'error' => $wpdb->last_error), 500);
        }
        if ($deleted === 0) {
            return new WP_REST_Response(array('message' => __('Appointment not found', 'schedula-smart-appointment-booking')), 404);
        }

        return new WP_REST_Response(array('message' => __('Appointment deleted successfully', 'schedula-smart-appointment-booking')), 200);
    }

    /**
     * Get all services.
     */
    public function get_services(WP_REST_Request $request)
    {
        global $wpdb;
        // Forward the request to the Services API class to handle it properly
        $services_api = new \SCHESAB\Api\SCHESAB_Services();
        return $services_api->get_services($request);
    }

    /**
     * Get a single service by ID.
     */
    public function get_service(WP_REST_Request $request)
    {
        global $wpdb;
        $services_table = $this->db->get_table_name('services');
        $id = (int) $request['id'];
        $service = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$services_table} WHERE id = %d", $id), ARRAY_A);

        if (empty($service)) {
            return new WP_REST_Response(array('message' => __('Service not found', 'schedula-smart-appointment-booking')), 404);
        }
        return new WP_REST_Response($service, 200);
    }

    /**
     * Get all categories.
     */
    public function get_categories(WP_REST_Request $request)
    {
        global $wpdb;
        $categories_table = $this->db->get_table_name('categories');
        $categories = $wpdb->get_results("SELECT * FROM {$categories_table} ORDER BY name ASC", ARRAY_A);
        return new WP_REST_Response($categories, 200);
    }

    /**
     * Get all staff members.
     */
    public function get_staff_members(WP_REST_Request $request)
    {
        // Forward the request to the Staff API class to handle it.
        // This avoids code duplication and ensures a single source of truth.
        $staff_api = new \SCHESAB\Api\SCHESAB_Staff();
        return $staff_api->get_staff_members($request);
    }

    /**
     * Uses the wp_schesab_staff_services junction table to get staff for a service.
     */
    public function get_staff_for_service(WP_REST_Request $request)
    {
        global $wpdb;
        $service_id = (int) $request['service_id'];
        $staff_services_table = $this->db->get_table_name('staff_services');
        $staff_table = $this->db->get_table_name('staff');

        if (!$service_id) {
            return new WP_REST_Response(array('message' => __('Service ID is required', 'schedula-smart-appointment-booking')), 400);
        }

        $staff_members = $wpdb->get_results($wpdb->prepare("
            SELECT
                s.id,
                s.name,
                s.email,
                ss.price AS price,
                ss.duration AS duration
            FROM {$staff_table} AS s
            INNER JOIN {$staff_services_table} AS ss ON s.id = ss.staff_id
            WHERE ss.service_id = %d AND s.status = 'active'
            ORDER BY s.name ASC
        ", $service_id), ARRAY_A);

        return new WP_REST_Response($staff_members, 200);
    }

    /**
     * Cancels an appointment using a secure token.
     */
    public function cancel_appointment_by_token(WP_REST_Request $request)
    {
        global $wpdb;
        $token = $request->get_param('token');

        if (empty($token)) {
            return new WP_REST_Response(['message' => __('Invalid cancellation token.', 'schedula-smart-appointment-booking')], 400);
        }

        $appointments_table = $this->db->get_table_name('appointments');
        $appointment = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$appointments_table} WHERE cancellation_token = %s", $token), ARRAY_A);

        if (empty($appointment)) {
            return new WP_REST_Response(['message' => __('Appointment not found or token is invalid.', 'schedula-smart-appointment-booking')], 404);
        }

        if ($appointment['status'] === 'cancelled') {
            return new WP_REST_Response(['message' => __('This appointment has already been cancelled.', 'schedula-smart-appointment-booking')], 400);
        }

        // Check cancellation buffer
        $general_settings = get_option('schesab_general_settings', []);
        $min_time_canceling = isset($general_settings['minTimeCanceling']) ? intval($general_settings['minTimeCanceling']) : 0;
        $start_timestamp = strtotime($appointment['start_datetime']);
        $cutoff_timestamp = $start_timestamp - ($min_time_canceling * 60);
        $now_timestamp = current_time('timestamp');

        if ($min_time_canceling > 0 && $now_timestamp > $cutoff_timestamp) {
            return new WP_REST_Response(['message' => __('This appointment can no longer be cancelled as the cancellation period has passed.', 'schedula-smart-appointment-booking')], 403);
        }

        // Update appointment status to cancelled
        $updated = $wpdb->update($appointments_table, ['status' => 'cancelled'], ['id' => $appointment['id']]);

        if ($updated === false) {
            return new WP_REST_Response(['message' => __('Could not update the appointment status.', 'schedula-smart-appointment-booking')], 500);
        }

        // Fetch full appointment details for notification
        $full_appointment_details_req = new WP_REST_Request('GET');
        $full_appointment_details_req->set_param('id', $appointment['id']);
        $full_appointment_details = $this->get_appointment($full_appointment_details_req)->get_data();

        // Send notifications
        $this->notifications_api->send_notification('cancelled_booking_client', $full_appointment_details);
        $this->notifications_api->send_notification('cancelled_booking_staff', $full_appointment_details);
        $this->notifications_api->send_notification('cancelled_booking_admin', $full_appointment_details);

        return new WP_REST_Response(['message' => __('Appointment successfully cancelled.', 'schedula-smart-appointment-booking')], 200);
    }


    /**
     * Get all data needed for the frontend booking form in a single request.
     * This improves performance by reducing the number of HTTP requests.
     */
    public function get_booking_form_data(WP_REST_Request $request)
    {
        global $wpdb;

        // 1. Get all Categories
        $categories_table = $this->db->get_table_name('categories');
        $categories = $wpdb->get_results("SELECT id, name, description FROM $categories_table ORDER BY position ASC, name ASC", ARRAY_A);

        // 2. Get all Services
        $services_table = $this->db->get_table_name('services');
        $services = $wpdb->get_results("SELECT id, category_id, title, description, duration, price, image_url FROM $services_table ORDER BY position ASC, title ASC", ARRAY_A);

        // 3. Get all active Staff
        $staff_table = $this->db->get_table_name('staff');
        $staff = $wpdb->get_results($wpdb->prepare("SELECT id, name, email, description, image_url FROM $staff_table WHERE status = %s ORDER BY name ASC", 'active'), ARRAY_A);

        // 4. Get Staff-Service relationships with price/duration overrides
        $staff_services_table = $this->db->get_table_name('staff_services');
        $staff_service_relations = $wpdb->get_results("SELECT service_id, staff_id, price, duration FROM $staff_services_table", ARRAY_A);

        // 5. Process data to embed staff info and overrides into services
        $service_staff_map = [];
        foreach ($staff_service_relations as $relation) {
            $service_staff_map[$relation['service_id']][] = [
                'staff_id' => (int) $relation['staff_id'],
                'price' => $relation['price'] !== null ? (float) $relation['price'] : null,
                'duration' => $relation['duration'] !== null ? (int) $relation['duration'] : null,
            ];
        }

        $services_with_staff = [];
        foreach ($services as $service) {
            $staff_relations = $service_staff_map[$service['id']] ?? [];
            // Add a simple array of IDs for easy filtering on the frontend
            $service['staff_ids'] = array_map(function ($relation) {
                return $relation['staff_id'];
            }, $staff_relations);
            // Add the detailed overrides array for price/duration logic
            $service['staff_overrides'] = $staff_relations;
            $services_with_staff[] = $service;
        }

        $response_data = [
            'categories' => $categories ?: [],
            'services' => $services_with_staff ?: [],
            'staff' => $staff ?: [],
        ];

        return new WP_REST_Response($response_data, 200);
    }




    /**
     * Deletes an incomplete appointment if it's still incomplete after a timeout.
     * This is triggered by a WP Cron event.
     *
     * @param int $appointment_id The ID of the appointment to check and potentially delete.
     */
    public function delete_incomplete_appointment_callback($appointment_id)
    {
        global $wpdb;
        $appointments_table = $this->db->get_table_name('appointments');
        $customer_appointments_table = $this->db->get_table_name('customer_appointments');
        $payments_table = $this->db->get_table_name('payments');

        // Fetch the appointment to check its current status
        $appointment = $wpdb->get_row($wpdb->prepare(
            "SELECT status FROM {$appointments_table} WHERE id = %d",
            $appointment_id
        ));

        // If the appointment still exists and its status is 'incomplete', delete it
        if ($appointment && $appointment->status === 'incomplete') {
            // Using a transaction to ensure all deletions are successful
            $wpdb->query('START TRANSACTION');

            $wpdb->delete($payments_table, array('appointment_id' => $appointment_id), array('%d'));
            $wpdb->delete($customer_appointments_table, array('appointment_id' => $appointment_id), array('%d'));
            $wpdb->delete($appointments_table, array('id' => $appointment_id), array('%d'));

            $wpdb->query('COMMIT');
        }
    }

    /**
     * Check for conflicts in a recurring appointment series.
     */
    public function check_recurrence_conflicts(WP_REST_Request $request)
    {
        global $wpdb;

        $service_id = $request->get_param('service_id');
        $staff_id = $request->get_param('staff_id');
        $start_date = $request->get_param('appointment_date');
        $start_time = $request->get_param('appointment_time');
        $frequency = $request->get_param('recurrence_frequency');
        $interval = $request->get_param('recurrence_interval');
        $count = $request->get_param('recurrence_count');

        $services_table = $this->db->get_table_name('services');
        $service = $wpdb->get_row($wpdb->prepare("SELECT duration FROM {$services_table} WHERE id = %d", $service_id), ARRAY_A);
        if (!$service) {
            return new WP_REST_Response(array('message' => __('Service not found.', 'schedula-smart-appointment-booking')), 404);
        }
        $duration_minutes = (int) $service['duration'];

        $conflicting_dates = [];
        $current_date = new \DateTime($start_date);

        // Loop through each occurrence to check for availability
        for ($i = 0; $i < $count; $i++) {
            if ($i > 0) { // For occurrences after the first one, calculate the next date
                $freq_unit = rtrim($frequency, 'ly');
                if ($freq_unit === 'dai')
                    $freq_unit = 'day';
                if (empty($freq_unit))
                    $freq_unit = 'week'; // Fallback
                $current_date->modify('+' . $interval . ' ' . $freq_unit);
            }

            $date_str = $current_date->format('Y-m-d');
            $start_datetime_str = $date_str . ' ' . $start_time . ':00';
            $end_datetime_str = gmdate('Y-m-d H:i:s', strtotime($start_datetime_str) + $duration_minutes * 60);

            // Use the existing is_staff_available method to check for conflicts
            if ($staff_id) {
                $availability = $this->is_staff_available($staff_id, $start_datetime_str, $end_datetime_str);

                if (!$availability['available']) {
                    $conflicting_dates[] = $date_str;
                }
            }
        }

        return new WP_REST_Response(array('conflicting_dates' => $conflicting_dates), 200);
    }
}