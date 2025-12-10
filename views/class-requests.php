<?php

namespace FastCourier;

class FastCourierRequests
{
    public static function getApiHeaders($body)
    {
        global $token, $version;

        $args = array(
            'httpversion' => '1.0',
            'timeout' => '10000',
            'headers' => array(
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Origin' => fc_origin(),
                'version' => $version,
            ),
            'body' => $body,
        );

        return $args;
    }

    public static function httpGet($url, $body = []): array
    {
        $endPoint = fc_apis_prefix();

        try {
            $args = Self::getApiHeaders($body);
            $result = wp_remote_get($endPoint . $url, $args);
            $response = wp_remote_retrieve_body($result);
            $result = json_decode($response, true);

            if (isset($result['status']) && $result['status'] == 1) {
                $response = Self::successResponse(200, $result);
            } else {
                $response = Self::failResponse($result['message']);
            }
        } catch (\Exception $e) {
            $response = Self::failResponse($e->getMessage());
        }

        return $response;
    }
    public static function httpConnectGet($url, $token): array
    {
        $endPoint = fc_apis_prefix();
        global   $version;
        try {
            $args =  array(
                'httpversion' => '1.0',
                'timeout' => '100000',
                'headers' => array(
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'Origin' => fc_origin(),
                    'version' => $version,
                ),

            );

            $result = wp_remote_get($endPoint . $url, $args);
            $response = wp_remote_retrieve_body($result);

            $result = json_decode($response, true);

            if (isset($result['status']) && $result['status'] == 1) {
                $response = Self::successResponse(200, $result);
            } else {
                $response = Self::failResponse($result['message']);
            }
        } catch (\Exception $e) {
            $response = Self::failResponse($e->getMessage());
        }

        return $response;
    }

    public static function httpPost($url, $body = []): array
    {
        $endPoint = fc_apis_prefix();

        try {
            $args = Self::getApiHeaders(wp_json_encode($body));
            $result = wp_remote_post($endPoint . $url, $args);
            $response = wp_remote_retrieve_body($result);
            $result = json_decode($response, true);

            if ($url == 'bulk_order_booking') {
                return $result;
            }

            if (isset($result['status']) && $result['status'] == 1) {
                $response = Self::successResponse(200, $result);
            } else {
                $response = Self::failResponse($result['message'], 400, $result['errors']);
            }
        } catch (\Exception $e) {
            $response = Self::failResponse($e->getMessage());
        }

        return $response;
    }
    public static function customHttpPost($url, $body = []): array
    {
        $endPoint = connect_fc_apis_prefix();

        try {
            $args = Self::getApiHeaders(wp_json_encode($body));
            $result = wp_remote_post($endPoint . $url, $args);
            $response = wp_remote_retrieve_body($result);
            $result = json_decode($response, true);

            if (isset($result['status']) && $result['status'] == 1) {
                $response = Self::successResponse(200, $result);
            } else {
                $response = Self::failResponse($result['message'], 400, $result['errors']);
            }
        } catch (\Exception $e) {
            $response = Self::failResponse($e->getMessage());
        }

        return $response;
    }

    public static function failResponse($message = 'Error', $status = 400, $errors = []): array
    {
        return ['status' => $status, 'message' => $message, 'errors' => $errors];
    }

    public static function successResponse($status = 200, $responseBody = []): array
    {
        return ['status' => $status, 'data' => $responseBody];
    }
}
