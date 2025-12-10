<?php

namespace FastCourier;

class FastCourierOrders
{
    public static function orderDetails($orderId)
    {
        $order = wc_get_order($orderId);

        $orderData = $order->get_data();
        $orderItems = $order->get_items();

        $items = [];
        $i = 0;
        foreach ($orderItems as $item) {
            $product = new \WC_Product($item['product_id']);
            $items[$i]['item_meta'] = $product->get_data();
            $items[$i]['item'] = $item->get_data();
            $i++;
        }

        global $options_prefix;
        $merchantDetails = get_option($options_prefix . 'merchant_details');

        $orderDetails = [
            'shipping' => $order->get_formatted_shipping_address(),
            'billing' => $order->get_formatted_billing_address(),
            'items' => $items,
            'order_meta' => [
                'order_id' => $orderId,
                'payment_method' => $orderData['payment_method'],
                'order_total' => $order->get_total(),
                'packages' => $order->get_meta('fc_order_packages'),
                'quote' => $order->get_meta('fc_order_quote'),
                'fc_status' => $order->get_meta('fc_status'),
                'fc_tracking_url' => $order->get_meta('fc_tracking_url'),
                'merchant_details' => json_decode($merchantDetails),
                'collection_date' => $order->get_meta('fc_collection_date'),
                'woo_url' => $order->get_edit_order_url(),
                'fc_consignment_number' => $order->get_meta('fc_consignment_number'),
                'fc_order_label' => $order->get_meta('fc_order_label'),
                'fc_order_doc_prefix' => $order->get_meta('fc_order_doc_prefix'),
                'fc_multiple_quotes' => $order->get_meta('fc_multiple_quotes')
            ],
        ];

        return $orderDetails;
    }

    public static function orderInsurance($orderValue = 0, $carierCharges = 0)
    {
        global $options_prefix;
        $merchantDetails = json_decode(get_option($options_prefix . 'merchant_details'));

        switch ($merchantDetails->insuranceType) {
            case 2:
                return '$' . $merchantDetails->insuranceAmount;

            case 3:
                return '$' . ($orderValue - (float) $carierCharges);

            default:
                return 'Not Required';
        }
    }

    public static function index()
    {
        $data = fc_sanitize_data($_GET);
        if (isset($data['order_id'])) {
            FastCourierMenuPage::layout('views/single-order.php', array('page_title' => 'Order Details', 'header' => true, 'order' => Self::orderDetails($data['order_id'])), true);
            exit;
        }
        FastCourierMenuPage::layout('views/orders.php', array('page_title' => 'Orders'));
        exit;
    }

    public static function processed()
    {
        $data = fc_sanitize_data($_GET);

        if (isset($data['order_id'])) {
            FastCourierMenuPage::layout('views/single-order.php', array('page_title' => 'Order Details', 'header' => true, 'order' => Self::orderDetails($data['order_id'])), true);
            exit;
        }
        FastCourierMenuPage::layout('views/processed-orders.php', array('page_title' => 'Orders'));
        exit;
    }

    public static function unprocessed()
    {
        $data = fc_sanitize_data($_GET);

        if (isset($data['order_id'])) {
            FastCourierMenuPage::layout('views/single-order.php', array('page_title' => 'Order Details', 'header' => true, 'order' => Self::orderDetails($data['order_id'])), true);
            exit;
        }
        FastCourierMenuPage::layout('views/unprocessed-orders.php', array('page_title' => 'Rejected Orders'));
        exit;
    }

    /**
     * Retrieve order details and display the appropriate page.
     *
     * @return void
     */
    public static function fallbacks()
    {
        // Sanitize the data from the GET request
        $data = fc_sanitize_data($_GET);

        // Check if the order_id parameter is set
        if (isset($data['order_id'])) {
            // Display the single order page with the order details
            FastCourierMenuPage::layout(
                'views/single-order.php',
                [
                    'page_title' => 'Order Details',
                    'header' => true,
                    'order' => Self::orderDetails($data['order_id'])
                ],
                true
            );
            // Exit the script
            exit;
        }

        // Display the fallback orders page
        FastCourierMenuPage::layout(
            'views/fallback-orders.php',
            ['page_title' => 'Fallback Orders']
        );
        // Exit the script
        exit;
    }
    /**
     * Retrieve all order and display the appropriate page.
     *
     * @return void
     */
    public static function allOrders()
    {
        // Sanitize the data from the GET request
        $data = fc_sanitize_data($_GET);

        // Check if the order_id parameter is set
        if (isset($data['order_id'])) {
            // Display the single order page with the order details
            FastCourierMenuPage::layout(
                'views/single-order.php',
                [
                    'page_title' => 'Order Details',
                    'header' => true,
                    'order' => Self::orderDetails($data['order_id'])
                ],
                true
            );
            // Exit the script
            exit;
        }

        // Display the fallback orders page
        FastCourierMenuPage::layout(
            'views/all-orders.php',
            ['page_title' => 'All Orders']
        );
        // Exit the script
        exit;
    }

