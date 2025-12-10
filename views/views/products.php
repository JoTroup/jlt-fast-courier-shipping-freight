<?php

use FastCourier\FastCourierManagePackages;

// Products
$products = $productResult['products'];

// Total Pages
$pages = $productResult['pages'];

// Packages
$packages = FastCourierManagePackages::index();
$packages_json = json_encode($packages);

// Package Types
$packageTypes = FastCourierManagePackages::getPackageTypes();
$pTypes = json_encode($packageTypes);

// Locations
$jsLocations = json_encode($locationResult);

//tags
$jsTags = json_encode($available_tags);

// If No Filters Implemented
if (!isset($_GET['category']))
    $_GET['category'] = [];
?>
<form action="<?php echo admin_url('admin.php') ?>" method="get">
    <div class="row">
        <input type='hidden' name='page' value="<?php echo esc_attr($_GET['page']) ?>">
        <div class="col-sm-2">
            <label>Keywords</label>
            <input type="text" name="s" value="<?php echo esc_attr(@$_GET['s']) ?>" class="w-100">
        </div>

        <div class="col-sm-2">
            <label>Category</label>
            <select name="category[]" class="w-100 form-control">
                <option value=''>All</option>
                <?php
                foreach ($categories as $category) {
                ?>
                    <option <?php echo esc_attr(@in_array($category->cat_name, $_GET['category']) ? 'selected' : '') ?>
                        value='<?php echo esc_attr($category->cat_name) ?>'><?php echo esc_html($category->cat_name) ?>
                    </option>
                <?php
                }
                ?>
            </select>
        </div>

        <div class="col-sm-2">
            <label>Tags</label>
            <select name="product_tag" class="w-100 form-control">
                <option value=''>All</option>
                <option <?php if (@$_GET['product_tag'] == 'noTags') {
                            echo "selected";
                        } ?> value='noTags'>No Tags
                </option>
                <?php foreach ($wooTags as $wooTag) { ?>
                    <option <?php if (@$_GET['product_tag'] == $wooTag) {
                                echo "selected";
                            } ?> value='<?php echo esc_attr($wooTag) ?>'><?php echo esc_html($wooTag) ?></option>
                <?php } ?>
            </select>
        </div>

        <div class="col-sm-2">
            <label>Product Type</label>
            <select name="productType" class="w-100 form-control">
                <option value=''>All</option>
                <?php foreach ($productTypeFilters as $productTypeFilterKey => $productTypeFilter) { ?>
                    <option <?php if (@$_GET['productType'] == $productTypeFilterKey) {
                                echo "selected";
                            } ?> value='<?php echo esc_attr($productTypeFilterKey) ?>'><?php echo esc_html($productTypeFilter) ?>
                    </option>
                <?php } ?>
            </select>
        </div>

        <div class="col-sm-2 d-flex justify-content-start align-items-end">
            <button type="submit" class="btn btn-outline-primary mt-2 mx-2 filter-out-btn">Filter</button>
            <a href="<?php echo esc_url(admin_url("admin.php?page=" . $_GET['page'])) ?>"
                class="btn btn-outline-primary mt-2 filter-out-btn">Reset</a>
        </div>
    </div>
</form>

