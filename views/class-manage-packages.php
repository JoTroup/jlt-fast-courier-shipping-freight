<?php

namespace FastCourier;

use FastCourier\FastCourierRequests;

class FastCourierManagePackages
{
    public static function index(): array
    {
        global $wpdb, $fc_packages_table;

        $query = "SELECT * FROM {$fc_packages_table} WHERE 1";
        $packages = $wpdb->get_results($query, ARRAY_A);

        return $packages;
    }

    public static function mapFcPackages()
    {
        try {
            $products = fc_sanitize_data($_POST['products']);
            $postData = fc_sanitize_data($_POST);

            $i = 0;
            while ($i < count($products)) {
                parse_str($products[$i], $outputArray);

                if ($outputArray['productType'] == 'variable') {
                    $product = wc_get_product($outputArray['pId']);
                } else {
                    $product = new \WC_Product($outputArray['pId']);
                }
                // delete the old fc meta fields
                delete_fc_meta($product);

                foreach ($postData as $key => $value) {
                    // Check if the key ends with a number (e.g., "length1", "width2")
                    if (preg_match('/^(\w+)(\d+)$/', $key, $matches)) {
                        // Get the base key (e.g., "length", "width")
                        $base_key = $matches[1];
                        // Get the index (e.g., "1", "2")
                        $index = $matches[2];
                        // Check if the base key is one of "length," "width," "height," or "weight"
                        if (in_array($base_key, array('length', 'width', 'height', 'weight', 'package_type'))) {
                            // Set the field (e.g., "fc_length_1", "fc_width_1")
                            $field = 'fc_' . $base_key . '_' . $index;
                            // Save the data with dynamic postfix as metadata
                            update_post_meta($outputArray['pId'], $field, $value);
                        }
                        if (in_array($base_key, array('individual'))) {
                            // Set the field (e.g., "fc_length_1", "fc_width_1")
                            $field = 'fc_is_' . $base_key . '_' . $index;
                            // Save the data with dynamic postfix as metadata
                            update_post_meta($outputArray['pId'], $field, $value);
                        }
                    }
                }

                update_post_meta($outputArray['pId'], 'fc_height', $postData['height']);
                update_post_meta($outputArray['pId'], 'fc_width', $postData['width']);
                update_post_meta($outputArray['pId'], 'fc_length', $postData['length']);
                update_post_meta($outputArray['pId'], 'fc_weight', $postData['weight']);
                update_post_meta($outputArray['pId'], 'fc_is_individual', $postData['individual']);
                update_post_meta($outputArray['pId'], 'fc_package_type', $postData['package_type']);
                $i++;
            }

            /*
                Need to discuss if weight update logic on line no. 157 need to be add
            */

            $configurationCompleted = ['product-mapping' => 1]; // set session for enable sidebar
            WC()->session->set('configuration_completed', $configurationCompleted);

            FastCourierVerifyToken::fc_menu_access_update();

            $response = (FastCourierRequests::successResponse());
        } catch (\Exception $e) {
            $response = (FastCourierRequests::failResponse($e->getMessage()));
        }

        header('Content-type: application/json');
        echo wp_json_encode($response);

        exit;
    }

    /**
     * Maps existing dimensions.
     */
    public static function mapExistingDimensions()
    {
        try {
            // Sanitize the data from the POST request
            $postData = fc_sanitize_data($_POST);

            // Create an instance of the current class
            $self = new self();

            // Validate products with packages
            $result = $self->validateProductsWithPackages($postData);

            // Check if the validation was successful
            if (!empty($result)) {
                // Create a success response with default status code
                $response = (FastCourierRequests::successResponse(200, $result));
            } else {
                // Create a success response with custom status code and result
                $response = (FastCourierRequests::successResponse());
            }
        } catch (\Exception $e) {
            // Create a failure response with the error message
            $response = (FastCourierRequests::failResponse($e->getMessage()));
        }

        // Set the response header to indicate JSON content
        header('Content-type: application/json');

        // Encode the response as JSON and output it
        echo wp_json_encode($response);

        // Exit the script
        exit;
    }

