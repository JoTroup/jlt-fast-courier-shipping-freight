<?php

namespace FastCourier;

/**
 * used to verify access token and activate plugin.
 *
 * Provides the functionality necessary for rendering the page corresponding
 * to the submenu with which this page is associated.
 *
 * @package Fast Courier
 */

class FastCourierVerifyToken
{
    public static function verifyAccessToken()
    {
        $apiData = [
            'headers' => [
                'Authorization' => "Bearer " . sanitize_text_field($_POST['access_token']),
                'Origin' => fc_origin(),
            ]
        ];

        try {
            global $wpdb, $options_prefix, $api, $fc_options;
            $response = FastCourierRequests::httpGet('verify');

            $data = $response['data'];

            if ($data['status'] == 1) {

                $wpdb->replace(
                    $fc_options,
                    [
                        'option_name' => $options_prefix . 'access_token', 'option_value' => sanitize_text_field($_POST['access_token']),
                        'autoload' => 'yes'
                    ]
                );

                echo 1;
            } else {
                $wpdb->delete($fc_options, ['option_name' => $options_prefix . "access_token"]);

                echo $data['message'];
            }
        } catch (\Exception $e) {
            echo esc_html($e->getMessage());
        }

        die;
    }

    public static function activeMerchant()
    {
        try {
            $data = fc_sanitize_data($_POST);

            $data['packageType'] = 'bag';
            $data['isInsurancePaidByCustomer'] = $data['isInsurancePaidByCustomer'] ?? 0;
            $data['isAuthorityToLeave'] = isset($data['isAuthorityToLeave']) ? 1 : 0;
            $data['shoppingPreference'] = 'show_price_of_shipping_in_sub_total';

            $response = FastCourierRequests::httpPost('activate', $data);

            if ($response['status'] == 400) {
                echo 0;
                die;
            }

            $session = WC()->session;
            $configSessionArray = $session->get('configuration_completed', array());
            // set session for enable sidebar
            if (isset($data['billingPhone']) && !empty($data['billingPhone'])) {
                $configSessionArray['basic'] = 1;
            }
            if (isset($data['paymentMethod']) && !empty($data['paymentMethod'])) {
                $configSessionArray['payment'] = 1;
            }
            $session->set('configuration_completed', $configSessionArray);

            Self::updateMerchantDetails($data);

            if ($data['automaticOrderProcess'] && $data['automaticOrderProcess'] >= 1) {
                do_action('activate_cron_auto_process_order');
                if (!defined('DISABLE_WP_CRON') || (defined('DISABLE_WP_CRON') && DISABLE_WP_CRON !== false)) {
                    echo 2;
                } else {
                    echo 1;
                }
            } else {
                do_action('deactivate_cron_auto_process_order');
                echo 1;
            }
        } catch (\Exception $e) {
            echo esc_html($e->getMessage());
        }

        die;
    }

    public static function activeMerchantPayment()
    {
        try {
            $data = fc_sanitize_data($_POST);

            $response = FastCourierRequests::httpPost('selectPaymentMethod', $data);

            if ($response['status'] == 400) {
                echo 0;
                die;
            }

            $session = WC()->session;
            $configSessionArray = $session->get('configuration_completed', array());
            // set session for enable sidebar
            if (isset($data['paymentMethod']) && !empty($data['paymentMethod'])) {
                $configSessionArray['payment'] = 1;
            }
            $session->set('configuration_completed', $configSessionArray);

            Self::updateMerchantPaymentMethod($data);

            if ($data['automaticOrderProcess'] && $data['automaticOrderProcess'] >= 1) {
                do_action('activate_cron_auto_process_order');
                if (!defined('DISABLE_WP_CRON') || (defined('DISABLE_WP_CRON') && DISABLE_WP_CRON !== false)) {
                    echo 2;
                } else {
                    echo 1;
                }
            } else {
                do_action('deactivate_cron_auto_process_order');
                echo 1;
            }
            Self::fc_menu_access_update();
        } catch (\Exception $e) {
            echo esc_html($e->getMessage());
        }

        die;
    }

