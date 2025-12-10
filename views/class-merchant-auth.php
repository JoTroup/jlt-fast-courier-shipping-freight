<?php

namespace FastCourier;

use Exception;

class FCMerchantAuth
{
    public static function merchantLogin($token)
    {
        header('Content-type: application/json');
        try {
            global $wpdb, $fc_options, $options_prefix;
            
            $response = FastCourierRequests::httpConnectGet('get_merchant', $token);
            $paymentMethodResponse = FastCourierRequests::httpConnectGet('payment_method',$token);

            $paymentMethods = $paymentMethodResponse['data']['data'] ?? [];
           
            if ($response['status'] == 400) {
                echo json_encode($response);
                exit;
            }
            // set this session to display the hidden side menus
            $configurationCompleted = (isset($response['data']['data']['abn']) &&$response['data']['data']['abn']!= '' &&  !empty($paymentMethods)) ? 1 : 0;
            WC()->session->set('configurationCompleted', $configurationCompleted);
           
            $wpdb->replace(
                $fc_options,
                [
                    'option_name' => $options_prefix . 'access_token', 'option_value' => sanitize_text_field($response['data']['data']['access_token']),
                    'autoload' => 'yes'
                ]
            );

            FastCourierVerifyToken::updateMerchantDetails($response['data']['data']);
        } catch (\Exception $e) {
            echo 0;
        }
        
    }

    public static function merchantRegistration()
    {
        header('Content-type: application/json');
        try {
            global $wpdb, $fc_options, $options_prefix;
            $response = FastCourierRequests::httpPost('signup', fc_sanitize_data($_GET));
            if ($response['status'] == 400) {
                echo json_encode($response);
                exit;
            }

            $wpdb->replace(
                $fc_options,
                [
                    'option_name' => $options_prefix . 'access_token', 'option_value' => sanitize_text_field($response['data']['merchant']['access_token']),
                    'autoload' => 'yes'
                ]
            );
            // hide the menu links that will be visible after configuration is completed
            WC()->session->set('configurationCompleted', 0);

            FastCourierVerifyToken::updateMerchantDetails($response['data']['merchant']);
            echo json_encode($response['data']);
            exit;
        } catch (\Exception $e) {
            echo $e->getMessage();
            echo 0;
        }
        exit;
    }

    public static function addPaymentMethod()
    {
        header('Content-type: application/json');
        try {
            $response = FastCourierRequests::httpPost('savePaymentMethod', fc_sanitize_data($_GET));

            if ($response['status'] == 400) {
                echo json_encode(['success' => false, 'message' => $response['message']]);
                exit;
            }
            echo json_encode(['success' => true, 'message' => 'Payment Method Added']);
        } catch (Exception $e) {
            echo 0;
        }
        exit;
    }

    public static function toggleTestMode()
    {
        header('Content-type: application/json');
        $response = ['success' => false, 'message' => 'Server Error'];
        try {
            global $wpdb, $test_key, $options_prefix, $option_merchant_field;

            $isTestMode = get_option($test_key, false);

            if (!$isTestMode) {
                $isTestMode = true;
            } else {
                $isTestMode = false;
            }

            WC()->session->__unset('configuration_completed');
            WC()->session->__unset('configurationCompleted');

            $result = $wpdb->replace($wpdb->options, ['option_value' => $isTestMode, 'option_name' => $test_key, 'autoload' => 'yes']);

            if ($result) {
                $wpdb->delete($wpdb->options, ['option_name' => $options_prefix . 'access_token']);
                $wpdb->delete($wpdb->options, ['option_name' => $option_merchant_field]);
                $response = ['success' => true, 'message' => 'Mode Changed'];
            }
        } catch (Exception $e) {
            $response['message'] = $e->getMessage();
        }

        echo json_encode($response);
        exit;
    }

    public static function removePaymentMethod()
    {
        $data = fc_sanitize_data($_GET);
        header('Content-type: application/json');
        $response = ['success' => false, 'message' => 'Server Error'];
        try {
            $card_id = $data['card_id'];

            $result = FastCourierRequests::httpPost("delete_payment_method/{$card_id}", []);

            if ($result['status'] == 400) {
                echo json_encode(['success' => false, 'message' => $result['message']]);
                exit;
            }
            echo json_encode(['success' => true, 'message' => 'Payment Method removed successfully']);
            exit;
        } catch (Exception $e) {
            $response = $e->getMessage();
        }

        echo json_encode($response);
        exit;
    }

    public static function forgotPassword()
    {
        $data = fc_sanitize_data($_GET);
        header('Content-type: application/json');
        $response = ['success' => false, 'message' => 'Server Error'];
        try {
            $data['origin'] = fc_origin();

            $result = FastCourierRequests::httpPost("forgot_password", $data);

            if ($result['status'] == 400) {
                echo json_encode(['success' => false, 'message' => $result['message']]);
                exit;
            }
            echo json_encode(['success' => true, 'message' => $result['data']['message']]);
            exit;
        } catch (Exception $e) {
            $response = $e->getMessage();
        }

        echo json_encode($response);
        exit;
    }

    public static function changePassword()
    {
        $data = fc_sanitize_data($_GET);
        $response = ['success' => false, 'message' => 'Server Error'];

        header('Content-type: application/json');
        try {
            $result = FastCourierRequests::httpPost('change_password', $data);

            $response = $result;
        } catch (Exception $e) {
            $response['message'] = $e->getMessage();
        }

        echo json_encode($response);
        exit;
    }
}
