<?php

/**
 * Schedula Forms API Controller.
 *
 * Handles REST API endpoints for managing service forms.
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

class SCHESAB_Forms
{

    private $namespace = 'schesab/v1';
    private $db;

    public function __construct()
    {
        $this->db = \SCHESAB\Database\SCHESAB_Database::get_instance();
    }

    /**
     * Register the routes for the objects of the controller.
     */
    public function register_routes()
    {
        $namespace = 'schesab/v1';
        $base = 'forms';

        register_rest_route($namespace, '/' . $base, [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_items'],
                'permission_callback' => [$this, 'check_admin_permissions'],
            ],
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'create_item'],
                'permission_callback' => [$this, 'check_admin_permissions'],
            ],
            'schema' => [$this, 'get_item_schema'],
        ]);

        register_rest_route($namespace, '/' . $base . '/(?P<id>\d+)', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_item'],
                'permission_callback' => [$this, 'check_admin_permissions'],
            ],
            [
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => [$this, 'update_item'],
                'permission_callback' => [$this, 'check_admin_permissions'],
            ],
            [
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => [$this, 'delete_item'],
                'permission_callback' => [$this, 'check_admin_permissions'],
            ],
            'schema' => [$this, 'get_item_schema'],
        ]);
    }

    /**
     * Get a collection of forms.
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     */
    public function get_items($request)
    {
        global $wpdb;
        $table_name = $this->db->get_table_name('forms');
        $services_table = $this->db->get_table_name('services');
        $categories_table = $this->db->get_table_name('categories');
        $staff_table = $this->db->get_table_name('staff');

        $per_page = isset($request['per_page']) ? (int) $request['per_page'] : 20;
        $page = isset($request['page']) ? (int) $request['page'] : 1;
        $offset = ($page - 1) * $per_page;

        $forms = $wpdb->get_results($wpdb->prepare(
            "SELECT
                f.id,
                f.name,
                f.shortcode,
                f.form_data,
                f.service_id,
                f.category_id,
                f.staff_id,
                s.title as serviceName,
                c.name as categoryName,
                st.name as staffName
            FROM {$table_name} f
            LEFT JOIN {$services_table} s ON f.service_id = s.id
            LEFT JOIN {$categories_table} c ON f.category_id = c.id
            LEFT JOIN {$staff_table} st ON f.staff_id = st.id
            LIMIT %d OFFSET %d",
            $per_page,
            $offset
        ), ARRAY_A);

        foreach ($forms as &$form) {
            $form['form_data'] = !empty($form['form_data']) ? json_decode($form['form_data'], true) : [];
        }

        $total = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");

        $response = new WP_REST_Response($forms, 200);
        $response->header('X-WP-Total', $total);
        $response->header('X-WP-TotalPages', ceil($total / $per_page));

        return $response;
    }

    /**
     * Get a single form.
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     */
    public function get_item($request)
    {
        global $wpdb;
        $table_name = $this->db->get_table_name('forms');
        $services_table = $this->db->get_table_name('services');
        $categories_table = $this->db->get_table_name('categories');
        $staff_table = $this->db->get_table_name('staff');

        $id = (int) $request['id'];

        $form = $wpdb->get_row($wpdb->prepare(
            "SELECT
                f.id,
                f.name,
                f.shortcode,
                f.form_data,
                f.service_id,
                f.category_id,
                f.staff_id,
                s.title as serviceName,
                c.name as categoryName,
                st.name as staffName
            FROM {$table_name} f
            LEFT JOIN {$services_table} s ON f.service_id = s.id
            LEFT JOIN {$categories_table} c ON f.category_id = c.id
            LEFT JOIN {$staff_table} st ON f.staff_id = st.id
            WHERE f.id = %d",
            $id
        ), ARRAY_A);

        if (empty($form)) {
            return new WP_Error('rest_form_not_found', __('Form not found.', 'schedula-smart-appointment-booking'), ['status' => 404]);
        }

        $form['form_data'] = json_decode($form['form_data'], true);

        return new WP_REST_Response($form, 200);
    }

    /**
     * Create a new form.
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     */
    public function create_item($request)
    {
        global $wpdb;
        $table_name = $this->db->get_table_name('forms');

        $name = sanitize_text_field($request['name']);
        $service_id = isset($request['serviceId']) ? (int) $request['serviceId'] : null;
        $category_id = isset($request['categoryId']) ? (int) $request['categoryId'] : null;
        $staff_id = isset($request['staffId']) ? (int) $request['staffId'] : null;
        $form_data = isset($request['formData']) ? $request['formData'] : null;

        if (empty($name)) {
            return new WP_Error('rest_form_name_empty', __('Form name cannot be empty.', 'schedula-smart-appointment-booking'), ['status' => 400]);
        }

        $validation = $this->validate_service_and_category($service_id, $category_id);
        if (is_wp_error($validation)) {
            return $validation;
        }

        if ($staff_id && !$this->get_staff_name($staff_id)) {
            return new WP_Error('rest_invalid_staff_id', __('Invalid staff ID.', 'schedula-smart-appointment-booking'), ['status' => 400]);
        }

        $validation = $this->validate_form_data($form_data);
        if (is_wp_error($validation)) {
            return $validation;
        }

        $data = [
            'name' => $name,
            'service_id' => $service_id,
            'category_id' => $category_id,
            'staff_id' => $staff_id,
            'form_data' => $form_data,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ];

        $inserted = $wpdb->insert($table_name, $data);

        if ($inserted) {
            $id = $wpdb->insert_id;
            // Generate shortcode AFTER insert to ensure correct ID
            $shortcode = sprintf('[schesab_service_form id="%d"]', $id);
            $wpdb->update(
                $table_name,
                ['shortcode' => $shortcode],
                ['id' => $id]
            );

            $response_data = array_merge(['id' => $id, 'shortcode' => $shortcode], $data);
            $response_data['serviceName'] = $this->get_service_title($service_id);
            $response_data['categoryName'] = $this->get_category_name($category_id);
            $response_data['staffName'] = $this->get_staff_name($staff_id);
            $response_data['form_data'] = json_decode($form_data, true);
            return new WP_REST_Response($response_data, 201);
        } else {
            return new WP_Error('rest_form_create_error', __('Failed to create form.', 'schedula-smart-appointment-booking'), ['status' => 500]);
        }
    }

    /**
     * Update a form.
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     */
    public function update_item($request)
    {
        global $wpdb;
        $table_name = $this->db->get_table_name('forms');

        $id = (int) $request['id'];
        $name = sanitize_text_field($request['name']);
        $service_id = isset($request['serviceId']) ? (int) $request['serviceId'] : null;
        $category_id = isset($request['categoryId']) ? (int) $request['categoryId'] : null;
        $staff_id = isset($request['staffId']) ? (int) $request['staffId'] : null;
        $form_data = isset($request['formData']) ? $request['formData'] : null;

        if (empty($name)) {
            return new WP_Error('rest_form_name_empty', __('Form name cannot be empty.', 'schedula-smart-appointment-booking'), ['status' => 400]);
        }

        $validation = $this->validate_service_and_category($service_id, $category_id);
        if (is_wp_error($validation)) {
            return $validation;
        }

        if ($staff_id && !$this->get_staff_name($staff_id)) {
            return new WP_Error('rest_invalid_staff_id', __('Invalid staff ID.', 'schedula-smart-appointment-booking'), ['status' => 400]);
        }

        $validation = $this->validate_form_data($form_data);
        if (is_wp_error($validation)) {
            return $validation;
        }

        $data = [
            'name' => $name,
            'service_id' => $service_id,
            'category_id' => $category_id,
            'staff_id' => $staff_id,
            'form_data' => $form_data,
            'updated_at' => current_time('mysql'),
        ];

        $updated = $wpdb->update($table_name, $data, ['id' => $id]);

        if ($updated !== false) {
            // Generate shortcode AFTER update to ensure correct ID
            $shortcode = sprintf('[schesab_service_form id="%d"]', $id);
            $wpdb->update(
                $table_name,
                ['shortcode' => $shortcode],
                ['id' => $id]
            );

            $response_data = array_merge(['id' => $id, 'shortcode' => $shortcode], $data);
            $response_data['serviceName'] = $this->get_service_title($service_id);
            $response_data['categoryName'] = $this->get_category_name($category_id);
            $response_data['staffName'] = $this->get_staff_name($staff_id);
            $response_data['form_data'] = json_decode($form_data, true);
            return new WP_REST_Response($response_data, 200);
        } else {
            return new WP_Error('rest_form_update_error', __('Failed to update form.', 'schedula-smart-appointment-booking'), ['status' => 500]);
        }
    }

    /**
     * Delete a form.
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     */
    public function delete_item($request)
    {
        global $wpdb;
        $table_name = $this->db->get_table_name('forms');

        $id = (int) $request['id'];

        $deleted = $wpdb->delete($table_name, ['id' => $id]);

        if ($deleted) {
            return new WP_REST_Response(['message' => __('Form deleted successfully.', 'schedula-smart-appointment-booking')], 200);
        } else {
            return new WP_Error('rest_form_delete_error', __('Failed to delete form.', 'schedula-smart-appointment-booking'), ['status' => 500]);
        }
    }

    /**
     * Check if a given request has access to manage forms.
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_Error|bool
     */
    public function check_admin_permissions(WP_REST_Request $request)
    {
        return current_user_can('manage_options');
    }

    /**
     * Get the form schema, conforming to JSON Schema.
     *
     * @return array
     */
    public function get_item_schema()
    {
        return [
            '$schema' => 'http://json-schema.org/draft-04/schema#',
            'title' => __('Form', 'schedula-smart-appointment-booking'),
            'type' => 'object',
            'properties' => [
                'id' => [
                    'type' => 'integer',
                    'description' => __('Unique identifier for the form.', 'schedula-smart-appointment-booking'),
                    'readonly' => true,
                ],
                'name' => [
                    'type' => 'string',
                    'description' => __('Name of the form.', 'schedula-smart-appointment-booking'),
                    'required' => true,
                    'minLength' => 1,
                ],
                'shortcode' => [
                    'type' => 'string',
                    'description' => __('Shortcode for the form.', 'schedula-smart-appointment-booking'),
                    'readonly' => true,
                ],
                'service_id' => [
                    'type' => 'integer',
                    'description' => __('ID of the associated service.', 'schedula-smart-appointment-booking'),
                ],
                'category_id' => [
                    'type' => 'integer',
                    'description' => __('ID of the associated category.', 'schedula-smart-appointment-booking'),
                ],
                'staff_id' => [
                    'type' => 'integer',
                    'description' => __('ID of the associated staff member.', 'schedula-smart-appointment-booking'),
                ],
                'serviceName' => [
                    'type' => 'string',
                    'description' => __('Name of the associated service.', 'schedula-smart-appointment-booking'),
                ],
                'categoryName' => [
                    'type' => 'string',
                    'description' => __('Name of the associated category.', 'schedula-smart-appointment-booking'),
                ],
                'staffName' => [
                    'type' => 'string',
                    'description' => __('Name of the associated staff member.', 'schedula-smart-appointment-booking'),
                ],
                'form_data' => [
                    'type' => 'object',
                    'description' => __('Custom form settings.', 'schedula-smart-appointment-booking'),
                ],
            ],
        ];
    }

    /**
     * Helper to get service name by ID.
     *
     * @param int $service_id Service ID.
     * @return string|null Service name or null if not found.
     */
    private function get_service_title($service_id)
    {
        if (empty($service_id)) {
            return null;
        }
        global $wpdb;
        $services_table = $this->db->get_table_name('services');
        return $wpdb->get_var($wpdb->prepare("SELECT title FROM {$services_table} WHERE id = %d", $service_id));
    }

    /**
     * Helper to get category name by ID.
     *
     * @param int $category_id Category ID.
     * @return string|null Category name or null if not found.
     */
    private function get_category_name($category_id)
    {
        if (empty($category_id)) {
            return null;
        }
        global $wpdb;
        $categories_table = $this->db->get_table_name('categories');
        return $wpdb->get_var($wpdb->prepare("SELECT name FROM {$categories_table} WHERE id = %d", $category_id));
    }

    /**
     * Helper to get staff name by ID.
     *
     * @param int $staff_id Staff ID.
     * @return string|null Staff name or null if not found.
     */
    private function get_staff_name($staff_id)
    {
        if (empty($staff_id)) {
            return null;
        }
        global $wpdb;
        $staff_table = $this->db->get_table_name('staff');
        return $wpdb->get_var($wpdb->prepare("SELECT name FROM {$staff_table} WHERE id = %d", $staff_id));
    }



    /**
     * Validate service and category IDs.
     *
     * @param int|null $service_id Service ID.
     * @param int|null $category_id Category ID.
     * @return true|WP_Error True on success, WP_Error on failure.
     */
    private function validate_service_and_category($service_id, $category_id)
    {
        global $wpdb;
        $services_table = $this->db->get_table_name('services');
        $categories_table = $this->db->get_table_name('categories');

        if ($service_id && !$wpdb->get_var($wpdb->prepare("SELECT id FROM {$services_table} WHERE id = %d", $service_id))) {
            return new WP_Error('rest_invalid_service_id', __('Invalid service ID.', 'schedula-smart-appointment-booking'), ['status' => 400]);
        }

        if ($category_id && !$wpdb->get_var($wpdb->prepare("SELECT id FROM {$categories_table} WHERE id = %d", $category_id))) {
            return new WP_Error('rest_invalid_category_id', __('Invalid category ID.', 'schedula-smart-appointment-booking'), ['status' => 400]);
        }

        return true;
    }

    /**
     * Validate form_data JSON structure.
     *
     * @param string|null $form_data JSON-encoded form data.
     * @return true|WP_Error True on success, WP_Error on failure.
     */
    private function validate_form_data($form_data)
    {
        if (empty($form_data)) {
            return true;
        }
        $decoded = json_decode($form_data, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('rest_invalid_form_data', __('Invalid JSON in form_data.', 'schedula-smart-appointment-booking'), ['status' => 400]);
        }
        return true;
    }
}
