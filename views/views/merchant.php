<?php

use FastCourier\FastCourierLocation;
use FastCourier\FastCourierProducts;

global $bookingPreferences, $shippingPreferences, $defaultProcessOrderAfterMinutes, $processAfterDays, $fc_name, $defaultTailLiftKgs, $token;

$merchantDetails = json_decode(@$formattedData['fast_courier_merchant_details'], true);
$selected = is_array(@$merchantDetails['courierPreferences']) ? $merchantDetails['courierPreferences'] : json_decode($merchantDetails['courierPreferences'] ?? "[]", true);

$session = WC()->session;
$configurationCompleted = $session->get('configurationCompleted', 0);

if (!$configurationCompleted || $configurationCompleted == 0) {
    $configurationCompleted = (isset($merchantDetails['abn']) && $merchantDetails['abn'] != '') ? 1 : 0;
    $session->set('configurationCompleted', $configurationCompleted);
}
// check if default location is exist
$locationResult = FastCourierLocation::index();

//get taglist
$available_tags = FastCourierLocation::tags();

$bulkActions = FastCourierProducts::getBulkActions();

$hasMappedProducts = FastCourierProducts::has_products_with_fc_length_and_weight();
$session->__unset('configuration_completed');
$configSessionArray = [];
?>

<div class="container mt-2">
    <div class="progress">
        <div class="progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
    </div>
</div>

<div class="tabs-container mt-4 merchant-config-tabs">
    <div class="tab tab-active" href="#basic" <?php if (@$merchantDetails['billingPhone']) {
                                                    $configSessionArray['basic'] = 1; ?> tab-completed="1" <?php } ?> data-toggle="tab-basic">Basic <?php if (@$merchantDetails['billingPhone']) { ?> <i class="far fa-check-circle fa-lg"></i> <?php } else { ?> <i class="fas fa-exclamation-circle fa-lg"></i> <?php } ?> </div>
    <div class="tab" href="#payment-method" <?php if (count(array_filter($paymentMethods['data'] ?? [], fn($method) => $method['default_method'] == 1)) > 0) {
                                                $configSessionArray['payment'] = 1; ?> tab-completed="1" <?php } ?> data-toggle="tab-payment-method">Payment Methods <?php if (count(array_filter($paymentMethods['data'] ?? [], fn($method) => $method['default_method'] == 1)) > 0) { ?> <i class="far fa-check-circle fa-lg"></i> <?php } else { ?> <i class="fas fa-exclamation-circle fa-lg"></i> <?php } ?> </div>
    <div class="tab" href="#location" <?php if (is_array($locationResult) && count($locationResult) > 0) {
                                            $configSessionArray['location'] = 1; ?> tab-completed="1" <?php } ?> data-toggle="tab-location">Pickup Locations <?php if (is_array($locationResult) && count($locationResult) > 0) { ?> <i class="far fa-check-circle fa-lg"></i> <?php } else { ?> <i class="fas fa-exclamation-circle fa-lg"></i> <?php } ?> </div>
    <div class="tab" href="#product-mapping" <?php if (@$hasMappedProducts) {
                                                    $configSessionArray['product-mapping'] = 1; ?> tab-completed="1" <?php } ?> data-toggle="tab-product-mapping">Product Mapping <?php if (@$hasMappedProducts) { ?> <i class="far fa-check-circle fa-lg"></i> <?php } else { ?> <i class="fas fa-exclamation-circle fa-lg"></i> <?php } ?> </div>