<div class="mt-3">
    <div class="row">
        <div class="col-sm-2 align-content-center">
            <select name="bulkAction" id="bulkAction" class="w-100 form-control pull-left">
                <option value=''>Bulk actions</option>
                <?php
                foreach ($bulkActions as $bulkActionKey => $bulkAction) {
                ?>
                    <option value='<?php echo esc_attr($bulkActionKey) ?>'><?php echo esc_html($bulkAction) ?></option>
                <?php
                }
                ?>
            </select>
        </div>
        <div class="col-sm-1 d-flex justify-content-start align-items-end">
            <button type="submit" onclick="toggleActionModal()"
                class="btn btn-outline-primary bulk-action-btn">Apply</button>
        </div>
        <div class="col-sm-9">
            <button class="btn btn-primary m-1 pull-right" onclick="toggleCSVimportModal()">Import Dimensions</button>
            <button class="btn btn-primary m-1 pull-right" onclick="toggleMappingModal()">Map with Woo
                Dimensions</button>
            <button class="btn btn-primary m-1 pull-right" onclick="toggleShippingBoxModal()">Shipping Boxes</button>
        </div>
    </div>
    <form action="" name="productsList">
        <div id="custom-tooltip"></div>
        <table class="table fc-table">
            <thead>
                <tr>
                    <th><input type="checkbox" id="checkAll"></th>
                    <th>Name</th>
                    <th>SKU</th>
                    <th>Price</th>
                    <th>Category</th>
                    <th>Product Type</th>
                    <th>L x W x H (CMs)</th>
                    <th>Weight (KGs)</th>
                    <th class="position-relative processed-order"
                        title="The product will be shipped in an individual package">Is Individual <i
                            class="fas fa-info-circle"> </i> </th>
                    <th class="position-relative processed-order" title="The product is eligible for shipping.">Eligible
                        For Shipping <i class="fas fa-info-circle"> </i> </th>
                    <th class="position-relative processed-order" title="The product is eligible for free shipping.">
                        Free Shipping <i class="fas fa-info-circle"> </i> </th>
                    <th>Location/Tag</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (!count($products)) {
                ?>
                    <tr>
                        <th colspan="10" class="text-center">No Records Found</th>
                    </tr>

                <?php
                }
                foreach ($products as $product) {
                    $variableOrGroupedProduct = $variableProduct = $product->is_type('variable');
                    $groupedProduct = false;

                    if (!$variableOrGroupedProduct) {
                        $variableOrGroupedProduct = $groupedProduct = $product->is_type('grouped');
                    }
                    $productId = $product->get_id();
                    $isVirtualProduct = $product->is_virtual();
                    // append product row
                    include('product-row.php');
                    // if product is variable type
                    if ($variableProduct) {
                        $variations = $product->get_available_variations();
                        foreach ($variations as $variation) {
                            // get variant id
                            $child_product_id = $variation['variation_id'];
                            // append variants of product as product on summary
                            include('product-row.php');
                            unset($child_product_id);
                        }
                    }
                    // if product is grouped product
                    if ($groupedProduct) {
                        $linked_product_ids = $product->get_children();
                        foreach ($linked_product_ids as $grouped_product_id) {
                            $child_product_id = $grouped_product_id;
                            // append child of group product as product on summary
                            include('product-row.php');
                            unset($child_product_id);
                        }
                        unset($groupedProduct);
                    }
                    unset($productId, $isVirtualProduct);
                } ?>
            </tbody>
        </table>
    </form>
</div>
<div class="row">
    <div class="col-sm-12">
        <button class="btn btn-primary m-1 pull-right" onclick="toggleMappingModal()">Map with Woo Dimensions</button>
        <button class="btn btn-primary m-1 pull-right" onclick="toggleShippingBoxModal()">Shipping Boxes</button>
    </div>
</div>

