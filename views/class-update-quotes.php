<?php

namespace FastCourier;

use DVDoug\BoxPacker\Packer;
use DVDoug\BoxPacker\NoBoxesAvailableException;
use DVDoug\BoxPacker\Test\TestBox;  // use your own `Box` implementation
use DVDoug\BoxPacker\Test\TestItem; // use your own `Item` implementation

class FastCourierUpdateQuotes
{
    private static function logPackingDebug($message, $context = [])
    {
        $encodedContext = wp_json_encode(is_array($context) ? $context : ['context' => $context]);

        if (function_exists('wc_get_logger')) {
            wc_get_logger()->debug($message . ' ' . $encodedContext, ['source' => 'fast-courier-packing']);
            return;
        }

        if (function_exists('error_log')) {
            error_log('[fast-courier-packing] ' . $message . ' ' . $encodedContext);
        }
    }

    public static function checkingQuotes($package = [])
    {
        global $wpdb, $fc_packages_table;

        // getting merchant details from WP Options
        $merchantDetails = fc_merchant_details();
        if (!$merchantDetails) return;
        if (!fc_is_configured()) {
            return;
        }

        $session = WC()->session;
        $fcClassicMode = isClassicMode();
        //getting cart and its item list
        $cartItems = [];
        if (!$fcClassicMode) {
            if (WC()->cart && is_array(WC()->cart->cart_contents)) {
                $cartItems = WC()->cart->cart_contents;
            }
        } else {
            $cart = WC()->cart;
            $cartItems = $cart->cart_contents;
        }

        // Customer data
        if (!$fcClassicMode) {
            $customerData = $package['destination'];
        } else {
            $customerData = $_POST;
        }

        $customerLat = $customerLon = 0;
        // Get latitiude/longitude
        if ((($city = $customerData['city']) && ($postcode = $customerData['postcode']) && ($state = $customerData['state'])) || (($city = $customerData['s_city']) && ($postcode = $customerData['s_postcode']) && ($state = $customerData['s_state']))) {
            $address = "{$city},{$postcode},{$state}";
            $result = getLatLongFromAddress($address);
            // Customer's latitude and longitude
            $customerLat = $result['latitude'] ?? 0; // customer's latitude
            $customerLon = $result['longitude'] ?? 0; // customer's longitude
        }

        /*
         * $seprated_items will contains an array of products based on there locations
         * array key will be the primary key (id) of location and value will be WC_Product.
         */
        $seprated_items = [];
        $cartLineCountsByProduct = [];

        foreach ($cartItems as $cartItemCountProbe) {
            $countProductId = (isset($cartItemCountProbe['variation_id']) && $cartItemCountProbe['variation_id'] > 0)
                ? (int) $cartItemCountProbe['variation_id']
                : (int) $cartItemCountProbe['product_id'];
            if (!isset($cartLineCountsByProduct[$countProductId])) {
                $cartLineCountsByProduct[$countProductId] = 0;
            }
            $cartLineCountsByProduct[$countProductId]++;
        }

        foreach ($cartItems as $cartItemKey => $item) {

            $cartItemKey = isset($item['key']) && $item['key'] ? (string) $item['key'] : (string) $cartItemKey;

            if (isset($item['variation_id']) && $item['variation_id'] > 0) {
                $product_id = $item['variation_id'];
                $wc_product = (!empty($item['data']) && is_object($item['data']) && (int) $item['data']->get_id() === (int) $product_id)
                    ? $item['data']
                    : wc_get_product($product_id);
            } else {
                $product_id = $item['product_id'];
                $wc_product = (!empty($item['data']) && is_object($item['data']))
                    ? $item['data']
                    : new \WC_Product($product_id);
            }

            // check if product is virtual in WP
            $isVirtualProduct = $wc_product->is_virtual();
            // Build meta_dimensions from the in-memory product object so any
            // cart-time overrides (e.g. from wmsd) are reflected instead of raw DB values.
            $meta_dimensions = [];
            foreach ($wc_product->get_meta_data() as $_meta) {
                $_d = $_meta->get_data();
                if (isset($_d['key']) && '' !== $_d['key']) {
                    $meta_dimensions[$_d['key']][] = $_d['value'];
                }
            }

            // Apply mapped overrides persisted by the WMSD plugin for this request.
            $wmsd_session_overrides = [];
            if (function_exists('WC') && WC()->session) {
                $wmsd_session_overrides = WC()->session->get('wmsd_meta_overrides', []);
            }
            $overridesToApply = [];
            if (!empty($wmsd_session_overrides['cart_items'][$cartItemKey]) && is_array($wmsd_session_overrides['cart_items'][$cartItemKey])) {
                $overridesToApply = $wmsd_session_overrides['cart_items'][$cartItemKey];
            } elseif (!empty($wmsd_session_overrides[$product_id]) && is_array($wmsd_session_overrides[$product_id])) {
                $productLineCount = isset($cartLineCountsByProduct[$product_id]) ? (int) $cartLineCountsByProduct[$product_id] : 1;
                if ($productLineCount <= 1) {
                    // Backward-compatible fallback for older override sessions.
                    $overridesToApply = $wmsd_session_overrides[$product_id];
                } else {
                    Self::logPackingDebug('Skipping product-level WMSD override fallback because multiple cart lines share same product id', [
                        'product_id' => $product_id,
                        'cart_item_key' => $cartItemKey,
                        'product_line_count' => $productLineCount,
                    ]);
                }
            }

            if (empty($overridesToApply)) {
                $derivedOverrides = Self::deriveOverridesFromCalculatorPayload($item, $wc_product, $product_id);
                if (!empty($derivedOverrides)) {
                    $overridesToApply = $derivedOverrides;
                    Self::logPackingDebug('Derived cart-item overrides from calculator payload', [
                        'product_id' => $product_id,
                        'cart_item_key' => $cartItemKey,
                        'derived_overrides' => $derivedOverrides,
                    ]);
                }
            }

            if (!empty($overridesToApply)) {
                foreach ($overridesToApply as $override_key => $override_value) {
                    $meta_dimensions[$override_key] = [strval($override_value)];
                    $wc_product->update_meta_data($override_key, $override_value);
                }
                if (method_exists($wc_product, 'get_changes') && method_exists($wc_product, 'apply_changes') && !empty($wc_product->get_changes())) {
                    $wc_product->apply_changes();
                }
            }
            $eligibleForShipping = "1";
            if ($isVirtualProduct) {
                $allow_shipping = "0";
            } else {
                // fc_allow_shipping will be 0, if shipping not eligible for product
                $allow_shipping = $eligibleForShipping = isset($meta_dimensions['fc_allow_shipping']) ? $meta_dimensions['fc_allow_shipping'][0] : "1";
            }

            $eligibleForFreeShipping = "0";
            if ($allow_shipping == "1") { // if product is eligible for shipping
                // check if merchant allow free shipping for a product (e.g. "fc_allow_free_shipping" will be "1" for free shipping)
                $eligibleForFreeShipping = isset($meta_dimensions['fc_allow_free_shipping']) ? $meta_dimensions['fc_allow_free_shipping'][0] : "0";
            }

            $dimensionsData = [];
            foreach ($meta_dimensions as $key => $value) {
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

            //getting location/tag id from product meta
            $fc_location_id = $wc_product->get_meta('fc_location');

            $location = null;
            //comparing type of location assigned to product by name or tags
            if ($wc_product->get_meta('fc_location_type') == 'tags') {
                // getting location based on tags
                $getwarehouses = FastCourierRequests::httpGet('merchant_locations/' . $merchantDetails['id'] . '/' . $fc_location_id);
                $warehouses = $getwarehouses['data']['data'] ?? [];

                $nearestDistance = PHP_INT_MAX;
                // get closest warehouse location
                foreach ($warehouses as $warehouse) {
                    $distance = calculateDistance($customerLat, $customerLon, $warehouse['latitude'], $warehouse['longitude']);
                    if ($distance < $nearestDistance) {
                        $nearestDistance = $distance;
                        $location = $warehouse;
                    }
                }
                //set default location if location by tag not found or deleted
                if (!$location) {
                    $location = Self::defaultLocation($merchantDetails['id']);
                }
            } else if ($wc_product->get_meta('fc_location_type') == 'name') {
                // Get the location data from the API
                $result = FastCourierRequests::httpGet('merchant_domain/location/' . $fc_location_id);
                $location = $result['data']['data'] ?? null;

                //set default location if location by name not found or deleted
                if (!$location) {
                    $location = Self::defaultLocation($merchantDetails['id']);
                }
            } else {
                //default location for all products
                $location = Self::defaultLocation($merchantDetails['id']);
            }

            $seprated_items['' . $eligibleForFreeShipping]['' . $location['id']]['products'][$cartItemKey] = $wc_product;
            $seprated_items['' . $eligibleForFreeShipping]['' . $location['id']]['location'] = $location;
            $seprated_items['' . $eligibleForFreeShipping]['' . $location['id']][$cartItemKey]['allow_shipping'] = $allow_shipping;
            $seprated_items['' . $eligibleForFreeShipping]['' . $location['id']][$cartItemKey]['eligibleForShipping'] = $eligibleForShipping;
            $seprated_items['' . $eligibleForFreeShipping]['' . $location['id']]['dimensions'][$cartItemKey] = $dimensionsData;
            $seprated_items['' . $eligibleForFreeShipping]['' . $location['id']][$cartItemKey]['order_qty'] = $item['quantity'];
        }

        // getting list of all available package items
        $packer = new Packer();
        $availablePacakges = $wpdb->get_results("SELECT * FROM {$fc_packages_table} WHERE 1", ARRAY_A);

        $body = [];
        $allPackages = [];
        $isPhysicalProduct = $addShippingQuotesOncheckout = false;
        $session->__unset('is_fallback_shipping');
        $session->__unset('is_allow_shipping');
        $session->__unset('is_local_pickup');
        $session->__unset('fallback_shipping');
        $session->__unset('atl');
        $session->__unset('addShippingQuotesOncheckout');

        foreach ($seprated_items as $eligibleForFreeShippingKey => $groupedItems) {
            // getting quotes for all same location products
            foreach ($groupedItems as $items) {

                $location = $items['location'];
                $products = $items['products'];

                // getting list of all available package sizes
                foreach ($availablePacakges as $key => $package) {
                    $availablePacakges[$key]['height'] = $package['outside_h'];
                    $availablePacakges[$key]['width'] = $package['outside_w'];
                    $availablePacakges[$key]['length'] = $package['outside_l'];

                    $packer->addBox(new TestBox(
                        wp_json_encode($availablePacakges[$key]),
                        $package['outside_w'],
                        $package['outside_l'],
                        $package['outside_h'],
                        0,
                        $package['outside_w'],
                        $package['outside_l'],
                        $package['outside_h'],
                        50000000
                    ));
                }

                $individualPacks = $seprated_products = [];
                $isAllowShipping = $eligibleForShippingFlag = false;
                $totalProductsWeight = 0;
                // creating order packages
                foreach ($products as $cartItemKey => $product) {
                    // get product id if product type is variation
                    if (is_a($product, 'WC_Product_Variation')) {
                        $reflection = new \ReflectionClass($product);
                        $property = $reflection->getProperty('id');
                        $property->setAccessible(true); // This makes the protected property accessible
                        // set product id
                        $productId = $property->getValue($product);
                        $property->setAccessible(false);
                    } else {
                        // set product id
                        $productId = $product->get_id();
                    }
                    $allow_shipping = $items[$cartItemKey]['allow_shipping'];
                    $eligibleForShipping = $items[$cartItemKey]['eligibleForShipping'];

                    if ($eligibleForShipping == '1') {
                        $eligibleForShippingFlag = $addShippingQuotesOncheckout = true;
                    }

                    // loop for multi shipped products
                    foreach ($items['dimensions'][$cartItemKey] as $k => $value) {

                        $ordered_qty = $items[$cartItemKey]['order_qty'];
                        if ($allow_shipping == "1") {
                            $isAllowShipping = true;
                            $isPhysicalProduct = true;
                            if ($k == 0) {
                                $height = isset($value['fc_height']) ? (int) $value['fc_height'] : (int) $product->get_meta('fc_height');
                                $width = isset($value['fc_width']) ? (int) $value['fc_width'] : (int) $product->get_meta('fc_width');
                                $length = isset($value['fc_length']) ? (int) $value['fc_length'] : (int) $product->get_meta('fc_length');
                                $weight = isset($value['fc_weight']) ? round((float) $value['fc_weight'], 2) : ($product->get_meta('fc_weight') ? round((float) $product->get_meta('fc_weight'), 2) : 0);
                                $is_individual = isset($value['fc_is_individual']) ? $value['fc_is_individual'] : $product->get_meta('fc_is_individual');
                                $pack_type = isset($value['fc_package_type']) ? $value['fc_package_type'] : $product->get_meta('fc_package_type');
                            } else {
                                $height_key = 'fc_height_' . $k;
                                $width_key = 'fc_width_' . $k;
                                $length_key = 'fc_length_' . $k;
                                $weight_key = 'fc_weight_' . $k;
                                $individual_key = 'fc_is_individual_' . $k;
                                $pack_type_key = 'fc_package_type_' . $k;

                                $height = isset($value[$height_key]) ? (int) $value[$height_key] : (int) $product->get_meta($height_key);
                                $width = isset($value[$width_key]) ? (int) $value[$width_key] : (int) $product->get_meta($width_key);
                                $length = isset($value[$length_key]) ? (int) $value[$length_key] : (int) $product->get_meta($length_key);
                                $weight = isset($value[$weight_key]) ? round((float) $value[$weight_key], 2) : ($product->get_meta($weight_key) ? round((float) $product->get_meta($weight_key), 2) : 0);
                                $is_individual = isset($value[$individual_key]) ? $value[$individual_key] : $product->get_meta($individual_key);
                                $pack_type = isset($value[$pack_type_key]) ? $value[$pack_type_key] : $product->get_meta($pack_type_key);
                            }

                            // ensure height, width and length are not zero and weight is less than 0.01
                            $height = ($height <= 0) ? 1 : $height;
                            $width = ($width <= 0) ? 1 : $width;
                            $length = ($length <= 0) ? 1 : $length;
                            $weight = ($weight <= 0.1) ? 0.1 : $weight;

                            $pack = ['name' => $product->get_name(), 'height' => $height, 'width' => $width, 'length' => $length, 'weight' => $weight, 'type' => $pack_type, 'quantity' => $ordered_qty];
                            $shipsOnPallet = (int) $product->get_meta('fc_ships_on_pallet') === 1;
                            $isPalletTypePack = strtolower((string) $pack_type) === 'pallet';

                            if ($is_individual) {
                                array_push($individualPacks, $pack);
                            } else {
                                Self::logPackingDebug('Preparing pack for boxpacker', [
                                    'product_id' => $productId,
                                    'pack' => $pack,
                                    'ships_on_pallet' => $shipsOnPallet,
                                    'is_pallet_type_pack' => $isPalletTypePack,
                                ]);
                                if ($shipsOnPallet || $isPalletTypePack) {
                                    $stackedPalletPacks = Self::buildStackedPalletPackages($pack, $availablePacakges, $ordered_qty);
                                    if (!empty($stackedPalletPacks)) {
                                        $individualPacks = array_merge($individualPacks, $stackedPalletPacks);
                                        Self::logPackingDebug('Using stacked pallet packs (height-first) and bypassing boxpacker for this item', [
                                            'product_id' => $productId,
                                            'stacked_pallet_packs' => $stackedPalletPacks,
                                        ]);
                                        continue;
                                    }
                                }
                                $packsToAdd = ($shipsOnPallet || $isPalletTypePack) ? Self::splitPackForPallets($pack, $availablePacakges) : [$pack];
                                Self::logPackingDebug('Pack list prepared for boxpacker', [
                                    'product_id' => $productId,
                                    'packs_to_add' => $packsToAdd,
                                ]);
                                foreach ($packsToAdd as $packToAdd) {
                                    $packer->addItem(new TestItem(wp_json_encode($packToAdd), $packToAdd['width'], $packToAdd['length'], $packToAdd['height'], $packToAdd['weight'], true), $ordered_qty);
                                }
                            }

                            $totalProductsWeight += $weight * $ordered_qty;
                        }

                        $temp_product = [
                            'name' => $product->get_name(),
                            'sku' => $product->get_sku(),
                            'cost' => $product->get_price(),
                            'quantity' => $ordered_qty,
                            'tax' => $product->get_tax_class() ?? 0,
                            // Keep item summary aligned with the dimensions used for quote packing.
                            'weight' => $weight,
                            'length' => $length,
                            'width' => $width,
                            'height' => $height,
                            'shipping' => !in_array($product->get_type(), array('virtual', 'downloadable')),
                            'total' => $product->get_price() * $ordered_qty,
                        ];
                        $seprated_products[] = $temp_product;
                    }
                }

                if ($isAllowShipping && $eligibleForShippingFlag) {
                    try {
                        $packedBoxes = $packer->pack();
                        foreach ($packedBoxes as $packedBox) {
                            $boxType = $packedBox->getBox();

                            $box = json_decode($boxType->getReference(), true);
                            $box['qty'] = 1;

                            $packedItems = $packedBox->getItems();

                            foreach ($packedItems as $packedItem) {
                                $box['sub_packs'][] = json_decode($packedItem->getItem()->getDescription(), true);
                            }
                            array_push($individualPacks, $box);
                        }
                    } catch (NoBoxesAvailableException $e) {
                        Self::logPackingDebug('Boxpacker could not pack one or more items', [
                            'error' => $e->getMessage(),
                        ]);
                        // Avoid checkout fatals: pass the failed item through to quote/fallback handling.
                        if (method_exists($e, 'getItem') && $e->getItem()) {
                            $failedPack = json_decode($e->getItem()->getDescription(), true);
                            if (is_array($failedPack) && !empty($failedPack)) {
                                $failedPack['quantity'] = isset($failedPack['quantity']) ? $failedPack['quantity'] : 1;
                                $individualPacks[] = $failedPack;
                                Self::logPackingDebug('Failed pack moved to fallback quote path', [
                                    'failed_pack' => $failedPack,
                                ]);
                            }
                        }
                    }
                }

                $individualPacks = Self::combinePalletPacksByHeight($individualPacks);

                $formData = Self::qouteDataFormatter($customerData, $location, $individualPacks);
                if (!$formData) return false;

                $formData['isDropOffTailLift'] = 0;
                if (isset($merchantDetails['isDropOffTailLift']) && (isset($merchantDetails['tailLiftValue']) && $totalProductsWeight >= (float) $merchantDetails['tailLiftValue'])) {
                    $formData['isDropOffTailLift'] = 1;
                }

                if (isset($formData['fc_local_pickup'])) {
                    $session->set('is_local_pickup', true);
                    $isAllowShipping = false;
                }

                if (@$merchantDetails['isAuthorityToLeave'] == '1') {
                    $formData['atl'] = true;
                }

                if ($isAllowShipping && $eligibleForShippingFlag) {
                    if ($location['is_flat_enable'] == 1 && isPostCodeIncludedInFlatRate($formData['destinationPostcode'], $location['flat_shipping_postcodes'])) {
                        $formData['subOrderType'] = 'flat_rate';
                        $formData['flatPrice'] = $location['flat_rate'];

                        $response = FastCourierRequests::httpPost('create-flate-order', $formData);

                        if ($response['status'] == 200) {
                            $response['data']['data']['priceIncludingGst'] = $location['flat_rate'];
                            $response['data']['order_type'] = ORDER_TYPE_FLATRATE;
                        }
                    } else {

                        $response = FastCourierRequests::httpGet('quote', $formData);
                    }

                    if ($response['status'] == 400) {
                        $formData['subOrderType'] = ORDER_TYPE_FALLBACK;
                        $formData['fallbackPrice'] = $merchantDetails['fallbackAmount'];

                        $response = FastCourierRequests::httpPost('create-fallback-order', $formData);

                        $session->set('is_fallback_shipping', true);
                        $session->set('fallback_shipping', $merchantDetails['fallbackAmount']);
                        $session->__unset('quote');

                        if ($response['status'] == 200) {
                            $response['data']['order_type'] = ORDER_TYPE_FALLBACK;
                        }
                    } else {
                        $session->__unset('is_fallback_shipping');
                        $session->__unset('fallback_shipping');
                    }
                    // checking if customer postcode available in location free shipping postcodes
                    $shippingStatus = SHIPPING_TYPE_PAID; // set shipping type default as paid
                    if ((isset($location['free_shipping_postcodes']) && !empty($location['free_shipping_postcodes'])) && (isset($customerData['postcode']))) {
                        $freeShippingcodes = explode(',', $location['free_shipping_postcodes']);
                        if (in_array($customerData['postcode'], $freeShippingcodes)) {
                            $shippingStatus = SHIPPING_TYPE_FREE; // set shipping type as free
                        }
                    }
                } else {
                    $response['data'] = [];
                    $shippingStatus = SHIPPING_TYPE_FREE; // set shipping type as free
                }

                if ($eligibleForFreeShippingKey == '1') {
                    $shippingStatus = SHIPPING_TYPE_FREE; // set shipping type as free
                }

                if (isset($formData['atl'])) {
                    $response['data']['atl'] = $formData['atl'];
                    $session->set('atl', $formData['atl']);
                }
                $destinationData = [];

                if (!$fcClassicMode) {
                    $destinationData = $customerData;
                } else {
                    if ($customerData && $customerData['post_data']) parse_str($customerData['post_data'], $destinationData);
                }
                $response['data']['location'] = $location;
                $response['data']['items'] = $seprated_products;
                $response['data']['packages'] = $individualPacks;
                $response['data']['shipping_type'] = $shippingStatus;
                $response['data']['destination'] = $destinationData;
                $body[] = $response['data'];
                $allPackages[] = $individualPacks;
            }
        }

        if (!$addShippingQuotesOncheckout) {
            $session->set('addShippingQuotesOncheckout', $addShippingQuotesOncheckout);
        }

        if ($isPhysicalProduct) {
            $session->set('is_allow_shipping', true);
        }

        $session->set('quote', wp_json_encode($body));

        if ($isAllowShipping) {
            $session->set('packages_for_quote', wp_json_encode($allPackages));
        }
    }

    /**
     * Returns the default location for a merchant's domain.
     *
     * @return array The default location data.
     */
    static function defaultLocation($domainId)
    {
        // Make a GET request to retrieve the default location data
        $getDefaultLocation = FastCourierRequests::httpGet('merchant_domain/default_location/' . $domainId);

        // Check if the request was successful
        if (!empty($getDefaultLocation['status'] == 200)) {
            // Return the default location data
            return $getDefaultLocation['data']['data'];
        }

        // Return an empty array if the request failed
        return [];
    }

    static function keyFilter(...$params)
    {
        foreach ($params as $param) {
            if ($param || $param != '') {
                return $param;
            }
        }
    }

    static function formatPackages($packages)
    {
        $packs = [];
        foreach ($packages as $key => $pack) {
            $quantity = isset($pack['quantity']) ? (int) $pack['quantity'] : 1;
            $quantity = ($quantity > 0) ? $quantity : 1;
            $unitWeight = isset($pack['weight']) ? (float) $pack['weight'] : 0;

            $packs[$key] = [
                'name' => $pack['package_name'] ?? $pack['name'],
                'type' => $pack['package_type'] ?? $pack['type'],
                'height' => $pack['height'],
                'width' => $pack['width'],
                'length' => $pack['length'],
                'weight' => round($unitWeight * $quantity, 2),
                'quantity' => $quantity
            ];

            if (isset($pack['sub_packs']) && count($pack['sub_packs'])) {
                $packs[$key]['sub_packs'] = $pack['sub_packs'];
                $packs[$key]['weight'] = round(array_sum(array_map('floatval', array_column($pack['sub_packs'], 'weight'))), 2);
            }
        }

        return $packs;
    }

    static function totalShipmentWeight($items)
    {
        return round(array_sum(array_map(function ($item) {
            return isset($item['weight']) ? (float) $item['weight'] : 0;
        }, $items)), 2);
    }

    static function canPackFitInPackage($pack, $package)
    {
        $itemDimensions = [
            (int) ($pack['length'] ?? 0),
            (int) ($pack['width'] ?? 0),
            (int) ($pack['height'] ?? 0)
        ];
        $packageDimensions = [
            (int) ($package['outside_l'] ?? 0),
            (int) ($package['outside_w'] ?? 0),
            (int) ($package['outside_h'] ?? 0)
        ];

        $rotations = [
            [$itemDimensions[0], $itemDimensions[1], $itemDimensions[2]],
            [$itemDimensions[0], $itemDimensions[2], $itemDimensions[1]],
            [$itemDimensions[1], $itemDimensions[0], $itemDimensions[2]],
            [$itemDimensions[1], $itemDimensions[2], $itemDimensions[0]],
            [$itemDimensions[2], $itemDimensions[0], $itemDimensions[1]],
            [$itemDimensions[2], $itemDimensions[1], $itemDimensions[0]],
        ];

        foreach ($rotations as $rotation) {
            if (
                $rotation[0] <= $packageDimensions[0]
                && $rotation[1] <= $packageDimensions[1]
                && $rotation[2] <= $packageDimensions[2]
            ) {
                return true;
            }
        }

        return false;
    }

    static function canPackFitInAnyPackage($pack, $availablePackages)
    {
        foreach ($availablePackages as $availablePackage) {
            if (Self::canPackFitInPackage($pack, $availablePackage)) {
                return true;
            }
        }

        return false;
    }

    static function splitPackForPallets($pack, $availablePackages)
    {
        if (Self::canPackFitInAnyPackage($pack, $availablePackages)) {
            Self::logPackingDebug('No split needed; pack already fits configured package', [
                'pack' => $pack,
            ]);
            return [$pack];
        }

        $palletCandidates = array_values(array_filter($availablePackages, function ($availablePackage) {
            if (!isset($availablePackage['package_type']) || strtolower(trim($availablePackage['package_type'])) !== 'pallet') {
                return false;
            }

            return true;
        }));

        if (empty($palletCandidates)) {
            Self::logPackingDebug('No pallet candidates available for split', [
                'pack' => $pack,
            ]);
            return [$pack];
        }

        $itemLength = max((int) $pack['length'], (int) $pack['width']);
        $itemWidth = min((int) $pack['length'], (int) $pack['width']);

        usort($palletCandidates, function ($a, $b) {
            $aLength = max((int) ($a['outside_l'] ?? 0), (int) ($a['outside_w'] ?? 0));
            $bLength = max((int) ($b['outside_l'] ?? 0), (int) ($b['outside_w'] ?? 0));
            return $bLength <=> $aLength;
        });

        $selectedPallet = null;
        foreach ($palletCandidates as $palletCandidate) {
            $palletLength = max((int) $palletCandidate['outside_l'], (int) $palletCandidate['outside_w']);
            $palletWidth = min((int) $palletCandidate['outside_l'], (int) $palletCandidate['outside_w']);

            if ($itemWidth <= $palletWidth && (int) $pack['height'] <= (int) $palletCandidate['outside_h']) {
                $selectedPallet = [
                    'length' => $palletLength,
                    'width' => $palletWidth,
                ];
                break;
            }
        }

        if (!$selectedPallet) {
            Self::logPackingDebug('No suitable pallet found for split dimensions', [
                'pack' => $pack,
                'item_length' => $itemLength,
                'item_width' => $itemWidth,
                'candidate_count' => count($palletCandidates),
            ]);
            return [$pack];
        }

        $sections = (int) ceil($itemLength / $selectedPallet['length']);
        if ($sections <= 1) {
            Self::logPackingDebug('Split produced single section; using original pack', [
                'pack' => $pack,
                'selected_pallet' => $selectedPallet,
            ]);
            return [$pack];
        }

        $sectionLength = (int) ceil($itemLength / $sections);
        $sectionWeight = round(((float) $pack['weight']) / $sections, 2);
        $sectionWeight = ($sectionWeight <= 0.1) ? 0.1 : $sectionWeight;
        $splitPacks = [];

        for ($i = 0; $i < $sections; $i++) {
            $splitPacks[] = [
                'name' => $pack['name'],
                'height' => (int) $pack['height'],
                'width' => $itemWidth,
                'length' => $sectionLength,
                'weight' => $sectionWeight,
                'type' => 'pallet',
                'quantity' => 1,
            ];
        }

        Self::logPackingDebug('Pack split for pallet transport', [
            'original_pack' => $pack,
            'selected_pallet' => $selectedPallet,
            'sections' => $sections,
            'split_packs' => $splitPacks,
        ]);

        return $splitPacks;
    }

    static function buildStackedPalletPackages($pack, $availablePackages, $orderedQty)
    {
        $orderedQty = (int) $orderedQty;
        $orderedQty = ($orderedQty > 0) ? $orderedQty : 1;

        $palletCandidates = array_values(array_filter($availablePackages, function ($availablePackage) {
            if (!isset($availablePackage['package_type']) || strtolower(trim($availablePackage['package_type'])) !== 'pallet') {
                return false;
            }

            return true;
        }));

        if (empty($palletCandidates)) {
            return [];
        }

        usort($palletCandidates, function ($a, $b) {
            $aLength = max((int) ($a['outside_l'] ?? 0), (int) ($a['outside_w'] ?? 0));
            $aWidth = min((int) ($a['outside_l'] ?? 0), (int) ($a['outside_w'] ?? 0));
            $bLength = max((int) ($b['outside_l'] ?? 0), (int) ($b['outside_w'] ?? 0));
            $bWidth = min((int) ($b['outside_l'] ?? 0), (int) ($b['outside_w'] ?? 0));

            $aArea = $aLength * $aWidth;
            $bArea = $bLength * $bWidth;

            if ($aArea !== $bArea) {
                return $aArea <=> $bArea;
            }

            return $aLength <=> $bLength;
        });

        $itemLength = max((int) $pack['length'], (int) $pack['width']);
        $itemWidth = min((int) $pack['length'], (int) $pack['width']);
        $itemHeight = (int) $pack['height'];
        $palletLimits = Self::getPalletLimits($palletCandidates);
        $maxPalletLength = $palletLimits['max_length'];
        $maxPalletWidth = $palletLimits['max_width'];

        // Do not split signs that are within the configured maximum pallet length.
        $sectionsPerItem = 1;
        if ($maxPalletLength > 0 && $itemLength > $maxPalletLength) {
            $sectionsPerItem = (int) ceil($itemLength / $maxPalletLength);
        }
        $sectionsPerItem = ($sectionsPerItem > 0) ? $sectionsPerItem : 1;

        $sectionLength = (int) ceil($itemLength / $sectionsPerItem);

        foreach ($palletCandidates as $palletCandidate) {
            $palletLength = max((int) $palletCandidate['outside_l'], (int) $palletCandidate['outside_w']);
            $palletWidth = min((int) $palletCandidate['outside_l'], (int) $palletCandidate['outside_w']);
            $palletHeight = (int) $palletCandidate['outside_h'];

            if ($itemWidth > $palletWidth || $itemHeight > $palletHeight) {
                continue;
            }

            if ($sectionLength > $palletLength) {
                continue;
            }

            $maxSectionsPerPallet = (int) floor($palletHeight / max($itemHeight, 1));
            if ($maxSectionsPerPallet < 1) {
                continue;
            }

            $totalSections = $sectionsPerItem * $orderedQty;
            $sectionWeight = round(((float) $pack['weight']) / $sectionsPerItem, 2);
            $sectionWeight = ($sectionWeight <= 0.1) ? 0.1 : $sectionWeight;

            $remainingSections = $totalSections;
            $stackedPallets = [];

            while ($remainingSections > 0) {
                $sectionsOnThisPallet = min($maxSectionsPerPallet, $remainingSections);
                $calculatedPalletHeight = max(1, min($palletHeight, $sectionsOnThisPallet * max($itemHeight, 1)));
                $subPacks = [];

                for ($i = 0; $i < $sectionsOnThisPallet; $i++) {
                    $subPacks[] = [
                        'name' => $pack['name'],
                        'height' => $itemHeight,
                        'width' => $itemWidth,
                        'length' => $sectionLength,
                        'weight' => $sectionWeight,
                        'type' => $pack['type'] ?? 'pallet',
                        'quantity' => 1,
                    ];
                }

                $stackedPallets[] = [
                    'name' => $palletCandidate['package_name'] ?? ($pack['name'] . ' Pallet'),
                    'type' => $palletCandidate['package_type'] ?? 'pallet',
                    'height' => $calculatedPalletHeight,
                    'max_height' => $palletHeight,
                    'width' => (int) $palletCandidate['outside_w'],
                    'length' => (int) $palletCandidate['outside_l'],
                    'weight' => round($sectionsOnThisPallet * $sectionWeight, 2),
                    'quantity' => 1,
                    'sub_packs' => $subPacks,
                ];

                $remainingSections -= $sectionsOnThisPallet;
            }

            Self::logPackingDebug('Selected pallet candidate using height-first stacking', [
                'original_pack' => $pack,
                'ordered_qty' => $orderedQty,
                'item_length' => $itemLength,
                'item_width' => $itemWidth,
                'item_height' => $itemHeight,
                'max_pallet_length' => $maxPalletLength,
                'max_pallet_width' => $maxPalletWidth,
                'sections_per_item' => $sectionsPerItem,
                'section_length' => $sectionLength,
                'max_sections_per_pallet' => $maxSectionsPerPallet,
                'selected_pallet' => $palletCandidate,
                'calculated_pallet_heights' => array_map(function ($stackedPallet) {
                    return $stackedPallet['height'];
                }, $stackedPallets),
                'generated_pallet_count' => count($stackedPallets),
            ]);

            return $stackedPallets;
        }

        Self::logPackingDebug('No pallet candidate could satisfy height-first stacking constraints', [
            'pack' => $pack,
            'ordered_qty' => $orderedQty,
        ]);

        return [];
    }

    private static function getPalletLimits($palletCandidates)
    {
        $maxLength = 0;
        $maxWidth = 0;

        foreach ($palletCandidates as $palletCandidate) {
            $length = max((int) ($palletCandidate['outside_l'] ?? 0), (int) ($palletCandidate['outside_w'] ?? 0));
            $width = min((int) ($palletCandidate['outside_l'] ?? 0), (int) ($palletCandidate['outside_w'] ?? 0));

            if ($length > $maxLength) {
                $maxLength = $length;
            }
            if ($width > $maxWidth) {
                $maxWidth = $width;
            }
        }

        return [
            'max_length' => $maxLength,
            'max_width' => $maxWidth,
        ];
    }

    static function combinePalletPacksByHeight($packs)
    {
        $nonPalletPacks = [];
        $palletGroups = [];

        foreach ($packs as $pack) {
            if (!isset($pack['type']) || strtolower((string) $pack['type']) !== 'pallet' || empty($pack['sub_packs']) || !is_array($pack['sub_packs'])) {
                $nonPalletPacks[] = $pack;
                continue;
            }

            $groupKey = strtolower((string) ($pack['type'] ?? 'pallet'));
            if (!isset($palletGroups[$groupKey])) {
                $palletGroups[$groupKey] = [
                    'packs' => [],
                    'sub_packs' => [],
                ];
            }

            $palletGroups[$groupKey]['packs'][] = $pack;
            $palletGroups[$groupKey]['sub_packs'] = array_merge($palletGroups[$groupKey]['sub_packs'], $pack['sub_packs']);
        }

        $combinedPallets = [];
        foreach ($palletGroups as $groupKey => $group) {
            if (empty($group['sub_packs']) || empty($group['packs'])) {
                continue;
            }

            // Promote to the largest footprint pallet used in this group,
            // then stack everything to maximize single-pallet utilization by height.
            $prototype = null;
            $largestArea = -1;
            foreach ($group['packs'] as $candidatePack) {
                $candidateArea = ((int) ($candidatePack['length'] ?? 0)) * ((int) ($candidatePack['width'] ?? 0));
                if ($candidateArea > $largestArea) {
                    $largestArea = $candidateArea;
                    $prototype = $candidatePack;
                }
            }
            if (!$prototype) {
                continue;
            }

            $maxHeight = (int) ($prototype['max_height'] ?? $prototype['height'] ?? 0);
            if ($maxHeight <= 0) {
                $maxHeight = 1;
            }

            $currentPallet = null;
            $currentHeight = 0;
            $currentWeight = 0.0;

            foreach ($group['sub_packs'] as $subPack) {
                $subHeight = max(1, (int) ($subPack['height'] ?? 1));
                $subWeight = (float) ($subPack['weight'] ?? 0);

                if ($currentPallet === null || ($currentHeight + $subHeight) > $maxHeight) {
                    if ($currentPallet !== null) {
                        $currentPallet['height'] = $currentHeight;
                        $currentPallet['weight'] = round($currentWeight, 2);
                        $combinedPallets[] = $currentPallet;
                    }

                    $currentPallet = [
                        'name' => $prototype['name'],
                        'type' => $prototype['type'],
                        'length' => $prototype['length'],
                        'width' => $prototype['width'],
                        'height' => 0,
                        'max_height' => $maxHeight,
                        'weight' => 0,
                        'quantity' => 1,
                        'sub_packs' => [],
                    ];
                    $currentHeight = 0;
                    $currentWeight = 0.0;
                }

                $currentPallet['sub_packs'][] = $subPack;
                $currentHeight += $subHeight;
                $currentWeight += $subWeight;
            }

            if ($currentPallet !== null) {
                $currentPallet['height'] = $currentHeight;
                $currentPallet['weight'] = round($currentWeight, 2);
                $combinedPallets[] = $currentPallet;
            }
        }

        if (count($combinedPallets) > 0) {
            Self::logPackingDebug('Combined pallet packs by height', [
                'input_pack_count' => count($packs),
                'group_count' => count($palletGroups),
                'combined_pallet_count' => count($combinedPallets),
            ]);
        }

        return array_merge($nonPalletPacks, $combinedPallets);
    }

    static function checkMandatoryAddressFields($address)
    {
        if ((!isset($address['fc_shipping_suburb']) || isset($address['fc_shipping_suburb']) && !$address['fc_shipping_suburb']) &&
            (!isset($address['fc_billing_suburb']) || isset($address['fc_billing_suburb']) && !$address['fc_billing_suburb'])
        ) {
            return false;
        }
        return true;
    }

    static function qouteDataFormatter($customer, $location, $items)
    {
        $data = false;
        if (isset($customer['post_data'])) { // for classic checkout
            $address = [];
            parse_str($customer['post_data'], $address);

            $address = fc_sanitize_data($address);

            if (!Self::checkMandatoryAddressFields($address)) {
                return $data;
            }

            if (isset($address['fc_shipping_suburb']) && !empty($address['fc_shipping_suburb']) && isset($address['ship_to_different_address']) && $address['ship_to_different_address'] == 1) {
                $postcode = $address['shipping_postcode'];
                $state = $address['shipping_state'];
                $buildingType = $address['shipping_company'] == '' ? 'residential' : 'commercial';
                $dCity = $_POST['s_city'];
                $firstName = $address['shipping_first_name'];
                $lastName = $address['shipping_last_name'];
                $companyName = $address['shipping_company'] == '' ? 'NA' : $address['shipping_company'];
                $address1 = ($address['shipping_address_1'] != '') ? $address['shipping_address_1'] : null;
                $address2 = $address['shipping_address_2'] ?? null;
                if (!$dCity || $dCity == '') {
                    $parts = explode(',', $address['fc_shipping_suburb']);
                    $city = trim($parts[0]);
                    $dCity = $city;
                }
                if (!$state || $state == '') {
                    if (preg_match('/\(([^)]+)\)/', $address['fc_shipping_suburb'], $m)) {
                        $state = trim($m[1]);
                    }
                }
                if ((!$postcode || $postcode == '') && $address['fc_shipping_suburb'] != '') {
                    if (preg_match('/\b(\d{4})\b/', $address['fc_shipping_suburb'], $m)) {
                        $postcode = $m[1];
                    }
                }
            } else {
                $postcode = $address['billing_postcode'];
                $state = $address['billing_state'];
                $buildingType = $address['billing_company'] == '' ? 'residential' : 'commercial';
                $dCity = $_POST['city'];
                $firstName = $address['billing_first_name'];
                $lastName = $address['billing_last_name'];
                $companyName = $address['billing_company'] == '' ? 'NA' : $address['billing_company'];
                $address1 = ($address['billing_address_1'] != '') ? $address['billing_address_1'] : null;
                $address2 = $address['billing_address_2'] ?? null;

                if ((!$dCity || $dCity == '') && $address['fc_billing_suburb'] != '') {
                    $parts = explode(',', $address['fc_billing_suburb']);
                    $city = trim($parts[0]);
                    $dCity = $city;
                }
                if ((!$state || $state == '') && $address['fc_billing_suburb'] != '') {
                    if (preg_match('/\(([^)]+)\)/', $address['fc_billing_suburb'], $m)) {
                        $state = trim($m[1]);
                    }
                }
                if ((!$postcode || $postcode == '') && $address['fc_billing_suburb'] != '') {
                    if (preg_match('/\b(\d{4})\b/', $address['fc_billing_suburb'], $m)) {
                        $postcode = $m[1];
                    }
                }
            }
            $email = $address['billing_email'];
            $phone = $address['billing_phone'];
            $formattedItems = Self::formatPackages($items);
            $totalShipmentWeight = Self::totalShipmentWeight($formattedItems);

            $data = [
                'pickupFirstName' => $location['first_name'],
                'pickupLastName' => $location['last_name'],
                'pickupCompanyName' => $location['location_name'],
                'pickupEmail' => $location['email'],
                'pickupAddress1' => $location['address1'] ?? null,
                'pickupStreetNumber' => $location['street_number'] ?? null,
                'pickupStreetName' => $location['street_name'] ?? null,
                'pickupAddress2' => $location['address2'] ? $location['address2'] : null,
                'pickupPhone' => $location['phone'],
                'pickupSuburb' => $location['suburb'],
                'pickupState' => $location['state'],
                'pickupPostcode' => $location['postcode'],
                'pickupBuildingType' => $location['building_type'],
                'pickupTimeWindow' => $location['time_window'],
                'isPickupTailLift' => $location['tail_lift'] ?? 0,

                'destinationSuburb' => $dCity,
                'destinationState' => $state,
                'destinationPostcode' => $postcode,
                'destinationBuildingType' => $buildingType,
                'destinationFirstName' => $firstName,
                'destinationLastName' => $lastName,
                'destinationCompanyName' => $companyName,
                'destinationEmail' => $email,
                'destinationAddress1' => $address1 ?? null,
                'destinationAddress2' => $address2,
                'destinationPhone' => $phone,

                'parcelContent' => 'Order from ' . $location['location_name'],

                'valueOfContent' => WC()->cart->get_cart_contents_total(),
                'items' => $formattedItems,
                'totalWeight' => $totalShipmentWeight
            ];

            if (isset($address['fc_atl_checkbox'])) {
                $data['atl'] = true;
            }

            if (isset($address['fc_local_pickup'])) {
                $data['fc_local_pickup'] = true;
            }
        } elseif ($customer['country'] && $customer['state'] && $customer['postcode'] && $customer['city']) { // for block hooks checkout

            $firstName = $customer['shipping_first_name'];
            $lastName = $customer['shipping_last_name'];
            $email = $customer['billing_email'] ?? null;
            $phone = $customer['shipping_phone'] ?? null;
            $address1 = $customer['shipping_address_1'] ?? null;
            $address2 = $customer['shipping_address_2'] ?? null;
            $dCity = $customer['shipping_city'];
            $state = $customer['shipping_state'];
            $postcode = $customer['shipping_postcode'];
            $buildingType = empty($customer['shipping_company']) ? 'residential' : 'commercial';
            $companyName = empty($customer['shipping_company']) ? 'NA' : $customer['shipping_company'];
            $formattedItems = Self::formatPackages($items);
            $totalShipmentWeight = Self::totalShipmentWeight($formattedItems);

            $data = [
                'pickupFirstName' => $location['first_name'],
                'pickupLastName' => $location['last_name'],
                'pickupCompanyName' => $location['location_name'],
                'pickupEmail' => $location['email'],
                'pickupAddress1' => $location['address1'] ?? null,
                'pickupStreetNumber' => $location['street_number'] ?? null,
                'pickupStreetName' => $location['street_name'] ?? null,
                'pickupAddress2' => $location['address2'] ? $location['address2'] : null,
                'pickupPhone' => $location['phone'],
                'pickupSuburb' => $location['suburb'],
                'pickupState' => $location['state'],
                'pickupPostcode' => $location['postcode'],
                'pickupBuildingType' => $location['building_type'],
                'pickupTimeWindow' => $location['time_window'],
                'isPickupTailLift' => $location['tail_lift'] ?? 0,

                'destinationSuburb' => $dCity,
                'destinationState' => $state,
                'destinationPostcode' => $postcode,
                'destinationBuildingType' => $buildingType,
                'destinationFirstName' => $firstName,
                'destinationLastName' => $lastName,
                'destinationCompanyName' => $companyName,
                'destinationEmail' => $email,
                'destinationAddress1' => $address1 ?? null,
                'destinationAddress2' => $address2,
                'destinationPhone' => $phone,

                'parcelContent' => 'Order from ' . $location['location_name'],

                'valueOfContent' => WC()->cart->get_cart_contents_total(),
                'items' => $formattedItems,
                'totalWeight' => $totalShipmentWeight
            ];
        }
        return $data;
    }

    private static function deriveOverridesFromCalculatorPayload($cartItem, $wcProduct, $productId)
    {
        if (empty($cartItem['ccb_calculator']) || empty($cartItem['ccb_calculator']['calc_data']) || !is_array($cartItem['ccb_calculator']['calc_data'])) {
            return [];
        }

        $rawMappings = get_option('wmsd_calculator_mappings', []);
        if (empty($rawMappings) || !is_array($rawMappings)) {
            return [];
        }

        $calculatorData = $cartItem['ccb_calculator'];
        $calcData = $calculatorData['calc_data'];
        $calcId = isset($calculatorData['calc_id']) ? (int) $calculatorData['calc_id'] : 0;
        $parentProductId = method_exists($wcProduct, 'get_parent_id') ? (int) $wcProduct->get_parent_id() : 0;
        $validProductIds = array_filter([(int) $productId, $parentProductId]);

        $overrides = [];
        foreach ($rawMappings as $mapping) {
            $mappingCalcId = isset($mapping['calculator_id']) ? (int) $mapping['calculator_id'] : 0;
            $mappingProductId = isset($mapping['product_id']) ? (int) $mapping['product_id'] : 0;
            $fieldAlias = isset($mapping['field_alias']) ? (string) $mapping['field_alias'] : '';
            $metaKey = isset($mapping['meta_key']) ? (string) $mapping['meta_key'] : '';

            if (!$mappingCalcId || !$fieldAlias || !$metaKey) {
                continue;
            }

            if ($calcId > 0 && $mappingCalcId !== $calcId) {
                continue;
            }

            if ($mappingProductId > 0 && !in_array($mappingProductId, $validProductIds, true)) {
                continue;
            }

            if (empty($calcData[$fieldAlias]) || !is_array($calcData[$fieldAlias]) || !array_key_exists('value', $calcData[$fieldAlias])) {
                continue;
            }

            $value = $calcData[$fieldAlias]['value'];
            if (is_array($value)) {
                $value = wp_json_encode($value);
            }
            if (is_string($value)) {
                $value = trim($value);
            }
            if ($value === '' && $value !== '0' && $value !== 0) {
                continue;
            }

            $overrides[$metaKey] = $value;
        }

        return $overrides;
    }
}
