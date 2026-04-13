<?php

namespace FastCourier;

class FastCourierRequests
{
    private static function logDebug($message, $context = []): void
    {
        if (defined('WMSD_DEBUG') && WMSD_DEBUG && function_exists('wc_get_logger')) {
            wc_get_logger()->debug('[fc-requests] ' . $message, ['source' => 'wmsd', 'context' => $context]);
        }
    }

    private static function normalizeLogValue($value)
    {
        if (is_array($value) || is_object($value)) {
            $encoded = wp_json_encode($value);

            if (is_string($encoded)) {
                return strlen($encoded) > 1000 ? substr($encoded, 0, 1000) . '...(truncated)' : $encoded;
            }

            return 'json_encode_failed';
        }

        if (is_string($value)) {
            return strlen($value) > 1000 ? substr($value, 0, 1000) . '...(truncated)' : $value;
        }

        return $value;
    }

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

            if (is_wp_error($result)) {
                Self::logDebug('GET wp_error', [
                    'url' => $url,
                    'error' => $result->get_error_message(),
                ]);

                return Self::failResponse($result->get_error_message());
            }

            $response = wp_remote_retrieve_body($result);
            $result = json_decode($response, true);

            if (!is_array($result)) {
                Self::logDebug('GET invalid_api_response', [
                    'url' => $url,
                    'body' => Self::normalizeLogValue($response),
                ]);

                return Self::failResponse('Invalid API response');
            }

            if (isset($result['status']) && $result['status'] == 1) {
                $response = Self::successResponse(200, $result);
            } else {
                Self::logDebug('GET fail_response', [
                    'url' => $url,
                    'status' => $result['status'] ?? null,
                    'message' => $result['message'] ?? 'Request failed',
                ]);

                $response = Self::failResponse($result['message'] ?? 'Request failed');
            }
        } catch (\Exception $e) {
            Self::logDebug('GET exception', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

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

            if (is_wp_error($result)) {
                Self::logDebug('CONNECT_GET wp_error', [
                    'url' => $url,
                    'error' => $result->get_error_message(),
                ]);

                return Self::failResponse($result->get_error_message());
            }

            $response = wp_remote_retrieve_body($result);

            $result = json_decode($response, true);

            if (!is_array($result)) {
                Self::logDebug('CONNECT_GET invalid_api_response', [
                    'url' => $url,
                    'body' => Self::normalizeLogValue($response),
                ]);

                return Self::failResponse('Invalid API response');
            }

            if (isset($result['status']) && $result['status'] == 1) {
                $response = Self::successResponse(200, $result);
            } else {
                Self::logDebug('CONNECT_GET fail_response', [
                    'url' => $url,
                    'status' => $result['status'] ?? null,
                    'message' => $result['message'] ?? 'Request failed',
                ]);

                $response = Self::failResponse($result['message'] ?? 'Request failed');
            }
        } catch (\Exception $e) {
            Self::logDebug('CONNECT_GET exception', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

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

            if (is_wp_error($result)) {
                Self::logDebug('POST wp_error', [
                    'url' => $url,
                    'error' => $result->get_error_message(),
                ]);

                return Self::failResponse($result->get_error_message());
            }

            $response = wp_remote_retrieve_body($result);
            $result = json_decode($response, true);

            if (!is_array($result)) {
                Self::logDebug('POST invalid_api_response', [
                    'url' => $url,
                    'body' => Self::normalizeLogValue($response),
                ]);

                return Self::failResponse('Invalid API response');
            }

            if ($url == 'bulk_order_booking') {
                return $result;
            }

            if (isset($result['status']) && $result['status'] == 1) {
                $response = Self::successResponse(200, $result);
            } else {
                Self::logDebug('POST fail_response', [
                    'url' => $url,
                    'status' => $result['status'] ?? null,
                    'message' => $result['message'] ?? 'Request failed',
                    'errors' => Self::normalizeLogValue($result['errors'] ?? []),
                ]);

                $response = Self::failResponse($result['message'] ?? 'Request failed', 400, $result['errors'] ?? []);
            }
        } catch (\Exception $e) {
            Self::logDebug('POST exception', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

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

            if (is_wp_error($result)) {
                Self::logDebug('CUSTOM_POST wp_error', [
                    'url' => $url,
                    'error' => $result->get_error_message(),
                ]);

                return Self::failResponse($result->get_error_message());
            }

            $response = wp_remote_retrieve_body($result);
            $result = json_decode($response, true);

            if (!is_array($result)) {
                Self::logDebug('CUSTOM_POST invalid_api_response', [
                    'url' => $url,
                    'body' => Self::normalizeLogValue($response),
                ]);

                return Self::failResponse('Invalid API response');
            }

            if (isset($result['status']) && $result['status'] == 1) {
                $response = Self::successResponse(200, $result);
            } else {
                Self::logDebug('CUSTOM_POST fail_response', [
                    'url' => $url,
                    'status' => $result['status'] ?? null,
                    'message' => $result['message'] ?? 'Request failed',
                    'errors' => Self::normalizeLogValue($result['errors'] ?? []),
                ]);

                $response = Self::failResponse($result['message'] ?? 'Request failed', 400, $result['errors'] ?? []);
            }
        } catch (\Exception $e) {
            Self::logDebug('CUSTOM_POST exception', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

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
