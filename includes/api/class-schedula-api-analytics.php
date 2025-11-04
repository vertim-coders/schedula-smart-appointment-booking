<?php

/**
 * REST API Endpoints for Schedula Analytics and Data Export.
 *
 * @package SCHESAB\Api
 */

namespace SCHESAB\Api;

use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use SCHESAB\Database\SCHESAB_Database; // Import the Database class

if (!defined('ABSPATH')) {
    exit;
}

class SCHESAB_Analytics
{
    private $namespace = 'schesab/v1';
    private $db;

    public function __construct()
    {
        $this->db = \SCHESAB\Database\SCHESAB_Database::get_instance(); // Get the database instance
    }

    public function register_routes()
    {
        // Data Export Endpoint
        register_rest_route($this->namespace, '/export-data', [
            'methods' => WP_REST_Server::READABLE, // GET request for data export
            'callback' => [$this, 'export_data'],
            'permission_callback' => [$this, 'check_admin_permissions'],
            'args' => [
                'export_type' => [
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => function ($param) {
                        $types = explode(',', $param);
                        foreach ($types as $type) {
                            if (!in_array(trim($type), ['appointments', 'clients', 'payments'])) {
                                return false;
                            }
                        }
                        return true;
                    },
                    'required' => true,
                    'description' => __('Comma-separated list of data types to export (e.g., appointments,clients).', 'schedula-smart-appointment-booking'),
                ],
                'start_date' => [
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => function ($param) {
                        return empty($param) || (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $param);
                    },
                    'required' => false,
                    'description' => __('Start date for data export (YYYY-MM-DD).', 'schedula-smart-appointment-booking'),
                ],
                'end_date' => [
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => function ($param) {
                        return empty($param) || (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $param);
                    },
                    'required' => false,
                    'description' => __('End date for data export (YYYY-MM-DD).', 'schedula-smart-appointment-booking'),
                ],
            ],
        ]);
    }

    /**
     * Check if the current user has admin capabilities.
     * Required for securing this endpoint.
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return bool True if the user has permissions, false otherwise.
     */
    public function check_admin_permissions(WP_REST_Request $request)
    {
        return current_user_can('manage_options'); // Only users with 'manage_options' cap can export data
    }