</div>
<?php $session->set('configuration_completed', $configSessionArray); ?>
<div class="tab-content merchant-config-tab-content">
    <!-- Content for the selected tab goes here -->
    <div id="custom-tooltip"></div>

    <div class="tab-content-child tab-basic tab-content-active merchant-basic-config">
        <form onsubmit="activateMerchant(); return false" name='merchant-form' oninput="handleFormChange()">
            <div class="container-fluid py-3 px-2">
                <h3 class="form-title tooltip-hover w-fit-content" data-tooltip="Please enter your official business contact details and address. This is for accounting & registration purposes only">Merchant Billing Details
                    <i class="fas fa-info-circle info-icon"> </i>
                </h3>
                <div class="row mt-4">
                    <div class="col-sm-6">
                        <label for="billingFirstName" class="required-label mb-1">First Name</label>
                        <input placeholder="First Name" type="text" name="billingFirstName" value="<?php echo esc_attr(@$merchantDetails['billingFirstName']) ?>" class="form-control inputwidth" id="billingFirstName" required />
                    </div>
                    <div class="col-sm-6">
                        <label for="billingAddress1" class="required-label mb-1">Address 1</label>
                        <input placeholder="Address 1" type="text" name="billingAddress1" value="<?php echo esc_attr(@$merchantDetails['billingAddress1']) ?>" class="form-control inputwidth" id="billingAddress1" required />
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-sm-6">
                        <label for="billingLastName" class="required-label mb-1">Last Name</label>
                        <input placeholder="Last Name" type="text" name="billingLastName" value="<?php echo esc_attr(@$merchantDetails['billingLastName']) ?>" class="form-control inputwidth" id="billingLastName" required />
                    </div>
                    <div class="col-sm-6">
                        <label for="billingAddress2" class="mb-1">Address 2</label>
                        <input placeholder="Address 2" type="text" name="billingAddress2" value="<?php echo esc_attr(@$merchantDetails['billingAddress2']) ?>" class="form-control inputwidth" id="billingAddress2" />
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-sm-6">
                        <label for="billingCompanyName" class="required-label mb-1">Company Name</label>
                        <input placeholder="Company Name" type="text" name="billingCompanyName" value="<?php echo esc_attr(@$merchantDetails['billingCompanyName']) ?>" class="form-control inputwidth" id="billingCompanyName" required />
                    </div>
                    <div class="col-sm-6 fc-suburb-dropdown">
                        <label for="billingSuburb" class="required-label mb-1">Suburb, Postcode & State</label></br>
                        <?php
                        $suburb = '';
                        if ($merchantDetails['billingSuburb']) {
                            $suburb = $merchantDetails['billingSuburb'] . ', ' . $merchantDetails['billingPostcode'] . ' (' . $merchantDetails['billingState'] . ')';
                        }
                        ?>
                        <input placeholder="Search for postcode or suburb" type="text" value="<?php echo esc_attr($suburb) ?>" class="form-control fc-selected-suburb merchant-basic-config-suburb inputwidth" />
                        <ul class="wp-ajax-suburbs fc-suburb-list form-control"></ul>
                        <input placeholder="Suburb" required type="hidden" name="billingSuburb" value="<?php echo esc_attr(@$merchantDetails['billingSuburb']) ?>" class="fc-suburb" />
                        <input placeholder="State" required type="hidden" name="billingState" value="<?php echo esc_attr(@$merchantDetails['billingState']) ?>" class="fc-state" />
                        <input placeholder="Post Code" required type="hidden" name="billingPostcode" value="<?php echo esc_attr(@$merchantDetails['billingPostcode']) ?>" class="fc-postcode" />
                    </div>

                </div>

                <div class="row mt-4">
                    <div class="col-sm-6">
                        <label for="abn" class="required-label mb-1">ABN</label>
                        <input placeholder="ABN" type="text" name="abn" value="<?php echo esc_attr(@$merchantDetails['abn']) ?>" class="form-control inputwidth" id="abn" required />
                    </div>
                </div>
                <div class="row mt-4">
                    <div class="col-sm-6">
                        <label for="billingPhone" class="required-label mb-1">Contact Phone Number</label>
                        <input placeholder="Contact Phone Number" type="text" name="billingPhone" value="<?php echo esc_attr(@$merchantDetails['billingPhone']) ?>" class="form-control inputwidth" id="billingPhone" required />
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-sm-6">
                        <label for="billingEmail" class="required-label mb-1">Email</label>
                        <input placeholder="Email" type="text" name="billingEmail" value="<?php echo esc_attr(@$merchantDetails['billingEmail']) ?>" class="form-control inputwidth" id="billingEmail" required />
                    </div>
                </div>
            </div>

            <hr class="mb-0" />
            <div class="container-fluid py-3 px-2 bg-light">
                <h3 class="form-title">Shipping Configurations</h3>
                <div class="row">
                    <div class="col-sm-6">
                        <label for="bookingPreference" class="required-label mb-1">Set your shipping costs preferences</label>
                        <div class="form-check">
                            <input <?php echo esc_attr(@$merchantDetails['bookingPreference'] == $bookingPreferences['free'] ? 'checked' : '') ?> type="radio" id='freeForAllOrders' value="<?php echo esc_html($bookingPreferences['free']) ?>" name="bookingPreference" required>
                            <label for="freeForAllOrders">Free For All orders</label>
                        </div>
                        <div class="form-check">
                            <input <?php echo esc_attr(@$merchantDetails['bookingPreference'] == $bookingPreferences['free_on_basket'] ? 'checked' : '') ?> type="radio" id='freeForBasketValue' value="<?php echo esc_html($bookingPreferences['free_on_basket']) ?>" name="bookingPreference" required>
                            <label for="freeForBasketValue">Free for Orders with Prices over </label>
                            <span id="conditionalPrice" style="visibility: hidden;"> > <input type="text" value="<?php echo esc_html(@$merchantDetails['conditionalPrice']) ?>" name="conditionalPrice" style="width:50px" class="bg-light border border-1"></span>
                        </div>
                        <div class="form-check">
                            <input <?php echo esc_attr(@$merchantDetails['bookingPreference'] == $bookingPreferences['not_free'] ? 'checked' : '') ?> <?php echo !isset($merchantDetails['bookingPreference']) ? 'checked' : '' ?> type="radio" id='notFree' value="<?php echo esc_html($bookingPreferences['not_free']) ?>" name="bookingPreference" required>
                            <label for="notFree">All Shipping Costs Passed on to Customer</label>
                        </div>
                    </div>

                    <div class="col-sm-6">
                        <div class="mt-4">
                            <div class="d-flex align-items-center w-fit-content tooltip-hover" data-tooltip="The fall back shipping amount ensures that eCommerce transactions can still go through even if there is a failure to generate real time shipping prices. The fallback shipping amount will be rendered in the checkout as the shipping cost and allow for a shipment to be booked manually at a later time">
                                <label class="required-label" for="fallBackAmount">Fallback Shipping Amount</label>
                                <i class="fas fa-info-circle info-icon"> </i>
                            </div>
                            <p class="sub-label">On occasions where no carrier can be found set a default shipping price</p>
                            <div class="form-check">
                                <input placeholder="500" type="text" name="fallbackAmount" value="<?php echo $merchantDetails['fallbackAmount'] ? esc_html(@$merchantDetails['fallbackAmount']) : FALLBACK_SHIPPING_AMOUNT ?>" class="form-control" style="width: 55%" id="fallBackAmount" required />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <hr class="mb-0 mt-0" />
            <div class="container-fluid py-3 px-2">
                <h3 class="form-title">Courier Preferences</h3>
                <div class="row">
                    <div class="col-sm-6">
                        <label class="required-label mb-1">Active Couriers</label>
                        <?php
                        foreach ($couriers['data'] as $courier) {
                        ?>
                            <div class="form-check">
                                <input type="checkbox" name='courierPreferences[]' id="courier<?php echo esc_html($courier['id']) ?>" value='<?php echo esc_html($courier['id']) ?>' <?php echo $selected ? ((is_array($selected) && in_array($courier['id'], $selected)) ? 'checked' : '') : 'checked' ?>>
                                <label class="form-check-label" for="courier<?php echo esc_html($courier['id']) ?>"><?php echo esc_html($courier['name']) ?></label>
                            </div>
                        <?php
                        }
                        ?>
                        <div class="form-check">
                            <small class="tag-line courier-service-msg" style="display: none; color: red">At least one courier service is mandatory</small>
                        </div>
                    </div>
                </div>
            </div>

            <hr class="mb-0" />
            <div class="container-fluid py-3 px-2 bg-light">
                <h3 class="form-title">Insurance Preferences</h3>
                <div class="row">
                    <div class="col-sm-6">
                        <label for="insuranceType" class="required-label mb-1">Insurance Types</label>
                        <div class="form-check">
                            <input <?php echo esc_attr(@$merchantDetails['insuranceType'] == '1' ? 'checked' : '') ?> <?php echo esc_attr(!isset($merchantDetails['insuranceType']) ? 'checked' : '') ?> type="radio" id='notRequired' value="1" name="insuranceType" required>
                            <label for="notRequired">Complimentary Coverage - No Additional Charge</label>
                        </div>
                        <div class="form-check">
                            <input <?php echo esc_attr(@$merchantDetails['insuranceType'] == '2' ? 'checked' : '') ?> type="radio" id='requiredUpto' value="2" name="insuranceType" required>
                            <label for="requiredUpto">Transit Insurance Coverage up to over $ </label>
                            <span id="insuranceAmount" style="visibility: hidden;"> > <input type="text" value="<?php echo esc_html(@$merchantDetails['insuranceAmount']) ?>" name="insuranceAmount" style="width:50px" class="bg-light border border-1"></span>
                        </div>
                        <div class="form-check">
                            <input <?php echo esc_attr(@$merchantDetails['insuranceType'] == '3' ? 'checked' : '') ?> type="radio" id='fullCartValue' value="3" name="insuranceType" required>
                            <label for="fullCartValue">Full Insurance Coverage of Shipment Value (Max. $10,000 AUD)</label>
                        </div>
                        <div class="mt-3">
                            <input <?php echo esc_attr(@$merchantDetails['isInsurancePaidByCustomer'] == '1' ? 'checked' : '') ?> type="checkbox" name="isInsurancePaidByCustomer" value="1" id="isInsurancePaidByCustomer" />
                            <label for="isInsurancePaidByCustomer">Insurance cost passed onto customer</label>
                        </div>
                    </div>
                </div>
            </div>

            <hr class="mb-0 mt-0" />
            <div class="container-fluid py-3 px-2">
                <h3 class="form-title">Settings</h3>
                <div class="row">
                    <div class="col-sm-12">
                        <label for="insuranceType" class="required-label mb-1">Order processing</label>
                        <div class="form-check">
                            <input <?php echo esc_attr(@$merchantDetails['automaticOrderProcess'] == '1' ? 'checked' : '') ?> type="radio" id='auto_process_order' value="1" name="automaticOrderProcess">
                            <label for="auto_process_order">Auto</label>
                            <span id="processAfterMinutes" style="visibility: hidden;"> > <input type="number" value="<?php echo esc_html(@$merchantDetails['processAfterMinutes'] ?? $defaultProcessOrderAfterMinutes) ?>" min="<?= $defaultProcessOrderAfterMinutes ?>" name="processAfterMinutes" style="width:70px" class="bg-light border border-1 check-minimum-val"> minutes
                                <p class="sub-label d-inline">(Make sure "DISABLE_WP_CRON" is set as "FALSE" in wp-config.php file)</p>
                            </span>
                        </div>
                        <div class="form-check d-none" id="processAfterDays" style="visibility: hidden;">
                            <div class="d-flex">
                                <label for="process_on sub-label">Should be Process On </label> &nbsp;
                                <select class="form-control sub-label form-control-sm" style="width: 20%; line-height: 1; min-height: 20px;" name="processAfterDays">
                                    <?php
                                    foreach ($processAfterDays as $key => $val) {
                                    ?>
                                        <option value="<?php echo esc_html($key) ?>" <?php echo esc_attr(@$merchantDetails['processAfterDays'] == $key ? 'selected' : '') ?>><?php echo esc_html($val) ?></option>
                                    <?php
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-check mt-1">
                            <input <?php echo esc_attr(@$merchantDetails['automaticOrderProcess'] == '0' ? 'checked' : '') ?> <?php echo esc_attr(!isset($merchantDetails['automaticOrderProcess']) ? 'checked' : '') ?> type="radio" id='not_auto_process_order' value="0" name="automaticOrderProcess">
                            <label for="not_auto_process_order">Manual</label>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-12">
                        <div class="mt-3 d-flex align-items-center">
                            <input <?php echo esc_attr(@$merchantDetails['isDropOffTailLift'] == '1' ? 'checked' : '') ?> type="checkbox" id='tail_lift' value="1" name="isDropOffTailLift">
                            <div class="d-flex align-items-center tooltip-hover w-fit-content" data-tooltip="This generates shipping rate with the Tail-Lift or Lift Assistance service for all deliveries where items being shipped are over 30 kgs. Please only select this option if you are sure about requiring tail lifts on all deliveries. Selecting a tail lift for all deliveries will substantially increase shipping costs.">
                                <label for="tail_lift">Default tail lift on delivery</label>
                                <i class="fas fa-info-circle info-icon ml-1"> </i>
                            </div>
                            <span id="tailLiftKgs" style="visibility: hidden;"> > It will only apply for packages over <input type="text" value="<?php echo esc_html(@$merchantDetails['tailLiftValue'] ?? $defaultTailLiftKgs) ?>" name="tailLiftValue" style="width:60px" class="bg-light border border-1"> Kgs.</span>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-12">
                        <div class="mt-2">
                            <input <?php echo esc_attr(@$merchantDetails['isAuthorityToLeave'] == '1' ? ' checked' : '') ?> type="checkbox" id='atl' value="1" name="isAuthorityToLeave">
                            <label for="atl">Authority to leave is mandatory for all customers</label>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-6 mt-3">
                        <div class="d-flex align-items-center tooltip-hover w-fit-content mb-1" data-tooltip="Please define the category of goods being shipped, this is required for insurance purposes.">
                            <label for="categoriesOfGoods" class="required-label ">Category of Goods Sold</label>
                            <i class="fas fa-info-circle info-icon"> </i>

                        </div>
                        <select id="categoriesOfGoods" class="form-control inputwidth" name="categoriesOfGoods[]" multiple required>
                            <option></option>
                            <?php
                            $selectedCategoriesOfGoods = [];
                            if (@$merchantDetails['categoriesOfGoods']) {
                                if (is_string($merchantDetails['categoriesOfGoods'])) {
                                    $selectedCategoriesOfGoods = json_decode($merchantDetails['categoriesOfGoods']);
                                } else {
                                    $selectedCategoriesOfGoods = $merchantDetails['categoriesOfGoods'];
                                }
                            }
                            foreach ($categoriesOfGoods as $category) {
                                $selected = '';
                                if (!empty($selectedCategoriesOfGoods) && in_array($category['id'], $selectedCategoriesOfGoods)) {
                                    $selected = 'selected';
                                } ?>
                                <option value="<?php echo $category['id'] ?>" <?php echo $selected ?>><?php echo $category['category'] ?></option>
                            <?php } ?>
                        </select>
                    </div>
                </div>
            </div>

            <input type="hidden" name="id" value="<?php echo esc_html($merchantDetails['id']) ?>">
            <div class="text-right mt-5 mb-5">
                <button type="button" onclick="activateMerchant(this)" class="btn btn-primary">Save Details</button>
            </div>
        </form>
    </div>
    <div class="tab-content-child tab-payment-method">

        <?php include_once('payment-methods.php'); ?>

        <form onsubmit="activateMerchantPayment(); return false" name='merchant-payment-form' oninput="handleFormChange()">
            <div class="container-fluid py-3 px-2">
                <div class="d-flex align-items-center justify-content-between">

                    <h3 class="form-title required-label">Payment Methods</h3>
                    <button type="button" onclick="quickLoginPayment()" class="btn btn-primary">Manage Payment Methods</button>
                </div>
                <div class="row mt-4">
                    <?php
                    // Loop through each payment method
                    foreach ($paymentMethods['data'] ?? [] as $index => $paymentMethod) {
                    ?>
                        <div class="col-sm" style="pointer-events: none;">
                            <div class="form-check">
                                <div class="card-details d-flex justify-content-between align-items-center">
                                    <div>
                                        <input
                                            <?php echo $paymentMethod['default_method'] == 1 ? 'checked' : '' ?>
                                            type="radio"
                                            name="paymentMethod"
                                            class="form-control mb-3"
                                            id="paymentMethod_<?php echo $index; ?>"
                                            value="" />
                                        <i class="fa-brands fa-cc-<?php echo esc_html($paymentMethod['brand']); ?>" aria-hidden="true"></i>
                                    </div>
                                    <div>
                                        <p class="mb-0 pull-right">XXXX XXXX XXXX <?php echo esc_html($paymentMethod['last4']); ?></p>
                                        <p class="mb-0"><?php echo strtoupper(esc_html($paymentMethod['brand'])); ?>&nbsp;&nbsp; <span class="pull-right pr-2">Exp: <?php echo str_pad(esc_html($paymentMethod['exp_month']), 2, '0', STR_PAD_LEFT); ?>/<?php echo substr($paymentMethod['exp_year'], -2); ?></span></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php
                    }
                    ?>
                </div>

            </div>
            <!-- <div class="text-right mt-5 mb-5">
                <button type="button" onclick="activateMerchantPayment(this)" class="btn btn-primary">Save Details</button>
            </div> -->
        </form>
    </div>
    <div class="tab-content-child tab-location">
        <?php include_once('locations.php'); ?>
    </div>
    <div class="tab-content-child tab-product-mapping">
        <?php
        $productFields = FastCourierProducts::get_all_product_fields();

        $data = fc_sanitize_data($_GET);
        $data['page'] = isset($_GET['page_no']) ? sanitize_text_field($_GET['page_no']) : 1;
        // fetch products
        $productResult = FastCourierProducts::products($data);

        // Products Categories
        $categories = FastCourierProducts::categories([]);

        // products tags for filter
        $wooTags = FastCourierProducts::get_all_tags();

        // products types for filter
        $productTypeFilters = FastCourierProducts::getProductTypeFilter();

        include_once('products.php');
        ?>
    </div>
</div>

<?php
global $token;
$portal_url = is_test_mode_active() ? $GLOBALS['api_origin'] : $GLOBALS['prod_api_origin'];
?>

<script>
    handleFormChange();
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
        var _parent = parent;

        // AJAX request to fetch suburbs data
        xhr = $.ajax({
            url: "<?php echo fc_apis_prefix() ?>suburbs",
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
                $(_parent + ' .wp-ajax-suburbs').empty();
                // Populate select dropdown with options based on AJAX response
                $.each(response.data, function(index, value) {
                    // Append each suburb as an option in the dropdown
                    $(_parent + ' .wp-ajax-suburbs').append('<li class="suburb-list" postcode="' + value.postcode + '" suburb="' + value.name + '" state="' + value.state + '" lat="' + value.latitude + '" long="' + value.longitude + '">' + value.name + ', ' + value.postcode + ' (' + value.state + ')' + '</li>');
                });
            },
            error: function(xhr, status, error) {
                // Log any errors in the console
                console.error(xhr.responseText);
            }
        });
    }

    // Function to assign selected suburb, state, and postcode values to form fields
    function assignSelectedSuburbStatePostcodeValues(parent, _this, latlong = false) {
        // Get the text of the selected item and assign it to the corresponding input field
        let selectedItem = _this.text();
        $(parent + ' .fc-selected-suburb').val(selectedItem);

        // Get postcode, suburb, and state attributes of the selected item
        let postcode = _this.attr('postcode');
        let suburb = _this.attr('suburb');
        let state = _this.attr('state');

        // Assign postcode, suburb, and state values to their respective input fields
        $(parent + ' .fc-suburb').val(suburb);
        $(parent + ' .fc-state').val(state);
        $(parent + ' .fc-postcode').val(postcode);

        // Additional functionality for handling latitude and longitude (if provided)
        if (latlong) {
            let latitude = _this.attr('lat');
            let longitude = _this.attr('long');
            $(parent + ' .fc-latitude').val(latitude);
            $(parent + ' .fc-longitude').val(longitude);
        }
    }

    $(document).ready(function() {
        // Check if there's a stored tab href in local storage
        var storedTabHref = localStorage.getItem('selectedTabHref');
        if (storedTabHref) {
            //remove active classes from tab and tab content
            $('.tab').removeClass('tab-active');
            $('.tab-content-child').removeClass('tab-content-active');
            let ele = $('div[class="tab"][href="' + storedTabHref + '"]');
            ele.addClass('tab-active');
            // enable tab content
            let targetTab = ele.data('toggle');
            $('.' + targetTab).addClass('tab-content-active');
        }

        // update progress bar
        function manageProgressBar() {
            var deferred = $.Deferred();
            var highestValue = 0; // Initialize with a very low value
            var progressBar = 25;

            var checkTabCompleted = new Promise(function(resolve, reject) {
                $('div[tab-completed]').each(function() {
                    var currentValue = parseInt($(this).attr('tab-completed'), 0);
                    if (!isNaN(currentValue)) {
                        highestValue = highestValue + currentValue;
                        // calculate the percentage of completion
                        let percent = parseInt(highestValue) * parseInt(progressBar);
                        $('.progress-bar').css('width', percent + '%');
                        $('.progress-bar').attr('aria-valuenow', percent);
                        $('.progress-bar').html(percent + '%');
                    }
                });
                resolve(highestValue);
            });

            checkTabCompleted.then(function(value) {
                if (value != 4) {
                    $('.merchant-only').css('display', 'none');
                }
            });
        }

        manageProgressBar();

        $('#categoriesOfGoods').select2({
            tokenSeparators: [',', ' '],
            placeholder: 'Select categories of goods'
        });

        // Start - merchant config page suburb populate

        // Event handler for clearing input value and showing suburb list on input click
        $('.merchant-basic-config-suburb').on('click', function() {
            // Clear input value
            $(this).val('');
            // Show suburb list
            $('.merchant-basic-config .fc-suburb-list').show();
        });

        // Event handler for populating suburbs based on user input
        $('.merchant-basic-config-suburb').on('keyup', function() {
            // Call function to populate suburbs
            populateSuburbs(".merchant-basic-config", $(this));
        });

        // Event handler for selecting a suburb from the list
        $(document).on('click', '.merchant-basic-config .suburb-list', function() {
            // Assign selected suburb, state, and postcode values to form fields
            assignSelectedSuburbStatePostcodeValues('.merchant-basic-config', $(this));
            // Hide suburb list
            $('.merchant-basic-config .fc-suburb-list').hide();
        });
        // End - merchant config page suburb populate
    })

    // on click tab, manage the display content
    $('.tab').on('click', function() {
        // append hash value to URL
        let tabHash = $(this).attr('href');
        localStorage.setItem('selectedTabHref', tabHash);
        // remove active classes from tab and tab content
        $('.tab').removeClass('tab-active');
        $('.tab-content-child').removeClass('tab-content-active');
        // Mark the clicked tab active
        $(this).addClass('tab-active');
        // enable tab content
        let targetTab = $(this).data('toggle');
        $('.' + targetTab).addClass('tab-content-active');
    })

    // For merchant's basic configuration validation
    function validateBasicInputs(formFields) {
        let i = 0;
        let isValid = true;

        while (i < formFields.length) {
            const field = $(formFields[i]);
            if (field.attr('required') && !field.val()) {
                const idOfField = field.attr('id');
                if (!$(`[error-for=${idOfField}]`).length) {
                    $(`[for=${idOfField}]`).after(`<small class='text-danger' error-for=${idOfField}>(Required)</small>`);
                    field.addClass('border-danger');
                }

                isValid = false;
            }
            i++;
        }

        var categoriesSelectBox = document.getElementById('categoriesOfGoods');
        // Check if at least one option is selected
        if (categoriesSelectBox && categoriesSelectBox.selectedOptions.length == 0) {
            if (!$(`[error-for=categoriesOfGoods]`).length) {
                $(`[for=categoriesOfGoods]`).after(`<small class='text-danger' error-for='categoriesOfGoods'>(Required)</small>`);
                let ele = $('#categoriesOfGoods').next().children().find('.select2-selection--multiple');
                if (ele) $(ele).addClass('border-danger');
            }
            isValid = false;
        }

        return isValid;
    }
    // For merchant's basic configuration
    function activateMerchant(e) {
        var formFields = $("[name='merchant-form']").find("input");
        if (validateBasicInputs(formFields)) {
            toggleLoader();
            var ajaxurl = '<?php echo esc_js(admin_url('admin-ajax.php', 'relative')); ?>';

            // IMPORTANT NOTE: Attached payment method form and Basic form fields, as merchant API required all the fields
            var formData = $("[name='merchant-form']").serialize() + '&action=post_activate_mechant';

            $.post(ajaxurl, formData, function(response) {
                if (response == '1') {
                    Swal.fire({
                        title: 'Merchant Activated Successfully',
                        icon: 'success',
                        theme: 'success',
                        showDenyButton: false,
                        confirmButtonText: 'Ok',
                        dangerMode: true,
                    }).then((result) => {
                        localStorage.setItem('selectedTabHref', "#payment-method");
                        location.reload();
                    });
                } else if (response == '2') {
                    Swal.fire("", "Merchant Activated Successfully! <br /> <b> Please set 'DISABLE_WP_CRON' as FALSE in wp-config.php for enable automatic order processing. </b>", "warning");
                } else {
                    Swal.fire("", 'Internal Server Error', "error");
                }
                toggleLoader();
            });
        } else {
            $('html, body').animate({
                scrollTop: $('.text-danger').offset().top - 100
            })
        }
    }
    // For merhcant payment method configuration
    function activateMerchantPayment(e) {
        var formFields = $("[name='merchant-payment-form']").find("input");
        if (validateBasicInputs(formFields)) {
            toggleLoader();
            var ajaxurl = '<?php echo esc_js(admin_url('admin-ajax.php', 'relative')); ?>';

            // IMPORTANT NOTE: Attached payment method form and Basic form fields, as merchant API required all the fields 
            var formData = $("[name='merchant-payment-form']").serialize() + '&action=post_activate_mechant_payment';

            $.post(ajaxurl, formData, function(response) {
                if (response == '1') {
                    Swal.fire({
                        title: 'Payment Method Activated Successfully',
                        icon: 'success',
                        theme: 'success',
                        showDenyButton: true,
                        confirmButtonText: 'Ok',
                        dangerMode: true,
                    }).then((result) => {
                        localStorage.setItem('selectedTabHref', "#location");
                        <?php
                        $configSessionArray = $session->get('configuration_completed', array());
                        $configSessionArray['payment'] = 1;
                        $session->set('configuration_completed', $configSessionArray);
                        ?>
                        location.reload();
                    });
                } else if (response == '2') {
                    Swal.fire("", "Merchant Activated Successfully! <br /> <b> Please set 'DISABLE_WP_CRON' as FALSE in wp-config.php for enable automatic order processing. </b>", "warning");
                } else {
                    Swal.fire("", 'Internal Server Error', "error");
                }
                toggleLoader();
            });
        } else {
            $('html, body').animate({
                scrollTop: $('.text-danger').offset().top - 100
            })
        }
    }

    function formatSuburbs(subsurbs) {
        if (subsurbs.loading) {
            return subsurbs.text;
        }

        var $container = $(`<div class="select2-user-result">${subsurbs.name || ''}, ${subsurbs.state || ''} (${subsurbs.postcode || ''})</div>`);

        return $container;
    }

    function suburbsSelection(suburb) {
        return suburb.name ? `${suburb.name}, ${suburb.postcode} (${suburb.state})` : '';
    }

    function handleFormChange() {
        if (document.getElementById('freeForBasketValue').checked) {
            document.getElementById('conditionalPrice').style.visibility = 'visible';
        } else {
            document.getElementById('conditionalPrice').style.visibility = 'hidden';
        }

        if (document.getElementById('requiredUpto').checked) {
            document.getElementById('insuranceAmount').style.visibility = 'visible';
        } else {
            document.getElementById('insuranceAmount').style.visibility = 'hidden';
        }

        if (document.getElementById('auto_process_order').checked) {
            document.getElementById('processAfterMinutes').style.visibility = 'visible';
        } else {
            document.getElementById('processAfterMinutes').style.visibility = 'hidden';
        }

        if (document.getElementById('auto_process_order').checked) {
            document.getElementById('processAfterDays').style.visibility = 'visible';
            document.getElementById('processAfterDays').classList.remove("d-none");
            document.getElementById('processAfterDays').classList.add("d-block");
        } else {
            document.getElementById('processAfterDays').style.visibility = 'hidden';
            document.getElementById('processAfterDays').classList.remove("d-block");
            document.getElementById('processAfterDays').classList.add("d-none");
        }

        if (document.getElementById('tail_lift').checked) {
            document.getElementById('tailLiftKgs').style.visibility = 'visible';
        } else {
            document.getElementById('tailLiftKgs').style.visibility = 'hidden';
        }

        const field = $('input:focus');
        const idOfField = field.attr('id');

        if ($(`[error-for=${idOfField}]`).length) {
            $(`[error-for=${idOfField}]`).remove();
            field.removeClass('border-danger');
        }

        $('#categoriesOfGoods').on('select2:select', function(e) {
            $(`[error-for=categoriesOfGoods]`).remove();
            let ele = $('#categoriesOfGoods').next().children().find('.select2-selection--multiple');
            if (ele) $(ele).removeClass('border-danger');
        });
    }

    $("input[type='checkbox'][name='courierPreferences[]']").click(function(e) {
        if ($("input[type='checkbox'][name='courierPreferences[]']:checked").length == 0) {
            $('.courier-service-msg').fadeIn();
            return false;
        }
        $('.courier-service-msg').fadeOut();
    })

    $('.check-minimum-val').on('keyup', function() {
        var inputValue = parseInt($(this).val());
        var minValue = $(this).attr('min');

        if (inputValue < minValue) {
            $(this).val(minValue);
        }
    });

    function quickLoginPayment() {
        const portalUrl = "<?php echo $portal_url; ?>";
        const access_token = "<?php echo $token ?>"

        const newWindow = window.open(
            `${portalUrl}quick-login?access_token=${access_token}&redirect_page=payment`,
            "popupWindow",
            "width=7000,height=7000"
        );
        var pollTimer = window.setInterval(async function() {
            if (newWindow.closed !== false) {
                window.clearInterval(pollTimer);
                document.querySelector(".loader").classList.toggle("active");
                window.location.reload()

            }
        }, 500);

    }

    document.querySelectorAll('.tooltip-hover').forEach(cell => {
        cell.addEventListener('mouseover', function(event) {
            const tooltip = document.getElementById('custom-tooltip');
            const cellRect = cell.getBoundingClientRect();

            // Set the text of the tooltip
            tooltip.textContent = cell.getAttribute('data-tooltip');

            // Make the tooltip visible
            tooltip.style.opacity = '1';
            tooltip.style.position = 'fixed'; // Use fixed position for consistency with scrolling
            tooltip.style.width = 'auto'; // Allow the width to adjust based on content
            tooltip.style.maxWidth = '300px';
            tooltip.style.zIndex = '3000000';
            // Get tooltip dimensions
            const tooltipWidth = tooltip.offsetWidth;
            const tooltipHeight = tooltip.offsetHeight;

            // Calculate available space
            const spaceAbove = cellRect.top;
            const spaceBelow = window.innerHeight - cellRect.bottom;
            const spaceLeft = cellRect.left;
            const spaceRight = window.innerWidth - cellRect.right;

            // Position the tooltip to stay within visible area, centered over the cell
            if (spaceBelow >= tooltipHeight) {
                // Position tooltip below the cell
                tooltip.style.top = `${cellRect.bottom + 5}px`;
                tooltip.style.left = `${cellRect.left + cellRect.width / 2}px`;
                tooltip.style.transform = 'translateX(-50%)';
            } else if (spaceAbove >= tooltipHeight) {
                // Position tooltip above the cell
                tooltip.style.top = `${cellRect.top - tooltipHeight - 5}px`;
                tooltip.style.left = `${cellRect.left + cellRect.width / 2}px`;
                tooltip.style.transform = 'translateX(-50%)';
            } else if (spaceRight >= tooltipWidth) {
                // Position tooltip to the right of the cell
                tooltip.style.top = `${cellRect.top + cellRect.height / 2}px`;
                tooltip.style.left = `${cellRect.right + 5}px`;
                tooltip.style.transform = 'translateY(-50%)';
            } else if (spaceLeft >= tooltipWidth) {
                // Position tooltip to the left of the cell
                tooltip.style.top = `${cellRect.top + cellRect.height / 2}px`;
                tooltip.style.left = `${cellRect.left - tooltipWidth - 5}px`;
                tooltip.style.transform = 'translateY(-50%)';
            } else {
                // Default to positioning above if space is limited
                tooltip.style.top = `${cellRect.top - tooltipHeight - 5}px`;
                tooltip.style.left = `${cellRect.left + cellRect.width / 2}px`;
                tooltip.style.transform = 'translateX(-50%)';
            }
        });

        cell.addEventListener('mouseout', function() {
            const tooltip = document.getElementById('custom-tooltip');
            // Hide the tooltip
            tooltip.style.opacity = '0';
            tooltip.style.top = '-9999px'; // Move it out of view to avoid display issues
            tooltip.style.left = '-9999px';
        });
    });
</script>

<style>
    .w-fit-content {
        width: fit-content;
    }

    .info-icon {
        font-size: 15px
    }

    .ml-1 {
        margin-left: 10px;

    }
</style>