    public static function hold()
    {
        $data = fc_sanitize_data($_GET);

        if (isset($data['order_id'])) {
            FastCourierMenuPage::layout('views/single-order.php', array('page_title' => 'Order Details', 'header' => true, 'order' => Self::orderDetails($data['order_id'])), true);
            exit;
        }
        FastCourierMenuPage::layout('views/hold-orders.php', array('page_title' => 'Hold Orders'));
        exit;
    }

    public static function update_order_status($request)
    {
        try {
            global $fc_order_status, $wpdb, $fc_web_hook_logs_table, $table_prefix;

            $file = fopen(__DIR__ . "/webhookLogs.txt", "a+");
            $payload = file_get_contents('php://input');
            fwrite($file, date('Y-m-d h:i:s') . "----" . $payload . "\r\n");
            fclose($file);
            $data = json_decode($payload, true);

            $isHposEnabled = isHposEnabled();
            // payload for store the logs in DB
            $dbPayload = [];

            foreach ($data as $order) {
                $postId = $order['wp_order_id'];

                if (!$postId) {
                    continue;
                }

                $query = "SELECT * FROM {$table_prefix}postmeta WHERE post_id = '$postId' AND meta_key = 'fc_order_quote' and meta_value LIKE %s";
                $result = $wpdb->get_results($wpdb->prepare($query, array("%$order[order_id]%")), ARRAY_A);
                $postMetaAvailable = true;

                if (empty($result) && $isHposEnabled) {
                    $postMetaAvailable = false;
                    $query = "SELECT * FROM {$table_prefix}wc_orders_meta WHERE order_id = '$postId' AND meta_key = 'fc_order_quote' and meta_value LIKE %s";
                    $result = $wpdb->get_results($wpdb->prepare($query, array("%$order[order_id]%")), ARRAY_A);
                }

                $rawMeta = $result[0]['meta_value'];
                if ($rawMeta[0] === '"' && substr($rawMeta, -1) === '"') {
                    $rawMeta = substr($rawMeta, 1, -1);
                    $rawMeta = stripslashes($rawMeta);
                }

                $orderMeta = json_decode($rawMeta, true);
                if (!is_array($orderMeta)) {
                    $orderMeta = json_decode($orderMeta, true);
                }

                $orderId = $order['wp_order_id'];
                if ($isHposEnabled) {
                    $currentOrder = new \WC_Order($orderId);
                }

                // In case, Flat rate order is booked through courier
                // Adding courier details in json to work same as other orders
                $isFlatRateOrFallBackOrder = false;
                foreach ($orderMeta as $key => $value) {
                    if (in_array($value['order_type'], [ORDER_TYPE_FLATRATE, ORDER_TYPE_FALLBACK]) && $value['order_id'] == $order['order_id']) {
                        $isFlatRateOrFallBackOrder = true;
                        $orderMeta[$key]['data']['courierName'] = sanitize_text_field($order['courierName']);
                        $orderMeta[$key]['data']['eta'] = sanitize_text_field($order['eta']);
                        $orderMeta[$key]['data']['id'] = sanitize_text_field($order['quote_id']);
                        $orderMeta[$key]['data']['logo'] = sanitize_text_field($order['logo']);
                        $orderMeta[$key]['data']['priceIncludingGst'] = sanitize_text_field($order['price']);
                        $orderMeta[$key]['data']['orderHashId'] = sanitize_text_field($order['order_id']);
                    }
                }


                if ($isFlatRateOrFallBackOrder) {
                    $fcOrderQuotes = json_encode(wp_json_encode($orderMeta));
                    $fcOrderQuotes = addslashes($fcOrderQuotes);
                    if ($postMetaAvailable) {
                        update_post_meta($orderId, 'fc_order_quote', $fcOrderQuotes);
                    }
                    if ($isHposEnabled) {
                        $currentOrder->update_meta_data('fc_order_quote', $fcOrderQuotes);
                    }
                }

                // update collection date for all orders
                if ($postMetaAvailable) {
                    update_post_meta($orderId, 'fc_collection_date', $order['collection_date']);
                }

                if ($isHposEnabled) {
                    $currentOrder->update_meta_data('fc_collection_date', $order['collection_date']);
                }

                if ($orderMeta && count($orderMeta) > 1) {
                    $unProcessedOrderCount = 0;
                    foreach ($orderMeta as $key => $value) {
                        if ($order['order_id'] == $value['data']['orderHashId']) {
                            $orderMeta[$key]['data']['fc_status'] = sanitize_text_field($order['status_for_merchant']);
                            $orderMeta[$key]['data']['fc_customer_status'] = sanitize_text_field($order['status_for_customer']);
                            $orderMeta[$key]['data']['fc_tracking_url'] = sanitize_text_field($order['tracking_url']);
                            $orderMeta[$key]['data']['fc_is_reprocessable'] = sanitize_text_field($order['is_reprocessable'] == true ? '1' : '0');

                            if (isset($order['invoice']) && $order['invoice']) $orderMeta[$key]['data']['fc_order_invoice'] = sanitize_text_field($order['invoice']);

                            if (isset($order['label']) && $order['label']) $orderMeta[$key]['data']['fc_order_label'] = sanitize_text_field($order['label']);

                            if (isset($order['additional']) && $order['additional']) $orderMeta[$key]['data']['fc_order_additional_docs'] = sanitize_text_field(wp_json_encode($order['additional']));

                            if (isset($order['doc_prefix']) && $order['doc_prefix']) $orderMeta[$key]['data']['fc_order_doc_prefix'] = $order['doc_prefix'];

                            if (isset($order['reason'])) {
                                $reasons = '';
                                foreach ($order['reason'] as $reason) {
                                    $reasons .= sanitize_text_field($reason[0]) . "<br>";
                                }

                                $orderMeta[$key]['data']['fc_fail_reason'] = $reasons;
                            }

                            if (isset($order['consignment_number'])) {
                                $orderMeta[$key]['data']['fc_consignment_number'] = $order['consignment_number'];
                            }

                            $unProcessedOrderCount = in_array($order['status_for_merchant'], [$fc_order_status['order_rejected']['key'], $fc_order_status['rejected']['key']]) ? $unProcessedOrderCount + 1 : $unProcessedOrderCount;
                        } else {
                            $unProcessedOrderCount = in_array($value['data']['fc_status'], [$fc_order_status['order_rejected']['key'], $fc_order_status['rejected']['key']]) ? $unProcessedOrderCount + 1 : $unProcessedOrderCount;
                        }
                    }

                    $fcOrderQuotes = json_encode(wp_json_encode($orderMeta));
                    $fcOrderQuotes = addslashes($fcOrderQuotes);

                    if ($unProcessedOrderCount == 0) {
                        if ($postMetaAvailable) {
                            update_post_meta($orderId, 'fc_status', sanitize_text_field($order['status_for_merchant']));
                            update_post_meta($orderId, 'fc_is_reprocessable', '0');
                        }
                        if ($isHposEnabled) {
                            $currentOrder->update_meta_data('fc_status', sanitize_text_field($order['status_for_merchant']));
                            $currentOrder->update_meta_data('fc_is_reprocessable', '0');
                        }
                    } else if (count($orderMeta) > $unProcessedOrderCount) {
                        if ($postMetaAvailable) {
                            update_post_meta($orderId, 'fc_status', sanitize_text_field($fc_order_status['processed']['key']));
                            update_post_meta($orderId, 'fc_is_reprocessable', '0');
                        }
                        if ($isHposEnabled) {
                            $currentOrder->update_meta_data('fc_status', sanitize_text_field($fc_order_status['processed']['key']));
                            $currentOrder->update_meta_data('fc_is_reprocessable', '0');
                        }
                    } else if (count($orderMeta) == $unProcessedOrderCount) {
                        if ($postMetaAvailable) {
                            update_post_meta($orderId, 'fc_status', sanitize_text_field($fc_order_status['order_rejected']['key']));
                            update_post_meta($orderId, 'fc_is_reprocessable', '1');
                        }
                        if ($isHposEnabled) {
                            $currentOrder->update_meta_data('fc_status', sanitize_text_field($fc_order_status['order_rejected']['key']));
                            $currentOrder->update_meta_data('fc_is_reprocessable', '1');
                        }
                    }

                    if ($isHposEnabled) {
                        $currentOrder->update_meta_data('fc_multiple_quotes', true);
                        $currentOrder->update_meta_data('fc_order_quote', $fcOrderQuotes);
                    }

                    if ($postMetaAvailable) {
                        update_post_meta($orderId, 'fc_multiple_quotes', true);
                        update_post_meta($orderId, 'fc_order_quote', $fcOrderQuotes);
                    }
                } else {
                    if ($isHposEnabled) {
                        $currentOrder->update_meta_data('fc_status', sanitize_text_field($order['status_for_merchant']));
                        $currentOrder->update_meta_data('fc_customer_status', sanitize_text_field($order['status_for_customer']));
                        $currentOrder->update_meta_data('fc_tracking_url', sanitize_text_field($order['tracking_url']));
                        $currentOrder->update_meta_data('fc_is_reprocessable', sanitize_text_field($order['is_reprocessable'] == true ? '1' : '0'));
                    }
                    if ($postMetaAvailable) {
                        update_post_meta($orderId, 'fc_status', sanitize_text_field($order['status_for_merchant']));
                        update_post_meta($orderId, 'fc_customer_status', sanitize_text_field($order['status_for_customer']));
                        update_post_meta($orderId, 'fc_tracking_url', sanitize_text_field($order['tracking_url']));
                        update_post_meta($orderId, 'fc_is_reprocessable', sanitize_text_field($order['is_reprocessable'] == true ? '1' : '0'));
                    }

                    if (isset($order['invoice']) && $order['invoice']) {
                        if ($isHposEnabled) {
                            $currentOrder->update_meta_data('fc_order_invoice', sanitize_text_field($order['invoice']));
                        }
                        if ($postMetaAvailable) {
                            update_post_meta($orderId, 'fc_order_invoice', sanitize_text_field($order['invoice']));
                        }
                    }

                    if (isset($order['label']) && $order['label']) {
                        if ($isHposEnabled) {
                            $currentOrder->update_meta_data('fc_order_label', sanitize_text_field($order['label']));
                        }
                        if ($postMetaAvailable) {
                            update_post_meta($orderId, 'fc_order_label', sanitize_text_field($order['label']));
                        }
                    }

                    if (isset($order['additional']) && $order['additional']) {
                        if ($isHposEnabled) {
                            $currentOrder->update_meta_data('fc_order_additional_docs', wp_json_encode(sanitize_text_field($order['additional'])));
                        }
                        if ($postMetaAvailable) {
                            update_post_meta($orderId, 'fc_order_additional_docs', wp_json_encode(sanitize_text_field($order['additional'])));
                        }
                    }

                    if (isset($order['doc_prefix']) && $order['doc_prefix']) {
                        if ($isHposEnabled) {
                            $currentOrder->update_meta_data('fc_order_doc_prefix', $order['doc_prefix']);
                        }
                        if ($postMetaAvailable) {
                            update_post_meta($orderId, 'fc_order_doc_prefix', $order['doc_prefix']);
                        }
                    }

                    if (isset($order['reason'])) {
                        $reasons = '';
                        foreach ($order['reason'] as $reason) {
                            $reasons .= sanitize_text_field($reason[0]) . "<br>";
                        }
                        if ($isHposEnabled) {
                            $currentOrder->update_meta_data('fc_fail_reason', $reasons);
                        }
                        if ($postMetaAvailable) {
                            update_post_meta($orderId, 'fc_fail_reason', $reasons);
                        }
                    }

                    if (isset($order['consignment_number'])) {
                        if ($isHposEnabled) {
                            $currentOrder->update_meta_data('fc_consignment_number', $order['consignment_number']);
                        }
                        if ($postMetaAvailable) {
                            update_post_meta($orderId, 'fc_consignment_number', $order['consignment_number']);
                        }
                    }
                }
                $dbPayload['payload'] = json_encode($order, true);
                if ($isHposEnabled) {
                    $currentOrder->save();
                }
            }

            // store logs in DB
            $wpdb->insert($fc_web_hook_logs_table, $dbPayload);
            $response = new \WP_REST_Response(['success' => true]);
            $response->set_status(200);
        } catch (\Exception $e) {
            $response = new \WP_REST_Response(['success' => false, 'message' => $e->getMessage()]);
            $response->set_status(400);
        }

        return $response;
    }

