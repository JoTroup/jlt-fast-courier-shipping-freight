<?php

use FastCourier\FastCourierManagePackages;
use FastCourier\FastCourierLocation;

global $wpdb, $fc_packages_table, $post, $fc_woo_field, $woo_packages_table;

$product = wc_get_product();
$height = $product->get_meta('fc_height');
$weight = $product->get_meta('fc_weight');
$width = $product->get_meta('fc_width');
$length = $product->get_meta('fc_length');
$is_individual = $product->get_meta('fc_is_individual');
$type = $product->get_meta('fc_package_type');
$location_type = $product->get_meta('fc_location_type');
$location = $product->get_meta('fc_location');
$custom_fields = get_post_meta($product->get_id());

$fc_allow_shipping = 'Yes';
if ($product->get_meta('fc_allow_shipping') == "0") {
    $fc_allow_shipping = 'No';
}

$fc_allow_free_shipping = 'No';
if ($product->get_meta('fc_allow_free_shipping') == "1") {
    $fc_allow_free_shipping = 'Yes';
}

$dimensionsData = [];
$rowCounter = 0;
foreach ($custom_fields as $key => $value) {
    if (in_array($key, array('fc_length', 'fc_width', 'fc_height', 'fc_weight', 'fc_is_individual', 'fc_package_type'))) {
        $dimensionsData[0][$key] = $value[0];
    }
    // Check if the key ends with a number (e.g., "length1", "width2")
    if (preg_match('/^(\w+)(\d+)$/', $key, $matches)) {
        // Get the base key (e.g., "length", "width")
        $base_key = $matches[1];
        // Check if the base key is one of "length," "width," "height," or "weight"
        if (in_array($base_key, array('fc_length_', 'fc_width_', 'fc_height_', 'fc_weight_', 'fc_is_individual_', 'fc_package_type_'))) {
            $index = $matches[2];
            $dimensionsData[$index][$key] = $value[0];
        }
    }
}

$packs = FastCourierManagePackages::getPackageTypes();
// Locations
$locations = FastCourierLocation::index();
$jsLocations = json_encode($locations);
//tags
$tags = FastCourierLocation::tags();
$jsTags = json_encode($tags);

