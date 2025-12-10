<?php

use FastCourier\Menu;

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the
 * plugin admin area. This file also defines a function that starts the plugin.
 *
 * @link              https://fastcourier.com.au
 * @since             1.0
 * @package           Fast Courier - Shipping & Freight
 *
 * @wordpress-plugin
 * Plugin Name:       Fast Courier - Shipping & Freight
 * Plugin URI:        https://fastcourier.com.au
 * Description:       Fast Courier is an Australian Courier & Freight shipping platform. Connect your WooCommerce Store with a network of Courier & Freight Providers. See more about Fast Courier Services here: <a href="https://fastcourier.com.au" target="_blank">https://fastcourier.com.au</a>
 * Version:           5.2.0
 * Author:            Fast Courier Australia
 * License:           GPLv2
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

if (!function_exists('fc_check_version_compatibility')) {
    function fc_check_version_compatibility()
    {
        // Check if WooCommerce is active
        if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
            wp_die(__('Unfortunately, you are unable to install this plugin as WooCommerce is not currently installed.', 'fast-courier'));
        }
    }

    register_activation_hook(__FILE__, 'fc_check_version_compatibility');
}

add_action('woocommerce_init', function () {

    if (class_exists('Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController')) {
        $hpos_enabled = wc_get_container()->get(
            \Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController::class
        )->custom_orders_table_usage_is_enabled();
    } else {
        $hpos_enabled = false;
    }
    // Store the result globally
    update_option('fast_courier_hpos_enabled', $hpos_enabled);

    $classicCheckout = false;
    $checkout_page = get_page_by_path('checkout');
    if ($checkout_page) {
        $content = $checkout_page->post_content;

        // Match shortcode even with extra spaces or self-closing tag
        if (preg_match('/\[\s*woocommerce_checkout\s*\/?\s*\]/i', $content)) {
            $classicCheckout = true;
        } elseif (has_shortcode($content, 'woocommerce_checkout')) {
            $classicCheckout = true;
        }
    }

    // Store the result globally
    update_option('fast_courier_checkout_mode', $classicCheckout ? 'classic' : 'block');

    if (!$classicCheckout) {
        // Ensure WooCommerce is active
        if (!class_exists('WC_Shipping_Method')) {
            return;
        }

        // Include your class file
        require_once plugin_dir_path(__FILE__) . 'includes/class-fast-courier-shipping-method.php';

        // Register method with WooCommerce
        add_filter('woocommerce_shipping_methods', function ($methods) {
            $methods['custom_shipping'] = 'Fast_Courier_Shipping_Method';
            return $methods;
        });
    }
});

// require all of our src files
require_once(plugin_dir_path(__FILE__) . '/vendor/autoload.php');

// Include the dependencies needed to instantiate the plugin.
foreach (glob(plugin_dir_path(__FILE__) . 'views/*.php') as $file) {
    include_once $file;
}

include 'configs.php';
include 'functions.php';
include 'actions.php';


/**
 * Starts the plugin.
 *
 * @since 1.0.0
 */
if (!function_exists('_init_fast_courier')) {
    add_action('plugins_loaded', '_init_fast_courier');
    function _init_fast_courier()
    {
        global $slug;

        $plugin = new Menu();
        $plugin->init();

        if (isset($_GET['page']) && strpos($_GET['page'], $slug)) fc_is_unauthorized();
    }
}

if (!function_exists('fc_allow_access')) {
    function fc_allow_access()
    {
        if ($shop_manager = get_role('shop_manager')) {
            $shop_manager->add_cap('manage_fast_courier');
        }

        if ($admin = get_role('administrator')) {
            $admin->add_cap('manage_fast_courier');
        }
    }
    register_activation_hook(__FILE__, 'fc_allow_access');
}

add_action('admin_enqueue_scripts', function () {
    wp_enqueue_script('jquery');
    wp_enqueue_style('fast-courier-admin', plugins_url('/views/styles/bootstrap.min.css', __FILE__));
    wp_enqueue_style('fast-courier-admin-custom', plugins_url('/views/styles/styles.css', __FILE__));
    wp_enqueue_style('fast-courier-admin-select2', plugins_url("/views/libs/select2/css/select2.min.css", __FILE__));
    wp_enqueue_style('fast-courier-admin-font-awesome', plugins_url("/views/libs/fontawesome/css/all.min.css", __FILE__));

    wp_enqueue_script('fast-courier-admin-script', plugins_url('/views/scripts/script.js', __FILE__));
    wp_enqueue_script('fast-courier-admin-sweetalert', plugins_url("/views/libs/sweetalert/sweetalert2.all.js", __FILE__));
    wp_enqueue_script('fast-courier-admin-select2', plugins_url("/views/libs/select2/js/select2.min.js", __FILE__));
});


if (!function_exists('enqueue_select2')) {
    if (isClassicMode()) {
        add_action('wp_enqueue_scripts', 'enqueue_select2');
        function enqueue_select2()
        {
            if (is_checkout() || is_account_page()) {
                wp_enqueue_style('fast-courier-admin-select2', plugins_url("/views/libs/select2/css/select2.min.css", __FILE__));
                wp_enqueue_script('jquery');
                wp_enqueue_script('fast-courier-admin-select2', plugins_url("/views/libs/select2/js/select2.min.js", __FILE__));
            }
        }
    }
}


if (!function_exists('fc_check_is_woocommerce_active')) {
    /**
     * Check if Woo is active
     */
    function fc_check_is_woocommerce_active()
    {
        $isWoocommerceActivated = false;
        if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
            $isWoocommerceActivated = true;
        }
        return $isWoocommerceActivated;
    }
}

