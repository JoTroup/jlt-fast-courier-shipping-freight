<?php

namespace FastCourier;

use DVDoug\BoxPacker\Packer;
use DVDoug\BoxPacker\Test\TestBox;  // use your own `Box` implementation
use DVDoug\BoxPacker\Test\TestItem; // use your own `Item` implementation

class FastCourierUpdateQuotes
{
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

        foreach ($cartItems as $item) {

            if (isset($item['variation_id']) && $item['variation_id'] > 0) {
                $product_id = $item['variation_id'];
                $wc_product = wc_get_product($product_id);
            } else {
                $product_id = $item['product_id'];
                $wc_product = new \WC_Product($product_id);
            }

            // check if product is virtual in WP
            $isVirtualProduct = $wc_product->is_virtual();
            $meta_dimensions = get_post_meta($product_id);
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

            $seprated_items['' . $eligibleForFreeShipping]['' . $location['id']]['products'][] = $wc_product;
            $seprated_items['' . $eligibleForFreeShipping]['' . $location['id']]['location'] = $location;
            $seprated_items['' . $eligibleForFreeShipping]['' . $location['id']][$product_id]['allow_shipping'] = $allow_shipping;
            $seprated_items['' . $eligibleForFreeShipping]['' . $location['id']][$product_id]['eligibleForShipping'] = $eligibleForShipping;
            $seprated_items['' . $eligibleForFreeShipping]['' . $location['id']]['dimensions'][$product_id] = $dimensionsData;
            $seprated_items['' . $eligibleForFreeShipping]['' . $location['id']][$product_id]['order_qty'] = $item['quantity'];
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
                foreach ($products as $product) {
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
                    $allow_shipping = $items[$productId]['allow_shipping'];
                    $eligibleForShipping = $items[$productId]['eligibleForShipping'];

                    if ($eligibleForShipping == '1') {
                        $eligibleForShippingFlag = $addShippingQuotesOncheckout = true;
                    }

                    // loop for multi shipped products
                    foreach ($items['dimensions'][$productId] as $k => $value) {

                        $ordered_qty = $items[$productId]['order_qty'];
                        if ($allow_shipping == "1") {
                            $isAllowShipping = true;
                            $isPhysicalProduct = true;


                            if ($product->get_meta('enable_dynamic_calculation') == 1) {
                                $height = (int) $product->get_meta('pm_height');
                                $width = (int) $product->get_meta('pm_width');
                                $length = (int) $product->get_meta('pm_length');
                                $weight = $product->get_meta('pm_weight') ? round((float) $product->get_meta('pm_weight'), 2) : 0;
                            } else {
                                if ($k == 0) {
                                    $height = (int) $product->get_meta('fc_height');
                                    $width = (int) $product->get_meta('fc_width');
                                    $length = (int) $product->get_meta('fc_length');
                                    $weight = $product->get_meta('fc_weight') ? round((float) $product->get_meta('fc_weight'), 2) : 0;
                                } else {
                                    $height = (int) $product->get_meta('fc_height_' . $k);
                                    $width = (int) $product->get_meta('fc_width_' . $k);
                                    $length = (int) $product->get_meta('fc_length_' . $k);
                                    $weight = $product->get_meta('fc_weight_' . $k) ? round((float) $product->get_meta('fc_weight_' . $k), 2) : 0;
                                }
                            }

                            // ensure height, width and length are not zero and weight is less than 0.01
                            $height = ($height <= 0) ? 1 : $height;
                            $width = ($width <= 0) ? 1 : $width;
                            $length = ($length <= 0) ? 1 : $length;
                            $weight = ($weight <= 0.1) ? 0.1 : $weight;

                            $pack = ['name' => $product->get_name(), 'height' => $height, 'width' => $width, 'length' => $length, 'weight' => $weight, 'type' => $pack_type, 'quantity' => $ordered_qty];

                            if ($is_individual) {
                                array_push($individualPacks, $pack);
                            } else {
                                $packer->addItem(new TestItem(wp_json_encode($pack), $pack['width'], $pack['length'], $pack['height'], $pack['weight'], true), $ordered_qty);
                            }

                            $totalProductsWeight += $weight * $ordered_qty;
                        }

                        $temp_product = [
                            'name' => $product->get_name(),
                            'sku' => $product->get_sku(),
                            'cost' => $product->get_price(),
                            'quantity' => $ordered_qty,
                            'tax' => $product->get_tax_class() ?? 0,
                            'weight' => $product->get_weight(),
                            'length' => $product->get_length(),
                            'width' => $product->get_width(),
                            'height' => $product->get_height(),
                            'shipping' => !in_array($product->get_type(), array('virtual', 'downloadable')),
                            'total' => $product->get_price() * $ordered_qty,
                        ];
                        $seprated_products[] = $temp_product;
                    }
                }

                if ($isAllowShipping && $eligibleForShippingFlag) {
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
                }

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
            $packs[$key] = [
                'name' => $pack['package_name'] ?? $pack['name'],
                'type' => $pack['package_type'] ?? $pack['type'],
                'height' => $pack['height'],
                'width' => $pack['width'],
                'length' => $pack['length'],
                'weight' => isset($pack['weight']) ? $pack['weight'] : 0,
                'quantity' => $pack['quantity'] ?? 1
            ];

            if (isset($pack['sub_packs']) && count($pack['sub_packs'])) {
                $packs[$key]['sub_packs'] = $pack['sub_packs'];
                $packs[$key]['weight'] = strval(array_sum(array_column($pack['sub_packs'], 'weight')));
            }
        }

        return $packs;
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
            }
            $email = $address['billing_email'];
            $phone = $address['billing_phone'];

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
                'items' => Self::formatPackages($items)
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
                'items' => Self::formatPackages($items)
            ];
        }
        return $data;
    }
}
