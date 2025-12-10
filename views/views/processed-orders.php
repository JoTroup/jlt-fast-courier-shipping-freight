<?php

use FastCourier\FastCourierBatches;

global $fc_order_status, $shippingTypes, $fc_holiday_file_path;

$filters = fc_format_order_filters($_GET);
if (!isHposEnabled()) {
    $filters['fc_order_status'] = 'processed';
    $filters['fc_is_reprocessable'] = '0';
    $query = new WC_Order_Query($filters);
} else {
    $customFilter = [];
    $customFilter['fc_order_status'] = 'processed';
    $customFilter['fc_is_reprocessable'] = '0';
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

if (isset($_POST['download'])) {
    $urls = explode(",", sanitize_text_field($_POST['download']));

    FastCourierBatches::downloadZip($urls);
}

$orderStatus = isset($_GET['fc_order_status']) ? esc_html($_GET['fc_order_status']) : '';

$portal_url = is_test_mode_active() ? $GLOBALS['api_origin'] : $GLOBALS['prod_api_origin'];
?>
<form action="<?php echo esc_url(admin_url('admin.php')) ?>" method="get">
    <div class="row">
        <input type='hidden' name='page' value="<?php echo esc_attr($_GET['page']) ?>">
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
            <label>Order ID</label>
            <input type="text" name="fc_order_id" class="w-100" placeholder="Order ID" value="<?php echo esc_attr(@$_GET['fc_order_id']) ?>">
        </div>
        <div class="col-sm-2">
            <label>Status</label>
            <select name="fc_order_status" class="w-100 form-control">
                <option value='all'>All</option>
                <?php
                foreach ($fc_order_status as $status) {
                    if ($status['key'] == 'unprocessed') {
                        continue;
                    }
                ?>
                    <option <?php echo esc_attr($orderStatus == $status['key'] ? 'selected' : '') ?> value='<?php echo esc_attr($status['key']) ?>'><?php echo esc_html($status['status']) ?></option>
                <?php
                }
                ?>
            </select>
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
            <a href="<?php echo esc_url(admin_url("admin.php?page=" . $_GET['page'])) ?>" class="btn btn-outline-primary pull-right mt-2">Reset</a>
            <button type="submit" class="btn btn-outline-primary pull-right mt-2 mx-2">Filter</button>
        </div>
    </div>
</form>

<table class="table fc-table">
    <thead>
        <tr>
            <th>Order ID</th>
            <th>Fast Courier <br /> Reference Number</th>
            <th>Date</th>
            <th>Customer</th>
            <th>Status</th>
            <th>Total</th>
            <th>Packages</th>
            <th>Carrier Details</th>
            <th>Shipping Date</th>
            <th>Shipping type</th>
            <th class="text-center">Documents</th>
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
            $isMultipleCourierOrders = $order->get_meta('fc_multiple_quotes');

            $quote = $order->get_meta('fc_order_quote');

            if (!is_array($quote)) {
                $quote = json_decode($quote, true);
            }

            $packs = json_decode($order->get_meta('fc_order_packages'), true);
            if ($packs && !is_array($packs)) $packs = json_decode($packs, true);

            $collectionDate = $order->get_meta('fc_collection_date');
            $docsPrefix = $order->get_meta('fc_order_doc_prefix');
            $invoice = $order->get_meta('fc_order_invoice');
            $label = $order->get_meta('fc_order_label');
            $docs = $order->get_meta('fc_order_additional_docs');
            $additional = json_decode($docs ? $docs : '[]');
            $additionalDocs = [];
            if (is_array($additional)) {
                foreach ($additional as $doc) {
                    if ($doc) {
                        $additionalDocs[] = $docsPrefix . substr($doc, 1);
                    }
                }
            }
        ?>
            <tr <?php if ($isMultipleCourierOrders) { ?> class="order-parent-row" data-parent-id="<?= esc_html($order->get_id()) ?>" <?php } ?>>
                <td class="no-click-effect"><a class="no-click-effect text-primary" href='?page=<?php echo esc_html($_GET['page']) ?>&order_id=<?php echo esc_html($order->get_id()) ?>'>#<?php echo esc_html($order->get_id()) ?></a></td>

                <td>
                    <?php
                    if (isset($quote['data'])) {
                        echo esc_html($quote['data']['orderHashId']) . '<br>';
                    } else {
                        if ($isMultipleCourierOrders) {
                            echo "--";
                        } else {
                            if ($quote && !is_array($quote)) {
                                $quote = json_decode($quote, true);
                            }

                            foreach ($quote ?? [] as $quoteItem) {
                                echo !empty($quoteItem['data']) ? esc_html($quoteItem['data']['orderHashId']) . ' <br>' : "--" . ' <br>';
                            }
                        }
                    }
                    ?>
                </td>
                <td><?php echo date('d M, y H:i', strtotime($order->get_date_created())) ?></td>
                <td><?php echo esc_html(($order->get_shipping_first_name()) ? $order->get_shipping_first_name() : $order->get_billing_first_name()) ?> <?php echo esc_html(($order->get_shipping_last_name()) ? $order->get_shipping_last_name() : $order->get_billing_last_name()) ?></td>
                <td><?php
                    if ($isMultipleCourierOrders) {
                        echo "--";
                    } else {
                        echo fc_order_status_chip($order->get_meta('fc_status'));
                    }
                    ?></td>
                <td>$<?php echo esc_html($order->get_total()) ?></td>
                <td><?php
                    if ($isMultipleCourierOrders) {
                        echo "--";
                    } else {
                        echo esc_html(is_array($packs) ? count($packs) : 1);
                    } ?></td>
                <td>
                    <?php
                    if (isset($quote['data'])) {
                        echo esc_html(isset($quote['data']['priceIncludingGst']) ? '$' . $quote['data']['priceIncludingGst'] : '--') . ' (' . esc_html(isset($quote['data']['courierName']) ? $quote['data']['courierName'] : '--') . ')';
                    } else {

                        if ($isMultipleCourierOrders) {
                            echo "--";
                        } else {
                            if ($quote && !is_array($quote)) {
                                $quote = json_decode($quote, true);
                            }

                            foreach ($quote ?? [] as $quoteItem) {
                                echo esc_html(isset($quoteItem['data']['priceIncludingGst']) ? '$' . $quoteItem['data']['priceIncludingGst'] : '--') . ' (' . esc_html(isset($quoteItem['data']['courierName']) ? $quoteItem['data']['courierName'] : '--') . ')<br>';
                            }
                        }
                    }
                    ?>
                </td>

                <td><?php if ($isMultipleCourierOrders) {
                        echo "--";
                    } else {
                        echo esc_html($collectionDate ? date('d M, Y', strtotime($collectionDate)) : '--');
                    } ?></td>
                <td>
                    <?php echo esc_html($shippingTypes[$order->get_meta('fc_order_shipping_type')] ?? '-') ?>
                </td>

                <?php
                if ($isMultipleCourierOrders) {
                    echo '<td class="text-center fc-toggle-icon"><i class="fas fa-chevron-down"></i></td>';
                } else {
                ?>
                    <td>
                        <form method="post" class="d-flex justify-content-center">
                            <a class="btn btn-success mr-2 <?php echo esc_attr($label ? '' : 'disabled') ?>" title='Label' href="<?php echo esc_url($label ?  $docsPrefix . $label : '#') ?>" download target="_blank"><i class="fa-solid fa-receipt"></i></a>
                            <a class="btn btn-warning mr-2 <?php echo esc_attr($invoice ? '' : 'disabled') ?>" title='Invoice' href="<?php echo esc_url($invoice ? $docsPrefix . $invoice : '#') ?>" download target="_blank"><i class="fa-solid fa-file-invoice"></i></a>
                            <button type="submit" class="btn btn-danger <?php echo esc_attr(count($additionalDocs) ? '' : 'disabled') ?>" name="download" title='Additional Documents' value="<?php echo esc_attr(count($additionalDocs) ? implode(",", $additionalDocs) : '') ?>"><i class="fa-solid fa-file-lines"></i></button>
                        </form>
                    </td>
                <?php } ?>
            </tr>
            <?php if ($isMultipleCourierOrders) {
                if (!is_array($quote)) {
                    $quote = json_decode($quote, true);
                }
                foreach ($quote as $childOrder) { ?>
                    <tr class="fc-bg-color-light-gray expand-children order-child-row order-child-row-<?= esc_html($order->get_id()) ?>">
                        <td></td>
                        <td> <?php echo !empty($childOrder['data']) ? esc_html($childOrder['data']['orderHashId']) : "--"; ?> </td>
                        <td><?php echo date('d M, y H:i', strtotime($order->get_date_created())) ?></td>
                        <td></td> <!-- Customer name -->
                        <td><?php
                            if ($childOrder['data'] && isset($childOrder['data']['fc_status'])) {
                                echo fc_order_status_chip($childOrder['data']['fc_status']);
                            } else {
                                echo fc_order_status_chip($order->get_meta('fc_status'));
                            }
                            ?></td>
                        <td></td> <!-- Total -->
                        <td><?php echo count($childOrder['packages']); ?></td>
                        <td>
                            <?php
                            echo esc_html(isset($childOrder['data']['priceIncludingGst']) ? '$' . $childOrder['data']['priceIncludingGst'] : '--') . ' (' . esc_html(isset($childOrder['data']['courierName']) ? $childOrder['data']['courierName'] : '--') . ')';
                            ?>
                        </td>
                        <?php
                        $collectionDateForCourierOrder;
                        if (isset($childOrder['data']['fc_collection_date'])) {
                            $collectionDateForCourierOrder = esc_html(date('d M, Y', strtotime($childOrder['data']['fc_collection_date'])));
                        } elseif ($collectionDate) {
                            $collectionDateForCourierOrder = esc_html(date('d M, Y', strtotime($collectionDate)));
                        }
                        ?>
                        <td> <?php echo $collectionDateForCourierOrder; ?> </td> <!-- Shipping Date -->
                        <td></td> <!-- Shipping type -->
                        <td>
                            <?php
                            $childLabel = ($childOrder['data'] && isset($childOrder['data']['fc_order_label'])) ? $childOrder['data']['fc_order_label'] : $label;
                            $childDocsPrefix = ($childOrder['data'] && isset($childOrder['data']['fc_order_doc_prefix'])) ? $childOrder['data']['fc_order_doc_prefix'] : $docsPrefix;
                            $childInvoice = ($childOrder['data'] && isset($childOrder['data']['fc_order_invoice'])) ? $childOrder['data']['fc_order_invoice'] : $invoice;
                            $ChildAdditionalDocs = ($childOrder['data'] && isset($childOrder['data']['fc_order_additional_docs'])) ? $childOrder['data']['fc_order_additional_docs'] : $docs;

                            $childAdditional = json_decode($ChildAdditionalDocs ? $ChildAdditionalDocs : '[]');
                            $childAdditionalDocs = [];
                            if (is_array($childAdditional)) {
                                foreach ($childAdditional as $childDoc) {
                                    if ($childDoc) {
                                        $childAdditionalDocs[] = $childDocsPrefix . substr($childDoc, 1);
                                    }
                                }
                            } ?>
                            <?php if ($childOrder['data']['fc_status'] == 'order_rejected') { ?>
                                <a class="btn btn-primary mt-2 mx-2" href="<?php echo $portal_url ?>plugin-shipments?order_type=new" target="_blank">Book Order</a>
                            <?php } else { ?>
                                <form method="post" class="d-flex justify-content-center">
                                    <a class="btn btn-success mr-2 <?php echo esc_attr($childLabel ? '' : 'disabled') ?>" title='Label' href="<?php echo esc_url($childLabel ?  $childDocsPrefix . $childLabel : '#') ?>" download target="_blank"><i class="fa-solid fa-receipt"></i></a>
                                    <a class="btn btn-warning mr-2 <?php echo esc_attr($childInvoice ? '' : 'disabled') ?>" title='Invoice' href="<?php echo esc_url($childInvoice ? $childDocsPrefix . $childInvoice : '#') ?>" download target="_blank"><i class="fa-solid fa-file-invoice"></i></a>
                                    <button type="submit" class="btn btn-danger <?php echo esc_attr(count($childAdditionalDocs) ? '' : 'disabled') ?>" name="download" title='Additional Documents' value="<?php echo esc_attr(count($childAdditionalDocs) ? implode(",", $childAdditionalDocs) : '') ?>"><i class="fa-solid fa-file-lines"></i></button>
                                </form>
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