    /**
     * Validates products with packages.
     *
     * @param array $postData The post data.
     * @return string The message.
     */
    public static function validateProductsWithPackages($postData)
    {
        $args = [];
        if (!empty($postData['products']) && is_array($postData['products'])) {
            $selectedProductStrings = fc_sanitize_data($postData['products']);
            $i = 0;
            while ($i < count($selectedProductStrings)) {
                parse_str($selectedProductStrings[$i], $outputArray);
                $args['include'][] = $outputArray['pId']; // argument include will include only selected products based on their ids
                $i++;
            }
        }

        // Get the list of products
        $productsResult = FastCourierProducts::products($args);
        $products = $productsResult['products'];

        // Initialize the message variable
        $message = [];

        $totalProducts = count($products);
        $syncedproduct = 0;
        try {
            // Loop through each product
            foreach ($products as $key => $product) {

                $current_date_time = current_time('Y-m-d H:i:s');
                // Check if the product is variable
                $variableProduct = $product->is_type('variable');

                $isDimensionsUpdated = false;
                if ($variableProduct) {
                    // Get the list of variations
                    $variations = $product->get_available_variations();
                    if (!empty($variations) && count($variations) > 0) {
                        // Loop through each variation
                        foreach ($variations as $variation) {
                            // Get the variation ID
                            $id = (int) $variation['variation_id'];

                            if (empty($args)) {
                                $fc_last_synced = get_post_meta($id, 'fc_last_synced', true);
                                if ($fc_last_synced) {
                                    $fc_last_synced_timestamp = is_numeric($fc_last_synced) ? intval($fc_last_synced) : strtotime($fc_last_synced);
                                    $fc_last_synced_plus_one_min = $fc_last_synced_timestamp + 60;
                                    $fc_last_synced_plus_one_min_formatted = date('Y-m-d H:i:s', $fc_last_synced_plus_one_min);

                                    $last_updated = get_post_field('post_modified', $id);

                                    if ($fc_last_synced_plus_one_min_formatted >= $last_updated) {
                                        $fc_last_synced = $last_updated = '';
                                        continue;
                                    }
                                }
                            }

                            $variable_product = wc_get_product($variation['variation_id']);
                            // Delete the fc meta fields
                            delete_fc_meta($variable_product);

                            // Get the dimensions and weight of the variation
                            $height = (float) $variable_product->get_height();
                            $width = (float) $variable_product->get_width();
                            $length = (float) $variable_product->get_length();
                            $weight = (float) $variable_product->get_weight();
                            $pack_type = $postData['packages'];

                            // Update the meta data of the variation
                            update_post_meta($id, 'fc_height', $height);
                            update_post_meta($id, 'fc_width', $width);
                            update_post_meta($id, 'fc_length', $length);
                            update_post_meta($id, 'fc_weight', $weight);
                            update_post_meta($id, 'fc_is_individual', 1);
                            update_post_meta($id, 'fc_package_type', $pack_type);
                            update_post_meta($id, 'fc_last_synced', $current_date_time);
                            $fc_last_synced = $last_updated = '';
                        }
                        $isDimensionsUpdated = true;
                    }
                } else {
                    // Get the product ID
                    $id = (int) $product->get_id();

                    if (empty($args)) {
                        $fc_last_synced = get_post_meta($id, 'fc_last_synced', true);
                        if ($fc_last_synced) {
                            $last_updated = get_post_field('post_modified', $id);
                            if ($fc_last_synced >= $last_updated) {
                                $fc_last_synced = $last_updated = '';
                                $syncedproduct++;
                                continue;
                            }
                        }
                    }
                    // Delete the old fc meta fields
                    delete_fc_meta($product);

                    // Get the dimensions and weight of the product
                    $height = (float) $product->get_height() ?? get_field($postData['height'], $id);
                    $width = (float) $product->get_width() ?? get_field($postData['width'], $id);
                    $length = (float) $product->get_length() ?? get_field($postData['length'], $id);
                    $weight = (float) $product->get_weight() ?? get_field($postData['weight'], $id);
                    $pack_type = $postData['packages'];

                    // Update the dimensions and weight of the product
                    $product->update_meta_data('fc_height', $height);
                    $product->update_meta_data('fc_width', $width);
                    $product->update_meta_data('fc_length', $length);
                    $product->update_meta_data('fc_weight', $weight);
                    $product->update_meta_data('fc_is_individual', 1);
                    $product->update_meta_data('fc_package_type', $pack_type);
                    $product->update_meta_data('fc_last_synced', $current_date_time);
                    $product->save();

                    $fc_last_synced = $last_updated = '';
                    $isDimensionsUpdated = true;
                }

                if ($isDimensionsUpdated) $syncedproduct++;
                $current_date_time = '';
            }
        } catch (\Exception $e) {
            $message['error'] = $e->getMessage();
        }

        $message['message'] = 'Total Products: ' . $totalProducts . ' | Synced Products: ' . $syncedproduct;
        // Set the is_fc_enabled option to 1 to allow fetch quotes
        update_option('is_fc_enabled', 1);

        return $message;
    }

