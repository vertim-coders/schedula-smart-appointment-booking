<?php

/**
 * Database structure for Schedula
 * File: includes/database/class-database.php
 */

namespace SCHESAB\Database;

if (!defined('ABSPATH')) {
    exit;
}

class SCHESAB_Database
{

    /**
     * Database version
     */
    const DB_VERSION = '1.0.45'; // Incremented version 

    /**
     * Unique instance of the class
     */
    private static $instance = null;

    /**
     * Table prefix
     */
    private $table_prefix;

    /**
     * Constructor
     */
    private function __construct()
    {
        global $wpdb;
        $this->table_prefix = $wpdb->prefix . 'schesab_';
        // Hook to run database updates on plugin load
        add_action('plugins_loaded', array($this, 'update_database'));
    }

    /**
     * Get the single instance
     */
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Returns the full table name with WordPress prefix and plugin prefix.
     */
    public function get_table_name($table_name)
    {
        return $this->table_prefix . $table_name;
    }

    /**
     * This method checks the current DB version and calls create_tables() if an update is needed.
     */
    public function update_database()
    {
        $installed_version = get_option('schesab_db_version');

        if ($installed_version !== self::DB_VERSION) {
            $this->create_tables();
            update_option('schesab_db_version', self::DB_VERSION);
        }
    }

    /**
     * Create all tables (or update them if they exist) using dbDelta.
     */
    public function create_tables()
    {
        global $wpdb;
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $this->create_categories_table();
        $this->create_services_table();
        $this->create_staff_table();
        $this->create_staff_schedule_table();
        $this->create_staff_services_table();
        $this->create_holidays_table();
        $this->create_customers_table();
        $this->create_series_table();
        $this->create_appointments_table();
        $this->create_customer_appointments_table();
        $this->create_orders_table();
        $this->create_payments_table();
        $this->create_sub_services_table();
        $this->create_schedule_item_breaks_table();
        $this->create_newsletter_subscribers_table();
        $this->create_forms_table();
        $this->create_email_log_table(); // Add this line

        $this->add_foreign_keys();
    }

    /**
     * Create email_log table
     */
    private function create_email_log_table()
    {
        global $wpdb;
        $table_name = $this->get_table_name('email_log');
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            recipient TEXT NOT NULL,
            subject TEXT NOT NULL,
            body LONGTEXT NOT NULL,
            sent_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'sent',
            error_message TEXT,
            PRIMARY KEY (id)
        ) $charset_collate;";

