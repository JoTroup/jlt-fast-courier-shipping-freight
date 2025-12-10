<?php
global $table_prefix, $wpdb;

// setting environment
define('WP_ENV', 'PRODUCTION');

$name = 'Fast Courier - Shipping & Freight';
$options_prefix = 'fast_courier_';

$GLOBALS['fc_name'] = $name;
$GLOBALS['fc_packages_table'] = $table_prefix . 'fc_packages';
$GLOBALS['fc_options'] = $table_prefix . 'options';
$GLOBALS['prod_api_origin'] = "https://portal.fastcourier.com.au/";
$GLOBALS['api_origin'] = "https://portal-staging.fastcourier.com.au/";
$GLOBALS['prod_api'] = "https://portal.fastcourier.com.au/api/wp/";
$GLOBALS['api'] = "https://portal-staging.fastcourier.com.au/api/wp/";
$GLOBALS['test_key'] = $options_prefix . 'test_mode';
$GLOBALS['version'] = '5.2.0';

$GLOBALS['options_prefix'] = $options_prefix;
$GLOBALS['fc_woo_field'] = "_product_fc_packages";
$GLOBALS['woo_packages_table'] = $table_prefix . "product_fc_packages";
$GLOBALS['option_merchant_field'] = $options_prefix . 'merchant_details';

$GLOBALS['fc_cron_logs_table'] = $table_prefix . "fc_cron_logs";
$GLOBALS['fc_locations_table'] = $table_prefix . "fc_locations";
$GLOBALS['fc_web_hook_logs_table'] = $table_prefix . "fc_web_hook_logs";

// Default fallback shipping amount
define('FALLBACK_SHIPPING_AMOUNT', 50);

define('WP_API_URL', esc_js(admin_url('admin-ajax.php', 'relative')));

$GLOBALS['token'] = get_option($options_prefix . 'access_token');

$GLOBALS['slug'] = 'fast-courier';

$fc_holiday_file_name = 'fc_holidays.txt';
$GLOBALS['fc_holiday_file'] = $fc_holiday_file_name;
$GLOBALS['fc_holiday_file_path'] = __DIR__ . '/views/' . $fc_holiday_file_name;

$GLOBALS['fc_order_status'] = [
    'unprocessed' => ['key' => 'unprocessed', 'status' => 'Ready to Book', 'class' => 'disabled'],
    'sent-for-processing' => ['key' => 'sent-for-processing', 'status' => 'Processing', 'class' => 'disabled'],
    'label-created' => ['key' => 'label-created', 'status' => 'Label Created', 'class' => 'processing'],
    'label-failed' => ['key' => 'label-failed', 'status' => 'Label Failed', 'class' => 'processing'],
    'shipped' => ['key' => 'shipped', 'status' => 'Shipped', 'class' => 'success'],
    'order-for-later' => ['key' => 'order-for-later', 'status' => 'Order(s) for Later', 'class' => 'error'],
    'not-to-ship' => ['key' => 'not-to-ship', 'status' => 'Not to Ship', 'class' => 'error'],
    'draft' => ['key' => 'draft', 'status' => 'draft', 'class' => 'disabled'],
    'package_details_completed' => ['key' => 'package_details_completed', 'status' => 'Package Details Completed', 'class' => 'disabled'],
    'select_quote_completed' => ['key' => 'select_quote_completed', 'status' => 'Select Quote Completed', 'class' => 'disabled'],
    'shipment_details_completed' => ['key' => 'shipment_details_completed', 'status' => 'Shipment Details Completed', 'class' => 'disabled'],
    'additional_info_completed' => ['key' => 'additional_info_completed', 'status' => 'Additional Info Completed', 'class' => 'disabled'],
    'payment_failure' => ['key' => 'payment_failure', 'status' => 'Payment Failure', 'class' => 'error'],
    'payment_completed' => ['key' => 'payment_completed', 'status' => 'Payment Completed', 'class' => 'disabled'],
    'order_rejected' => ['key' => 'order_rejected', 'status' => 'Order Rejected', 'class' => 'error'],
    'order_completed' => ['key' => 'order_completed', 'status' => 'Order Completed', 'class' => 'processing'],
    'booked_for_collection' => ['key' => 'booked_for_collection', 'status' => 'Booked For Collection', 'class' => 'disabled'],
    'not_collected' => ['key' => 'not_collected', 'status' => 'Not Collected', 'class' => 'disabled'],
    'collected' => ['key' => 'collected', 'status' => 'Collected', 'class' => 'processing'],
    'in_transit' => ['key' => 'in_transit', 'status' => 'In Transit', 'class' => 'warning'],
    'out_for_delivery' => ['key' => 'out_for_delivery', 'status' => 'Out For Delivery', 'class' => 'warning'],
    'partially_delivered' => ['key' => 'partially_delivered', 'status' => 'Partially Delivered', 'class' => 'warning'],
    'proof_of_delivery' => ['key' => 'proof_of_delivery', 'status' => 'Proof Of Delivery', 'class' => 'processing'],
    'under_investigation' => ['key' => 'under_investigation', 'status' => 'Under Investigation', 'class' => 'warning'],
    'rebooked_for_collection' => ['key' => 'rebooked_for_collection', 'status' => 'Rebooked For Collection', 'class' => 'disabled'],
    'futile' => ['key' => 'futile', 'status' => 'Futile', 'class' => 'success'],
    'rejected' => ['key' => 'rejected', 'status' => 'Rejected', 'class' => 'error'],
    'driver_error' => ['key' => 'driver_error', 'status' => 'Driver Error', 'class' => 'error'],
    'no_scans' => ['key' => 'no_scans', 'status' => 'No Scans', 'class' => 'success'],
    'cancelled' => ['key' => 'cancelled', 'status' => 'Cancelled', 'class' => 'error'],
    'refunded' => ['key' => 'refunded', 'status' => 'Refunded', 'class' => 'warning'],
    'fulfilled' => ['key' => 'fulfilled', 'status' => 'Fulfilled', 'class' => 'processing'],
    'label:sent' => ['key' => 'label', 'status' => 'Label', 'class' => 'disabled'],
    'shipment_created' => ['key' => 'shipment_created', 'status' => 'Shipment Created', 'class' => 'disabled'],
    'delivered' => ['key' => 'delivered', 'status' => 'Delivered', 'class' => 'processing'],
    'missed_delivery' => ['key' => 'missed_delivery', 'status' => 'Missed Delivery', 'class' => 'error'],
    'processing' => ['key' => 'processing', 'status' => 'Processing', 'class' => 'success'],
    'hold' => ['key' => 'hold', 'status' => 'Hold', 'class' => 'success'],
    'processed' => ['key' => 'processed', 'status' => 'Processed', 'class' => 'success'],
    'flatrate' => ['key' => 'flatrate', 'status' => 'FlatRate', 'class' => 'success'],
    'payment_pending' => ['key' => 'payment_pending', 'status' => 'Payment Pending', 'class' => 'warning'],
    'order_failed' => ['key' => 'order_failed', 'status' => 'Order Failed', 'class' => 'error'],
];

