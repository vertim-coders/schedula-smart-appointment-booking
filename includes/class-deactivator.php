<?php

/**
 * Plugin Deactivator for Schedula.
 * Handles cleanup tasks upon plugin deactivation.
 *
 * @package Schedula
 */

namespace SCHESAB; // Namespace for this class

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Schesab_Deactivator
{

    /**
     * Main deactivation method.
     * This method is executed when the plugin is deactivated.
     */
    public static function deactivate()
    {
        //self::schesab_delete_plugin_tables_dev();
        // Clear any scheduled cron jobs created by the plugin
        wp_clear_scheduled_hook('schesab_send_reminders');
        wp_clear_scheduled_hook('schesab_cleanup');
        wp_clear_scheduled_hook('schesab_sync_calendars');
        wp_clear_scheduled_hook('schesab_generate_reports');


        // Flush rewrite rules to remove any custom rewrite rules added by the plugin
        flush_rewrite_rules();

        // You might want to log deactivation for debugging or analytics
        error_log('Schedula Plugin Deactivated at ' . current_time('mysql'));
    }

    private static function schesab_delete_plugin_tables_dev()
    {
        global $wpdb;

        $table_prefix = $wpdb->prefix . 'schesab_';

        // Ordre de suppression des tables pour gérer les dépendances des clés étrangères
        $tables_to_delete = array(
            $table_prefix . 'schedule_item_breaks',
            $table_prefix . 'sub_services',
            $table_prefix . 'payments',
            $table_prefix . 'customer_appointments',
            $table_prefix . 'appointments',
            $table_prefix . 'series',
            $table_prefix . 'holidays',
            $table_prefix . 'staff_services',
            $table_prefix . 'staff_schedule',
            $table_prefix . 'services',
            $table_prefix . 'orders',
            $table_prefix . 'staff',
            $table_prefix . 'customers',
            $table_prefix . 'categories',
        );

        foreach ($tables_to_delete as $table) {
            $wpdb->query("DROP TABLE IF EXISTS `$table`");
            error_log("Table supprimée (DEV MODE): " . $table);
        }

        error_log('Toutes les tables Schedula ont été supprimées en mode développement.');
    }
}
