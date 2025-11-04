<?php

/**
 * REST API Endpoints for Schedula Categories.
 * Handles CRUD operations for the 'schesab_categories' database table.
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

class SCHESAB_Categories
{

    private $namespace = 'schesab/v1';
    private $db;

    /**
     * Constructor.
     * Hooks into 'rest_api_init' to register custom REST API routes.
     */
    public function __construct()
    {
        // Ensure Schedula_Database is loaded and get its instance
        $this->db = \SCHESAB\Database\SCHESAB_Database::get_instance();
    }

    /**
     * Registers custom REST API routes for categories.
     */
    public function register_routes()
    {
        // Access WordPress database object via $this->db
        $categories_table = $this->db->get_table_name('categories');

        // Route for fetching all categories (GET)
        register_rest_route($this->namespace, '/categories', [
            'methods' => WP_REST_Server::READABLE, // Corresponds to GET requests
            'callback' => [$this, 'get_categories'],
            'permission_callback' => '__return_true', // Check user capabilities
            'args' => [
                // No specific arguments for fetching all
            ],
        ]);

        // Route for creating a new category (POST)
        register_rest_route($this->namespace, '/categories', [
            'methods' => WP_REST_Server::CREATABLE, // Corresponds to POST requests
            'callback' => [$this, 'create_category'],
            'permission_callback' => [$this, 'admin_permissions_check'],
            'args' => [
                'name' => [
                    'required' => true,
                    'sanitize_callback' => 'sanitize_text_field', // Sanitize input
                    'validate_callback' => function ($value) {
                        return !empty($value);
                    }, // Validate input
                    'description' => __('Name of the category.', 'schedula-smart-appointment-booking'),
                    'type' => 'string',
                ],
                'description' => [
                    'sanitize_callback' => 'sanitize_textarea_field',
                    'required' => false,
                    'description' => __('Description of the category.', 'schedula-smart-appointment-booking'),
                    'type' => 'string',
                ],
            ],
        ]);

        // Route for fetching a single category by ID (GET)
        register_rest_route($this->namespace, '/categories/(?P<id>\d+)', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_category'],
            'permission_callback' => '__return_true',
            'args' => [
                'id' => [
                    'validate_callback' => function ($param, $request, $key) {
                        return is_numeric($param); // Validate ID is numeric
                    },
                    'description' => __('ID of the category.', 'schedula-smart-appointment-booking'),
                    'type' => 'integer',
                ],
            ],
        ]);

        // Route for updating an existing category by ID (PUT/PATCH)
        register_rest_route($this->namespace, '/categories/(?P<id>\d+)', [
            'methods' => WP_REST_SERVER::EDITABLE, // Corresponds to PUT/PATCH requests
            'callback' => [$this, 'update_category'],
            'permission_callback' => [$this, 'admin_permissions_check'],
            'args' => [
                'id' => [
                    'validate_callback' => function ($param, $request, $key) {
                        return is_numeric($param);
                    },
                    'description' => __('ID of the category.', 'schedula-smart-appointment-booking'),
                    'type' => 'integer',
                ],
                'name' => [
                    'required' => true, // Name is required for update too
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => function ($value) {
                        return !empty($value);
                    },
                    'type' => 'string',
                ],
                'description' => [
                    'sanitize_callback' => 'sanitize_textarea_field',
                    'required' => false,
                    'type' => 'string',
                ],
            ],
        ]);

        // Route for deleting a category by ID (DELETE)
        register_rest_route($this->namespace, '/categories/(?P<id>\d+)', [
            'methods' => WP_REST_SERVER::DELETABLE, // Corresponds to DELETE requests
            'callback' => [$this, 'delete_category'],
            'permission_callback' => [$this, 'admin_permissions_check'],
            'args' => [
                'id' => [
                    'validate_callback' => function ($param, $request, $key) {
                        return is_numeric($param);
                    },
                    'description' => __('ID of the category to delete.', 'schedula-smart-appointment-booking'),
                    'type' => 'integer',
                ],
            ],
        ]);
    }

    /**
     * Permission callback to check if the current user has 'manage_options' capability.
     * This ensures only administrators (or users with this capability) can access these API endpoints.
     *
     * @param WP_REST_Request $request The request object.
     * @return bool True if the user has permission, false otherwise.
     */
    public function admin_permissions_check(WP_REST_Request $request)
    {
        return current_user_can('manage_options');
    }

    /**
     * Retrieves all categories from the database.
     *
     * @param WP_REST_Request $request The request object.
     * @return WP_REST_Response A response object containing categories or an error.
     */
    public function get_categories(WP_REST_Request $request)
    {
        global $wpdb;
        $table_name = $this->db->get_table_name('categories');

        // Fetch all categories, ordered by position then name
        $categories = $wpdb->get_results("SELECT id, name, description, position FROM $table_name ORDER BY position ASC, name ASC", ARRAY_A);

        if ($wpdb->last_error) {
            return new WP_REST_Response(['message' => __('Database error: ', 'schedula-smart-appointment-booking') . $wpdb->last_error], 500);
        }

        return new WP_REST_Response($categories, 200);
    }

    /**
     * Retrieves a single category by its ID.
     *
     * @param WP_REST_Request $request The request object.
     * @return WP_REST_Response A response object containing the category or an error.
     */
    public function get_category(WP_REST_Request $request)
    {
        global $wpdb;
        $table_name = $this->db->get_table_name('categories');
        $id = (int) $request['id'];

        $category = $wpdb->get_row($wpdb->prepare("SELECT id, name, description, position FROM $table_name WHERE id = %d", $id), ARRAY_A);

        if (empty($category)) {
            return new WP_REST_Response(['message' => __('Category not found.', 'schedula-smart-appointment-booking')], 404);
        }
        if ($wpdb->last_error) {
            return new WP_REST_Response(['message' => __('Database error: ', 'schedula-smart-appointment-booking') . $wpdb->last_error], 500);
        }
        return new WP_REST_Response($category, 200);
    }

    /**
     * Creates a new category in the database.
     *
     * @param WP_REST_Request $request The request object containing category data.
     * @return WP_REST_Response A response object containing the new category data or an error.
     */
    public function create_category(WP_REST_Request $request)
    {
        global $wpdb;
        $table_name = $this->db->get_table_name('categories');

        // Sanitize and get data from the request body
        $name = sanitize_text_field($request['name']);
        $description = sanitize_textarea_field($request['description']);

        // Basic validation
        if (empty($name)) {
            return new WP_REST_Response(['message' => __('Category name is required.', 'schedula-smart-appointment-booking')], 400);
        }

        $data = [
            'name' => $name,
            'description' => $description,
            'created_at' => current_time('mysql'), // ADDED: timestamps
            'updated_at' => current_time('mysql'), // ADDED: timestamps
        ];

        $inserted = $wpdb->insert($table_name, $data);

        if ($inserted === false) { // Check for strict false as 0 means no rows inserted
            return new WP_REST_Response(['message' => __('Failed to create category.', 'schedula-smart-appointment-booking'), 'db_error' => $wpdb->last_error], 500);
        }

        $data['id'] = $wpdb->insert_id; // Get the ID of the newly inserted row
        return new WP_REST_Response($data, 201); // 201 Created status
    }

    /**
     * Updates an existing category in the database.
     *
     * @param WP_REST_Request $request The request object containing updated category data.
     * @return WP_REST_Response A response object containing the updated category data or an error.
     */
    public function update_category(WP_REST_Request $request)
    {
        global $wpdb;
        $table_name = $this->db->get_table_name('categories');
        $id = (int) $request['id']; // Get ID from URL parameter

        // Sanitize and get data from the request body
        $name = sanitize_text_field($request['name']);
        $description = sanitize_textarea_field($request['description']);

        // Basic validation
        if (empty($name)) {
            return new WP_REST_Response(['message' => __('Category name is required.', 'schedula-smart-appointment-booking')], 400);
        }

        $data = [
            'name' => $name,
            'description' => $description,
            'updated_at' => current_time('mysql'), // ADDED: timestamps
        ];
        $where = ['id' => $id];

        $updated = $wpdb->update($table_name, $data, $where);

        if ($updated === false) {
            return new WP_REST_Response(['message' => __('Failed to update category.', 'schedula-smart-appointment-booking'), 'db_error' => $wpdb->last_error], 500);
        } elseif ($updated === 0) {
            // Check if the category actually exists, if 0 rows were updated
            $exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE id = %d", $id));
            if (!$exists) {
                return new WP_REST_Response(['message' => __('Category not found.', 'schedula-smart-appointment-booking')], 404);
            }
            // If exists but 0 rows updated, it means data was identical
            return new WP_REST_Response(['message' => __('Category updated (no changes detected).', 'schedula-smart-appointment-booking'), 'data' => $data], 200);
        }

        // Return the updated data (or a success message)
        return new WP_REST_Response(['message' => __('Category updated successfully.', 'schedula-smart-appointment-booking'), 'data' => $data], 200);
    }

    /**
     * Deletes a category from the database.
     *
     * @param WP_REST_Request $request The request object containing the category ID.
     * @return WP_REST_Response A response object indicating success or failure.
     */
    public function delete_category(WP_REST_Request $request)
    {
        global $wpdb;
        $table_name = $this->db->get_table_name('categories');
        $id = (int) $request['id'];

        // Check for associated services before deleting a category
        $services_table = $this->db->get_table_name('services');
        $has_services = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$services_table} WHERE category_id = %d", $id));
        if ($has_services > 0) {
            // translators: %d is the number of  associated services 
            return new WP_REST_Response(['message' => sprintf(__('Cannot delete category: It has %d associated service(s). Please reassign or delete services first.', 'schedula-smart-appointment-booking'), $has_services)], 409); // Conflict
        }

        $deleted = $wpdb->delete($table_name, ['id' => $id]);

        if ($deleted === false) {
            return new WP_REST_Response(['message' => __('Failed to delete category.', 'schedula-smart-appointment-booking'), 'db_error' => $wpdb->last_error], 500);
        } elseif ($deleted === 0) {
            return new WP_REST_Response(['message' => __('Category not found.', 'schedula-smart-appointment-booking')], 404);
        }

        return new WP_REST_Response(['message' => __('Category deleted successfully.', 'schedula-smart-appointment-booking')], 200);
    }
}
