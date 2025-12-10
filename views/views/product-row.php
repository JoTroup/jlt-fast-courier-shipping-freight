<?php
// fetch product on the behalf of variant id
if (isset($child_product_id)) {

    // This is a variation, use $variation_product
    $child_product = wc_get_product($child_product_id);
    if($child_product) {
        $isVirtualChildProduct = false;
        if (isset($groupedProduct)) {
            $isVirtualChildProduct = $child_product->is_virtual();
        }
        $custom_fields = get_post_meta($child_product->get_id());
        $weight = $dimensions = $individual = $otherPackageTypes = [];
        foreach ($custom_fields as $key => $value) {
            // Check if the key ends with a number (e.g., "length1", "width2")
            if (preg_match('/^(\w+)(\d+)$/', $key, $matches)) {
                // Get the base key (e.g., "length", "width")
                $base_key = $matches[1];
                // Check if the base key is one of "length," "width," "height," or "weight"
                if (in_array($base_key, array('fc_length_', 'fc_width_', 'fc_height_', 'fc_weight_', 'fc_is_individual_', 'fc_package_type_'))) {
                    $index = $matches[2];
                    if ($base_key == 'fc_is_individual_') {
                        $individual[$index] = ($value[0]) ? 'Yes' : 'No';
                    } elseif ($base_key == 'fc_package_type_') {
                        $otherPackageTypes[$index] = $value[0];
                    } elseif ($base_key == 'fc_weight_') {
                        $weight[$index] = $value[0];
                    } else {
                        $dimensions[$index][$base_key] = $value[0];
                    }
                }
            }
        }
?>
    <tr class="fc-bg-color-light-gray product-child-row product-child-row-<?= $productId ?>">
        <td><input type="checkbox" class="product-checkbox" value="pId=<?php echo esc_html($child_product->get_id()) . "&productType=variable" ?>" name="products[]"></td>
        <td><a href="<?php echo esc_url(get_edit_post_link($child_product->get_id())) ?>"><?php echo esc_html($child_product->get_name()) ?></td>
        <?php
            $tooltipClass = '';
            $sku = esc_html($child_product->get_sku());
            if(strlen($sku) > 15) {
                $tooltipClass = "truncate-text hover-cell";
            } ?>
        <td class="<?php echo $tooltipClass ?>"> <?php echo $sku; ?> </td>
        <td>$<?php echo esc_html($child_product->get_price()) ?></td>
        <td><?php echo wc_get_product_category_list($child_product->get_id()) ?></td>
        <td>
            <?php echo "--"; ?>
        </td>
        <td class="text-center">
            <?php if (!$isVirtualChildProduct) {
                echo esc_html($child_product->get_meta('fc_length')) ?> x <?php echo esc_html($child_product->get_meta('fc_width')) ?> x <?php echo esc_html($child_product->get_meta('fc_height')); ?>
            <?php
                foreach ($dimensions as $item) {
                    $string = '';
                    // Check and concatenate "fc_length_"
                    if (isset($item['fc_length_']) && $item['fc_length_'] !== '') {
                        $string .= $item['fc_length_'] . ' X ';
                    }
                    // Check and concatenate "fc_width_"
                    if (isset($item['fc_width_']) && $item['fc_width_'] !== '') {
                        $string .= $item['fc_width_'] . ' X ';
                    }
                    // Check and concatenate "fc_height_"
                    if (isset($item['fc_height_']) && $item['fc_height_'] !== '') {
                        $string .= $item['fc_height_'];
                    }
                    // Remove the trailing " X " if present
                    $string = rtrim($string, ' X ');
                    // Add the string to the result array
                    if (isset($string)) {
                        echo '<br/>' . $string;
                    }
                }
            } else {
                echo "--";
            } ?>
        </td>
        <td>
            <?php if (!$isVirtualChildProduct) {
                echo esc_html($child_product->get_meta('fc_weight'));
                echo !empty($weight) ? '<br/>' . implode('<br/>', array_filter($weight)) : '';
            } else {
                echo "--";
            } ?>
        </td>
        <td>
            <?php if (!$isVirtualChildProduct) {
                echo esc_html($child_product->get_meta('fc_is_individual') ? 'Yes' : 'No');
                echo !empty($individual) ? '<br/>' . implode('<br/>', array_filter($individual)) : '';
            } else {
                echo "--";
            } ?>
        </td>
        <td class="no-click-effect text-center">
            <?php if (isset($variableOrGroupedProduct)) {
                $fc_allow_shipping = 'checked';
                if ($child_product->get_meta('fc_allow_shipping') == "0") {
                    $fc_allow_shipping = '';
                }
                $disable = $tooltipClass = $tooltipTitle = "";
                if ($isVirtualChildProduct) {
                    $disable = "disabled";
                    $tooltipClass = "virtual-product";
                    $tooltipTitle = "Virtual product not required shipping";
                    $fc_allow_shipping = '';
                } ?>
                <label class="switch no-click-effect">
                    <input type="checkbox" class="no-click-effect" onchange="updateAllowShipping(this)" data-value="<?php echo $fc_allow_shipping ?>" <?php echo $disable ?> data-product="pId=<?php echo esc_html($child_product->get_id()) ?>" <?php echo $fc_allow_shipping ?>>
                    <span class="slider round no-click-effect <?php echo $tooltipClass ?>" title="<?php echo $tooltipTitle ?>"></span>
                </label>
            <?php } ?>
        </td>
        <td></td>
        <td>
            <?php
            if (!$isVirtualChildProduct) {
                if ($child_product->get_meta('fc_location_type') == 'name') {
                    if (is_array($locationResult) && count($locationResult) > 0) {
                        foreach ($locationResult as $location_tag) {
                            if ($location_tag['id'] == $child_product->get_meta('fc_location')) {
                                echo $location_tag['location_name'] ?? '';
                            }
                        }
                    }
                } else {
                    if (is_array($available_tags) && count($available_tags) > 0) {
                        foreach ($available_tags as $tag) {
                            if ($tag['id'] == $child_product->get_meta('fc_location')) {
                                echo $tag['name'];
                            }
                        }
                    }
                }
            } else {
                echo "--";
            }
            ?>
        </td>
    </tr>
<?php }
    } else {

    $custom_fields = get_post_meta($productId);
    $weight = $dimensions = $individual = $otherPackageTypes = [];
    foreach ($custom_fields as $key => $value) {
        // Check if the key ends with a number (e.g., "length1", "width2")
        if (preg_match('/^(\w+)(\d+)$/', $key, $matches)) {
            // Get the base key (e.g., "length", "width")
            $base_key = $matches[1];
            // Check if the base key is one of "length," "width," "height," or "weight"
            if (in_array($base_key, array('fc_length_', 'fc_width_', 'fc_height_', 'fc_weight_', 'fc_is_individual_', 'fc_package_type_'))) {
                $index = $matches[2];
                if ($base_key == 'fc_is_individual_') {
                    $individual[$index] = ($value[0]) ? 'Yes' : 'No';
                } elseif ($base_key == 'fc_package_type_') {
                    $otherPackageTypes[$index] = $value[0];
                } elseif ($base_key == 'fc_weight_') {
                    $weight[$index] = $value[0];
                } else {
                    $dimensions[$index][$base_key] = $value[0];
                }
            }
        }
    }
?>
    <tr <?php if ($variableOrGroupedProduct) { ?> class="product-parent-row" data-parent-id="<?= $productId ?>" <?php } ?>>
        <?php
        if (!$isVirtualProduct) {
            $productType = "";
            if ($variableOrGroupedProduct) {
                $productType = "grouped";
                if ($variableProduct) {
                    $productType = "variable";
                }
            } else {
                $productType = "simple";
            } ?>
            <td><input type="checkbox" class="product-checkbox no-click-effect" value="pId=<?php echo esc_html($productId) . "&productType=" . $productType ?>" name="products[]"></td>
        <?php } else {
            echo "<td></td>";
        } ?>
        <td><a href="<?php echo esc_url(get_edit_post_link($productId)) ?>"><?php echo esc_html($product->get_name()) ?></td>
        <?php
            $tooltipClass = '';
            $sku = esc_html($product->get_sku());
            if(strlen($product->get_sku()) > 15) {
                $tooltipClass = "truncate-text hover-cell";
            } ?>
        <td class="<?php echo $tooltipClass ?>"> <?php echo $sku; ?> </td>
        <td><?php if (!$variableOrGroupedProduct) { ?> $<?php echo esc_html($product->get_price()) ?></td> <?php } ?>
    <td><?php echo wc_get_product_category_list($productId) ?></td>
    <td>
        <?php 
            if($variableProduct) {
                echo $productTypeFilters['variable'];
            } elseif ($groupedProduct) {
                echo $productTypeFilters['grouped'];
            } elseif ($isVirtualProduct) {
                echo $productTypeFilters['virtual'];
            } else {
                echo $productTypeFilters['simple'];
            }
        ?>
    </td>
    <td class="text-center">
        <?php if (!$isVirtualProduct && !$variableOrGroupedProduct) {
            echo esc_html($product->get_meta('fc_length')) ?> x <?php echo esc_html($product->get_meta('fc_width')) ?> x <?php echo esc_html($product->get_meta('fc_height')) ?>
        <?php
            foreach ($dimensions as $item) {
                $string = '';
                // Check and concatenate "fc_length_"
                if (isset($item['fc_length_']) && $item['fc_length_'] !== '') {
                    $string .= $item['fc_length_'] . ' X ';
                }
                // Check and concatenate "fc_width_"
                if (isset($item['fc_width_']) && $item['fc_width_'] !== '') {
                    $string .= $item['fc_width_'] . ' X ';
                }
                // Check and concatenate "fc_height_"
                if (isset($item['fc_height_']) && $item['fc_height_'] !== '') {
                    $string .= $item['fc_height_'];
                }
                // Remove the trailing " X " if present
                $string = rtrim($string, ' X ');
                // Add the string to the result array
                if (isset($string)) {
                    echo '<br/>' . $string;
                }
            }
        } else {
            echo "--";
        } ?>
    </td>
    <td><?php if (!$isVirtualProduct && !$variableOrGroupedProduct) {
            echo esc_html($product->get_meta('fc_weight'));
            echo !empty($weight) ? '<br/>' . implode('<br/>', array_filter($weight)) : '';
        } else {
            echo "--";
        } ?>
    </td>
    <td>
        <?php if (!$isVirtualProduct && !$variableOrGroupedProduct) {
            echo esc_html($product->get_meta('fc_is_individual') ? 'Yes' : 'No');
            echo !empty($individual) ? '<br/>' . implode('<br/>', array_filter($individual)) : '';
        } else {
            echo "--";
        } ?>
    </td>
    <td class="no-click-effect text-center">
        <?php $disable = "";
        if (!$variableOrGroupedProduct) {
            $fc_allow_shipping = 'checked';
            if ($product->get_meta('fc_allow_shipping') == "0") {
                $fc_allow_shipping = '';
            }
            $tooltipClass = $tooltipTitle = $tooltipFreeShippingTitle = "";
            if ($isVirtualProduct) {
                $disable = "disabled";
                $tooltipClass = "virtual-product";
                $tooltipTitle = "Virtual product not required shipping";
                $tooltipFreeShippingTitle = "Virtual product not eligible for shipping";
                $fc_allow_shipping = '';
            } ?>
            <label class="switch no-click-effect">
                <input type="checkbox" class="no-click-effect" onchange="updateAllowShipping(this)" data-value="<?php echo $fc_allow_shipping ?>" <?php echo $disable ?> data-product="pId=<?php echo esc_html($productId) ?>" <?php echo $fc_allow_shipping ?>>
                <span class="slider round no-click-effect <?php echo $tooltipClass ?>" title="<?php echo $tooltipTitle ?>"></span>
            </label>
        <?php } ?>
    </td>
    <?php $fc_allow_free_shipping = '';
        if ($product->get_meta('fc_allow_free_shipping') == "1") {
            $fc_allow_free_shipping = 'checked';
        }
        if ($disable != '') {
            $fc_allow_free_shipping = '';
        }
        ?>
        <td class="no-click-effect text-center">
            <label class="switch no-click-effect">
                <input type="checkbox" class="no-click-effect" onchange="updateAllowFreeShipping(this)" data-value="<?php echo $fc_allow_free_shipping ?>" <?php echo $disable ?> data-product="pId=<?php echo esc_html($productId) ?>" <?php echo $fc_allow_free_shipping ?>>
                <span class="slider round no-click-effect <?php echo $tooltipClass ?>" title="<?php echo $tooltipFreeShippingTitle ?>"></span>
            </label>
        </td>

        <?php if (!$isVirtualProduct && !$variableOrGroupedProduct) { ?>
        <td>
            <?php if ($product->get_meta('fc_location_type') == 'name') {
                if (is_array($locationResult) && count($locationResult) > 0) {
                    foreach ($locationResult as $location_tag) {
                        if ($location_tag['id'] == $product->get_meta('fc_location')) {
                            echo $location_tag['location_name'] ?? '';
                        }
                    }
                }
            } else {
                if (is_array($available_tags) && count($available_tags) > 0) {
                    foreach ($available_tags as $tag) {
                        if ($tag['id'] == $product->get_meta('fc_location')) {
                            echo $tag['name'];
                        }
                    }
                }
            } ?>
        </td>
        <?php } else {
            if ($variableOrGroupedProduct) { ?>
                <td class="text-center fc-toggle-icon"><i class="fas fa-chevron-down"></i></td>
            <?php } else {
                echo "<td>--</td>";
            }
        } ?>
    </tr>
<?php } ?>