    public static function process_orders()
    {
        global $fc_order_status;
        try {
            $postOrders = fc_sanitize_data($_POST['orders']);
            $postData = fc_sanitize_data($_POST);
            $orders = [];
            $isHposEnabled = isHposEnabled();

            $totalOrders = [];
            foreach ($postOrders as $orderId) {
                $orderDetail = new \WC_Order($orderId);

                $self = new self();
                $newOrders = $self->payloadForCreateOrders($orderDetail, $postData['collection_date']);
                $orders = array_merge($orders, $newOrders);

                $isMultipleCourierOrders = $orderDetail->get_meta('fc_multiple_quotes');

                if ($isMultipleCourierOrders) {

                    $orderQuotes = $orderDetail->get_meta('fc_order_quote');
                    $hashId = [];
                    if ($postData && isset($postData['selectedHashId'])) {
                        $hashId = is_array($postData['selectedHashId']) ? $postData['selectedHashId'] : [$postData['selectedHashId']];
                    }

                    if (!is_array($orderQuotes)) {
                        while (!is_array($orderQuotes)) {
                            $orderQuotes = json_decode($orderQuotes, true);
                        }
                    }
                    foreach ($orderQuotes as $key => $value) {
                        if (!empty($hashId)) {
                            if (in_array($value['data']['orderHashId'], $hashId)) {
                                $orderQuotes[$key]['data']['fc_status'] = $fc_order_status['sent-for-processing']['key'];
                                $orderQuotes[$key]['data']['fc_is_reprocessable'] = '0';
                                $orderQuotes[$key]['data']['fc_collection_date'] = $postData['collection_date'];
                            }
                        } else {
                            $orderQuotes[$key]['data']['fc_status'] = $fc_order_status['sent-for-processing']['key'];
                            $orderQuotes[$key]['data']['fc_is_reprocessable'] = '0';
                            $orderQuotes[$key]['data']['fc_collection_date'] = $postData['collection_date'];
                        }
                    }

                    $fcOrderQuotes = json_encode(wp_json_encode($orderQuotes));
                    $fcOrderQuotes = addslashes($fcOrderQuotes);
                    if ($isHposEnabled) {
                        $orderDetail->update_meta_data('fc_multiple_quotes', true);
                        $orderDetail->update_meta_data('fc_order_quote', $fcOrderQuotes);
                    } else {
                        update_post_meta($orderId, 'fc_order_quote', $fcOrderQuotes);
                    }
                    update_post_meta($orderId, 'fc_multiple_quotes', true);

                    if (empty($hashId)) {
                        if ($isHposEnabled) {
                            $orderDetail->update_meta_data('fc_status', $fc_order_status['sent-for-processing']['key']);
                            $orderDetail->update_meta_data('fc_is_reprocessable', '0');
                        } else {
                            update_post_meta($orderId, 'fc_status', $fc_order_status['sent-for-processing']['key']);
                        }
                        update_post_meta($orderId, 'fc_is_reprocessable', '0');
                        // Save the custom status to the order
                        $orderDetail->update_status("wc-pending-pickup");
                        $orderDetail->save();
                    }
                } else {
                    if ($isHposEnabled) {
                        $orderDetail->update_meta_data('fc_collection_date', sanitize_text_field($postData['collection_date']));
                        $orderDetail->update_meta_data('fc_status', $fc_order_status['sent-for-processing']['key']);
                        $orderDetail->update_meta_data('fc_is_reprocessable', '0');
                    }

                    if (!isClassicMode()) {
                        update_post_meta($orderId, 'fc_order_quote', $orderDetail->get_meta('fc_order_quote'));
                    }
                    update_post_meta($orderId, 'fc_status', $fc_order_status['sent-for-processing']['key']);
                    update_post_meta($orderId, 'fc_collection_date', sanitize_text_field($postData['collection_date']));
                    update_post_meta($orderId, 'fc_is_reprocessable', '0');
                    // Save the custom status to the order
                    $orderDetail->update_status("wc-pending-pickup");
                    $orderDetail->save();
                }

                $totalOrders[$orderId]++;
            }

            // Convert string to boolean if is_reprocessing is exists
            $is_reprocessing = isset($postData['is_reprocessing']) ? filter_var($postData['is_reprocessing'], FILTER_VALIDATE_BOOLEAN) : false;

            $response = FastCourierRequests::httpPost('bulk_order_booking', ['orders' => $orders, 'isReprocessOrders' => $is_reprocessing]);

            $processedOrders = [];
            foreach ($response['response'] as $res) {
                if (!$res['status'] && $res['status_code'] == 422) {
                    $wporder = new \WC_Order($res['wpOrderId']);

                    $reasons = implode("<br>", $res['errors']);

                    if ($isHposEnabled) {
                        $wporder->update_meta_data('fc_status', $fc_order_status['order_rejected']['key']);
                        $wporder->update_meta_data('fc_is_reprocessable', '1');
                        $wporder->update_meta_data('fc_fail_reason', $reasons);
                    } else {
                        update_post_meta($res['wpOrderId'], 'fc_status', $fc_order_status['order_rejected']['key']);
                    }
                    update_post_meta($res['wpOrderId'], 'fc_is_reprocessable', '1');
                    update_post_meta($res['wpOrderId'], 'fc_fail_reason', $reasons);

                    // Save the custom status to the order
                    $wporder->update_status("wc-processing");
                    $wporder->save();
                }

                if ($res['status'] == 1) {
                    $processedOrders[$res['wpOrderId']]++;
                }
            }
            $message = count($processedOrders) . ' order(s) processed out of ' . count($totalOrders) . ' order(s).';

            $response = ['status' => 200, 'message' => $message];
        } catch (\Exception $e) {
            $response = FastCourierRequests::failResponse($e->getMessage());
        }

        header('Content-type: application/json');
        echo wp_json_encode($response);
        exit;
    }

