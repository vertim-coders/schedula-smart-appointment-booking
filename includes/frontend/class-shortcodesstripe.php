<?php

/**
 * Frontend Shortcode Handlers for Schedula Stripe.
 *
 * @package SCHESAB\Frontend
 */

namespace SCHESAB\Frontend;

use SCHESAB\Database\SCHESAB_Database;
use SCHESAB\Utils\SCHESAB_Encryption;

if (!defined('ABSPATH')) {
        exit;
}

class SCHESAB_ShortcodesStripe
{
        private $db;

        public function __construct()
        {
                $this->db = SCHESAB_Database::get_instance();
        }

        /**
         * Handles the [schesab_stripe_return] shortcode.
         * This shortcode is used on the page where Stripe redirects after a successful payment.
         *
         * @param array $atts Shortcode attributes.
         * @return string The content to display.
         */
        public function handle_stripe_return_shortcode($atts)
        {
                $checkout_session_id = sanitize_text_field($_GET['checkout_session_id'] ?? '');

                if (empty($checkout_session_id)) {
                        return '<div class="schedula-stripe-message schedula-stripe-error">' . __('Invalid payment confirmation: Session ID is missing.', 'schedula-smart-appointment-booking') . '</div>';
                }

                // Check if this session has already been processed to prevent duplicate appointments
                $processed_transient_key = 'schesab_processed_' . $checkout_session_id;
                if (get_transient($processed_transient_key)) {
                        return '<div class="schedula-stripe-message schedula-stripe-success">' . __('Your booking is confirmed. You should receive an email shortly.', 'schedula-smart-appointment-booking') . '</div>';
                }

                $settings = $this->get_stripe_settings();
                if (is_wp_error($settings)) {
                        return '<div class="schedula-stripe-message schedula-stripe-error">' . __('Could not retrieve payment settings.', 'schedula-smart-appointment-booking') . '</div>';
                }

                // Retrieve the session from Stripe
                $api_url = 'https://api.stripe.com/v1/checkout/sessions/' . $checkout_session_id;
                $headers = ['Authorization' => 'Bearer ' . $settings['secretKey']];
                $response = wp_remote_get($api_url, ['headers' => $headers]);

                if (is_wp_error($response)) {
                        return '<div class="schedula-stripe-message schedula-stripe-error">' . __('Error communicating with payment provider.', 'schedula-smart-appointment-booking') . '</div>';
                }

                $session = json_decode(wp_remote_retrieve_body($response), true);

                if (empty($session) || isset($session['error']) || $session['payment_status'] !== 'paid') {
                        $error_msg = $session['error']['message'] ?? 'Payment not completed.';
                        return '<div class="schedula-stripe-message schedula-stripe-error">' . __('Payment was not successful.', 'schedula-smart-appointment-booking') . '</div>';
                }

                $transient_key = $session['client_reference_id'] ?? null;
                if (!$transient_key) {
                        return '<div class="schedula-stripe-message schedula-stripe-error">' . __('Could not find appointment reference.', 'schedula-smart-appointment-booking') . '</div>';
                }

                $form_data = get_transient($transient_key);
                if (!$form_data) {
                        $payment_intent_id = $session['payment_intent'];
                        global $wpdb;
                        $payments_table = $this->db->get_table_name('payments');
                        $existing_payment = $wpdb->get_var($wpdb->prepare("SELECT appointment_id FROM {$payments_table} WHERE transaction_id = %s", $payment_intent_id));
                        if ($existing_payment) {
                                return '<div class="schedula-stripe-message schedula-stripe-success">' . __('Your booking is confirmed. You should receive an email shortly.', 'schedula-smart-appointment-booking') . '</div>';
                        }

                        return '<div class="schedula-stripe-message schedula-stripe-error">' . __('Your session has expired. Please try to book again.', 'schedula-smart-appointment-booking') . '</div>';
                }

                $appointments_api = new \SCHESAB\Api\SCHESAB_Appointments();
                $payment_info = [
                        'method' => 'stripe',
                        'transaction_id' => $session['payment_intent'],
                        'amount' => (float) ($session['amount_total'] / 100),
                        'currency' => $session['currency'],
                ];

                $appointment_id = $appointments_api->create_appointment_from_payment($form_data, $payment_info);

                if (is_wp_error($appointment_id)) {
                        return '<div class="schedula-stripe-message schedula-stripe-error">' . __('There was an error creating your booking. Please contact support.', 'schedula-smart-appointment-booking') . '</div>';
                }

                set_transient($processed_transient_key, $appointment_id, HOUR_IN_SECONDS);
                delete_transient($transient_key);

                $message = __('Payment successful! Your booking is confirmed. This tab will close automatically.', 'schedula-smart-appointment-booking');
                wp_add_inline_script('schedula-payment-status', 'document.addEventListener("DOMContentLoaded", function() { setSchedulaPaymentStatus("success"); });');
                return "<div id=\"schedula-payment-message\" class=\"schedula-stripe-message schedula-stripe-success\">{$message}</div>";
        }

        /**
         * Handles the [schesab_stripe_cancel] shortcode.
         * This shortcode is used on the page where Stripe redirects after a cancelled payment.
         *
         * @param array $atts Shortcode attributes.
         * @return string The content to display.
         */
        public function handle_stripe_cancel_shortcode($atts)
        {
                $message = __('Payment cancelled. Your booking has not been confirmed. This tab will close automatically.', 'schedula-smart-appointment-booking');
                wp_add_inline_script('schedula-payment-status', 'document.addEventListener("DOMContentLoaded", function() { setSchedulaPaymentStatus("cancelled"); });');
                return "<div id='schedula-payment-message' class='schedula-stripe-message schedula-stripe-info'>{$message}</div>";
        }

        /**
         * Retrieves and decrypts Stripe settings from the database.
         *
         * @return array|\WP_Error An array containing Stripe settings or a WP_Error if not configured.
         */
        private function get_stripe_settings()
        {
                $stripe_settings_raw = get_option('schesab_stripe_settings', []);
                $defaults_stripe = [
                        'enableStripe' => false,
                        'secretKey' => '',
                ];
                $stripe_settings = array_merge($defaults_stripe, $stripe_settings_raw);

                if (empty($stripe_settings['enableStripe'])) {
                        return new \WP_Error('stripe_disabled', __('Stripe payment gateway is disabled.', 'schedula-smart-appointment-booking'));
                }

                $secret_key = SCHESAB_Encryption::decrypt_data($stripe_settings['secretKey']);

                if (empty($secret_key)) {
                        return new \WP_Error('stripe_credentials_missing', __('Stripe Secret Key is missing in settings.', 'schedula-smart-appointment-booking'));
                }

                return [
                        'secretKey' => $secret_key,
                ];
        }
}