    public static function merchantFiedsFormat($data)
    {
        $formattedData = [];

        foreach ($data as $key => $value) {
            $str = lcfirst(str_replace('_', '', ucwords($key, '_')));

            $formattedData[$str] = $value;
        }


        return $formattedData;
    }

    public static function syncMerchantDetails()
    {
        try {
            $merchantDetails = FastCourierRequests::httpGet('get_merchant');

            Self::updateMerchantDetails($merchantDetails['data']['data']);

            header('Content-type: application/json');
            echo wp_json_encode($merchantDetails);
        } catch (\Exception $e) {
            $e->getMessage();
        }
        exit;
    }

    public static function updateMerchantDetails($data)
    {
        global $wpdb, $fc_options, $option_merchant_field;
        try {
            $merchantData = fc_merchant_details();

            $merchantData = is_array($merchantData) ? $merchantData : [];

            if (isset($merchantData) && isset($merchantData['paymentMethod'])) {
                $data['paymentMethod'] = $merchantData['paymentMethod'];
            }

            $updatedData = array_merge($merchantData, Self::merchantFiedsFormat($data));

            $wpdb->replace($fc_options, ['option_name' => $option_merchant_field, 'option_value' => wp_json_encode($updatedData), 'autoload' => 'yes']);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    public static function updateMerchantPaymentMethod($newPaymentMethod)
    {
        global $wpdb, $fc_options, $option_merchant_field;
        try {
            // Find the merchant details and update the payment method
            $merchantData = fc_merchant_details();
            if (isset($merchantData)) {
                $merchantData['paymentMethod'] = $newPaymentMethod['paymentMethod'];

                // Save the updated data back to the database
                $wpdb->replace($fc_options, [
                    'option_name' => $option_merchant_field,
                    'option_value' => wp_json_encode($merchantData),
                    'autoload' => 'yes'
                ]);
            } else {
                throw new \Exception('Merchant not found');
            }
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    public static function deletePackage()
    {
        try {
            global $fc_packages_table, $wpdb;

            $wpdb->delete($fc_packages_table, ['id' => sanitize_text_field($_POST['id'])]);

            echo 1;
        } catch (\Exception $e) {
            echo esc_html($e->getMessage());
        }

        die;
    }

    public static function deleteWooPackage()
    {
        try {
            global $woo_packages_table, $wpdb;

            $wpdb->delete($woo_packages_table, ['id' => sanitize_text_field($_POST['id'])]);
            echo 1;
        } catch (\Exception $e) {
            echo esc_html($e->getMessage());
        }

        die;
    }

    public static function fc_menu_access_update()
    {
        try {
            $session = WC()->session;
            if (isset($session) && !empty($session)) {
                $configuration_completed = $session->get('configuration_completed', array());
                if (count($configuration_completed) === 4) {

                    $merchantDetails = fc_merchant_details();

                    if (isset($merchantDetails['abn']) && !empty($merchantDetails['abn'])) {
                        $session->set('configurationCompleted', 1);
                    } else {
                        $keysToKeep = [
                            'paymentMethod',
                            'billingFirstName',
                            'billingLastName',
                            'billingCompanyName',
                            'billingPhone',
                            'billingEmail',
                            'abn',
                            'billingAddress1',
                            'billingAddress2',
                            'billingSuburb',
                            'billingState',
                            'billingPostcode',
                            'conditionalPrice',
                            'bookingPreference',
                            'fallbackAmount',
                            'courierPreferences',
                            'insuranceType',
                            'insuranceAmount',
                            'packageType',
                            'isInsurancePaidByCustomer',
                            'shoppingPreference',
                            'isAuthorityToLeave'
                        ];

                        $payload = [];
                        foreach ($keysToKeep as $key) {
                            if (isset($merchantDetails[$key])) {
                                $payload[$key] = $merchantDetails[$key];
                            }
                        }

                        FastCourierRequests::httpPost('activate', $payload);

                        Self::updateMerchantDetails($merchantDetails);

                        $session->set('configurationCompleted', 1);
                    }
                }
            }
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }
}
