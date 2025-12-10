<?php

use FastCourier\FastCourierRequests;
use FastCourier\FCMerchantAuth;


if (!function_exists('fc_origin')) {
    function fc_origin()
    {
        $origin = parse_url(sanitize_url($_SERVER['HTTP_HOST']));

        return $origin['host'];
    }
}

if (!function_exists('my_plugin_force_session_initialization')) {
    function my_plugin_force_session_initialization()
    {
        if (class_exists('WooCommerce')) {
            // Ensure WooCommerce session handler is initialized
            WC()->session = new WC_Session_Handler();
            WC()->session->init();
        }
    }

    add_action('init', 'my_plugin_force_session_initialization');
}

if (!function_exists('force_early_jQuery_loading')) {
    function force_early_jQuery_loading()
    {
        if (is_admin()) {
            wp_enqueue_script('jquery');
            add_action('admin_head', function () {
                echo '<script>window.$ = window.jQuery;</script>';
            });
        } else {
            if (is_checkout() || is_account_page()) {
                wp_enqueue_script('jquery');
                add_action('wp_head', function () {
                    echo '<script>window.$ = window.jQuery;</script>';
                });
            }
        }
    }
    add_action('init',  'force_early_jQuery_loading', 1);
}

if (!function_exists('isClassicMode')) {
    function isClassicMode()
    {
        $mode = get_option('fast_courier_checkout_mode', 'classic');
        $classicMode = true;
        if ($mode === 'block') {
            $classicMode = false;
        }
        return $classicMode;
    }
}

if (!function_exists('isHposEnabled')) {
    function isHposEnabled()
    {
        return get_option('fast_courier_hpos_enabled', false);
    }
}

if (!function_exists('fc_merchant_details')) {
    function fc_merchant_details()
    {
        global $option_merchant_field;
        $merchantDetails = get_option($option_merchant_field);


        if (!$merchantDetails || !count(json_decode($merchantDetails, true))) {
            return false;
        } else {
            return json_decode($merchantDetails, true);
        }
    }
}

if (!function_exists('fc_is_unauthorized')) {
    function fc_is_unauthorized()
    {
        $_GET = fc_sanitize_data($_GET);
        global $fc_options, $options_prefix, $wpdb, $token, $slug;

        $response = FastCourierRequests::httpGet('verify');
        $data = $response['data'];

        if (isset($data['message']) && $data['message'] == 'ERROR_001') {
            $wpdb->delete($fc_options, ['option_name' => $options_prefix . "access_token"]);

            if (!isset($_GET['page'])) {
                return false;
            }

            if ($_GET['page'] == $slug) {
                return false;
            }

            header('location: ' . admin_url('admin.php?page=' . $slug));
        }

        if (isset($data['error']) && !$data['status'] && isset($data['error']) && $data['error'] == 'unauthenticated') {
            $wpdb->delete($fc_options, ['option_name' => $options_prefix . "access_token"]);
            if ($_GET['page'] == $slug) {
                return false;
            }

            header('location: ' . admin_url('admin.php?page=' . $slug));
        }

        return true;
    }
}

if (!function_exists('fc_custom_register_order_status')) {
    // Add new order status
    function fc_custom_register_order_status()
    {
        register_post_status('wc-pending-pickup', array(
            'label'                     => 'Pending Pickup',
            'public'                    => true,
            'exclude_from_search'       => false,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop('Pick up pending <span class="count">(%s)</span>', 'Pick up pending <span class="count">(%s)</span>'),
        ));
    }
    add_action('init', 'fc_custom_register_order_status');
}

if (!function_exists('fc_custom_add_order_status')) {
    // Add the new status to the list of order statuses
    function fc_custom_add_order_status($order_statuses)
    {
        $order_statuses['wc-pending-pickup'] = 'Pending Pickup';
        return $order_statuses;
    }
    add_filter('wc_order_statuses', 'fc_custom_add_order_status');
}

if (!function_exists('fc_is_configured')) {
    function fc_is_configured()
    {
        return get_option('is_fc_enabled');
    }
}

if (!function_exists('fc_create_log')) {
    function fc_create_log($data, $file_path = __DIR__ . '/data_log.txt')
    {
        // Check if the data is an array, if so, convert it to a string
        if (is_array($data)) {
            $data_string = print_r($data, true);
        } elseif (is_string($data)) {
            $data_string = $data;
        } else {
            // Handle other data types if necessary
            $data_string = var_export($data, true);
        }

        // Ensure that each log entry is on a new line
        $data_string .= PHP_EOL;

        // Check if the file exists, if not create it
        if (!file_exists($file_path)) {
            // Create the file
            $file = fopen($file_path, 'w');
            fclose($file);
        }

        // Append the data string to the file
        file_put_contents($file_path, $data_string, FILE_APPEND | LOCK_EX);
    }
}

// Woocommerce successful order is placed hook
if (!function_exists('add_fc_details_in_order')) {
    function add_fc_details_in_order($order_id)
    {
        fc_add_quotes_in_order_meta($order_id);
    }
    add_action('woocommerce_new_order', 'add_fc_details_in_order', 10, 1);
}


function fc_add_quotes_in_order_meta($order_id)
{
    $session = WC()->session;

    if (
        $session->get('packages_for_quote') ||
        $session->get('quote') ||
        $session->get('is_fallback_shipping')
    ) {

        global $fc_order_status;

        $freeShippig = $paidShipping = false;

        $order = wc_get_order($order_id);

        // checking the shipping type
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            if ($product && $product->is_virtual()) {
                $freeShippig = true;
            }
        }

        $qoutes = $session->get('quote') ? json_decode($session->get('quote')) : [];
        foreach ($qoutes as $val) {
            if ($val->shipping_type == SHIPPING_TYPE_FREE) {
                $freeShippig = true;
            } elseif ($val->shipping_type == SHIPPING_TYPE_PAID) {
                $paidShipping = true;
            }
        }
        // set shipping type and add to meta
        $shippingType = '';
        if ($freeShippig && $paidShipping) {
            $shippingType = SHIPPING_TYPE_PARTIALLY_FREE;
        } elseif ($freeShippig) {
            $shippingType = SHIPPING_TYPE_FREE;
        } elseif ($paidShipping) {
            $shippingType = SHIPPING_TYPE_PAID;
        }


        if ($session->get('packages_for_quote')) {
            $order->update_meta_data('fc_order_packages', wp_json_encode(sanitize_text_field($session->get('packages_for_quote'))));
        }

        if ($session->get('quote')) {
            $order->update_meta_data('fc_order_quote', sanitize_text_field(json_encode($session->get('quote'))));
        }

        $quoteDataJson = json_decode($session->get('quote'));

        $order->update_meta_data('is_fc_order', true);
        $order->update_meta_data('fc_order_shipping_type', $shippingType);
        $order->update_meta_data('fc_order_id', $order->get_id());
        $order->update_meta_data('fc_status', $fc_order_status['unprocessed']['key']);

        if ($session->get('atl')) {
            $order->update_meta_data('fc_atl', $session->get('atl'));
        }

        if (!$session->get('is_fallback_shipping')) {
            if ($session->get('is_allow_shipping') && !$session->get('is_local_pickup')) {
                $order->update_meta_data('fc_status', $fc_order_status['unprocessed']['key']);
            } else {
                $order->update_meta_data('fc_status', $fc_order_status['processed']['key']);
            }
        }

        // If Its a Flat Rate Order
        if ($quoteDataJson[0]->order_type == ORDER_TYPE_FLATRATE && !$session->get('is_fallback_shipping')) {
            $order->update_meta_data('fc_status', $fc_order_status['flatrate']['key']);
        }

        $isFallback = false;
        if ($session->get('is_fallback_shipping')) {
            $isFallback = true;
            $order->update_meta_data('is_fallbacked', 1);
        } else {
            $order->update_meta_data('is_fallbacked', 0);
        }

        $decodedOrder = json_decode($order);
        $decodedQuoteData = json_decode($session->get('quote'));

        $billingDetails = $decodedQuoteData[0]->destination;
        $billingFirstName = $billingDetails->billing_first_name;
        $billingLastName = $billingDetails->billing_last_name;
        $billingPhone = $billingDetails->billing_phone;
        $billingEmail = $billingDetails->billing_email;

        foreach ($decodedQuoteData as $quoteData) {
            $hashId = null;
            if ($isFallback && isset($quoteData) && isset($quoteData->order_id)) {
                $hashId = $quoteData->order_id ?? null;
            } elseif (isset($quoteData) && isset($quoteData->order_type)) {
                $hashId = isset($quoteData->order_id) ? $quoteData->order_id : null;
            } else {
                $hashId = isset($quoteData->data->orderHashId) ? $quoteData->data->orderHashId : null;
            }
            update_order_on_portal([
                'hash_id' => $hashId,
                'order_id' => $decodedOrder->id,
                'destination_email' => $billingEmail,
                'destination_phone' => $billingPhone,
                'destination_first_name' => $billingFirstName,
                'destination_last_name' => $billingLastName,
                'wp_checkout_type' => isClassicMode() ? 'classic' : 'block',
            ]);
        }

        $order->save();
        $session->__unset('packages_for_quote');
        $session->__unset('is_fallback_shipping');
        $session->__unset('quote');
        $session->__unset('atl');
        $session->__unset('is_allow_shipping');
        $session->__unset('is_local_pickup');
        $session->__unset('fc_calculated_shipping_cost');
        $session->__unset('fc_last_shipping_hash');
    }
}