if (!function_exists('fc_create_plugin_database_table')) {
    /**
     * Creates Packages table
     */
    function fc_create_plugin_database_table()
    {
        global $wpdb, $fc_packages_table;

        if ($wpdb->get_var("show tables like '$fc_packages_table'") != $fc_packages_table) {
            try {
                $query = "CREATE TABLE `$fc_packages_table` ( `id` INT NOT NULL AUTO_INCREMENT , `package_name` VARCHAR(255) NOT NULL , `package_type` VARCHAR(100) NOT NULL , `dimensions` VARCHAR(255) NOT NULL , `outside_h` VARCHAR(20) NOT NULL , `outside_l` VARCHAR(20) NOT NULL , `outside_w` VARCHAR(20) NOT NULL , `is_default` INT(10) NOT NULL , PRIMARY KEY (`id`)) ENGINE = InnoDB;";
                $wpdb->query($query);
            } catch (\Exception $e) {
                echo esc_html($e->getMessage());
            }
        }
    }
    register_activation_hook(__FILE__, 'fc_create_plugin_database_table');
}

if (!function_exists('create_fc_woo_packages_table')) {
    /**
     * Create Product packages table
     */
    function create_fc_woo_packages_table()
    {
        global $wpdb, $woo_packages_table;

        if ($wpdb->get_var("show tables like '$woo_packages_table'") != $woo_packages_table) {
            try {
                $query = "CREATE TABLE `$woo_packages_table` (`id` INT NOT NULL AUTO_INCREMENT , `product_id` int(11) NOT NULL , `package_type` int(10) NOT NULL , `is_individual` int(10) NOT NULL , `weight` VARCHAR(50) NOT NULL , PRIMARY KEY (`id`)) ENGINE = InnoDB;";
                $wpdb->query($query);
            } catch (\Exception $e) {
                echo esc_html($e->getMessage());
            }
        }
    }
    register_activation_hook(__FILE__, 'create_fc_woo_packages_table');
}

if (!function_exists('fc_delete_prefilled_data')) {
    /**
     * Delete all prefilled data
     */
    function fc_delete_prefilled_data()
    {
        global $wpdb, $options_prefix, $fc_options;

        $wpdb->delete($fc_options, ['option_name' => $options_prefix . "access_token"]);
    }
    register_activation_hook(__FILE__, 'fc_delete_prefilled_data');
}

// Register custom cron schedule interval
function fc_custom_cron_interval($schedules)
{
    $schedules['every_15_minutes'] = array(
        'interval' => 900, // 15 minutes in seconds
        'display' => __('Every 15 minutes')
    );
    return $schedules;
}
add_filter('cron_schedules', 'fc_custom_cron_interval');


if (!function_exists('fc_cron_auto_process_order')) {
    add_action('fc_auto_process_cron', ['FastCourier\FastCourierOrders', 'cron_process_orders']);

    function fc_cron_auto_process_order()
    {
        if (!wp_next_scheduled('fc_auto_process_cron')) {
            wp_schedule_event(time(), 'every_15_minutes', 'fc_auto_process_cron');
        }
    }
    add_action('activate_cron_auto_process_order', 'fc_cron_auto_process_order');
}

if (!function_exists('fc_auto_process_order_deactivate_cron')) {
    function fc_auto_process_order_deactivate_cron()
    {
        wp_clear_scheduled_hook('fc_auto_process_cron');
    }

    register_deactivation_hook(__FILE__, 'fc_auto_process_order_deactivate_cron');
    add_action('deactivate_cron_auto_process_order', 'fc_auto_process_order_deactivate_cron');
}

if (!function_exists('create_fc_cron_logs')) {
    /**
     * Create cron logs table
     */
    function create_fc_cron_logs()
    {
        global $wpdb, $fc_cron_logs_table;
        if ($wpdb->get_var("show tables like '$fc_cron_logs_table'") != $fc_cron_logs_table) {
            try {
                $query = "CREATE TABLE IF NOT EXISTS `$fc_cron_logs_table` (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    name VARCHAR(150) DEFAULT NULL,
                    payload VARCHAR(255) DEFAULT NULL,
                    order_ids VARCHAR(150) DEFAULT NULL,
                    total_orders INT(9) DEFAULT NULL,
                    processed_orders INT(9) DEFAULT NULL,
                    collection_date TIMESTAMP NULL,
                    started_at TIMESTAMP NULL,
                    completed_at TIMESTAMP NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                );";

                $wpdb->query($query);
            } catch (\Exception $e) {
                echo esc_html($e->getMessage());
            }
        }
    }
    register_activation_hook(__FILE__, 'create_fc_cron_logs');
}

if (!function_exists('create_fc_web_hook_logs')) {
    /**
     * Create web hook logs table
     */
    function create_fc_web_hook_logs()
    {
        global $wpdb, $fc_web_hook_logs_table;
        if ($wpdb->get_var("show tables like '$fc_web_hook_logs_table'") != $fc_web_hook_logs_table) {
            try {
                $query = "CREATE TABLE IF NOT EXISTS `$fc_web_hook_logs_table` (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    payload TEXT DEFAULT NULL,
                    is_deleted TINYINT(1) DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                );";

                $wpdb->query($query);
            } catch (\Exception $e) {
                echo esc_html($e->getMessage());
            }
        }
    }
    register_activation_hook(__FILE__, 'create_fc_web_hook_logs');
}
