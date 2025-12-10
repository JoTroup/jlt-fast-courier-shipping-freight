<?php

use FastCourier\FastCourierUpdateQuotes;
use FastCourier\FastCourierCart;

class Fast_Courier_Shipping_Method extends WC_Shipping_Method
{

    protected $cost;

    /**
     * Constructor.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->id = 'fast_courier_shipping';
        $this->method_title = __('Fast Courier');
        // $this->method_description = __('Adds a flat custom shipping rate.');
        $this->enabled = "yes";
        $this->title = "Fast Courier";
        $this->init();
        add_action('woocommerce_cart_updated', [$this, 'reset_fc_quote_flag']);
    }

    /**
     * Initialize Fast Courier.
     *
     * Initialize the shipping method by loading it's settings and
     * defining the form fields. Also, add an action to hook into
     * the save settings process.
     *
     * @since 1.0.0
     */
    public function init()
    {
        $this->init_form_fields();
        $this->init_settings();
        $this->title = $this->get_option('title');
        $this->cost = $this->get_option('cost');

        add_action('woocommerce_update_options_shipping_' . $this->id, [$this, 'process_admin_options']);
    }

    /**
     * Init form fields.
     *
     * @return void
     */
    public function init_form_fields()
    {
        $this->form_fields = [
            'title' => [
                'title'       => __('Method Title'),
                'type'        => 'text',
                'description' => __('Title seen by customers'),
                'default'     => __('Fast Courier'),
            ]
        ];
    }