if (!function_exists('fc_product_settings_tabs')) {
    add_filter('woocommerce_product_data_tabs', 'fc_product_settings_tabs');
    function fc_product_settings_tabs($tabs)
    {
        $tabs['fc_quotes'] = array(
            'label'    => 'FC Shipping Configuration',
            'target'   => 'fcPackagesSelection',
            'priority' => 9999,
        );
        return $tabs;
    }
}

if (!function_exists('fc_woocommerce_product_custom_fields')) {
    /**
     * FC Quotes fields on products page
     */
    function fc_woocommerce_product_custom_fields()
    {
        if (!fc_is_unauthorized()) {
            include_once('views/admin/views/notice-activation.php');
            return;
        }

        include_once('views/views/wc-package-config.php');
    }
}

if (!function_exists('fc_woocommerce_product_custom_fields_save')) {
    /**
     * Action to assign packages to a product
     *
     * @param int $post_id The ID of the post being saved
     */
    function fc_woocommerce_product_custom_fields_save($post_id)
    {
        try {
            // Create a new WC_Product instance
            $product = wc_get_product($post_id);

            // Delete the fc meta fields
            delete_fc_meta($product);

            // Check if the product is a variable product
            if (is_a($product, 'WC_Product')) {
                // Get variations shipping configuration from POST data
                $variationsShippingConfiguration = $_POST['vData'] ?? [];
                if (!empty($variationsShippingConfiguration) && $product->is_type('variable')) {
                    // Get the variations
                    fc_save_variations_shipping_configuration($variationsShippingConfiguration);
                }
            }

            // Sanitize POST data
            $postData = fc_sanitize_data($_POST);

            // Loop through POST data and update product meta data
            foreach ($postData as $key => $value) {
                // Check if the key ends with a number (e.g., "fc_length_1")
                if (preg_match('/^(\w+)(\d+)$/', $key, $matches)) {
                    // Get the base key (e.g., "fc_length_", "fc_width_")
                    $base_key = $matches[1];
                    // Check if the base key is one of "fc_length_," "fc_width_," etc
                    if (in_array($base_key, array('fc_length_', 'fc_width_', 'fc_height_', 'fc_weight_', 'fc_package_type_', 'fc_is_individual_'))) {
                        // Save the data with dynamic postfix as metadata
                        $product->update_meta_data($key, $value);
                    }
                }
            }

            // Update specific meta data fields
            $product->update_meta_data('fc_height', $postData['fc_height']);
            $product->update_meta_data('fc_width', $postData['fc_width']);
            $product->update_meta_data('fc_length', $postData['fc_length']);
            $product->update_meta_data('fc_weight', $postData['fc_weight']);
            $product->update_meta_data('fc_is_individual', isset($postData['fc_is_individual']) ? $postData['fc_is_individual'] : 0);
            $product->update_meta_data('fc_package_type', $postData['fc_package_type']);
            $product->update_meta_data('fc_location_type', $postData['fc_location_type']);
            $product->update_meta_data('fc_location', $postData['fc_location']);
            $product->update_meta_data('fc_allow_shipping', $postData['fc_allow_shipping']);
            $product->update_meta_data('fc_allow_free_shipping', $postData['fc_allow_free_shipping']);

            // Save the product
            $product->save();
        } catch (\Exception $e) {
            // Handle exceptions
            die($e->getMessage());
        }
    }
}

if (!function_exists('fc_save_variations_shipping_configuration')) {
    /**
     * Save variations' shipping configuration.
     *
     * @param array $variationsData The variations data.
     */
    function fc_save_variations_shipping_configuration($variationsData)
    {
        try {
            foreach ($variationsData as $key => $value) {
                $variableProduct = wc_get_product($key);

                // Delete the fc meta fields
                delete_fc_meta($variableProduct);

                foreach ($value as $k => $val) {
                    // Check if the key ends with a number (e.g., "fc_length_1")
                    if (preg_match('/^(\w+)(\d+)$/', $k, $matches)) {
                        // Get the base key (e.g., "fc_length_", "fc_width_")
                        $base_key = $matches[1];

                        // Check if the base key is one of "fc_length_," "fc_width_," etc
                        if (in_array($base_key, array('fc_length_', 'fc_width_', 'fc_height_', 'fc_weight_', 'fc_package_type_', 'fc_is_individual_'))) {
                            // Save the data with dynamic postfix as metadata
                            update_post_meta($key, $k, $val);
                        }
                    }
                }

                // Update meta data
                update_post_meta($key, 'fc_height', $value['fc_height']);
                update_post_meta($key, 'fc_width', $value['fc_width']);
                update_post_meta($key, 'fc_length', $value['fc_length']);
                update_post_meta($key, 'fc_weight', $value['fc_weight']);
                update_post_meta($key, 'fc_is_individual', isset($value['fc_is_individual']) ? $value['fc_is_individual'] : 0);
                update_post_meta($key, 'fc_package_type', $value['fc_package_type']);
                update_post_meta($key, 'fc_location_type', $value['fc_location_type']);
                update_post_meta($key, 'fc_location', $value['fc_location']);
            }
        } catch (\Exception $e) {
            // Handle exceptions
            die($e->getMessage());
        }
    }
}

if (!function_exists('delete_fc_meta')) {
    /**
     * Delete FC meta data from a product.
     *
     * @param WP_Product $product The product object.
     */
    function delete_fc_meta($product)
    {
        try {
            $productId = $product->get_id();
            // Get all meta data for the product
            $all_meta = get_post_meta($productId);

            // Loop through each meta data
            foreach ($all_meta as $meta_key => $meta_value) {
                // Check if the meta key starts with "fc_" except for "fc_location_type" and "fc_location"
                if (strpos($meta_key, "fc_") === 0 && !in_array($meta_key, array('fc_location_type', 'fc_location', 'fc_allow_free_shipping', 'fc_allow_shipping'))) {
                    // Delete the meta data
                    delete_post_meta($productId, $meta_key);
                }
            }
        } catch (\Exception $e) {
            die($e->getMessage());
        }
    }
}
if (!function_exists('fc_order_status_chip')) {
    function fc_order_status_chip($status)
    {
        if ($status == '' || !isset($status) || empty($status)) {
            return;
        }
        global $fc_order_status;
        $data = $fc_order_status[$status];

        include('views/views/common/status-chip.php');
    }
}

