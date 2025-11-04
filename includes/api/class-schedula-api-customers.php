<?php
/**
 * REST API Endpoints for Schedula Customers.
 * Handles CRUD operations for customer data.
 *
 * @package SCHESAB\Api
 * 
 */

namespace SCHESAB\Api;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use SCHESAB\Database\SCHESAB_Database;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class SCHESAB_Customers
{

    private $namespace = 'schesab/v1';
    private $db; // Retained: Property to hold the Schedula_Database instance

    public function __construct()
    {
        // Ensure Schedula_Database is loaded and get its instance
        // It's already namespaced, so reference it correctly.
        // The autoloader will handle loading SCHESAB\SCHESAB_Database
        $this->db = \SCHESAB\Database\SCHESAB_Database::get_instance();
    }

    /**
     * Register the REST API routes for customers.
     */
    public function register_routes()
    {
        // Get all customers
        register_rest_route($this->namespace, '/customers', array(
            'methods' => 'GET',
            'callback' => [$this, 'get_customers'],
            'permission_callback' => [$this, 'check_admin_permissions'],
            'args' => array(
                'search' => array(
                    'sanitize_callback' => 'sanitize_text_field',
                    'required' => false,
                    'description' => __('Search term for first_name, last_name, or email.', 'schedula-smart-appointment-booking'),
                ),
                'page' => array( // Added for pagination
                    'sanitize_callback' => 'absint',
                    'default' => 1,
                    'required' => false,
                    'description' => __('Current page number.', 'schedula-smart-appointment-booking'),
                ),
                'per_page' => array( // Added for pagination
                    'sanitize_callback' => 'absint',
                    'default' => 10, // Default items per page
                    'required' => false,
                    'description' => __('Number of items per page.', 'schedula-smart-appointment-booking'),
                ),
                'sort_by' => array(
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => function ($param, $request, $key) {
                        return in_array($param, ['id', 'first_name', 'last_name', 'email', 'created_at']);
                    },
                    'default' => 'created_at',
                    'required' => false,
                    'description' => __('Column to sort by (id, first_name, last_name, email, created_at).', 'schedula-smart-appointment-booking'),
                ),
                'sort_direction' => array(
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => function ($param, $request, $key) {
                        return in_array(strtolower($param), ['asc', 'desc']);
                    },
                    'default' => 'desc',
                    'required' => false,
                    'description' => __('Sorting direction (asc or desc).', 'schedula-smart-appointment-booking'),
                ),
            ),
        ));

        // Get a single customer
        register_rest_route($this->namespace, '/customers/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => [$this, 'get_customer'],
            'permission_callback' => [$this, 'check_admin_permissions'],
            'args' => array(
                'id' => array(
                    'validate_callback' => function ($param, $request, $key) {
                        return is_numeric($param);
                    },
                    'required' => true,
                ),
            ),
        ));

        // Create a new customer
        register_rest_route($this->namespace, '/customers', array(
            'methods' => 'POST',
            'callback' => [$this, 'create_customer'],
            'permission_callback' => [$this, 'check_admin_permissions'],
            'args' => $this->get_customer_args(),
        ));

        // Update an existing customer
        register_rest_route($this->namespace, '/customers/(?P<id>\d+)', array(
            'methods' => 'PUT',
            'callback' => [$this, 'update_customer'],
            'permission_callback' => [$this, 'check_admin_permissions'],
            'args' => $this->get_customer_args(true),
        ));

        // Delete a customer
        register_rest_route($this->namespace, '/customers/(?P<id>\d+)', array(
            'methods' => 'DELETE',
            'callback' => [$this, 'delete_customer'],
            'permission_callback' => [$this, 'check_admin_permissions'],
            'args' => array(
                'id' => array(
                    'validate_callback' => function ($param, $request, $key) {
                        return is_numeric($param);
                    },
                    'required' => true,
                ),
            ),
        ));

        // Endpoint to check if a customer has associated appointments
        register_rest_route($this->namespace, '/customers/(?P<id>\d+)/has-appointments', array(
            'methods' => 'GET',
            'callback' => [$this, 'customer_has_appointments'],
            'permission_callback' => [$this, 'check_admin_permissions'],
            'args' => array(
                'id' => array(
                    'validate_callback' => function ($param, $request, $key) {
                        return is_numeric($param);
                    },
                    'required' => true,
                ),
            ),
        ));

