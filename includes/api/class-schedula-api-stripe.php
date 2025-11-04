<?php

/**
 * REST API Endpoints for Schedula Stripe Payments.
 * Handles creating and confirming Stripe PaymentIntents, and processing webhooks.
 *
 * @package SCHESAB\Api
 */

namespace SCHESAB\Api;

use SCHESAB\Utils\SCHESAB_Encryption;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WP_Error;
use SCHESAB\Database\SCHESAB_Database; // Import the Database class


if (!defined('ABSPATH')) {
    exit;
}

class SCHESAB_Stripe
{
    private $namespace = 'schesab/v1';
    private $db;
    public function __construct()
    {
        $this->db = \SCHESAB\Database\SCHESAB_Database::get_instance(); // Get the Schedula Database instance
    }

    public function register_routes()
    {
        // Route for Stripe Webhook Listener
        register_rest_route($this->namespace, '/stripe/webhook', [
            'methods' => WP_REST_Server::CREATABLE, // POST
            'callback' => [$this, 'handle_stripe_webhook'],
            'permission_callback' => '__return_true', // Stripe sends webhooks without auth
        ]);

        // Route to test Stripe API credentials
        register_rest_route($this->namespace, '/stripe/test-credentials', [
            'methods' => WP_REST_Server::CREATABLE, // POST
            'callback' => [$this, 'test_stripe_credentials'],
            'permission_callback' => [$this, 'check_admin_permissions'], // Admin only
            'args' => [
                'publishableKey' => [
                    'sanitize_callback' => 'sanitize_text_field',
                    'required' => true,
                ],
                'secretKey' => [
                    'sanitize_callback' => 'sanitize_text_field',
                    'required' => true,
                ],
                'sandboxMode' => [
                    'validate_callback' => function ($param) {
                        return is_bool($param);
                    },
                    'required' => true,
                ],
            ],
        ]);

        // Route to create a new Stripe Checkout Session
        register_rest_route($this->namespace, '/stripe/create-checkout-session', [
            [
                'methods' => WP_REST_Server::CREATABLE, // POST
                'callback' => [$this, 'create_checkout_session'],
                'permission_callback' => [$this, 'check_frontend_permissions'],
                'args' => [
                    'form_data' => [
                        'required' => true,
                        'validate_callback' => function ($param, $request, $key) {
                            return is_array($param);
                        }
                    ]
                ]
            ]
        ]);
    }

