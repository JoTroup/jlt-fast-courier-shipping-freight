<?php
// Rejected orders screen
global $fc_order_status, $shippingTypes, $fc_holiday_file_path;

$filters = fc_format_order_filters($_GET);

if (!isHposEnabled()) {
    $filters['fc_is_reprocessable'] = '1';
    $filters['fc_order_status'] = 'all';
    $query = new WC_Order_Query($filters);
} else {
    $customFilter = [];
    $customFilter['fc_is_reprocessable'] = '1';
    $customFilter['fc_order_status'] = 'all';
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
// polutate holidays
populate_fc_holidays();
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
    <button class="btn btn-primary mt-2 mx-2" onclick="toggleStatusModal()">Book Selected Orders</button>
</div>

<form name="selectedOrders">
    <table class="table fc-table">
        <thead>
            <tr>
                <th><input type="checkbox" id="checkAll"></th>
                <th>Order ID</th>
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
                $pickupLocationError = false;
                $isMultipleCourierOrders = $order->get_meta('fc_multiple_quotes');
                $quote = $order->get_meta('fc_order_quote');

                if (!is_array($quote)) {
                    $quote = json_decode($quote, true);
                }

                $packs = json_decode($order->get_meta('fc_order_packages'), true);
                if ($packs && !is_array($packs)) $packs = json_decode($packs, true);

                $collectionDate = $order->get_meta('fc_collection_date');
                $remarks = $order->get_meta('fc_fail_reason') ?? '';
                if ($remarks != "" && str_contains($remarks, "pickup")) {
                    $remarks .= "<br>" . $pickupLocationErrorMessage;
                    $pickupLocationError = true;
                }
            ?>
                <tr <?php if ($isMultipleCourierOrders) { ?> class="order-parent-row" data-parent-id="<?= esc_html($order->get_id()) ?>" <?php } ?>>
                    <td> <?php if (!$isMultipleCourierOrders && !$pickupLocationError) { ?>
                            <input type="checkbox" class="fc-new-order-checkbox" value="<?php echo esc_html($order->get_id()) ?>" name="orders[]">
                        <?php } ?>
                    </td>
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
                    <td><?php if ($isMultipleCourierOrders) {
                            echo "--";
                        } else {
                            echo esc_html(is_array($packs) ? count($packs) : 1);
                        } ?>
                        <?php
                        if ($quote && !is_array($quote)) {
                            $quote = json_decode($quote, true);
                        }
                        $pickUpLocationSuburb = $pickUpLocationState = [];
                        if (!isset($quote['status'])) {
                            foreach ($quote ?? [] as $key => $quote_item) {
                                if (!empty($quote_item['location'])) {
                                    $pickUpLocationSuburb[] = $quote_item['location']['suburb'];
                                    $pickUpLocationState[] = $quote_item['location']['state'];
                                }
                            }
                        } else {
                            $pickUpLocationSuburb[] = $quote['location']['suburb'];
                            $pickUpLocationState[] = $quote['location']['state'];
                        }
                        ?>
                        <input type="hidden" class="pickupLocationInfo" data-pickupSuburb="<?php echo esc_attr(implode(',', $pickUpLocationSuburb)) ?>" data-pickupState="<?php echo esc_attr(implode(',', $pickUpLocationState)) ?>">
                    </td>
                    <td>
                        <?php echo esc_html($shippingTypes[$order->get_meta('fc_order_shipping_type')] ?? '-') ?>
                    </td>
                    <?php
                    if ($isMultipleCourierOrders) {
                        echo '<td class="text-center fc-toggle-icon"><i class="fas fa-chevron-down"></i></td>';
                    } else {
                    ?>
                        <td>
                            <?php if (!$pickupLocationError) { ?>
                                <a class="btn btn-warning" href="<?php echo esc_html($order->get_edit_order_url()) ?>"><i class="fa-solid fa-pen-to-square"></i></a>
                            <?php } ?>
                        </td>
                    <?php } ?>
                </tr>
                <?php if ($isMultipleCourierOrders) {
                    if (!is_array($quote)) {
                        $quote = json_decode($quote, true);
                    }

                    foreach ($quote as $childOrder) {
                        $pickupLocationError = false;
                        $remarks = $childOrder['data']['fc_fail_reason'] ?? '';
                        if ($remarks != "" && str_contains($remarks, "pickup")) {
                            $remarks .= $pickupLocationErrorMessage;
                            $pickupLocationError = true;
                        }
                ?>
                        <tr class="fc-bg-color-light-gray expand-children order-child-row order-child-row-<?= esc_html($order->get_id()) ?>">
                            <td>
                                <?php if ($childOrder['data']['fc_status'] == 'order_rejected' && !$pickupLocationError) { ?>
                                    <input type="checkbox" class="fc-new-order-checkbox" value="<?php echo esc_html($order->get_id()) ?>" name="orders[]">
                                    <input type="hidden" value="<?php echo esc_html($childOrder['data']['orderHashId']) ?>" name="selectedHashId[]">
                                <?php } ?>
                            </td>
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
                            <td><?php echo count($childOrder['packages']); ?></td> <!-- Packages -->
                            <td></td> <!-- Shipping Type -->
                            <td>
                                <?php if ($childOrder['data']['fc_status'] == 'order_rejected' && !$pickupLocationError) { ?>
                                    <a class="btn btn-warning" href="<?php echo esc_html($order->get_edit_order_url()) ?>"><i class="fa-solid fa-pen-to-square"></i></a>
                                <?php } ?>
                            </td>
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

$date = date('Y-m-d');
$maxDate = date('Y-m-d', strtotime('+7days', strtotime($date)));

// Fetch public holidays
$holidaysArray = [];
// Check if the file exists
if (file_exists($fc_holiday_file_path)) {
    // get the holidays from the file
    $holidaysArray = file_get_contents($fc_holiday_file_path);
}
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
                        <input type="date" min="<?php echo esc_attr($date) ?>" onchange="verifyDate()" name="collection_date" id="collection_date" class="form-control">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-light" data-action="close-date-modal" onclick="toggleStatusModal(this)">Close</button>
                <button class="btn btn-primary ml-2" onclick="processOrders()">Submit</button>
            </div>
        </div>
    </div>
</div>

<script>
    $("#checkAll").change(function() {
        $("input:checkbox").prop('checked', $(this).prop("checked"));
        if ($(this).prop("checked")) {
            collectSelectedPickupLocations();
        }
    });

    var pickupSuburb = '';
    var pickupState = '';
    var holidayDatesArray = [];

    function makeHolidaysArray() {
        holidayDatesArray = [];
        const holidayData = <?php echo !empty($holidaysArray) ? $holidaysArray : []; ?>;

        let pickupStateList = pickupState.split(',').map(state => state.trim());
        let pickupSuburbList = pickupSuburb.split(',').map(suburb => suburb.trim());
        pickupStateList.forEach(state => {
            if (holidayData[state]) {
                holidayData[state].forEach(date => {
                    if (!holidayDatesArray.includes(date)) {
                        holidayDatesArray.push(date);
                    }
                });
            }
        });

        pickupSuburbList.forEach(suburb => {
            for (let cityName in holidayData.Cities) {
                if (suburb.includes(cityName) && $.inArray(holidayData.Cities[cityName].state, pickupStateList) !== -1) {
                    holidayData.Cities[cityName].dates.forEach(date => {
                        if (!holidayDatesArray.includes(date)) {
                            holidayDatesArray.push(date);
                        }
                    });
                }
            }
        });

    }

    function collectSelectedPickupLocations() {
        let selectedPickupSuburb = [];
        let selectedPickupState = [];
        $('.fc-new-order-checkbox:checked').each(function() {
            let hiddenInput = $(this).closest('tr').find('.pickupLocationInfo');
            let selectedPickupSuburbValue = hiddenInput.attr('data-pickupSuburb');
            let selectedPickupStateValue = hiddenInput.attr('data-pickupState');

            selectedPickupSuburb.push(selectedPickupSuburbValue);
            selectedPickupState.push(selectedPickupStateValue);
        });

        pickupSuburb = selectedPickupSuburb.join(', ');
        pickupState = selectedPickupState.join(', ');

        makeHolidaysArray();
    }

    $(document).on('change', '.fc-new-order-checkbox', function() {
        collectSelectedPickupLocations();
    });

    function toggleStatusModal(_this) {
        const selectedOrders = $("[name='orders[]']:checked").length;
        if (!$('.modal').hasClass('show') && !selectedOrders) {
            Swal.fire('', 'Please choose at least 1 order', 'error');
            return;
        }

        $('#selectOrders').text($("[name='orders[]']:checked").length);

        var action = $(_this).data('action');
        if (action == 'close-date-modal') {
            let dPicker = document.getElementById('collection_date');
            dPicker.value = '';
        }

        $('.modal').toggleClass('show d-flex align-items-center justify-content-center');
    }

    function verifyDate() {
        const picker = document.getElementById('collection_date');

        const holidaysArray = holidayDatesArray;

        if (!picker.value) {
            Swal.fire('', 'Please select collected date', 'error');
            return false;
        }

        var day = new Date(picker.value).getUTCDay();

        if ([6, 0].includes(day)) {
            Swal.fire('', 'Bookings are not available on weekends. Kindly select a different date', 'error');

            picker.value = '';
            return false;
        }

        if (holidaysArray.includes(picker.value)) {
            Swal.fire('', 'Bookings are not available on public holidays. Kindly select a different date', 'error');

            picker.value = '';
            return false;
        }

        const date = new Date();
        if (new Date(picker.value) < date) {
            Swal.fire('', 'Please choose future dates only', 'error');

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
        const params = `action=post_process_orders&${orders}&is_reprocessing=true`;

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