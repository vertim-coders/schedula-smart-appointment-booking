<?php

/**
 * REST API Endpoints for Schedula Payments.
 * Handles fetching, deleting, and managing payment-related data,
 * and now also creating new payment records.
 *
 * @package SCHESAB\Api
 * I
 */

namespace SCHESAB\Api;

use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use SCHESAB\Database\SCHESAB_Database; // Correctly import the Database class

if (!defined('ABSPATH')) {
    exit;
}

class SCHESAB_Payments
{

    private $namespace = 'schesab/v1';
    private $db;

    public function __construct()
    {
        $this->db = \SCHESAB\Database\SCHESAB_Database::get_instance();
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
        $general_settings = get_option('schesab_general_settings', []);

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

    public function register_routes()
    {
        // Get all payments (with filters)
        register_rest_route($this->namespace, '/payments', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_payments'),
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
                'sort_by' => array(
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => function ($param) {
                        return in_array($param, ['payment_id', 'payment_date', 'customer_full_name', 'staff_name', 'service_title', 'amount', 'start_datetime']);
                    },
                    'required' => false,
                    'default' => 'payment_date', // Default sort column
                ),
                'sort_direction' => array(
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => function ($param) {
                        return in_array(strtolower($param), ['asc', 'desc']);
                    },
                    'required' => false,
                    'default' => 'desc', // Default sort direction
                ),
            ),
        ));


        register_rest_route($this->namespace, '/payments', array(
            'methods' => 'POST',
            'callback' => array($this, 'create_payment'),
            'permission_callback' => array($this, 'check_admin_permissions'), // Or a more specific permission if only the system should create
            'args' => array(
                'appointment_id' => array(
                    'sanitize_callback' => 'absint',
                    'validate_callback' => function ($param) {
                        return is_numeric($param) && $param > 0;
                    },
                    'required' => true,
                ),
                'order_id' => array( // NEW: Added order_id for stripe or other order-based payments
                    'sanitize_callback' => 'absint',
                    'validate_callback' => function ($param) {
                        return empty($param) || (is_numeric($param) && $param > 0);
                    },
                    'required' => false,
                    'default' => null,
                ),
                'amount' => array(
                    'sanitize_callback' => function ($param) {
                        return floatval($param);
                    },
                    'validate_callback' => function ($param) {
                        return is_numeric($param) && $param >= 0;
                    },
                    'required' => true,
                ),
                'payment_type' => array(
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => function ($param) {
                        // Define allowed payment types (e.g. 'stripe', 'local', 'admin')
                        return in_array($param, ['stripe', 'local', 'admin_manual', 'unknown']);
                    },
                    'required' => true,
                ),
                'transaction_id' => array(
                    'sanitize_callback' => 'sanitize_text_field',
                    'required' => false, // Can be null for local payments
                ),
                'status' => array(
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => function ($param) {
                        return in_array($param, ['pending', 'completed', 'failed', 'refunded']);
                    },
                    'required' => true,
                    'default' => 'pending',
                ),
                'notes' => array(
                    'sanitize_callback' => 'sanitize_textarea_field',
                    'required' => false,
                ),
            ),
        ));

        // Delete a payment (and its associated appointment)
        register_rest_route($this->namespace, '/payments/(?P<id>\d+)', array(
            'methods' => 'DELETE',
            'callback' => array($this, 'delete_payment'),
            'permission_callback' => array($this, 'check_admin_permissions'),
        ));