if (!function_exists('fc_handle_custom_query_var')) {
    function fc_handle_custom_query_var($query, $query_vars)
    {
        if (!empty($query_vars['is_fc_order'])) {
            $query['meta_query'][] = array(
                'key' => 'is_fc_order',
                'value' => esc_attr($query_vars['is_fc_order']),
            );
        }

        if (isset($query_vars['is_fallbacked'])) {
            $query['meta_query'][] = array(
                'key' => 'is_fallbacked',
                'value' => esc_attr($query_vars['is_fallbacked']),
                'compare' => '='
            );
        }
        // Filter for shipping type
        if (isset($query_vars['fc_order_shipping_type'])) {
            if ($query_vars['fc_order_shipping_type'] == SHIPPING_TYPE_PAID) {
                // For paid orders, old orders have no fc_order_shipping_type key
                $query['meta_query'][] = array(
                    'relation' => 'OR',
                    array(
                        'key'     => 'fc_order_shipping_type',
                        'compare' => 'NOT EXISTS',
                    ),
                    array(
                        'key'   => 'fc_order_shipping_type',
                        'value' => $query_vars['fc_order_shipping_type'],
                    ),
                );
            } else {
                $query['meta_query'][] = array(
                    'key' => 'fc_order_shipping_type',
                    'value' => $query_vars['fc_order_shipping_type'],
                    'compare' => '='
                );
            }
        }

        if (isset($query_vars['fc_is_reprocessable'])) {
            $query['meta_query'][] = array(
                'key' => 'fc_is_reprocessable',
                'value' => esc_attr($query_vars['fc_is_reprocessable']),
            );
        }

        if (!empty($query_vars['fc_order_id'])) {
            $query['meta_query'][] = array(
                'key' => 'fc_order_id',
                'value' => esc_attr($query_vars['fc_order_id']),
            );
        }

        if (!is_account_page()) {
            global $fc_order_status;
            $getData = fc_sanitize_data($_GET);
            $orderStatus = isset($getData['fc_order_status']) ? $getData['fc_order_status'] : $fc_order_status['unprocessed']['key'];
            $comparison = '=';


            if (isset($query_vars['fc_order_status'])) {
                if ($query_vars['fc_order_status'] == 'all') {
                    $query['meta_query'][] = array(
                        'key' => 'fc_status',
                        'value' => $fc_order_status['unprocessed']['key'],
                        'compare' => '!=',
                    );
                } else if ($query_vars['fc_order_status'] == 'processed') {

                    if (isset($getData['fc_order_status']) && $getData['fc_order_status'] != 'all') {
                        $query['meta_query'][] = array(
                            'key' => 'fc_status',
                            'value' => esc_attr($orderStatus),
                            'compare' => $comparison,
                        );
                    } else {
                        $query['meta_query'][] = array(
                            'key' => 'fc_status',
                            'value' => array(
                                $fc_order_status['unprocessed']['key'],
                                $fc_order_status['hold']['key'],
                                $fc_order_status['payment_pending']['key'],
                                $fc_order_status['cancelled']['key'],
                                $fc_order_status['refunded']['key'],
                                $fc_order_status['order_failed']['key'],
                                $fc_order_status['draft']['key']
                            ),
                            'compare' => 'NOT IN',
                        );
                    }
                } else if ($query_vars['fc_order_status'] == 'other') {
                    $query['meta_query'][] = array(
                        'key' => 'fc_status',
                        'value' => array(
                            $fc_order_status['payment_pending']['key'],
                            $fc_order_status['cancelled']['key'],
                            $fc_order_status['refunded']['key'],
                            $fc_order_status['order_failed']['key'],
                            $fc_order_status['draft']['key']
                        ),
                        'compare' => 'IN',
                    );
                } else {
                    $query['meta_query'][] = array(
                        'key' => 'fc_status',
                        'value' => $query_vars['fc_order_status'],
                        'compare' => '=',
                    );
                }
            } else {
                if (isset($getData['page']) && $getData['page'] == 'fast-courier-processed-orders') {
                    $orderStatus = isset($getData['fc_order_status']) ? $getData['fc_order_status'] : 'unprocessed';
                    $comparison = '!=';
                    if (isset($getData['fc_order_status'])) {
                        if ($getData['fc_order_status'] == 'all') {
                            $orderStatus = 'unprocessed';
                        } else {
                            $comparison = '=';
                        }
                    }
                }
                if ($orderStatus != 'all') {
                    $query['meta_query'][] = array(
                        'key' => 'fc_status',
                        'value' => esc_attr($orderStatus),
                        'compare' => $comparison,
                    );
                }
            }
        }

        return $query;
    }
    add_filter('woocommerce_order_data_store_cpt_get_orders_query', 'fc_handle_custom_query_var', 10, 2);
}

if (!function_exists('fc_handle_custom_query_var')) {
    function fc_handle_custom_query_var($data)
    {
        unset($data['page']);
        $filters = [
            'limit' => 10,
            'orderby' => 'date',
            'order' => 'DESC',
            'page' => isset($data['page_no']) ? $data['page_no'] : 1,
            'is_fc_order' => true,
            'paginate' => true,
        ];
        foreach ($data as $key => $filter) {
            if (!empty($data[$key])) {
                $filters[$key] = $filter;
            }
        }

        return $filters;
    }
}

if (!function_exists('fc_format_order_filters')) {
    function fc_format_order_filters($data)
    {
        unset($data['page']);
        $filters = [
            'limit' => 10,
            'orderby' => 'date',
            'order' => 'DESC',
            'page' => isset($data['page_no']) ? $data['page_no'] : 1,
            'is_fc_order' => true,
            'paginate' => true,
        ];
        foreach ($data as $key => $filter) {
            if (!empty($data[$key])) {
                $filters[$key] = $filter;
            }
        }

        return $filters;
    }
}

if (!function_exists('fc_pagination')) {
    function fc_pagination($pages)
    {
        include_once('views/views/common/pagination.php');
    }
}

if (!function_exists('fc_protect_order_meta')) {
    add_filter('is_protected_meta', 'fc_protect_order_meta', 10, 2);
    function fc_protect_order_meta($protected, $meta_key)
    {
        return str_contains($meta_key, 'fc') || str_contains($meta_key, 'order_quotes') ? true : $protected;
    }
}


add_filter('http_request_timeout', function () {
    return 60;
});

if (!function_exists('fc_disable_shipping_calc_on_cart')) {
    function fc_disable_shipping_calc_on_cart($show_shipping)
    {
        return false;
        if (is_cart() || is_checkout()) {
        }
        return $show_shipping;
    }
    if (isClassicMode()) {
        add_filter('woocommerce_cart_ready_to_calc_shipping', 'fc_disable_shipping_calc_on_cart', 99);
    }
}

if (!function_exists('fc_orders_data_customer_account')) {
    function fc_orders_data_customer_account($columns = array())
    {
        $columns['fc-status'] = 'Shipping Status';
        $columns['fc-tracking-url'] = 'Tracking URL';
        return $columns;
    }
    add_filter('woocommerce_my_account_my_orders_columns', 'fc_orders_data_customer_account');
}

if (!function_exists('fc_add_orders_column_status_cusomter')) {
    add_action('woocommerce_my_account_my_orders_column_fc-status', 'fc_add_orders_column_status_cusomter');
    function fc_add_orders_column_status_cusomter($order)
    {
        echo esc_html(fc_order_status_chip($order->get_meta('fc_status')));
    }
}

if (!function_exists('fc_add_orders_column_tracking_url_customer')) {
    add_action('woocommerce_my_account_my_orders_column_fc-tracking-url', 'fc_add_orders_column_tracking_url_customer');
    function fc_add_orders_column_tracking_url_customer($order)
    {
        if ($order->get_meta('fc_tracking_url')) {
            echo "<a href='" . esc_url($order->get_meta('fc_tracking_url')) . "' target='_blank'>Click Here</a>";
        } else {
            echo '--';
        }
    }
}

if (!function_exists('fc_override_checkout_fields')) {
    if (isClassicMode()) {
        add_filter('woocommerce_checkout_fields', 'fc_override_checkout_fields');
    }
    function fc_override_checkout_fields($fields)
    {
        $fields['billing']['fc_billing_suburb']['priority'] = 60;

        $fields['billing']['billing_state']['class'] = ['fc-d-none'];
        $fields['billing']['billing_city']['class'] = ['fc-d-none'];
        $fields['billing']['billing_postcode']['class'] = ['fc-d-none'];

        $merchantDetails = fc_merchant_details();
        // if isAuthorityToLeave is 1 from merchant, add as hidden input
        if (@$merchantDetails['isAuthorityToLeave'] == '1') {
            $fields['billing']['fc_atl_checkbox'] = array(
                'type' => 'hidden',
                'priority' => 66,
                'default' => '1'
            );
        } else {
            // Add checkbox field
            $fields['billing']['fc_atl_checkbox'] = array(
                'type' => 'checkbox',
                'name' => 'atl',
                'label' => 'Authority to Leave',
                'class' => array('atl-selection'),
                'priority' => 66,
                'value' => 1,
            );
        }

        return $fields;
    }
}