$GLOBALS['bookingPreferences'] = [
    'free' => 'free_for_all_orders',
    'free_on_basket' => 'free_for_basket_value_total',
    'not_free' => 'shipping_cost_passed_on_to_customer'
];

$GLOBALS['shippingPreferences'] = [
    'show_price' => 'show_price_of_shipping_in_sub_total',
    'with_carrier_name' => 'show_shipping_price_with_carrier_name',
];

$GLOBALS['defaultProcessOrderAfterMinutes'] = 60;

$GLOBALS['defaultTailLiftKgs'] = 30;

function dd($data)
{
    echo "<pre>";
    print_r($data);
    echo "</pre>";
    exit;
}

$GLOBALS['fc_courier_validation'] = [
    'Toll' => [
        'first_name' => 19,
        'last_name' => 19,
        'company_name' => 40,
        'address' => 40,
        'phone_number' => 14,
    ],
    'Couriers Please' => [
        'first_name' => 15,
        'last_name' => 15,
        'company_name' => 19,
        'address' => 19,
        'phone_number' => 14,
    ],
    'TNT' => [
        'first_name' => 15,
        'last_name' => 14,
        'company_name' => 30,
        'address' => 30,
        'phone_number' => 13,
    ],
    'AlliedExpress' => [
        'address' => 100,
        'phone_number' => 14,
    ],
    'Star Track' => [
        'company_name' => 19,
        'address' => 40,
        'phone_number' => 14,
    ],
    'Aramex' => [
        'address' => 30,
        'phone_number' => 14,
    ],
    'Toll Palletised Express' => [
        'first_name' => 20,
        'last_name' => 20,
        'company_name' => 40,
        'address' => 30,
        'phone_number' => 10,
    ],
    'Bonds Couriers' => [
        'first_name' => 10,
        'last_name' => 10,
        'company_name' => 50,
        'address' => 30,
        'phone_number' => 14,
    ],
    'Capital Transport' => [
        'first_name' => 15,
        'last_name' => 15,
        'company_name' => 20,
        'address' => 30,
    ],
    'Northline Express' => [
        'first_name' => 14,
        'last_name' => 14,
        'company_name' => 50,
        'phone_number' => 10,
        'address' => 30,
    ],
    'MRL Global' => [
        'first_name' => 15,
        'last_name' => 14,
        'company_name' => 30,
        'address' => 30,
        'phone_number' => 13,
    ],
];

$GLOBALS['processAfterDays'] = [
    '0' => 'Next Available Date',
    '1' => 'Next 1 Day',
    '2' => 'Next 2 Days',
    '3' => 'Next 3 Days',
    '4' => 'Next 4 Days',
    '5' => 'Next 5 Days',
];
// Shipping statuses
define('SHIPPING_TYPE_FREE', 'free');
define('SHIPPING_TYPE_PAID', 'paid');
define('SHIPPING_TYPE_PARTIALLY_FREE', 'partially_free');

define('ORDER_TYPE_FLATRATE', 'flatrate');
define('ORDER_TYPE_FALLBACK', 'fallback');

$GLOBALS['shippingTypes'] = [
    'free' => 'Free',
    'paid' => 'Paid',
    'partially_free' => 'Partially Free'
];
