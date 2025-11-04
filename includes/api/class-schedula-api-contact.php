<?php

namespace SCHESAB\Api;

use WP_REST_Controller;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use SCHESAB\Schesab; // Import the main Schedula class to access constants

if (!defined('ABSPATH')) {
    exit;
}

class SCHESAB_Contact extends WP_REST_Controller
{
    public function __construct()
    {
        $this->namespace = 'schesab/v1';
        $this->rest_base = 'contact-form';
    }

    public function register_routes()
    {
        register_rest_route($this->namespace, '/' . $this->rest_base, [
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'submit_contact_form'],
                'permission_callback' => '__return_true', // No authentication needed for contact form
                'args' => $this->get_endpoint_args_for_item_schema(WP_REST_Server::CREATABLE),
            ],
        ]);
    }

    public function submit_contact_form(WP_REST_Request $request)
    {
        $name = sanitize_text_field($request->get_param('name'));
        $email = sanitize_email($request->get_param('email'));
        $message = sanitize_textarea_field($request->get_param('message'));

        if (empty($name) || empty($email) || empty($message)) {
            return new WP_REST_Response(['success' => false, 'message' => __('All fields are required.', 'schedula-smart-appointment-booking')], 400);
        }

        if (!is_email($email)) {
            return new WP_REST_Response(['success' => false, 'message' => __('Invalid email address.', 'schedula-smart-appointment-booking')], 400);
        }

        $to = Schesab::DEV_EMAIL; // Get developer email from the constant

        /* translators: %s: Site title */
        $subject = sprintf(__('New Contact Form Submission from %s', 'schedula-smart-appointment-booking'), get_bloginfo('name'));
        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $name . ' <' . $email . '>',
            'Reply-To: ' . $email,
        ];

        $body = '<p><strong>' . __('Name', 'schedula-smart-appointment-booking') . ':</strong> ' . $name . '</p>';
        $body .= '<p><strong>' . __('Email', 'schedula-smart-appointment-booking') . ':</strong> ' . $email . '</p>';
        $body .= '<p><strong>' . __('Message', 'schedula-smart-appointment-booking') . ':</strong></p>';
        $body .= '<p>' . nl2br($message) . '</p>';

        $mail_sent = wp_mail($to, $subject, $body, $headers);

        if ($mail_sent) {
            return new WP_REST_Response(['success' => true, 'message' => __('Message sent successfully!', 'schedula-smart-appointment-booking')], 200);
        } else {
            return new WP_REST_Response(['success' => false, 'message' => __('Failed to send message. Please try again later.', 'schedula-smart-appointment-booking')], 500);
        }
    }

    public function get_item_schema()
    {
        return [
            '$schema' => 'http://json-schema.org/draft-04/schema#',
            'title' => 'contact-form',
            'type' => 'object',
            'properties' => [
                'name' => [
                    'type' => 'string',
                    'description' => __("Sender's name", 'schedula-smart-appointment-booking'),
                    'required' => true,
                ],
                'email' => [
                    'type' => 'string',
                    'format' => 'email',
                    'description' => __("Sender's email address", 'schedula-smart-appointment-booking'),
                    'required' => true,
                ],
                'message' => [
                    'type' => 'string',
                    'description' => __('The message content', 'schedula-smart-appointment-booking'),
                    'required' => true,
                ],
            ],
        ];
    }
}