    /**
     * Callback for creating a Stripe Checkout Session.
     *
     * @param WP_REST_Request $request The request object.
     * @return WP_REST_Response
     */
    public function create_checkout_session(WP_REST_Request $request)
    {
        global $wpdb;
        $settings = $this->get_stripe_credentials_and_mode();
        if (is_wp_error($settings) || !$settings['enableStripe']) {
            return new WP_REST_Response(['message' => __('Stripe is not enabled or configured.', 'schedula-smart-appointment-booking')], 400);
        }

        $form_data = $request->get_param('form_data');
        if (empty($form_data) || !is_array($form_data)) {
            return new WP_Error('missing_form_data', __('Appointment form data is required.', 'schedula-smart-appointment-booking'), ['status' => 400]);
        }

        $amount = (float) $form_data['price'];
        $currency_code = strtolower($settings['currencyCode']);

        $service_id = (int) $form_data['service_id'];
        $services_table = $this->db->get_table_name('services');
        $service = $wpdb->get_row($wpdb->prepare("SELECT title FROM {$services_table} WHERE id = %d", $service_id), ARRAY_A);
        $description = $service ? $service['title'] : __('Appointment Booking', 'schedula-smart-appointment-booking');

        // Apply price correction from settings
        if ($settings['priceCorrection']['type'] !== 'none' && $settings['priceCorrection']['amount'] > 0) {
            $correction_amount = (float) $settings['priceCorrection']['amount'];
            switch ($settings['priceCorrection']['type']) {
                case 'increase_percent':
                    $amount = $amount * (1 + ($correction_amount / 100));
                    break;
                case 'discount_percent':
                    $amount = $amount * (1 - ($correction_amount / 100));
                    break;
                case 'addition':
                    $amount = $amount + $correction_amount;
                    break;
                case 'deduction':
                    $amount = $amount - $correction_amount;
                    break;
            }
            $amount = max(0, $amount);
        }

        $zero_decimal_currencies = [
            'bif',
            'clp',
            'djf',
            'gnf',
            'jpy',
            'kmf',
            'krw',
            'mga',
            'pyg',
            'rwf',
            'ugx',
            'vnd',
            'vuv',
            'xaf',
            'xof',
            'xpf'
        ];

        $amount_in_cents = 0;
        if (in_array(strtolower($currency_code), $zero_decimal_currencies)) {
            $amount_in_cents = round($amount);
        } else {
            $amount_in_cents = round($amount * 100);
        }

        $transient_key = 'schesab_stripe_' . wp_generate_uuid4();
        set_transient($transient_key, $form_data, HOUR_IN_SECONDS);

        $stripe = new \Stripe\StripeClient($settings['secretKey']);

        $success_page_id = get_option('schesab_stripe_return_page_id');
        $cancel_page_id = get_option('schesab_stripe_cancel_page_id');

        if (!$success_page_id || !$cancel_page_id || !get_post($success_page_id) || !get_post($cancel_page_id)) {
            return new WP_Error(
                'stripe_pages_not_configured',
                __('The Stripe success or cancel pages are not configured correctly in WordPress. Please deactivate and reactivate the Schedula plugin to recreate them.', 'schedula-smart-appointment-booking'),
                ['status' => 500]
            );
        }

        $success_url = get_permalink($success_page_id);
        $cancel_url = get_permalink($cancel_page_id);

        try {
            $checkout_session = $stripe->checkout->sessions->create([
                'payment_method_types' => ['card'],
                'line_items' => [
                    [
                        'price_data' => [
                            'currency' => $currency_code,
                            'product_data' => [
                                'name' => $description,
                            ],
                            'unit_amount' => $amount_in_cents,
                        ],
                        'quantity' => 1,
                    ],
                ],
                'mode' => 'payment',
                'success_url' => add_query_arg('checkout_session_id', '{CHECKOUT_SESSION_ID}', $success_url),
                'cancel_url' => $cancel_url,
                'client_reference_id' => $transient_key,
                'metadata' => [
                    'transient_key' => $transient_key,
                ],
            ]);
        } catch (\Exception $e) {
            return new WP_REST_Response(['message' => __('Failed to create Stripe Checkout session.', 'schedula-smart-appointment-booking'), 'details' => $e->getMessage()], 500);
        }

        $checkout_url = $checkout_session->url ?? null;

        if (!$checkout_url) {
            return new WP_REST_Response(['message' => __('Failed to retrieve Stripe checkout URL.', 'schedula-smart-appointment-booking')], 500);
        }

        return new WP_REST_Response(['checkout_url' => $checkout_url], 200);
    }