        dbDelta($sql);
    }

    /**
     * Create categories table
     */
    private function create_categories_table()
    {
        global $wpdb;
        $table_name = $this->get_table_name('categories');
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            description text,
            position int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";

        dbDelta($sql);
    }

    /**
     * Create services table - SANS clés étrangères
     */
    private function create_services_table()
    {
        global $wpdb;
        $table_name = $this->get_table_name('services');
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            category_id mediumint(9) DEFAULT NULL,
            title varchar(255) NOT NULL,
            description text,
            duration int(11) NOT NULL DEFAULT 0,
            price decimal(10,2) NOT NULL DEFAULT 0.00,
            image_url varchar(255) NULL DEFAULT NULL,
            position int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY (id),
            KEY category_id (category_id)
        ) $charset_collate;";

        dbDelta($sql);
    }

    /**
     * Create staff table - SANS clés étrangères
     */
    private function create_staff_table()
    {
        global $wpdb;
        $table_name = $this->get_table_name('staff');
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED DEFAULT NULL,
            name varchar(190) NOT NULL,
            email varchar(190) NOT NULL UNIQUE,
            phone varchar(50),
            image_url varchar(255) NULL DEFAULT NULL,
            description text,
            status varchar(20) DEFAULT 'active' NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY (id),
            KEY user_id (user_id)
        ) $charset_collate;";

        dbDelta($sql);
    }

    /**
     * Create staff schedule table - SANS clés étrangères
     */
    private function create_staff_schedule_table()
    {
        global $wpdb;
        $table_name = $this->get_table_name('staff_schedule');
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            staff_id mediumint(9) NOT NULL,
            day_of_week tinyint(1) NOT NULL,
            start_time time NOT NULL,
            end_time time NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY (id),
            KEY staff_id (staff_id)
        ) $charset_collate;";

        dbDelta($sql);
    }

    /**
     * Create staff services table - SANS clés étrangères
     */
    private function create_staff_services_table()
    {
        global $wpdb;
        $table_name = $this->get_table_name('staff_services');
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            staff_id mediumint(9) NOT NULL,
            service_id mediumint(9) NOT NULL,
            price decimal(10,2) DEFAULT NULL, 
            duration int(11) UNSIGNED NULL, 
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY staff_service (staff_id, service_id)
        ) $charset_collate;";

        dbDelta($sql);
    }

    /**
     * Create holidays table - SANS clés étrangères
     */
    private function create_holidays_table()
    {
        global $wpdb;
        $table_name = $this->get_table_name('holidays');
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            staff_id mediumint(9) DEFAULT NULL, 
            holiday_date date NULL, 
            start_date date NULL, 
            end_date date NULL,   
            description text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY (id),
            KEY staff_id (staff_id)
        ) $charset_collate;";

        dbDelta($sql);
    }

    /**
     * Create customers table - SANS clés étrangères
     */
    private function create_customers_table()
    {
        global $wpdb;
        $table_name = $this->get_table_name('customers');
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED DEFAULT NULL,
            first_name varchar(190) NOT NULL,
            last_name varchar(190) NOT NULL,
            email varchar(190) NOT NULL UNIQUE,
            phone varchar(50),
            notes text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY (id),
            KEY user_id (user_id)
        ) $charset_collate;";

        dbDelta($sql);
    }

    /**
     * Create series table - SANS clés étrangères
     */
    private function create_series_table()
    {
        global $wpdb;
        $table_name = $this->get_table_name('series');
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(190) NOT NULL,
            description text,
            frequency varchar(50) NOT NULL,
            `interval` int(11) NOT NULL DEFAULT 1,
            start_date date NOT NULL,
            end_date date DEFAULT NULL,
            occurrence_count int(11) DEFAULT NULL,
            service_id mediumint(9) DEFAULT NULL,
            staff_id mediumint(9) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY (id),
            KEY service_id (service_id),
            KEY staff_id (staff_id)
        ) $charset_collate;";

        dbDelta($sql);
    }

    /**
     * Create appointments table - SANS clés étrangères
     */
    private function create_appointments_table()
    {
        global $wpdb;
        $table_name = $this->get_table_name('appointments');
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            service_id mediumint(9) NOT NULL,
            staff_id mediumint(9) DEFAULT NULL,
            series_id mediumint(9) DEFAULT NULL, 
            customer_name varchar(190) NOT NULL, 
            customer_email varchar(190) NOT NULL, 
            customer_phone varchar(50), 
            start_datetime datetime NOT NULL,
            end_datetime datetime NOT NULL,
            duration int(11) NOT NULL,  
            price decimal(10,2) NOT NULL, 
            status varchar(20) DEFAULT 'pending' NOT NULL,
            payment_status varchar(20) DEFAULT 'unpaid' NOT NULL,
            cancellation_token VARCHAR(64) NULL DEFAULT NULL,
            notes text, 
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY (id),
            KEY start_datetime (start_datetime),
            KEY service_id (service_id),
            KEY staff_id (staff_id),
            KEY series_id (series_id),
            KEY cancellation_token (cancellation_token)
        ) $charset_collate;";

        dbDelta($sql);
    }

    /**
     * Create customer_appointments table - SANS clés étrangères
     */
    private function create_customer_appointments_table()
    {
        global $wpdb;
        $table_name = $this->get_table_name('customer_appointments');
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            customer_id mediumint(9) NOT NULL,
            appointment_id mediumint(9) NOT NULL,
            series_id mediumint(9) DEFAULT NULL, 
            number_of_persons int(11) DEFAULT 1,
            role varchar(50) DEFAULT 'participant', 
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY customer_appointment_unique (customer_id, appointment_id),
            KEY series_id (series_id)
        ) $charset_collate;";

        dbDelta($sql);
    }

    /**
     * Create orders table - SANS clés étrangères
     */
    private function create_orders_table()
    {
        global $wpdb;
        $table_name = $this->get_table_name('orders');
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            customer_id mediumint(9) DEFAULT NULL, 
            order_date datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            total_amount decimal(10,2) NOT NULL DEFAULT 0.00,
            status varchar(20) DEFAULT 'pending' NOT NULL, 
            notes text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY (id),
            KEY customer_id (customer_id)
        ) $charset_collate;";

        dbDelta($sql);
    }

    /**
     * Create payments table - SANS clés étrangères
     */
    private function create_payments_table()
    {
        global $wpdb;
        $table_name = $this->get_table_name('payments');
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            appointment_id mediumint(9) DEFAULT NULL, 
            order_id mediumint(9) DEFAULT NULL, 
            amount DECIMAL(10, 2) NOT NULL,
            payment_type VARCHAR(50) NOT NULL DEFAULT 'local', 
            transaction_id VARCHAR(255) NULL,
            payment_date DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'pending', 
            notes TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY (id),
            KEY appointment_id (appointment_id),
            KEY order_id (order_id)
        ) $charset_collate;";

        dbDelta($sql);
    }

    /**
     * Create sub_services table - SANS clés étrangères
     */
    private function create_sub_services_table()
    {
        global $wpdb;
        $table_name = $this->get_table_name('sub_services');
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            parent_service_id mediumint(9) NOT NULL,
            child_service_id mediumint(9) NOT NULL,
            quantity int(11) DEFAULT 1 NOT NULL, 
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY parent_child_unique (parent_service_id, child_service_id)
        ) $charset_collate;";

        dbDelta($sql);
    }

    /**
     * Create schedule_item_breaks table - SANS clés étrangères
     */
    private function create_schedule_item_breaks_table()
    {
        global $wpdb;
        $table_name = $this->get_table_name('schedule_item_breaks');
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            schedule_item_id mediumint(9) NOT NULL,
            start_time time NOT NULL,
            end_time time NOT NULL,
            description varchar(255),
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY (id),
            KEY schedule_item_id (schedule_item_id)
        ) $charset_collate;";

        dbDelta($sql);
    }

    /**
     * Create newsletter_subscribers table 
     */
    private function create_newsletter_subscribers_table()
    {
        global $wpdb;
        $table_name = $this->get_table_name('newsletter_subscribers');
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            email varchar(255) NOT NULL,
            subscribed_at datetime NOT NULL,
            status varchar(50) DEFAULT 'active' NOT NULL,
            PRIMARY KEY (id)
        ) {$charset_collate};";

        dbDelta($sql);
    }

    /**
     * Create forms table - SANS clés étrangères
     */
    private function create_forms_table()
    {
        global $wpdb;
        $table_name = $this->get_table_name('forms');
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            service_id mediumint(9) DEFAULT NULL,
            category_id mediumint(9) DEFAULT NULL,
            shortcode varchar(191) NOT NULL UNIQUE,
            staff_id bigint(20) DEFAULT NULL,
            form_data longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY (id),
            KEY service_id (service_id),
            KEY category_id (category_id)
        ) $charset_collate;";

        dbDelta($sql);
    }

    /**
     * Ajouter les clés étrangères après la création des tables
     */
    private function add_foreign_keys()
    {
        global $wpdb;

        // Vérifier si les clés étrangères existent déjà avant de les ajouter
        $foreign_keys = array(
            // Table services
            array(
                'table' => $this->get_table_name('services'),
                'constraint' => 'fk_services_category',
                'sql' => "ALTER TABLE {$this->get_table_name('services')} 
                         ADD CONSTRAINT fk_services_category 
                         FOREIGN KEY (category_id) REFERENCES {$this->get_table_name('categories')}(id) 
                         ON DELETE SET NULL"
            ),

            // Table staff
            array(
                'table' => $this->get_table_name('staff'),
                'constraint' => 'fk_staff_user',
                'sql' => "ALTER TABLE {$this->get_table_name('staff')} 
                         ADD CONSTRAINT fk_staff_user 
                         FOREIGN KEY (user_id) REFERENCES {$wpdb->users}(ID) 
                         ON DELETE SET NULL"
            ),

            // Table staff_schedule
            array(
                'table' => $this->get_table_name('staff_schedule'),
                'constraint' => 'fk_staff_schedule_staff',
                'sql' => "ALTER TABLE {$this->get_table_name('staff_schedule')} 
                         ADD CONSTRAINT fk_staff_schedule_staff 
                         FOREIGN KEY (staff_id) REFERENCES {$this->get_table_name('staff')}(id) 
                         ON DELETE CASCADE"
            ),

            // Table staff_services
            array(
                'table' => $this->get_table_name('staff_services'),
                'constraint' => 'fk_staff_services_staff',
                'sql' => "ALTER TABLE {$this->get_table_name('staff_services')} 
                         ADD CONSTRAINT fk_staff_services_staff 
                         FOREIGN KEY (staff_id) REFERENCES {$this->get_table_name('staff')}(id) 
                         ON DELETE CASCADE"
            ),
            array(
                'table' => $this->get_table_name('staff_services'),
                'constraint' => 'fk_staff_services_service',
                'sql' => "ALTER TABLE {$this->get_table_name('staff_services')} 
                         ADD CONSTRAINT fk_staff_services_service 
                         FOREIGN KEY (service_id) REFERENCES {$this->get_table_name('services')}(id) 
                         ON DELETE CASCADE"
            ),

            // Table holidays
            array(
                'table' => $this->get_table_name('holidays'),
                'constraint' => 'fk_holidays_staff',
                'sql' => "ALTER TABLE {$this->get_table_name('holidays')} 
                         ADD CONSTRAINT fk_holidays_staff 
                         FOREIGN KEY (staff_id) REFERENCES {$this->get_table_name('staff')}(id) 
                         ON DELETE CASCADE"
            ),

            // Table customers
            array(
                'table' => $this->get_table_name('customers'),
                'constraint' => 'fk_customers_user',
                'sql' => "ALTER TABLE {$this->get_table_name('customers')} 
                         ADD CONSTRAINT fk_customers_user 
                         FOREIGN KEY (user_id) REFERENCES {$wpdb->users}(ID) 
                         ON DELETE SET NULL"
            ),

            // Table series
            array(
                'table' => $this->get_table_name('series'),
                'constraint' => 'fk_series_service',
                'sql' => "ALTER TABLE {$this->get_table_name('series')} 
                         ADD CONSTRAINT fk_series_service 
                         FOREIGN KEY (service_id) REFERENCES {$this->get_table_name('services')}(id) 
                         ON DELETE SET NULL"
            ),
            array(
                'table' => $this->get_table_name('series'),
                'constraint' => 'fk_series_staff',
                'sql' => "ALTER TABLE {$this->get_table_name('series')} 
                         ADD CONSTRAINT fk_series_staff 
                         FOREIGN KEY (staff_id) REFERENCES {$this->get_table_name('staff')}(id) 
                         ON DELETE SET NULL"
            ),

            // Table appointments
            array(
                'table' => $this->get_table_name('appointments'),
                'constraint' => 'fk_appointments_service',
                'sql' => "ALTER TABLE {$this->get_table_name('appointments')} 
                         ADD CONSTRAINT fk_appointments_service 
                         FOREIGN KEY (service_id) REFERENCES {$this->get_table_name('services')}(id) 
                         ON DELETE CASCADE"
            ),
            array(
                'table' => $this->get_table_name('appointments'),
                'constraint' => 'fk_appointments_staff',
                'sql' => "ALTER TABLE {$this->get_table_name('appointments')} 
                         ADD CONSTRAINT fk_appointments_staff 
                         FOREIGN KEY (staff_id) REFERENCES {$this->get_table_name('staff')}(id) 
                         ON DELETE SET NULL"
            ),
            array(
                'table' => $this->get_table_name('appointments'),
                'constraint' => 'fk_appointments_series',
                'sql' => "ALTER TABLE {$this->get_table_name('appointments')} 
                         ADD CONSTRAINT fk_appointments_series 
                         FOREIGN KEY (series_id) REFERENCES {$this->get_table_name('series')}(id) 
                         ON DELETE SET NULL"
            ),

            // Table customer_appointments
            array(
                'table' => $this->get_table_name('customer_appointments'),
                'constraint' => 'fk_customer_appointments_customer',
                'sql' => "ALTER TABLE {$this->get_table_name('customer_appointments')} 
                         ADD CONSTRAINT fk_customer_appointments_customer 
                         FOREIGN KEY (customer_id) REFERENCES {$this->get_table_name('customers')}(id) 
                         ON DELETE CASCADE"
            ),
            array(
                'table' => $this->get_table_name('customer_appointments'),
                'constraint' => 'fk_customer_appointments_appointment',
                'sql' => "ALTER TABLE {$this->get_table_name('customer_appointments')} 
                         ADD CONSTRAINT fk_customer_appointments_appointment 
                         FOREIGN KEY (appointment_id) REFERENCES {$this->get_table_name('appointments')}(id) 
                         ON DELETE CASCADE"
            ),
            array(
                'table' => $this->get_table_name('customer_appointments'),
                'constraint' => 'fk_customer_appointments_series',
                'sql' => "ALTER TABLE {$this->get_table_name('customer_appointments')} 
                         ADD CONSTRAINT fk_customer_appointments_series 
                         FOREIGN KEY (series_id) REFERENCES {$this->get_table_name('series')}(id) 
                         ON DELETE SET NULL"
            ),

            // Table orders
            array(
                'table' => $this->get_table_name('orders'),
                'constraint' => 'fk_orders_customer',
                'sql' => "ALTER TABLE {$this->get_table_name('orders')} 
                         ADD CONSTRAINT fk_orders_customer 
                         FOREIGN KEY (customer_id) REFERENCES {$this->get_table_name('customers')}(id) 
                         ON DELETE SET NULL"
            ),

            // Table payments
            array(
                'table' => $this->get_table_name('payments'),
                'constraint' => 'fk_payments_appointment',
                'sql' => "ALTER TABLE {$this->get_table_name('payments')} 
                         ADD CONSTRAINT fk_payments_appointment 
                         FOREIGN KEY (appointment_id) REFERENCES {$this->get_table_name('appointments')}(id) 
                         ON DELETE SET NULL"
            ),
            array(
                'table' => $this->get_table_name('payments'),
                'constraint' => 'fk_payments_order',
                'sql' => "ALTER TABLE {$this->get_table_name('payments')} 
                         ADD CONSTRAINT fk_payments_order 
                         FOREIGN KEY (order_id) REFERENCES {$this->get_table_name('orders')}(id) 
                         ON DELETE SET NULL"
            ),

            // Table sub_services
            array(
                'table' => $this->get_table_name('sub_services'),
                'constraint' => 'fk_sub_services_parent',
                'sql' => "ALTER TABLE {$this->get_table_name('sub_services')} 
                         ADD CONSTRAINT fk_sub_services_parent 
                         FOREIGN KEY (parent_service_id) REFERENCES {$this->get_table_name('services')}(id) 
                         ON DELETE CASCADE"
            ),
            array(
                'table' => $this->get_table_name('sub_services'),
                'constraint' => 'fk_sub_services_child',
                'sql' => "ALTER TABLE {$this->get_table_name('sub_services')} 
                         ADD CONSTRAINT fk_sub_services_child 
                         FOREIGN KEY (child_service_id) REFERENCES {$this->get_table_name('services')}(id) 
                         ON DELETE CASCADE"
            ),

            // Table schedule_item_breaks
            array(
                'table' => $this->get_table_name('schedule_item_breaks'),
                'constraint' => 'fk_schedule_item_breaks_schedule',
                'sql' => "ALTER TABLE {$this->get_table_name('schedule_item_breaks')} 
                         ADD CONSTRAINT fk_schedule_item_breaks_schedule 
                         FOREIGN KEY (schedule_item_id) REFERENCES {$this->get_table_name('staff_schedule')}(id) 
                         ON DELETE CASCADE"
            ),

            // Table forms
            array(
                'table' => $this->get_table_name('forms'),
                'constraint' => 'fk_forms_service',
                'sql' => "ALTER TABLE {$this->get_table_name('forms')} 
                         ADD CONSTRAINT fk_forms_service 
                         FOREIGN KEY (service_id) REFERENCES {$this->get_table_name('services')}(id) 
                         ON DELETE SET NULL"
            ),
            array(
                'table' => $this->get_table_name('forms'),
                'constraint' => 'fk_forms_category',
                'sql' => "ALTER TABLE {$this->get_table_name('forms')} 
                         ADD CONSTRAINT fk_forms_category 
                         FOREIGN KEY (category_id) REFERENCES {$this->get_table_name('categories')}(id) 
                         ON DELETE SET NULL"
            ),
        );

        foreach ($foreign_keys as $fk) {
            // Vérifier si la contrainte existe déjà
            $constraint_exists = $wpdb->get_var($wpdb->prepare("
                SELECT CONSTRAINT_NAME 
                FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = %s 
                AND TABLE_NAME = %s 
                AND CONSTRAINT_NAME = %s
            ", $wpdb->dbname, $fk['table'], $fk['constraint']));

            if (!$constraint_exists) {
                // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $fk['sql'] is safe and not user input
                $result = $wpdb->query($fk['sql']);
                if ($result === false) {
                    error_log(sprintf(
                        /* translators: 1: Foreign key constraint name, 2: Database error message */
                        __('Schedula: Erreur lors de l\'ajout de la clé étrangère %1$s: %2$s', 'schedula-smart-appointment-booking'),
                        $fk['constraint'],
                        $wpdb->last_error
                    ));
                }
            }
        }
    }
}