?>
<div class="panel woocommerce_options_panel hidden" id="fcPackagesSelection">
    <div class="container-fluid">
        <?php
        if ($product->is_type('variable')) {
            include('product-variations.php');
        } else {
        ?>
            <div class="row">
                <div class="col-sm-12">
                    <table class="table table-striped table-packages">
                        <thead>
                            <tr>
                                <th>Sr. No.</th>
                                <th>Package Type</th>
                                <th>Dimensions</th>
                                <th>Shipping Weight (KGs)</th>
                                <th>Ship Individually</th>
                            </tr>
                        </thead>
                        <tbody id="packages">
                            <?php if (!empty($dimensionsData)) {
                                foreach ($dimensionsData as $index => $dimension) {
                                    $rowCounter = $index + 1; ?>
                                    <tr class="dimension_<?= $index ?>">
                                        <td></td>
                                        <td>
                                            <?php $field = 'fc_package_type';
                                            $value = $dimension[$field] ?? "";
                                            if ($index > 0) {
                                                $field = 'fc_package_type_' . $index;
                                                $value =  $dimension[$field];
                                            } ?>
                                            <select name='<?php echo $field ?>'>
                                                <?php foreach ($packs as $pack) {
                                                ?>
                                                    <option value='<?php echo esc_attr($pack['name']) ?>' <?php echo $pack['name'] == $value ? 'selected' : '' ?>><?php echo esc_html($pack['name']) ?></option>
                                                <?php
                                                }
                                                ?>
                                            </select>
                                        </td>
                                        <td>
                                            <div class="form-check form-check-inline">
                                                <?php $field = 'fc_length';
                                                $value = $dimension[$field] ?? "";
                                                if ($index > 0) {
                                                    $field = 'fc_length_' . $index;
                                                    $value =  $dimension[$field];
                                                } ?>
                                                <span class="form-check-label" for="">L: </span>
                                                <input type='text' class="package-dimensions form-control numericInput" name='<?php echo $field ?>' value="<?php echo esc_attr($value) ?>">
                                            </div>

                                            <div class="form-check form-check-inline">
                                                <?php $field = 'fc_width';
                                                $value = $dimension[$field] ?? "";
                                                if ($index > 0) {
                                                    $field = 'fc_width_' . $index;
                                                    $value =  $dimension[$field];
                                                } ?>
                                                <span class="form-check-label" for="">W: </span>
                                                <input type='text' class="package-dimensions form-control numericInput" name='<?php echo $field ?>' value="<?php echo esc_attr($value) ?>">
                                            </div>

                                            <div class="form-check form-check-inline">
                                                <?php $field = 'fc_height';
                                                $value = $dimension[$field] ?? "";
                                                if ($index > 0) {
                                                    $field = 'fc_height_' . $index;
                                                    $value =  $dimension[$field];
                                                } ?>
                                                <span class="form-check-label" for="">H: </span>
                                                <input type='text' class="package-dimensions form-control numericInput" name='<?php echo $field ?>' value="<?php echo esc_attr($value) ?>">
                                            </div>
                                        </td>
                                        <td>
                                            <?php $field = 'fc_weight';
                                            $value = $dimension[$field] ?? "";
                                            if ($index > 0) {
                                                $field = 'fc_weight_' . $index;
                                                $value =  $dimension[$field];
                                            } ?>
                                            <input type='text' name='<?php echo $field ?>' value="<?php echo esc_attr($value) ?>" class="form-control numericInput">
                                        </td>
                                        <td>
                                            <?php $field = 'fc_is_individual';
                                            $value = $dimension[$field] ?? "";
                                            if ($index > 0) {
                                                $field = 'fc_is_individual_' . $index;
                                                $value =  $dimension[$field] ?? "";
                                            } ?>
                                            <?php $checked = "";
                                            if ($value) {
                                                $checked = esc_attr('checked');
                                            } ?>
                                            <input type='checkbox' style="margin-top: 10px;" <?php echo $checked ?> value='1' name='<?php echo $field ?>' class='form-control'>
                                            <?php if ($index > 0) { ?>
                                                <i class="fa fa-trash delete-dimensions-row" data-parent="packages" data-row="<?= $index ?>" style="float: right;color: red;cursor: pointer;margin: 10px 20px 0 0;font-size: 20px;" aria-hidden="true"></i>
                                            <?php } ?>
                                        </td>
                                    </tr>
                                <?php }
                            } else { ?>
                                <tr>
                                    <td></td>
                                    <td>
                                        <select name='fc_package_type'>
                                            <?php
                                            foreach ($packs as $pack) {
                                            ?>
                                                <option value='<?php echo esc_attr($pack['name']) ?>' <?php echo $pack['name'] == 'box' ? 'selected' : '' ?>><?php echo esc_html($pack['name']) ?></option>
                                            <?php
                                            }
                                            ?>
                                        </select>
                                    </td>
                                    <td>
                                        <div class="form-check form-check-inline">
                                            <span class="form-check-label" for="">L: </span>
                                            <input type='text' class="package-dimensions form-control numericInput" name='fc_length' value="">
                                        </div>

                                        <div class="form-check form-check-inline">
                                            <span class="form-check-label" for="">W: </span>
                                            <input type='text' class="package-dimensions form-control numericInput" name='fc_width' value="">
                                        </div>

                                        <div class="form-check form-check-inline">
                                            <span class="form-check-label" for="">H: </span>
                                            <input type='text' class="package-dimensions form-control numericInput" name='fc_height' value="">
                                        </div>
                                    </td>
                                    <td>
                                        <input type='text' name='fc_weight' value="" class="form-control numericInput">
                                    </td>
                                    <td>
                                        <input type='checkbox' style="margin-top: 10px;" checked value='1' name='fc_is_individual' class='form-control'>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="row">
                <p style="font-size: 14px; font-weight: 600;" data-prefix="" data-parent="packages" data-row="<?php echo ($rowCounter == 0) ? 1 : $rowCounter ?>" class="add-dimensions-row"><a href="#">Add More Row</a></p>
            </div>
            <div class="row">
                <p style="font-size: 16px; font-weight: 700;">Location Assigment</p>
            </div>
            <div class="row">
                <div class="col-sm-12">
                    <table class="table table-striped table-packages">
                        <thead>
                            <tr>
                                <th>Sr. No.</th>
                                <th>Location By</th>
                                <th>Location/Tag List</th>
                            </tr>
                        </thead>
                        <tbody id="locations">
                            <tr>
                                <td></td>

                                <td>
                                    <select name="fc_location_type" id="locationsBy" class="form-control w-100">
                                        <option value="name" <?php echo $location_type == 'name' ? 'selected' : '' ?>>Name</option>
                                        <option value="tags" <?php echo $location_type == 'tags' ? 'selected' : '' ?>>Tags</option>
                                    </select>
                                </td>
                                <td class="d-flex flex-column">
                                    <select name="fc_location" id="locationsList" class="form-control w-100">
                                        <?php
                                        if ($location_type == 'tags') {
                                            foreach ($tags as $tag) {
                                        ?>
                                                <option value='<?php echo esc_html($tag['id']) ?>' <?php echo esc_html($tag['id']) == $location ? 'selected' : '' ?>><?php echo esc_html($tag['name']) ?></option>
                                            <?php }
                                        } else {
                                            foreach ($locations as $location_tag) {
                                            ?>
                                                <option value='<?php echo esc_html($location_tag['id']) ?>' <?php echo esc_html($location_tag['id']) == $location ? 'selected' : '' ?>><?php echo esc_html($location_tag['location_name']) ?></option>
                                        <?php }
                                        }
                                        ?>
                                    </select>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php } ?>
        <div class="row">
            <div class="col-sm-12">
                <div class="row">
                    <div class="col-sm-10" style="padding-left: 9px;">
                        <div class="d-flex">
                            <div class="w-25">
                                <p style="font-size: 13px; font-weight: 700;">Eligible for Shipping</p>
                            </div>
                            <div class="w-75">
                                <select class="form-control h-50 w-75" style="margin: 11px 5px;" name="fc_allow_shipping" id="fcAllowShipping">
                                    <option value="0" <?php echo esc_html($fc_allow_shipping) == "No" ? 'selected' : '' ?>>No</option>
                                    <option value="1" <?php echo esc_html($fc_allow_shipping) == "Yes" ? 'selected' : '' ?>>Yes</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-12">
                <div class="row">
                    <div class="col-sm-10" style="padding-left: 9px;">
                        <div class="d-flex">
                            <div class="w-25">
                                <p style="font-size: 13px; font-weight: 700;">Free Shipping</p>
                            </div>
                            <div class="w-75">
                                <select class="form-control h-50 w-75" style="margin: 11px 5px;" name="fc_allow_free_shipping" id="fcAllowFreeShipping">
                                    <option value="0" <?php echo esc_html($fc_allow_free_shipping) == "No" ? 'selected' : '' ?>>No</option>
                                    <option value="1" <?php echo esc_html($fc_allow_free_shipping) == "Yes" ? 'selected' : '' ?>>Yes</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    var locationNames = JSON.parse('<?php echo $jsLocations ?>');
    var locationTags = JSON.parse('<?php echo $jsTags ?>');
</script>
<script>
    $(document).on('click', '.add-dimensions-row', function(echo) {
        let rowID = $(this).data('row');
        let parent = $(this).data('parent');
        let prefix = $(this).data('prefix');
        let tableRow = "";
        if (prefix != "") {
            tableRow = `<tr class="dimension_${rowID}">
                        <td></td>
                        <td>
                            <select name='${prefix}[fc_package_type_${rowID}]'>
                                <?php foreach ($packs as $pack) { ?>
                                    <option value='<?php echo esc_attr($pack['name']) ?>' <?php echo $pack['name'] == 'box' ? 'selected' : '' ?>><?php echo esc_html($pack['name']) ?></option>
                                <?php } ?>
                            </select>
                        </td>
                        <td>
                            <div class="form-check form-check-inline">
                                <span class="form-check-label" for="">L: </span>
                                <input type='text' class="package-dimensions numericInput" name='${prefix}[fc_length_${rowID}]' class="form-control">
                            </div>

                            <div class="form-check form-check-inline">
                                <span class="form-check-label" for="">W: </span>
                                <input type='text' class="package-dimensions numericInput" name='${prefix}[fc_width_${rowID}]' class="form-control">
                            </div>

                            <div class="form-check form-check-inline">
                                <span class="form-check-label" for="">H: </span>
                                <input type='text' class="package-dimensions numericInput" name='${prefix}[fc_height_${rowID}]' class="form-control">
                            </div>
                        </td>
                        <td>
                            <input type='text' name='${prefix}[fc_weight_${rowID}]' class="form-control numericInput">
                        </td>
                        <td>
                            <input type='checkbox' style="margin-top: 10px;" checked value='1' name='${prefix}[fc_is_individual_${rowID}]' class='form-control'>
                            <i class="fa fa-trash delete-dimensions-row" data-row="${rowID}" style="float: right;color: red;cursor: pointer;margin: 10px 20px 0 0;font-size: 20px;" aria-hidden="true"></i>
                        </td>
                    </tr>`;
        } else {
            tableRow = `<tr class="dimension_${rowID}">
                        <td></td>
                        <td>
                            <select name='fc_package_type_${rowID}'>
                                <?php foreach ($packs as $pack) { ?>
                                    <option value='<?php echo esc_attr($pack['name']) ?>' <?php echo $pack['name'] == 'box' ? 'selected' : '' ?>><?php echo esc_html($pack['name']) ?></option>
                                <?php } ?>
                            </select>
                        </td>
                        <td>
                            <div class="form-check form-check-inline">
                                <span class="form-check-label" for="">L: </span>
                                <input type='text' class="package-dimensions numericInput" name='fc_length_${rowID}' class="form-control">
                            </div>

                            <div class="form-check form-check-inline">
                                <span class="form-check-label" for="">W: </span>
                                <input type='text' class="package-dimensions numericInput" name='fc_width_${rowID}' class="form-control">
                            </div>

                            <div class="form-check form-check-inline">
                                <span class="form-check-label" for="">H: </span>
                                <input type='text' class="package-dimensions numericInput" name='fc_height_${rowID}' class="form-control">
                            </div>
                        </td>
                        <td>
                            <input type='text' name='fc_weight_${rowID}' class="form-control numericInput">
                        </td>
                        <td>
                            <input type='checkbox' style="margin-top: 10px;" checked value='1' name='fc_is_individual_${rowID}' class='form-control'>
                            <i class="fa fa-trash delete-dimensions-row" data-row="${rowID}" style="float: right;color: red;cursor: pointer;margin: 10px 20px 0 0;font-size: 20px;" aria-hidden="true"></i>
                        </td>
                    </tr>`;
        }
        $("#" + parent).append(tableRow);
        var newValue = rowID + 1;
        $(this).data('row', newValue);
    })

    // Handle click event on "Delete dimensions Row" buttons
    $(document).on("click", ".delete-dimensions-row", function(e) {
        e.preventDefault();
        let rowNum = $(this).data("row");
        Swal.fire({
                title: "Do you want to delete the selected row?",
                icon: 'warning',
                theme: 'warning',
                showDenyButton: true,
                showCancelButton: true,
                confirmButtonText: 'Delete',
                denyButtonText: `Cancel`,
                dangerMode: true,
            })
            .then((result) => {
                if (result.value) {
                    $(this).closest('.dimension_' + rowNum).remove();
                }
            });
    })

    function toggleDeletion(element, packageId) {
        if (jQuery('#packages > tr').length == 1) {
            Swal.fire("", "At least 1 package is required", "error");
        } else {
            if (packageId) {
                Swal.fire({
                        title: "Are you sure?",
                        text: "Once deleted, you will not be able to recover this pacakge!",
                        icon: "warning",
                        buttons: true,
                        dangerMode: true,
                    })
                    .then((willDelete) => {
                        if (willDelete) {
                            deletePackage(element, packageId);
                        }
                    });
            } else {
                jQuery(element.parentNode.parentNode).remove();
            }
        }
    }

    function deletePackage(element, packageId) {
        var ajaxurl = '<?php echo esc_js(admin_url('admin-ajax.php', 'relative')); ?>';

        var formData = `id=${packageId}&action=post_delete_woo_packages`;

        jQuery.post(ajaxurl, formData, function(response) {
            if (response == 1) {
                jQuery(element.parentNode.parentNode).remove();
                Swal.fire("", "Package deleted", "success");
            } else {
                Swal.fire("", response, "error");
            }
        });

    }

    $('#locationsBy').change(function() {
        $('#locationsList').empty();
        if ($(this).val() == "name") {
            locationNames.forEach((option) => {
                $('#locationsList').append($('<option></option>').val(option.id).text(option.name));
            });
        } else {
            locationTags.forEach((option) => {
                $('#locationsList').append($('<option></option>').val(option.id).text(option.name));
            });
        }
    });
    // For numeric values, accept integer or float values only
    $(document).on("input", ".numericInput", function() {
        var inputValue = $(this).val();
        // Remove any non-numeric and non-decimal characters
        var sanitizedInput = inputValue.replace(/[^0-9.]/g, "");
        // Ensure there is only one decimal point
        var decimalCount = sanitizedInput.split(".").length - 1;
        if (decimalCount > 1) {
            sanitizedInput = sanitizedInput.substring(0, sanitizedInput.lastIndexOf("."));
        }
        // Update the input value with the sanitized content
        $(this).val(sanitizedInput);
    });
</script>