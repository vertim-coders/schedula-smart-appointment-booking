<?php
/**
 * REST API Endpoints for Schedula Staff Management.
 * Handles CRUD operations for staff data, schedules, and holidays.
 *
 * @package SCHESAB\Api
 * 
 */

namespace SCHESAB\Api;

use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use SCHESAB\Database\SCHESAB_Database;

if (!defined('ABSPATH')) {
    exit;
}

class SCHESAB_Staff
{

    private $namespace = 'schesab/v1';
    private $db;

    public function __construct()
    {
        $this->db = \SCHESAB\Database\SCHESAB_Database::get_instance();
    }

    public function register_routes()
    {
        // Staff Endpoints - Updated with pagination and SORTING support
        register_rest_route($this->namespace, '/staff', array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_staff_members'),
                'permission_callback' => '__return_true',
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
                        'sanitize_callback' => 'intval',
                        'default' => 10,
                        'required' => false,
                        'description' => __('Number of items per page.', 'schedula-smart-appointment-booking'),
                    ),
                    // START NEW: Sorting arguments
                    'sort_by' => array(
                        'sanitize_callback' => 'sanitize_text_field',
                        'validate_callback' => function ($param, $request, $key) {
                            // Allowed columns for sorting
                            return in_array($param, ['id', 'name', 'email', 'phone']);
                        },
                        'default' => 'name', // Default sort column for staff
                        'required' => false,
                        'description' => __('Column to sort by (id, name, email, phone).', 'schedula-smart-appointment-booking'),
                    ),
                    'sort_direction' => array(
                        'sanitize_callback' => 'sanitize_text_field',
                        'validate_callback' => function ($param, $request, $key) {
                            return in_array(strtolower($param), ['asc', 'desc']);
                        },
                        'default' => 'asc', // Default sort direction for staff
                        'required' => false,
                        'description' => __('Sorting direction (asc or desc).', 'schedula-smart-appointment-booking'),
                    ),
                    // END NEW: Sorting arguments
                ),
            ),
            array(
                'methods' => 'POST',
                'callback' => array($this, 'create_staff_member'),
                'permission_callback' => array($this, 'check_admin_permissions'),
                'args' => $this->get_staff_member_args(false),
            ),
        ));

        register_rest_route($this->namespace, '/staff/(?P<id>\d+)', array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_staff_member'),
                'permission_callback' => '__return_true',
                'args' => array(
                    'id' => array(
                        'validate_callback' => function ($param, $request, $key) {
                            return is_numeric($param);
                        },
                    ),
                ),
            ),
            array(
                'methods' => 'PUT',
                'callback' => array($this, 'update_staff_member'),
                'permission_callback' => array($this, 'check_admin_permissions'),
                'args' => $this->get_staff_member_args(true),
            ),
            array(
                'methods' => 'DELETE',
                'callback' => array($this, 'delete_staff_member'),
                'permission_callback' => array($this, 'check_admin_permissions'),
                'args' => array(
                    'id' => array(
                        'validate_callback' => function ($param, $request, $key) {
                            return is_numeric($param);
                        },
                    ),
                    'force' => array(
                        'sanitize_callback' => 'rest_sanitize_boolean',
                        'default' => false,
                        'required' => false,
                    ),
                ),
            ),
        ));

        // Staff Services Endpoints
        register_rest_route($this->namespace, '/staff/(?P<staff_id>\d+)/services', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_staff_services'),
            'permission_callback' => '__return_true',
            'args' => array(
                'staff_id' => array(
                    'validate_callback' => function ($param, $request, $key) {
                        return is_numeric($param);
                    },
                ),
            ),
        ));

        register_rest_route($this->namespace, '/staff/(?P<staff_id>\d+)/services', array(
            'methods' => 'POST',
            'callback' => array($this, 'add_staff_service'),
            'permission_callback' => array($this, 'check_admin_permissions'),
            'args' => $this->get_staff_service_args(),
        ));

        register_rest_route($this->namespace, '/staff/(?P<staff_id>\d+)/services/(?P<service_id>\d+)', array(
            'methods' => 'PUT',
            'callback' => array($this, 'update_staff_service'),
            'permission_callback' => array($this, 'check_admin_permissions'),
            'args' => $this->get_staff_service_args(true),
        ));

        register_rest_route($this->namespace, '/staff/(?P<staff_id>\d+)/services/(?P<service_id>\d+)', array(
            'methods' => 'DELETE',
            'callback' => array($this, 'delete_staff_service'),
            'permission_callback' => array($this, 'check_admin_permissions'),
        ));

        // Staff Schedule Endpoints
        register_rest_route($this->namespace, '/staff/(?P<staff_id>\d+)/schedule', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_staff_schedule'),
            'permission_callback' => '__return_true',
            'args' => array(
                'staff_id' => array(
                    'validate_callback' => function ($param) {
                        return is_numeric($param);
                    },
                ),
            ),
        ));

        register_rest_route($this->namespace, '/staff/(?P<staff_id>\d+)/schedule', array(
            'methods' => 'POST',
            'callback' => array($this, 'add_staff_schedule'),
            'permission_callback' => array($this, 'check_admin_permissions'),
            'args' => $this->get_staff_schedule_args(),
        ));

        register_rest_route($this->namespace, '/staff/(?P<staff_id>\d+)/schedule/(?P<id>\d+)', array(
            'methods' => 'PUT',
            'callback' => array($this, 'update_staff_schedule'),
            'permission_callback' => array($this, 'check_admin_permissions'),
            'args' => $this->get_staff_schedule_args(true),
        ));

        register_rest_route($this->namespace, '/staff/(?P<staff_id>\d+)/schedule/(?P<id>\d+)', array(
            'methods' => 'DELETE',
            'callback' => array($this, 'delete_staff_schedule'),
            'permission_callback' => array($this, 'check_admin_permissions'),
        ));

        // Schedule Item Breaks Endpoints
        register_rest_route($this->namespace, '/schedule-items/(?P<schedule_item_id>\d+)/breaks', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_schedule_item_breaks'),
            'permission_callback' => '__return_true',
            'args' => array(
                'schedule_item_id' => array(
                    'validate_callback' => function ($param) {
                        return is_numeric($param);
                    },
                ),
            ),
        ));

        register_rest_route($this->namespace, '/schedule-items/(?P<schedule_item_id>\d+)/breaks', array(
            'methods' => 'POST',
            'callback' => array($this, 'add_schedule_item_break'),
            'permission_callback' => array($this, 'check_admin_permissions'),
            'args' => $this->get_schedule_item_break_args(),
        ));

        register_rest_route($this->namespace, '/schedule-items/(?P<schedule_item_id>\d+)/breaks/(?P<id>\d+)', array(
            'methods' => 'PUT',
            'callback' => array($this, 'update_schedule_item_break'),
            'permission_callback' => array($this, 'check_admin_permissions'),
            'args' => $this->get_schedule_item_break_args(true),
        ));

        register_rest_route($this->namespace, '/schedule-items/(?P<schedule_item_id>\d+)/breaks/(?P<id>\d+)', array(
            'methods' => 'DELETE',
            'callback' => array($this, 'delete_schedule_item_break'),
            'permission_callback' => array($this, 'check_admin_permissions'),
        ));

        // Holidays Endpoints
        register_rest_route($this->namespace, '/holidays', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_holidays'),
            'permission_callback' => '__return_true',
            'args' => array(
                'staff_id' => array(
                    'sanitize_callback' => 'absint',
                    'required' => false,
                ),
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
            ),
        ));

        register_rest_route($this->namespace, '/holidays', array(
            'methods' => 'POST',
            'callback' => array($this, 'create_holiday'),
            'permission_callback' => array($this, 'check_admin_permissions'),
            'args' => $this->get_holiday_args(),
        ));

        register_rest_route($this->namespace, '/holidays/(?P<id>\d+)', array(
            'methods' => 'PUT',
            'callback' => array($this, 'update_holiday'),
            'permission_callback' => array($this, 'check_admin_permissions'),
            'args' => $this->get_holiday_args(true),
        ));

        register_rest_route($this->namespace, '/holidays/(?P<id>\d+)', array(
            'methods' => 'DELETE',
            'callback' => array($this, 'delete_holiday'),
            'permission_callback' => array($this, 'check_admin_permissions'),
        ));

        // Endpoint to get staff unavailable days (holidays + no schedule)
        register_rest_route($this->namespace, '/staff/(?P<staff_id>\d+)/unavailable-days', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_staff_unavailable_days'),
            'permission_callback' => '__return_true',
            'args' => array(
                'staff_id' => array(
                    'validate_callback' => function ($param) {
                        return is_numeric($param);
                    },
                    'required' => true,
                ),
                'start_date' => array(
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => function ($param) {
                        return (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $param);
                    },
                    'required' => true,
                ),
                'end_date' => array(
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => function ($param) {
                        return (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $param);
                    },
                    'required' => true,
                ),
            ),
        ));

        // Get working hours for a specific date
        register_rest_route($this->namespace, '/staff/(?P<staff_id>\d+)/working-hours', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_staff_working_hours'),
            'permission_callback' => '__return_true',
            'args' => array(
                'staff_id' => array(
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
            ),
        ));
    }

    /**
     * Helper to get common staff member arguments.
     * FIXED: Improved email validation to handle edge cases
     * @param bool $for_update If true, all args are optional.
     * @return array
     */
    protected function get_staff_member_args($for_update = false)
    {
        $args = [
            'name' => [
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => function ($param) {
                    return !empty(trim($param));
                },
                'required' => !$for_update,
            ],
            'email' => [
                'sanitize_callback' => 'sanitize_email',
                'validate_callback' => function ($param) {
                    // FIXED: Better email validation
                    if (empty($param)) {
                        return false;
                    }
                    $sanitized_email = sanitize_email($param);
                    return !empty($sanitized_email) && is_email($sanitized_email) !== false;
                },
                'required' => !$for_update,
            ],
            'phone' => [
                'sanitize_callback' => 'sanitize_text_field',
                'required' => false,
            ],
            'description' => [
                'sanitize_callback' => 'sanitize_textarea_field',
                'required' => false,
            ],
            'image_url' => [
                'sanitize_callback' => 'esc_url_raw',
                'validate_callback' => function ($param) {
                    // Allow empty URL since it's not a required field.
                    if (empty(trim($param))) {
                        return true;
                    }
                    // First, check if it's a syntactically valid URL.
                    if (filter_var($param, FILTER_VALIDATE_URL) === false) {
                        return false;
                    }
                    // Second, check if the URL ends with a common image extension.
                    return (bool) preg_match('/\.(jpg|jpeg|png|gif|webp|svg)(\?.*)?$/i', $param);
                },
                'required' => false,
            ],
            'status' => [
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => function ($param) {
                    return in_array($param, ['active', 'inactive']);
                },
                'required' => false,
                'default' => 'active',
            ],
        ];

        if ($for_update) {
            foreach ($args as &$arg) {
                $arg['required'] = false;
            }
        }
        return $args;
    }

    /**
     * Helper to get common staff service arguments.
     * @param bool $for_update If true, all args are optional.
     * @return array
     */
    protected function get_staff_service_args($for_update = false)
    {
        $args = [
            'service_id' => [
                'sanitize_callback' => 'absint',
                'validate_callback' => function ($param) {
                    return is_numeric($param) && $param > 0;
                },
                'required' => !$for_update,
            ],
            'price' => [
                'sanitize_callback' => function ($value) {
                    return is_numeric($value) ? floatval($value) : null;
                },
                'validate_callback' => function ($param) {
                    return is_numeric($param) && $param >= 0;
                },
                'required' => false,
            ],
            'duration' => [
                'sanitize_callback' => function ($value) {
                    return is_numeric($value) ? absint($value) : null;
                },
                'validate_callback' => function ($param) {
                    return is_numeric($param) || is_null($param) || $param === '';
                },
                'required' => false,
            ],
        ];
        if ($for_update) {
            foreach ($args as &$arg) {
                $arg['required'] = false;
            }
        }
        return $args;
    }

    /**
     * Helper to get common staff schedule arguments.
     * @param bool $for_update If true, all args are optional.
     * @return array
     */
    protected function get_staff_schedule_args($for_update = false)
    {
        $args = [
            'day_of_week' => [
                'sanitize_callback' => 'absint',
                'validate_callback' => function ($param) {
                    return is_numeric($param) && $param >= 0 && $param <= 6;
                },
                'required' => !$for_update,
            ],
            'start_time' => [
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => function ($param) {
                    return (bool) preg_match('/^\d{2}:\d{2}$/', $param);
                },
                'required' => !$for_update,
            ],
            'end_time' => [
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => function ($param) {
                    return (bool) preg_match('/^\d{2}:\d{2}$/', $param);
                },
                'required' => !$for_update,
            ],
        ];
        if ($for_update) {
            foreach ($args as &$arg) {
                $arg['required'] = false;
            }
        }
        return $args;
    }

    /**
     * Helper to get common schedule item break arguments.
     * @param bool $for_update If true, all args are optional.
     * @return array
     */
    protected function get_schedule_item_break_args($for_update = false)
    {
        $args = [
            'start_time' => [
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => function ($param) {
                    return (bool) preg_match('/^\d{2}:\d{2}$/', $param);
                },
                'required' => !$for_update,
            ],
            'end_time' => [
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => function ($param) {
                    return (bool) preg_match('/^\d{2}:\d{2}$/', $param);
                },
                'required' => !$for_update,
            ],
        ];
        if ($for_update) {
            foreach ($args as &$arg) {
                $arg['required'] = false;
            }
        }
        return $args;
    }

    /**
     * Helper to get common holiday arguments.
     * @param bool $for_update If true, all args are optional.
     * @return array
     */
    protected function get_holiday_args($for_update = false)
    {
        $args = [
            'staff_id' => [
                'sanitize_callback' => 'absint',
                'required' => !$for_update,
            ],
            'start_date' => [
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => function ($param) {
                    return (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $param);
                },
                'required' => !$for_update,
            ],
            'end_date' => [
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => function ($param) {
                    return (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $param);
                },
                'required' => !$for_update,
            ],
            'description' => [
                'sanitize_callback' => 'sanitize_textarea_field',
                'required' => false,
            ],
        ];
        if ($for_update) {
            foreach ($args as &$arg) {
                $arg['required'] = false;
            }
        }
        return $args;
    }

    /**
     * Check if the current user has admin permissions.
     * @return bool
     */
    public function check_admin_permissions()
    {
        return current_user_can('manage_options');
    }

    /**
     * Get all staff members with pagination and search support.
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function get_staff_members(WP_REST_Request $request)
    {
        global $wpdb;
        $staff_table = $this->db->get_table_name('staff');
        $search = $request->get_param('search');
        $status = $request->get_param('status');
        $page = $request->get_param('page');
        $per_page = $request->get_param('per_page');
        $sort_by = $request->get_param('sort_by');
        $sort_direction = strtoupper($request->get_param('sort_direction'));

        // Build the WHERE clause for search and status
        $where_clauses = [];
        $params = [];

        if (!empty($search)) {
            $search_like = '%' . $wpdb->esc_like($search) . '%';
            $where_clauses[] = "(name LIKE %s OR email LIKE %s OR phone LIKE %s)";
            $params[] = $search_like;
            $params[] = $search_like;
            $params[] = $search_like;
        }

        if ($status && $status !== 'all') {
            $where_clauses[] = "status = %s";
            $params[] = $status;
        }

        $base_sql = "FROM {$staff_table}";
        $where_sql = '';
        if (!empty($where_clauses)) {
            $where_sql = " WHERE " . implode(" AND ", $where_clauses);
        }

        // Count total items
        $count_sql = "SELECT COUNT(id) {$base_sql}{$where_sql}";
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        $total_items = $wpdb->get_var($wpdb->prepare($count_sql, ...$params));

        // Build the main query
        $sql = "SELECT * {$base_sql}{$where_sql}";

        // Whitelist and append ORDER BY clause
        $allowed_sort_by_columns = ['id', 'name', 'email', 'phone'];
        $sort_by_column = in_array($sort_by, $allowed_sort_by_columns) ? $sort_by : 'name';
        $sort_direction_clause = in_array($sort_direction, ['ASC', 'DESC']) ? $sort_direction : 'ASC';
        $sql .= " ORDER BY {$sort_by_column} {$sort_direction_clause}";

        // Append LIMIT and OFFSET for pagination
        if ($per_page > 0) {
            $sql .= " LIMIT %d OFFSET %d";
            $params[] = $per_page;
            $params[] = ($page - 1) * $per_page;
        }

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        $staff_members = $wpdb->get_results($wpdb->prepare($sql, ...$params), ARRAY_A);

        if ($wpdb->last_error) {
            error_log('Schedula API Error (get_staff_members): ' . $wpdb->last_error);
            return new WP_REST_Response(['message' => __('Database error fetching staff members.', 'schedula-smart-appointment-booking'), 'details' => $wpdb->last_error], 500);
        }

        return new WP_REST_Response([
            'staff' => $staff_members ?: [],
            'total_items' => (int) $total_items,
        ], 200);
    }

    /**
     * Get a single staff member by ID.
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function get_staff_member(WP_REST_Request $request)
    {
        global $wpdb;
        $staff_table = $this->db->get_table_name('staff');
        $id = (int) $request['id'];


        $staff_member = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$staff_table} WHERE id = %d",
            $id
        ), ARRAY_A);

        if (empty($staff_member)) {
            return new WP_REST_Response(array('message' => __('Staff member not found.', 'schedula-smart-appointment-booking')), 404);
        }

        return new WP_REST_Response($staff_member, 200);
    }

    /**
     * Create a new staff member.
     * FIXED: Enhanced error handling and validation
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function create_staff_member(WP_REST_Request $request)
    {
        global $wpdb;
        $staff_table = $this->db->get_table_name('staff');

        // FIXED: Enhanced data preparation with better error handling
        try {
            $data = $this->prepare_item_for_database($request, $this->get_staff_member_args());

            // Additional validation
            if (empty($data['name'])) {
                return new WP_REST_Response(['message' => __('Invalid parameter: name is required and cannot be empty.', 'schedula-smart-appointment-booking')], 400);
            }

            if (empty($data['email'])) {
                return new WP_REST_Response(['message' => __('Invalid parameter: email is required and cannot be empty.', 'schedula-smart-appointment-booking')], 400);
            }

            // Check for duplicate email
            $existing_email = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$staff_table} WHERE email = %s",
                $data['email']
            ));

            if ($existing_email > 0) {
                return new WP_REST_Response(['message' => __('Invalid parameter: email already exists. Please use a different email address.', 'schedula-smart-appointment-booking')], 400);
            }

        } catch (Exception $e) {
            error_log('Schedula API Error (create_staff_member - data preparation): ' . $e->getMessage());
            return new WP_REST_Response(['message' => __('Invalid parameters provided.', 'schedula-smart-appointment-booking'), 'details' => $e->getMessage()], 400);
        }

        $inserted = $wpdb->insert($staff_table, $data);

        if ($inserted === false) {
            error_log('Schedula API Error (create_staff_member - database insert): ' . $wpdb->last_error);
            error_log('Data being inserted: ' . print_r($data, true));
            return new WP_REST_Response(['message' => __('Failed to create staff member.', 'schedula-smart-appointment-booking'), 'db_error' => $wpdb->last_error], 500);
        }

        $new_staff_id = $wpdb->insert_id;
        $new_staff_member = $this->get_staff_member_data($new_staff_id);

        if (empty($new_staff_member)) {
            return new WP_REST_Response(['message' => __('Failed to retrieve newly created staff member.', 'schedula-smart-appointment-booking')], 500);
        }

        return new WP_REST_Response($new_staff_member, 201);
    }

    /**
     * Update an existing staff member.
     * FIXED: Enhanced error handling and validation
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function update_staff_member(WP_REST_Request $request)
    {
        global $wpdb;
        $staff_table = $this->db->get_table_name('staff');
        $id = (int) $request['id'];

        // FIXED: Enhanced data preparation with better error handling
        try {
            $data = $this->prepare_item_for_database($request, $this->get_staff_member_args(true), true);

            if (empty($data)) {
                return new WP_REST_Response(['message' => __('No data provided for update.', 'schedula-smart-appointment-booking')], 400);
            }

            // If email is being updated, check for duplicates
            if (isset($data['email'])) {
                $existing_email = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$staff_table} WHERE email = %s AND id != %d",
                    $data['email'],
                    $id
                ));

                if ($existing_email > 0) {
                    return new WP_REST_Response(['message' => __('Invalid parameter: email already exists. Please use a different email address.', 'schedula-smart-appointment-booking')], 400);
                }
            }

        } catch (Exception $e) {
            error_log('Schedula API Error (update_staff_member - data preparation): ' . $e->getMessage());
            return new WP_REST_Response(['message' => __('Invalid parameters provided.', 'schedula-smart-appointment-booking'), 'details' => $e->getMessage()], 400);
        }

        $updated = $wpdb->update($staff_table, $data, array('id' => $id));

        if ($updated === false) {
            error_log('Schedula API Error (update_staff_member - database update): ' . $wpdb->last_error);
            error_log('Data being updated: ' . print_r($data, true));
            return new WP_REST_Response(['message' => __('Failed to update staff member.', 'schedula-smart-appointment-booking'), 'db_error' => $wpdb->last_error], 500);
        } elseif ($updated == 0) {
            // Check if the staff member exists but no changes were made
            $exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$staff_table} WHERE id = %d", $id));
            if ($exists) {
                return new WP_REST_Response(['message' => __('Staff member found, but no changes were made.', 'schedula-smart-appointment-booking')], 200);
            } else {
                return new WP_REST_Response(['message' => __('Staff member not found.', 'schedula-smart-appointment-booking')], 404);
            }
        }

        $updated_staff_member = $this->get_staff_member_data($id);
        return new WP_REST_Response($updated_staff_member, 200);
    }

    /**
     * Delete a staff member.
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function delete_staff_member(WP_REST_Request $request)
    {
        global $wpdb;
        $staff_table = $this->db->get_table_name('staff');
        $appointments_table = $this->db->get_table_name('appointments');

        $id = (int) $request['id'];
        $force = (bool) $request->get_param('force');

        // Check for associated appointments
        $appointment_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$appointments_table} WHERE staff_id = %d", $id));

        // If force is false (first check before actual deletion)
        if (!$force) {
            if ($appointment_count > 0) {
                return new WP_REST_Response([
                    'message' => sprintf(
                        // translators: %d is the number of appointments associated with the staff member
                        __('This staff member is associated with %d appointment(s). Deleting the staff member will also delete these appointments. Are you sure you want to continue?', 'schedula-smart-appointment-booking'),
                        $appointment_count
                    ),
                    'code' => 'staff_has_appointments',
                    'data' => ['count' => $appointment_count]
                ], 409); // 409 Conflict to indicate a required confirmation
            } else {
                $staff_name = $wpdb->get_var($wpdb->prepare("SELECT name FROM {$staff_table} WHERE id = %d", $id));
                return new WP_REST_Response([
                    'message' => sprintf(
                        // translators: %s is the name of the staff member
                        __('Are you sure you want to delete staff member "%s"? This action cannot be undone.', 'schedula-smart-appointment-booking'),
                        $staff_name
                    ),
                    'code' => 'no_appointments_found'
                ], 200); // 200 OK for simple confirmation without conflicts
            }
        }

        // If force is true, proceed with actual deletion
        // Start a transaction for atomicity
        $wpdb->query('START TRANSACTION');

        try {
            // 1. Delete appointments associated with this staff member
            if ($appointment_count > 0) {
                // Get appointment IDs for further cascading deletes (e.g., customer_appointments, payments)
                $appointment_ids = $wpdb->get_col($wpdb->prepare("SELECT id FROM {$appointments_table} WHERE staff_id = %d", $id));

                if (!empty($appointment_ids)) {
                    $id_placeholders = implode(', ', array_fill(0, count($appointment_ids), '%d'));

                    // Delete associated customer_appointments
                    $customer_appointments_table = $this->db->get_table_name('customer_appointments');
                    $wpdb->query($wpdb->prepare("DELETE FROM {$customer_appointments_table} WHERE appointment_id IN ($id_placeholders)", $appointment_ids));
                    if ($wpdb->last_error) {
                        throw new \Exception(__('Failed to delete associated customer appointments: ', 'schedula-smart-appointment-booking') . $wpdb->last_error);
                    }

                    // Delete associated payments
                    $payments_table = $this->db->get_table_name('payments');
                    $wpdb->query($wpdb->prepare("DELETE FROM {$payments_table} WHERE appointment_id IN ($id_placeholders)", $appointment_ids));
                    if ($wpdb->last_error) {
                        throw new \Exception(__('Failed to delete associated payments: ', 'schedula-smart-appointment-booking') . $wpdb->last_error);
                    }
                }

                // Finally delete the appointments themselves
                $wpdb->delete($appointments_table, array('staff_id' => $id));
                if ($wpdb->last_error) {
                    throw new \Exception(__('Failed to delete associated appointments: ', 'schedula-smart-appointment-booking') . $wpdb->last_error);
                }
            }

            // 2. Delete associated staff_services
            $staff_services_table = $this->db->get_table_name('staff_services');
            $wpdb->delete($staff_services_table, array('staff_id' => $id));
            if ($wpdb->last_error) {
                throw new \Exception(__('Failed to delete associated staff services: ', 'schedula-smart-appointment-booking') . $wpdb->last_error);
            }

            // 3. Delete associated staff_schedule and their breaks
            $staff_schedule_table = $this->db->get_table_name('staff_schedule');
            $schedules = $wpdb->get_col($wpdb->prepare("SELECT id FROM {$staff_schedule_table} WHERE staff_id = %d", $id));
            if (!empty($schedules)) {
                $schedule_id_placeholders = implode(', ', array_fill(0, count($schedules), '%d'));
                $breaks_table = $this->db->get_table_name('schedule_item_breaks');
                $wpdb->query($wpdb->prepare("DELETE FROM {$breaks_table} WHERE schedule_item_id IN ($schedule_id_placeholders)", $schedules));
                if ($wpdb->last_error) {
                    throw new \Exception(__('Failed to delete associated schedule item breaks: ', 'schedula-smart-appointment-booking') . $wpdb->last_error);
                }
            }
            $wpdb->delete($staff_schedule_table, array('staff_id' => $id));
            if ($wpdb->last_error) {
                throw new \Exception(__('Failed to delete associated staff schedules: ', 'schedula-smart-appointment-booking') . $wpdb->last_error);
            }

            // 4. Delete associated holidays
            $holidays_table = $this->db->get_table_name('holidays');
            $wpdb->delete($holidays_table, array('staff_id' => $id));
            if ($wpdb->last_error) {
                throw new \Exception(__('Failed to delete associated staff holidays: ', 'schedula-smart-appointment-booking') . $wpdb->last_error);
            }

            // 5. Delete the staff member itself
            $staff_table = $this->db->get_table_name('staff');
            $deleted = $wpdb->delete($staff_table, array('id' => $id));

            if ($deleted === false) {
                throw new \Exception(__('Failed to delete staff member: ', 'schedula-smart-appointment-booking') . $wpdb->last_error);
            } elseif ($deleted === 0) {
                $wpdb->query('ROLLBACK');
                return new WP_REST_Response(['message' => __('Staff member not found or already deleted.', 'schedula-smart-appointment-booking')], 404);
            }

            $wpdb->query('COMMIT');
            return new WP_REST_Response(['message' => __('Staff member and all associated data deleted successfully.', 'schedula-smart-appointment-booking')], 200);

        } catch (\Exception $e) {
            $wpdb->query('ROLLBACK');
            error_log('Schedula API Error (delete_staff_member): ' . $e->getMessage());
            return new WP_REST_Response(['message' => __('Failed to delete staff member and its associated data. ', 'schedula-smart-appointment-booking') . $e->getMessage()], 500);
        }
    }

    /**
     * Get staff services.
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function get_staff_services(WP_REST_Request $request)
    {
        global $wpdb;
        $staff_id = (int) $request['staff_id'];
        $staff_services_table = $this->db->get_table_name('staff_services');
        $services_table = $this->db->get_table_name('services');

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT ss.service_id, s.title, ss.price, ss.duration
             FROM {$staff_services_table} ss
             JOIN {$services_table} s ON ss.service_id = s.id
             WHERE ss.staff_id = %d",
            $staff_id
        ), ARRAY_A);

        return new WP_REST_Response($results, 200);
    }

    /**
     * Add a service to a staff member.
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function add_staff_service(WP_REST_Request $request)
    {
        global $wpdb;
        $staff_services_table = $this->db->get_table_name('staff_services');
        $services_table = $this->db->get_table_name('services');
        $staff_id = (int) $request['staff_id'];
        $service_id = (int) $request['service_id'];
        $price = $request->get_param('price');
        $duration = $request->get_param('duration');

        // Check for existing entry
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$staff_services_table} WHERE staff_id = %d AND service_id = %d",
            $staff_id,
            $service_id
        ));

        if ($exists) {
            return new WP_REST_Response(['message' => __('This service is already assigned to this staff member.', 'schedula-smart-appointment-booking')], 409);
        }

        // If price or duration are not valid numbers, fetch default values from the service
        if (!is_numeric($price) || !is_numeric($duration)) {
            $default_service = $wpdb->get_row($wpdb->prepare(
                "SELECT price, duration FROM {$services_table} WHERE id = %d",
                $service_id
            ), ARRAY_A);

            if (!$default_service) {
                return new WP_REST_Response(['message' => __('Service not found to fetch default values.', 'schedula-smart-appointment-booking')], 404);
            }

            if (!is_numeric($price)) {
                $price = $default_service['price'];
            }
            if (!is_numeric($duration)) {
                $duration = $default_service['duration'];
            }
        }

        if (!is_numeric($price) || !is_numeric($duration) || $duration <= 0) {
            return new WP_REST_Response(['message' => __('Invalid or missing price/duration for the service.', 'schedula-smart-appointment-booking')], 400);
        }

        $data = [
            'staff_id' => $staff_id,
            'service_id' => $service_id,
            'price' => floatval($price),
            'duration' => absint($duration),
        ];

        $inserted = $wpdb->insert($staff_services_table, $data);

        if ($inserted === false) {
            return new WP_REST_Response(['message' => __('Failed to assign service to staff member.', 'schedula-smart-appointment-booking'), 'db_error' => $wpdb->last_error], 500);
        }

        return new WP_REST_Response(['message' => __('Service assigned to staff member successfully.', 'schedula-smart-appointment-booking')], 201);
    }

    /**
     * Update a staff service entry.
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function update_staff_service(WP_REST_Request $request)
    {
        global $wpdb;
        $staff_services_table = $this->db->get_table_name('staff_services');
        $services_table = $this->db->get_table_name('services');
        $staff_id = (int) $request['staff_id'];
        $service_id = (int) $request['service_id'];

        $data = [];
        $price = $request->get_param('price');
        $duration = $request->get_param('duration');

        // If price or duration are not set, fetch default values from the service
        if (!isset($price) || !isset($duration)) {
            $default_service = $wpdb->get_row($wpdb->prepare(
                "SELECT price, duration FROM {$services_table} WHERE id = %d",
                $service_id
            ), ARRAY_A);

            if (!$default_service) {
                return new WP_REST_Response(['message' => __('Service not found to fetch default values.', 'schedula-smart-appointment-booking')], 404);
            }

            if (!isset($price)) {
                $price = $default_service['price'];
            }
            if (!isset($duration)) {
                $duration = $default_service['duration'];
            }
        }

        // Now, validate and set the data
        if (is_numeric($price)) {
            $data['price'] = floatval($price);
        } else {
            return new WP_REST_Response(['message' => __('Invalid parameter(s): price must be a number.', 'schedula-smart-appointment-booking')], 400);
        }

        if (is_numeric($duration) && $duration > 0) {
            $data['duration'] = absint($duration);
        } else {
            return new WP_REST_Response(['message' => __('Invalid parameter(s): duration must be a positive number.', 'schedula-smart-appointment-booking')], 400);
        }

        if (empty($data)) {
            return new WP_REST_Response(['message' => __('No data provided for update.', 'schedula-smart-appointment-booking')], 400);
        }

        $updated = $wpdb->update(
            $staff_services_table,
            $data,
            ['staff_id' => $staff_id, 'service_id' => $service_id]
        );

        if ($updated === false) {
            return new WP_REST_Response(['message' => __('Failed to update staff service.', 'schedula-smart-appointment-booking'), 'db_error' => $wpdb->last_error], 500);
        } elseif ($updated == 0) {
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$staff_services_table} WHERE staff_id = %d AND service_id = %d",
                $staff_id,
                $service_id
            ));
            if ($exists) {
                return new WP_REST_Response(['message' => __('Staff service found, but no changes were made.', 'schedula-smart-appointment-booking')], 200);
            } else {
                return new WP_REST_Response(['message' => __('Staff service not found.', 'schedula-smart-appointment-booking')], 404);
            }
        }

        return new WP_REST_Response(['message' => __('Staff service updated successfully.', 'schedula-smart-appointment-booking')], 200);
    }

    /**
     * Delete a staff service entry.
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function delete_staff_service(WP_REST_Request $request)
    {
        global $wpdb;
        $staff_services_table = $this->db->get_table_name('staff_services');
        $staff_id = (int) $request['staff_id'];
        $service_id = (int) $request['service_id'];

        $deleted = $wpdb->delete(
            $staff_services_table,
            array('staff_id' => $staff_id, 'service_id' => $service_id)
        );

        if ($deleted === false) {
            return new WP_REST_Response(['message' => __('Failed to delete staff service.', 'schedula-smart-appointment-booking'), 'db_error' => $wpdb->last_error], 500);
        } elseif ($deleted == 0) {
            return new WP_REST_Response(['message' => __('Staff service not found or already deleted.', 'schedula-smart-appointment-booking')], 404);
        }

        return new WP_REST_Response(['message' => __('Service deleted successfully.', 'schedula-smart-appointment-booking')], 200);
    }

    /**
     * Get staff schedule.
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function get_staff_schedule(WP_REST_Request $request)
    {
        global $wpdb;
        $staff_id = (int) $request['staff_id'];
        $staff_schedule_table = $this->db->get_table_name('staff_schedule');
        $schedule_item_breaks_table = $this->db->get_table_name('schedule_item_breaks');

        $schedules = $wpdb->get_results($wpdb->prepare(
            "SELECT id, day_of_week, start_time, end_time FROM {$staff_schedule_table} WHERE staff_id = %d ORDER BY day_of_week ASC, start_time ASC",
            $staff_id
        ), ARRAY_A);

        // Fetch breaks for each schedule item
        foreach ($schedules as &$schedule) {
            $schedule['breaks'] = $wpdb->get_results($wpdb->prepare(
                "SELECT id, start_time, end_time FROM {$schedule_item_breaks_table} WHERE schedule_item_id = %d ORDER BY start_time ASC",
                $schedule['id']
            ), ARRAY_A);
        }

        return new WP_REST_Response($schedules, 200);
    }

    /**
     * Add staff schedule.
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function add_staff_schedule(WP_REST_Request $request)
    {
        global $wpdb;
        $staff_schedule_table = $this->db->get_table_name('staff_schedule');
        $staff_id = (int) $request['staff_id'];

        $data = $this->prepare_item_for_database($request, $this->get_staff_schedule_args());
        $data['staff_id'] = $staff_id;

        // Check for overlapping schedules for the same staff and day
        $existing_schedules = $wpdb->get_results($wpdb->prepare(
            "SELECT id FROM {$staff_schedule_table}
             WHERE staff_id = %d AND day_of_week = %d
               AND (
                    (%s < end_time AND %s > start_time) OR
                    (%s < end_time AND %s > start_time) OR
                    (%s <= start_time AND %s >= end_time)
                   )",
            $staff_id,
            $data['day_of_week'],
            $data['start_time'],
            $data['end_time'],
            $data['end_time'],
            $data['start_time'],
            $data['start_time'],
            $data['end_time']
        ));

        if (!empty($existing_schedules)) {
            return new WP_REST_Response(['message' => __('Overlapping schedule entry for this staff member and day.', 'schedula-smart-appointment-booking')], 409);
        }

        $inserted = $wpdb->insert($staff_schedule_table, $data);

        if ($inserted === false) {
            return new WP_REST_Response(['message' => __('Failed to add staff schedule.', 'schedula-smart-appointment-booking'), 'db_error' => $wpdb->last_error], 500);
        }

        $data['id'] = $wpdb->insert_id;
        return new WP_REST_Response($data, 201);
    }

    /**
     * Update staff schedule.
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function update_staff_schedule(WP_REST_Request $request)
    {
        global $wpdb;
        $staff_schedule_table = $this->db->get_table_name('staff_schedule');
        $staff_id = (int) $request['staff_id'];
        $id = (int) $request['id'];

        $data = $this->prepare_item_for_database($request, $this->get_staff_schedule_args(true), true);

        if (empty($data)) {
            return new WP_REST_Response(['message' => __('No data provided for update.', 'schedula-smart-appointment-booking')], 400);
        }

        // Check for overlapping schedules (excluding the current one being updated)
        $current_schedule = $wpdb->get_row($wpdb->prepare(
            "SELECT day_of_week, start_time, end_time FROM {$staff_schedule_table} WHERE id = %d AND staff_id = %d",
            $id,
            $staff_id
        ), ARRAY_A);

        if (!$current_schedule) {
            return new WP_REST_Response(['message' => __('Schedule entry not found for this staff member.', 'schedula-smart-appointment-booking')], 404);
        }

        $day_of_week = isset($data['day_of_week']) ? $data['day_of_week'] : $current_schedule['day_of_week'];
        $start_time = isset($data['start_time']) ? $data['start_time'] : $current_schedule['start_time'];
        $end_time = isset($data['end_time']) ? $data['end_time'] : $current_schedule['end_time'];

        $overlapping_schedules = $wpdb->get_results($wpdb->prepare(
            "SELECT id FROM {$staff_schedule_table}
             WHERE staff_id = %d AND day_of_week = %d AND id != %d
               AND (
                    (%s < end_time AND %s > start_time) OR
                    (%s < end_time AND %s > start_time) OR
                    (%s <= start_time AND %s >= end_time)
                   )",
            $staff_id,
            $day_of_week,
            $id,
            $start_time,
            $end_time,
            $end_time,
            $start_time,
            $start_time,
            $end_time
        ));

        if (!empty($overlapping_schedules)) {
            return new WP_REST_Response(['message' => __('Overlapping schedule entry for this staff member and day.', 'schedula-smart-appointment-booking')], 409);
        }

        $updated = $wpdb->update($staff_schedule_table, $data, array('id' => $id, 'staff_id' => $staff_id));

        if ($updated === false) {
            return new WP_REST_Response(['message' => __('Failed to update staff schedule.', 'schedula-smart-appointment-booking'), 'db_error' => $wpdb->last_error], 500);
        } elseif ($updated == 0) {
            return new WP_REST_Response(['message' => __('Staff schedule found, but no changes were made or record not found.', 'schedula-smart-appointment-booking')], 200);
        }

        $updated_schedule = $wpdb->get_row($wpdb->prepare(
            "SELECT id, day_of_week, start_time, end_time FROM {$staff_schedule_table} WHERE id = %d",
            $id
        ), ARRAY_A);

        return new WP_REST_Response(['message' => __('Staff schedule updated successfully.', 'schedula-smart-appointment-booking'), 'data' => $updated_schedule], 200);
    }

    /**
     * Delete staff schedule.
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function delete_staff_schedule(WP_REST_Request $request)
    {
        global $wpdb;
        $staff_schedule_table = $this->db->get_table_name('staff_schedule');
        $schedule_item_breaks_table = $this->db->get_table_name('schedule_item_breaks');
        $staff_id = (int) $request['staff_id'];
        $id = (int) $request['id'];

        // Start a transaction
        $wpdb->query('START TRANSACTION');

        try {
            // Delete associated breaks first
            $wpdb->delete($schedule_item_breaks_table, array('schedule_item_id' => $id));
            if ($wpdb->last_error) {
                throw new \Exception(__('Failed to delete associated schedule item breaks: ', 'schedula-smart-appointment-booking') . $wpdb->last_error);
            }

            // Then delete the schedule item
            $deleted = $wpdb->delete($staff_schedule_table, array('id' => $id, 'staff_id' => $staff_id));

            if ($deleted === false) {
                throw new \Exception(__('Failed to delete staff schedule: ', 'schedula-smart-appointment-booking') . $wpdb->last_error);
            } elseif ($deleted == 0) {
                $wpdb->query('ROLLBACK');
                return new WP_REST_Response(['message' => __('Staff schedule not found or already deleted.', 'schedula-smart-appointment-booking')], 404);
            }

            $wpdb->query('COMMIT');
            return new WP_REST_Response(['message' => __('Staff schedule and associated breaks deleted successfully.', 'schedula-smart-appointment-booking')], 200);

        } catch (\Exception $e) {
            $wpdb->query('ROLLBACK');
            error_log('Schedula API Error (delete_staff_schedule): ' . $e->getMessage());
            return new WP_REST_Response(['message' => __('Failed to delete staff schedule and its associated data. ', 'schedula-smart-appointment-booking') . $e->getMessage()], 500);
        }
    }

    /**
     * Get schedule item breaks.
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function get_schedule_item_breaks(WP_REST_Request $request)
    {
        global $wpdb;
        $schedule_item_id = (int) $request['schedule_item_id'];
        $schedule_item_breaks_table = $this->db->get_table_name('schedule_item_breaks');

        $breaks = $wpdb->get_results($wpdb->prepare(
            "SELECT id, start_time, end_time FROM {$schedule_item_breaks_table} WHERE schedule_item_id = %d ORDER BY start_time ASC",
            $schedule_item_id
        ), ARRAY_A);

        return new WP_REST_Response($breaks, 200);
    }

    /**
     * Add schedule item break.
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function add_schedule_item_break(WP_REST_Request $request)
    {
        global $wpdb;
        $schedule_item_breaks_table = $this->db->get_table_name('schedule_item_breaks');
        $schedule_item_id = (int) $request['schedule_item_id'];

        $data = $this->prepare_item_for_database($request, $this->get_schedule_item_break_args());
        $data['schedule_item_id'] = $schedule_item_id;

        // Check for overlapping breaks within the same schedule item
        $existing_breaks = $wpdb->get_results($wpdb->prepare(
            "SELECT id FROM {$schedule_item_breaks_table}
             WHERE schedule_item_id = %d
               AND (
                    (%s < end_time AND %s > start_time) OR
                    (%s < end_time AND %s > start_time) OR
                    (%s <= start_time AND %s >= end_time)
                   )",
            $schedule_item_id,
            $data['start_time'],
            $data['end_time'],
            $data['end_time'],
            $data['start_time'],
            $data['start_time'],
            $data['end_time']
        ));

        if (!empty($existing_breaks)) {
            return new WP_REST_Response(['message' => __('Overlapping break entry for this schedule item.', 'schedula-smart-appointment-booking')], 409);
        }

        $inserted = $wpdb->insert($schedule_item_breaks_table, $data);

        if ($inserted === false) {
            return new WP_REST_Response(['message' => __('Failed to add schedule item break.', 'schedula-smart-appointment-booking'), 'db_error' => $wpdb->last_error], 500);
        }

        $data['id'] = $wpdb->insert_id;
        return new WP_REST_Response(['message' => __('Schedule item break added successfully.', 'schedula-smart-appointment-booking'), 'data' => $data], 201);
    }

    /**
     * Update schedule item break.
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function update_schedule_item_break(WP_REST_Request $request)
    {
        global $wpdb;
        $schedule_item_breaks_table = $this->db->get_table_name('schedule_item_breaks');
        $schedule_item_id = (int) $request['schedule_item_id'];
        $id = (int) $request['id'];

        $data = $this->prepare_item_for_database($request, $this->get_schedule_item_break_args(true), true);

        if (empty($data)) {
            return new WP_REST_Response(['message' => __('No data provided for update.', 'schedula-smart-appointment-booking')], 400);
        }

        // Check for overlapping breaks (excluding the current one being updated)
        $current_break = $wpdb->get_row($wpdb->prepare(
            "SELECT start_time, end_time FROM {$schedule_item_breaks_table} WHERE id = %d AND schedule_item_id = %d",
            $id,
            $schedule_item_id
        ), ARRAY_A);

        if (!$current_break) {
            return new WP_REST_Response(['message' => __('Break entry not found for this schedule item.', 'schedula-smart-appointment-booking')], 404);
        }

        $start_time = isset($data['start_time']) ? $data['start_time'] : $current_break['start_time'];
        $end_time = isset($data['end_time']) ? $data['end_time'] : $current_break['end_time'];

        $overlapping_breaks = $wpdb->get_results($wpdb->prepare(
            "SELECT id FROM {$schedule_item_breaks_table}
             WHERE schedule_item_id = %d AND id != %d
               AND (
                    (%s < end_time AND %s > start_time) OR
                    (%s < end_time AND %s > start_time) OR
                    (%s <= start_time AND %s >= end_time)
                   )",
            $schedule_item_id,
            $id,
            $start_time,
            $end_time,
            $end_time,
            $start_time,
            $start_time,
            $end_time
        ));

        if (!empty($overlapping_breaks)) {
            return new WP_REST_Response(['message' => __('Overlapping break entry for this schedule item.', 'schedula-smart-appointment-booking')], 409);
        }

        $updated = $wpdb->update($schedule_item_breaks_table, $data, array('id' => $id, 'schedule_item_id' => $schedule_item_id));

        if ($updated === false) {
            return new WP_REST_Response(['message' => __('Failed to update schedule item break.', 'schedula-smart-appointment-booking'), 'db_error' => $wpdb->last_error], 500);
        } elseif ($updated == 0) {
            return new WP_REST_Response(['message' => __('Schedule item break found, but no changes were made or record not found.', 'schedula-smart-appointment-booking')], 200);
        }

        $updated_break = $wpdb->get_row($wpdb->prepare(
            "SELECT id, start_time, end_time FROM {$schedule_item_breaks_table} WHERE id = %d",
            $id
        ), ARRAY_A);

        return new WP_REST_Response(['message' => __('Schedule item break updated successfully.', 'schedula-smart-appointment-booking'), 'data' => $updated_break], 200);
    }

    /**
     * Delete schedule item break.
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function delete_schedule_item_break(WP_REST_Request $request)
    {
        global $wpdb;
        $schedule_item_breaks_table = $this->db->get_table_name('schedule_item_breaks');
        $schedule_item_id = (int) $request['schedule_item_id'];
        $id = (int) $request['id'];

        $deleted = $wpdb->delete($schedule_item_breaks_table, array('id' => $id, 'schedule_item_id' => $schedule_item_id));

        if ($deleted === false) {
            return new WP_REST_Response(['message' => __('Failed to delete schedule item break.', 'schedula-smart-appointment-booking'), 'db_error' => $wpdb->last_error], 500);
        } elseif ($deleted == 0) {
            return new WP_REST_Response(['message' => __('Schedule item break not found or already deleted.', 'schedula-smart-appointment-booking')], 404);
        }

        return new WP_REST_Response(['message' => __('Schedule item break deleted successfully.', 'schedula-smart-appointment-booking')], 200);
    }

    /**
     * Get holidays.
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function get_holidays(WP_REST_Request $request)
    {
        global $wpdb;
        $holidays_table = $this->db->get_table_name('holidays');
        $staff_id = $request->get_param('staff_id');
        $start_date = $request->get_param('start_date');
        $end_date = $request->get_param('end_date');

        $sql = "SELECT id, staff_id, start_date, end_date, description FROM {$holidays_table}";
        $where_clauses = [];
        $params = [];

        if (!empty($staff_id)) {
            $where_clauses[] = "staff_id = %d";
            $params[] = $staff_id;
        }
        if (!empty($start_date)) {
            $where_clauses[] = "start_date >= %s";
            $params[] = $start_date;
        }
        if (!empty($end_date)) {
            $where_clauses[] = "end_date <= %s";
            $params[] = $end_date;
        }

        if (!empty($where_clauses)) {
            $sql .= " WHERE " . implode(" AND ", $where_clauses);
        }

        $sql .= " ORDER BY start_date ASC";

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        $holidays = $wpdb->get_results($wpdb->prepare($sql, ...$params), ARRAY_A);

        return new WP_REST_Response($holidays, 200);
    }

    /**
     * Create a holiday.
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function create_holiday(WP_REST_Request $request)
    {
        global $wpdb;
        $holidays_table = $this->db->get_table_name('holidays');

        $data = $this->prepare_item_for_database($request, $this->get_holiday_args());

        // Check for overlapping holidays for the same staff
        $existing_holidays = $wpdb->get_results($wpdb->prepare(
            "SELECT id FROM {$holidays_table}
             WHERE staff_id = %d
               AND (
                    (%s <= end_date AND %s >= start_date)
                   )",
            $data['staff_id'],
            $data['start_date'],
            $data['end_date']
        ));

        if (!empty($existing_holidays)) {
            return new WP_REST_Response(['message' => __('Overlapping holiday entry for this staff member.', 'schedula-smart-appointment-booking')], 409);
        }

        $inserted = $wpdb->insert($holidays_table, $data);

        if ($inserted === false) {
            return new WP_REST_Response(['message' => __('Failed to create holiday.', 'schedula-smart-appointment-booking'), 'db_error' => $wpdb->last_error], 500);
        }

        $data['id'] = $wpdb->insert_id;
        return new WP_REST_Response(['message' => __('Holiday created successfully.', 'schedula-smart-appointment-booking'), 'data' => $data], 201);
    }

    /**
     * Update a holiday.
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function update_holiday(WP_REST_Request $request)
    {
        global $wpdb;
        $holidays_table = $this->db->get_table_name('holidays');
        $id = (int) $request['id'];

        $data = $this->prepare_item_for_database($request, $this->get_holiday_args(true), true);

        if (empty($data)) {
            return new WP_REST_Response(['message' => __('No data provided for update.', 'schedula-smart-appointment-booking')], 400);
        }

        // Check for overlapping holidays (excluding the current one being updated)
        $current_holiday = $wpdb->get_row($wpdb->prepare(
            "SELECT staff_id, start_date, end_date FROM {$holidays_table} WHERE id = %d",
            $id
        ), ARRAY_A);

        if (!$current_holiday) {
            return new WP_REST_Response(['message' => __('Holiday not found.', 'schedula-smart-appointment-booking')], 404);
        }

        $staff_id = isset($data['staff_id']) ? $data['staff_id'] : $current_holiday['staff_id'];
        $start_date = isset($data['start_date']) ? $data['start_date'] : $current_holiday['start_date'];
        $end_date = isset($data['end_date']) ? $data['end_date'] : $current_holiday['end_date'];

        $overlapping_holidays = $wpdb->get_results($wpdb->prepare(
            "SELECT id FROM {$holidays_table}
             WHERE staff_id = %d AND id != %d
               AND (
                    (%s <= end_date AND %s >= start_date)
                   )",
            $staff_id,
            $id,
            $start_date,
            $end_date
        ));

        if (!empty($overlapping_holidays)) {
            return new WP_REST_Response(['message' => __('Overlapping holiday entry for this staff member.', 'schedula-smart-appointment-booking')], 409);
        }

        $updated = $wpdb->update($holidays_table, $data, array('id' => $id));

        if ($updated === false) {
            return new WP_REST_Response(['message' => __('Failed to update holiday.', 'schedula-smart-appointment-booking'), 'db_error' => $wpdb->last_error], 500);
        } elseif ($updated == 0) {
            return new WP_REST_Response(['message' => __('Holiday found, but no changes were made or record not found.', 'schedula-smart-appointment-booking')], 200);
        }

        $updated_holiday = $wpdb->get_row($wpdb->prepare(
            "SELECT id, staff_id, start_date, end_date, description FROM {$holidays_table} WHERE id = %d",
            $id
        ), ARRAY_A);

        return new WP_REST_Response(['message' => __('Holiday updated successfully.', 'schedula-smart-appointment-booking'), 'data' => $updated_holiday], 200);
    }

    /**
     * Delete a holiday.
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function delete_holiday(WP_REST_Request $request)
    {
        global $wpdb;
        $holidays_table = $this->db->get_table_name('holidays');
        $id = (int) $request['id'];

        $deleted = $wpdb->delete($holidays_table, array('id' => $id));

        if ($deleted === false) {
            return new WP_REST_Response(['message' => __('Failed to delete holiday.', 'schedula-smart-appointment-booking'), 'db_error' => $wpdb->last_error], 500);
        } elseif ($deleted == 0) {
            return new WP_REST_Response(['message' => __('Holiday not found or already deleted.', 'schedula-smart-appointment-booking')], 404);
        }

        return new WP_REST_Response(['message' => __('Holiday deleted successfully.', 'schedula-smart-appointment-booking')], 200);
    }

    /**
     * Prepare item for database insertion/update.
     * FIXED: Enhanced error handling and validation
     * @param WP_REST_Request $request The request object.
     * @param array $args The arguments schema for the endpoint.
     * @param bool $for_update Whether preparing for an update (makes all fields optional).
     * @return array The prepared data.
     */
    protected function prepare_item_for_database(WP_REST_Request $request, $args, $for_update = false)
    {
        $data = [];
        $validation_errors = [];

        foreach ($args as $key => $arg_data) {
            $value = $request->get_param($key);
            $sanitize_callback = isset($arg_data['sanitize_callback']) ? $arg_data['sanitize_callback'] : null;
            $validate_callback = isset($arg_data['validate_callback']) ? $arg_data['validate_callback'] : null;
            $required = isset($arg_data['required']) ? $arg_data['required'] : false;

            // Skip if not provided and not required for update, or not provided for creation
            if (($required && !$for_update && !isset($value)) || ($for_update && !array_key_exists($key, $request->get_params()))) {
                if ($required && !$for_update) {
                    // translators: %s is the name of the parameter
                    $validation_errors[] = sprintf(__('Missing required parameter: %s', 'schedula-smart-appointment-booking'), $key);
                }
                continue;
            }

            // Handle validation
            if ($validate_callback && isset($value)) {
                if (!call_user_func($validate_callback, $value, $request, $key)) {
                    // translators: %s is the name of the parameter
                    $validation_errors[] = sprintf(__('Invalid parameter: %s', 'schedula-smart-appointment-booking'), $key);
                    continue;
                }
            }

            // Sanitize the value if provided
            if (isset($value)) {
                if ($sanitize_callback) {
                    $sanitized_value = call_user_func($sanitize_callback, $value);
                    // Additional check for email sanitization
                    if ($key === 'email' && empty($sanitized_value)) {
                        // translators: %s is the name of the parameter
                        $validation_errors[] = sprintf(__('Invalid parameter: %s (email format is invalid)', 'schedula-smart-appointment-booking'), $key);
                        continue;
                    }
                    $data[$key] = $sanitized_value;
                } else {
                    $data[$key] = $value;
                }
            }
        }

        // Throw exception if there are validation errors
        if (!empty($validation_errors)) {
            $safe_errors = array_map('esc_html', $validation_errors);
            // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
            throw new \Exception(implode(', ', $safe_errors));
        }

        // Add created_at/updated_at for new records
        if (!$for_update) {
            $data['created_at'] = current_time('mysql');
        }
        $data['updated_at'] = current_time('mysql');

        return $data;
    }

    /**
     * Helper to retrieve staff member data.
     * @param int $id Staff member ID.
     * @return array|null Staff member data or null if not found.
     */
    private function get_staff_member_data($id)
    {
        global $wpdb;
        $staff_table = $this->db->get_table_name('staff');
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$staff_table} WHERE id = %d",
            $id
        ), ARRAY_A);
    }

    /**
     * Get days when a staff member (or any staff) is unavailable due to holidays or no schedule.
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function get_staff_unavailable_days(WP_REST_Request $request)
    {
        global $wpdb;
        $staff_id = (int) $request['staff_id'];
        $start_date_str = sanitize_text_field($request['start_date']);
        $end_date_str = sanitize_text_field($request['end_date']);

        $holidays_table = $this->db->get_table_name('holidays');
        $staff_schedule_table = $this->db->get_table_name('staff_schedule');

        $unavailable_dates = [];

        // Fetch holidays for the staff member (or general holidays) within the date range
        $holidays = $wpdb->get_results($wpdb->prepare(
            "SELECT start_date, end_date FROM {$holidays_table}
             WHERE (staff_id = %d OR staff_id = 0)
             AND (start_date <= %s AND end_date >= %s)",
            $staff_id,
            $end_date_str,
            $start_date_str
        ), ARRAY_A);

        // Add all dates within holiday ranges to unavailable_dates
        foreach ($holidays as $holiday) {
            $current_date = new \DateTime($holiday['start_date']);
            $end_date = new \DateTime($holiday['end_date']);
            while ($current_date <= $end_date) {
                $unavailable_dates[$current_date->format('Y-m-d')] = true;
                $current_date->modify('+1 day');
            }
        }

        // Determine working days from schedules
        $working_days_of_week = [];
        $schedule_days = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT day_of_week FROM {$staff_schedule_table}
             WHERE (staff_id = %d OR staff_id = 0)",
            $staff_id
        ));
        foreach ($schedule_days as $day) {
            $working_days_of_week[] = (int) $day;
        }

        // Iterate through the date range and mark days without schedules as unavailable
        $current_date_loop = new \DateTime($start_date_str);
        $end_date_loop = new \DateTime($end_date_str);

        while ($current_date_loop <= $end_date_loop) {
            $day_of_week = (int) $current_date_loop->format('w');
            $date_str = $current_date_loop->format('Y-m-d');

            // If a day has no schedule for the selected staff (or any staff if staff_id is 0)
            if (!in_array($day_of_week, $working_days_of_week)) {
                $unavailable_dates[$date_str] = true;
            }

            $current_date_loop->modify('+1 day');
        }

        // Return unique unavailable dates as an array of strings
        return new WP_REST_Response(array_keys($unavailable_dates), 200);
    }

    /**
     * Get working hours for a staff member on a specific date.
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function get_staff_working_hours(WP_REST_Request $request)
    {
        global $wpdb;
        $staff_id = (int) $request['staff_id'];
        $date_str = sanitize_text_field($request['date']);

        try {
            $date = new \DateTime($date_str);
        } catch (\Exception $e) {
            return new WP_REST_Response(['message' => __('Invalid date format. Please use YYYY-MM-DD.', 'schedula-smart-appointment-booking')], 400);
        }

        $day_of_week = (int) $date->format('w');

        $holidays_table = $this->db->get_table_name('holidays');
        $staff_schedule_table = $this->db->get_table_name('staff_schedule');
        $schedule_item_breaks_table = $this->db->get_table_name('schedule_item_breaks');

        // 1. Check for holidays
        $is_holiday = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$holidays_table}
             WHERE (staff_id = %d OR staff_id = 0)
             AND (%s BETWEEN start_date AND end_date)",
            $staff_id,
            $date_str
        ));

        if ($is_holiday > 0) {
            return new WP_REST_Response([], 200); // Return empty array on holiday
        }

        // 2. Get schedule for the day
        $schedules = $wpdb->get_results($wpdb->prepare(
            "SELECT id, start_time, end_time FROM {$staff_schedule_table}
             WHERE staff_id = %d AND day_of_week = %d
             ORDER BY start_time ASC",
            $staff_id,
            $day_of_week
        ), ARRAY_A);

        if (empty($schedules)) {
            return new WP_REST_Response([], 200); // Return empty array if no schedule
        }

        $working_hours = [];

        foreach ($schedules as $schedule) {
            // Get breaks for this schedule item
            $breaks = $wpdb->get_results($wpdb->prepare(
                "SELECT start_time, end_time FROM {$schedule_item_breaks_table}
                 WHERE schedule_item_id = %d
                 ORDER BY start_time ASC",
                $schedule['id']
            ), ARRAY_A);

            $last_end_time = $schedule['start_time'];

            foreach ($breaks as $break) {
                if ($break['start_time'] > $last_end_time) {
                    $working_hours[] = [
                        'start_time' => $last_end_time,
                        'end_time' => $break['start_time'],
                    ];
                }
                $last_end_time = $break['end_time'];
            }

            if ($schedule['end_time'] > $last_end_time) {
                $working_hours[] = [
                    'start_time' => $last_end_time,
                    'end_time' => $schedule['end_time'],
                ];
            }
        }

        return new WP_REST_Response($working_hours, 200);
    }
}