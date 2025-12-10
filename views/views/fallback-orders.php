<?php
global $fc_order_status, $shippingTypes;

$filters = fc_format_order_filters($_GET);
if (!isHposEnabled()) {
    $filters['is_fallbacked'] = '1';
    $query = new WC_Order_Query($filters);
} else {
    $customFilter = [];
    $customFilter['is_fallbacked'] = '1';
    $fc_keys = array_filter($filters, function ($key) {
        return strpos($key, 'fc_') === 0;
    }, ARRAY_FILTER_USE_KEY);
    if (!empty($fc_keys)) {
        $customFilter = array_merge($customFilter, $fc_keys);
    }
    $finalFilter = fc_handle_custom_query_var($filters, $customFilter);
    $query = new WC_Order_Query($finalFilter);
}

$result = $query->get_orders();
$orders = $result->orders;
$totalPages = $result->max_num_pages;

$dates = [];
if (isset($_GET['date_created'])) {
    $dates = explode('...', sanitize_text_field($_GET['date_created']));
}
$portal_url = is_test_mode_active() ? $GLOBALS['api_origin'] : $GLOBALS['prod_api_origin'];
?>
<form action="<?php echo admin_url('admin.php') ?>" method="get">
    <div class="row">
        <input type='hidden' name='page' value="<?php echo esc_html($_GET['page']) ?>">
        <input type="hidden" name="date_created" value="<?php echo esc_attr(@$_GET['date_created']) ?>">
        <div class="col-sm-2">
            <label>From Date</label>
            <input type="date" id="from_date" value="<?php echo esc_attr(@$dates[0]) ?>" class="w-100" onchange="updateDateField('from_date')">
        </div>
        <div class="col-sm-2">
            <label>To Date</label>
            <input type="date" id="to_date" value="<?php echo esc_attr(@$dates[1]) ?>" class="w-100" onchange="updateDateField('to_date')">
        </div>
        <div class="col-sm-2">
            <label>Order Id</label>
            <input type="text" name="fc_order_id" class="w-100" placeholder="Order Number" value="<?php echo esc_attr(@$_GET['fc_order_id']) ?>">
        </div>
        <div class="col-sm-2 d-flex align-items-end justify-content-end">
            <a href="<?php echo esc_url(admin_url("admin.php?page=" . ($_GET['page']))) ?>" class="btn btn-outline-primary pull-right mt-2">Reset</a>
            <button type="submit" class="btn btn-outline-primary pull-right mt-2 mx-2">Filter</button>
        </div>
    </div>
</form>
<div class="col-sm-3">
    <!-- <button class="btn btn-primary mt-2 mx-2" onclick="toggleStatusModal()">Book Selected Orders</button> -->
</div>

<form name="selectedOrders">
    <table class="table fc-table">
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Fast Courier <br /> Reference Number</th>
                <th>Date</th>
                <th>Customer</th>
                <th>Ship To</th>
                <th>Status</th>
                <th>Remarks</th>
                <th>Total</th>
                <th>Packages</th>
                <th>Shipping type</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if (!count($orders)) {
            ?>
                <tr>
                    <th colspan="12" class="text-center">
                        No Orders Available
                    </th>
                </tr>
            <?php
            }
            ?>
            <?php
            foreach ($orders as $order) {
                $quote = $order->get_meta('fc_order_quote');

                if (!is_array($quote)) {
                    $quote = json_decode($quote, true);
                }

                $packs = json_decode($order->get_meta('fc_order_packages'), true);
                if (isset($packs) && !is_array($packs)) $packs = json_decode($packs, true);

                $collectionDate = $order->get_meta('fc_collection_date');
                $remarks = $order->get_meta('fc_fail_reason');
            ?>
                <tr>
                    <td><a class="text-primary" href='#'>#<?php echo esc_html($order->get_id()) ?></a></td>
                    <td>
                        <?php
                        if ($quote && !is_array($quote)) {
                            $quote = json_decode($quote, true);
                        }
                        foreach ($quote ?? [] as $quoteItem) {
                            echo !empty($quoteItem['order_id']) ? esc_html($quoteItem['order_id']) . ' <br>' : "--" . ' <br>';
                        }
                        ?>
                    </td>
                    <td><?php echo date('d M, y H:i', strtotime($order->get_date_created())) ?></td>
                    <td><?php echo esc_html(($order->get_shipping_first_name()) ? $order->get_shipping_first_name() : $order->get_billing_first_name()) ?> <?php echo esc_html(($order->get_shipping_last_name()) ? $order->get_shipping_last_name() : $order->get_billing_last_name()) ?></td>
                    <td>
                        <?php echo html_entity_decode(esc_html(($order->get_formatted_shipping_address()) ? $order->get_formatted_shipping_address() : $order->get_formatted_billing_address())) ?>
                    </td>
                    <td><?php echo fc_order_status_chip($order->get_meta('fc_status')) ?></td>
                    <td><?php echo esc_html($remarks) ?></td>
                    <td>$<?php echo esc_html($order->get_total()) ?></td>
                    <td><?php echo is_array($packs) ? esc_html(count($packs)) : 1 ?></td>
                    <td>
                        <?php echo esc_html($shippingTypes[$order->get_meta('fc_order_shipping_type')] ?? '-') ?>
                    </td>
                    <td>
                        <a class="btn btn-primary mt-2 mx-2" href="<?php echo $portal_url ?>fallback-plugin-shipments?order_type=fallback" target="_blank">Book Order</a>
                    </td>
                </tr>
            <?php
            }
            ?>
        </tbody>
    </table>
</form>
<?php
fc_pagination($totalPages);

$date = date('Y-m-d');
$maxDate = date('Y-m-d', strtotime('+7days', strtotime($date)));
?>