    public static function hold_orders()
    {
        global $fc_order_status;
        try {
            $isHposEnabled = isHposEnabled();

            $postOrders = fc_sanitize_data($_POST['orders']);
            $postData = fc_sanitize_data($_POST);
            $orders = [];
            foreach ($postOrders as $orderId) {
                $orderDetail = new \WC_Order($orderId);

                $self = new self();
                $newOrders = $self->payloadForCreateOrders($orderDetail, $postData['collection_date']);
                $orders = array_merge($orders, $newOrders);

                if ($isHposEnabled) {
                    $orderDetail->update_meta_data('fc_status', $fc_order_status['hold']['key']);
                    $orderDetail->update_meta_data('fc_is_reprocessable', '0');
                } else {
                    update_post_meta($orderId, 'fc_status', $fc_order_status['hold']['key']);
                }
                update_post_meta($orderId, 'fc_is_reprocessable', '0');
                // Save the hold status to the order for woo-commerce
                $orderDetail->update_status("wc-on-hold");
                $orderDetail->save();
            }
            $response = new \WP_REST_Response(['success' => true]);
            $response->set_status(200);
        } catch (\Exception $e) {
            $response = new \WP_REST_Response(['success' => false]);
            $response->set_status(400);
        }
        echo wp_json_encode($response);
        header('Content-type: application/json');
        exit;
    }