    /**
     * Handles incoming Stripe webhooks for asynchronous event processing.
     *
     * @param WP_REST_Request $request The request object containing webhook data.
     * @return WP_REST_Response
     */
    public function handle_stripe_webhook(WP_REST_Request $request)
    {
        require_once SCHESAB_PLUGIN_DIR . 'vendor/autoload.php';

        $settings = $this->get_stripe_credentials_and_mode();
        if (is_wp_error($settings)) {
            return new WP_REST_Response(['status' => 'error', 'message' => 'Could not retrieve settings.'], 500);
        }

        $webhook_secret = SCHESAB_Encryption::decrypt_data($settings['webhookSecret']);
        if (empty($webhook_secret)) {
            return new WP_REST_Response(['status' => 'error', 'message' => 'Webhook service not configured.'], 400);
        }

        \Stripe\Stripe::setApiKey($settings['secretKey']);

        $payload = $request->get_body();
        $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
        $event = null;

        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload,
                $sig_header,
                $webhook_secret
            );
        } catch (\UnexpectedValueException $e) {
            // Invalid payload
            return new WP_REST_Response(['status' => 'error', 'message' => 'Invalid payload.'], 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            // Invalid signature
            return new WP_REST_Response(['status' => 'error', 'message' => 'Invalid signature.'], 400);
        }

        // Handle the event
        switch ($event->type) {
            case 'checkout.session.completed':
                $session = $event->data->object;
                $transient_key = $session->client_reference_id;

                if ($session->payment_status === 'paid' && $transient_key) {
                    $form_data = get_transient($transient_key);
                    if ($form_data) {
                        delete_transient($transient_key);

                        $appointments_api = new \SCHESAB\Api\SCHESAB_Appointments();

                        $payment_info = [
                            'method' => 'stripe',
                            'transaction_id' => $session->payment_intent,
                            'amount' => (float) ($session->amount_total / 100),
                            'currency' => $session->currency,
                        ];

                        $appointment_id = $appointments_api->create_appointment_from_payment($form_data, $payment_info);

                        if (is_wp_error($appointment_id)) {
                        } else {
                        }
                    }
                }
                break;

            default:
                break;
        }

        return new WP_REST_Response(['status' => 'success'], 200);
    }

    /**
     * Checks if the user has permissions for frontend actions (can be public or requires authentication).
     *
     * @param WP_REST_Request $request The request object.
     * @return bool|WP_Error
     */
    public function check_frontend_permissions(WP_REST_Request $request)
    {
        // For public-facing payment initiation/capture, no admin capabilities are strictly needed.
        return true;
    }

    /**
     * Checks if the current user has admin permissions to manage settings.
     *
     * @param WP_REST_Request $request The request object.
     * @return bool|WP_Error
     */
    public function check_admin_permissions(WP_REST_Request $request)
    {
        if (!current_user_can('manage_options')) {
            return new WP_Error('rest_forbidden', __('You do not have permission to perform this action.', 'schedula-smart-appointment-booking'), ['status' => 403]);
        }
        return true;
    }

    /**
     * Retrieves and decrypts Stripe credentials and merges them with general payment settings.
     *
     * @return array|WP_Error An array of settings on success, or a WP_Error on failure.
     */
    public function get_stripe_credentials_and_mode()
    {
        $stripe_settings = get_option('schesab_stripe_settings', []);
        $general_settings = get_option('schesab_general_settings', []);

        // Get default settings to ensure all keys are present
        $settings_api = new \SCHESAB\Api\SCHESAB_Settings();
        $stripe_defaults = $settings_api->get_default_settings('stripe');
        $general_defaults = $settings_api->get_default_settings('general');

        $stripe_settings = array_merge($stripe_defaults, $stripe_settings);
        $general_settings = array_merge($general_defaults, $general_settings);

        if (empty($stripe_settings['secretKey'])) {
            return new WP_Error('stripe_not_configured', __('Stripe secret key is not configured.', 'schedula-smart-appointment-booking'), ['status' => 500]);
        }

        // Decrypt sensitive data for use
        $stripe_settings['secretKey'] = SCHESAB_Encryption::decrypt_data($stripe_settings['secretKey']);
        $stripe_settings['publishableKey'] = SCHESAB_Encryption::decrypt_data($stripe_settings['publishableKey']);
        $stripe_settings['webhookSecret'] = SCHESAB_Encryption::decrypt_data($stripe_settings['webhookSecret']);

        // Combine settings for use in payment flow
        return [
            'enableStripe' => (bool) $stripe_settings['enableStripe'],
            'secretKey' => $stripe_settings['secretKey'],
            'publishableKey' => $stripe_settings['publishableKey'],
            'webhookSecret' => $stripe_settings['webhookSecret'],
            'sandboxMode' => (bool) $stripe_settings['sandboxMode'],
            'priceCorrection' => $stripe_settings['priceCorrection'],
            'currencyCode' => $general_settings['currencyCode'],
        ];
    }

    /**
     * Callback for testing Stripe API credentials.
     * Attempts to make a simple API call (e.g., list payment methods) to verify credentials.
     *
     * @param WP_REST_Request $request The request object.
     * @return WP_REST_Response
     */
    public function test_stripe_credentials(WP_REST_Request $request)
    {
        $publishable_key = $request->get_param('publishableKey');
        $secret_key = $request->get_param('secretKey');

        if (empty($publishable_key) || empty($secret_key)) {
            return new WP_REST_Response(['success' => false, 'message' => __('API keys cannot be empty.', 'schedula-smart-appointment-booking')], 400);
        }

        // Basic format check for publishable key
        if (!preg_match('/^pk_(test|live)_/', $publishable_key)) {
            return new WP_REST_Response(['success' => false, 'message' => __('Invalid Publishable Key format. It should start with pk_test_ or pk_live_.', 'schedula-smart-appointment-booking')], 400);
        }

        // Basic format check for secret key
        if (!preg_match('/^(sk|rk)_(test|live)_/', $secret_key)) {
            return new WP_REST_Response(['success' => false, 'message' => __('Invalid Secret Key format. It should start with sk_test_, sk_live_, rk_test_, or rk_live_.', 'schedula-smart-appointment-booking')], 400);
        }

        try {
            $stripe = new \Stripe\StripeClient($secret_key);
            $stripe->customers->all(['limit' => 1]);
            return new WP_REST_Response(['success' => true, 'message' => __('Connection successful!', 'schedula-smart-appointment-booking')], 200);
        } catch (\Exception $e) {
            return new WP_REST_Response([
                'success' => false,
                'message' => __('Connection failed:', 'schedula-smart-appointment-booking') . ' ' . $e->getMessage()
            ], 400);
        }
    }
}