if (!function_exists('fc_custom_shipping_price_html_modification')) {
    /**
     * Add a condition to trigger the modification only when needed.
     *
     * This function will add a filter to the WooCommerce cart totals
     * fee HTML only when the user is on the cart or checkout page.
     */
    function fc_custom_shipping_price_html_modification()
    {
        // Add a condition to trigger the modification only when needed
        if (is_cart() || is_checkout()) {
            // Add a filter to the WooCommerce cart totals fee HTML
            add_filter('woocommerce_cart_totals_fee_html', 'custom_shipping_fee_html', 10, 2);
        }
    }
}

if (!function_exists('custom_shipping_fee_html')) {
    /**
     * Modify the HTML of the shipping fee when it's label is "Shipping".
     *
     * @param string $fee_html The original HTML of the fee.
     * @param object $fee The fee object.
     *
     * @return string The modified HTML of the fee.
     */
    function custom_shipping_fee_html($fee_html, $fee)
    {
        // Check if the fee label is "Shipping"
        if ($fee->name === 'Shipping') {
            // Modify the fee HTML as desired
            $fee_html = '<div class="shipping-container"><div class="shipping-label">' . esc_html__('N/A', 'woocommerce') . ' </div><div class="tooltip">' . esc_html__('Item unavailable for shipping, please contact us to get a shipping quote.', 'woocommerce') . '</div></div>';
        }

        return $fee_html;
    }
}

if (!function_exists('fc_override_billing_fields')) {
    if (isClassicMode()) {
        add_filter('woocommerce_billing_fields', 'fc_override_billing_fields');
    }
    function fc_override_billing_fields($fields)
    {
        $fields['fc_billing_suburb'] = array(
            'type' => 'text',
            'label' => __('Suburb, Postcode, State', 'woocommerce'),
            'placeholder' => __('Suburb, Postcode, State', 'woocommerce'),
            'required' => true,
            'class' => array('fc-selected-suburb'),
            'clear' => true,
        );

        $fields['billing_state']['class'] = ['fc-d-none'];
        $fields['billing_city']['class'] = ['fc-d-none'];
        $fields['billing_postcode']['class'] = ['fc-d-none'];

        return $fields;
    }
}
if (!function_exists('fc_override_shipping_fields')) {
    if (isClassicMode()) {
        add_filter('woocommerce_shipping_fields', 'fc_override_shipping_fields');
    }
    function fc_override_shipping_fields($fields)
    {
        $fields['fc_shipping_suburb'] = array(
            'type' => 'text',
            'label' => __('Suburb, Postcode, State', 'woocommerce'),
            'placeholder' => __('Suburb, Postcode, State', 'woocommerce'),
            'required' => true,
            'class' => array('fc-selected-suburb'),
            'clear' => true,
        );

        $fields['shipping_state']['class'] = ['fc-d-none'];
        $fields['shipping_city']['class'] = ['fc-d-none'];
        $fields['shipping_postcode']['class'] = ['fc-d-none'];

        return $fields;
    }
}

if (!function_exists('fc_sanitize_data')) {
    function fc_sanitize_data($data)
    {
        $sanitized = [];
        foreach ($data as $key => $value) {
            if (is_array($data[$key])) {
                $childArray = [];
                foreach ($value as $ckey => $cvalue) {
                    $childArray[$ckey] = sanitize_text_field($cvalue);
                }
                $sanitized[$key] = $childArray;
            } else {
                $sanitized[$key] = sanitize_text_field($value);
            }
        }

        return $sanitized;
    }
}

if (!function_exists('fc_check_value_exists_in_postcode_range')) {
    /**
     * Checks if a given postcode exists within a range of postcodes.
     *
     * @param string $postcode The postcode to check.
     * @param array $postcodeRange An array of postcode ranges to check against.
     * @return bool True if the postcode exists within any of the ranges, false otherwise.
     */
    function fc_check_value_exists_in_postcode_range($postcode, $postcodeRange)
    {
        // Flag to store if the postcode exists within any of the ranges
        $existsInRange = false;
        if ($postcode && !empty($postcodeRange)) {
            // Iterate through each range
            foreach ($postcodeRange as $range) {
                // Split the range into start and end postcodes
                list($start, $end) = explode('...', $range);

                // Check if the postcode is within the range
                if ($postcode >= $start && $postcode <= $end) {
                    // Set the flag to true and exit the loop
                    $existsInRange = true;
                    break;
                }
            }
        }

        // Return the flag indicating if the postcode exists within any of the ranges
        return $existsInRange;
    }
}

if (!function_exists('fc_add_local_pickup_checkbox')) {
    if (isClassicMode()) {
        add_action('woocommerce_review_order_before_payment', 'fc_add_local_pickup_checkbox', 10);
    }
    /**
     * Adds a local pickup checkbox to the WooCommerce checkout page.
     */
    function fc_add_local_pickup_checkbox()
    {
?>
        <script>
            var localPickupPostcodes, localPickupCountry, localPickupState, localPickupPostcodeRange;
        </script>
        <?php
        if (!empty(WC_Shipping_Zones::get_zones())) {
            // Initialize variables for storing local pickup details
            $local_pickup_postcodes = $local_pickup_country = $local_pickup_state = $local_pickup_postcode_range = [];
            $session = WC()->session;
            $session->__unset('fc_local_pickup_zone_found');
            // Loop through shipping zones
            foreach (WC_Shipping_Zones::get_zones() as $zone) {
                // Check if the zone has a method for Local Pickup
                if (isset($zone['shipping_methods']) && is_array($zone['shipping_methods'])) {
                    foreach ($zone['shipping_methods'] as $shipping_method) {
                        if ($shipping_method instanceof WC_Shipping_Local_Pickup) {
                            // Get the local pickup label
                            $localPickupLabel = $shipping_method->title;

                            $session->set('fc_local_pickup_zone_found', true);

                            // Get the zone instance and its locations
                            $zone_instance = new WC_Shipping_Zone($zone['zone_id']);
                            $zone_locations = $zone_instance->get_zone_locations();

                            // Process each zone location
                            foreach ($zone_locations as $zone_location) {
                                $zone_code = $zone_location->code;
                                $zone_type = $zone_location->type;

                                // Check the type of zone location
                                if ($zone_type === 'postcode') {
                                    // Check if the range is added in local pickup zone
                                    if (strpos($zone_code, ".") !== false) {
                                        $local_pickup_postcode_range[] = $zone_code;
                                    } else {
                                        $local_pickup_postcodes[] = $zone_code;
                                    }
                                }
                                if ($zone_type === 'country') {
                                    $local_pickup_country[] = $zone_code;
                                }
                                if ($zone_type === 'state') {
                                    $local_pickup_state[] = $zone_code;
                                }
                            }
                        }
                    }
                }
            }

            $user_id = wp_get_current_user()->data->ID ?? null;
            $current_user = new WC_Customer($user_id);

            $localPickupClass = 'fc-d-none';
            $localPickupDisable = 'disabled';

            if (!empty($local_pickup_postcodes)) {
                if (in_array($current_user->get_billing_postcode(), $local_pickup_postcodes)) {
                    $localPickupClass = $localPickupDisable = '';
                }
        ?>
                <script>
                    var localPickupPostcodes = <?php echo json_encode($local_pickup_postcodes) ?>;
                </script>
            <?php
            }
            if (!empty($local_pickup_country)) {
                if (in_array($current_user->get_billing_country(), $local_pickup_country)) {
                    $localPickupClass = $localPickupDisable = '';
                }
            ?>
                <script>
                    var localPickupCountry = <?php echo json_encode($local_pickup_country) ?>;
                </script>
            <?php
            }
            if (!empty($local_pickup_state)) {
                if (in_array($current_user->get_billing_country() . ':' . $current_user->get_billing_state(), $local_pickup_state)) {
                    $localPickupClass = $localPickupDisable = '';
                }
            ?>
                <script>
                    var localPickupState = <?php echo json_encode($local_pickup_state) ?>;
                </script>
            <?php
            }
            if (!empty($local_pickup_postcode_range)) {
                if (fc_check_value_exists_in_postcode_range($current_user->get_billing_postcode(), $local_pickup_postcode_range)) {
                    $localPickupClass = $localPickupDisable = '';
                }
            ?>
                <script>
                    var localPickupPostcodeRange = <?php echo json_encode($local_pickup_postcode_range) ?>;
                </script>
            <?php
            }
            // Display the local pickup checkbox if there are available locations
            echo '<div class="fc-local-pickup ' . $localPickupClass . '" style="margin-top: 20px"><ul style="list-style-type: none; padding: 0;"><li><label><input type="checkbox" name="fc_local_pickup" id="fc_local_pickup" ' . $localPickupDisable . '> ' . $localPickupLabel . '</label></span></li></ul></div>';
        } else {
            // Get the local pickup instance from the plugin settings
            $local_pickup_instance = get_option('woocommerce_pickup_location_settings', []);

            // Check if local pickup is enabled
            if (isset($local_pickup_instance) && !empty($local_pickup_instance) && $local_pickup_instance['enabled'] == 'yes') {
                // Display the local pickup checkbox
                echo '<div style="margin-top: 20px"><ul style="list-style-type: none; padding: 0;"><li><label><input type="checkbox" name="fc_local_pickup" id="fc_local_pickup"> ' . $local_pickup_instance['title'] . '</label></span></li></ul></div>';
            }
        }
    }
}

