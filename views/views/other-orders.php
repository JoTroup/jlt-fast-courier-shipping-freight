<?php
// payment_pending, cancelled, refunded, order_failed, draft
global $fc_order_status, $shippingTypes, $fc_holiday_file_path;

$filters = fc_format_order_filters($_GET);
if (!isHposEnabled()) {
    $filters['fc_order_status'] = 'other';
    $query = new WC_Order_Query($filters);
} else {
    $customFilter = [];
    $customFilter['fc_order_status'] = 'other';
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

$orderStatus = isset($_GET['fc_order_status']) ? sanitize_text_field($_GET['fc_order_status']) : esc_html($fc_order_status['unprocessed']['key']);
$portal_url = is_test_mode_active() ? $GLOBALS['api_origin'] : $GLOBALS['prod_api_origin'];

$pickupLocationErrorMessage = 'Click <a class="fc-default-color" href="' . $portal_url . 'plugin-shipments?order_type=new" target="_blank">here</a> to update the pickup address from the portal.';
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

<form name="selectedOrders">
    <table class="table fc-table">
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Date</th>
                <th>Customer</th>
                <th>Ship To</th>
                <th>Status</th>
                <th>Remarks</th>
                <th>Total</th>
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
                $pickupLocationError = false;
                $isMultipleCourierOrders = $order->get_meta('fc_multiple_quotes');
                $quote = $order->get_meta('fc_order_quote');

                if (!is_array($quote)) {
                    while (!is_array($quote)) {
                        $quote = json_decode($quote, true);
                    }
                }

                $packs = json_decode($order->get_meta('fc_order_packages'), true);
                if (!is_array($packs)) $packs = json_decode($packs, true);

                $collectionDate = $order->get_meta('fc_collection_date');
                $remarks = $order->get_meta('fc_fail_reason') ?? '';
                if ($remarks != "" && str_contains($remarks, "pickup")) {
                    $remarks .= "<br>" . $pickupLocationErrorMessage;
                    $pickupLocationError = true;
                }
            ?>
                <tr <?php if ($isMultipleCourierOrders) { ?> class="order-parent-row" data-parent-id="<?= esc_html($order->get_id()) ?>" <?php } ?>>
                    <td class="no-click-effect"><a class="text-primary" href='?page=<?php echo esc_html($_GET['page']) ?>&order_id=<?php echo esc_html($order->get_id()) ?>'>#<?php echo esc_html($order->get_id()) ?></a></td>
                    <td><?php echo date('d M, y H:i', strtotime($order->get_date_created())) ?></td>
                    <td><?php echo esc_html(($order->get_shipping_first_name()) ? $order->get_shipping_first_name() : $order->get_billing_first_name()) ?> <?php echo esc_html(($order->get_shipping_last_name()) ? $order->get_shipping_last_name() : $order->get_billing_last_name()) ?></td>
                    <td>
                        <?php echo html_entity_decode(esc_html(($order->get_formatted_shipping_address()) ? $order->get_formatted_shipping_address() : $order->get_formatted_billing_address())) ?>
                    </td>
                    <td><?php
                        if ($isMultipleCourierOrders) {
                            echo "--";
                        } else {
                            echo fc_order_status_chip($order->get_meta('fc_status'));
                        }
                        ?></td>
                    <td><?php if ($isMultipleCourierOrders) {
                            echo "--";
                        } else {
                            echo $remarks;
                        } ?></td>
                    <td>$<?php echo esc_html($order->get_total()) ?></td>
                </tr>
                <?php if ($isMultipleCourierOrders) {
                    foreach ($quote as $childOrder) {
                        $pickupLocationError = false;
                        $remarks = $childOrder['data']['fc_fail_reason'] ?? '';
                        if ($remarks != "" && str_contains($remarks, "pickup")) {
                            $remarks .= $pickupLocationErrorMessage;
                            $pickupLocationError = true;
                        }
                ?>
                        <tr class="fc-bg-color-light-gray expand-children order-child-row order-child-row-<?= esc_html($order->get_id()) ?>">
                            <td></td> <!-- Order Id -->
                            <td><?php echo date('d M, y H:i', strtotime($order->get_date_created())) ?></td>
                            <td></td> <!-- Customer name -->
                            <td></td> <!-- Shipping address -->
                            <td><?php
                                if ($childOrder['data'] && isset($childOrder['data']['fc_status'])) {
                                    echo fc_order_status_chip($childOrder['data']['fc_status']);
                                } else {
                                    echo fc_order_status_chip($order->get_meta('fc_status'));
                                }
                                ?></td> <!-- Status -->
                            <td><?php echo $remarks ?></td> <!-- Remarks -->
                            <td></td> <!-- Total -->
                        </tr>
            <?php
                    }
                }
            }
            ?>
        </tbody>
    </table>
</form>
<?php
fc_pagination($totalPages);
?>


<script>
    function updateDateField(date_type) {
        var dateField = $('[name="date_created"]');
        let dates = [];
        var date = jQuery(`#${date_type}`).val();

        if (!dateField.val()) {
            dateField.val(date);
        } else {
            dates = dateField.val().split("...");

            if (date_type == 'from_date') {
                dates[0] = date;
            } else if (date_type == 'to_date') {
                dates[1] = date;
            }
            dateField.val(dates.join('...'));
        }
    }
    $(document).ready(function() {
        // On click of the parent row, toggle the children rows
        $('.order-parent-row').on('click', function(event) {
            event.stopPropagation();
            if ($(event.target).hasClass('no-click-effect')) {} else {
                var childId = $(this).data('parent-id');
                $(this).nextAll('.order-child-row-' + childId).toggle('slow');
                $(this).find('i').toggleClass('fa-chevron-down fa-chevron-up');
            }
        });
    });
</script>