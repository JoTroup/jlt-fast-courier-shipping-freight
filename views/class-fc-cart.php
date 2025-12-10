<?php

namespace FastCourier;

class FastCourierCart
{
    public static function index() {}

    public static function add_fc_charges()
    {
        global $option_merchant_field, $bookingPreferences, $shippingPreferences;
        $cart = WC()->cart;
        $session = WC()->session;

        if ($session->get('is_local_pickup')) {
            $shippingPrice = (float) 0;
            // if local pickup zone is avilable
            if ($session->get('fc_local_pickup_zone_found')) {
                // Get the customer's shipping zone
                $customer_zone = \WC_Shipping_Zones::get_zone_matching_package(array(
                    'destination' => array(
                        'country'  => WC()->customer->get_shipping_country(),
                        'state'    => WC()->customer->get_shipping_state(),
                        'postcode' => WC()->customer->get_shipping_postcode(),
                    ),
                ));

                // Check if local pickup is available in the customer's shipping zone
                $local_pickup_method = null;

                // Get all shipping methods in the zone
                $shipping_methods = $customer_zone->get_shipping_methods();

                foreach ($shipping_methods as $method) {
                    if ($method->id === 'local_pickup') {
                        $local_pickup_method = $method;
                        break;
                    }
                }

                if ($local_pickup_method) {
                    // Local pickup is available
                    $shippingtitle = $local_pickup_method->get_title() ?? $local_pickup_method->get_method_title();
                    $shippingPrice = (float) $local_pickup_method->get_option('cost') ?? 0;
                }
            } else {
                // if local pickup option is enabled
                $pickup_location_settings = get_option('woocommerce_pickup_location_settings', []);
                if ($pickup_location_settings['cost']) {
                    $shippingPrice = (float) $pickup_location_settings['cost'];
                }
                $shippingtitle = $pickup_location_settings['title'];
            }
            $cart->add_fee($shippingtitle, $shippingPrice);
            return;
        }

        $merchantDetails = fc_merchant_details();

        if (!$merchantDetails) return;

        $shippingAddress =  WC()->cart->get_shipping_packages()[0]['destination'];
        // $merchantDetails = json_decode(get_option($option_merchant_field), true);
        $freeShipping = false;

        if ($merchantDetails['shoppingPreference'] == $shippingPreferences['with_carrier_name']) {
            $freeShipping = false;
        }

        if ($merchantDetails['bookingPreference'] == $bookingPreferences['free']) {
            $session->set('fc_calculated_shipping_cost', 0);
            return;
        }

        if ($merchantDetails['bookingPreference'] == $bookingPreferences['free_on_basket']) {

            // Check if cart total is greater than or equal to the conditional price
            if ($cart->cart_contents_total >= $merchantDetails['conditionalPrice'] && $shippingAddress['postcode']) {
                $freeShipping = true;
            }
            Self::show_shipping_cost($freeShipping);
        }

        if ($merchantDetails['bookingPreference'] == $bookingPreferences['not_free']) {

            Self::show_shipping_cost($freeShipping);
        }
    }

    public static function show_shipping_cost($freeShipping)
    {

        $cart = WC()->cart;
        $session = WC()->session;
        $fcClassicMode = isClassicMode();

        $shippingAmount = 0;
        // in case shipping is free and not be eligible for order
        if ($freeShipping) {
            // 0 amount shipping will be removed
            if ($fcClassicMode) {
                $cart->shipping_total = $shippingAmount;
                $cart->shipping_tax_total = $shippingAmount;
            }
            $session->set('fc_calculated_shipping_cost', $shippingAmount);
            return;
        }

        // product is not eligible for shipping
        if ($session->get('addShippingQuotesOncheckout') && !$session->get('addShippingQuotesOncheckout')) {
            if ($fcClassicMode) {
                $cart->add_fee("Shipping", (float) $shippingAmount);
            }
            $session->set('fc_calculated_shipping_cost', $shippingAmount);
            // remove the shipping value and add contact html for shipping quotes
            fc_custom_shipping_price_html_modification();
            return;
        }

        if ($session->get('is_fallback_shipping')) {
            $fallbackShipping = $session->get('fallback_shipping');
            $session->set('fc_calculated_shipping_cost', (float) $fallbackShipping);
            if ($fcClassicMode) {
                $cart->add_fee("Shipping", (float) $fallbackShipping);
            }
            return;
        }

        if (!$session->get('quote')) return;
        // removed stripslashes as it breaks the json for the products that have " in their name
        $quotes = json_decode($session->get('quote'), true);
        $amount = 0;

        foreach ($quotes as $quote) {
            $amount += ($quote['shipping_type'] != SHIPPING_TYPE_FREE) ? $quote['data']['priceIncludingGst'] : 0;
        }

        if ($amount == 0) {
            // 0 amount shipping will be removed
            if ($fcClassicMode) {
                $cart->shipping_total = 0;
                $cart->shipping_tax_total = 0;
            }

            $session->set('fc_calculated_shipping_cost', $amount);
            return;
        }
        $session->set('fc_calculated_shipping_cost', (float) $amount);
        if ($fcClassicMode) {
            $cart->add_fee("Shipping", (float) $amount);
        }
    }
}