if (!function_exists('fc_add_jscript_checkout')) {
    /**
     * Adds JavaScript code to the checkout page.
     */
    function fc_add_jscript_checkout()
    {
        if (is_checkout() || is_account_page()) {
            global $token, $api, $prod_api;
            $user_id = wp_get_current_user()->data->ID;
            $current_user = new WC_Customer($user_id);

            $subursApi = is_test_mode_active() ? $api : $prod_api; ?>

            <script>
                // Function to check if a value falls within any of the specified ranges
                function isInRange(value, ranges) {
                    if (!value && !ranges) {
                        return false;
                    }
                    return Array.isArray(ranges) && ranges.some(range => {
                        var [start, end] = range.split('...').map(Number);
                        return value >= start && value <= end;
                    });
                }
                // Wait for the document to be fully loaded
                window.addEventListener("load", function() {
                    // Find the element with ID #fc_billing_suburb
                    var billingSuburb = document.getElementById('fc_billing_suburb');
                    var shippingSuburb = document.getElementById('fc_shipping_suburb');

                    var actualWidth = document.querySelector('#fc_billing_suburb').offsetWidth;

                    // Check if the element exists
                    if (billingSuburb) {
                        // Create a new ul element with the specified classes
                        let ulElement = document.createElement('ul');
                        ulElement.style.setProperty('width', actualWidth + 'px');
                        ulElement.className = 'wp-ajax-suburbs fc-suburb-list form-control';
                        // Insert the new ul element as a sibling after the billingSuburb element
                        billingSuburb.parentNode.insertBefore(ulElement, billingSuburb.nextSibling);
                    }
                    if (shippingSuburb) {
                        // Create a new ul element with the specified classes
                        let ulElement = document.createElement('ul');
                        ulElement.style.setProperty('width', actualWidth + 'px');
                        ulElement.className = 'wp-ajax-suburbs fc-suburb-list form-control';
                        // Insert the new ul element as a sibling after the shippingSuburb element
                        shippingSuburb.parentNode.insertBefore(ulElement, shippingSuburb.nextSibling);
                    }
                });
            </script>

            <?php
            // Add JavaScript code for billing details
            if ($current_user->get_billing_postcode()) { ?>
                <script>
                    jQuery("#fc_billing_suburb").val('<?php echo esc_html($current_user->get_billing_city()) . ', ' . esc_html($current_user->get_billing_postcode()) . ' (' . esc_html($current_user->get_billing_state()) . ')' ?>');
                    jQuery('#billing_state').val('<?php echo esc_html($current_user->get_billing_state()) ?>');
                    jQuery('#billing_postcode').val('<?php echo esc_html($current_user->get_billing_postcode()) ?>');
                    jQuery('#billing_city').val('<?php echo esc_html($current_user->get_billing_city()) ?>');
                </script>
            <?php }
            if ($current_user->get_shipping_postcode()) { ?>
                <script>
                    jQuery("#fc_shipping_suburb").val('<?php echo esc_html($current_user->get_shipping_city()) . ', ' . esc_html($current_user->get_shipping_postcode()) . ' (' . esc_html($current_user->get_shipping_state()) . ')' ?>');
                    jQuery('#shipping_state').val('<?php echo esc_html($current_user->get_shipping_state()) ?>');
                    jQuery('#shipping_postcode').val('<?php echo esc_html($current_user->get_shipping_postcode()) ?>');
                    jQuery('#shipping_city').val('<?php echo esc_html($current_user->get_shipping_city()) ?>');
                </script>
            <?php } ?>

            <style>
                .fc-d-none {
                    display: none !important;
                }

                label.checkbox {
                    display: flex;
                    align-items: center;
                }

                label.checkbox br {
                    display: none;
                }

                label.checkbox input[type="checkbox"] {
                    margin-right: 5px;
                    /* Adjust spacing as needed */
                }

                /* Container for the shipping label and tooltip */
                .shipping-container {
                    position: relative;
                    display: inline-block;
                    width: 100%;
                }

                /* Styling for the shipping label */
                .shipping-label {
                    cursor: pointer;
                    transition: background-color 0.3s;
                }

                /* Tooltip styling */
                .tooltip {
                    position: absolute;
                    top: 100%;
                    background-color: #555;
                    color: #fff;
                    font-size: 14px;
                    padding: 5px;
                    border-radius: 5px;
                    opacity: 0;
                    transition: opacity 0.3s;
                }

                /* Show tooltip on hover */
                .shipping-container:hover .tooltip {
                    opacity: 1;
                }

                /* Suburb dropdown css started */
                .fc-suburb-dropdown {
                    position: relative;
                    display: inline-block;
                }

                .fc-selected-suburb {
                    background-color: #fff;
                    cursor: pointer;
                }

                .fc-suburb-list {
                    display: none;
                    position: absolute;
                    z-index: 1;
                    background-color: #fff;
                    border: 1px solid #ccc;
                    list-style-type: none !important;
                    margin: 0;
                    padding: 0 !important;
                    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
                    max-height: 200px;
                    overflow-y: auto;
                    scrollbar-width: thin;
                    /* width: 97%; */
                }

                .fc-suburb-list li {
                    padding: 5px;
                }

                .fc-suburb-list li:hover {
                    background-color: #f0f0f0;
                    cursor: pointer;
                }

                /* Webkit (Safari, Chrome) */
                .fc-suburb-dropdown .fc-suburb-list::-webkit-scrollbar {
                    width: 1px;
                }

                /* Webkit (Safari, Chrome) */
                .fc-suburb-dropdown .fc-suburb-list::-webkit-scrollbar-track {
                    background: #f1f1f1;
                }

                /* Webkit (Safari, Chrome) */
                .fc-suburb-dropdown .fc-suburb-list::-webkit-scrollbar-thumb {
                    background: #888;
                }

                /* Suburb dropdown css ended */
            </style>

            <script>
                jQuery(document).ready(function() {
                    var xhr;
                    // Function to populate suburbs based on user input
                    function populateSuburbs(parent, _this) {

                        // Trim user input
                        let param = _this.val().trim();

                        // If there's an ongoing AJAX request, abort it to prevent duplicate requests
                        if (xhr && xhr !== null) {
                            xhr.abort();
                        }

                        // Store a reference to the parent element
                        var dropDownElement = _this.next('ul.fc-suburb-list');

                        // AJAX request to fetch suburbs data
                        xhr = jQuery.ajax({
                            url: "<?php echo fc_apis_prefix(); ?>suburbs",
                            dataType: 'json',
                            headers: {
                                'Authorization': `Bearer <?php echo $token ?>`,
                                'version': '5.2.0',
                            },
                            data: {
                                q: 'term', // Query parameter
                                term: param // Search term
                            },
                            success: function(response) {
                                // Clear previous dropdown options
                                jQuery(dropDownElement).empty();
                                // Populate select dropdown with options based on AJAX response
                                jQuery.each(response.data, function(index, value) {
                                    // Append each suburb as an option in the dropdown
                                    jQuery(dropDownElement).append('<li class="suburb-list" postcode="' + value.postcode + '" suburb="' + value.name + '" state="' + value.state + '">' + value.name + ', ' + value.postcode + ' (' + value.state + ')' + '</li>');
                                });

                                var fcSuburbList = document.querySelectorAll(parent + ' .fc-suburb-list .suburb-list');

                                for (let fcSuburbListItem of fcSuburbList) {
                                    fcSuburbListItem.addEventListener('click', function(e) {
                                        selectSuburb(parent, jQuery(e.target))
                                    })
                                }
                            },
                            error: function(xhr, status, error) {
                                if (status != 'abort') {
                                    // Log any errors in the console
                                    console.error(xhr.responseText)
                                }
                            }
                        });
                    }

                    jQuery('#fc_billing_suburb').on('click', function() {
                        // Clear input value
                        jQuery(this).val('');
                        // Show suburb list
                        jQuery('.fc-suburb-list').show();
                    });

                    jQuery('#fc_billing_suburb').on('keyup', function() {
                        // Call function to populate suburbs
                        populateSuburbs('#fc_billing_suburb_field', jQuery(this));
                    });

                    jQuery('#fc_shipping_suburb').on('click', function() {
                        // Clear input value
                        jQuery(this).val('');
                        // Show suburb list
                        jQuery('.fc-suburb-list').show();
                    });

                    jQuery('#fc_shipping_suburb').on('keyup', function() {
                        // Call function to populate suburbs
                        populateSuburbs('#fc_shipping_suburb_field', jQuery(this));
                    });

                    function appendCustomHiddenElement(element, insertAfterElement) {
                        jQuery('<input>', {
                            type: 'hidden',
                            class: element,
                            id: element,
                            name: element,
                            value: ''
                        }).insertAfter(insertAfterElement);
                    }

                    // Event handler for selecting a suburb from the list
                    function selectSuburb(parent, _this) {

                        // Assign selected suburb, state, and postcode values to form fields
                        var selectedValue = _this.text();
                        // Get postcode, suburb, and state attributes of the selected item
                        let state = _this.attr('state');
                        let postcode = _this.attr('postcode');
                        let suburb = _this.attr('suburb');

                        if (parent == '#fc_billing_suburb_field') {
                            let billingState = jQuery('#billing_state')
                            let billingPostcode = jQuery('#billing_postcode')
                            let billingCity = jQuery('#billing_city')

                            billingState.val(state);
                            billingPostcode.val(postcode);
                            billingCity.val(suburb);
                        } else {
                            let shippingState = jQuery('#shipping_state');
                            let shippingPostcode = jQuery('#shipping_postcode');
                            let shippingCity = jQuery('#shipping_city');

                            shippingState.val(state);
                            shippingPostcode.val(postcode);
                            shippingCity.val(suburb);
                        }

                        let billingCountryName = jQuery('#billing_country').val();
                        let enableLocalPickup = false;
                        // to enable local pickup checkbox on checkout page if local pickup conditions are met
                        if (localPickupPostcodes && localPickupPostcodes.includes(postcode)) {
                            enableLocalPickup = true;
                        } else if (localPickupCountry && localPickupCountry.includes(billingCountryName)) {
                            enableLocalPickup = true;
                        } else if (localPickupState && localPickupState.includes(billingCountryName + ':' + state)) {
                            enableLocalPickup = true;
                        } else if (localPickupPostcodeRange && isInRange(postcode, localPickupPostcodeRange)) {
                            enableLocalPickup = true;
                        }

                        if (enableLocalPickup) {
                            jQuery('#fc_local_pickup').prop("disabled", false);
                            jQuery('.fc-local-pickup').removeClass("fc-d-none");
                        } else {
                            jQuery('#fc_local_pickup').prop('checked', false);
                            jQuery('#fc_local_pickup').prop("disabled", true);
                            jQuery('.fc-local-pickup').addClass("fc-d-none");
                        }

                        // Find the nearest input element relative to the dropdown
                        var nearestInput = _this.closest('.fc-selected-suburb').find('input[type="text"]');
                        // Set the value of the input element
                        nearestInput.val(selectedValue);
                        // Hide suburb list
                        jQuery('.fc-suburb-list').hide();
                        jQuery('ul.fc-suburb-list').empty();

                        jQuery('body').trigger('update_checkout');
                    }


                    jQuery('#fc_atl_checkbox, #fc_local_pickup').on('change', function() {
                        jQuery('body').trigger('update_checkout');
                    });
                })
            </script>

<?php
        }
    }
    if (isClassicMode()) {
        add_action('wp_footer', 'fc_add_jscript_checkout', 99);
    }
}

