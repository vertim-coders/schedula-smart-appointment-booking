<?php

/**
 * REST API Endpoints for Schedula Services.
 * Handles CRUD operations for the 'schesab_services' database table.
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

class SCHESAB_Services
{

    private $namespace = 'schesab/v1';
    private $db;

    /**
     * Constructor.
     * Hooks into 'rest_api_init' to register custom REST API routes.
     */
    public function __construct()
    {
        $this->db = \SCHESAB\Database\SCHESAB_Database::get_instance();
    }

    /**
     * Registers custom REST API routes for services.
     */
    public function register_routes()
    {
        $categories_table = $this->db->get_table_name('categories');

        // Route for fetching all services (GET) - Updated with pagination support
        register_rest_route($this->namespace, '/services', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_services'],
            'permission_callback' => '__return_true',
            'args' => [
                'search' => [
                    'sanitize_callback' => 'sanitize_text_field',
                    'required' => false,
                    'description' => __('Search term for service title or description.', 'schedula-smart-appointment-booking'),
                ],
                'page' => [ // Added for pagination
                    'sanitize_callback' => 'absint',
                    'default' => 1,
                    'required' => false,
                    'description' => __('Current page number.', 'schedula-smart-appointment-booking'),
                ],
                'per_page' => [ // Added for pagination
                    'sanitize_callback' => 'absint',
                    'default' => 10, // Default items per page
                    'required' => false,
                    'description' => __('Number of items per page.', 'schedula-smart-appointment-booking'),
                ],
                'sort_by' => [ // Added for sorting
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => function ($param) {
                        return in_array($param, ['id', 'title', 'duration', 'price', 'category_id']);
                    },
                    'default' => 'title',
                    'required' => false,
                    'description' => __('Column to sort services by (id, title, duration, price, category_id).', 'schedula-smart-appointment-booking'),
                ],
                'sort_direction' => [ // Added for sorting
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => function ($param) {
                        return in_array(strtolower($param), ['asc', 'desc']);
                    },
                    'default' => 'asc',
                    'required' => false,
                    'description' => __('Sorting direction (asc or desc).', 'schedula-smart-appointment-booking'),
                ],
            ],
        ]);

        // Route for creating a new service (POST)
        register_rest_route($this->namespace, '/services', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'create_service'],
            'permission_callback' => [$this, 'admin_permissions_check'],
            'args' => [
                'category_id' => [
                    'required' => true,
                    'sanitize_callback' => function ($value) {
                        return absint($value);
                    },
                    'validate_callback' => function ($value) use ($categories_table) {
                        global $wpdb;
                        return is_numeric($value) && $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $categories_table WHERE id = %d", $value));
                    },
                    'description' => __('ID of the category this service belongs to.', 'schedula-smart-appointment-booking'),
                    'type' => 'integer',
                ],
                'title' => [
                    'required' => true,
                    'sanitize_callback' => function ($value) {
                        return sanitize_text_field($value);
                    },
                    'validate_callback' => function ($value) {
                        return !empty($value);
                    },
                    'description' => __('Title of the service.', 'schedula-smart-appointment-booking'),
                    'type' => 'string',
                ],
                'description' => [
                    'sanitize_callback' => function ($value) {
                        return sanitize_textarea_field($value);
                    },
                    'required' => false,
                    'description' => __('Description of the service.', 'schedula-smart-appointment-booking'),
                    'type' => 'string',
                ],
                'duration' => [
                    'required' => true,
                    'sanitize_callback' => function ($value) {
                        return absint($value);
                    },
                    'validate_callback' => function ($value) {
                        return is_numeric($value) && $value > 0;
                    },
                    'description' => __('Duration of the service in minutes.', 'schedula-smart-appointment-booking'),
                    'type' => 'integer',
                ],
                'price' => [
                    'required' => true,
                    'sanitize_callback' => function ($value) {
                        return floatval($value);
                    },
                    'validate_callback' => function ($value) {
                        return is_numeric($value) && $value >= 0;
                    },
                    'description' => __('Price of the service.', 'schedula-smart-appointment-booking'),
                    'type' => 'number',
                ],
                'image_url' => [
                    'sanitize_callback' => 'esc_url_raw',
                    'required' => false,
                    'description' => __('URL of the service image.', 'schedula-smart-appointment-booking'),
                    'type' => 'string',
                ],
            ],
        ]);

        // Route for fetching a single service by ID (GET)
        register_rest_route($this->namespace, '/services/(?P<id>\d+)', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_service'],
            'permission_callback' => '__return_true',
            'args' => [
                'id' => [
                    'validate_callback' => function ($param, $request, $key) {
                        return is_numeric($param);
                    },
                    'type' => 'integer',
                ],
            ],
        ]);

        // Route for updating an existing service by ID (PUT/PATCH)
        register_rest_route($this->namespace, '/services/(?P<id>\d+)', [
            'methods' => WP_REST_Server::EDITABLE,
            'callback' => [$this, 'update_service'],
            'permission_callback' => [$this, 'admin_permissions_check'],
            'args' => [
                'id' => [
                    'validate_callback' => function ($param, $request, $key) {
                        return is_numeric($param);
                    },
                    'type' => 'integer',
                ],
                'category_id' => [
                    'required' => true,
                    'sanitize_callback' => function ($value) {
                        return absint($value);
                    },
                    'validate_callback' => function ($value) use ($categories_table) {
                        global $wpdb;
                        return is_numeric($value) && $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $categories_table WHERE id = %d", $value));
                    },
                    'type' => 'integer',
                ],
                'title' => [
                    'required' => true,
                    'sanitize_callback' => function ($value) {
                        return sanitize_text_field($value);
                    },
                    'validate_callback' => function ($value) {
                        return !empty($value);
                    },
                    'description' => __('Title of the service.', 'schedula-smart-appointment-booking'),
                    'type' => 'string',
                ],
                'description' => [
                    'sanitize_callback' => function ($value) {
                        return sanitize_textarea_field($value);
                    },
                    'required' => false,
                    'type' => 'string',
                ],
                'duration' => [
                    'required' => true,
                    'sanitize_callback' => function ($value) {
                        return absint($value);
                    },
                    'validate_callback' => function ($value) {
                        return is_numeric($value) && $value > 0;
                    },
                    'description' => __('Duration of the service in minutes.', 'schedula-smart-appointment-booking'),
                    'type' => 'integer',
                ],
                'price' => [
                    'required' => true,
                    'sanitize_callback' => function ($value) {
                        return floatval($value);
                    },
                    'validate_callback' => function ($value) {
                        return is_numeric($value) && $value >= 0;
                    },
                    'description' => __('Price of the service.', 'schedula-smart-appointment-booking'),
                    'type' => 'number',
                ],
                'image_url' => [
                    'sanitize_callback' => 'esc_url_raw',
                    'required' => false,
                    'description' => __('URL of the service image.', 'schedula-smart-appointment-booking'),
                    'type' => 'string',
                ],
            ],
        ]);

        // Route for deleting a service by ID (DELETE)
        register_rest_route($this->namespace, '/services/(?P<id>\d+)', [
            'methods' => WP_REST_Server::DELETABLE,
            'callback' => [$this, 'delete_service'],
            'permission_callback' => [$this, 'admin_permissions_check'],
            'args' => [
                'id' => [
                    'validate_callback' => function ($param, $request, $key) {
                        return is_numeric($param);
                    },
                    'type' => 'integer',
                ],
            ],
        ]);
    }

    /**
     * Permission callback to check if the current user has 'manage_options' capability.
     */
    public function admin_permissions_check(WP_REST_Request $request)
    {
        return current_user_can('manage_options');
    }

    /**
     * Retrieves all services from the database with pagination support.
     * Includes search functionality by title or description.
     *
     * @param WP_REST_Request $request The request object.
     * @return WP_REST_Response A response object containing services or an error.
     */
    public function get_services(WP_REST_Request $request)
    {
        global $wpdb;
        $services_table = $this->db->get_table_name('services');
        $categories_table = $this->db->get_table_name('categories');

        $search_term = $request->get_param('search');
        $page = $request->get_param('page');
        $per_page_param = $request->get_param('per_page');
        $sort_by = $request->get_param('sort_by');
        $sort_direction = strtoupper($request->get_param('sort_direction') ?? '');

        // Define allowed sortable columns to prevent SQL injection
        $allowed_sort_by = ['id', 'title', 'duration', 'price', 'category_id'];
        if (!in_array($sort_by, $allowed_sort_by)) {
            $sort_by = 'title';
        }

        // Define allowed sort directions
        $allowed_sort_direction = ['ASC', 'DESC'];
        if (!in_array($sort_direction, $allowed_sort_direction)) {
            $sort_direction = 'ASC';
        }

        // Build the WHERE clause for search
        $where_clauses = [];
        $params = [];
        if (!empty($search_term)) {
            $search_like = '%' . $wpdb->esc_like($search_term) . '%';
            $where_clauses[] = "(s.title LIKE %s OR s.description LIKE %s)";
            $params[] = $search_like;
            $params[] = $search_like;
        }

        // Count total items before applying limit/offset for pagination
        $count_query = "
        SELECT COUNT(s.id)  
        FROM {$services_table} AS s
        LEFT JOIN {$categories_table} AS c ON s.category_id = c.id ";

        if (!empty($where_clauses)) {
            $count_query .= " WHERE " . implode(" AND ", $where_clauses);
        }

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        $total_items = $wpdb->get_var($wpdb->prepare($count_query, ...$params));

        if ($wpdb->last_error) {
            error_log('Schedula API Error (get_services count): ' . esc_html($wpdb->last_error));
            return new WP_REST_Response([
                'services' => [],
                'total_items' => 0,
                'error' => __('Database error counting services.', 'schedula-smart-appointment-booking'),
            ], 500);
        }


        // Build the main query for fetching services
        $query = "
            SELECT
                s.id,
                s.category_id,
                s.title,
                s.description,
                s.duration,
                s.price,
                s.image_url,
                s.position,
                s.created_at,
                s.updated_at,
                c.name AS category_name
            FROM {$services_table} AS s
            LEFT JOIN {$categories_table} AS c ON s.category_id = c.id
        ";

        if (!empty($where_clauses)) {
            $query .= " WHERE " . implode(" AND ", $where_clauses);
        }

        $query .= " ORDER BY s.{$sort_by} {$sort_direction}";

        $main_query_params = $params;

        // Handle pagination only if per_page is not -1
        if ((int) $per_page_param !== -1) {
            $page = max(1, intval($page));
            $per_page = max(1, intval($per_page_param));
            $offset = ($page - 1) * $per_page;

            // Add LIMIT and OFFSET with placeholders
            $query .= " LIMIT %d OFFSET %d";

            // Merge all parameters including LIMIT and OFFSET values
            $main_query_params = array_merge($params, [$per_page, $offset]);
        } else {
            $page = 1;
            $per_page = -1; // Indicate all items are returned
            // No LIMIT/OFFSET added, so use original params
            $main_query_params = $params;
        }

        // Prepare and execute the query
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        $services = $wpdb->get_results($wpdb->prepare($query, ...$main_query_params), ARRAY_A);

        if ($wpdb->last_error) {
            error_log('Schedula API Error (get_services): ' . $wpdb->last_error);
            error_log('Query: ' . $query);
            error_log('Params: ' . print_r($main_query_params, true));
            return new WP_REST_Response([
                'services' => [],
                'total_items' => 0,
                'error' => __('Database error fetching services.', 'schedula-smart-appointment-booking')
            ], 500);
        }

        $response_data = [
            'services' => $services ?: [],
            'total_items' => (int) $total_items,
        ];

        if ((int) $per_page_param !== -1) {
            $response_data['current_page'] = $page;
            $response_data['per_page'] = $per_page;
            $response_data['total_pages'] = ceil((int) $total_items / $per_page);
        }

        return new WP_REST_Response($response_data, 200);
    }
    /**
     * Retrieves a single service by its ID.
     */
    public function get_service(WP_REST_Request $request)
    {
        global $wpdb;
        $services_table = $this->db->get_table_name('services');
        $categories_table = $this->db->get_table_name('categories');
        $id = (int) $request['id'];

        $service = $wpdb->get_row($wpdb->prepare("
            SELECT
                s.id,
                s.category_id,
                s.title,
                s.description,
                s.duration,
                s.price,
                s.image_url,
                s.position,
                s.created_at,
                s.updated_at,
                c.name AS category_name
            FROM {$services_table} AS s
            LEFT JOIN {$categories_table} AS c ON s.category_id = c.id
            WHERE s.id = %d
        ", $id), ARRAY_A);

        if (empty($service)) {
            return new WP_REST_Response(['message' => __('Service not found.', 'schedula-smart-appointment-booking')], 404);
        }
        if ($wpdb->last_error) {
            error_log('Schedula API Error (get_service): ' . $wpdb->last_error);
            return new WP_REST_Response(['message' => __('Database error fetching service.', 'schedula-smart-appointment-booking'), 'details' => $wpdb->last_error], 500);
        }
        return new WP_REST_Response($service, 200);
    }

    /**
     * Creates a new service in the database.
     */
    public function create_service(WP_REST_Request $request)
    {
        global $wpdb;
        $table_name = $this->db->get_table_name('services');

        $category_id = absint($request->get_param('category_id'));
        $title = sanitize_text_field($request->get_param('title'));
        $description = sanitize_textarea_field($request->get_param('description'));
        $duration = absint($request->get_param('duration'));
        $price = floatval($request->get_param('price'));
        $image_url = esc_url_raw($request->get_param('image_url'));

        if (empty($title)) {
            return new WP_REST_Response(['message' => __('Service title is required.', 'schedula-smart-appointment-booking')], 400);
        }
        if (empty($category_id)) {
            return new WP_REST_Response(['message' => __('Service category is required.', 'schedula-smart-appointment-booking')], 400);
        }
        if ($duration <= 0) {
            return new WP_REST_Response(['message' => __('Duration must be a positive number.', 'schedula-smart-appointment-booking')], 400);
        }
        if ($price < 0) {
            return new WP_REST_Response(['message' => __('Price cannot be negative.', 'schedula-smart-appointment-booking')], 400);
        }

        $data = [
            'category_id' => $category_id,
            'title' => $title,
            'description' => $description,
            'duration' => $duration,
            'price' => $price,
            'image_url' => $image_url,
            'position' => 0, // Default position
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ];

        $format = ['%d', '%s', '%s', '%d', '%f', '%s', '%d', '%s', '%s'];

        $inserted = $wpdb->insert($table_name, $data, $format);

        if ($inserted === false) {
            error_log('Schedula API Error (create_service): ' . $wpdb->last_error);
            return new WP_REST_Response(['message' => __('Failed to create service.', 'schedula-smart-appointment-booking'), 'db_error' => $wpdb->last_error], 500);
        }

        $data['id'] = $wpdb->insert_id;
        return new WP_REST_Response($data, 201);
    }

    /**
     * Updates an existing service in the database.
     */
    public function update_service(WP_REST_Request $request)
    {
        global $wpdb;
        $table_name = $this->db->get_table_name('services');
        $id = (int) $request['id'];

        $category_id = absint($request->get_param('category_id'));
        $title = sanitize_text_field($request->get_param('title'));
        $description = sanitize_textarea_field($request->get_param('description'));
        $duration = absint($request->get_param('duration'));
        $price = floatval($request->get_param('price'));
        $image_url = esc_url_raw($request->get_param('image_url'));
        $position = absint($request->get_param('position'));

        if (empty($title) || empty($category_id) || $duration <= 0 || $price < 0) {
            return new WP_REST_Response(['message' => __('Missing required service data or invalid values.', 'schedula-smart-appointment-booking')], 400);
        }

        $data = [
            'category_id' => $category_id,
            'title' => $title,
            'description' => $description,
            'duration' => $duration,
            'price' => $price,
            'image_url' => $image_url,
            'position' => $position,
            'updated_at' => current_time('mysql'),
        ];
        $where = ['id' => $id];

        $data_format = ['%d', '%s', '%s', '%d', '%f', '%s', '%d', '%s'];
        $where_format = ['%d'];

        $updated = $wpdb->update($table_name, $data, $where, $data_format, $where_format);

        if ($updated === false) {
            error_log('Schedula API Error (update_service): ' . $wpdb->last_error);
            return new WP_REST_Response(['message' => __('Failed to update service.', 'schedula-smart-appointment-booking'), 'db_error' => $wpdb->last_error], 500);
        } elseif ($updated === 0) {
            $exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE id = %d", $id));
            if (!$exists) {
                return new WP_REST_Response(['message' => __('Service not found.', 'schedula-smart-appointment-booking')], 404);
            }
            return new WP_REST_Response(['message' => __('Service updated (no changes detected).', 'schedula-smart-appointment-booking'), 'data' => $data], 200);
        }

        return new WP_REST_Response(['message' => __('Service updated successfully.', 'schedula-smart-appointment-booking'), 'data' => $data], 200);
    }

    /**
     * Deletes a service from the database.
     */
    public function delete_service(WP_REST_Request $request)
    {
        global $wpdb;
        $table_name = $this->db->get_table_name('services');
        $id = (int) $request['id'];

        // Check for associated appointments before deleting a service
        $appointments_table = $this->db->get_table_name('appointments');
        $has_appointments = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$appointments_table} WHERE service_id = %d", $id));
        if ($has_appointments > 0) {
            $message = sprintf(
                /* translators: %d: Number of associated appointments */
                __('Cannot delete service: It has %d associated appointment(s). Please delete or reassign appointments first.', 'schedula-smart-appointment-booking'),
                $has_appointments
            );
            return new WP_REST_Response(['message' => $message], 409);
        }

        // Check for associated staff_services before deleting a service
        $staff_services_table = $this->db->get_table_name('staff_services');
        $has_staff_services = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$staff_services_table} WHERE service_id = %d", $id));
        if ($has_staff_services > 0) {
            // Delete associated staff_services entries
            $wpdb->delete($staff_services_table, ['service_id' => $id]);
            if ($wpdb->last_error) {
                error_log('Schedula API Error (delete_service - staff_services): ' . $wpdb->last_error);
                return new WP_REST_Response(['message' => __('Failed to delete associated staff services. Please try again.', 'schedula-smart-appointment-booking'), 'db_error' => $wpdb->last_error], 500);
            }
        }

        $deleted = $wpdb->delete($table_name, ['id' => $id]);

        if ($deleted === false) {
            error_log('Schedula API Error (delete_service): ' . $wpdb->last_error);
            return new WP_REST_Response(['message' => __('Failed to delete service.', 'schedula-smart-appointment-booking'), 'db_error' => $wpdb->last_error], 500);
        } elseif ($deleted === 0) {
            return new WP_REST_Response(['message' => __('Service not found.', 'schedula-smart-appointment-booking')], 404);
        }

        return new WP_REST_Response(['message' => __('Service deleted successfully.', 'schedula-smart-appointment-booking')], 200);
    }
}
