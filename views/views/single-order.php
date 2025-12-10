<?php

use FastCourier\FastCourierOrders;

global $shippingTypes;

$packages = json_decode($order['order_meta']['packages'], true);
if (!is_array($packages)) {
    $packages = json_decode($packages, true);
}

$quotes = $order['order_meta']['quote'];
if (!is_array($quotes)) {
    while (!is_array($quotes)) {
        $quotes = json_decode($quotes, true);
    }
}

$is_atl = false;
foreach ($quotes as $quote) {
    if (isset($quote['atl'])) {
        $is_atl = true;
    }
}

$docsPrefix = $order['order_meta']['fc_order_doc_prefix'];
function isOrderRejected($status)
{
    global $fc_order_status;
    return $status == $fc_order_status['order_rejected']['key'];
}

$isMultipleQuotes = $order['order_meta']['fc_multiple_quotes'] ?? false;
?>
<style>
    .fc3-title a {
        all: unset;
        cursor: pointer;
        font-size: 16px;
    }

    .copy-icon {
        cursor: pointer;
        margin-left: 10px;
    }
</style>
<div class="container-fluid mb-4">
    <div class="row">
        <div class="col-sm-4">
            <ul class="p-0 m-0 order-details">
                <li>Order: <span>#<?php echo esc_html($order['order_meta']['order_id']) ?></span></li>
                <li>Store Name: <span><?php echo esc_html($order['order_meta']['merchant_details']->pickupCompanyName ?? '') ?></span></li>
                <li>Payment Method: <span><?php echo esc_html(strtoupper($order['order_meta']['payment_method'])) ?></span></li>
                <li>Total: <span>$<?php echo esc_html($order['order_meta']['order_total']) ?></span></li>
                <li>Insurance Required: <span><?php echo esc_html(FastCourierOrders::orderInsurance(@$order['order_meta']['order_total'], @$quotes['data']['priceIncludingGst'])) ?></span></li>
                <li>Authority to Leave: <span><?php echo $is_atl ? 'True' : 'False'; ?></span></li>
                <?php if (!$isMultipleQuotes && $order['order_meta']['fc_tracking_url']) { ?>
                    <li class="fc3-title"><a href="<?php echo esc_html($order['order_meta']['fc_tracking_url']) ?>" target="_blank">Tracking Link</a></li>
                <?php } ?>
            </ul>
        </div>

        <div class="col-sm-4">
            <h4 class="order-modal-title">
                Delivery Address
                <?php
                if (isOrderRejected($order['order_meta']['fc_status'])) {
                ?>
                    <a class="" href="<?php echo esc_html($order['order_meta']['woo_url']) ?>"><i class="fa-solid fa-pen-to-square"></i></a>
                <?php
                }
                ?>
            </h4>
            <p class="order-details">
                <?php echo html_entity_decode(esc_html(str_replace("<br/>", ",<br/>", $order['billing']))) ?>
            </p>
        </div>

        <div class="col-sm-4">
            <h4 class="order-modal-title">Shipping Address</h4>
            <p class="order-details"><?php echo $order['shipping'] ?></p>
        </div>
    </div>
</div>

