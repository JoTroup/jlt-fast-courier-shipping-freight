<?php
if ($product->is_type('variable')) {
    $variations = $product->get_available_variations();
    if (!empty($variations) && count($variations) > 0) { ?>
        <div class="row">
            <p style="font-size: 16px; font-weight: 700;">Variations Shipping Configuration</p>
        </div>
        <?php foreach ($variations as $variation) {
            $variable_product = wc_get_product($variation['variation_id']);
            $variations_custom_fields = get_post_meta($variation['variation_id']);
            $variations_dimensionsData = [];
            $rowNumber = 0;
            foreach ($variations_custom_fields as $key => $value) {
                if (in_array($key, array('fc_length', 'fc_width', 'fc_height', 'fc_weight', 'fc_is_individual', 'fc_package_type'))) {
                    $variations_dimensionsData[0][$key] = $value[0];
                }
                // Check if the key ends with a number (e.g., "length1", "width2")
                if (preg_match('/^(\w+)(\d+)$/', $key, $matches)) {
                    // Get the base key (e.g., "length", "width")
                    $base_key = $matches[1];
                    // Check if the base key is one of "length," "width," "height," or "weight"
                    if (in_array($base_key, array('fc_length_', 'fc_width_', 'fc_height_', 'fc_weight_', 'fc_is_individual_', 'fc_package_type_'))) {
                        $index = $matches[2];
                        $variations_dimensionsData[$index][$key] = $value[0];
                    }
                }
            }
        ?>
            <div class="row">
                <p class="m-0" style="font-size: 15px; font-weight: 700;">For SKU: <?php echo esc_html($variable_product->get_sku()) ?></p>
            </div>
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
                        <tbody id="variants_packages_<?= $variation['variation_id'] ?>">
                            <?php if (!empty($variations_dimensionsData)) {
                                foreach ($variations_dimensionsData as $index => $variation_dimension) {
                                    $rowNumber = $index + 1;
                            ?>
                                    <tr class="dimension_<?= $index ?>">
                                        <td></td>
                                        <td>
                                            <?php
                                            $fieldKey = "fc_package_type";
                                            $field = "vData[" . $variation['variation_id'] . "][" . $fieldKey . "]";
                                            $value = $variation_dimension[$fieldKey] ?? "";
                                            if ($index > 0) {
                                                $fieldKey = "fc_package_type_" . $index;
                                                $field = "vData[" . $variation['variation_id'] . "][" . $fieldKey . "]";
                                                $value =  $variation_dimension[$fieldKey];
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
                                                <?php $fieldKey = "fc_length";
                                                $field = "vData[" . $variation['variation_id'] . "][" . $fieldKey . "]";
                                                $value = $variation_dimension[$fieldKey] ?? "";
                                                if ($index > 0) {
                                                    $fieldKey = "fc_length_" . $index;
                                                    $field = "vData[" . $variation['variation_id'] . "][" . $fieldKey . "]";
                                                    $value =  $variation_dimension[$fieldKey];
                                                } ?>
                                                <span class="form-check-label" for="">L: </span>
                                                <input type='text' class="package-dimensions form-control numericInput" name='<?php echo $field ?>' value="<?php echo esc_attr($value) ?>">
                                            </div>

                                            <div class="form-check form-check-inline">
                                                <?php $fieldKey = "fc_width";
                                                $field = "vData[" . $variation['variation_id'] . "][" . $fieldKey . "]";
                                                $value = $variation_dimension[$fieldKey] ?? "";
                                                if ($index > 0) {
                                                    $fieldKey = "fc_width_" . $index;
                                                    $field = "vData[" . $variation['variation_id'] . "][" . $fieldKey . "]";
                                                    $value =  $variation_dimension[$fieldKey];
                                                } ?>
                                                <span class="form-check-label" for="">W: </span>
                                                <input type='text' class="package-dimensions form-control numericInput" name='<?php echo $field ?>' value="<?php echo esc_attr($value) ?>">
                                            </div>

                                            <div class="form-check form-check-inline">
                                                <?php $fieldKey = "fc_height";
                                                $field = "vData[" . $variation['variation_id'] . "][" . $fieldKey . "]";
                                                $value = $variation_dimension[$fieldKey] ?? "";
                                                if ($index > 0) {
                                                    $fieldKey = "fc_height_" . $index;
                                                    $field = "vData[" . $variation['variation_id'] . "][" . $fieldKey . "]";
                                                    $value =  $variation_dimension[$fieldKey];
                                                } ?>
                                                <span class="form-check-label" for="">H: </span>
                                                <input type='text' class="package-dimensions form-control numericInput" name='<?php echo $field ?>' value="<?php echo esc_attr($value) ?>">
                                            </div>
                                        </td>
                                        <td>
                                            <?php $fieldKey = "fc_weight";
                                            $field = "vData[" . $variation['variation_id'] . "][" . $fieldKey . "]";
                                            $value = $variation_dimension[$fieldKey] ?? "";
                                            if ($index > 0) {
                                                $fieldKey = "fc_weight_" . $index;
                                                $field = "vData[" . $variation['variation_id'] . "][" . $fieldKey . "]";
                                                $value =  $variation_dimension[$fieldKey];
                                            } ?>
                                            <input type='text' name='<?php echo $field ?>' value="<?php echo esc_attr($value) ?>" class="form-control numericInput">
                                        </td>
                                        <td>
                                            <?php $fieldKey = "fc_is_individual";
                                            $field = "vData[" . $variation['variation_id'] . "][" . $fieldKey . "]";
                                            $value = $variation_dimension[$fieldKey] ?? "";
                                            if ($index > 0) {
                                                $fieldKey = "fc_is_individual_" . $index;
                                                $field = "vData[" . $variation['variation_id'] . "][" . $fieldKey . "]";
                                                $value =  $variation_dimension[$fieldKey] ?? "";
                                            } ?>
                                            <?php $checked = "";
                                            if ($value) {
                                                $checked = esc_attr('checked');
                                            } ?>
                                            <input type='checkbox' style="margin-top: 10px;" <?php echo $checked ?> value='1' name='<?php echo $field ?>' class='form-control'>
                                            <?php if ($index > 0) { ?>
                                                <i class="fa fa-trash delete-dimensions-row" data-parent="variants_packages_<?= $variation['variation_id'] ?>" data-row="<?= $index ?>" style="float: right;color: red;cursor: pointer;margin: 10px 20px 0 0;font-size: 20px;" aria-hidden="true"></i>
                                            <?php } ?>
                                        </td>
                                    </tr>
                                <?php }
                            } else { ?>
                                <tr>
                                    <td></td>
                                    <td>
                                        <?php
                                        $fieldKey = "fc_package_type";
                                        $field = "vData[" . $variation['variation_id'] . "][" . $fieldKey . "]";
                                        ?>
                                        <select name='<?php echo $field ?>'>
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
                                        <?php $fieldKey = "fc_length";
                                        $field = "vData[" . $variation['variation_id'] . "][" . $fieldKey . "]"; ?>
                                        <div class="form-check form-check-inline">
                                            <span class="form-check-label" for="">L: </span>
                                            <input type='text' class="package-dimensions form-control numericInput" name='<?php echo $field ?>' value="">
                                        </div>
                                        <?php $fieldKey = "fc_width";
                                        $field = "vData[" . $variation['variation_id'] . "][" . $fieldKey . "]"; ?>
                                        <div class="form-check form-check-inline">
                                            <span class="form-check-label" for="">W: </span>
                                            <input type='text' class="package-dimensions form-control numericInput" name='<?php echo $field ?>' value="">
                                        </div>
                                        <?php $fieldKey = "fc_height";
                                        $field = "vData[" . $variation['variation_id'] . "][" . $fieldKey . "]"; ?>
                                        <div class="form-check form-check-inline">
                                            <span class="form-check-label" for="">H: </span>
                                            <input type='text' class="package-dimensions form-control numericInput" name='<?php echo $field ?>' value="">
                                        </div>
                                    </td>
                                    <td>
                                        <?php $fieldKey = "fc_weight";
                                        $field = "vData[" . $variation['variation_id'] . "][" . $fieldKey . "]"; ?>
                                        <input type='text' name='<?php echo $field ?>' value="" class="form-control numericInput">
                                    </td>
                                    <td>
                                        <?php $fieldKey = "fc_is_individual";
                                        $field = "vData[" . $variation['variation_id'] . "][" . $fieldKey . "]"; ?>
                                        <input type='checkbox' style="margin-top: 10px;" checked value='1' name='<?php echo $field ?>' class='form-control'>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="row">
                <p style="font-size: 14px; font-weight: 600;" data-prefix="vData[<?= $variation['variation_id'] ?>]" data-parent="variants_packages_<?= $variation['variation_id'] ?>" data-row="<?php echo ($rowNumber == 0) ? 1 : $rowNumber ?>" class="add-dimensions-row"><a href="#">Add More Row</a></p>
            </div>
            <div class="row">
                <p style="font-size: 15px; font-weight: 700;">Location Assigment</p>
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
                                    <?php
                                    $fieldKey = "fc_location_type";
                                    $location_type = $variable_product->get_meta($fieldKey);
                                    $field = "vData[" . $variation['variation_id'] . "][" . $fieldKey . "]";
                                    ?>
                                    <select name="<?php echo $field ?>" id="locationsBy" class="form-control w-100">
                                        <option value="name" <?php echo $location_type == 'name' ? 'selected' : '' ?>>Name</option>
                                        <option value="tags" <?php echo $location_type == 'tags' ? 'selected' : '' ?>>Tags</option>
                                    </select>
                                </td>
                                <td class="d-flex flex-column">
                                    <?php $fieldKey = "fc_location";
                                    $field = "vData[" . $variation['variation_id'] . "][" . $fieldKey . "]";
                                    $location = $variable_product->get_meta($fieldKey);
                                    ?>
                                    <select name="<?php echo $field ?>" id="locationsList" class="form-control w-100">
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
<?php }
    }
}
?>