    public static function updateWeight()
    {
        try {
            global $wpdb, $fc_options;
            $products = fc_sanitize_data($_POST['products']);
            $postData = fc_sanitize_data($_POST);

            $i = 0;
            while ($i < count($products)) {
                $product = new \WC_Product($products[$i]);
                $product->update_meta_data('fc_weight', $postData['weight']);
                $product->save();

                $i++;
            }

            $args = array(
                'posts_per_page' => -1,
                'post_type' => 'product',
                'product_type' => 'simple',
            );
            $query = new \WP_Query($args);

            $count_posts = $query->post_count;

            $table = _get_meta_table('post');
            $weightedProducts = $wpdb->get_results("SELECT COUNT(*) AS total_products FROM {$table} WHERE meta_key = 'fc_weight'", ARRAY_A);

            if ($count_posts <= $weightedProducts[0]['total_products']) {
                $wpdb->replace($fc_options, ['option_name' => 'is_fc_enabled', 'option_value' => 1, 'autoload' => 'yes']);
            } else {
                $wpdb->replace($fc_options, ['option_name' => 'is_fc_enabled', 'option_value' => 0, 'autoload' => 'yes']);
            }

            $response = (FastCourierRequests::successResponse());
        } catch (\Exception $e) {
            $response = (FastCourierRequests::failResponse($e->getMessage()));
        }

        header('Content-type: application/json');
        echo wp_json_encode($response);

        exit;
    }

    public static function getPackageTypes()
    {
        $response = FastCourierRequests::httpGet('package_types');

        return $response['data']['data'];
    }

    /**
     * Adds a new shipping box to the database.
     */
    public static function addShippingBox()
    {
        try {
            global $wpdb, $fc_packages_table;

            // Creating an array with the shipping package data
            $shipping_package = [
                'package_name' => $_POST['package_name'], // Get the package name from the $_POST array
                'package_type' => $_POST['package_type'], // Get the package type from the $_POST array
                'outside_l' => $_POST['outside_l'], // Get the outside length from the $_POST array
                'outside_w' => $_POST['outside_w'], // Get the outside width from the $_POST array
                'outside_h' => $_POST['outside_h'], // Get the outside height from the $_POST array
                'is_default' => $_POST['is_default'], // Get the is_default flag from the $_POST array
            ];

            // Checking if this package should be set as default
            if ($_POST['is_default'] == 1) {
                // Removing all default packages from the database
                $wpdb->update($fc_packages_table, ['is_default' => 0], ['is_default' => 1], ['%d']);
            }

            // Inserting the shipping package data into the database
            $wpdb->insert($fc_packages_table, $shipping_package);

            $response = (FastCourierRequests::successResponse()); // Generate a success response
        } catch (\Exception $e) {
            $response = (FastCourierRequests::failResponse($e->getMessage())); // Generate a failure response with the error message
        }

        header('Content-type: application/json'); // Set the response header to indicate JSON content
        echo wp_json_encode($response); // Encode the response as JSON and echo it

        exit; // Terminate the execution of the script
    }