<div class="modal" id="modalPackages">
    <div class="modal-dailog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="order-modal-title">Assign dimensions to selected product(s)</h3>
            </div>
            <div class="modal-body">
                <form action="" name="selectedPackages">
                    <div class="row dimensions-row">
                        <div class="col-sm-4">
                            <div class="form-group">
                                <label class="required-label" for="dimensions">Package Types</label>
                                <select class="form-control w-100" name="package_type" id="package_type" required>
                                    <?php
                                    foreach ($packageTypes as $packageType) {
                                    ?>
                                        <option value="<?php echo esc_html($packageType['name']) ?>" <?php if (esc_html($packageType['name']) == 'box') { ?> selected <?php } ?>>
                                            <?php echo esc_html($packageType['name']) ?></option>
                                    <?php
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="form-group">
                                <label class="required-label w-100" for="length">Length</label>
                                <input type="text" id="length" class="form-control" name="length" required />
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="form-group">
                                <label class="required-label w-100" for="width">Width</label>
                                <input type="text" id="width" class="form-control" name="width" required />
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="form-group">
                                <label class="required-label w-100" for="height">Height</label>
                                <input type="text" id="height" class="form-control" name="height" required />
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="form-group">
                                <label class="required-label" for="weight">Weight (KGs)</label>
                                <input type="text" id="weight" name="weight" value="" step=".01" min="0" required
                                    class="form-control w-100" />
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="form-group">
                                <label class="required-label" class="required-label" for="isIndividuals">Is this product
                                    only shipped individually and not combined with other products in a single
                                    package?</label>
                                <div class="w-100">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" name="individual" checked type="radio"
                                            id="yesIndividual" value="1" required>
                                        <label class="form-check-label" for="yesIndividual">Yes</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" name="individual" type="radio" id="noIndividual"
                                            value="0" required>
                                        <label class="form-check-label" for="noIndividual">No</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <a href="#" class="add-dimensions-row"> Add More Dimensions </a>

                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-light" onclick="togglePackagesModal()">Close</button>
                <button type="submit" class="btn btn-primary ml-2" onclick="mapProducts(this);">Submit</button>
            </div>
        </div>
    </div>
</div>

<div class="modal" id="modalLocations">
    <div class="modal-dailog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="order-modal-title">Assign Location to selected product(s)</h3>
            </div>
            <div class="modal-body">
                <form action="" name="updateLocation">
                    <div class="form-group">
                        <label for="locationsBy">Location By</label>
                        <select class="form-control w-100" name="locationsBy" id="locationsBy" required>
                            <option value="name">Name</option>
                            <option value="tags">Tags</option>
                        </select>
                        <label for="locationsList" class="mt-2">Location List</label>
                        <select class="form-control w-100" name="locations" id="locationsList" required>
                            <?php
                            foreach ($locationResult as $location_tag) {
                            ?>
                                <option value='<?php echo esc_html($location_tag['id']) ?>'>
                                    <?php echo esc_html($location_tag['location_name']) ?></option>
                            <?php
                            }
                            ?>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-light" onclick="toggleLocationModal()">Close</button>
                <button type="submit" class="btn btn-primary ml-2" onclick="updateLocationData();">Submit</button>
            </div>
        </div>
    </div>
</div>

<div class="modal" id="modalAllowShipping">
    <div class="modal-dailog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="order-modal-title">Mark as Eligible For Shipping to selected product(s)</h3>
            </div>
            <div class="modal-body">
                <form action="" name="updateEligibleForShipping">
                    <div class="form-group">
                        <select class="form-control w-100" name="fc_allow_shipping" required>
                            <option value="0">No</option>
                            <option value="1">Yes</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-light" onclick="toggleEligibleForShippingModal()">Close</button>
                <button type="submit" class="btn btn-primary ml-2"
                    onclick="bulkUpdateEligibleForShipping();">Submit</button>
            </div>
        </div>
    </div>
</div>

<div class="modal" id="modalFreeShipping">
    <div class="modal-dailog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="order-modal-title">Allow Free Shipping to selected product(s)</h3>
            </div>
            <div class="modal-body">
                <form action="" name="updateFreeShipping">
                    <div class="form-group">
                        <select class="form-control w-100" name="fc_allow_free_shipping" required>
                            <option value="0">No</option>
                            <option value="1">Yes</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-light" onclick="toggleFreeShippingModal()">Close</button>
                <button type="submit" class="btn btn-primary ml-2" onclick="bulkUpdateFreeShipping();">Submit</button>
            </div>
        </div>
    </div>
</div>

<div class="modal" id="modalIndividual">
    <div class="modal-dailog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="order-modal-title">Update individual shipping to selected product(s)</h3>
            </div>
            <div class="modal-body">
                <form action="" name="updateIndividual">
                    <div class="form-group">
                        <select class="form-control w-100" name="fc_is_individual" required>
                            <option value="0">No</option>
                            <option value="1">Yes</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-light" onclick="toggleIndividualModal()">Close</button>
                <button type="submit" class="btn btn-primary ml-2" onclick="bulkUpdateIndividual();">Submit</button>
            </div>
        </div>
    </div>
</div>

<div class="modal" id="modalWeight">
    <div class="modal-dailog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="order-modal-title">Assign Weight to selected product(s)</h3>
            </div>
            <div class="modal-body">
                <form action="" name="updatedWeight">
                    <div class="form-group">
                        <label class="required-label" for="weight">Weight (KGs)</label>
                        <input type="number" id="weight" name="weight" value="" step=".01" min="0" required
                            class="form-control w-100" />
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-light" onclick="toggleWeightModal()">Close</button>
                <button class="btn btn-primary ml-2" onclick="updateWeight()">Submit</button>
            </div>
        </div>
    </div>
</div>

<div class="modal" id="modalCSVimport">
    <div class="modal-dailog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="order-modal-title">Import dimensions & weight for product(s)</h3>
            </div>
            <div class="modal-body">
                <form action="" id="importDimensionsCSV">
                    <div class="row">
                        <div class="col-12 mt-2">
                            <div class="row">
                                <div class="col-6">
                                    <input type="file" id="dimensionsCsvFile" accept=".csv"
                                        style="padding: unset !important; padding-top: 1rem!important;">
                                </div>
                                <div class="col-6 pt-3 text-right">
                                    <span style="padding-top: 1rem!important;">
                                        <a
                                            href="<?php echo esc_url(plugins_url('../sample/dimensions-sample.csv', __FILE__)) ?>">
                                            Sample CSV </a>
                                    </span>
                                </div>
                                <div class="col-6">
                                    <span class="csv_error_message"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- <div class="form-group">
                        <label class="required-label" for="weight">Weight (KGs)</label>
                        <input type="number" id="weight" name="weight" value="" step=".01" min="0" required class="form-control w-100" />
                    </div> -->
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-light" onclick="toggleCSVimportModal()">Close</button>
                <button class="btn btn-primary ml-2" onclick="importDimensions()">Import</button>
            </div>
        </div>
    </div>
</div>

<div class="modal" id="modalMapping" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dailog modal-lg" style="width: 550px;">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="order-modal-title">Map Dimensions & Weight Fields</h3>
            </div>
            <div class="modal-body">
                <form action="" name="updateProductMappingDimensions">
                    <div class="row">
                        <div class="col-md-6"></div>
                        <div class="col-md-6"><strong>WooCommerce fields</strong></div>
                    </div>
                    <div class="row">
                        <!-- Left column -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="packagesList">Package Type</label>
                            </div>
                        </div>
                        <!-- Right column -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <select class="form-control" name="packages" id="packagesList">
                                    <?php
                                    foreach ($packageTypes as $packageType) {
                                    ?>
                                        <option value="<?php echo esc_html($packageType['name']) ?>">
                                            <?php echo esc_html($packageType['name']) ?></option>
                                    <?php
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <!-- Left column -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="weight">Weight (kg)</label>
                            </div>
                        </div>
                        <!-- Right column -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <select class="form-control" id="weight" name="weight">
                                    <?php
                                    foreach ($productFields as $field) {
                                    ?>
                                        <option <?php if ($field == 'weight' || $field == '_weight') {
                                                    echo esc_attr('selected');
                                                } ?> value='<?php echo esc_attr($field) ?>'><?php echo esc_html($field) ?></option>
                                    <?php
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="dimensions">Dimensions (cm)</label>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <fieldset class="pl-2">
                                <div class="form-group">
                                    <label for="length">Length</label>
                                </div>
                                <div class="form-group">
                                    <label for="width">Width</label>
                                </div>
                                <div class="form-group">
                                    <label for="height">Height</label>
                                </div>
                            </fieldset>
                        </div>
                        <div class="col-md-6">
                            <fieldset class="">
                                <div class="form-group">
                                    <select class="form-control" id="length" name="length">
                                        <?php
                                        foreach ($productFields as $field) {
                                        ?>
                                            <option <?php if ($field == 'length' || $field == '_length') {
                                                        echo esc_attr('selected');
                                                    } ?> value='<?php echo esc_attr($field) ?>'><?php echo esc_html($field) ?></option>
                                        <?php
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <select class="form-control" id="width" name="width">
                                        <?php
                                        foreach ($productFields as $field) {
                                        ?>
                                            <option <?php if ($field == 'width' || $field == '_width') {
                                                        echo esc_attr('selected');
                                                    } ?> value='<?php echo esc_attr($field) ?>'><?php echo esc_html($field) ?></option>
                                        <?php
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <select class="form-control" id="height" name="height">
                                        <?php
                                        foreach ($productFields as $field) {
                                        ?>
                                            <option <?php if ($field == 'height' || $field == '_height') {
                                                        echo esc_attr('selected');
                                                    } ?> value='<?php echo esc_attr($field) ?>'><?php echo esc_html($field) ?></option>
                                        <?php
                                        }
                                        ?>
                                    </select>
                                </div>
                            </fieldset>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-dismiss="modal"
                    onclick="toggleMappingModal();">Close</button>
                <button type="submit" class="btn btn-primary ml-2" onclick="updateProductMapping();">Map</button>
            </div>
        </div>
    </div>
</div>
<?php include_once('shipping-box.php'); ?>
<script>
    var locationNames = JSON.parse('<?php echo $jsLocations ?>');
    var locationTags = JSON.parse('<?php echo $jsTags ?>');
    var packageTypes = JSON.parse('<?php echo $pTypes ?>');
</script>

<script>
    var rowCounter = 1; // Initialize row counter
    // Handle click event on "Add Row" button
    $(".add-dimensions-row").click(function() {
        // Generate a unique row ID with a postfix
        var rowID = "row_" + rowCounter;
        let options = '';
        $.each(packageTypes, function(key, package) {
            let selected = '';
            if (package.name == 'box') {
                selected = 'selected';
            }
            options += `<option value="${package.name}" ${selected}> ${package.name} </option>`;
        });

        // Create a new row with two cells (Name and Value) 
        dimRow = `<div class="col-sm-4 ${rowID}">
                    <div class="form-group">
                        <label class="required-label" for="dimensions">Package Types</label>
                        <select class="form-control w-100" name="package_type${rowCounter}" id="package_type" required>
                        ${options}
                        </select>
                    </div>
                    </div>
                    <div class="col-sm-4 ${rowID}">
                        <div class="form-group">
                            <label class="required-label w-100" for="length${rowCounter}">Length</label>
                            <input type="text" id="length${rowCounter}" class="form-control" name="length${rowCounter}" required />
                        </div>
                    </div>
                    <div class="col-sm-4 ${rowID}">
                        <div class="form-group">
                            <label class="required-label w-100" for="width${rowCounter}">Width</label>
                            <input type="text" id="width${rowCounter}" class="form-control" name="width${rowCounter}" required />
                        </div>
                    </div>
                    <div class="col-sm-4 ${rowID}">
                        <div class="form-group">
                            <label class="required-label w-100" for="height${rowCounter}">Height</label>
                            <input type="text" id="height${rowCounter}" class="form-control" name="height${rowCounter}" required />
                        </div>
                    </div>
                    <div class="col-sm-4 ${rowID}">
                        <div class="form-group">
                            <label class="required-label" for="weight${rowCounter}">Weight (KGs)</label>
                            <input type="text" id="weight${rowCounter}" name="weight${rowCounter}" value="" step=".01" min="0" required class="form-control w-100" />
                        </div>
                    </div>
                    <div class="col-sm-4 ${rowID}">
                        <div class="form-group">
                            <label class="required-label" class="required-label" for="isIndividuals">Is this product only shipped individually and not combined with other products in a single package?</label>
                            <div class="w-100">
                                <div class="form-check form-check-inline" style="margin-top: 0.7rem!important;">
                                    <input class="form-check-input" name="individual${rowCounter}" checked type="radio" id="yesIndividual" value="1" required>
                                    <label class="form-check-label" for="yesIndividual">Yes</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" name="individual${rowCounter}" type="radio" id="noIndividual" value="0" required>
                                    <label class="form-check-label" for="noIndividual">No</label>
                                    <i class="fa fa-trash delete-dimensions-row" data-row="${rowID}" style="float: right; color: red; cursor: pointer; margin-left: 20px; font-size: 22px;" aria-hidden="true"></i>
                                </div>
                            </div>
                        </div>
                    </div>`;

        // Append the new row to the table body
        $(".dimensions-row").append(dimRow);

        // Increment the row counter
        rowCounter++;
    });

    // Handle click event on "Delete dimensions Row" buttons
    $(document).on("click", ".delete-dimensions-row", function(e) {
        e.preventDefault();
        let rowNum = $(this).data("row");
        jQuery('.' + rowNum).remove();
    })

    $("#checkAll").change(function() {
        $(".product-checkbox").prop("checked", $(this).prop("checked"));
    });

    function toggleMappingModal() {
        if (!$('#modalMapping').hasClass('show')) {
            Swal.fire({
                text: 'This will overwrite dimensions & weight with WooCommerce fields',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'OK',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.value) {
                    // User clicked "OK"
                    $('#modalMapping').toggleClass('show d-flex align-items-center justify-content-center');
                    return;
                } else {
                    // User clicked "Cancel"
                    return false;
                }
            });
        } else {
            $('#modalMapping').toggleClass('show d-flex align-items-center justify-content-center');
        }
    }

    // process / upload CSV file
    function processFile(file) {
        var deferred = $.Deferred();

        var formData = new FormData();
        formData.append("csvFile", file);

        const Url = '<?php echo esc_js(admin_url('admin-ajax.php', 'relative')); ?>?action=process_dimensions_csv';
        // AJAX request
        $.ajax({
            url: Url,
            type: "POST",
            data: formData,
            contentType: false,
            processData: false,
            success: function(response) {
                deferred.resolve(response); // Resolve the promise with the AJAX response
            },
            error: function(xhr, status, error) {
                deferred.reject(error); // Reject the promise in case of an error
            }
        });

        return deferred.promise();
    }

    function importDimensions() {
        toggleLoader();
        let fileInput = $('#dimensionsCsvFile')[0];
        var file = fileInput.files[0]; // file
        if (file) {
            // file type csv accepted only
            if (file.type === 'text/csv') {
                processFile(file)
                    .done(function(result) {

                        toggleCSVimportModal();

                        var message = '',
                            responseType = '';
                        if (result.status && result.status == '200' && result.data != '') {
                            message = 'CSV imported Successfully';
                            responseType = 'success';
                        }

                        if (result.data != '' && result.data.productsNotFound != '') {
                            message += '<br/>' + result.data.productsNotFound;
                            responseType = 'warning';
                        }

                        if (result.data != '' && result.data.noPackageFoundForRows != '') {
                            message += '<br/>' + result.data.noPackageFoundForRows;
                            responseType = 'warning';
                        }

                        toggleMappingModal();

                        Swal.fire('', message, responseType).then(function() {
                            location.reload();
                        });

                        toggleLoader();
                    })
                    .fail(function(error) {
                        toggleCSVimportModal();
                        console.error("Error:", error);
                    });
            } else {
                // if uploaded file is not CSV, return error
                var csvImportError = $('#dimensionsCsvFile').parents('#importDimensionsCSV').find('.csv_error_message');
                csvImportError.html('<small class="text-danger" error-for="csv_error">(Uploaded file is not a CSV file)</small>');
                toggleLoader();
            }
        } else {
            // if uploaded file is not CSV, return error
            var csvImportError = $('#dimensionsCsvFile').parents('#importDimensionsCSV').find('.csv_error_message');
            csvImportError.html('<small class="text-danger" error-for="csv_error">(No file added. Please select CSV file)</small>');
            toggleLoader();
        }
    }

    // for toggle bulk action modal
    function toggleActionModal() {

        var selectedBulkAction = $('#bulkAction').val();
        if (selectedBulkAction === "") {
            Swal.fire('', 'Please choose action type', 'error');
            return;
        }

        var selectedProducts = $("[name='products[]']:checked").length;
        if (!$(`#${selectedBulkAction}`).hasClass('show') && !selectedProducts) {
            Swal.fire('', 'Please choose at least 1 product for mapping', 'error');
            return;
        }

        $(`#${selectedBulkAction}`).toggleClass('show d-flex align-items-center justify-content-center');
    }

    // For toggle bulk update eligible for shipping modal
    function toggleEligibleForShippingModal() {
        const selectedProducts = $("[name='products[]']:checked").length;

        if (!$('#modalAllowShipping').hasClass('show') && !selectedProducts) {
            Swal.fire('', 'Please choose at least 1 product for mapping', 'error');
            return;
        }

        $('#selectProducts').text($("[name='products[]']:checked").length);

        $('#modalAllowShipping').toggleClass('show d-flex align-items-center justify-content-center');
    }

    // For bulk update eligible for shipping modal
    function bulkUpdateEligibleForShipping() {
        toggleLoader();
        var ajaxurl = '<?php echo WP_API_URL; ?>';

        const products = $("[name='productsList']").serialize();
        const allowFreeShipping = $("[name='updateEligibleForShipping']").serialize();
        const params = `action=post_bulk_allow_eligible_for_shipping&${products}&${allowFreeShipping}`;

        $.post(ajaxurl, params, function(result) {
            if (result.status == 200) {
                toggleEligibleForShippingModal();
                Swal.fire('', 'Eligible for shipping Updated Successfully', 'success').then(function() {
                    location.reload();
                });
            } else {
                Swal.fire('', result.message, 'error');
                toggleLoader();
            }
        });
    }

    // For toggle bulk update free shipping modal
    function toggleFreeShippingModal() {
        const selectedProducts = $("[name='products[]']:checked").length;

        if (!$('#modalFreeShipping').hasClass('show') && !selectedProducts) {
            Swal.fire('', 'Please choose at least 1 product for mapping', 'error');
            return;
        }

        $('#selectProducts').text($("[name='products[]']:checked").length);

        $('#modalFreeShipping').toggleClass('show d-flex align-items-center justify-content-center');
    }

    // For toggle bulk update individual shipping modal
    function toggleIndividualModal() {
        const selectedProducts = $("[name='products[]']:checked").length;

        if (!$('#modalIndividual').hasClass('show') && !selectedProducts) {
            Swal.fire('', 'Please choose at least 1 product for mapping', 'error');
            return;
        }

        $('#selectProducts').text($("[name='products[]']:checked").length);

        $('#modalIndividual').toggleClass('show d-flex align-items-center justify-content-center');
    }

    // For bulk update Individual shipping modal
    function bulkUpdateIndividual() {
        toggleLoader();
        var ajaxurl = '<?php echo WP_API_URL; ?>';

        const products = $("[name='productsList']").serialize();
        const updateIndividual = $("[name='updateIndividual']").serialize();
        const params = `action=post_bulk_allow_individual&${products}&${updateIndividual}`;

        $.post(ajaxurl, params, function(result) {
            if (result.status == 200) {
                toggleIndividualModal();
                Swal.fire('', 'Updated Individual shipping to the product(s) successfully', 'success').then(function() {
                    location.reload();
                });
            } else {
                Swal.fire('', result.message, 'error');
                toggleLoader();
            }
        });
    }
    // For bulk update free shipping modal
    function bulkUpdateFreeShipping() {
        toggleLoader();
        var ajaxurl = '<?php echo WP_API_URL; ?>';

        const products = $("[name='productsList']").serialize();
        const allowFreeShipping = $("[name='updateFreeShipping']").serialize();
        const params = `action=post_bulk_allow_free_shipping&${products}&${allowFreeShipping}`;

        $.post(ajaxurl, params, function(result) {
            if (result.status == 200) {
                toggleFreeShippingModal();
                Swal.fire('', 'Allowed Free shipping Successfully', 'success').then(function() {
                    location.reload();
                });
            } else {
                Swal.fire('', result.message, 'error');
                toggleLoader();
            }
        });
    }

    function togglePackagesModal() {
        const selectedProducts = $("[name='products[]']:checked").length;
        if (!$('#modalPackages').hasClass('show') && !selectedProducts) {
            Swal.fire('', 'Please choose at least 1 product for mapping', 'error');
            return;
        }

        $('#selectProducts').text($("[name='products[]']:checked").length);

        $('#modalPackages').toggleClass('show d-flex align-items-center justify-content-center');
    }

    function toggleLocationModal() {
        const selectedProducts = $("[name='products[]']:checked").length;
        if (!$('#modalLocations').hasClass('show') && !selectedProducts) {
            Swal.fire('', 'Please choose at least 1 product for mapping', 'error');
            return;
        }

        $('#selectProducts').text($("[name='products[]']:checked").length);

        $('#modalLocations').toggleClass('show d-flex align-items-center justify-content-center');
    }

    function updateAllowShipping(toggle) {
        toggleLoader();
        var ajaxurl = '<?php echo WP_API_URL; ?>';
        let isChecked = $(toggle).data('value');
        var shippingAllowed = "1";
        var newValue = "checked";
        if (isChecked) {
            shippingAllowed = "0";
            newValue = "";
        }
        var products = $(toggle).data('product');
        const params = `action=post_allow_shipping&${products}&fc_allow_shipping=${shippingAllowed}`;

        $.post(ajaxurl, params, function(result) {
            if (result.status == 200) {
                Swal.fire('', 'Allow Shipping Updated Successfully', 'success').then(function() {
                    $(toggle).data('value', newValue);
                });
            } else {
                Swal.fire('', result.message, 'error');
            }
            toggleLoader();
        });
    }
    // allow free shipping product level
    function updateAllowFreeShipping(toggle) {
        toggleLoader();
        var ajaxurl = '<?php echo WP_API_URL; ?>';
        let isChecked = $(toggle).data('value');
        var freeShippingAllowed = "1";
        var newValue = "checked";
        if (isChecked != '') {
            freeShippingAllowed = "0";
            newValue = "";
        }
        var products = $(toggle).data('product');
        const params = `action=post_allow_free_shipping&${products}&fc_allow_free_shipping=${freeShippingAllowed}`;

        $.post(ajaxurl, params, function(result) {
            if (result.status == 200) {
                Swal.fire('', 'Free Shipping Updated Successfully', 'success').then(function() {
                    $(toggle).data('value', newValue);
                });
            } else {
                Swal.fire('', result.message, 'error');
            }
            toggleLoader();
        });
    }

    function toggleWeightModal() {
        const selectedProducts = $("[name='products[]']:checked").length;
        if (!$('#modalWeight').hasClass('show') && !selectedProducts) {
            Swal.fire('', 'Please choose at least 1 product', 'error');
            return;
        }

        $('#selectProducts').text($("[name='products[]']:checked").length);

        $('#modalWeight').toggleClass('show d-flex align-items-center justify-content-center');
    }

    function toggleCSVimportModal() {
        $('#modalCSVimport').toggleClass('show d-flex align-items-center justify-content-center');
    }

    function updateProductMapping(e) {
        toggleLoader();
        var ajaxurl = '<?php echo WP_API_URL; ?>';

        const products = $("[name='productsList']").serialize();
        const mapping = $("[name='updateProductMappingDimensions']").serialize();
        const params = `action=post_map_existing_to_fc_packages&${mapping}&${products}`;

        $.post(ajaxurl, params, function(result) {

            if (result.status == 200) {
                $('form').trigger('reset');
                var message = 'Products Mapped Successfully';
                var responseType = 'success';

                if (result.data != '') {
                    message = result.data.message;
                    if (result.data.error) {
                        responseType = 'warning';
                        message += '<br/>' + result.data.error;
                    }
                }
                toggleMappingModal();
                Swal.fire('', message, responseType).then(function() {
                    location.reload();
                });
            } else {
                Swal.fire('', result.message, 'error');
            }
            toggleLoader();
        });

    }

    function mapProducts(e) {
        toggleLoader();
        var ajaxurl = '<?php echo WP_API_URL; ?>';

        const products = $("[name='productsList']").serialize();
        const packages = $("[name='selectedPackages']").serialize();
        const params = `action=post_map_fc_packages&${products}&${packages}`;

        var isEmpty = false;
        $(".dimensions-row input[type='text']").each(function() {
            if ($(this).val() === "") {
                isEmpty = true;
                return false; // Exit the loop early if an empty field is found
            }
        });

        var productsWidth = $('#width').val();
        var productsHeight = $('#height').val();
        var productsLength = $('#length').val();
        if (!productsWidth || !productsHeight || !productsLength || !$('#package_type').val() || isEmpty) {
            Swal.fire('', 'Missing required fields', 'error');
            toggleLoader();
            return;
        }
        // if product is set as not sent individually for shipping
        var individualShipped = $("[name='individual']").serializeArray();
        if (!individualShipped[0].value || individualShipped[0].value == 0) {
            var allPackages = <?php echo $packages_json; ?>;
            if (Array.isArray(allPackages) && allPackages.length > 0) {
                var packageAvailable = 0;
                $.each(allPackages, function(key, package) {
                    let packageWidth = package.outside_w;
                    let packageHeight = package.outside_h;
                    let packageLength = package.outside_l;
                    let widthFound = 0,
                        heightFound = 0,
                        lengthFound = 0;
                    if (parseInt(packageWidth) > parseInt(productsWidth)) {
                        widthFound = 1;
                    }

                    if (parseInt(packageHeight) > parseInt(productsHeight)) {
                        heightFound = 1;
                    }

                    if (parseInt(packageLength) > parseInt(productsLength)) {
                        lengthFound = 1;
                    }

                    if (widthFound && heightFound && lengthFound) {
                        packageAvailable = 1;
                    }
                });

                if (!packageAvailable) {
                    Swal.fire('', 'There is no shipping box for the added products', 'error');
                    toggleLoader();
                    return;
                }
            } else {
                Swal.fire('', 'Please Add Shipping Boxes', 'error');
                toggleLoader();
                return;
            }
        }

        $.post(ajaxurl, params, function(result) {
            if (result.status == 200) {
                $('form').trigger('reset');
                $('.multiple-packages').val(null).trigger("change");

                togglePackagesModal();

                Swal.fire('', 'Dimensions Updated Successfully', 'success').then(function() {
                    location.reload();
                });
            } else {
                Swal.fire('', result.message, 'error');
            }
            toggleLoader();
        });

        return false;

    }

    function updateLocationData() {
        if (!$('#locationsList').val()) {
            Swal.fire('', 'Please Select Location', 'error');
            return;

        } else {
            toggleLoader();
            var ajaxurl = '<?php echo WP_API_URL; ?>';

            const products = $("[name='productsList']").serialize();
            const locationData = $("[name='updateLocation']").serialize();
            const params = `action=post_update_location&${products}&${locationData}`;

            $.post(ajaxurl, params, function(result) {
                if (result.status == 200) {
                    $('form').trigger('reset');
                    $('.multiple-packages').val(null).trigger("change");

                    toggleLocationModal();

                    Swal.fire('', 'Location Updated Successfully', 'success').then(function() {
                        location.reload();
                    });
                } else {
                    Swal.fire('', result.message, 'error');
                }
                toggleLoader();
            });
        }
    }

    function updateWeight() {
        if (!$('#weight').val()) {
            Swal.fire('', 'Please Enter Weight', 'error');
            return;

        } else {
            toggleLoader();
            var ajaxurl = '<?php echo WP_API_URL; ?>';

            const products = $("[name='productsList']").serialize();
            const weight = $("[name='updatedWeight']").serialize();
            const params = `action=post_update_weight&${products}&${weight}`;

            $.post(ajaxurl, params, function(result) {
                if (result.status == 200) {
                    $('form').trigger('reset');
                    $('.multiple-packages').val(null).trigger("change");

                    toggleWeightModal();

                    Swal.fire('', 'Weight Updated Successfully', 'success').then(function() {
                        location.reload();
                    });
                } else {
                    Swal.fire('', result.message, 'error');
                }
                toggleLoader();
            });
        }
    }

    $('#locationsBy').change(function() {
        $('#locationsList').empty();
        if ($(this).val() == "name") {
            locationNames.forEach((option) => {
                $('#locationsList').append($('<option></option>').val(option.id).text(option.location_name));
            });
        } else {
            locationTags.forEach((option) => {
                $('#locationsList').append($('<option></option>').val(option.id).text(option.name));
            });
        }
    });

    $(document).ready(function() {
        // On click of the parent row, toggle the children rows
        $('.product-parent-row').on('click', function(event) {
            event.stopPropagation();
            if ($(event.target).hasClass('no-click-effect')) {} else {
                var childId = $(this).data('parent-id');
                $(this).nextAll('.product-child-row-' + childId).toggle('slow');
                $(this).find('i').toggleClass('fa-chevron-down fa-chevron-up');
            }
        });
    });

    $('.filter-out-btn').click(function() {
        toggleLoader();
    })

    document.querySelectorAll('.hover-cell').forEach(cell => {
        cell.addEventListener('mouseover', function(event) {
            const tooltip = document.getElementById('custom-tooltip');
            const cellRect = cell.getBoundingClientRect();

            // Set the text of the tooltip
            tooltip.textContent = cell.textContent;

            // Temporarily display the tooltip to measure its height
            tooltip.style.opacity = '0';
            tooltip.style.display = 'block';

            // Calculate the position above the cell
            const tooltipHeight = tooltip.offsetHeight;
            tooltip.style.left = `${cellRect.left + window.scrollX + cellRect.width / 2}px`;
            tooltip.style.top = `${cellRect.top + window.scrollY - tooltipHeight - 5}px`;

            // Center the tooltip horizontally
            tooltip.style.transform = 'translateX(-50%)';

            // Show the tooltip
            tooltip.style.opacity = '1';
        });

        cell.addEventListener('mouseout', function() {
            const tooltip = document.getElementById('custom-tooltip');
            // Hide the tooltip
            tooltip.style.opacity = '0';
            tooltip.style.display = 'none';
        });
    });
</script>