if (!function_exists('is_test_mode_active')) {
    function is_test_mode_active()
    {
        try {
            if (WP_ENV == 'STAGING') {
                global $test_key;
                $testMode = get_option($test_key, false);

                return $testMode;
            }
            return false;
        } catch (Exception $e) {
            return false;
        }
    }
}


if (!function_exists('fc_apis_prefix')) {
    function fc_apis_prefix()
    {
        global $api, $prod_api;

        if (is_test_mode_active()) {
            return $api;
        } else {
            return $prod_api;
        }
    }
}
if (!function_exists('connect_fc_apis_prefix')) {
    function connect_fc_apis_prefix()
    {
        global $api_origin, $prod_api_origin;

        if (is_test_mode_active()) {
            return $api_origin;
        } else {
            return $prod_api_origin;
        }
    }
}
if (!function_exists('update_order_on_portal')) {

    function update_order_on_portal($data = [])
    {
        FastCourierRequests::customHttpPost('api/update-order-id', fc_sanitize_data($data));
    }
}

if (!function_exists('fc_get_collection_date')) {
    function fc_get_collection_date($processAfterDays)
    {
        global $fc_holiday_file_path;
        // Check if the file exists
        if (!file_exists($fc_holiday_file_path)) {
            // populate holidays
            populate_fc_holidays();
        }

        $current_date = date('Y-m-d');
        $collection_date = ($processAfterDays === '0') ? $current_date : date('Y-m-d', strtotime('+' . (int) $processAfterDays . ' day', strtotime($current_date)));

        $current_time = strtotime(date('H:i'));
        $target_time = strtotime('12:00');
        // if current time is > 12PM, collection date will be the next day
        if ($current_time > $target_time) {
            $collection_date = date('Y-m-d', strtotime('+1 day', strtotime($collection_date)));
        }

        // Fetch public holidays
        $holiday_dates = [];
        // Check if the file exists
        if (file_exists($fc_holiday_file_path)) {
            // get the holidays from the file
            $holidaysArray = file_get_contents($fc_holiday_file_path);
            $decoded = json_decode($holidaysArray);

            // Convert stdClass to array if needed
            if ($decoded instanceof stdClass) {
                $holiday_array = json_decode(json_encode($decoded), true);
            } elseif (is_array($decoded)) {
                $holiday_array = $decoded;
            } else {
                $holiday_array = [];
            }

            // Flatten all holiday dates into one array
            foreach ($holiday_array as $key => $value) {
                if (is_array($value)) {
                    $holiday_dates = array_merge($holiday_dates, $value);
                } elseif ($key === 'Cities') {
                    foreach ($value as $city) {
                        if (isset($city['dates']) && is_array($city['dates'])) {
                            $holiday_dates = array_merge($holiday_dates, $city['dates']);
                        }
                    }
                }
            }
        }

        $current_date_is_valid = true;
        // Check if the date falls on a weekend (Saturday or Sunday)
        if (date('N', strtotime($collection_date)) >= 6) {
            $current_date_is_valid = false;
        }
        // Check if the date falls on a holiday
        if (in_array($collection_date, $holiday_dates)) {
            $current_date_is_valid = false;
        }

        if ($current_date_is_valid) {
            return $collection_date;
        } else {
            // check date for next working day
            $next_day = strtotime('+1 day', strtotime($collection_date));

            while (date('N', $next_day) >= 6 || in_array(date('Y-m-d', $next_day), $holiday_dates)) {
                $next_day = strtotime('+1 day', $next_day);
            }
            return date('Y-m-d', $next_day);
        }
    }
}

// get latitude/longitude from address (e.g. city,postcode,state)
if (!function_exists('getLatLongFromAddress')) {

    function getLatLongFromAddress($address)
    {
        $query = http_build_query(['q' => $address]);
        $response = FastCourierRequests::httpGet('suburbs/geocode' . '?' . $query);

        if (isset($response['data']['latitude']) && isset($response['data']['longitude'])) {
            return ['latitude' => $response['data']['latitude'], 'longitude' => $response['data']['longitude']];
        } else {
            return null;
        }
    }
}

// calculate distance using latitude/longitude
if (!function_exists('calculateDistance')) {

    function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371; // Earth's radius in kilometers

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        $distance = $earthRadius * $c;

        return $distance;
    }
}