        // Endpoint to get customer stats
        register_rest_route($this->namespace, '/customers/(?P<id>\d+)/stats', array(
            'methods' => 'GET',
            'callback' => [$this, 'get_customer_stats'],
            'permission_callback' => [$this, 'check_admin_permissions'],
            'args' => array(
                'id' => array(
                    'validate_callback' => function ($param, $request, $key) {
                        return is_numeric($param);
                    },
                    'required' => true,
                ),
            ),
        ));
    }

    /**
     * Permission callback for all customer API endpoints.
     */
    public function check_admin_permissions(WP_REST_Request $request)
    {
        return current_user_can('manage_options');
    }

    /**
     * Get arguments for customer creation/update.
     */
    private function get_customer_args($for_update = false)
    {
        $args = array(
            'first_name' => array(
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => function ($param, $request, $key) {
                    return !empty($param);
                },
                'required' => true,
                'description' => __('First name of the customer.', 'schedula-smart-appointment-booking'),
            ),
            'last_name' => array(
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => function ($param, $request, $key) {
                    return !empty($param);
                },
                'required' => true,
                'description' => __('Last name of the customer.', 'schedula-smart-appointment-booking'),
            ),
            'email' => array(
                'sanitize_callback' => 'sanitize_email',
                'validate_callback' => function ($param, $request, $key) {
                    return is_email($param);
                },
                'required' => true,
                'description' => __('Email address of the customer (must be unique).', 'schedula-smart-appointment-booking'),
            ),
            'phone' => array(
                'sanitize_callback' => 'sanitize_text_field',
                'required' => false,
                'description' => __('Phone number of the customer.', 'schedula-smart-appointment-booking'),
            ),
            'notes' => array(
                'sanitize_callback' => 'sanitize_textarea_field',
                'required' => false,
                'description' => __('Additional notes about the customer.', 'schedula-smart-appointment-booking'),
            ),
            'user_id' => array(
                'validate_callback' => function ($param, $request, $key) {
                    return is_numeric($param) || is_null($param);
                },
                'required' => false,
                'description' => __('ID of the associated WordPress user.', 'schedula-smart-appointment-booking'),
            ),
        );

        if ($for_update) {
            $args['id'] = array(
                'validate_callback' => function ($param, $request, $key) {
                    return is_numeric($param);
                },
                'required' => true,
                'description' => __('ID of the customer to update.', 'schedula-smart-appointment-booking'),
            );
        }

        return $args;
    }

    /**
     * Get all customers with pagination and search.
     */
    public function get_customers(WP_REST_Request $request)
    {
        global $wpdb;
        $customers_table = $this->db->get_table_name('customers');
        $search = $request->get_param('search');
        $page = $request->get_param('page');
        $per_page = $request->get_param('per_page');
        $sort_by = $request->get_param('sort_by');
        $sort_direction = strtoupper($request->get_param('sort_direction'));

        // Whitelist allowed sortable columns
        $allowed_sort_by = ['id', 'first_name', 'last_name', 'email', 'created_at'];
        if (!in_array($sort_by, $allowed_sort_by)) {
            $sort_by = 'created_at'; // Default sort
        }

        // Whitelist allowed sort directions
        if (!in_array($sort_direction, ['ASC', 'DESC'])) {
            $sort_direction = 'DESC'; // Default direction
        }

        $params = [];
        $base_sql = "FROM {$customers_table}";
        $where_sql = '';

        if (!empty($search)) {
            $search_like = '%' . $wpdb->esc_like($search) . '%';
            $where_sql = ' WHERE (first_name LIKE %s OR last_name LIKE %s OR email LIKE %s)';
            $params[] = $search_like;
            $params[] = $search_like;
            $params[] = $search_like;
        }

        // Count total items
        $count_sql = "SELECT COUNT(id) {$base_sql}{$where_sql}";
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        $total_items = $wpdb->get_var($wpdb->prepare($count_sql, ...$params));

        // Build the main query
        $sql = "SELECT id, first_name, last_name, email, phone, notes, user_id {$base_sql}{$where_sql}";

        // Append ORDER BY clause safely without variable interpolation in the final prepare()
        $sql .= " ORDER BY {$sort_by} {$sort_direction}";

        $sql .= " LIMIT %d OFFSET %d";
        $params[] = $per_page;
        $params[] = ($page - 1) * $per_page;

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        $customers = $wpdb->get_results($wpdb->prepare($sql, ...$params), ARRAY_A);

        return new WP_REST_Response([
            'clients' => $customers,
            'total_items' => (int) $total_items,
        ], 200);
    }

    /**
     * Get a single customer by ID.
     */
    public function get_customer(WP_REST_Request $request)
    {
        global $wpdb;
        $customers_table = $this->db->get_table_name('customers');
        $id = (int) $request['id'];

        $customer = $wpdb->get_row($wpdb->prepare(
            "SELECT id, first_name, last_name, email, phone, notes, user_id FROM {$customers_table} WHERE id = %d",
            $id
        ), ARRAY_A);

        if (empty($customer)) {
            return new WP_REST_Response(array('message' => __('Customer not found.', 'schedula-smart-appointment-booking')), 404);
        }

        return new WP_REST_Response($customer, 200);
    }

    /**
     * Create a new customer.
     */
    public function create_customer(WP_REST_Request $request)
    {
        global $wpdb;
        $customers_table = $this->db->get_table_name('customers');

        $data = array(
            'first_name' => $request->get_param('first_name'),
            'last_name' => $request->get_param('last_name'),
            'email' => $request->get_param('email'),
            'phone' => $request->get_param('phone'),
            'notes' => $request->get_param('notes'),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        );

        $user_id_param = $request->get_param('user_id');
        if (empty($user_id_param)) {
            $data['user_id'] = null;
        } else {
            $data['user_id'] = absint($user_id_param);
        }

        // Check for duplicate email
        $existing_customer = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$customers_table} WHERE email = %s",
            $data['email']
        ));
        if ($existing_customer) {
            return new WP_REST_Response(array('message' => __('A customer with this email already exists.', 'schedula-smart-appointment-booking')), 409);
        }

        $inserted = $wpdb->insert($customers_table, $data);

        if ($inserted === false) {
            return new WP_REST_Response(array('message' => __('Failed to create customer.', 'schedula-smart-appointment-booking'), 'db_error' => $wpdb->last_error), 500);
        }

        $customer_id = $wpdb->insert_id;
        // Instantiate WP_REST_Request with method and route, then set the ID parameter
        $new_request = new WP_REST_Request('GET', '/customers/' . $customer_id);
        $new_request->set_param('id', $customer_id);
        $new_customer_response = $this->get_customer($new_request);

        if (is_wp_error($new_customer_response) || $new_customer_response->get_status() !== 200) {
            return new WP_REST_Response(array('message' => __('Customer created but could not retrieve details.', 'schedula-smart-appointment-booking'), 'details' => $new_customer_response->get_data()), 500);
        }
        $new_customer = $new_customer_response->get_data();


        return new WP_REST_Response($new_customer, 201);
    }

    /**
     * Update an existing customer.
     */
    public function update_customer(WP_REST_Request $request)
    {
        global $wpdb;
        $customers_table = $this->db->get_table_name('customers');
        $id = (int) $request['id'];

        $customer_exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$customers_table} WHERE id = %d",
            $id
        ));

        if (!$customer_exists) {
            return new WP_REST_Response(array('message' => __('Customer not found.', 'schedula-smart-appointment-booking')), 404);
        }

        $data = array(
            'first_name' => $request->get_param('first_name'),
            'last_name' => $request->get_param('last_name'),
            'email' => $request->get_param('email'),
            'phone' => $request->get_param('phone'),
            'notes' => $request->get_param('notes'),
            'updated_at' => current_time('mysql'),
        );

        $user_id_param = $request->get_param('user_id');
        if (empty($user_id_param)) {
            $data['user_id'] = null;
        } else {
            $data['user_id'] = absint($user_id_param);
        }

        // Check for duplicate email, excluding the current customer
        $existing_customer_with_email = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$customers_table} WHERE email = %s AND id != %d",
            $data['email'],
            $id
        ));
        if ($existing_customer_with_email) {
            return new WP_REST_Response(array('message' => __('Another customer with this email already exists.', 'schedula-smart-appointment-booking')), 409);
        }

        $updated = $wpdb->update(
            $customers_table,
            $data,
            array('id' => $id)
        );

        if ($updated === false) {
            return new WP_REST_Response(array('message' => __('Failed to update customer.', 'schedula-smart-appointment-booking'), 'db_error' => $wpdb->last_error), 500);
        }

        // Instantiate WP_REST_Request with method and route, then set the ID parameter
        $updated_request = new WP_REST_Request('GET', '/customers/' . $id);
        $updated_request->set_param('id', $id);
        $updated_customer_response = $this->get_customer($updated_request);

        if (is_wp_error($updated_customer_response) || $updated_customer_response->get_status() !== 200) {
            return new WP_REST_Response(array('message' => __('Customer updated but could not retrieve details.', 'schedula-smart-appointment-booking'), 'details' => $updated_customer_response->get_data()), 500);
        }
        $updated_customer = $updated_customer_response->get_data();

        return new WP_REST_Response($updated_customer, 200);
    }

    /**
     * Delete a customer and all their associated appointments.
     */
    public function delete_customer(WP_REST_Request $request)
    {
        global $wpdb;
        $customers_table = $this->db->get_table_name('customers');
        $customer_appointments_table = $this->db->get_table_name('customer_appointments');
        $appointments_table = $this->db->get_table_name('appointments');

        $id = (int) $request['id'];

        // 1. Check if customer exists
        $customer_exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$customers_table} WHERE id = %d",
            $id
        ));
        if (!$customer_exists) {
            return new WP_REST_Response(array('message' => __('Customer not found.', 'schedula-smart-appointment-booking')), 404);
        }

        // 2. Get all appointment IDs associated with this customer through the junction table
        $appointment_ids_to_delete = $wpdb->get_col($wpdb->prepare(
            "SELECT appointment_id FROM {$customer_appointments_table} WHERE customer_id = %d",
            $id
        ));

        // 3. If there are associated appointments, delete them from the 'appointments' table
        if (!empty($appointment_ids_to_delete)) {
            $placeholders = implode(', ', array_fill(0, count($appointment_ids_to_delete), '%d'));
            $query = $wpdb->prepare("DELETE FROM {$appointments_table} WHERE id IN ($placeholders)", $appointment_ids_to_delete);
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $deleted_appointments = $wpdb->query($query);

            if ($deleted_appointments === false) {
                error_log("Schedula: Failed to delete associated appointments for customer ID {$id}. DB Error: {$wpdb->last_error}");
                return new WP_REST_Response(array('message' => __('Failed to delete associated appointments. Please try again.', 'schedula-smart-appointment-booking'), 'db_error' => $wpdb->last_error), 500);
            }
        }

        // 4. Delete the customer from the 'customers' table
        $deleted_customer = $wpdb->delete($customers_table, array('id' => $id));

        if ($deleted_customer === false) {
            return new WP_REST_Response(array('message' => __('Failed to delete customer.', 'schedula-smart-appointment-booking'), 'db_error' => $wpdb->last_error), 500);
        } elseif ($deleted_customer == 0) {
            return new WP_REST_Response(array('message' => __('Customer not found or already deleted.', 'schedula-smart-appointment-booking')), 404);
        }

        return new WP_REST_Response(array('message' => __('Customer and all associated appointments deleted successfully.', 'schedula-smart-appointment-booking')), 200);
    }

    /**
     * Endpoint to check if a customer has associated appointments.
     */
    public function customer_has_appointments(WP_REST_Request $request)
    {
        global $wpdb;
        $customer_appointments_table = $this->db->get_table_name('customer_appointments');
        $id = (int) $request['id'];

        $has_appointments = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$customer_appointments_table} WHERE customer_id = %d",
            $id
        ));

        return new WP_REST_Response(array('has_appointments' => ($has_appointments > 0)), 200);
    }

    /**
     * Get statistics for a single customer.
     */
    public function get_customer_stats(WP_REST_Request $request)
    {
        global $wpdb;
        $customer_id = (int) $request['id'];

        $customer_appointments_table = $this->db->get_table_name('customer_appointments');
        $payments_table = $this->db->get_table_name('payments');

        // Get appointment count
        $appointment_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$customer_appointments_table} WHERE customer_id = %d",
            $customer_id
        ));

        // Get total paid amount
        $total_paid = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(p.amount) 
             FROM {$payments_table} p
             JOIN {$customer_appointments_table} ca ON p.appointment_id = ca.appointment_id
             WHERE ca.customer_id = %d AND p.status = 'completed'",
            $customer_id
        ));

        // Format the response
        $stats = array(
            'appointments_count' => (int) $appointment_count,
            'total_paid' => !is_null($total_paid) ? number_format((float) $total_paid, 2, '.', '') . ' €' : '0.00 €'
        );

        return new WP_REST_Response($stats, 200);
    }
}
