<?php
/**
 * Main Schedula REST API Handler.
 * Registers all specific API controllers.
 *
 * @package Schedula
 * @subpackage API
 */

namespace SCHESAB\Api;
use SCHESAB\Api\SCHESAB_Customers;
use SCHESAB\Api\SCHESAB_Appointments;
use SCHESAB\Api\SCHESAB_Services;
use SCHESAB\Api\SCHESAB_Categories;
use SCHESAB\Api\SCHESAB_Staff;
use SCHESAB\Api\SCHESAB_Payments;
use SCHESAB\Api\SCHESAB_Newsletter;
use SCHESAB\Api\SCHESAB_Appearance;
use SCHESAB\Api\SCHESAB_Settings;
use SCHESAB\Api\SCHESAB_Forms;
use SCHESAB\Api\SCHESAB_Notifications;
use SCHESAB\Api\SCHESAB_Analytics;
use SCHESAB\Api\SCHESAB_Stripe;
use SCHESAB\Api\SCHESAB_Contact; 


if (!defined('ABSPATH')) {
    exit;
}

class SCHESAB_Api
{

    public function __construct()
    {
        // Hook into WordPress's REST API initialization action
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    /**
     * Register all specific API controller routes.
     * Each controller class handles its own route registration.
     */
    public function register_routes()
    {
        // Instantiate and register routes for each API controller.
        // The autoloader will find these classes based on their namespace
        (new SCHESAB_Customers())->register_routes();
        (new SCHESAB_Appointments())->register_routes();
        (new SCHESAB_Services())->register_routes();
        (new SCHESAB_Categories())->register_routes();
        (new SCHESAB_Staff())->register_routes();
        (new SCHESAB_Payments())->register_routes();
        (new SCHESAB_Newsletter())->register_routes();
        (new SCHESAB_Appearance())->register_routes();
        (new SCHESAB_Settings())->register_routes();
        (new SCHESAB_Forms())->register_routes();
        (new SCHESAB_Notifications())->register_routes();
        (new SCHESAB_Analytics())->register_routes();
        (new SCHESAB_Stripe())->register_routes();
        (new SCHESAB_Contact())->register_routes();
    }
}