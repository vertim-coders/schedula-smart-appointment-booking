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
                        $message = __('Payment successful! Your booking is confirmed. This tab will close automatically.', 'schedula-smart-appointment-booking');
                        wp_add_inline_script('schedula-payment-status', 'document.addEventListener("DOMContentLoaded", function() { setSchedulaPaymentStatus("success"); });');
                        return "<div id=\"schedula-payment-message\" class=\"schedula-stripe-message schedula-stripe-success\">{$message}</div>";
                }

                // Enqueue payment status script
                wp_enqueue_script('schedula-payment-status', SCHESAB_PLUGIN_URL . 'assets/js/payment-status.js', [], SCHESAB_VERSION, true);

                // Display loading message immediately and verify in background
                $loading_message = __('Verifying your payment...', 'schedula-smart-appointment-booking');
                $success_message = __('Payment successful! Your booking is confirmed. This tab will close automatically.', 'schedula-smart-appointment-booking');
                $error_message = __('Payment verification failed. Please contact support.', 'schedula-smart-appointment-booking');

                // Simple async verification script
                $verification_script = "
                (function() {
                    const messageDiv = document.getElementById('schedula-payment-message');
                    const sessionId = '" . esc_js($checkout_session_id) . "';
                    
                    fetch('" . esc_url(rest_url('schesab/v1/stripe/verify-payment')) . "', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-WP-Nonce': '" . wp_create_nonce('wp_rest') . "'
                        },
                        body: JSON.stringify({ checkout_session_id: sessionId })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            messageDiv.className = 'schedula-stripe-message schedula-stripe-success';
                            messageDiv.textContent = '" . esc_js($success_message) . "';
                            setSchedulaPaymentStatus('success');
                        } else {
                            messageDiv.className = 'schedula-stripe-message schedula-stripe-error';
                            messageDiv.textContent = data.message || '" . esc_js($error_message) . "';
                        }
                    })
                    .catch(error => {
                        messageDiv.className = 'schedula-stripe-message schedula-stripe-error';
                        messageDiv.textContent = '" . esc_js($error_message) . "';
                    });
                })();
                ";

                wp_add_inline_script('schedula-payment-status', $verification_script);
                
                return "<div id=\"schedula-payment-message\" class=\"schedula-stripe-message schedula-stripe-info\">{$loading_message}</div>";
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