<?php
if (!is_array($quotes)) {
    $quotes = json_decode($quotes, true);
    foreach ($quotes as $key => $quote) {
?>
        <div class="table-container mb-5">
            <table class="fc3-table">
                <tr>
                    <th colspan="8" class="fc3-title fs-4">Shipment <?php echo $key + 1 ?> <span style="color: #212529;"> <?php echo (isset($quote['shipping_type'])) ? esc_html($shippingTypes[$quote['shipping_type']]) : ''; ?> </span> </th>
                </tr>
                <tr>
                    <th class="w-350px">Item</th>
                    <th>SKU</th>
                    <th>Cost</th>
                    <th>Quantity</th>
                    <th>Weight (KGs)</th>
                    <th>Dimensions</th>
                    <th>Shipping Required</th>
                    <th>Total</th>
                </tr>
                <?php
                foreach ($quote['items'] as $item) {
                ?>

                    <tr>
                        <td><?php echo esc_html($item['name']) ?></td>
                        <td><?php echo esc_html($item['sku']) ?></td>
                        <td>$<?php echo esc_html($item['cost']) ?></td>
                        <td><?php echo esc_html($item['quantity']) ?></td>
                        <td><?php echo esc_html($item['weight']) ?? 'N/A' ?></td>
                        <td>
                            <?php echo esc_html($item['length']) ?> x
                            <?php echo esc_html($item['width']) ?> x
                            <?php echo esc_html($item['height']) ?>
                        </td>
                        <td><?php echo !empty($quote['data']) ? "Yes" : "--" ?></td>
                        <td>$<?php echo esc_html($item['total']) ?></td>
                    </tr>

                <?php
                }
                ?>

                <tr>
                    <th colspan="8" class="fc3-title">Recommended Package</th>
                </tr>

                <tr>
                    <th colspan="2">Package Type</th>
                    <th colspan="2">Weight (KGs)</th>
                    <th colspan="2">Dimensions (CMs)</th>
                    <th colspan="2">Sub Packs</th>
                </tr>
                <?php
                foreach ($quote['packages'] as $package) {
                ?>
                    <tr>
                        <td colspan="2"><?php echo esc_html($package['package_name']) ?></td>
                        <td colspan="2"><?php echo isset($package['weight']) ? esc_html($package['weight']) : 'N/A' ?></td>
                        <td colspan="2"><?php echo esc_html($package['length']) ?> x <?php echo esc_html($package['width']) ?> x <?php echo esc_html($package['height']) ?></td>
                        <td colspan="2"><?php echo esc_html(isset($package['sub_packs']) ? count($package['sub_packs']) : 0) ?></td>
                    </tr>
                <?php
                }
                ?>

                <tr>
                    <th colspan="8" class="fc3-title">Shipping</th>
                </tr>

                <tr>
                    <th>Reference Number</th>
                    <th>Consignment Number</th>
                    <th>Courier</th>
                    <th>Price</th>
                    <th>Estimated Delivery</th>
                    <th>Location</th>
                    <th colspan="1">Address</th>
                    <th>Suburb, Postcode, State</th>
                </tr>
                <?php if (isset($quote['location']['name'])) {
                    $location = $quote['location']['name'];
                } else {
                    $location = $quote['location']['location_name'];
                }
                $notApplicable = null;
                if (empty($quote['data'])) {
                    $notApplicable = "--";
                }
                ?>
                <tr>
                    <td>#<?php echo $notApplicable ?? esc_html($quote['data']['orderHashId']) ?></td>
                    <?php $consignmentInfo;
                    $pdfURL;
                    $consignmentAvailable = false;
                    if ($docsPrefix) {
                        $docsPrefix = $docsPrefix;
                    } elseif (($quote['data']) && isset($quote['data']['fc_order_doc_prefix']) && !empty($quote['data']['fc_order_doc_prefix'])) {
                        $docsPrefix = $quote['data']['fc_order_doc_prefix'];
                    }

                    if ($notApplicable) {
                        $consignmentInfo = $notApplicable;
                    } elseif (isset($quote['data']['fc_consignment_number']) && !empty($quote['data']['fc_consignment_number'])) {
                        $consignmentInfo = esc_html($quote['data']['fc_consignment_number']);
                        $pdfURL = $quote['data']['fc_order_label'];
                        $consignmentAvailable = true;
                    } elseif (isset($order['order_meta']['fc_consignment_number']) && !empty($order['order_meta']['fc_consignment_number'])) {
                        $consignmentInfo = esc_html($order['order_meta']['fc_consignment_number']);
                        $pdfURL = $order['order_meta']['fc_order_label'];
                        $consignmentAvailable = true;
                    } else {
                        $consignmentInfo = 'N/A';
                    } ?>
                    <td>
                        <?php if ($consignmentAvailable) { ?>
                            <a href="#" class="label-link fc3-title" style="text-decoration: none;" data-pdf-url="<?php echo $docsPrefix . $pdfURL; ?>"><?php echo $consignmentInfo; ?></a>
                            <i class="fas fa-copy copy-icon"></i>
                        <?php } else {
                            echo $consignmentInfo;
                        } ?>
                    </td>
                    <td><?php echo $notApplicable ?? esc_html($quote['data']['courierName']) ?></td>
                    <td>$<?php echo $notApplicable ?? esc_html($quote['data']['priceIncludingGst']) ?></td>
                    <td><?php echo $notApplicable ?? esc_html($quote['data']['eta'] ?? 'N/A') ?></td>
                    <td><?php echo $notApplicable ?? esc_html($location) ?></td>
                    <td colspan="1"><?php echo $notApplicable ?? esc_html($quote['location']['address1'] . ' ' . $quote['location']['address2']) ?></td>
                    <td><?php echo $notApplicable ?? esc_html($quote['location']['suburb'] . ', ' . $quote['location']['postcode']) . ' (' . $quote['location']['state'] . ')' ?></td>
                </tr>
            </table>
        </div>
    <?php
    }
} else {

    foreach ($quotes as $key => $quote) {
    ?>
        <div class="table-container mb-5">
            <table class="fc3-table">
                <tr>
                    <?php
                    $colSpan = 8;
                    $th = '';
                    if ($isMultipleQuotes && isset($quote["data"]["fc_tracking_url"]) && !empty($quote["data"]["fc_tracking_url"])) {
                        $colSpan = 7;
                        $th = '<th class="fc3-title"><a href="' . esc_html($quote["data"]["fc_tracking_url"]) . '" target="_blank">Tracking Link</a></th>';
                    }
                    ?>
                    <th colspan="<?php echo $colSpan ?>" class="fc3-title fs-4">Shipment <?php echo $key + 1 ?> <span style="color: #212529;"> <?php echo (isset($quote['shipping_type'])) ? esc_html($shippingTypes[$quote['shipping_type']]) : ''; ?> </span></th>
                    <?php echo $th ?>
                </tr>

                <tr>
                    <th class="w-350px">Item</th>
                    <th>SKU</th>
                    <th>Cost</th>
                    <th>Quantity</th>
                    <th>Weight (KGs)</th>
                    <th>Dimensions</th>
                    <th>Shipping Required</th>
                    <th>Total</th>
                </tr>
                <?php
                if (!empty($quote['items'])) {
                    foreach ($quote['items'] as $item) {
                ?>
                        <tr>
                            <td><?php echo esc_html($item['name']) ?></td>
                            <td><?php echo esc_html($item['sku']) ?></td>
                            <td>$<?php echo esc_html($item['cost']) ?></td>
                            <td><?php echo esc_html($item['quantity']) ?></td>
                            <td><?php echo esc_html($item['weight']) ?? 'N/A' ?></td>
                            <td>
                                <?php echo esc_html($item['length']) ?> x
                                <?php echo esc_html($item['width']) ?> x
                                <?php echo esc_html($item['height']) ?>
                            </td>
                            <td><?php echo !empty($quote['data']) ? "Yes" : "--" ?></td>
                            <td>$<?php echo esc_html($item['total']) ?></td>
                        </tr>

                <?php
                    }
                }
                ?>

                <tr>
                    <th colspan="8" class="fc3-title">Recommended Package</th>
                </tr>
                <tr>
                    <th colspan="2">Package Type</th>
                    <th colspan="2">Weight (KGs)</th>
                    <th colspan="2">Dimensions (CMs)</th>
                    <th colspan="2">Sub Packs</th>
                </tr>

                <?php
                if (!empty($quote['packages'])) {
                    foreach ($quote['packages'] as $pack) {
                ?>

                        <tr>
                            <td colspan="2"><?php echo esc_html($pack['package_name'] ?? $pack['name']) ?></td>
                            <td colspan="2"><?php echo isset($pack['weight']) ? esc_html($pack['weight']) : 'N/A' ?></td>
                            <td colspan="2"><?php echo esc_html($pack['length']) ?> x <?php echo esc_html($pack['width']) ?> x <?php echo esc_html($pack['height']) ?></td>
                            <td colspan="2"><?php echo esc_html(isset($pack['sub_packs']) ? count($pack['sub_packs']) : 0) ?></td>
                        </tr>
                <?php
                    }
                }
                ?>
                <tr>
                    <th colspan="8" class="fc3-title">Shipping</th>
                </tr>
                <tr>
                    <th>Reference Number</th>
                    <th>Consignment Number</th>
                    <th>Courier</th>
                    <th>Price</th>
                    <th>Estimated Delivery</th>
                    <th>Location</th>
                    <th colspan="1">Address</th>
                    <th>Suburb, Postcode, State</th>
                </tr>
                <tr>
                    <?php
                    $notApplicable = null;
                    if (empty($quote['data'])) {
                        $notApplicable = "--";
                    }
                    ?>
                    <td><?php echo $notApplicable ?? esc_html($quote['data']['orderHashId']) ?></td>
                    <?php $consignmentInfo;
                    $pdfURL;
                    $consignmentAvailable = false;
                    if ($docsPrefix) {
                        $docsPrefix = $docsPrefix;
                    } elseif (($quote['data']) && isset($quote['data']['fc_order_doc_prefix']) && !empty($quote['data']['fc_order_doc_prefix'])) {
                        $docsPrefix = $quote['data']['fc_order_doc_prefix'];
                    }

                    if ($notApplicable) {
                        $consignmentInfo = $notApplicable;
                    } elseif (isset($quote['data']['fc_consignment_number']) && !empty($quote['data']['fc_consignment_number'])) {
                        $consignmentInfo = esc_html($quote['data']['fc_consignment_number']);
                        $pdfURL = $quote['data']['fc_order_label'];
                        $consignmentAvailable = true;
                    } elseif (isset($order['order_meta']['fc_consignment_number']) && !empty($order['order_meta']['fc_consignment_number'])) {
                        $consignmentInfo = esc_html($order['order_meta']['fc_consignment_number']);
                        $pdfURL = $order['order_meta']['fc_order_label'];
                        $consignmentAvailable = true;
                    } else {
                        $consignmentInfo = 'N/A';
                    } ?>
                    <td>
                        <?php if ($consignmentAvailable) { ?>
                            <a href="#" class="label-link fc3-title" style="text-decoration: none;" data-pdf-url="<?php echo $docsPrefix . $pdfURL; ?>"><?php echo $consignmentInfo; ?></a>
                            <i class="fas fa-copy copy-icon"></i>
                        <?php } else {
                            echo $consignmentInfo;
                        } ?>
                    </td>
                    <td><?php echo $notApplicable ?? esc_html($quote['data']['courierName']) ?></td>
                    <td><?php echo $notApplicable ?? "$" . esc_html($quote['data']['priceIncludingGst']) ?></td>
                    <td><?php echo $notApplicable ?? esc_html($quote['data']['eta'] ?? 'N/A') ?></td>
                    <?php
                    if (isset($quote['location'])) {
                        if (isset($quote['location']['name'])) {
                            $location = $quote['location']['name'];
                        } else {
                            $location = $quote['location']['location_name'];
                        }
                    ?>
                        <td><?php echo $notApplicable ?? esc_html($location) ?></td>
                        <td colspan="1"><?php echo $notApplicable ?? esc_html($quote['location']['address1'] . ' ' . $quote['location']['address2']) ?></td>
                        <td><?php echo $notApplicable ?? esc_html($quote['location']['suburb'] . ', ' . $quote['location']['postcode']) . ' (' . $quote['location']['state'] . ')' ?></td>
                    <?php } else { ?>
                        <td></td>
                        <td colspan="1"></td>
                        <td></td>
                    <?php } ?>
                </tr>
            </table>
        </div>
<?php
        unset($notApplicable);
    }
}
?>
<!-- Check label PDF Modal start -->
<div class="modal fade" id="pdfModal" tabindex="-1" aria-labelledby="pdfModalLabel" aria-hidden="true">
    <div class="modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="pdfModalLabel">PDF Document</h5>
            </div>
            <div class="modal-body">
                <iframe id="pdfIframe" src="" style="min-width: 1000px; min-height: 550px" frameborder="0"></iframe>
            </div>
            <div class="modal-footer">
                <button class="btn btn-light" onclick="togglePdfModal()">Close</button>
            </div>
        </div>
    </div>
</div>
<!-- Check label PDF Modal end-->
<script>
    $(document).ready(function() {
        $('.label-link').click(function() {
            var pdfUrl = $(this).data('pdf-url');
            $('#pdfIframe').attr('src', pdfUrl);
            $('#pdfModal').toggleClass('show d-flex align-items-center justify-content-center');
        });

        $('.copy-icon').click(function(e) {
            e.stopPropagation(); // Prevent the modal from opening

            $('.copy-icon').hide();

            var linkText = $(this).siblings('a').text();
            var tempInput = $('<input>');

            $('body').append(tempInput);
            tempInput.val(linkText).select();
            document.execCommand('copy');
            tempInput.remove();

            // Display a temporary message
            var $message = $('<span class="copy-message">Copied!</span>');
            $(this).parent().append($message);

            $message.fadeIn().delay(1000).fadeOut(function() {
                $(this).remove();
                $('.copy-icon').show();
            });
        });

    });

    function togglePdfModal() {
        $('#pdfModal').toggleClass('show d-flex align-items-center justify-content-center');
    }
</script>