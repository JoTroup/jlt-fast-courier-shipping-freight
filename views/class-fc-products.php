<?php

namespace FastCourier;

class FastCourierProducts
{
    // Static array property for product types
    protected static $productTypes = array(
        'simple' => 'Simple',
        'virtual' => 'Virtual',
        'grouped' => 'Grouped',
        'variable' => 'Variable',
    );

    // Static array for bulk actions for products
    protected static $bulkActionTypes = array(
        'modalLocations' => 'Assign Location',
        'modalAllowShipping' => 'Eligible For Shipping',
        'modalFreeShipping' => 'Free Shipping',
        'modalIndividual' => 'Is Individual',
        'modalPackages' => 'Manually Assign Dimensions',
    );

    public static function categories($request): array
    {
        $taxonomy     = 'product_cat';
        $orderby      = 'name';
        $show_count   = 0;      // 1 for yes, 0 for no
        $pad_counts   = 0;      // 1 for yes, 0 for no
        $hierarchical = 0;      // 1 for yes, 0 for no  
        $title        = '';
        $empty        = 0;

        $args = array(
            'taxonomy'     => $taxonomy,
            'orderby'      => $orderby,
            'show_count'   => $show_count,
            'pad_counts'   => $pad_counts,
            'hierarchical' => $hierarchical,
            'title_li'     => $title,
            'hide_empty'   => $empty
        );
        $all_categories = get_categories($args);
        return $all_categories;
    }

    /**
     * Retrieves all tags from the 'product_tag' taxonomy.
     *
     * @return array An associative array of tag IDs and names.
     */
    public static function get_all_tags()
    {
        // Retrieve all tags from the 'product_tag' taxonomy
        $tags = get_terms(array(
            'taxonomy' => 'product_tag',
            'hide_empty' => false,
        ));

        $prod_tags = [];

        // Check if tags were retrieved successfully
        if (!empty($tags) && !is_wp_error($tags)) {
            // Loop through the tags and add them to the associative array
            foreach ($tags as $tag) {
                $prod_tags[$tag->term_id] = $tag->name;
            }
        }

        return $prod_tags;
    }

    public static function productTypes(): array
    {
        $all_types = wc_get_product_types();
        return $all_types;
    }


    public static function filterParams($keys)
    {
        $filters = [];
        // if product_type filter is selected
        if (isset($keys['productType']) && !empty($keys['productType'])) {
            if ($keys['productType'] == 'virtual') {
                $filters['virtual'] = true;
            } else {
                $filters['type'] = $keys['productType'];
            }
            unset($keys['productType']);
        } else {
            $filters['type'] = ['simple', 'variable', 'grouped'];
        }

        $filters['paginate'] = 1;
        $filters['limit'] = -1;
        foreach ($keys as $key => $value) {
            if ($key == 'category' && empty($value[0])) {
                continue;
            }
            // filter out products that have no tags
            if ($key == 'product_tag' && $value == 'noTags') {
                $filters['tax_query'] = array(
                    array(
                        'taxonomy' => 'product_tag',
                        'operator' => 'NOT EXISTS',
                    ),
                );
                continue;
            }

            if ($value) {
                $filters[$key] = $value;
            }
        }

        return $filters;
    }

    public static function products($requests): array
    {
        $filters = Self::filterParams($requests);
        $result = wc_get_products($filters);

        $products = $result->products;
        $pages = $result->max_num_pages;

        return ['products' => $products, 'pages' => $pages];
    }

    public static function get_all_product_fields()
    {
        global $wpdb;

        $product_fields = array();

        // Query the database for distinct product fields
        $query = "SELECT DISTINCT meta_key FROM {$wpdb->prefix}postmeta
                WHERE post_id IN ( SELECT ID FROM {$wpdb->prefix}posts WHERE post_type = 'product') ";

        $results = $wpdb->get_results($query);

        if ($results) {
            foreach ($results as $result) {
                $product_fields[] = $result->meta_key;
            }
        }

        return $product_fields;
    }

