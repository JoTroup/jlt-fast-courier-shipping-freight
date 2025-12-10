<?php

global $shippingTypes;

$filters = fc_format_order_filters($_GET);
if (!isHposEnabled()) {
    $filters['fc_order_status'] = ORDER_TYPE_FLATRATE;
    $query = new WC_Order_Query($filters);
} else {
    $customFilter = [];
    $customFilter['fc_order_status'] = ORDER_TYPE_FLATRATE;
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
        <div class="col-sm-2">
            <label class="mb-1">Order Shipping Type</label>
            <select name="fc_order_shipping_type" class="form-control inputwidth">
                <option value="">All</option>
                <option <?php if (@$_GET['fc_order_shipping_type'] == SHIPPING_TYPE_FREE) {
                            echo "selected";
                        } ?> value="free"><?php echo $shippingTypes['free']; ?></option>
                <option <?php if (@$_GET['fc_order_shipping_type'] == SHIPPING_TYPE_PAID) {
                            echo "selected";
                        } ?> value="paid"><?php echo $shippingTypes['paid']; ?></option>
                <option <?php if (@$_GET['fc_order_shipping_type'] == SHIPPING_TYPE_PARTIALLY_FREE) {
                            echo "selected";
                        } ?> value="partially_free"><?php echo $shippingTypes['partially_free']; ?></option>
            </select>
        </div>

        <div class="col-sm-2 d-flex align-items-end justify-content-end">
            <a href="<?php echo esc_url(admin_url("admin.php?page=" . ($_GET['page']))) ?>" class="btn btn-outline-primary pull-right mt-2">Reset</a>
            <button type="submit" class="btn btn-outline-primary pull-right mt-2 mx-2">Filter</button>
        </div>
    </div>
</form>
<div class="col-sm-3">
    <button class="btn btn-primary mt-2 mx-2"
        style="visibility:hidden;"
        onclick="">Book Selected Orders</button>
</div>

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
                <th>Packages</th>
                <th>Shipping type</th>
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
                if ($packs && !is_array($packs)) $packs = json_decode($packs, true);

                $collectionDate = $order->get_meta('fc_collection_date');
                $remarks = $order->get_meta('fc_fail_reason');
            ?>
                <tr>

                    <!-- <td>
                        <input type="checkbox" value="<?php echo esc_html($order->get_id()) ?>" name="orders[]">
                    </td> -->
                    <td><a class="text-primary" href='?page=<?php echo esc_html($_GET['page']) ?>&order_id=<?php echo esc_html($order->get_id()) ?>'>#<?php echo esc_html($order->get_id()) ?></a></td>
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
                    <!-- <td>
                        <a class="btn btn-warning" href="<?php echo esc_html($order->get_edit_order_url()) ?>"><i class="fa-solid fa-pen-to-square"></i></a>
                    </td> -->
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

<div class="modal">
    <div class="modal-dailog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="order-modal-title">Process</h3>
            </div>
            <div class="modal-body">
                <form action="" name="selectedOrders">
                    <div class="form-group">
                        <label>Collection Date</label>
                        <input type="date" min="<?php echo esc_attr($date) ?>" max="<?php echo esc_attr($maxDate) ?>" onchange="verifyDate()" name="collection_date" id="collection_date" class="form-control">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-light" onclick="toggleStatusModal()">Close</button>
                <button class="btn btn-primary ml-2" onclick="processOrders()">Submit</button>
            </div>
        </div>
    </div>
</div>

<script>
    $("#checkAll").change(function() {
        $("input:checkbox").prop('checked', $(this).prop("checked"));
    });

    function toggleStatusModal() {
        const selectedOrders = $("[name='orders[]']:checked").length;
        if (!$('.modal').hasClass('show') && !selectedOrders) {
            Swal.fire('', 'Please choose at least 1 order', 'error');
            return;
        }

        $('#selectOrders').text($("[name='orders[]']:checked").length);

        $('.modal').toggleClass('show d-flex align-items-center justify-content-center');
    }

    function verifyDate() {
        const picker = document.getElementById('collection_date');
        if (!picker.value) {
            Swal.fire('', 'Please select collected date', 'error');
            return false;
        }

        var day = new Date(picker.value).getUTCDay();

        if ([6, 0].includes(day)) {
            Swal.fire('', 'Weekends not allowed', 'error');

            picker.value = '';
            return false;
        }


        const date = new Date();
        if (new Date(picker.value) < date) {
            Swal.fire('', 'Please choose future dates only', 'error');

            picker.value = '';
            return false;
        }

        date.setDate(date.getDate() + 7);
        if (date < new Date(picker.value)) {
            Swal.fire('', 'Please choose date between 7 days', 'error');

            picker.value = '';
            return false;
        }

        return true;
    }

    function processOrders() {
        toggleLoader();

        if (!verifyDate()) {
            toggleLoader();

            return;
        }
        var ajaxurl = '<?php echo WP_API_URL; ?>';

        const orders = $("[name='selectedOrders']").serialize();
        const params = `action=post_process_orders&${orders}&is_reprocessing=false`;

        $.post(ajaxurl, params, function(result) {

            if (result.status == 200) {
                $('form').trigger('reset');

                toggleStatusModal();

                Swal.fire('', result.message, 'success').then(function() {
                    location.reload();
                });
            } else {
                Swal.fire('', result.message, 'error').then(() => {
                    toggleLoader();
                });
            }
        });

    }

    $("[data-dismiss='modal']").on("click", function() {
        const modal = $(this).attr("data-target");

        jQuery(modal).fadeOut(500);

        setTimeout(() => {
            jQuery(modal).removeClass("show d-flex");
        }, 500);
    });

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
</script>