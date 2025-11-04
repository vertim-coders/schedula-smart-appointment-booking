<?php

/**
 * Schedula - Appointment Cancellation Page Template
 *
 * This template is loaded virtually by the plugin to handle appointment cancellations.
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$token = get_query_var('token') ? sanitize_text_field(get_query_var('token')) : '';
$message = '';
$show_form = false;
$appointment = null;
$service_name = '';

if (empty($token)) {
    $message = __('Invalid cancellation link. No token provided.', 'schedula-smart-appointment-booking');
} else {
    $appointments_table = $wpdb->prefix . 'schesab_appointments';
    $services_table = $wpdb->prefix . 'schesab_services';

    // Fetch appointment and service details
    $appointment = $wpdb->get_row(
        $wpdb->prepare(
            "
            SELECT a.*, s.title as service_title
            FROM {$appointments_table} as a
            LEFT JOIN {$services_table} as s ON a.service_id = s.id
            WHERE a.cancellation_token = %s
            ",
            $token
        ),
        ARRAY_A
    );

    if (!$appointment) {
        $message = __('This cancellation link is invalid or has expired.', 'schedula-smart-appointment-booking');
    } else if ($appointment['status'] === 'cancelled') {
        $message = __('This appointment has already been cancelled.', 'schedula-smart-appointment-booking');
    } else {
        // Check if cancellation is still possible
        $general_settings = get_option('schesab_general_settings', []);
        $min_time_canceling = isset($general_settings['minTimeCanceling']) ? intval($general_settings['minTimeCanceling']) : 0;
        $start_timestamp = strtotime($appointment['start_datetime']);
        $cutoff_timestamp = $start_timestamp - ($min_time_canceling * 60);
        $now_timestamp = current_time('timestamp');

        if ($min_time_canceling > 0 && $now_timestamp > $cutoff_timestamp) {
            $message = __('This appointment can no longer be cancelled as the cancellation period has passed.', 'schedula-smart-appointment-booking');
        } else {
            $show_form = true;
            $service_name = $appointment['service_title'];
        }
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_appointment_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['cancel_appointment_nonce'])), 'schesab_cancel_appointment')) {
    $show_form = false; // Hide form after submission
    $api_url = home_url('/wp-json/schesab/v1/appointments/cancel-by-token');
    $response = wp_remote_post($api_url, [
        'method' => 'POST',
        'headers' => ['Content-Type' => 'application/json'],
        'body' => json_encode(['token' => $token]),
        'timeout' => 20,
    ]);

    if (is_wp_error($response)) {
        $message = __('An error occurred while trying to cancel the appointment. Please try again later.', 'schedula-smart-appointment-booking');
    } else {
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        if (wp_remote_retrieve_response_code($response) === 200) {
            $message = __('Your appointment has been successfully cancelled.', 'schedula-smart-appointment-booking');
        } else {
            $message = isset($data['message']) ? esc_html($data['message']) : __('Could not cancel the appointment. Please contact us directly.', 'schedula-smart-appointment-booking');
        }
    }
}

// Basic styling for the page
$settings = get_option('schesab_appearance_settings', []);
$primary_color = $settings['colors']['primary'] ?? '#3b82f6';
$text_color = $settings['colors']['textColor'] ?? '#333333';
$bg_color = $settings['colors']['background'] ?? '#f9fafb';

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php esc_html_e('Cancel Appointment', 'schedula-smart-appointment-booking'); ?></title>
    <?php
    // Enqueue styles using WordPress best practices
    wp_register_style('schedula-cancellation', false, array(), SCHESAB_VERSION);
    wp_enqueue_style('schedula-cancellation');

    $custom_css = "
        body { background-color: " . esc_attr($bg_color) . "; font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, Helvetica, Arial, sans-serif; color: " . esc_attr($text_color) . "; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; padding: 20px; box-sizing: border-box; }
        .container { background-color: #ffffff; padding: 40px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); text-align: center; max-width: 500px; width: 100%; }
        h1 { font-size: 24px; margin-bottom: 15px; }
        p { margin-bottom: 25px; line-height: 1.6; }
        .appointment-details { text-align: left; margin-bottom: 30px; padding: 20px; background-color: #f9f9f9; border-left: 4px solid " . esc_attr($primary_color) . "; border-radius: 4px; }
        .appointment-details strong { color: " . esc_attr($text_color) . "; }
        .button { display: inline-block; background-color: " . esc_attr($primary_color) . "; color: #ffffff; padding: 12px 25px; border-radius: 5px; text-decoration: none; font-weight: bold; border: none; cursor: pointer; font-size: 16px; transition: background-color 0.3s; }
        .button:hover { background-color: #5296f7; }
        .button.button-secondary { background-color: #cccccc; margin-top: 15px; }
        .button.button-secondary:hover { background-color: #bbbbbb; }
        .button.button-danger { background-color: #dc3545; }
        .button.button-danger:hover { background-color: #c82333; }
        .button.button-success { background-color: #28a745; }
        .button.button-success:hover { background-color: #218838; }
        .message { padding: 20px; border-radius: 5px; font-weight: bold; }
        .message.success { background-color: #166534; color: #ffffff; }
        .message.error { background-color: #dc3545; color: #ffffff; }
        .button-container { display: flex; gap: 20px; justify-content: center; align-items: center; margin-top: 20px; flex-wrap: wrap; }
        form { margin: 0; }

        /* Loader styles */
        .loader {
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-top-color: #fff;
            border-radius: 50%;
            width: 1.2em;
            height: 1.2em;
            animation: spin 1s linear infinite;
            display: none;
            margin: 0 auto;
        }
        .button.loading .button-text {
            display: none;
        }
        .button.loading .loader {
            display: inline-block;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Responsive design for mobile */
        @media (max-width: 480px) {
            .button-container {
                flex-direction: column;
                gap: 15px;
            }
            .button {
                width: 100%;
                min-width: 200px;
            }
        }
    ";
    wp_add_inline_style('schedula-cancellation', $custom_css);

    wp_head();
    ?>
</head>

<body>
    <div class="container">
        <?php if ($show_form): ?>
            <h1><?php esc_html_e('Confirm Your Cancellation', 'schedula-smart-appointment-booking'); ?></h1>
            <div class="appointment-details">
                <p><strong><?php esc_html_e('Service:', 'schedula-smart-appointment-booking'); ?></strong>
                    <?php echo esc_html($service_name); ?></p>
                <p><strong><?php esc_html_e('Date:', 'schedula-smart-appointment-booking'); ?></strong>
                    <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($appointment['start_datetime']))); ?>
                </p>
                <p><strong><?php esc_html_e('Time:', 'schedula-smart-appointment-booking'); ?></strong>
                    <?php echo esc_html(date_i18n('g:i a', strtotime($appointment['start_datetime']))); ?></p>
            </div>
            <p><?php esc_html_e('Do you really want to cancel this appointment?', 'schedula-smart-appointment-booking'); ?>
            </p>
            <div class="button-container">
                <form id="schedula-cancel-form" method="POST" action="">
                    <?php wp_nonce_field('schesab_cancel_appointment', 'cancel_appointment_nonce'); ?>
                    <button type="submit" class="button button-danger">
                        <span
                            class="button-text"><?php esc_html_e('Yes, Cancel My Appointment', 'schedula-smart-appointment-booking'); ?></span>
                        <span class="loader"></span>
                    </button>
                </form>
                <a href="<?php echo esc_url(home_url()); ?>"
                    class="button button-success"><?php esc_html_e('No, Keep My Appointment', 'schedula-smart-appointment-booking'); ?></a>
            </div>
        <?php else: ?>
            <h1><?php esc_html_e('Cancellation Status', 'schedula-smart-appointment-booking'); ?></h1>
            <p class="message <?php echo strpos($message, 'success') !== false ? 'success' : 'error'; ?>">
                <?php echo esc_html($message); ?></p>
            <a href="<?php echo esc_url(home_url()); ?>"
                class="button"><?php esc_html_e('Go to Homepage', 'schedula-smart-appointment-booking'); ?></a>
        <?php endif; ?>
    </div>

    <?php
    // Enqueue scripts using WordPress best practices
    wp_register_script('schedula-cancellation', false, array(), SCHESAB_VERSION, true);
    wp_enqueue_script('schedula-cancellation');

    $custom_js = "
        document.addEventListener('DOMContentLoaded', function() {
            const cancelForm = document.getElementById('schedula-cancel-form');
            if (cancelForm) {
                cancelForm.addEventListener('submit', function(e) {
                    const button = cancelForm.querySelector('button[type=\"submit\"]');
                    button.classList.add('loading');
                    button.disabled = true;
                    const buttonText = button.querySelector('.button-text');
                    if(buttonText) {
                        buttonText.style.display = 'none';
                    }
                    const loader = button.querySelector('.loader');
                    if(loader) {
                        loader.style.display = 'inline-block';
                    }
                });
            }
        });
    ";
    wp_add_inline_script('schedula-cancellation', $custom_js);

    wp_footer();
    ?>
</body>

</html>