if (!function_exists('syncLocations')) {
    function syncLocations()
    {
        try {
            global $wpdb, $fc_locations_table, $options_prefix;

            $locations = $wpdb->get_results("SELECT * FROM {$fc_locations_table} WHERE is_deleted = 0", ARRAY_A);

            if (!empty($locations)) {
                $merchantDetails = json_decode(get_option($options_prefix . 'merchant_details'));
                foreach ($locations as $location) {
                    unset($location['id'], $location['created_at'], $location['updated_at']);
                    $location['merchant_domain_id'] = $merchantDetails->id;
                    $location['location_name'] = $location['name'];

                    FastCourierRequests::httpPost('merchant_domain/locations/add', fc_sanitize_data($location));
                }
            }
        } catch (\Exception $e) {
        }
    }
}

if (!function_exists('fc_change_shipping_text_on_cart_page_script')) {
    function fc_change_shipping_text_on_cart_page_script()
    {
        echo '<script>
        jQuery(document).ready(function($){
            // Find the form with the specified class
            var shippingCalculatorForm = $("form.woocommerce-shipping-calculator");
            
            // Find the shipping row in the cart table
            var shippingRow = $("tr.fee");

            if (shippingRow.length > 0) {
                $("tr.fee").remove();
            }

            // Add a sibling element with the text "Proceed to Calculate"
            shippingCalculatorForm.after(`Proceed to Calculate`);

            shippingCalculatorForm.remove();

            // The cart API request is completed
            $(document.body).on(`updated_cart_totals`, function() {
                // Find the shipping row in the cart table
                var shippingRow = $("tr.fee");

                if (shippingRow.length > 0) {
                    $("tr.fee").remove();
                }
            });
        });
        </script>';
    }
    if (isClassicMode()) {
        add_action('wp_footer', 'fc_change_shipping_text_on_cart_page_script');
    }
}

if (!function_exists('fc_custom_add_atl_to_order_email')) {
    /**
     * Add ATL to order email
     *
     * @param WC_Order $order The order object.
     * @param bool $sent_to_admin Is the order being sent to an admin.
     * @param bool $plain_text Is the email being sent as plain text.
     * @param WC_Email $email The email object.
     * @return WC_Order The order object.
     */
    function fc_custom_add_atl_to_order_email($order, $sent_to_admin, $plain_text, $email)
    {
        // Check if the order contains the ATL checkbox field
        $fc_atl_value = get_post_meta($order->get_id(), 'fc_atl', true);

        // If ATL checkbox was selected
        if ($fc_atl_value == '1' && !$sent_to_admin && 'customer_processing_order' === $email->id) {
            /**
             * Add a line to the order email that indicates the customer has
             * given permission to leave the package at their doorstep.
             */
            echo '<p>We have Authority to Leave the order at your doorstep.</p>';
        }

        return $order;
    }

    add_action('woocommerce_email_order_details', 'fc_custom_add_atl_to_order_email', 10, 4);
}

if (!function_exists('remove_shipping_cost')) {
    if (isClassicMode()) {
        add_action('woocommerce_cart_calculate_fees', 'remove_shipping_cost');
    }
    function remove_shipping_cost()
    {
        // Set the shipping cost as 0 to make correct value for total cart value
        if (is_cart()) {
            WC()->cart->add_fee("Shipping", (float) 0);
        }
    }
}

if (!function_exists('update_fc_post_meta_on_hold_order')) {
    /**
     * Update additional post meta when an order is put on hold
     *
     * @param int $order_id Order ID
     */
    function update_fc_post_meta_on_hold_order($order_id)
    {
        global $fc_order_status;
        update_post_meta($order_id, 'fc_status', $fc_order_status['hold']['key']);
        update_post_meta($order_id, 'fc_is_reprocessable', '0');

        if (isHposEnabled()) {
            $order = new \WC_Order($order_id);
            $order->update_meta_data('fc_status', $fc_order_status['hold']['key']);
            $order->update_meta_data('fc_is_reprocessable', '0');
            $order->save();
        }
    }

    // Hook the function to the woocommerce_order_status_on-hold action
    add_action('woocommerce_order_status_on-hold', 'update_fc_post_meta_on_hold_order', 10, 1);
}

if (!function_exists('shouldUpdateFile')) {
    /**
     * Checks if the file needs to be updated.
     *
     * @return bool True if the file needs to be updated, false otherwise.
     */
    function shouldUpdateFile()
    {
        global $fc_holiday_file_path;

        // Check if the file exists
        if (!file_exists($fc_holiday_file_path)) {
            // File doesn't exist, so let's create it
            touch($fc_holiday_file_path);
            return true;
        }

        // Get the file's last modification date
        $lastModifiedDate = date('Y-m-d', filemtime($fc_holiday_file_path));

        // Get the current date
        $currentDate = date('Y-m-d');

        // Check if the file was last modified on a different day
        return $lastModifiedDate !== $currentDate;
    }
}

if (!function_exists('populate_fc_holidays')) {
    /**
     * Populates the fc_holidays file with public holidays from the API if necessary.
     */
    function populate_fc_holidays()
    {
        global $fc_holiday_file_path;

        // Check if the file needs to be updated
        if (shouldUpdateFile()) {
            // Get the public holidays from the API
            $publicHolidays = FastCourierRequests::httpGet('public-holidays');

            // Retrieve the holiday data from the response
            $holidays = !empty($publicHolidays) ? $publicHolidays['data']['data'] : [];

            // Write the updated content to the file
            file_put_contents($fc_holiday_file_path, json_encode($holidays));
        }
    }
}

if (!function_exists('getOrderErrorList')) {
    /**
     * Retrieve a list of errors based on quote and shipping details.
     * @param string|array $quote - The quote data.
     * @param array $shipping_details - The shipping details.
     * @return array - The list of errors.
     */
    function getOrderErrorList($quote, $shipping_details)
    {
        // Convert JSON string to array if quote is not an array
        $response = FastCourierRequests::httpGet('get-validations');
        $fc_courier_validation_api = $response['data']['data'];

        if ($quote && !is_array($quote)) {
            $quote = json_decode($quote, true);
        }

        $errors = [];

        // Return no errors if the quote has a status
        if (isset($quote['status'])) {
            return $errors;
        }

        // Initialize validation rules
        $validations = [
            'first_name' => 0,
            'last_name' => 0,
            'company_name' => 0,
            'address' => 0,
            'phone_number' => 0,
        ];

        // Create fields array
        $fields = ['first_name', 'last_name', 'company_name', 'address', 'phone_number'];

        // Loop through the quote items to set validation rules
        foreach ($quote ?? [] as $quoteItem) {
            $courierVals = (!empty($quoteItem['data']) && !empty($quoteItem['data']['courierName']) && isset($fc_courier_validation_api[$quoteItem['data']['courierName']])) ? $fc_courier_validation_api[$quoteItem['data']['courierName']] : null;
            if ($courierVals) {
                // Set validation rules based on courier values
                foreach ($fields as $field) {
                    if (isset($courierVals[$field])) {
                        if ($validations[$field] == 0 || $validations[$field] > $courierVals[$field]) {
                            $validations[$field] = $courierVals[$field];
                        }
                    }
                }
            }
        }

        // Check shipping details against validation rules and add errors if necessary
        foreach ($fields as $field) {
            if ($validations[$field] > 0 && strlen($shipping_details[$field] ?? '') > $validations[$field]) {
                $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' must be ' . $validations[$field] . ' characters long.';
            }
        }

        return $errors;
    }
}

