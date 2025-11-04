<?php
/**
 * REST API Endpoints for Schedula Newsletter.
 * Handles newsletter subscription.
 *
 * @package SCHESAB\Api
 * 
 */

namespace SCHESAB\Api;

use WP_REST_Request;
use WP_REST_Response;
use SCHESAB\Database\SCHESAB_Database; // Ensure this class is available for DB access

if (!defined('ABSPATH')) {
    exit;
}

class SCHESAB_Newsletter
{

    private $namespace = 'schesab/v1';
    private $db;

    public function __construct()
    {
        $this->db = \SCHESAB\Database\SCHESAB_Database::get_instance();
    }

    public function register_routes()
    {
        register_rest_route($this->namespace, '/subscribe-newsletter', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_newsletter_subscription'),
            'permission_callback' => '__return_true', // Allows anyone to subscribe (public endpoint)
            'args' => array(
                'email' => array(
                    'required' => true,
                    'validate_callback' => function ($param, $request, $key) {
                        return is_email($param); // Validates email format
                    },
                    'sanitize_callback' => 'sanitize_email', // Sanitizes the email input
                    'description' => __('Email address for newsletter subscription.', 'schedula-smart-appointment-booking'),
                    'type' => 'string',
                ),
            ),
            'schema' => null, // You can define a schema for better API documentation
        ));
    }

    /**
     * Handles the newsletter subscription logic.
     */
    public function handle_newsletter_subscription(WP_REST_Request $request)
    {
        global $wpdb;
        $email = $request->get_param('email');
        $table_name = $this->db->get_table_name('newsletter_subscribers');

        if (!is_email($email)) {
            return new WP_REST_Response(['success' => false, 'message' => __('Please provide a valid email address.', 'schedula-smart-appointment-booking')], 400);
        }

        $existing_email = $wpdb->get_var($wpdb->prepare("SELECT email FROM {$table_name} WHERE email = %s", $email));

        if ($existing_email) {
            return new WP_REST_Response(['success' => false, 'message' => __('This email is already subscribed.', 'schedula-smart-appointment-booking')], 409);
        }

        $inserted = $wpdb->insert(
            $table_name,
            [
                'email' => $email,
                'subscribed_at' => current_time('mysql'),
                'status' => 'active',
            ],
            ['%s', '%s', '%s']
        );

        if ($inserted === false) {
            return new WP_REST_Response(['success' => false, 'message' => __('Failed to subscribe due to a database error.', 'schedula-smart-appointment-booking')], 500);
        }

        // --- Send Confirmation Email ---
        $to = $email;
        $subject = __('Thanks for subscribing to Schedula Newsletter! 💌', 'schedula-smart-appointment-booking');
        $body = '<html>
            <head><title>' . __('Welcome to Schedula Newsletter!', 'schedula-smart-appointment-booking') . '</title></head>
            <body>
                <p>' . __('Thanks for subscribing! 💌 Now you\'ll be the first to know about our latest news, expert insights, and subscriber-only perks. Stay tuned.', 'schedula-smart-appointment-booking') . '</p>
                <p>' . __('Best regards,', 'schedula-smart-appointment-booking') . '<br>' . __('The Schedula Team', 'schedula-smart-appointment-booking') . '</p>
            </body>
            </html>';
        $headers = array('Content-Type: text/html; charset=UTF-8');

        $email_sent = wp_mail($to, $subject, $body, $headers);

        $this->log_email($to, $subject, $body, $email_sent ? 'sent' : 'failed', $email_sent ? '' : 'wp_mail failed');

        if (!$email_sent) {
        }
        // --- End Send Confirmation Email ---

        return new WP_REST_Response(['success' => true, 'message' => __('Successfully subscribed to the newsletter!', 'schedula-smart-appointment-booking')], 200);
    }

    /**
     * Log email to database.
     */
    private function log_email($to, $subject, $body, $status, $error = '')
    {
        global $wpdb;
        $table_name = $this->db->get_table_name('email_log');

        $wpdb->insert($table_name, [
            'recipient' => is_array($to) ? implode(', ', $to) : $to,
            'subject' => $subject,
            'body' => $body,
            'status' => $status,
            'error_message' => $error,
            'sent_at' => current_time('mysql'),
        ]);
    }
}