    public static function cron_process_orders()
    {
        global $wpdb, $fc_order_status, $options_prefix, $defaultProcessOrderAfterMinutes, $fc_cron_logs_table;
        try {
            $merchantDetails = json_decode(get_option($options_prefix . 'merchant_details'));
            $cron_log = array();
            if ($merchantDetails->automaticOrderProcess) {
                $isHposEnabled = isHposEnabled();
                $cron_log['started_at'] = date('Y-m-d H:i:s');
                $cron_log['name'] = 'Auto process orders';
                // Calculate the date and time as per config setting
                $beforeMinutes = $merchantDetails->processAfterMinutes ?? $defaultProcessOrderAfterMinutes;
                $newTime = strtotime('-' . $beforeMinutes . ' minutes');
                $before = date('Y-m-d H:i:s', $newTime);

                // Create the order query
                $filters = array(
                    'date_query'   => array(
                        array(
                            'before'    => $before, // Orders older than specific minutes
                            'inclusive' => true,
                        ),
                    ),
                    'is_fc_order'   => true,
                    'is_fallbacked' => '0',
                    'limit'         => -1, // Retrieve all matching orders
                );

                if ($isHposEnabled) {
                    $customFilter['is_fc_order'] = true;
                    $customFilter['is_fallbacked'] = '0';
                    $filters = fc_handle_custom_query_var($filters, $customFilter);
                }

                $cron_log['payload'] = json_encode($filters);
                // Get the orders
                $query = new \WC_Order_Query($filters);
                $result = $query->get_orders();
                $count = count($result);
                $cron_log['total_orders'] = $count;
                if ($count > 0) {
                    $unprocessed_orders = [];
                    $processAfterDays = $merchantDetails->processAfterDays ?? '0';
                    $collectionDate = fc_get_collection_date($processAfterDays);
                    $cron_log['collection_date'] = $collectionDate;

                    // Loop through the orders
                    foreach ($result as $order) {
                        $orderId = $order->get_id();

                        $self = new self();
                        $orders = $self->payloadForCreateOrders($order, $collectionDate);
                        // $orders return empty array if there are errors
                        if (empty($orders)) {
                            continue;
                        }

                        $unprocessed_orders = array_merge($unprocessed_orders, $orders);

                        if ($isHposEnabled) {
                            $order->update_meta_data('fc_collection_date', $collectionDate);
                            $order->update_meta_data('fc_status', $fc_order_status['sent-for-processing']['key']);
                            $order->update_meta_data('fc_is_reprocessable', '0');
                        }

                        update_post_meta($orderId, 'fc_collection_date', $collectionDate);
                        update_post_meta($orderId, 'fc_status', $fc_order_status['sent-for-processing']['key']);
                        update_post_meta($orderId, 'fc_is_reprocessable', '0');

                        // Save the custom status to the order
                        $order->update_status("wc-pending-pickup");
                        $order->save();
                    }

                    // Process the orders
                    $response = FastCourierRequests::httpPost('bulk_order_booking', ['orders' => $unprocessed_orders, 'isReprocessOrders' => false]);

                    $orderIds = [];
                    foreach ($response['response'] as $res) {
                        if (!$res['status'] && $res['status_code'] == 422) {
                            $wporder = new \WC_Order($res['wpOrderId']);

                            $reasons = implode("<br>", $res['errors']);

                            if ($isHposEnabled) {
                                $wporder->update_meta_data('fc_status', $fc_order_status['order_rejected']['key']);
                                $wporder->update_meta_data('fc_is_reprocessable', '1');
                                $wporder->update_meta_data('fc_fail_reason', $reasons);
                            }

                            update_post_meta($res['wpOrderId'], 'fc_status', $fc_order_status['order_rejected']['key']);
                            update_post_meta($res['wpOrderId'], 'fc_is_reprocessable', '1');
                            update_post_meta($res['wpOrderId'], 'fc_fail_reason', $reasons);

                            // Save the custom status to the order
                            $wporder->update_status("wc-processing");
                            $wporder->save();
                        }

                        if ($res['status'] == 1) {
                            $orderIds[] = $res['wpOrderId'];
                        }
                    }

                    $cron_log['processed_orders'] = count($orderIds);
                    $cron_log['order_ids'] = implode(', ', $orderIds);
                }
                $cron_log['completed_at'] = date('Y-m-d H:i:s');
                // inserting location data in database
                $wpdb->insert($fc_cron_logs_table, $cron_log);
            }
            echo 1;
            // Delete the old logs
            $self = new self();
            $self->delete_old_logs();
        } catch (\Exception $e) {
            FastCourierRequests::failResponse($e->getMessage());
        }
        exit;
    }