if (!function_exists('fc_order_meta_box')) {
    // Add custom order meta box on order details page
    add_action('add_meta_boxes', 'fc_add_custom_order_meta_box');

    function fc_add_custom_order_meta_box()
    {
        add_meta_box(
            'custom-order-meta-box',
            __('Shipping Information'),
            'fc_custom_order_meta_box_content',
            'shop_order',
            'side',
            'default'
        );
    }

    function fc_custom_order_meta_box_content($post)
    {
        $order = wc_get_order($post->ID);
        if ($order) {
            $quotes = $order->get_meta('fc_order_quote');
            if ($quotes && !is_array($quotes)) {
                $quotes = json_decode($quotes, true);
            }
            if ($quotes && !is_array($quotes)) {
                $quotes = json_decode($quotes, true);
            }
        }
        if ($order && !empty($quotes)) {
            echo '<div>';
            foreach ($quotes as $quote) {

                $notApplicable = null;
                if (empty($quote['data'])) {
                    $notApplicable = "--";
                }

                $consignmentInfo = null;
                $consignmentAvailable = false;

                if ($notApplicable) {
                    $consignmentInfo = $notApplicable;
                } elseif (($order->get_meta('fc_consignment_number')) && !empty($order->get_meta('fc_consignment_number'))) {
                    $consignmentInfo = esc_html($order->get_meta('fc_consignment_number'));
                    $consignmentAvailable = true;
                } elseif ($quote['data'] && !empty($quote['data']['fc_consignment_number'])) {
                    $consignmentInfo = esc_html($quote['data']['fc_consignment_number']);
                    $consignmentAvailable = true;
                } else {
                    $consignmentInfo = 'N/A';
                }

                $consignmentValue = $consignmentInfo;
                if ($consignmentAvailable) {
                    $consignmentValue = $consignmentInfo;
                }

                $hashId = $notApplicable ?? esc_html($quote['data']['orderHashId']);
                $courierName = $notApplicable ?? esc_html($quote['data']['courierName']);
                $priceIncludingGst = $notApplicable ?? esc_html($quote['data']['priceIncludingGst']);
                $eta = $notApplicable ?? esc_html($quote['data']['eta']);

                echo '<p><strong>' . __('Reference Number:') . '</strong> ' . $hashId . '</p>';
                echo '<p><strong>' . __('Consignment Number:') . '</strong> ' . $consignmentValue . '</p>';
                echo '<p><strong>' . __('Courier:') . '</strong> ' . $courierName . '</p>';
                echo '<p><strong>' . __('Price:') . '</strong> $' . $priceIncludingGst . '</p>';
                echo '<p><strong>' . __('Estimated Delivery:') . '</strong> ' . $eta . '</p>';

                $location = null;
                if (isset($quote['location'])) {
                    if (isset($quote['location']['name'])) {
                        $location = $quote['location']['name'];
                    } else {
                        $location = $quote['location']['location_name'];
                    }
                }
                $locationVal = $notApplicable ?? esc_html($location ?? 'N/A');
                echo '<p><strong>' . __('Location:') . '</strong> ' . $locationVal . '</p>';

                $trackingUrl = '';
                if ($order->get_meta('fc_tracking_url')) {
                    $trackingUrl = '<span class="fc3-title"><a href="' . esc_html($order->get_meta('fc_tracking_url')) . '" target="_blank">Tracking Link</a></span>';
                } elseif ($quote['data'] && !empty($quote['data']['fc_tracking_url'])) {
                    $trackingUrl = '<span class="fc3-title"><a href="' . esc_html($quote['data']['fc_tracking_url']) . '" target="_blank">Tracking Link</a></span>';
                }

                $docsPrefix = $order->get_meta('fc_order_doc_prefix');
                if ($docsPrefix) {
                    $docsPrefix = $docsPrefix;
                } elseif (($quote['data']) && isset($quote['data']['fc_order_doc_prefix']) && !empty($quote['data']['fc_order_doc_prefix'])) {
                    $docsPrefix = $quote['data']['fc_order_doc_prefix'];
                }

                echo '<p> ' . $trackingUrl;

                $label = $order->get_meta('fc_order_label');
                if ($label) {
                    echo ' | <span class="fc3-title"> <a href="' . $docsPrefix . $label . '" target="_blank">Label</a></span>';
                } elseif ($quote['data'] && !empty($quote['data']['fc_order_label'])) {
                    echo ' | <span class="fc3-title"> <a href="' . $docsPrefix . $quote['data']['fc_order_label'] . '" target="_blank">Label</a></span>';
                }

                $invoice = $order->get_meta('fc_order_invoice');
                if ($invoice) {
                    echo ' | <span class="fc3-title"> <a href="' . $docsPrefix . $invoice . '" target="_blank"> Invoice </a> </span>';
                } elseif ($quote['data'] && !empty($quote['data']['fc_order_invoice'])) {
                    echo ' | <span class="fc3-title"> <a href="' . $docsPrefix . $quote['data']['fc_order_invoice'] . '" target="_blank"> Invoice </a> </span>';
                }

                echo '</p>';
                echo '<hr />';
            }
            echo '</div>';
        } else {
            echo '<p>' . __('No order found.') . '</p>';
        }
    }
}


if (!function_exists('clean_address_part')) {
    function clean_address_part($address_part)
    {
        // Remove any commas at the start or end of the address part
        return trim($address_part, ',');
    }
}


if (!function_exists('oauth_callback')) {
    function oauth_callback($request)
    {
        $code = $_REQUEST['code'] ?? null;
        $state = $_REQUEST['state'];
        $access_token = $_REQUEST['access_token'] ?? null;
        // IF THERE IS ERROR
        if (isset($_REQUEST['error'])) {
            return [
                'status' => 'error',
                'message' => 'State parameter exists.',
                "close" => true
            ];
        }

        // IF WE GET CODE
        if (isset($code)) {
            $decodedState = json_decode(base64_decode($state), true);
            $redirectURI = urlencode($decodedState['extra']['additional_redirect_uri'] ?? '');
            $decodedURI = urldecode($redirectURI);
            $urlParts = parse_url($decodedURI);
            parse_str($urlParts['query'] ?? '', $queryParams);
            $redirectUrl = connect_fc_apis_prefix();
            header("Location: {$redirectUrl}oauth/callback?code={$code}&state={$state}&redirect_uri={$redirectURI}");
            exit;
        }

        if (isset($access_token)) {

            FCMerchantAuth::merchantLogin($access_token);
            return [
                'success' => true,
                'message' => 'Webhook received and processed successfully.',
                "close" => true
            ];
        }
    }
}

if (!function_exists('get_client_credentials')) {
    function get_client_credentials()
    {
        $client_id = get_option("client_id", 0);
        $client_secret = get_option("client_secret", 0);

        wp_send_json_success(['client_id' => $client_id, 'client_secret' => $client_secret]);
    }
}

if (!function_exists('isPostCodeIncludedInFlatRate')) {
    function isPostCodeIncludedInFlatRate($postCode, $flatRatePostCodes = "1000,2000,300-800")
    {
        $postCodes = explode(",", $flatRatePostCodes);

        foreach ($postCodes as $range) {
            $postCodeRange = explode("-", $range);

            if (count($postCodeRange) === 1) {
                if ((int)$postCode === (int)$postCodeRange[0]) {
                    return true;
                }
            } elseif (count($postCodeRange) === 2) {
                $start = (int)$postCodeRange[0];
                $end = (int)$postCodeRange[1];
                if ((int)$postCode >= $start && (int)$postCode <= $end) {
                    return true;
                }
            }
        }

        return false;
    }
}

if (!function_exists('fc_update_order_meta_on_status_change')) {

    function fc_update_order_meta_on_status_change($order_id, $old_status, $new_status, $order)
    {
        global $fc_order_status;

        if ($new_status == 'processing') {
            $fcStatus = $fc_order_status['unprocessed']['key'];
        } else if ($new_status == 'pending') { // peyment pending
            $fcStatus = $fc_order_status['payment_pending']['key'];
        } else if ($new_status == 'on-hold') {
            $fcStatus = $fc_order_status['hold']['key'];
        } else if ($new_status == 'completed') {
            $fcStatus = $fc_order_status['order_completed']['key'];
        } else if ($new_status == 'cancelled') {
            $fcStatus = $fc_order_status['cancelled']['key'];
        } else if ($new_status == 'refunded') {
            $fcStatus = $fc_order_status['refunded']['key'];
        } else if ($new_status == 'failed') {
            $fcStatus = $fc_order_status['order_failed']['key'];
        } else if ($new_status == 'pending-pickup') {
            $fcStatus = $fc_order_status['sent-for-processing']['key'];
        } else if ($new_status == 'checkout-draft') {
            $fcStatus = $fc_order_status['draft']['key'];
        } else {
            $fcStatus = $fc_order_status['unprocessed']['key'];
        }

        // Update order meta field
        update_post_meta($order_id, 'fc_status', $fcStatus);

        if (isHposEnabled()) {
            $order->update_meta_data('fc_status', $fcStatus);
            $order->save();
        }
    }
    add_action('woocommerce_order_status_changed', 'fc_update_order_meta_on_status_change', 10, 4);
}