    /**
     * Checks if the current page is the checkout page.
     *
     * @return bool
     */
    private function fc_is_checkout_context(): bool
    {
        // Handle classic checkout pages
        if (is_checkout()) {
            return true;
        }

        // Handle block-based Store API requests made **only by Checkout block**
        if (defined('REST_REQUEST') && REST_REQUEST) {
            $uri = $_SERVER['REQUEST_URI'] ?? '';
            $referer = $_SERVER['HTTP_REFERER'] ?? '';

            if (
                preg_match('#/wp-json/wc/store(?:/v1)?/(checkout|cart|shipping|batch)#', $uri) &&
                strpos($referer, 'checkout') !== false
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Computes a hash for the given address data.
     *
     * This function can be used to create a unique identifier for an address.
     * The hash is computed by encoding the address data as a JSON string and
     * then computing the MD5 hash of that string.
     *
     * @param array $address_data The address data to create a hash for.
     * @return string The computed hash.
     */
    private function get_address_hash($address_data)
    {
        return md5(json_encode($address_data));
    }

    /**
     * Determines if the current request is a Store API place order request.
     *
     * This function checks if the request is a REST API request with the POST method
     * and matches the Store API checkout endpoint pattern.
     *
     * @return bool True if the request is a Store API place order request, false otherwise.
     */

    private function is_store_api_place_order_request(): bool
    {
        if (!defined('REST_REQUEST') || !REST_REQUEST || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            return false;
        }

        $uri = $_SERVER['REQUEST_URI'] ?? '';
        return preg_match('#/wp-json/wc/store(?:/v1)?/checkout/?(\?.*)?$#', $uri);
    }

    /**
     * Adds a shipping method with the calculated shipping cost to the checkout.
     *
     * @param bool $is_place_order Whether this is a place order request.
     */
    private function add_shipping_price($is_place_order = false)
    {
        $session = WC()->session;
        $shipping_cost = $session->get('fc_calculated_shipping_cost') ?? 0;

        $rate = [
            'id'    => $this->id,
            'label' => $this->title,
            'cost'  => $shipping_cost,
            'calc_tax' => 'per_order'
        ];

        $this->add_rate($rate);
    }

    /**
     * Calculates shipping cost.
     *
     * This method is used on cart and checkout pages. It checks if the
     * current page is the checkout or cart page and returns if it's not.
     *
     * It then reads the values from the checkout form and adds them to
     * the package 'destination' array. It then calls the
     * FastCourierUpdateQuotes::checkingQuotes method to get the shipping
     * quotes for the package and adds the shipping cost to the package.
     *
     * @param array $package The package array.
     *
     * @return void
     */
    public function calculate_shipping($package = [])
    {
        $session = WC()->session;

        // Skip if not on checkout page
        if (!$this->fc_is_checkout_context()) {
            return;
        }

        $existingDestination = $package['destination'] ?? [];
        $classicCheckoutArray = [
            'billing_first_name' => WC()->checkout()->get_value('billing_first_name'),
            'billing_last_name' => WC()->checkout()->get_value('billing_last_name'),
            'billing_company' => WC()->checkout()->get_value('billing_company'),
            'billing_country' => WC()->checkout()->get_value('billing_country'),
            'billing_address_1' => WC()->checkout()->get_value('billing_address_1'),
            'billing_address_2' => WC()->checkout()->get_value('billing_address_2'),
            'billing_city' => WC()->checkout()->get_value('billing_city'),
            'billing_state' => WC()->checkout()->get_value('billing_state'),
            'billing_postcode' => WC()->checkout()->get_value('billing_postcode'),
            'billing_phone' => WC()->checkout()->get_value('billing_phone'),
            'billing_email' => WC()->checkout()->get_value('email'),
            'shipping_first_name' => WC()->checkout()->get_value('shipping_first_name'),
            'shipping_last_name' => WC()->checkout()->get_value('shipping_last_name'),
            'shipping_country' => WC()->checkout()->get_value('shipping_country'),
            'shipping_address_1' => WC()->checkout()->get_value('shipping_address_1'),
            'shipping_address_2' => WC()->checkout()->get_value('shipping_address_2'),
            'shipping_city' => WC()->checkout()->get_value('shipping_city'),
            'shipping_state' => WC()->checkout()->get_value('shipping_state'),
            'shipping_postcode' => WC()->checkout()->get_value('shipping_postcode'),
            'shipping_phone' => WC()->checkout()->get_value('shipping_phone'),
        ];

        if ($classicCheckoutArray['shipping_postcode'] == '' || $classicCheckoutArray['shipping_postcode'] == null) {
            return;
        }


        $cart_object = WC()->cart;
        error_log("Modifying cart contents: {total items} " . count($cart_object->get_cart()));
        foreach ( $cart_object->get_cart() as $cart_item_key => $cart_item ) {
            // Log the entire cart item array
            error_log("Cart Item [$cart_item_key]: ");
            $ccb_calculator = isset($cart_item['ccb_calculator']) ? $cart_item['ccb_calculator'] : null;
    
            $calc_height = $quantity_field_id_5 = isset($ccb_calculator['calc_data']['quantity_field_id_5']['value']) ? $ccb_calculator['calc_data']['quantity_field_id_5']['value'] : null;
    
            $calc_width = $quantity_field_id_4 = isset($ccb_calculator['calc_data']['quantity_field_id_4']['value']) ? $ccb_calculator['calc_data']['quantity_field_id_4']['value'] : null;
    
            $calc_length = $quantity_field_id_6 = isset($ccb_calculator['calc_data']['quantity_field_id_10']['value']) ? $ccb_calculator['calc_data']['quantity_field_id_10']['value'] : null;
    
            $calc_weight = $quantity_field_id_3 = isset($ccb_calculator['calc_data']['quantity_field_id_17']['value']) ? $ccb_calculator['calc_data']['quantity_field_id_17']['value'] : null;
    
            if ($calc_height !== null && $calc_width !== null && $calc_length !== null && $calc_weight !== null) {
                
                /// just for testing to see if we can set both pm_ and fc_ meta data to get plugin to work.
                foreach ( $cart_object->get_cart() as $cart_item_key => $cart_item ) {
                    // Overwrite fc_height meta data
                    if (isset($cart_item['data']) && is_object($cart_item['data'])) {
                        $cart_item['data']->update_meta_data('fc_height', $calc_height);
                        error_log("Overwrote fc_height for Cart Item [$cart_item_key]");
                    }
    
                    // Overwrite fc_width meta data
                    if (isset($cart_item['data']) && is_object($cart_item['data'])) {
                        $cart_item['data']->update_meta_data('fc_width', $calc_width);
                        error_log("Overwrote fc_width for Cart Item [$cart_item_key]");
                    }
    
                    // Overwrite fc_length meta data
                    if (isset($cart_item['data']) && is_object($cart_item['data'])) {
                        $cart_item['data']->update_meta_data('fc_length', $calc_length);
                        error_log("Overwrote fc_length for Cart Item [$cart_item_key]");
                    }
    
                    // Overwrite fc_weight meta data
                    if (isset($cart_item['data']) && is_object($cart_item['data'])) {
                        $cart_item['data']->update_meta_data('fc_weight', $calc_weight);
                        error_log("Overwrote fc_weight for Cart Item [$cart_item_key]");
                    }
                }
    
                // Log the calculated dimensions
                error_log("Calculated dimensions: Height=$calc_height, Width=$calc_width, Length=$calc_length, Weight=$calc_weight");
    
                foreach ( $cart_object->get_cart() as $cart_item_key => $cart_item ) {
                    error_log("Meta fc_width: " . $cart_item['data']->get_meta('fc_width', true));
                    error_log("Meta fc_length: " . $cart_item['data']->get_meta('fc_length', true));
                    error_log("Meta fc_height: " . $cart_item['data']->get_meta('fc_height', true));
                    error_log("Meta fc_weight: " . $cart_item['data']->get_meta('fc_weight', true));
                }
    
    
                // Apply changes to ensure they take effect
                if( !empty( $cart_item['data']->get_changes() ) )
                       $cart_item['data']->apply_changes(); 
    
                
                // Check and log the values set on the product
                $height = $cart_item['data']->get_height();
                $width  = $cart_item['data']->get_width();
                $length = $cart_item['data']->get_length();
                $weight = $cart_item['data']->get_weight();
            
            } else {
                error_log(" - Calculated dimensions not found in cart item.");
            }
        }




        // Iterate through cart items and log dimensions and weight
        foreach (WC()->cart->get_cart() as $cart_item_key => $item) {
            $product = $item['data'];

            if ($product instanceof WC_Product) {
            $fc_width = $product->get_width();
            $fc_height = $product->get_height();
            $fc_length = $product->get_length();
            $fc_weight = $product->get_weight();

            // Log the dimensions and weight
            error_log("Product Dimensions - Width: $fc_width, Height: $fc_height, Length: $fc_length, Weight: $fc_weight");
            }
        }
        

        $package['destination'] = array_merge($existingDestination, $classicCheckoutArray);

        $currentAddressHash = $this->get_address_hash($package['destination']);

        $lastAddressHash = $session->get('fc_last_shipping_hash') ?? null;
        $has_called_api = $session->get('fc_api_called_on_checkout', false);

        $is_place_order = $this->is_store_api_place_order_request();
        $is_order_placed = false;

        // Reset API call flag if cart/address has changed
        if ($currentAddressHash !== $lastAddressHash) {
            $session->set('fc_last_shipping_hash', $currentAddressHash);
            $session->set('fc_api_called_on_checkout', false);
            $has_called_api = false;
        }

        if ($is_place_order && $currentAddressHash === $lastAddressHash) {
            $is_order_placed = true;
            $this->add_shipping_price($is_order_placed);
        } else {
            if (!$has_called_api) {
                FastCourierUpdateQuotes::checkingQuotes($package);
                FastCourierCart::add_fc_charges();
                $session->set('fc_api_called_on_checkout', true);
            }

            $this->add_shipping_price();
        }
    }

    /**
     * Resets the Fast Courier quote flag if the cart contents have changed.
     *
     * This function compares the current cart items with the last saved cart hash.
     * If the hash has changed, it updates the session with the new cart hash
     * and resets the API call flag for checkout. This ensures that the shipping
     * quotes are recalculated when the cart contents are modified.
     *
     * @return void
     */
    public function reset_fc_quote_flag()
    {
        $session = WC()->session;

        if (!WC()->cart || WC()->cart->is_empty()) {
            return;
        }

        // Generate a consistent hash of cart items (not just cart hash)
        $cart_items_data = [];

        foreach (WC()->cart->get_cart() as $cart_item_key => $item) {
            $cart_items_data[] = [
                'product_id'   => $item['product_id'],
                'variation_id' => $item['variation_id'],
                'quantity'     => $item['quantity'],
            ];
        }

        $current_cart_hash = md5(json_encode($cart_items_data));
        $last_cart_hash = $session->get('fc_cart_hash');

        if ($current_cart_hash !== $last_cart_hash) {
            $session->set('fc_cart_hash', $current_cart_hash);
            $session->set('fc_api_called_on_checkout', false);
        }
    }
}

add_filter('woocommerce_shipping_methods', function ($methods) {
    $methods['fast_courier_shipping'] = Fast_Courier_Shipping_Method::class;
    return $methods;
});