    /**
     * Process the dimensions CSV file.
     * 
     * @throws Exception if an error occurs while processing the CSV file.
     */
    public static function processDimensionsCsv()
    {
        try {
            // Check if CSV file is uploaded and has no errors
            if (isset($_FILES["csvFile"]) && $_FILES["csvFile"]["error"] == UPLOAD_ERR_OK) {
                // Get the temporary path of the CSV file
                $csvFilePath = $_FILES["csvFile"]["tmp_name"];

                // Open the CSV file for reading
                $file = fopen($csvFilePath, 'r');

                // Get the shipping boxes
                $shipping_box = self::index();

                $data = $noPackageFoundForRow = [];
                $lastSku = "";
                $metaIndex = $csvRow = 0;
                // Loop through each row in the CSV
                while (($row = fgetcsv($file)) !== false) {

                    // Skip the first row (header row)
                    if ($csvRow == 0) {
                        $csvRow++;
                        continue;
                    }

                    // Get the values from the CSV row
                    $name = $row[0] ? trim($row[0]) : "";
                    $sku = $row[1] ? trim($row[1]) : "";
                    $length = $row[2] ? (float) trim($row[2]) : 0;
                    $width = $row[3] ? (float) trim($row[3]) : 0;
                    $height = $row[4] ? (float) trim($row[4]) : 0;
                    $weight = $row[5] ? (float) trim($row[5]) : 0;
                    $individual = $row[6] ? trim($row[6]) : "yes"; // set default as "YES"
                    $packageType = $row[7] ? trim($row[7]) : "box"; // set default as "BOX"

                    // Check if the product is not shipped individually, and any shipping package is available 
                    if ($individual != "" && strtolower($individual) == 'no') {

                        $shippingPackageAvailable = 0;

                        foreach ($shipping_box as $k => $pack) {
                            $lengthFound = $widthFound = $heightFound = 0;
                            $pack_length = (float) $pack['outside_l'];
                            $pack_height = (float) $pack['outside_h'];
                            $pack_width = (float) $pack['outside_w'];

                            if ($pack_length > $length) {
                                $lengthFound = 1;
                            }

                            if ($pack_height > $height) {
                                $heightFound = 1;
                            }

                            if ($pack_width > $width) {
                                $widthFound = 1;
                            }

                            if ($lengthFound && $heightFound && $widthFound) {
                                $shippingPackageAvailable = 1;
                            }
                        }
                        // If no shipping package is available
                        if (!$shippingPackageAvailable) {
                            $csvRow++;
                            $noPackageFoundForRow[] = $csvRow;
                            continue;
                        }
                    }

                    // collect the dimensions data
                    if (($lastSku != "" && $sku == "") || ($lastSku != "" && $sku != "" && $lastSku == $sku)) {

                        $metaIndex++;
                        $data[$lastSku]['fc_length_' . $metaIndex] = $length;
                        $data[$lastSku]['fc_width_' . $metaIndex] = $width;
                        $data[$lastSku]['fc_height_' . $metaIndex] = $height;
                        $data[$lastSku]['fc_weight_' . $metaIndex] = $weight;
                        $data[$lastSku]['fc_is_individual_' . $metaIndex] = ($individual != "" && strtolower($individual) == 'yes') ? 1 : 0;
                        $data[$lastSku]['fc_package_type_' . $metaIndex] = ($packageType != "") ? strtolower($packageType) : "";
                    } else {

                        $data[$sku] = [
                            'name' => $name,
                            'sku' => $sku,
                            'fc_length' => $length,
                            'fc_width' => $width,
                            'fc_height' => $height,
                            'fc_weight' => $weight,
                            'fc_is_individual' => ($individual != "" && strtolower($individual) == 'yes') ? 1 : 0,
                            'fc_package_type' => ($packageType != "") ? strtolower($packageType) : "",
                        ];
                    }

                    if ($lastSku != "" && $sku != "" && $lastSku != $sku) {
                        $metaIndex = 0;
                    }

                    if ($lastSku == "" || $sku != "") {
                        $lastSku = ($sku != "") ? $sku : $lastSku;
                    }

                    $csvRow++;
                }
                // Close the CSV file
                fclose($file);

                $productsNotFound = [];
                foreach ($data as $sku => $value) {
                    // get product id from sku
                    $productId = wc_get_product_id_by_sku($sku);
                    // product not found for the sku ($sku)
                    if ($productId == 0) {
                        $productsNotFound[] = $sku;
                        continue;
                    }
                    // get product
                    $product = wc_get_product($productId);
                    // delete the old fc meta fields
                    delete_fc_meta($product);
                    // unset unwanted fields
                    unset($value['sku'], $value['name']);
                    // update the product
                    foreach ($value as $metaKey => $metaValue) {
                        update_post_meta($productId, $metaKey, $metaValue);
                    }
                }
                // set response data
                $responseData['productsNotFound'] = $responseData['noPackageFoundForRows'] = '';
                // set response data if no product found
                if (count($productsNotFound) > 0) {
                    $responseData['productsNotFound'] = "No product found for sku(s): " . implode(', ', $productsNotFound);
                }
                // set response data if no package found
                if (count($noPackageFoundForRow) > 0) {
                    $responseData['noPackageFoundForRows'] = 'No shipping boxes found for row(s): ' . implode(', ', $noPackageFoundForRow);
                }

                $configurationCompleted = ['product-mapping' => 1]; // set session for enable sidebar
                WC()->session->set('configuration_completed', $configurationCompleted);

                // return response data
                $response = (FastCourierRequests::successResponse(200, $responseData));
            }
        } catch (\Exception $e) {
            // Generate a fail response with the error message
            $response = (FastCourierRequests::failResponse($e->getMessage()));
        }

        // Set the response header to JSON and encode the response as JSON
        header('Content-type: application/json');
        echo wp_json_encode($response);

        // Exit the script
        exit;
    }
}
