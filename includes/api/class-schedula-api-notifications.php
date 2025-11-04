<?php

/**
 * Schedula Notification Settings API.
 * Handles REST API endpoints for email notification settings and email log.
 * Also contains the core logic for sending emails.
 *
 * @package SCHESAB\Api
 */

namespace SCHESAB\Api;

use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use SCHESAB\Utils\SCHESAB_Encryption;

if (!defined('ABSPATH')) {
    exit;
}

class SCHESAB_Notifications
{
    private $namespace = 'schesab/v1';
    private $settings_key = 'schesab_notification_settings'; // Option key to store settings in DB

    public function __construct()
    {
        add_action('rest_api_init', [$this, 'register_routes']);
        add_action('schesab_send_notification_hook', [$this, 'handle_scheduled_notification'], 10, 1);
        add_action('phpmailer_init', [$this, 'configure_smtp']);
    }

    public function register_routes()
    {
        register_rest_route($this->namespace, '/notification-settings', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_notification_settings'],
                'permission_callback' => [$this, 'admin_permissions_check'],
            ],
            [
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => [$this, 'update_notification_settings'],
                'permission_callback' => [$this, 'admin_permissions_check'],
                'args' => $this->get_settings_args(),
            ],
            [
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => [$this, 'reset_notification_settings'],
                'permission_callback' => [$this, 'admin_permissions_check'],
            ],
            'schema' => [$this, 'get_item_schema'],
        ]);

        register_rest_route($this->namespace, '/notification-settings/test', [
            [
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => [$this, 'send_test_email'],
                'permission_callback' => [$this, 'admin_permissions_check'],
            ],
        ]);

        register_rest_route($this->namespace, '/email-log', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_email_log'],
                'permission_callback' => [$this, 'admin_permissions_check'],
                'args' => [
                    'search' => [
                        'sanitize_callback' => 'sanitize_text_field',
                        'required' => false,
                    ],
                    'page' => [
                        'sanitize_callback' => 'absint',
                        'default' => 1,
                        'required' => false,
                    ],
                    'per_page' => [
                        'sanitize_callback' => 'absint',
                        'default' => 10,
                        'required' => false,
                    ],
                    'sort_by' => [
                        'sanitize_callback' => 'sanitize_text_field',
                        'validate_callback' => function ($param) {
                            return in_array($param, ['id', 'recipient', 'subject', 'sent_at', 'status']);
                        },
                        'default' => 'sent_at',
                        'required' => false,
                    ],
                    'sort_direction' => [
                        'sanitize_callback' => 'sanitize_text_field',
                        'validate_callback' => function ($param) {
                            return in_array(strtolower($param), ['asc', 'desc']);
                        },
                        'default' => 'desc',
                        'required' => false,
                    ],
                ],
            ],
            [
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => [$this, 'delete_email_log_items'],
                'permission_callback' => [$this, 'admin_permissions_check'],
                'args' => [
                    'ids' => [
                        'description' => __('An array of email log IDs to delete.', 'schedula-smart-appointment-booking'),
                        'type' => 'array',
                        'items' => ['type' => 'integer'],
                        'required' => true,
                    ],
                ],
            ],
        ]);

        register_rest_route($this->namespace, '/email-log/(?P<id>[\d]+)', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_single_email_log'],
                'permission_callback' => [$this, 'admin_permissions_check'],
                'args' => [
                    'id' => [
                        'validate_callback' => function ($param, $request, $key) {
                            return is_numeric($param);
                        }
                    ],
                ],
            ],
        ]);
    }

    // =================================================================
    // CORE NOTIFICATION SENDING LOGIC
    // =================================================================

    /**
     * Handles the asynchronous sending of notifications scheduled via WP-Cron.
     *
     * @param array $args Arguments passed from the cron event, including 'type' and 'appointment_id'.
     */
    public function handle_scheduled_notification($args)
    {
        $type = isset($args['type']) ? $args['type'] : '';
        $appointment_details = isset($args['appointment_details']) ? $args['appointment_details'] : null;
        $appointment_id = isset($args['appointment_id']) ? $args['appointment_id'] : ($appointment_details['id'] ?? 0);

        if (empty($type) || (empty($appointment_id) && empty($appointment_details))) {
            return;
        }

        // If full details are passed (e.g., for a deleted appointment), use them directly.
        if (!empty($appointment_details)) {
            $this->send_notification($type, $appointment_details);
            return;
        }

        // Fallback for hooks that only pass an ID; re-fetch to ensure data freshness.
        $appointments_api = new \SCHESAB\Api\SCHESAB_Appointments();
        $request = new \WP_REST_Request('GET');
        $request->set_param('id', $appointment_id);
        $response = $appointments_api->get_appointment($request);

        if ($response->get_status() === 200) {
            $appointment_details = $response->get_data();
            $this->send_notification($type, $appointment_details);
        } else {
        }
    }

    /**
     * Main function to send a notification based on a type and appointment data.
     *
     * @param string $type The type of notification (e.g., 'new_booking_client', 'cancelled_booking_staff').
     * @param array $appointment_details The details of the appointment.
     * @return bool True on success, false on failure.
     */
    public function send_notification($type, $appointment_details)
    {
        $settings = get_option($this->settings_key, []);
        $defaults = $this->get_default_settings();
        $settings = array_merge($defaults, $settings);

        $template_keys = $this->get_template_keys_for_type($type);
        if (!$template_keys) {
            return false;
        }

        // Check if this notification type is enabled
        if (empty($settings[$template_keys['enableKey']])) {
            return false; // Notification is disabled, so we just stop here.
        }

        $recipient = $this->get_recipient_for_type($type, $appointment_details);
        if (!$recipient) {
            return false;
        }

        $subject_template = $settings[$template_keys['subjectKey']];
        $body_template = $settings[$template_keys['bodyKey']];

        $placeholders = $this->get_placeholders_and_values($appointment_details);

        $subject = str_replace(array_keys($placeholders), array_values($placeholders), $subject_template);
        $body = str_replace(array_keys($placeholders), array_values($placeholders), $body_template);

        $sender_name = $settings['senderName'];
        $sender_email = $settings['senderEmail'];
        $content_type = $settings['emailContentType'];

        $headers = [
            "From: {$sender_name} <{$sender_email}>",
            "Content-Type: text/{$content_type}; charset=UTF-8"
        ];

        $sent = wp_mail($recipient, $subject, $body, $headers);

        $this->log_email($recipient, $subject, $body, $sent ? 'success' : 'failed');

        return $sent;
    }

    /**
     * Configures PHPMailer to use SMTP if enabled.
     *
     * @param \PHPMailer $phpmailer The PHPMailer object.
     */
    public function configure_smtp(&$phpmailer)
    {
        $settings = get_option($this->settings_key, []);
        $defaults = $this->get_default_settings();
        $settings = array_merge($defaults, $settings);

        if (empty($settings['smtpEnabled'])) {
            return;
        }

        $phpmailer->isSMTP();
        $phpmailer->Host = $settings['smtpHost'];
        $phpmailer->Port = $settings['smtpPort'];

        if (!empty($settings['smtpEncryption']) && $settings['smtpEncryption'] !== 'none') {
            $phpmailer->SMTPSecure = $settings['smtpEncryption'];
        }

        if (!empty($settings['smtpAuth'])) {
            $phpmailer->SMTPAuth = true;
            $phpmailer->Username = $settings['smtpUsername'];
            $phpmailer->Password = SCHESAB_Encryption::decrypt_data($settings['smtpPassword']);
        }
    }

    /**
     * Sends a test email to the admin.
     */
    public function send_test_email(WP_REST_Request $request)
    {
        $settings = get_option($this->settings_key, []);
        $defaults = $this->get_default_settings();
        $settings = array_merge($defaults, $settings);

        $recipient = $settings['adminRecipientEmail'] ?? get_bloginfo('admin_email');
        $subject = __('Schedula SMTP Test', 'schedula-smart-appointment-booking');
        $body = __('This is a test email to verify your SMTP settings in Schedula.', 'schedula-smart-appointment-booking');

        $sender_name = $settings['senderName'];
        $sender_email = $settings['senderEmail'];
        $content_type = $settings['emailContentType'];

        $headers = [
            "From: {$sender_name} <{$sender_email}>",
            "Content-Type: text/{$content_type}; charset=UTF-8"
        ];

        $sent = wp_mail($recipient, $subject, $body, $headers);

        if ($sent) {
            $message = sprintf(
                // translators: %s is the recipient email address
                __('Test email sent successfully to %s.', 'schedula-smart-appointment-booking'),
                $recipient
            );
            return new WP_REST_Response(['message' => $message], 200);
        } else {
            global $schesab_mail_errors;
            $error_message = implode("\n", $schesab_mail_errors ?? [__('Unknown error', 'schedula-smart-appointment-booking')]);
            // translators: %s is the error message
            return new WP_Error('send_error', sprintf(__('Failed to send test email. Error: %s', 'schedula-smart-appointment-booking'), $error_message), ['status' => 500]);
        }
    }

    /**
     * Logs a system notice to the database, visible in the email log.
     *
     * @param string $subject The subject or title of the notice.
     * @param string $body The main content of the notice.
     */
    public function log_system_notice($subject, $body)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'schesab_email_log';

        $wpdb->insert($table_name, [
            'recipient' => __('System', 'schedula-smart-appointment-booking'),
            'subject' => $subject,
            'body' => $body,
            'sent_at' => current_time('mysql'),
            'status' => 'notice', // Use a distinct status
        ]);
    }

    /**
     * Determines the recipient's email address based on the notification type.
     */
    private function get_recipient_for_type($type, $appointment_details)
    {
        if (strpos($type, '_staff') !== false) {
            if (empty($appointment_details['staff_id'])) {
                return null; // No staff assigned
            }
            global $wpdb;
            $staff_table = $wpdb->prefix . 'schesab_staff';
            return $wpdb->get_var($wpdb->prepare("SELECT email FROM {$staff_table} WHERE id = %d", $appointment_details['staff_id']));
        }

        if (strpos($type, '_client') !== false) {
            return $appointment_details['customer_email'] ?? null;
        }

        if (strpos($type, '_admin') !== false) {
            $settings = get_option($this->settings_key, []);
            $defaults = $this->get_default_settings();
            $settings = array_merge($defaults, $settings);
            return $settings['adminRecipientEmail'] ?? null;
        }

        return null;
    }

    /**
     * Maps a notification type to its corresponding settings keys.
     */
    private function get_template_keys_for_type($type)
    {
        $map = [
            'new_booking_client' => ['enableKey' => 'enableNewBookingToClient', 'subjectKey' => 'newBookingToClientSubject', 'bodyKey' => 'newBookingToClientBody'],
            'new_booking_staff' => ['enableKey' => 'enableNewBookingToStaff', 'subjectKey' => 'newBookingToStaffSubject', 'bodyKey' => 'newBookingToStaffBody'],
            'new_booking_admin' => ['enableKey' => 'enableNewBookingToAdmin', 'subjectKey' => 'newBookingToAdminSubject', 'bodyKey' => 'newBookingToAdminBody'],

            'pending_group_booking_client' => ['enableKey' => 'enablePendingGroupBookingToClient', 'subjectKey' => 'pendingGroupBookingToClientSubject', 'bodyKey' => 'pendingGroupBookingToClientBody'],
            'pending_group_booking_staff' => ['enableKey' => 'enablePendingGroupBookingToStaff', 'subjectKey' => 'pendingGroupBookingToStaffSubject', 'bodyKey' => 'pendingGroupBookingToStaffBody'],
            'pending_group_booking_admin' => ['enableKey' => 'enablePendingGroupBookingToAdmin', 'subjectKey' => 'pendingGroupBookingToAdminSubject', 'bodyKey' => 'pendingGroupBookingToAdminBody'],

            'confirmed_group_booking_client' => ['enableKey' => 'enableConfirmedGroupBookingToClient', 'subjectKey' => 'confirmedGroupBookingToClientSubject', 'bodyKey' => 'confirmedGroupBookingToClientBody'],
            'confirmed_group_booking_staff' => ['enableKey' => 'enableConfirmedGroupBookingToStaff', 'subjectKey' => 'confirmedGroupBookingToStaffSubject', 'bodyKey' => 'confirmedGroupBookingToStaffBody'],
            'confirmed_group_booking_admin' => ['enableKey' => 'enableConfirmedGroupBookingToAdmin', 'subjectKey' => 'confirmedGroupBookingToAdminSubject', 'bodyKey' => 'confirmedGroupBookingToAdminBody'],

            'pending_recurring_booking_client' => ['enableKey' => 'enablePendingRecurringBookingToClient', 'subjectKey' => 'pendingRecurringBookingToClientSubject', 'bodyKey' => 'pendingRecurringBookingToClientBody'],
            'pending_recurring_booking_staff' => ['enableKey' => 'enablePendingRecurringBookingToStaff', 'subjectKey' => 'pendingRecurringBookingToStaffSubject', 'bodyKey' => 'pendingRecurringBookingToStaffBody'],
            'pending_recurring_booking_admin' => ['enableKey' => 'enablePendingRecurringBookingToAdmin', 'subjectKey' => 'pendingRecurringBookingToAdminSubject', 'bodyKey' => 'pendingRecurringBookingToAdminBody'],

            'confirmed_recurring_booking_client' => ['enableKey' => 'enableConfirmedRecurringBookingToClient', 'subjectKey' => 'confirmedRecurringBookingToClientSubject', 'bodyKey' => 'confirmedRecurringBookingToClientBody'],
            'confirmed_recurring_booking_staff' => ['enableKey' => 'enableConfirmedRecurringBookingToStaff', 'subjectKey' => 'confirmedRecurringBookingToStaffSubject', 'bodyKey' => 'confirmedRecurringBookingToStaffBody'],
            'confirmed_recurring_booking_admin' => ['enableKey' => 'enableConfirmedRecurringBookingToAdmin', 'subjectKey' => 'confirmedRecurringBookingToAdminSubject', 'bodyKey' => 'confirmedRecurringBookingToAdminBody'],

            'pending_recurring_group_booking_client' => ['enableKey' => 'enablePendingRecurringGroupBookingToClient', 'subjectKey' => 'pendingRecurringGroupBookingToClientSubject', 'bodyKey' => 'pendingRecurringGroupBookingToClientBody'],
            'pending_recurring_group_booking_staff' => ['enableKey' => 'enablePendingRecurringGroupBookingToStaff', 'subjectKey' => 'pendingRecurringGroupBookingToStaffSubject', 'bodyKey' => 'pendingRecurringGroupBookingToStaffBody'],
            'pending_recurring_group_booking_admin' => ['enableKey' => 'enablePendingRecurringGroupBookingToAdmin', 'subjectKey' => 'pendingRecurringGroupBookingToAdminSubject', 'bodyKey' => 'pendingRecurringGroupBookingToAdminBody'],

            'confirmed_recurring_group_booking_client' => ['enableKey' => 'enableConfirmedRecurringGroupBookingToClient', 'subjectKey' => 'confirmedRecurringGroupBookingToClientSubject', 'bodyKey' => 'confirmedRecurringGroupBookingToClientBody'],
            'confirmed_recurring_group_booking_staff' => ['enableKey' => 'enableConfirmedRecurringGroupBookingToStaff', 'subjectKey' => 'confirmedRecurringGroupBookingToStaffSubject', 'bodyKey' => 'confirmedRecurringGroupBookingToStaffBody'],
            'confirmed_recurring_group_booking_admin' => ['enableKey' => 'enableConfirmedRecurringGroupBookingToAdmin', 'subjectKey' => 'confirmedRecurringGroupBookingToAdminSubject', 'bodyKey' => 'confirmedRecurringGroupBookingToAdminBody'],

            'confirmed_booking_client' => ['enableKey' => 'enableConfirmedToClient', 'subjectKey' => 'confirmedToClientSubject', 'bodyKey' => 'confirmedToClientBody'],
            'confirmed_booking_staff' => ['enableKey' => 'enableConfirmedToStaff', 'subjectKey' => 'confirmedToStaffSubject', 'bodyKey' => 'confirmedToStaffBody'],
            'confirmed_booking_admin' => ['enableKey' => 'enableConfirmedToAdmin', 'subjectKey' => 'confirmedToAdminSubject', 'bodyKey' => 'confirmedToAdminBody'],

            'cancelled_booking_client' => ['enableKey' => 'enableCancelledToClient', 'subjectKey' => 'cancelledToClientSubject', 'bodyKey' => 'cancelledToClientBody'],
            'cancelled_booking_staff' => ['enableKey' => 'enableCancelledToStaff', 'subjectKey' => 'cancelledToStaffSubject', 'bodyKey' => 'cancelledToStaffBody'],
            'cancelled_booking_admin' => ['enableKey' => 'enableCancelledToAdmin', 'subjectKey' => 'cancelledToAdminSubject', 'bodyKey' => 'cancelledToAdminBody'],

            'rejected_booking_client' => ['enableKey' => 'enableRejectedToClient', 'subjectKey' => 'rejectedToClientSubject', 'bodyKey' => 'rejectedToClientBody'],
            'rejected_booking_staff' => ['enableKey' => 'enableRejectedToStaff', 'subjectKey' => 'rejectedToStaffSubject', 'bodyKey' => 'rejectedToStaffBody'],
            'rejected_booking_admin' => ['enableKey' => 'enableRejectedToAdmin', 'subjectKey' => 'rejectedToAdminSubject', 'bodyKey' => 'rejectedToAdminBody'],
        ];
        return $map[$type] ?? null;
    }

    /**
     * Gathers all possible data points and creates the placeholder array.
     */
    private function get_placeholders_and_values($appointment)
    {
        $start_timestamp = strtotime($appointment['start_datetime']);

        // Fallback for potentially missing data
        $staff_name = !empty($appointment['staff_name']) ? $appointment['staff_name'] : __('N/A', 'schedula-smart-appointment-booking');
        $service_title = !empty($appointment['service_title']) ? $appointment['service_title'] : __('N/A', 'schedula-smart-appointment-booking');
        $customer_name = !empty($appointment['customer_name']) ? $appointment['customer_name'] : __('N/A', 'schedula-smart-appointment-booking');

        // Ensure a cancellation token exists, creating one if necessary for older appointments.
        if (empty($appointment['cancellation_token'])) {
            global $wpdb;
            $appointments_table = $wpdb->prefix . 'schesab_appointments';
            $new_token = wp_generate_uuid4();

            $wpdb->update(
                $appointments_table,
                ['cancellation_token' => $new_token],
                ['id' => $appointment['id']],
                ['%s'],
                ['%d']
            );

            // Update the local appointment array so the new token is used immediately.
            $appointment['cancellation_token'] = $new_token;
        }

        // --- NEW: Cancellation Policy and Link Placeholders ---
        $general_settings = get_option('schesab_general_settings', []);
        $min_time_canceling = isset($general_settings['minTimeCanceling']) ? intval($general_settings['minTimeCanceling']) : 0;
        $cancellation_policy_notice = '';
        if ($min_time_canceling > 0) {
            $cutoff_timestamp = $start_timestamp - ($min_time_canceling * 60);
            $formatted_cutoff = date_i18n(get_option('date_format') . ' ' . 'g:i a', $cutoff_timestamp);
            $cancellation_policy_notice = sprintf(
                // translators: %s is the cutoff time
                __('Please note: This appointment can only be cancelled before %s.', 'schedula-smart-appointment-booking'),
                $formatted_cutoff
            );
        } else {
            $cancellation_policy_notice = __('You can cancel this appointment at any time.', 'schedula-smart-appointment-booking');
        }

        $cancellation_link = home_url('/schedula/cancel/' . ($appointment['cancellation_token'] ?? ''));

        // NEW PLACEHOLDERS
        $number_of_persons = !empty($appointment['number_of_persons']) ? $appointment['number_of_persons'] : 1;

        $recurrence_details = '';
        if (!empty($appointment['recurrence_frequency'])) {
            $interval = (int) $appointment['recurrence_interval'];
            $freq_text = $appointment['recurrence_frequency'];
            $freq_text_singular = rtrim($freq_text, 'ly');
            if ($freq_text_singular === 'dai') { // Fix for 'daily'
                $freq_text_singular = 'day';
            }


            if ($interval > 1) {
                // translators: %1$d is the interval number, %2$s is the frequency unit (e.g., "days", "weeks")
                $message = __('This appointment repeats every %1$d %2$s', 'schedula-smart-appointment-booking');

                $frequency_plural = '';
                switch ($freq_text_singular) {
                    case 'day':
                        $frequency_plural = _n('day', 'days', $interval, 'schedula-smart-appointment-booking');
                        break;
                    case 'week':
                        $frequency_plural = _n('week', 'weeks', $interval, 'schedula-smart-appointment-booking');
                        break;
                    case 'month':
                        $frequency_plural = _n('month', 'months', $interval, 'schedula-smart-appointment-booking');
                        break;
                    case 'year':
                        $frequency_plural = _n('year', 'years', $interval, 'schedula-smart-appointment-booking');
                        break;
                    default:
                        // Fallback for custom frequencies, though not ideal for translation.
                        $frequency_plural = $freq_text_singular . 's';
                }
                $recurrence_details = sprintf($message, $interval, $frequency_plural);
            } else {
                // translators: %s is the frequency description (e.g., "weekly", "daily")
                $message = __('This appointment repeats %s', 'schedula-smart-appointment-booking');

                $frequency_text = '';
                switch ($freq_text) {
                    case 'daily':
                        $frequency_text = __('daily', 'schedula-smart-appointment-booking');
                        break;
                    case 'weekly':
                        $frequency_text = __('weekly', 'schedula-smart-appointment-booking');
                        break;
                    case 'monthly':
                        $frequency_text = __('monthly', 'schedula-smart-appointment-booking');
                        break;
                    case 'yearly':
                        $frequency_text = __('yearly', 'schedula-smart-appointment-booking');
                        break;
                    default:
                        $frequency_text = $freq_text;
                }
                $recurrence_details = sprintf($message, ucfirst($frequency_text));
            }

            if (!empty($appointment['recurrence_occurrence_count'])) {
                // translators: %d is the number of occurrences
                $recurrence_details .= sprintf(__(' for %d occurrences.', 'schedula-smart-appointment-booking'), $appointment['recurrence_occurrence_count']);
            } elseif (!empty($appointment['recurrence_end_date'])) {
                $end_date_timestamp = strtotime($appointment['recurrence_end_date']);
                // translators: %s is the end date
                $recurrence_details .= sprintf(__(' until %s.', 'schedula-smart-appointment-booking'), date_i18n(get_option('date_format'), $end_date_timestamp));
            }
        }

        return [
            '{{client_name}}' => $customer_name,
            '{{staff_name}}' => $staff_name,
            '{{service_name}}' => $service_title,
            '{{appointment_date}}' => date_i18n(get_option('date_format'), $start_timestamp),
            '{{appointment_time}}' => date_i18n('g:i a', $start_timestamp),
            '{{appointment_status}}' => ucfirst($appointment['status']),
            '{{business_name}}' => get_bloginfo('name'),
            '{{business_website}}' => home_url(),
            '{{price}}' => $appointment['price'],
            '{{duration}}' => $appointment['duration'],
            '{{cancellation_link}}' => $cancellation_link,
            '{{cancellation_policy_notice}}' => $cancellation_policy_notice,
            '{{number_of_persons}}' => $number_of_persons,
            '{{recurrence_details}}' => $recurrence_details,
        ];
    }

    /**
     * Logs an email attempt to the database.
     */
    private function log_email($recipient, $subject, $body, $status)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'schesab_email_log';

        $wpdb->insert($table_name, [
            'recipient' => $recipient,
            'subject' => $subject,
            'body' => $body,
            'sent_at' => current_time('mysql'),
            'status' => $status, // 'success' or 'failed'
        ]);
    }


    // =================================================================
    // NOTIFICATION SETTINGS
    // =================================================================

    public function get_notification_settings(WP_REST_Request $request)
    {
        $settings = get_option($this->settings_key, []);
        $defaults = $this->get_default_settings();
        $settings = array_merge($defaults, $settings);

        // Don't expose the password, just whether it's set
        $settings['smtpPasswordSet'] = !empty($settings['smtpPassword']);
        unset($settings['smtpPassword']);

        return new WP_REST_Response($settings, 200);
    }

    public function update_notification_settings(WP_REST_Request $request)
    {
        $current_settings = get_option($this->settings_key, []);
        $schema = $this->get_item_schema();
        $valid_settings = [];

        foreach ($schema['properties'] as $field => $properties) {
            if ($request->has_param($field)) {
                $value = $request->get_param($field);

                // Special handling for password to avoid saving an empty one
                if ($field === 'smtpPassword' && empty($value)) {
                    if (isset($current_settings['smtpPassword'])) {
                        $valid_settings['smtpPassword'] = $current_settings['smtpPassword'];
                    }
                    continue;
                }

                $sanitized_value = $this->sanitize_setting_value($value, $properties);
                $validation_result = $this->validate_setting_value($sanitized_value, $properties, $field);

                if (is_wp_error($validation_result)) {
                    return $validation_result;
                }
                $valid_settings[$field] = $sanitized_value;
            }
        }

        // Encrypt password if it is set
        if (!empty($valid_settings['smtpPassword'])) {
            $valid_settings['smtpPassword'] = SCHESAB_Encryption::encrypt_data($valid_settings['smtpPassword']);
        }

        $final_settings = array_merge($current_settings, $valid_settings);

        // Validate required fields if SMTP is enabled
        if ($final_settings['smtpEnabled']) {
            if (empty($final_settings['smtpHost'])) {
                return new WP_Error('rest_invalid_param', __('SMTP Host is required when SMTP is enabled.', 'schedula-smart-appointment-booking'), ['status' => 400]);
            }
            if (empty($final_settings['smtpPort'])) {
                return new WP_Error('rest_invalid_param', __('SMTP Port is required when SMTP is enabled.', 'schedula-smart-appointment-booking'), ['status' => 400]);
            }
            if ($final_settings['smtpAuth']) {
                if (empty($final_settings['smtpUsername'])) {
                    return new WP_Error('rest_invalid_param', __('SMTP Username is required when SMTP authentication is enabled.', 'schedula-smart-appointment-booking'), ['status' => 400]);
                }
                if (empty($final_settings['smtpPassword'])) {
                    return new WP_Error('rest_invalid_param', __('SMTP Password is required when SMTP authentication is enabled.', 'schedula-smart-appointment-booking'), ['status' => 400]);
                }
            }
        }

        update_option($this->settings_key, $final_settings);

        return new WP_REST_Response(['message' => __('Notification settings updated successfully.', 'schedula-smart-appointment-booking')], 200);
    }

    public function reset_notification_settings(WP_REST_Request $request)
    {
        delete_option($this->settings_key);
        return new WP_REST_Response(['message' => __('Notification settings reset to default.', 'schedula-smart-appointment-booking')], 200);
    }

    // =================================================================
    // EMAIL LOG
    // =================================================================

    public function get_email_log(WP_REST_Request $request)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'schesab_email_log';

        $search = $request->get_param('search');
        $page = $request->get_param('page');
        $per_page = $request->get_param('per_page');
        $sort_by = $request->get_param('sort_by');
        $sort_direction = strtoupper($request->get_param('sort_direction'));

        $allowed_sort_by = ['id', 'recipient', 'subject', 'sent_at', 'status'];
        if (!in_array($sort_by, $allowed_sort_by)) {
            $sort_by = 'sent_at';
        }
        if (!in_array($sort_direction, ['ASC', 'DESC'])) {
            $sort_direction = 'DESC';
        }

        $where_clauses = [];
        $params = [];
        if (!empty($search)) {
            $search_like = '%' . $wpdb->esc_like($search) . '%';
            $where_clauses[] = "(recipient LIKE %s OR subject LIKE %s)";
            $params[] = $search_like;
            $params[] = $search_like;
        }

        $count_sql = "SELECT COUNT(id) FROM {$table_name}";
        if (!empty($where_clauses)) {
            $count_sql .= " WHERE " . implode(" AND ", $where_clauses);
        }
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        $total_items = $wpdb->get_var($wpdb->prepare($count_sql, ...$params));

        $offset = ($page - 1) * $per_page;

        $sql = "SELECT id, recipient, subject, sent_at, status FROM {$table_name}"; // Body excluded for performance
        if (!empty($where_clauses)) {
            $sql .= " WHERE " . implode(" AND ", $where_clauses);
        }
        $sql .= " ORDER BY {$sort_by} {$sort_direction} LIMIT %d OFFSET %d";

        $query_params = array_merge($params, [$per_page, $offset]);

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        $items = $wpdb->get_results($wpdb->prepare($sql, ...$query_params), ARRAY_A);

        return new WP_REST_Response([
            'logs' => $items,
            'total_items' => (int) $total_items,
        ], 200);
    }

    public function delete_email_log_items(WP_REST_Request $request)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'schesab_email_log';
        $ids = $request->get_param('ids');

        if (empty($ids) || !is_array($ids)) {
            return new WP_Error('rest_invalid_param', __('Invalid IDs provided.', 'schedula-smart-appointment-booking'), ['status' => 400]);
        }

        $ids = array_map('intval', $ids);
        $id_placeholders = implode(', ', array_fill(0, count($ids), '%d'));

        $query = $wpdb->prepare("DELETE FROM {$table_name} WHERE id IN ($id_placeholders)", $ids);
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        $wpdb->query($query);

        return new WP_REST_Response(['message' => __('Email log entries deleted.', 'schedula-smart-appointment-booking')], 200);
    }

    public function get_single_email_log(WP_REST_Request $request)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'schesab_email_log';
        $id = (int) $request['id'];

        $email = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id), ARRAY_A);

        if (!$email) {
            return new WP_Error('rest_not_found', __('Email log not found.', 'schedula-smart-appointment-booking'), ['status' => 404]);
        }

        return new WP_REST_Response($email, 200);
    }

    // =================================================================
    // HELPERS & PERMISSIONS
    // =================================================================

    public function admin_permissions_check(WP_REST_Request $request)
    {
        if (!current_user_can('manage_options')) {
            return new WP_Error('rest_forbidden', __('You do not have permission to manage these settings.', 'schedula-smart-appointment-booking'), ['status' => 403]);
        }
        return true;
    }

    private function sanitize_setting_value($value, $properties)
    {
        $type = $properties['type'] ?? 'string';

        if (is_null($value))
            return null;

        switch ($type) {
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
            case 'string':
                if (isset($properties['format']) && $properties['format'] === 'email') {
                    return sanitize_email($value);
                }
                if (isset($properties['context']) && in_array('template_body', $properties['context'])) {
                    return wp_kses_post($value);
                }
                return sanitize_text_field($value);
            default:
                return sanitize_text_field($value);
        }
    }

    private function validate_setting_value($value, $properties, $field)
    {
        if (isset($properties['required']) && $properties['required'] && empty($value)) {
            return new WP_Error('rest_invalid_param', sprintf(
                /* translators: %s: Field name that cannot be empty */
                __('Field %s cannot be empty.', 'schedula-smart-appointment-booking'),
                $field
            ), ['status' => 400]);
        }
    }

    protected function get_default_settings()
    {
        return [
            // General Settings
            'senderName' => get_bloginfo('name'),
            'senderEmail' => get_bloginfo('admin_email'),
            'emailContentType' => 'html',
            'adminRecipientEmail' => get_bloginfo('admin_email'), // New: Admin recipient email

            // SMTP Settings
            'smtpEnabled' => false,
            'smtpHost' => '',
            'smtpPort' => 587,
            'smtpEncryption' => 'tls',
            'smtpAuth' => true,
            'smtpUsername' => '',
            'smtpPassword' => '',

            // New Booking to Client
            'enableNewBookingToClient' => true,
            'newBookingToClientSubject' => __('Your appointment is pending confirmation', 'schedula-smart-appointment-booking'),
            'newBookingToClientBody' => __('Dear {{client_name}},<br><br>We have received your request for {{service_name}} with {{staff_name}} on {{appointment_date}} at {{appointment_time}}. We will confirm it shortly.<br><br>Thank you!<br><br><hr><p style="font-size: small; color: #777;">{{cancellation_policy_notice}}<br>To cancel this appointment, please use the following link: <a href="{{cancellation_link}}">Cancel Appointment</a></p>', 'schedula-smart-appointment-booking'),
            'newBookingToClientHeaderBgColor' => '#3498db',
            'newBookingToClientHeaderTextColor' => '#ffffff',

            // New Booking to Staff
            'enableNewBookingToStaff' => true,
            'newBookingToStaffSubject' => __('New Appointment Request: {{service_name}} with {{client_name}}', 'schedula-smart-appointment-booking'),
            'newBookingToStaffBody' => __('Hi {{staff_name}},<br><br>A new appointment has been requested by {{client_name}} for {{service_name}} on {{appointment_date}} at {{appointment_time}}. Please review and confirm it in the dashboard.<br><br>{{business_name}}', 'schedula-smart-appointment-booking'),
            'newBookingToStaffHeaderBgColor' => '#3498db',
            'newBookingToStaffHeaderTextColor' => '#ffffff',

            // Pending Group Booking to Client
            'enablePendingGroupBookingToClient' => true,
            'pendingGroupBookingToClientSubject' => __('Your group appointment is pending confirmation', 'schedula-smart-appointment-booking'),
            'pendingGroupBookingToClientBody' => __('Dear {{client_name}},<br><br>We have received your request for a group appointment ({{number_of_persons}} persons) for {{service_name}} with {{staff_name}} on {{appointment_date}} at {{appointment_time}}. Your appointment status is: {{appointment_status}}.<br><br>Thank you!<br><br><hr><p style="font-size: small; color: #777;">{{cancellation_policy_notice}}<br>To cancel this appointment, please use the following link: <a href="{{cancellation_link}}">Cancel Appointment</a></p>', 'schedula-smart-appointment-booking'),
            'pendingGroupBookingToClientHeaderBgColor' => '#3498db',
            'pendingGroupBookingToClientHeaderTextColor' => '#ffffff',

            // Pending Group Booking to Staff
            'enablePendingGroupBookingToStaff' => true,
            'pendingGroupBookingToStaffSubject' => __('New Group Appointment Request: {{service_name}} with {{client_name}}', 'schedula-smart-appointment-booking'),
            'pendingGroupBookingToStaffBody' => __('Hi {{staff_name}},<br><br>A new group appointment ({{number_of_persons}} persons) has been requested by {{client_name}} for {{service_name}} on {{appointment_date}} at {{appointment_time}}. The appointment status is: {{appointment_status}}.<br><br>{{business_name}}', 'schedula-smart-appointment-booking'),
            'pendingGroupBookingToStaffHeaderBgColor' => '#3498db',
            'pendingGroupBookingToStaffHeaderTextColor' => '#ffffff',

            // Confirmed Group Booking to Client
            'enableConfirmedGroupBookingToClient' => true,
            'confirmedGroupBookingToClientSubject' => __('Your Group Appointment is Confirmed!', 'schedula-smart-appointment-booking'),
            'confirmedGroupBookingToClientBody' => __('Dear {{client_name}},<br><br>Your group appointment for {{number_of_persons}} persons for {{service_name}} with {{staff_name}} on {{appointment_date}} at {{appointment_time}} is now confirmed. We look forward to seeing you!<br><br><hr><p style="font-size: small; color: #777;">{{cancellation_policy_notice}}<br>To cancel this appointment, please use the following link: <a href="{{cancellation_link}}">Cancel Appointment</a></p>', 'schedula-smart-appointment-booking'),
            'confirmedGroupBookingToClientHeaderBgColor' => '#3498db',
            'confirmedGroupBookingToClientHeaderTextColor' => '#ffffff',

            // Confirmed Group Booking to Staff
            'enableConfirmedGroupBookingToStaff' => true,
            'confirmedGroupBookingToStaffSubject' => __('Group Appointment Confirmed: {{service_name}} with {{client_name}}', 'schedula-smart-appointment-booking'),
            'confirmedGroupBookingToStaffBody' => __('Hi {{staff_name}},<br><br>The group appointment with {{client_name}} ({{number_of_persons}} persons) for {{service_name}} on {{appointment_date}} at {{appointment_time}} has been confirmed.', 'schedula-smart-appointment-booking'),
            'confirmedGroupBookingToStaffHeaderBgColor' => '#3498db',
            'confirmedGroupBookingToStaffHeaderTextColor' => '#ffffff',

            // Pending Recurring Booking to Client
            'enablePendingRecurringBookingToClient' => true,
            'pendingRecurringBookingToClientSubject' => __('Your recurring appointment series is pending', 'schedula-smart-appointment-booking'),
            'pendingRecurringBookingToClientBody' => __('Dear {{client_name}},<br><br>We have received your request for a recurring appointment for {{service_name}} with {{staff_name}}. The first appointment is on {{appointment_date}} at {{appointment_time}}. Your appointment status is: {{appointment_status}}.<br><br>{{recurrence_details}}<br><br>Thank you!<br><br><hr><p style="font-size: small; color: #777;">{{cancellation_policy_notice}}<br>To cancel this appointment, please use the following link: <a href="{{cancellation_link}}">Cancel Appointment</a></p>', 'schedula-smart-appointment-booking'),
            'pendingRecurringBookingToClientHeaderBgColor' => '#3498db',
            'pendingRecurringBookingToClientHeaderTextColor' => '#ffffff',

            // Pending Recurring Booking to Staff
            'enablePendingRecurringBookingToStaff' => true,
            'pendingRecurringBookingToStaffSubject' => __('New Recurring Appointment Series Request: {{service_name}} with {{client_name}}', 'schedula-smart-appointment-booking'),
            'pendingRecurringBookingToStaffBody' => __('Hi {{staff_name}},<br><br>A new recurring appointment series has been requested by {{client_name}} for {{service_name}}. The first appointment is on {{appointment_date}} at {{appointment_time}}. The appointment status is: {{appointment_status}}.<br><br>{{recurrence_details}}<br><br>{{business_name}}', 'schedula-smart-appointment-booking'),
            'pendingRecurringBookingToStaffHeaderBgColor' => '#3498db',
            'pendingRecurringBookingToStaffHeaderTextColor' => '#ffffff',

            // Confirmed Recurring Booking to Client
            'enableConfirmedRecurringBookingToClient' => true,
            'confirmedRecurringBookingToClientSubject' => __('Your Recurring Appointment Series is Confirmed!', 'schedula-smart-appointment-booking'),
            'confirmedRecurringBookingToClientBody' => __('Dear {{client_name}},<br><br>Your recurring appointment series for {{service_name}} with {{staff_name}} starting on {{appointment_date}} at {{appointment_time}} is confirmed.<br><br>{{recurrence_details}}<br><br>We look forward to seeing you!<br><br><hr><p style="font-size: small; color: #777;">{{cancellation_policy_notice}}<br>To cancel this appointment, please use the following link: <a href="{{cancellation_link}}">Cancel Appointment</a></p>', 'schedula-smart-appointment-booking'),
            'confirmedRecurringBookingToClientHeaderBgColor' => '#3498db',
            'confirmedRecurringBookingToClientHeaderTextColor' => '#ffffff',

            // Confirmed Recurring Booking to Staff
            'enableConfirmedRecurringBookingToStaff' => true,
            'confirmedRecurringBookingToStaffSubject' => __('Recurring Appointment Series Confirmed: {{service_name}} with {{client_name}}', 'schedula-smart-appointment-booking'),
            'confirmedRecurringBookingToStaffBody' => __('Hi {{staff_name}},<br><br>The recurring appointment series with {{client_name}} for {{service_name}} starting on {{appointment_date}} at {{appointment_time}} has been confirmed.<br><br>{{recurrence_details}}', 'schedula-smart-appointment-booking'),
            'confirmedRecurringBookingToStaffHeaderBgColor' => '#3498db',
            'confirmedRecurringBookingToStaffHeaderTextColor' => '#ffffff',

            // Pending Recurring Group Booking to Client
            'enablePendingRecurringGroupBookingToClient' => true,
            'pendingRecurringGroupBookingToClientSubject' => __('Your recurring group appointment series is pending', 'schedula-smart-appointment-booking'),
            'pendingRecurringGroupBookingToClientBody' => __('Dear {{client_name}},<br><br>We have received your request for a recurring group appointment ({{number_of_persons}} persons) for {{service_name}} with {{staff_name}}. The first appointment is on {{appointment_date}} at {{appointment_time}}. Your appointment status is: {{appointment_status}}.<br><br>{{recurrence_details}}<br><br>Thank you!<br><br><hr><p style="font-size: small; color: #777;">{{cancellation_policy_notice}}<br>To cancel this appointment, please use the following link: <a href="{{cancellation_link}}">Cancel Appointment</a></p>', 'schedula-smart-appointment-booking'),
            'pendingRecurringGroupBookingToClientHeaderBgColor' => '#3498db',
            'pendingRecurringGroupBookingToClientHeaderTextColor' => '#ffffff',

            // Pending Recurring Group Booking to Staff
            'enablePendingRecurringGroupBookingToStaff' => true,
            'pendingRecurringGroupBookingToStaffSubject' => __('New Recurring Group Appointment Series Request: {{service_name}} with {{client_name}}', 'schedula-smart-appointment-booking'),
            'pendingRecurringGroupBookingToStaffBody' => __('Hi {{staff_name}},<br><br>A new recurring group appointment series ({{number_of_persons}} persons) has been requested by {{client_name}} for {{service_name}}. The first appointment is on {{appointment_date}} at {{appointment_time}}. The appointment status is: {{appointment_status}}.<br><br>{{recurrence_details}}<br><br>{{business_name}}', 'schedula-smart-appointment-booking'),
            'pendingRecurringGroupBookingToStaffHeaderBgColor' => '#3498db',
            'pendingRecurringGroupBookingToStaffHeaderTextColor' => '#ffffff',

            // Confirmed Recurring Group Booking to Client
            'enableConfirmedRecurringGroupBookingToClient' => true,
            'confirmedRecurringGroupBookingToClientSubject' => __('Your Recurring Group Appointment Series is Confirmed!', 'schedula-smart-appointment-booking'),
            'confirmedRecurringGroupBookingToClientBody' => __('Dear {{client_name}},<br><br>Your recurring group appointment series ({{number_of_persons}} persons) for {{service_name}} with {{staff_name}} starting on {{appointment_date}} at {{appointment_time}} is confirmed.<br><br>{{recurrence_details}}<br><br>We look forward to seeing you!<br><br><hr><p style="font-size: small; color: #777;">{{cancellation_policy_notice}}<br>To cancel this appointment, please use the following link: <a href="{{cancellation_link}}">Cancel Appointment</a></p>', 'schedula-smart-appointment-booking'),
            'confirmedRecurringGroupBookingToClientHeaderBgColor' => '#3498db',
            'confirmedRecurringGroupBookingToClientHeaderTextColor' => '#ffffff',

            // Confirmed Recurring Group Booking to Staff
            'enableConfirmedRecurringGroupBookingToStaff' => true,
            'confirmedRecurringGroupBookingToStaffSubject' => __('Recurring Group Appointment Series Confirmed: {{service_name}} with {{client_name}}', 'schedula-smart-appointment-booking'),
            'confirmedRecurringGroupBookingToStaffBody' => __('Hi {{staff_name}},<br><br>The recurring group appointment series ({{number_of_persons}} persons) with {{client_name}} for {{service_name}} starting on {{appointment_date}} at {{appointment_time}} has been confirmed.<br><br>{{recurrence_details}}', 'schedula-smart-appointment-booking'),
            'confirmedRecurringGroupBookingToStaffHeaderBgColor' => '#3498db',
            'confirmedRecurringGroupBookingToStaffHeaderTextColor' => '#ffffff',

            // Confirmed Booking to Client
            'enableConfirmedToClient' => true,
            'confirmedToClientSubject' => __('Your Appointment is Confirmed!', 'schedula-smart-appointment-booking'),
            'confirmedToClientBody' => __('Dear {{client_name}},<br><br>Your appointment for {{service_name}} with {{staff_name}} on {{appointment_date}} at {{appointment_time}} is now confirmed. We look forward to seeing you!<br><br><hr><p style="font-size: small; color: #777;">{{cancellation_policy_notice}}<br>To cancel this appointment, please use the following link: <a href="{{cancellation_link}}">Cancel Appointment</a></p>', 'schedula-smart-appointment-booking'),
            'confirmedToClientHeaderBgColor' => '#3498db',
            'confirmedToClientHeaderTextColor' => '#ffffff',

            // Confirmed Booking to Staff
            'enableConfirmedToStaff' => true,
            'confirmedToStaffSubject' => __('Appointment Confirmed: {{service_name}} with {{client_name}}', 'schedula-smart-appointment-booking'),
            'confirmedToStaffBody' => __('Hi {{staff_name}},<br><br>The appointment with {{client_name}} for {{service_name}} on {{appointment_date}} at {{appointment_time}} has been confirmed.', 'schedula-smart-appointment-booking'),
            'confirmedToStaffHeaderBgColor' => '#3498db',
            'confirmedToStaffHeaderTextColor' => '#ffffff',

            // Cancelled Booking to Client
            'enableCancelledToClient' => true,
            'cancelledToClientSubject' => __('Your appointment has been cancelled', 'schedula-smart-appointment-booking'),
            'cancelledToClientBody' => __('Dear {{client_name}},<br><br>Your appointment for {{service_name}} on {{appointment_date}} at {{appointment_time}} has been cancelled. We hope to see you again soon!', 'schedula-smart-appointment-booking'),
            'cancelledToClientHeaderBgColor' => '#3498db',
            'cancelledToClientHeaderTextColor' => '#ffffff',

            // Cancelled Booking to Staff
            'enableCancelledToStaff' => true,
            'cancelledToStaffSubject' => __('Appointment Cancelled: {{service_name}} with {{client_name}}', 'schedula-smart-appointment-booking'),
            'cancelledToStaffBody' => __('Hi {{staff_name}},<br><br>The appointment with {{client_name}} for {{service_name}} on {{appointment_date}} at {{appointment_time}} has been cancelled.', 'schedula-smart-appointment-booking'),
            'cancelledToStaffHeaderBgColor' => '#3498db',
            'cancelledToStaffHeaderTextColor' => '#ffffff',

            // Rejected Appointment (Customer)
            'enableRejectedToClient' => true,
            'rejectedToClientSubject' => __('Your appointment request was rejected', 'schedula-smart-appointment-booking'),
            'rejectedToClientBody' => __('Dear {{ client_name }},<br><br>We regret to inform you that your appointment for {{ service_name }} on {{ appointment_date }} could not be confirmed.<br><br>{{ business_name }}', 'schedula-smart-appointment-booking'),
            'rejectedToClientHeaderBgColor' => '#3498db',
            'rejectedToClientHeaderTextColor' => '#ffffff',

            // Rejected Appointment (Staff)
            'enableRejectedToStaff' => true,
            'rejectedToStaffSubject' => __('An appointment has been rejected', 'schedula-smart-appointment-booking'),
            'rejectedToStaffBody' => __('Hi {{ staff_name }},<br><br>The appointment with {{ client_name }} for {{ service_name }} on {{ appointment_date }} at {{ appointment_time }} has been rejected.<br><br>{{ business_name }}', 'schedula-smart-appointment-booking'),
            'rejectedToStaffHeaderBgColor' => '#3498db',
            'rejectedToStaffHeaderTextColor' => '#ffffff',

            // New Booking to Admin
            'enableNewBookingToAdmin' => true,
            'newBookingToAdminSubject' => __('New Appointment Request: {{service_name}} by {{client_name}}', 'schedula-smart-appointment-booking'),
            'newBookingToAdminBody' => __('Hello Admin,<br><br>A new appointment has been requested by {{client_name}} for {{service_name}} with {{staff_name}} on {{appointment_date}} at {{appointment_time}}. Status: {{appointment_status}}.<br><br>{{business_name}}', 'schedula-smart-appointment-booking'),
            'newBookingToAdminHeaderBgColor' => '#3498db',
            'newBookingToAdminHeaderTextColor' => '#ffffff',

            // Pending Group Booking to Admin
            'enablePendingGroupBookingToAdmin' => true,
            'pendingGroupBookingToAdminSubject' => __('New Group Appointment Request: {{service_name}} by {{client_name}}', 'schedula-smart-appointment-booking'),
            'pendingGroupBookingToAdminBody' => __('Hello Admin,<br><br>A new group appointment ({{number_of_persons}} persons) has been requested by {{client_name}} for {{service_name}} with {{staff_name}} on {{appointment_date}} at {{appointment_time}}. Status: {{appointment_status}}.<br><br>{{business_name}}', 'schedula-smart-appointment-booking'),
            'pendingGroupBookingToAdminHeaderBgColor' => '#3498db',
            'pendingGroupBookingToAdminHeaderTextColor' => '#ffffff',

            // Confirmed Group Booking to Admin
            'enableConfirmedGroupBookingToAdmin' => true,
            'confirmedGroupBookingToAdminSubject' => __('Group Appointment Confirmed: {{service_name}} by {{client_name}}', 'schedula-smart-appointment-booking'),
            'confirmedGroupBookingToAdminBody' => __('Hello Admin,<br><br>A group appointment ({{number_of_persons}} persons) has been confirmed for {{client_name}} for {{service_name}} with {{staff_name}} on {{appointment_date}} at {{appointment_time}}.<br><br>{{business_name}}', 'schedula-smart-appointment-booking'),
            'confirmedGroupBookingToAdminHeaderBgColor' => '#3498db',
            'confirmedGroupBookingToAdminHeaderTextColor' => '#ffffff',

            // Pending Recurring Booking to Admin
            'enablePendingRecurringBookingToAdmin' => true,
            'pendingRecurringBookingToAdminSubject' => __('New Recurring Series Request: {{service_name}} by {{client_name}}', 'schedula-smart-appointment-booking'),
            'pendingRecurringBookingToAdminBody' => __('Hello Admin,<br><br>A new recurring appointment series has been requested by {{client_name}} for {{service_name}} with {{staff_name}}. The first appointment is on {{appointment_date}} at {{appointment_time}}. Status: {{appointment_status}}.<br><br>{{recurrence_details}}<br><br>{{business_name}}', 'schedula-smart-appointment-booking'),
            'pendingRecurringBookingToAdminHeaderBgColor' => '#3498db',
            'pendingRecurringBookingToAdminHeaderTextColor' => '#ffffff',

            // Confirmed Recurring Booking to Admin
            'enableConfirmedRecurringBookingToAdmin' => true,
            'confirmedRecurringBookingToAdminSubject' => __('Recurring Series Confirmed: {{service_name}} by {{client_name}}', 'schedula-smart-appointment-booking'),
            'confirmedRecurringBookingToAdminBody' => __('Hello Admin,<br><br>A recurring appointment series has been confirmed for {{client_name}} for {{service_name}} with {{staff_name}}. The first appointment is on {{appointment_date}} at {{appointment_time}}.<br><br>{{recurrence_details}}<br><br>{{business_name}}', 'schedula-smart-appointment-booking'),
            'confirmedRecurringBookingToAdminHeaderBgColor' => '#3498db',
            'confirmedRecurringBookingToAdminHeaderTextColor' => '#ffffff',

            // Pending Recurring Group Booking to Admin
            'enablePendingRecurringGroupBookingToAdmin' => true,
            'pendingRecurringGroupBookingToAdminSubject' => __('New Recurring Group Series Request: {{service_name}} by {{client_name}}', 'schedula-smart-appointment-booking'),
            'pendingRecurringGroupBookingToAdminBody' => __('Hello Admin,<br><br>A new recurring group appointment series ({{number_of_persons}} persons) has been requested by {{client_name}} for {{service_name}} with {{staff_name}}. The first appointment is on {{appointment_date}} at {{appointment_time}}. Status: {{appointment_status}}.<br><br>{{recurrence_details}}<br><br>{{business_name}}', 'schedula-smart-appointment-booking'),
            'pendingRecurringGroupBookingToAdminHeaderBgColor' => '#3498db',
            'pendingRecurringGroupBookingToAdminHeaderTextColor' => '#ffffff',

            // Confirmed Recurring Group Booking to Admin
            'enableConfirmedRecurringGroupBookingToAdmin' => true,
            'confirmedRecurringGroupBookingToAdminSubject' => __('Recurring Group Series Confirmed: {{service_name}} by {{client_name}}', 'schedula-smart-appointment-booking'),
            'confirmedRecurringGroupBookingToAdminBody' => __('Hello Admin,<br><br>A recurring group appointment series ({{number_of_persons}} persons) has been confirmed for {{client_name}} for {{service_name}} with {{staff_name}}. The first appointment is on {{appointment_date}} at {{appointment_time}}.<br><br>{{recurrence_details}}<br><br>{{business_name}}', 'schedula-smart-appointment-booking'),
            'confirmedRecurringGroupBookingToAdminHeaderBgColor' => '#3498db',
            'confirmedRecurringGroupBookingToAdminHeaderTextColor' => '#ffffff',

            // Confirmed Booking to Admin
            'enableConfirmedToAdmin' => true,
            'confirmedToAdminSubject' => __('Appointment Confirmed: {{service_name}} by {{client_name}}', 'schedula-smart-appointment-booking'),
            'confirmedToAdminBody' => __('Hello Admin,<br><br>An appointment has been confirmed for {{client_name}} for {{service_name}} with {{staff_name}} on {{appointment_date}} at {{appointment_time}}.<br><br>{{business_name}}', 'schedula-smart-appointment-booking'),
            'confirmedToAdminHeaderBgColor' => '#3498db',
            'confirmedToAdminHeaderTextColor' => '#ffffff',

            // Cancelled Booking to Admin
            'enableCancelledToAdmin' => true,
            'cancelledToAdminSubject' => __('Appointment Cancelled: {{service_name}} by {{client_name}}', 'schedula-smart-appointment-booking'),
            'cancelledToAdminBody' => __('Hello Admin,<br><br>An appointment for {{client_name}} for {{service_name}} on {{appointment_date}} at {{appointment_time}} has been cancelled.<br><br>{{business_name}}', 'schedula-smart-appointment-booking'),
            'cancelledToAdminHeaderBgColor' => '#3498db',
            'cancelledToAdminHeaderTextColor' => '#ffffff',

            // Rejected Booking to Admin
            'enableRejectedToAdmin' => true,
            'rejectedToAdminSubject' => __('Appointment Rejected: {{service_name}} by {{client_name}}', 'schedula-smart-appointment-booking'),
            'rejectedToAdminBody' => __('Hello Admin,<br><br>An appointment for {{client_name}} for {{service_name}} on {{appointment_date}} at {{appointment_time}} has been rejected.<br><br>{{business_name}}', 'schedula-smart-appointment-booking'),
            'rejectedToAdminHeaderBgColor' => '#3498db',
            'rejectedToAdminHeaderTextColor' => '#ffffff',
        ];
    }

    public function get_item_schema()
    {
        $props = [
            // General Settings
            'senderName' => ['type' => 'string', 'description' => __('The name emails are sent from.', 'schedula-smart-appointment-booking'), 'context' => ['view', 'edit']],
            'senderEmail' => ['type' => 'string', 'format' => 'email', 'description' => __('The email address emails are sent from.', 'schedula-smart-appointment-booking'), 'context' => ['view', 'edit']],
            'emailContentType' => ['type' => 'string', 'description' => __('Email content type.', 'schedula-smart-appointment-booking'), 'enum' => ['html', 'text'], 'context' => ['view', 'edit']],
            'adminRecipientEmail' => ['type' => 'string', 'format' => 'email', 'description' => __('The email address to send admin notifications to.', 'schedula-smart-appointment-booking'), 'context' => ['view', 'edit']],

            // SMTP Settings
            'smtpEnabled' => ['type' => 'boolean', 'context' => ['view', 'edit']],
            'smtpHost' => ['type' => 'string', 'context' => ['view', 'edit']],
            'smtpPort' => ['type' => 'integer', 'context' => ['view', 'edit']],
            'smtpEncryption' => ['type' => 'string', 'enum' => ['none', 'ssl', 'tls'], 'context' => ['view', 'edit']],
            'smtpAuth' => ['type' => 'boolean', 'context' => ['view', 'edit']],
            'smtpUsername' => ['type' => 'string', 'context' => ['view', 'edit']],
            'smtpPassword' => ['type' => 'string', 'context' => ['edit']], // Only for writing
            'smtpPasswordSet' => ['type' => 'boolean', 'readonly' => true, 'context' => ['view']], // Only for reading

            // New Booking to Client
            'enableNewBookingToClient' => ['type' => 'boolean', 'context' => ['view', 'edit']],
            'newBookingToClientSubject' => ['type' => 'string', 'context' => ['view', 'edit']],
            'newBookingToClientBody' => ['type' => 'string', 'context' => ['view', 'edit', 'template_body']],
            'newBookingToClientHeaderBgColor' => ['type' => 'string', 'context' => ['view', 'edit']],
            'newBookingToClientHeaderTextColor' => ['type' => 'string', 'context' => ['view', 'edit']],

            // New Booking to Staff
            'enableNewBookingToStaff' => ['type' => 'boolean', 'context' => ['view', 'edit']],
            'newBookingToStaffSubject' => ['type' => 'string', 'context' => ['view', 'edit']],
            'newBookingToStaffBody' => ['type' => 'string', 'context' => ['view', 'edit', 'template_body']],
            'newBookingToStaffHeaderBgColor' => ['type' => 'string', 'context' => ['view', 'edit']],
            'newBookingToStaffHeaderTextColor' => ['type' => 'string', 'context' => ['view', 'edit']],

            // Pending Group Booking
            'enablePendingGroupBookingToClient' => ['type' => 'boolean', 'context' => ['view', 'edit']],
            'pendingGroupBookingToClientSubject' => ['type' => 'string', 'context' => ['view', 'edit']],
            'pendingGroupBookingToClientBody' => ['type' => 'string', 'context' => ['view', 'edit', 'template_body']],
            'pendingGroupBookingToClientHeaderBgColor' => ['type' => 'string', 'context' => ['view', 'edit']],
            'pendingGroupBookingToClientHeaderTextColor' => ['type' => 'string', 'context' => ['view', 'edit']],
            'enablePendingGroupBookingToStaff' => ['type' => 'boolean', 'context' => ['view', 'edit']],
            'pendingGroupBookingToStaffSubject' => ['type' => 'string', 'context' => ['view', 'edit']],
            'pendingGroupBookingToStaffBody' => ['type' => 'string', 'context' => ['view', 'edit', 'template_body']],
            'pendingGroupBookingToStaffHeaderBgColor' => ['type' => 'string', 'context' => ['view', 'edit']],
            'pendingGroupBookingToStaffHeaderTextColor' => ['type' => 'string', 'context' => ['view', 'edit']],
            'enablePendingGroupBookingToAdmin' => ['type' => 'boolean', 'context' => ['view', 'edit']],
            'pendingGroupBookingToAdminSubject' => ['type' => 'string', 'context' => ['view', 'edit']],
            'pendingGroupBookingToAdminBody' => ['type' => 'string', 'context' => ['view', 'edit', 'template_body']],
            'pendingGroupBookingToAdminHeaderBgColor' => ['type' => 'string', 'context' => ['view', 'edit']],
            'pendingGroupBookingToAdminHeaderTextColor' => ['type' => 'string', 'context' => ['view', 'edit']],

            // Confirmed Group Booking
            'enableConfirmedGroupBookingToClient' => ['type' => 'boolean', 'context' => ['view', 'edit']],
            'confirmedGroupBookingToClientSubject' => ['type' => 'string', 'context' => ['view', 'edit']],
            'confirmedGroupBookingToClientBody' => ['type' => 'string', 'context' => ['view', 'edit', 'template_body']],
            'confirmedGroupBookingToClientHeaderBgColor' => ['type' => 'string', 'context' => ['view', 'edit']],
            'confirmedGroupBookingToClientHeaderTextColor' => ['type' => 'string', 'context' => ['view', 'edit']],
            'enableConfirmedGroupBookingToStaff' => ['type' => 'boolean', 'context' => ['view', 'edit']],
            'confirmedGroupBookingToStaffSubject' => ['type' => 'string', 'context' => ['view', 'edit']],
            'confirmedGroupBookingToStaffBody' => ['type' => 'string', 'context' => ['view', 'edit', 'template_body']],
            'confirmedGroupBookingToStaffHeaderBgColor' => ['type' => 'string', 'context' => ['view', 'edit']],
            'confirmedGroupBookingToStaffHeaderTextColor' => ['type' => 'string', 'context' => ['view', 'edit']],
            'enableConfirmedGroupBookingToAdmin' => ['type' => 'boolean', 'context' => ['view', 'edit']],
            'confirmedGroupBookingToAdminSubject' => ['type' => 'string', 'context' => ['view', 'edit']],
            'confirmedGroupBookingToAdminBody' => ['type' => 'string', 'context' => ['view', 'edit', 'template_body']],
            'confirmedGroupBookingToAdminHeaderBgColor' => ['type' => 'string', 'context' => ['view', 'edit']],
            'confirmedGroupBookingToAdminHeaderTextColor' => ['type' => 'string', 'context' => ['view', 'edit']],

            // Pending Recurring Booking
            'enablePendingRecurringBookingToClient' => ['type' => 'boolean', 'context' => ['view', 'edit']],
            'pendingRecurringBookingToClientSubject' => ['type' => 'string', 'context' => ['view', 'edit']],
            'pendingRecurringBookingToClientBody' => ['type' => 'string', 'context' => ['view', 'edit', 'template_body']],
            'pendingRecurringBookingToClientHeaderBgColor' => ['type' => 'string', 'context' => ['view', 'edit']],
            'pendingRecurringBookingToClientHeaderTextColor' => ['type' => 'string', 'context' => ['view', 'edit']],
            'enablePendingRecurringBookingToStaff' => ['type' => 'boolean', 'context' => ['view', 'edit']],
            'pendingRecurringBookingToStaffSubject' => ['type' => 'string', 'context' => ['view', 'edit']],
            'pendingRecurringBookingToStaffBody' => ['type' => 'string', 'context' => ['view', 'edit', 'template_body']],
            'pendingRecurringBookingToStaffHeaderBgColor' => ['type' => 'string', 'context' => ['view', 'edit']],
            'pendingRecurringBookingToStaffHeaderTextColor' => ['type' => 'string', 'context' => ['view', 'edit']],
            'enablePendingRecurringBookingToAdmin' => ['type' => 'boolean', 'context' => ['view', 'edit']],
            'pendingRecurringBookingToAdminSubject' => ['type' => 'string', 'context' => ['view', 'edit']],
            'pendingRecurringBookingToAdminBody' => ['type' => 'string', 'context' => ['view', 'edit', 'template_body']],
            'pendingRecurringBookingToAdminHeaderBgColor' => ['type' => 'string', 'context' => ['view', 'edit']],
            'pendingRecurringBookingToAdminHeaderTextColor' => ['type' => 'string', 'context' => ['view', 'edit']],

            // Confirmed Recurring Booking
            'enableConfirmedRecurringBookingToClient' => ['type' => 'boolean', 'context' => ['view', 'edit']],
            'confirmedRecurringBookingToClientSubject' => ['type' => 'string', 'context' => ['view', 'edit']],
            'confirmedRecurringBookingToClientBody' => ['type' => 'string', 'context' => ['view', 'edit', 'template_body']],
            'confirmedRecurringBookingToClientHeaderBgColor' => ['type' => 'string', 'context' => ['view', 'edit']],
            'confirmedRecurringBookingToClientHeaderTextColor' => ['type' => 'string', 'context' => ['view', 'edit']],
            'enableConfirmedRecurringBookingToStaff' => ['type' => 'boolean', 'context' => ['view', 'edit']],
            'confirmedRecurringBookingToStaffSubject' => ['type' => 'string', 'context' => ['view', 'edit']],
            'confirmedRecurringBookingToStaffBody' => ['type' => 'string', 'context' => ['view', 'edit', 'template_body']],
            'confirmedRecurringBookingToStaffHeaderBgColor' => ['type' => 'string', 'context' => ['view', 'edit']],
            'confirmedRecurringBookingToStaffHeaderTextColor' => ['type' => 'string', 'context' => ['view', 'edit']],
            'enableConfirmedRecurringBookingToAdmin' => ['type' => 'boolean', 'context' => ['view', 'edit']],
            'confirmedRecurringBookingToAdminSubject' => ['type' => 'string', 'context' => ['view', 'edit']],
            'confirmedRecurringBookingToAdminBody' => ['type' => 'string', 'context' => ['view', 'edit', 'template_body']],
            'confirmedRecurringBookingToAdminHeaderBgColor' => ['type' => 'string', 'context' => ['view', 'edit']],
            'confirmedRecurringBookingToAdminHeaderTextColor' => ['type' => 'string', 'context' => ['view', 'edit']],

            // Pending Recurring Group Booking
            'enablePendingRecurringGroupBookingToClient' => ['type' => 'boolean', 'context' => ['view', 'edit']],
            'pendingRecurringGroupBookingToClientSubject' => ['type' => 'string', 'context' => ['view', 'edit']],
            'pendingRecurringGroupBookingToClientBody' => ['type' => 'string', 'context' => ['view', 'edit', 'template_body']],
            'pendingRecurringGroupBookingToClientHeaderBgColor' => ['type' => 'string', 'context' => ['view', 'edit']],
            'pendingRecurringGroupBookingToClientHeaderTextColor' => ['type' => 'string', 'context' => ['view', 'edit']],
            'enablePendingRecurringGroupBookingToStaff' => ['type' => 'boolean', 'context' => ['view', 'edit']],
            'pendingRecurringGroupBookingToStaffSubject' => ['type' => 'string', 'context' => ['view', 'edit']],
            'pendingRecurringGroupBookingToStaffBody' => ['type' => 'string', 'context' => ['view', 'edit', 'template_body']],
            'pendingRecurringGroupBookingToStaffHeaderBgColor' => ['type' => 'string', 'context' => ['view', 'edit']],
            'pendingRecurringGroupBookingToStaffHeaderTextColor' => ['type' => 'string', 'context' => ['view', 'edit']],
            'enablePendingRecurringGroupBookingToAdmin' => ['type' => 'boolean', 'context' => ['view', 'edit']],
            'pendingRecurringGroupBookingToAdminSubject' => ['type' => 'string', 'context' => ['view', 'edit']],
            'pendingRecurringGroupBookingToAdminBody' => ['type' => 'string', 'context' => ['view', 'edit', 'template_body']],
            'pendingRecurringGroupBookingToAdminHeaderBgColor' => ['type' => 'string', 'context' => ['view', 'edit']],
            'pendingRecurringGroupBookingToAdminHeaderTextColor' => ['type' => 'string', 'context' => ['view', 'edit']],

            // Confirmed Recurring Group Booking
            'enableConfirmedRecurringGroupBookingToClient' => ['type' => 'boolean', 'context' => ['view', 'edit']],
            'confirmedRecurringGroupBookingToClientSubject' => ['type' => 'string', 'context' => ['view', 'edit']],
            'confirmedRecurringGroupBookingToClientBody' => ['type' => 'string', 'context' => ['view', 'edit', 'template_body']],
            'confirmedRecurringGroupBookingToClientHeaderBgColor' => ['type' => 'string', 'context' => ['view', 'edit']],
            'confirmedRecurringGroupBookingToClientHeaderTextColor' => ['type' => 'string', 'context' => ['view', 'edit']],
            'enableConfirmedRecurringGroupBookingToStaff' => ['type' => 'boolean', 'context' => ['view', 'edit']],
            'confirmedRecurringGroupBookingToStaffSubject' => ['type' => 'string', 'context' => ['view', 'edit']],
            'confirmedRecurringGroupBookingToStaffBody' => ['type' => 'string', 'context' => ['view', 'edit', 'template_body']],
            'confirmedRecurringGroupBookingToStaffHeaderBgColor' => ['type' => 'string', 'context' => ['view', 'edit']],
            'confirmedRecurringGroupBookingToStaffHeaderTextColor' => ['type' => 'string', 'context' => ['view', 'edit']],
            'enableConfirmedRecurringGroupBookingToAdmin' => ['type' => 'boolean', 'context' => ['view', 'edit']],
            'confirmedRecurringGroupBookingToAdminSubject' => ['type' => 'string', 'context' => ['view', 'edit']],
            'confirmedRecurringGroupBookingToAdminBody' => ['type' => 'string', 'context' => ['view', 'edit', 'template_body']],
            'confirmedRecurringGroupBookingToAdminHeaderBgColor' => ['type' => 'string', 'context' => ['view', 'edit']],
            'confirmedRecurringGroupBookingToAdminHeaderTextColor' => ['type' => 'string', 'context' => ['view', 'edit']],

            // Confirmed Booking to Client
            'enableConfirmedToClient' => ['type' => 'boolean', 'context' => ['view', 'edit']],
            'confirmedToClientSubject' => ['type' => 'string', 'context' => ['view', 'edit']],
            'confirmedToClientBody' => ['type' => 'string', 'context' => ['view', 'edit', 'template_body']],
            'confirmedToClientHeaderBgColor' => ['type' => 'string', 'context' => ['view', 'edit']],
            'confirmedToClientHeaderTextColor' => ['type' => 'string', 'context' => ['view', 'edit']],

            // Confirmed Booking to Staff
            'enableConfirmedToStaff' => ['type' => 'boolean', 'context' => ['view', 'edit']],
            'confirmedToStaffSubject' => ['type' => 'string', 'context' => ['view', 'edit']],
            'confirmedToStaffBody' => ['type' => 'string', 'context' => ['view', 'edit', 'template_body']],
            'confirmedToStaffHeaderBgColor' => ['type' => 'string', 'context' => ['view', 'edit']],
            'confirmedToStaffHeaderTextColor' => ['type' => 'string', 'context' => ['view', 'edit']],

            // Cancelled Booking to Client
            'enableCancelledToClient' => ['type' => 'boolean', 'context' => ['view', 'edit']],
            'cancelledToClientSubject' => ['type' => 'string', 'context' => ['view', 'edit']],
            'cancelledToClientBody' => ['type' => 'string', 'context' => ['view', 'edit', 'template_body']],
            'cancelledToClientHeaderBgColor' => ['type' => 'string', 'context' => ['view', 'edit']],
            'cancelledToClientHeaderTextColor' => ['type' => 'string', 'context' => ['view', 'edit']],

            // Cancelled Booking to Staff
            'enableCancelledToStaff' => ['type' => 'boolean', 'context' => ['view', 'edit']],
            'cancelledToStaffSubject' => ['type' => 'string', 'context' => ['view', 'edit']],
            'cancelledToStaffBody' => ['type' => 'string', 'context' => ['view', 'edit', 'template_body']],
            'cancelledToStaffHeaderBgColor' => ['type' => 'string', 'context' => ['view', 'edit']],
            'cancelledToStaffHeaderTextColor' => ['type' => 'string', 'context' => ['view', 'edit']],

            // Rejected Appointment (Customer)
            'enableRejectedToClient' => ['type' => 'boolean', 'context' => ['view', 'edit']],
            'rejectedToClientSubject' => ['type' => 'string', 'context' => ['view', 'edit']],
            'rejectedToClientBody' => ['type' => 'string', 'context' => ['view', 'edit', 'template_body']],
            'rejectedToClientHeaderBgColor' => ['type' => 'string', 'context' => ['view', 'edit']],
            'rejectedToClientHeaderTextColor' => ['type' => 'string', 'context' => ['view', 'edit']],

            // Rejected Appointment (Staff)
            'enableRejectedToStaff' => ['type' => 'boolean', 'context' => ['view', 'edit']],
            'rejectedToStaffSubject' => ['type' => 'string', 'context' => ['view', 'edit']],
            'rejectedToStaffBody' => ['type' => 'string', 'context' => ['view', 'edit', 'template_body']],
            'rejectedToStaffHeaderBgColor' => ['type' => 'string', 'context' => ['view', 'edit']],
            'rejectedToStaffHeaderTextColor' => ['type' => 'string', 'context' => ['view', 'edit']],

            // New Booking to Admin
            'enableNewBookingToAdmin' => ['type' => 'boolean', 'context' => ['view', 'edit']],
            'newBookingToAdminSubject' => ['type' => 'string', 'context' => ['view', 'edit']],
            'newBookingToAdminBody' => ['type' => 'string', 'context' => ['view', 'edit', 'template_body']],
            'newBookingToAdminHeaderBgColor' => ['type' => 'string', 'context' => ['view', 'edit']],
            'newBookingToAdminHeaderTextColor' => ['type' => 'string', 'context' => ['view', 'edit']],

            // Confirmed Booking to Admin
            'enableConfirmedToAdmin' => ['type' => 'boolean', 'context' => ['view', 'edit']],
            'confirmedToAdminSubject' => ['type' => 'string', 'context' => ['view', 'edit']],
            'confirmedToAdminBody' => ['type' => 'string', 'context' => ['view', 'edit', 'template_body']],
            'confirmedToAdminHeaderBgColor' => ['type' => 'string', 'context' => ['view', 'edit']],
            'confirmedToAdminHeaderTextColor' => ['type' => 'string', 'context' => ['view', 'edit']],

            // Cancelled Booking to Admin
            'enableCancelledToAdmin' => ['type' => 'boolean', 'context' => ['view', 'edit']],
            'cancelledToAdminSubject' => ['type' => 'string', 'context' => ['view', 'edit']],
            'cancelledToAdminBody' => ['type' => 'string', 'context' => ['view', 'edit', 'template_body']],
            'cancelledToAdminHeaderBgColor' => ['type' => 'string', 'context' => ['view', 'edit']],
            'cancelledToAdminHeaderTextColor' => ['type' => 'string', 'context' => ['view', 'edit']],

            // Rejected Booking to Admin
            'enableRejectedToAdmin' => ['type' => 'boolean', 'context' => ['view', 'edit']],
            'rejectedToAdminSubject' => ['type' => 'string', 'context' => ['view', 'edit']],
            'rejectedToAdminBody' => ['type' => 'string', 'context' => ['view', 'edit', 'template_body']],
            'rejectedToAdminHeaderBgColor' => ['type' => 'string', 'context' => ['view', 'edit']],
            'rejectedToAdminHeaderTextColor' => ['type' => 'string', 'context' => ['view', 'edit']],
        ];

        // Set defaults from get_default_settings
        $defaults = $this->get_default_settings();
        foreach ($props as $key => &$value) {
            if (isset($defaults[$key])) {
                $value['default'] = $defaults[$key];
            }
        }

        return [
            '$schema' => 'http://json-schema.org/draft-04/schema#',
            'title' => 'notification_settings',
            'type' => 'object',
            'properties' => $props,
        ];
    }

    protected function get_settings_args()
    {
        $schema = $this->get_item_schema();
        $args = [];

        foreach ($schema['properties'] as $field => $properties) {
            if (isset($properties['readonly']) && $properties['readonly']) {
                continue;
            }

            $args[$field] = [
                'description' => $properties['description'] ?? '',
                'type' => $properties['type'] ?? 'string',
                'sanitize_callback' => function ($value) use ($properties) {
                    return $this->sanitize_setting_value($value, $properties);
                },
                'validate_callback' => function ($value) use ($properties, $field) {
                    return $this->validate_setting_value($value, $properties, $field);
                },
                'required' => $properties['required'] ?? false,
            ];
            if (isset($properties['enum'])) {
                $args[$field]['enum'] = $properties['enum'];
            }
        }

        return $args;
    }
}