        // Get payment receipt details
        register_rest_route($this->namespace, '/payments/(?P<id>\d+)/receipt', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_payment_receipt'),
            'permission_callback' => array($this, 'check_admin_permissions'),
        ));
    }

    /**
     * Check if the current user has admin capabilities.
     */
    public function check_admin_permissions(WP_REST_Request $request)
    {
        // For creating payments, you might want a different permission check,
        // or ensure that only authenticated payment gateways can use it.
        // For now, it uses 'manage_options' consistent with other admin endpoints.
        return current_user_can('manage_options'); // Or a more specific capability
    }

    /**
     * Get all payments with filters.
     */
    public function get_payments(WP_REST_Request $request)
    {
        global $wpdb;
        $payments_table = $this->db->get_table_name('payments');
        $appointments_table = $this->db->get_table_name('appointments');
        $services_table = $this->db->get_table_name('services');
        $staff_table = $this->db->get_table_name('staff');
        $customers_table = $this->db->get_table_name('customers');
        $customer_appointments_table = $this->db->get_table_name('customer_appointments');

        $start_date_filter = $request->get_param('start_date');
        $end_date_filter = $request->get_param('end_date');
        $customer_name_search = $request->get_param('customer_name');
        $staff_name_search = $request->get_param('staff_name');
        $service_title_search = $request->get_param('service_title');
        $payment_type_filter = $request->get_param('payment_type');

        // Sorting parameters
        $sort_by = $request->get_param('sort_by');
        $sort_direction = strtoupper($request->get_param('sort_direction')); // Ensure uppercase for SQL

        $where_clauses = [];
        $params = [];

        // Base condition: only fetch completed payments for the list.
        $where_clauses[] = "p.status = 'completed'";

        if (!empty($start_date_filter) && !empty($end_date_filter)) {
            $where_clauses[] = "DATE(p.payment_date) BETWEEN %s AND %s";
            $params[] = $start_date_filter;
            $params[] = $end_date_filter;
        }
        if (!empty($customer_name_search)) {
            $search_like = '%' . $wpdb->esc_like($customer_name_search) . '%';
            $where_clauses[] = "(cust.first_name LIKE %s OR cust.last_name LIKE %s)";
            $params[] = $search_like;
            $params[] = $search_like;
        }
        if (!empty($staff_name_search)) {
            $where_clauses[] = "st.name LIKE %s";
            $params[] = '%' . $wpdb->esc_like($staff_name_search) . '%';
        }
        if (!empty($service_title_search)) {
            $where_clauses[] = "s.title LIKE %s";
            $params[] = '%' . $wpdb->esc_like($service_title_search) . '%';
        }
        if (!empty($payment_type_filter)) {
            $where_clauses[] = "p.payment_type = %s";
            $params[] = $payment_type_filter;
        }

        $query = "SELECT
                    p.id AS payment_id,
                    p.payment_date,
                    p.payment_type,
                    p.amount,
                    p.status AS payment_status,
                    p.notes AS payment_notes,
                    a.id AS appointment_id,
                    a.start_datetime,
                    a.end_datetime,
                    a.status AS appointment_status,
                    s.title AS service_title,
                    st.name AS staff_name,
                    cust.first_name AS customer_first_name,
                    cust.last_name AS customer_last_name,
                    cust.email AS customer_email
                  FROM {$payments_table} AS p
                  INNER JOIN {$appointments_table} AS a ON p.appointment_id = a.id
                  LEFT JOIN {$services_table} AS s ON a.service_id = s.id
                  LEFT JOIN {$staff_table} AS st ON a.staff_id = st.id
                  LEFT JOIN {$customer_appointments_table} AS ca ON a.id = ca.appointment_id
                  LEFT JOIN {$customers_table} AS cust ON ca.customer_id = cust.id
                  WHERE " . implode(" AND ", $where_clauses);

        // Validate sort_by column to prevent SQL injection
        $allowed_sort_columns = [
            'payment_id' => 'p.id',
            'payment_date' => 'p.payment_date',
            'start_datetime' => 'a.start_datetime',
            'customer_full_name' => 'CONCAT(cust.first_name, " ", cust.last_name)', // Sort by full customer name
            'staff_name' => 'st.name',
            'service_title' => 's.title',
            'amount' => 'p.amount',
        ];

        // Default to 'payment_date' if an invalid column is provided
        $sort_column_sql = $allowed_sort_columns[$sort_by] ?? $allowed_sort_columns['payment_date'];
        // Default direction
        $sort_direction_sql = in_array($sort_direction, ['ASC', 'DESC']) ? $sort_direction : 'DESC';

        // Add dynamic ORDER BY clause
        $query .= " ORDER BY {$sort_column_sql} {$sort_direction_sql}";

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        $payments = $wpdb->get_results($wpdb->prepare($query, ...$params), ARRAY_A);

        // Add a display-friendly payment type and convert times to business timezone
        $business_timezone = $this->get_business_timezone();
        if (!@timezone_open($business_timezone)) {
            $business_timezone = 'UTC';
        }

        foreach ($payments as &$payment) {
            $payment['customer_full_name'] = trim($payment['customer_first_name'] . ' ' . $payment['customer_last_name']);
            // Create a human-readable version of the payment type
            $payment['display_payment_type'] = ucwords(str_replace('_', ' ', $payment['payment_type']));

            // Convert payment date from UTC to business timezone for display
            if (!empty($payment['payment_date'])) {
                $payment_dt = new \DateTime($payment['payment_date'], new \DateTimeZone('UTC'));
                $payment_dt->setTimezone(new \DateTimeZone($business_timezone));
                $payment['payment_date'] = $payment_dt->format('Y-m-d H:i:s');
            }
        }

        return new WP_REST_Response($payments, 200);
    }

    /**
     * Creates a new payment record in the database.
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_REST_Response Response object on success or error.
     */
    public function create_payment(WP_REST_Request $request)
    {
        global $wpdb;
        $payments_table = $this->db->get_table_name('payments');

        $appointment_id = $request->get_param('appointment_id');
        $order_id = $request->get_param('order_id');
        $amount = $request->get_param('amount');
        $payment_type = $request->get_param('payment_type');
        $transaction_id = $request->get_param('transaction_id');
        $status = $request->get_param('status');
        $notes = $request->get_param('notes');

        // If payment type is 'local' (cash), do not create a payment record via this API.
        // The payment will be handled manually or marked as 'pending' on the appointment itself.
        if ($payment_type === 'local' || $payment_type === 'cash') {
            // For local payments, we don't create a payment record here.
            // The appointment status itself will reflect the payment state (e.g., 'pending' or 'confirmed' with payment due).
            // We return a success response as the "creation" of the payment intent (to pay locally) is acknowledged.
            return new WP_REST_Response(array('message' => __('Local payment acknowledged. No payment record created via API.', 'schedula-smart-appointment-booking'), 'payment_id' => null), 200);
        }

        // Optional: Perform additional business logic validation here,
        // e.g., check if the appointment_id exists and is valid.

        // Get current time in business timezone (no conversion)
        $business_tz = new \DateTimeZone($this->get_business_timezone());
        $current_dt = new \DateTime('now', $business_tz);

        $data = array(
            'appointment_id' => $appointment_id,
            'order_id' => $order_id, // NEW: Include order_id in data
            'payment_date' => $current_dt->format('Y-m-d H:i:s'), // Record the current time in business timezone
            'amount' => $amount,
            'payment_type' => $payment_type,
            'transaction_id' => $transaction_id,
            'status' => $status,
            'notes' => $notes,
        );

        // Adjust format array to include order_id
        $format = array('%d', '%d', '%s', '%f', '%s', '%s', '%s', '%s'); // appointment_id, order_id, Datetime, Float, String, String, String, String

        $inserted = $wpdb->insert($payments_table, $data, $format);

        if ($inserted === false) {
            error_log('Schedula API Error: Failed to create payment. DB Error: ' . $wpdb->last_error);
            return new WP_REST_Response(
                array(
                    'message' => __('Failed to create payment.', 'schedula-smart-appointment-booking'),
                    'db_error' => $wpdb->last_error,
                    'request_data' => $data // For debugging
                ),
                500
            );
        }

        // Return the newly created payment details, including its ID
        $new_payment_id = $wpdb->insert_id;
        $response_data = array_merge($data, ['id' => $new_payment_id]);
        return new WP_REST_Response(array('message' => __('Payment created successfully.', 'schedula-smart-appointment-booking'), 'payment' => $response_data), 201);
    }


    /**
     * Get detailed information for a single payment receipt.
     */
    public function get_payment_receipt(WP_REST_Request $request)
    {
        global $wpdb;
        $id = (int) $request['id'];

        $payments_table = $this->db->get_table_name('payments');
        $appointments_table = $this->db->get_table_name('appointments');
        $services_table = $this->db->get_table_name('services');
        $staff_table = $this->db->get_table_name('staff');
        $customers_table = $this->db->get_table_name('customers');
        $customer_appointments_table = $this->db->get_table_name('customer_appointments');

        $receipt_data = $wpdb->get_row($wpdb->prepare("
            SELECT
                p.id AS payment_id,
                p.payment_date,
                p.amount,
                p.payment_type,
                p.status AS payment_status,
                p.notes AS payment_notes,
                a.id AS appointment_id,
                a.start_datetime,
                a.end_datetime,
                a.duration,
                a.notes AS appointment_notes,
                s.title AS service_title,
                st.name AS staff_name,
                st.email AS staff_email,
                cust.first_name AS customer_first_name,
                cust.last_name AS customer_last_name,
                cust.email AS customer_email,
                cust.phone AS customer_phone
            FROM {$payments_table} AS p
            INNER JOIN {$appointments_table} AS a ON p.appointment_id = a.id
            LEFT JOIN {$services_table} AS s ON a.service_id = s.id
            LEFT JOIN {$staff_table} AS st ON a.staff_id = st.id
            LEFT JOIN {$customer_appointments_table} AS ca ON a.id = ca.appointment_id
            LEFT JOIN {$customers_table} AS cust ON ca.customer_id = cust.id
            WHERE p.id = %d
        ", $id), ARRAY_A);

        if (empty($receipt_data)) {
            return new WP_REST_Response(array('message' => __('Payment receipt not found.', 'schedula-smart-appointment-booking')), 404);
        }

        // Format data for a clean frontend display
        $receipt_data['customer_full_name'] = trim($receipt_data['customer_first_name'] . ' ' . $receipt_data['customer_last_name']);
        $receipt_data['payment_type_display'] = ucwords(str_replace('_', ' ', $receipt_data['payment_type']));

        return new WP_REST_Response($receipt_data, 200);
    }

    /**
     * Delete a payment AND its associated appointment.
     */
    public function delete_payment(WP_REST_Request $request)
    {
        global $wpdb;
        $payments_table = $this->db->get_table_name('payments');
        $appointments_table = $this->db->get_table_name('appointments');
        $customer_appointments_table = $this->db->get_table_name('customer_appointments');

        $payment_id = (int) $request['id'];

        // Get the appointment_id linked to this payment
        $appointment_id = $wpdb->get_var($wpdb->prepare("SELECT appointment_id FROM {$payments_table} WHERE id = %d", $payment_id));

        if (!$appointment_id) {
            return new WP_REST_Response(array('message' => __('Payment not found or not linked to an appointment.', 'schedula-smart-appointment-booking')), 404);
        }

        // 1. Delete the payment record
        $deleted_payment = $wpdb->delete($payments_table, array('id' => $payment_id));
        if ($deleted_payment === false) {
            error_log('Schedula API Error: Failed to delete payment ID ' . $payment_id . '. DB Error: ' . $wpdb->last_error);
            return new WP_REST_Response(array('message' => __('Failed to delete payment.', 'schedula-smart-appointment-booking'), 'db_error' => $wpdb->last_error), 500);
        }

        // 2. Delete associated customer_appointments record(s)
        $deleted_customer_appointments = $wpdb->delete($customer_appointments_table, array('appointment_id' => $appointment_id));
        if ($deleted_customer_appointments === false) {
            error_log('Schedula API Error: Failed to delete associated customer_appointments for appointment ID ' . $appointment_id . '. DB Error: ' . $wpdb->last_error);
            // Continue with appointment deletion, but log the error
        }

        // 3. Delete the associated appointment record
        $deleted_appointment = $wpdb->delete($appointments_table, array('id' => $appointment_id));
        if ($deleted_appointment === false) {
            error_log('Schedula API Error: Failed to delete associated appointment ID ' . $appointment_id . '. DB Error: ' . $wpdb->last_error);
            return new WP_REST_Response(array('message' => __('Failed to delete associated appointment.', 'schedula-smart-appointment-booking'), 'db_error' => $wpdb->last_error), 500);
        }

        if ($deleted_payment === 0 && $deleted_appointment === 0) {
            return new WP_REST_Response(array('message' => __('Payment and associated appointment not found or already deleted.', 'schedula-smart-appointment-booking')), 404);
        }

        return new WP_REST_Response(array('message' => __('Payment and associated appointment deleted successfully.', 'schedula-smart-appointment-booking')), 200);
    }
}