    /**
     * Checks if there are products with FC length and weight.
     *
     * @return bool True if at least one product with both fields exists, false otherwise.
     */
    public static function has_products_with_fc_length_and_weight()
    {
        // Set up the query arguments
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => 1, // Limit the query to 1 result
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => 'fc_length',
                    'compare' => 'EXISTS', // Check if the field exists
                ),
                array(
                    'key' => 'fc_weight',
                    'compare' => 'EXISTS', // Check if the field exists
                ),
            ),
        );

        // Execute the query
        $products_query = new \WP_Query($args);

        // Check if at least one product with both fields exists
        if ($products_query->have_posts()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Updates the fc meta data for the product and its variants.
     *
     * @param string $metaField The name of the meta data field to update.
     * 
     * @param array $payload The payload containing the product's ID and the
     *                       new value for the fc meta data.
     *
     * @return void
     */
    private static function updateSingleFcMetaData(string $metaField, array $payload)
    {
        // Update the $metaField meta data for the product
        update_post_meta($payload['pId'], $metaField, $payload[$metaField]);

        // Only update the product's variants if the product is not a simple product
        if (!isset($payload['productType']) || (isset($payload['productType']) && $payload['productType'] != 'simple')) {
            // Free shipping is allowed only on parent level
            if ($metaField == 'fc_allow_free_shipping') {
                return;
            }
            // Get the product object
            $product = wc_get_product($payload['pId']);

            // Check if the product is a variable product
            $variableType = $product->is_type('variable');

            if ($variableType) {

                // Get the product's available variations
                $variations = $product->get_available_variations();

                // Loop through the variations
                foreach ($variations as $variation) {

                    // Get the variant's ID
                    $variation_id = $variation['variation_id'];

                    // Update the dynamic $metaField meta data for the product's variants
                    update_post_meta($variation_id, $metaField, $payload[$metaField]);

                    // Unset the variant's ID
                    unset($variation_id);
                }
            }
        }
    }

    /**
     * Updates the allowShipping meta data for multiple products.
     */
    public static function updateAllowShipping()
    {
        try {
            // Sanitize the entire $_POST array
            $postData = fc_sanitize_data($_POST);

            // Update the allowShipping meta data for each product
            self::updateSingleFcMetaData("fc_allow_shipping", $postData);

            // Generate a success response
            $response = (FastCourierRequests::successResponse());
        } catch (\Exception $e) {
            // Generate a fail response with the error message
            $response = (FastCourierRequests::failResponse($e->getMessage()));
        }

        // Set the content type header to JSON
        header('Content-type: application/json');

        // Encode the response as JSON and output it
        echo wp_json_encode($response);

        // Terminate the script
        exit;
    }

    /**
     * Updates the allowShipping meta data for multiple products.
     */
    public static function updateBulkAllowShipping()
    {
        try {
            // Sanitize the product IDs array
            $products = fc_sanitize_data($_POST['products']);
            // Sanitize the entire $_POST array
            $postData = fc_sanitize_data($_POST);

            // Loop through each product ID and update its allowShipping meta data
            foreach ($products as $productId) {

                // Parse the product ID into an array
                parse_str($productId, $outputArray);
                // Merge the product ID array with the $_POST data
                $payload = array_merge($outputArray, $postData);

                // Update the allowShipping meta data for the product
                self::updateSingleFcMetaData("fc_allow_shipping", $payload);
            }

            // Generate a success response
            $response = (FastCourierRequests::successResponse());
        } catch (\Exception $e) {
            // Generate a fail response with the error message
            $response = (FastCourierRequests::failResponse($e->getMessage()));
        }

        // Set the content type header to JSON
        header('Content-type: application/json');

        // Encode the response as JSON and output it
        echo wp_json_encode($response);

        // Terminate the script
        exit;
    }

    /**
     * Updates the allowFreeShipping meta data for multiple products.
     */
    public static function updateAllowFreeShipping()
    {
        try {
            // Sanitize the entire $_POST array
            $postData = fc_sanitize_data($_POST);

            // Update the fc_allow_free_shipping meta data for the product
            self::updateSingleFcMetaData("fc_allow_free_shipping", $postData);

            // Generate a success response
            $response = (FastCourierRequests::successResponse());
        } catch (\Exception $e) {
            // Generate a fail response with the error message
            $response = (FastCourierRequests::failResponse($e->getMessage()));
        }

        // Set the content type header to JSON
        header('Content-type: application/json');

        // Encode the response as JSON and output it
        echo wp_json_encode($response);

        // Terminate the script
        exit;
    }

    /**
     * Updates the allowFreeShipping meta data for multiple products.
     *
     * @throws \Exception If there is an error updating the products' meta data
     */
    public static function updateBulkAllowFreeShipping()
    {
        try {
            // Sanitize the product IDs array
            $products = fc_sanitize_data($_POST['products']);
            // Sanitize the entire $_POST array
            $postData = fc_sanitize_data($_POST);

            // Loop through each product ID and update its fc_allow_free_shipping meta data
            foreach ($products as $productId) {
                // Parse the product ID into an array
                parse_str($productId, $outputArray);

                // Merge the product ID array with the $_POST data
                $payload = array_merge($outputArray, $postData);

                // Update the fc_allow_free_shipping meta data for the product
                self::updateSingleFcMetaData("fc_allow_free_shipping", $payload);
            }

            // Generate a success response
            $response = (FastCourierRequests::successResponse());
        } catch (\Exception $e) {
            // Generate a fail response with the error message
            $response = (FastCourierRequests::failResponse($e->getMessage()));
        }

        // Set the content type header to JSON
        header('Content-type: application/json');

        // Encode the response as JSON and output it
        echo wp_json_encode($response);

        // Terminate the script
        exit;
    }

    /**
     * Update the fc_is_individual meta data for multiple products.
     *
     * @return void
     */
    public static function updateBulkIndividual()
    {
        try {
            // Try to sanitize the product IDs array
            $products = fc_sanitize_data($_POST['products']);

            // Sanitize the entire $_POST array
            $postData = fc_sanitize_data($_POST);

            // Loop through each product ID and update its fc_is_individual meta data
            foreach ($products as $productId) {

                // Parse the product ID into an array
                parse_str($productId, $outputArray);

                // Get all meta fields
                $custom_fields = get_post_meta($outputArray['pId']);

                foreach ($custom_fields as $key => $value) {

                    // Check if the key is fc_is_individual
                    if (in_array($key, array('fc_is_individual'))) {

                        // Update the fc_is_individual meta data
                        update_post_meta($outputArray['pId'], $key, $postData[$key]);
                    }

                    // Check if the key ends with a number (e.g., "length1", "width2")
                    if (preg_match('/^(\w+)(\d+)$/', $key, $matches)) {

                        $base_key = $matches[1];

                        // Check if the base key is fc_is_individual_
                        if (in_array($base_key, array('fc_is_individual_'))) {

                            // Update the meta data with the new value
                            update_post_meta($outputArray['pId'], $base_key . $matches[2], $postData['fc_is_individual']);
                        }
                    }
                }
            }

            // Generate a success response
            $response = (FastCourierRequests::successResponse());
        }
        // Catch any exceptions and generate a fail response
        catch (\Exception $e) {
            $response = (FastCourierRequests::failResponse($e->getMessage()));
        }
        // Set the content type header to JSON
        header('Content-type: application/json');
        // Encode the response as JSON and output it
        echo wp_json_encode($response);
        // Terminate the script
        exit;
    }

    /**
     * Get the product type filter.
     *
     * @return array The product types filter.
     */
    public static function getProductTypeFilter()
    {
        // Return the product types filter
        return self::$productTypes;
    }

    /**
     * Get the available bulk actions.
     *
     * @return array The available bulk actions.
     */
    public static function getBulkActions()
    {
        return self::$bulkActionTypes;
    }
}