    /**
     * Handles the data export for various types (appointments, clients, payments).
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_REST_Response|WP_Error Response object with CSV data or WP_Error on failure.
     */
    public function export_data(WP_REST_Request $request)
    {
        global $wpdb;

        $export_types_str = $request->get_param('export_type');
        $export_types = array_map('trim', explode(',', $export_types_str));
        $start_date = $request->get_param('start_date');
        $end_date = $request->get_param('end_date');

        $appointments_table = $this->db->get_table_name('appointments');
        $customers_table = $this->db->get_table_name('customers');
        $services_table = $this->db->get_table_name('services');
        $staff_table = $this->db->get_table_name('staff');
        $payments_table = $this->db->get_table_name('payments');

        // Use in-memory string instead of file operations
        $csv_content = chr(0xEF) . chr(0xBB) . chr(0xBF); // Add UTF-8 BOM

        $delimiter = ';';
        $enclosure = '"';

        $is_first_table = true;

        foreach ($export_types as $export_type) {
            if (!$is_first_table) {
                $csv_content .= "\n\n";
            }

            $headers = [];
            $data = [];
            $keys = [];

            switch ($export_type) {
                case 'appointments':
                    $headers = [
                        __('ID', 'schedula-smart-appointment-booking'),
                        __('Service', 'schedula-smart-appointment-booking'),
                        __('Staff', 'schedula-smart-appointment-booking'),
                        __('Customer Name', 'schedula-smart-appointment-booking'),
                        __('Customer Email', 'schedula-smart-appointment-booking'),
                        __('Customer Phone', 'schedula-smart-appointment-booking'),
                        __('Start Date/Time', 'schedula-smart-appointment-booking'),
                        __('End Date/Time', 'schedula-smart-appointment-booking'),
                        __('Duration (mins)', 'schedula-smart-appointment-booking'),
                        __('Price', 'schedula-smart-appointment-booking'),
                        __('Status', 'schedula-smart-appointment-booking'),
                        __('Payment Status', 'schedula-smart-appointment-booking'),
                        __('Notes', 'schedula-smart-appointment-booking'),
                        __('Created At', 'schedula-smart-appointment-booking')
                    ];
                    $keys = [
                        'id',
                        'service_title',
                        'staff_name',
                        'customer_name',
                        'customer_email',
                        'customer_phone',
                        'start_datetime',
                        'end_datetime',
                        'duration',
                        'price',
                        'status',
                        'payment_status',
                        'notes',
                        'created_at'
                    ];

                    if (!empty($start_date) && !empty($end_date)) {
                        $data = $wpdb->get_results($wpdb->prepare(
                            "SELECT a.id, s.title AS service_title, st.name AS staff_name, a.customer_name, a.customer_email, a.customer_phone, a.start_datetime, a.end_datetime, a.duration, a.price, a.status, a.payment_status, a.notes, a.created_at\n                            FROM {$appointments_table} AS a\n                            LEFT JOIN {$services_table} AS s ON a.service_id = s.id\n                            LEFT JOIN {$staff_table} AS st ON a.staff_id = st.id\n                            WHERE DATE(a.start_datetime) >= %s AND DATE(a.start_datetime) <= %s\n                            ORDER BY a.start_datetime ASC",
                            $start_date,
                            $end_date
                        ), ARRAY_A);
                    } elseif (!empty($start_date)) {
                        $data = $wpdb->get_results($wpdb->prepare(
                            "SELECT a.id, s.title AS service_title, st.name AS staff_name, a.customer_name, a.customer_email, a.customer_phone, a.start_datetime, a.end_datetime, a.duration, a.price, a.status, a.payment_status, a.notes, a.created_at\n                            FROM {$appointments_table} AS a\n                            LEFT JOIN {$services_table} AS s ON a.service_id = s.id\n                            LEFT JOIN {$staff_table} AS st ON a.staff_id = st.id\n                            WHERE DATE(a.start_datetime) >= %s\n                            ORDER BY a.start_datetime ASC",
                            $start_date
                        ), ARRAY_A);
                    } elseif (!empty($end_date)) {
                        $data = $wpdb->get_results($wpdb->prepare(
                            "SELECT a.id, s.title AS service_title, st.name AS staff_name, a.customer_name, a.customer_email, a.customer_phone, a.start_datetime, a.end_datetime, a.duration, a.price, a.status, a.payment_status, a.notes, a.created_at\n                            FROM {$appointments_table} AS a\n                            LEFT JOIN {$services_table} AS s ON a.service_id = s.id\n                            LEFT JOIN {$staff_table} AS st ON a.staff_id = st.id\n                            WHERE DATE(a.start_datetime) <= %s\n                            ORDER BY a.start_datetime ASC",
                            $end_date
                        ), ARRAY_A);
                    } else {
                        $data = $wpdb->get_results($wpdb->prepare(
                            "SELECT a.id, s.title AS service_title, st.name AS staff_name, a.customer_name, a.customer_email, a.customer_phone, a.start_datetime, a.end_datetime, a.duration, a.price, a.status, a.payment_status, a.notes, a.created_at\n                            FROM {$appointments_table} AS a\n                            LEFT JOIN {$services_table} AS s ON a.service_id = s.id\n                            LEFT JOIN {$staff_table} AS st ON a.staff_id = st.id\n                            ORDER BY a.start_datetime ASC"
                        ), ARRAY_A);
                    }
                    break;

                case 'clients':
                    $headers = [__('ID', 'schedula-smart-appointment-booking'), __('First Name', 'schedula-smart-appointment-booking'), __('Last Name', 'schedula-smart-appointment-booking'), __('Email', 'schedula-smart-appointment-booking'), __('Phone', 'schedula-smart-appointment-booking'), __('Notes', 'schedula-smart-appointment-booking'), __('Created At', 'schedula-smart-appointment-booking')];
                    $keys = ['id', 'first_name', 'last_name', 'email', 'phone', 'notes', 'created_at'];

                    if (!empty($start_date) && !empty($end_date)) {
                        $data = $wpdb->get_results($wpdb->prepare(
                            "SELECT id, first_name, last_name, email, phone, notes, created_at FROM {$customers_table}
                            WHERE DATE(created_at) >= %s AND DATE(created_at) <= %s\n                            ORDER BY created_at ASC",
                            $start_date,
                            $end_date
                        ), ARRAY_A);
                    } elseif (!empty($start_date)) {
                        $data = $wpdb->get_results($wpdb->prepare(
                            "SELECT id, first_name, last_name, email, phone, notes, created_at FROM {$customers_table}
                            WHERE DATE(created_at) >= %s\n                            ORDER BY created_at ASC",
                            $start_date
                        ), ARRAY_A);
                    } elseif (!empty($end_date)) {
                        $data = $wpdb->get_results($wpdb->prepare(
                            "SELECT id, first_name, last_name, email, phone, notes, created_at FROM {$customers_table}
                            WHERE DATE(created_at) <= %s\n                            ORDER BY created_at ASC",
                            $end_date
                        ), ARRAY_A);
                    } else {
                        $data = $wpdb->get_results($wpdb->prepare(
                            "SELECT id, first_name, last_name, email, phone, notes, created_at FROM {$customers_table}
                            ORDER BY created_at ASC"
                        ), ARRAY_A);
                    }
                    break;

                case 'payments':
                    $headers = [
                        __('ID', 'schedula-smart-appointment-booking'),
                        __('Appointment ID', 'schedula-smart-appointment-booking'),
                        __('Order ID', 'schedula-smart-appointment-booking'),
                        __('Amount', 'schedula-smart-appointment-booking'),
                        __('Payment Type', 'schedula-smart-appointment-booking'),
                        __('Transaction ID', 'schedula-smart-appointment-booking'),
                        __('Payment Date', 'schedula-smart-appointment-booking'),
                        __('Status', 'schedula-smart-appointment-booking'),
                        __('Notes', 'schedula-smart-appointment-booking'),
                        __('Created At', 'schedula-smart-appointment-booking')
                    ];
                    $keys = [
                        'id',
                        'appointment_id',
                        'order_id',
                        'amount',
                        'payment_type',
                        'transaction_id',
                        'payment_date',
                        'status',
                        'notes',
                        'created_at'
                    ];

                    if (!empty($start_date) && !empty($end_date)) {
                        $data = $wpdb->get_results($wpdb->prepare(
                            "SELECT id, appointment_id, order_id, amount, payment_type, transaction_id, payment_date, status, notes, created_at FROM {$payments_table} AS p\n                            WHERE DATE(p.payment_date) >= %s AND DATE(p.payment_date) <= %s\n                            ORDER BY p.payment_date ASC",
                            $start_date,
                            $end_date
                        ), ARRAY_A);
                    } elseif (!empty($start_date)) {
                        $data = $wpdb->get_results($wpdb->prepare(
                            "SELECT id, appointment_id, order_id, amount, payment_type, transaction_id, payment_date, status, notes, created_at FROM {$payments_table} AS p\n                            WHERE DATE(p.payment_date) >= %s\n                            ORDER BY p.payment_date ASC",
                            $start_date
                        ), ARRAY_A);
                    } elseif (!empty($end_date)) {
                        $data = $wpdb->get_results($wpdb->prepare(
                            "SELECT id, appointment_id, order_id, amount, payment_type, transaction_id, payment_date, status, notes, created_at FROM {$payments_table} AS p\n                            WHERE DATE(p.payment_date) <= %s\n                            ORDER BY p.payment_date ASC",
                            $end_date
                        ), ARRAY_A);
                    } else {
                        $data = $wpdb->get_results($wpdb->prepare(
                            "SELECT id, appointment_id, order_id, amount, payment_type, transaction_id, payment_date, status, notes, created_at FROM {$payments_table} AS p\n                            ORDER BY p.payment_date ASC"
                        ), ARRAY_A);
                    }
                    break;
                default:
                    continue 2;
            }

            $table_title = '';
            switch ($export_type) {
                case 'appointments':
                    $table_title = __('Appointments', 'schedula-smart-appointment-booking');
                    break;
                case 'clients':
                    $table_title = __('Clients', 'schedula-smart-appointment-booking');
                    break;
                case 'payments':
                    $table_title = __('Payments', 'schedula-smart-appointment-booking');
                    break;
            }
            if ($table_title) {
                $csv_content .= $this->array_to_csv([$table_title], $delimiter, $enclosure) . "\n";
            }

            $csv_content .= $this->array_to_csv($headers, $delimiter, $enclosure) . "\n";

            if (!empty($data)) {
                foreach ($data as $row) {
                    $csv_row = [];
                    foreach ($keys as $key) {
                        $csv_row[] = $row[$key] ?? '';
                    }
                    $csv_content .= $this->array_to_csv($csv_row, $delimiter, $enclosure) . "\n";
                }
            }
            $is_first_table = false;
        }

        $filename = 'schedula-export.csv';
        if (count($export_types) === 1) {
            $filename = 'schedula-' . $export_types[0] . '-export.csv';
        }

        $response = new WP_REST_Response($csv_content, 200);
        $response->header('Content-Type', 'text/csv; charset=utf-8');
        $response->header('Content-Disposition', 'attachment; filename="' . $filename . '"');

        return $response;
    }
    /**
     * Helper method to convert array to CSV string
     */
    private function array_to_csv($array, $delimiter = ',', $enclosure = '"')
    {
        $output = '';
        foreach ($array as $field) {
            if ($output !== '') {
                $output .= $delimiter;
            }
            $output .= $enclosure . str_replace($enclosure, $enclosure . $enclosure, $field) . $enclosure;
        }
        return $output;
    }
}
