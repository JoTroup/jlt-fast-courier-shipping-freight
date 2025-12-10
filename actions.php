<?php

// Action to verify Token
add_action('wp_ajax_post_verify_token', ['FastCourier\FastCourierVerifyToken', 'verifyAccessToken']);

// Action to activate merchant and add merchant details
add_action('wp_ajax_post_activate_mechant', ['FastCourier\FastCourierVerifyToken', 'activeMerchant']);

// Action to activate merchant payment
add_action('wp_ajax_post_activate_mechant_payment', ['FastCourier\FastCourierVerifyToken', 'activeMerchantPayment']);

// Prefered Couriers
add_action('wp_ajax_post_active_couriers', ['FastCourier\FastCourierVerifyToken', 'activeCouriers']);

// Delete packages
add_action('wp_ajax_post_delete_packages', ['FastCourier\FastCourierVerifyToken', 'deletePackage']);

// Delete package from product
add_action('wp_ajax_post_delete_woo_packages', ['FastCourier\FastCourierVerifyToken', 'deleteWooPackage']);

if (isClassicMode()) {
    // Order Details
    add_action('woocommerce_checkout_update_order_review', ['FastCourier\FastCourierUpdateQuotes', 'checkingQuotes']);

    // Update FC charges in cart
    add_action('wp_ajax_nopriv_post_updateCartFee', 'updateCartFee');

    // Fetch Qoutes from FC
    add_action('woocommerce_cart_calculate_fees', ['FastCourier\FastCourierCart', 'add_fc_charges']);
}
// FC Fields in woocommerce
add_action('woocommerce_product_data_panels', 'fc_woocommerce_product_custom_fields');

// Saving packages for woocommerce products
add_action('woocommerce_process_product_meta', 'fc_woocommerce_product_custom_fields_save');

// Map Packages
add_action('wp_ajax_post_map_fc_packages', ['FastCourier\FastCourierManagePackages', 'mapFcPackages']);
add_action('wp_ajax_post_map_existing_to_fc_packages', ['FastCourier\FastCourierManagePackages', 'mapExistingDimensions']);

// Update Weight
add_action('wp_ajax_post_update_weight', ['FastCourier\FastCourierManagePackages', 'updateWeight']);

// allow eligible for shipping to a product
add_action('wp_ajax_post_allow_shipping', ['FastCourier\FastCourierProducts', 'updateAllowShipping']);
// Bulk assign eligible for shipping to the products
add_action('wp_ajax_post_bulk_allow_eligible_for_shipping', ['FastCourier\FastCourierProducts', 'updateBulkAllowShipping']);

// allow free shipping to a product
add_action('wp_ajax_post_allow_free_shipping', ['FastCourier\FastCourierProducts', 'updateAllowFreeShipping']);
// bulk allow free shipping to the product(s)
add_action('wp_ajax_post_bulk_allow_free_shipping', ['FastCourier\FastCourierProducts', 'updateBulkAllowFreeShipping']);

// bulk allow individual to the product(s)
add_action('wp_ajax_post_bulk_allow_individual', ['FastCourier\FastCourierProducts', 'updateBulkIndividual']);

// Sync Merchant Details
add_action('wp_ajax_sync_merchant_details', ['FastCourier\FastCourierVerifyToken', 'syncMerchantDetails']);

// Send orders for processing
add_action('wp_ajax_post_process_orders', ['FastCourier\FastCourierOrders', 'process_orders']);

// Hold orders
add_action('wp_ajax_post_hold_orders', ['FastCourier\FastCourierOrders', 'hold_orders']);

// Action to download zip from URLs
add_action('wp_ajax_post_download_zip', ['FastCourierBatches', 'downloadZip'], 10, 1);

// Order Processing Hook
add_action('rest_api_init', function () {
    register_rest_route('fastcourier', 'order-status-update', array(
        'methods'  => 'POST',
        'callback' => ['FastCourier\FastCourierOrders', 'update_order_status'],
        'permission_callback' => '__return_true'
    ));
});


//  V2 Actions
// Merchant Login 
add_action('wp_ajax_merchant_login', ['FastCourier\FCMerchantAuth', 'merchantLogin']);
add_action('wp_ajax_merchant_register', ['FastCourier\FCMerchantAuth', 'merchantRegistration']);
add_action('wp_ajax_add_payment_method', ['FastCourier\FCMerchantAuth', 'addPaymentMethod']);
add_action('wp_ajax_toggle_test_mode', ['FastCourier\FcMerchantAuth', 'toggleTestMode']);
add_action('wp_ajax_remove_payment_method', ['FastCourier\FcMerchantAuth', 'removePaymentMethod']);
add_action('wp_ajax_forgot_password', ['FastCourier\FcMerchantAuth', 'forgotPassword']);
add_action('wp_ajax_change_password', ['FastCourier\FcMerchantAuth', 'changePassword']);

// cron
add_action('wp_ajax_auto_process_orders', ['FastCourier\FastCourierOrders', 'cron_process_orders']);
add_action('wp_ajax_resync_all_orders', ['FastCourier\FastCourierOrders', 'resync_all_orders']);

// location actions
add_action('wp_ajax_add_location', ['FastCourier\FastCourierLocation', 'addLocation']);
add_action('wp_ajax_edit_location', ['FastCourier\FastCourierLocation', 'editLocation']);
add_action('wp_ajax_delete_location', ['FastCourier\FastCourierLocation', 'deleteLocation']);
add_action('wp_ajax_process_csv', ['FastCourier\FastCourierLocation', 'processCsv']);
add_action('wp_ajax_get_edit_locaton', ['FastCourier\FastCourierLocation', 'getEditLocation']);

// Assign location
add_action('wp_ajax_post_update_location', ['FastCourier\FastCourierLocation', 'assignLocation']);

// shipping box
add_action('wp_ajax_post_add_shipping_box', ['FastCourier\FastCourierManagePackages', 'addShippingBox']);

// import dimensions
add_action('wp_ajax_process_dimensions_csv', ['FastCourier\FastCourierManagePackages', 'processDimensionsCsv']);

// clear webhook logs
add_action('wp_ajax_delete_webhook_logs', ['FastCourier\FastCourierBatches', 'deleteWebhookLogs']);

// Wordpress Updates Version 2.0
add_action('rest_api_init', function () {
    register_rest_route('fastcourier', '/oauth-callback', array(
        'methods' => 'GET',
        'callback' => 'oauth_callback',
        'permission_callback' => '__return_true',
    ));
});

add_action('wp_ajax_get_client_credentials', 'get_client_credentials');
add_action('wp_ajax_nopriv_get_client_credentials', 'get_client_credentials'); // For non-logged-in users