    public static function resync_all_orders()
    {
        global $wpdb, $fc_options, $options_prefix;
        date_default_timezone_set('Australia/Sydney');
        try {
            // Replace into the options table
            $wpdb->replace(
                $fc_options,
                [
                    'option_name' => $options_prefix . 'resync_time',
                    'option_value' => date('d F, Y h:iA'),
                    'autoload' => 'yes'
                ]
            );

            // Check FastCourierRequests::httpGet response structure
            $response = FastCourierRequests::httpGet('run-order-synicing-cron');

            if (isset($response['status']) && $response['status'] == 200) {
                echo json_encode(['success' => true, 'message' => 'Order Syncing Finished.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to sync orders.']);
            }

            exit;
        } catch (\Throwable $th) {
            // Log the error for better troubleshooting
            error_log($th->getMessage());
            echo json_encode(['success' => false, 'message' => 'Something went wrong.']);
            return;
        }
    }

    /**
     * Generate payload for creating bulk orders
     *
     * @param $orderDetail
     * @param $collection_date
     * @return array
     */
    private function payloadForCreateOrders($orderDetail, $collection_date)
    {
        // Retrieve quote and convert to array if it's not already
        $quote = $orderDetail->get_meta('fc_order_quote');
        if (!is_array($quote)) {
            while (!is_array($quote)) {
                $quote = json_decode($quote, true);
            }
        }

        // Determine shipping details
        $shippingFirstName = $orderDetail->get_shipping_first_name() ?: $orderDetail->get_billing_first_name();
        $shippingLastName = $orderDetail->get_shipping_last_name() ?: $orderDetail->get_billing_last_name();
        $shippingAddress1 = $orderDetail->get_shipping_address_1() ?: $orderDetail->get_billing_address_1();
        $shippingAddress2 = $orderDetail->get_shipping_address_2() ?: $orderDetail->get_billing_address_2();
        $shippingPhone = $orderDetail->get_shipping_phone() ?: $orderDetail->get_billing_phone();
        $shippingCompany = 'NA';
        if ($orderDetail->get_shipping_company()) {
            $shippingCompany = $orderDetail->get_shipping_company();
        } elseif ($orderDetail->get_billing_company()) {
            $shippingCompany = $orderDetail->get_billing_company();
        }

        // Construct shipping details
        $shipping_details = [
            'first_name' => $shippingFirstName,
            'last_name' => $shippingLastName,
            'company' => $shippingCompany,
            'address' => $shippingAddress1 . ' ' . $shippingAddress2,
            'phone_number' => $shippingPhone,
        ];

        // Check for any order errors
        $errors = getOrderErrorList($quote, $shipping_details);

        // Return empty array if there are errors
        if (count($errors) > 0) {
            return [];
        }

        // Retrieve order details
        $orderId = $orderDetail->get_id();
        $email = $orderDetail->get_billing_email();
        $phone = $shippingPhone;
        $firstName = $shippingFirstName;
        $lastname = $shippingLastName;
        $companyName = $shippingCompany;
        $address1 = $shippingAddress1;
        $address2 = $shippingAddress2;

        // Process orders based on quote status
        if (isset($quote['status']) && $quote['status'] == 1) {
            // Collect the order to be processed
            $orders = [
                'quoteId' => $quote['data']['id'],
                'orderHashId' => $quote['data']['orderHashId'],
                'collectionDate' => $collection_date,
                'destinationEmail' => $email,
                'destinationPhone' => $phone,
                'wpOrderId' => $orderId,
                'destinationFirstName' => $firstName,
                'destinationLastName' => $lastname,
                'destinationCompanyName' => $companyName,
                'destinationAddress1' => $address1,
                'destinationAddress2' => $address2,
            ];
        } else {

            // Process orders for each quote item
            foreach ($quote as $quoteItem) {

                if (isset($quoteItem['data']['fc_is_reprocessable']) && $quoteItem['data']['fc_is_reprocessable'] == '0') {
                    continue;
                }
                $location = $quoteItem['location'];
                // Collect the order to be processed
                $order = [
                    'quoteId' => $quoteItem['data']['id'],
                    'orderHashId' => $quoteItem['data']['orderHashId'],
                    'collectionDate' => $collection_date,
                    'destinationEmail' => $email,
                    'destinationPhone' => $phone,
                    'wpOrderId' => $orderId,
                    'destinationFirstName' => $firstName,
                    'destinationLastName' => $lastname,
                    'destinationCompanyName' => $companyName,
                    'destinationAddress1' => $address1,
                    'destinationAddress2' => $address2,

                    'pickupFirstName' => $location['first_name'],
                    'pickupLastName' => $location['last_name'],
                    'pickupCompanyName' => $location['location_name'],
                    'pickupAddress1' => $location['address1'] ?? null,
                    'pickupStreetNumber' => $location['street_number'] ?? null,
                    'pickupStreetName' => $location['street_name'] ?? null,
                    'pickupAddress2' => $location['address2'],
                    'pickupPhone' => $location['phone'],
                    'pickupEmail' => $location['email']
                ];

                // Determine if ATL is required
                if (isset($quoteItem['atl']) && $quoteItem['atl']) {
                    $order['atl'] = true;
                } else {
                    $order['atl'] = false;
                }

                $orders[] = $order;
            }
        }
        return $orders;
    }

    // Delete the logs older then 3 months
    private function delete_old_logs()
    {
        global $wpdb, $fc_cron_logs_table;

        $threeMonthsAgo = date('Y-m-d H:i:s', strtotime('-3 months')); // Get the date three months ago

        $query = $wpdb->prepare("SELECT id FROM {$fc_cron_logs_table} WHERE created_at < %s", $threeMonthsAgo);
        $recordIDs = $wpdb->get_col($query);

        if (!empty($recordIDs)) {
            $idList = implode(',', $recordIDs); // Create a comma-separated list of IDs
            $deleteQuery = "DELETE FROM {$fc_cron_logs_table} WHERE id IN ({$idList})";
            $wpdb->query($deleteQuery);
        }
    }
}
