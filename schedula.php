<?php
/*
Plugin Name: Schedula Smart Appointment Booking
Description: Schedula is a comprehensive WordPress booking plugin designed for service-based businesses. It features an intuitive frontend reservation system where customers can easily book appointments and make payments online, paired with a powerful admin dashboard for managing services, staff schedules, customer data, and appointments. Perfect for hairdressers, consultants, beauty salons, garages, and other local professionals who need a reliable, responsive booking solution that handles the scheduling complexity while they focus on their business.
Version: 1.0
Author: Vertim Coders
Author URI: https://vertimcoders.com
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: schedula-smart-appointment-booking
Domain Path: /languages
*/

/**
 * Copyright (c) 2025 Vertim Coders. All rights reserved.
 *
 * Released under the GPL license
 * http://www.opensource.org/licenses/gpl-license.php
 *
 * This is an add-on for WordPress
 * http://wordpress.org/
 *
 * Inspired by:
 *
 * **********************************************************************
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 * **********************************************************************
 */

// don't call the file directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * PLUGIN CONSTANTS
 * These constants are used throughout the plugin
 */
define('SCHESAB_VERSION', '1.0.0');
define('SCHESAB_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SCHESAB_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SCHESAB_PLUGIN_BASENAME', plugin_basename(__FILE__));


spl_autoload_register(function ($class) {
    // Project-specific namespace prefix
    $prefix = 'SCHESAB\\';

    // Base directory for the namespace prefix (includes folder)
    $base_dir = SCHESAB_PLUGIN_DIR . 'includes/';

    // Does the class use the namespace prefix?
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        // No, move to the next registered autoloader.
        return;
    }

    // Get the relative class name 
    $relative_class = substr($class, $len);

    // Determine the final file path based on namespace structure and naming conventions
    $file = '';

    // Handle the main plugin class
    if ($relative_class === 'Schesab') {
        $file = $base_dir . 'class-schedula.php';
    }
    // Special case: main API class
    else if ($relative_class === 'Api\\SCHESAB_Api') {
        $file = $base_dir . 'api/class-schedula-api.php';
    }
    // Special case: Database class
    else if ($relative_class === 'Database\\SCHESAB_Database') {
        $file = $base_dir . 'database/class-database.php';
    }
    // Other API controllers (e.g. SCHESAB\Api\SCHESAB_Customers)
    else if (strpos($relative_class, 'Api\\') === 0 && count(explode('\\', $relative_class)) === 2) {
        $class_name_without_namespace = basename(str_replace('\\', '/', $relative_class));
        // Map 'SCHESAB_Customers' -> 'includes/api/class-schedula-api-customers.php'
        $mapped_api_name = preg_replace('/^SCHESAB_/', '', $class_name_without_namespace);
        $mapped_api_name = strtolower(str_replace('_', '-', $mapped_api_name));
        $file = $base_dir . 'api/class-schedula-api-' . $mapped_api_name . '.php';
    }
    // All other classes
    else {
        $file_path_parts = explode('\\', $relative_class);
        $last_segment = array_pop($file_path_parts);
        $file = $base_dir . implode('/', $file_path_parts) . '/class-' . str_replace('_', '-', strtolower($last_segment)) . '.php';
    }

    if (file_exists($file)) {
        require $file;
    }
});

/**
 * PLUGIN ACTIVATION
 * This function executes when the plugin is activated in WordPress
 */
function activate_schedula()
{
    require_once SCHESAB_PLUGIN_DIR . 'includes/class-activator.php';

    \SCHESAB\Schesab_Activator::activate();
}

/**
 * PLUGIN DEACTIVATION
 * This function executes when the plugin is deactivated
 */
function deactivate_schedula()
{
    // The Deactivator class is now namespaced, so call it with its full name.
    // The autoloader will load it.
    require_once SCHESAB_PLUGIN_DIR . 'includes/class-deactivator.php';
    \SCHESAB\Schesab_Deactivator::deactivate();
}


// Tell WordPress which functions to call during activation/deactivation
register_activation_hook(__FILE__, 'activate_schedula');
register_deactivation_hook(__FILE__, 'deactivate_schedula');


/**
 * LOADING THE MAIN CLASS
 * This file requires the main Schedula class, which is now namespaced.
 */
require_once SCHESAB_PLUGIN_DIR . 'vendor/autoload.php';
require SCHESAB_PLUGIN_DIR . 'includes/class-schedula.php';

/**
 * PLUGIN STARTUP
 * This function starts the entire plugin by instantiating the main Schedula class.
 */
function run_schedula()
{
    $plugin = new \SCHESAB\Schesab();
    $plugin->run();
}

// Start the plugin as soon as WordPress is ready
run_schedula();
