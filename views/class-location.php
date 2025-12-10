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

class FastCourierLocation
{

    /**
     * Retrieve the locations associated with the domain.
     *
     * @return array The locations associated with the domain.
     */
    public static function index()
    {
        // Retrieve the merchant details from the options
        $merchantDetails = fc_merchant_details();
        // Retrieve the locations for the domain from the API
        $location = FastCourierRequests::httpGet('merchant_domain/locations/' . $merchantDetails['id']);
        // Return the locations data
        return $location['data']['data'];
    }

    /**
     * Retrieve the tags associated with the merchant's location.
     *
     * @return array The tags associated with the merchant's location.
     */
    public static function tags()
    {
        // Retrieve the merchant details from the options
        $merchantDetails = fc_merchant_details();

        // Retrieve the tags from the API using the domain_id
        $tags = FastCourierRequests::httpGet('merchant_location_tags/' . $merchantDetails['id']);
        // Return the tags data
        return $tags['data']['data'];
    }

    /**
     * Assigns location to the products.
     * 
     * @return void
     */
    public static function assignLocation()
    {
        try {
            // Sanitize the 'products' and '$_POST' data
            $products = fc_sanitize_data($_POST['products']);
            $postData = fc_sanitize_data($_POST);

            // Loop through each product
            foreach ($products as $productId) {
                parse_str($productId, $outputArray);

                // Update the meta data of the product
                update_post_meta($outputArray['pId'], "fc_location", $postData['locations']);
                update_post_meta($outputArray['pId'], "fc_location_type", $postData['locationsBy']);
            }

            // Generate a success response
            $response = (FastCourierRequests::successResponse());
        } catch (\Exception $e) {
            // Generate a fail response with the error message
            $response = (FastCourierRequests::failResponse($e->getMessage()));
        }

        // Set the response header
        header('Content-type: application/json');

        // Output the response as JSON
        echo wp_json_encode($response);

        // Exit the script
        exit;
    }

    /**
     * This function handles the AJAX request for adding a new location.
     */
    public static function addLocation()
    {
        try {
            // Retrieve the merchant details from the options
            $merchantDetails = fc_merchant_details();

            // Creating an array with the location data to be inserted
            $location = [
                'location_name' => $_POST['name'],
                'address1' => ($_POST['address1'] && $_POST['address1'] != '') ? $_POST['address1'] : $_POST['street_number'] . ', ' . $_POST['street_name'],
                'street_number' => $_POST['street_number'],
                'street_name' => $_POST['street_name'],
                'building_type' => $_POST['building_type'],
                'email' => $_POST['email'],
                'first_name' => $_POST['first_name'],
                'last_name' => $_POST['last_name'],
                'phone' => $_POST['phone'],
                'postcode' => $_POST['postcode'],
                'state' => $_POST['state'],
                'suburb' => $_POST['suburb'],
                'latitude' => $_POST['latitude'] ?? null,
                'longitude' => $_POST['longitude'] ?? null,
                'time_window' => $_POST['time_window'],
                'tag' => $_POST['tags'] ?? null,
                'free_shipping_postcodes' => ($_POST['free_shipping_postcodes']) ? implode(',', $_POST['free_shipping_postcodes']) : null,
                'is_deleted' => 0,
                'merchant_domain_id' => $merchantDetails['id'],
                'is_default' => $_POST['is_default'],
                'tail_lift' => $_POST['tail_lift'],
                'flat_shipping_postcodes' => $_POST['flatratecodes'] ?? "",
                'flat_rate'       => $_POST['flatrate'] ?? "",
                'is_flat_enable'  => $_POST['is_flat_rate_enabled'] ? 1 : 0,
            ];

            if ($_POST['address2']) {
                $location['address2'] = $_POST['address2'];
            } else {
                $location['address2'] = '';
            }

            // Saving the location data
            $location = FastCourierRequests::httpPost('merchant_domain/locations/add', fc_sanitize_data($location));

            // Checking if the location was saved successfully
            if ($location['status'] == 200 && !empty($location['data'])) {
                $session = WC()->session;
                $configArray = $session->get('configuration_completed', array());
                $configArray['location'] = 1; // Setting session for enabling sidebar
                $session->set('configuration_completed', $configArray);
                echo 1;
                FastCourierVerifyToken::fc_menu_access_update();
            } else if ($location['status'] == 400) {
                $serverErrors = '';
                if (is_array($location['errors'])) {
                    foreach ($location['errors'] ?? [] as $error) {
                        $serverErrors .= $error[0] . " \n ";
                    }
                } else {
                    $serverErrors = $location['errors'] ?? 'Something went wrong';
                }

                // NOTE: In case of missing suburb, state, postcode fields, updated the error message
                $locationFields = ['suburb', 'state', 'postcode', 'latitude', 'longitude'];
                foreach ($locationFields as $word) {
                    if (strpos($serverErrors, $word) !== false) {
                        $serverErrors = "Please select the Suburb, Postcode, State field value from the dropdown";
                        break;
                    }
                }

                echo $serverErrors;
            }
        } catch (\Exception $e) {
            FastCourierRequests::failResponse($e->getMessage());
            echo esc_html($e->getMessage());
        }
        die;
    }

    /**
     * Update the location using AJAX.
     */
    public static function editLocation()
    {
        try {
            // Get the location data from the API
            $location = FastCourierRequests::httpGet('merchant_domain/location/' . sanitize_text_field($_POST['id']));

            // Check if the location exists
            if (empty($location['data']['data'])) {
                echo esc_html("Location not found!");
                die;
            }

            // Retrieve the merchant details from the options
            $merchantDetails = fc_merchant_details();

            // Prepare the location data to be updated
            $updatedLocation = [
                'location_name' => $_POST['name'],
                'first_name' => $_POST['first_name'],
                'last_name' => $_POST['last_name'],
                'email' => $_POST['email'],
                'phone' => $_POST['phone'],
                'address1' => ($_POST['address1'] && $_POST['address1'] != '') ? $_POST['address1'] : $_POST['street_number'] . ', ' . $_POST['street_name'],
                'street_number' => $_POST['street_number'],
                'street_name' => $_POST['street_name'],
                'building_type' => $_POST['building_type'],
                'time_window' => $_POST['time_window'],
                'suburb' => $_POST['suburb'],
                'state' => $_POST['state'],
                'postcode' => $_POST['postcode'],
                'latitude' => $_POST['latitude'] ?? null,
                'longitude' => $_POST['longitude'] ?? null,
                'tag' => $_POST['tags'] ?? null,
                'free_shipping_postcodes' => ($_POST['free_shipping_postcodes']) ? implode(',', $_POST['free_shipping_postcodes']) : null,
                'is_deleted' => 0,
                'merchant_domain_id' => $merchantDetails['id'],
                'is_default' => $_POST['is_default'],
                'tail_lift' => $_POST['tail_lift'],
                'flat_shipping_postcodes' => $_POST['flatratecodes'] ?? "",
                'flat_rate'       => $_POST['flatrate'] ?? "",
                'is_flat_enable'  => $_POST['is_flat_rate_enabled'] ? 1 : 0,
            ];

            // Check if address2 is provided and set it in the location data
            if ($_POST['address2']) {
                $updatedLocation['address2'] = $_POST['address2'];
            } else {
                $updatedLocation['address2'] = '';
            }

            // Update the location data using the API
            $updatedLocation = FastCourierRequests::httpPost('merchant_domain/location/edit/' . sanitize_text_field($_POST['id']), fc_sanitize_data($updatedLocation));

            // Check if the location was successfully updated
            if ($updatedLocation['status'] == 200 && !empty($updatedLocation['data'])) {
                echo 1;
            } else if ($updatedLocation['status'] == 400) {
                $serverErrors = '';
                if (is_array($updatedLocation['errors'])) {
                    foreach ($updatedLocation['errors'] ?? [] as $error) {
                        $serverErrors .= $error[0] . " \n ";
                    }
                } else {
                    $serverErrors = $updatedLocation['errors'] ?? 'Something went wrong';
                }

                // NOTE: In case of missing suburb, state, postcode fields, updated the error message
                $locationFields = ['suburb', 'state', 'postcode', 'latitude', 'longitude'];
                foreach ($locationFields as $word) {
                    if (strpos($serverErrors, $word) !== false) {
                        $serverErrors = "Please select the Suburb, Postcode, State field value from the dropdown";
                        break;
                    }
                }

                echo $serverErrors;
            }
        } catch (\Exception $e) {
            echo esc_html($e->getMessage());
        }
        die;
    }

    /**
     * Deletes a location via AJAX.
     */
    public static function deleteLocation()
    {
        try {
            // Get the location from the API
            $location = FastCourierRequests::httpGet('merchant_domain/location/' . sanitize_text_field($_POST['id']));

            // If location is not found, return error message
            if (!$location['data']['data']) {
                echo esc_html("Location not found!");
                die;
            }

            // Set delete flag in the database for locations
            $deleteLocation = FastCourierRequests::httpPost('merchant_domain/location/delete/' . sanitize_text_field($_POST['id']));

            // If delete is successful, return success code
            if ($deleteLocation['status'] == 200 && !empty($deleteLocation['data'])) {
                echo 1;
            }
        } catch (\Exception $e) {
            // If an exception occurs, return the error message
            echo esc_html($e->getMessage());
        }
        die;
    }

    /**
     * Generates a string of HTML select options based on the given CSV data.
     *
     * @param array $csvData The CSV data to generate the select options from.
     *
     * @return string The generated HTML select options.
     */
    public static function createOptions($csvData)
    {
        // Initialize the select options string
        $selectOptions = '';

        // Loop through each value in the CSV data
        foreach ($csvData as $value) {
            // Concatenate the option HTML string to the select options string
            $selectOptions .= '<option value="' . $value . '" selected>' . $value . '</option>';
        }

        // Return the select options string
        return $selectOptions;
    }

    /**
     * Process the uploaded CSV file and generate a JSON response.
     */
    public static function processCsv()
    {
        try {
            // Check if CSV file is uploaded and has no errors
            if (isset($_FILES["csvFile"]) && $_FILES["csvFile"]["error"] == UPLOAD_ERR_OK) {
                $csvFilePath = $_FILES["csvFile"]["tmp_name"];

                // Read the CSV file into an array, ignoring empty lines
                $csvData = file($csvFilePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

                // Remove the first element (header) from the CSV data
                $header = array_shift($csvData);

                // Generate options for select2 based on the CSV data
                $finalHtml = self::createOptions($csvData);

                // Generate a success response with the final HTML
                $response = (FastCourierRequests::successResponse(200, [$finalHtml]));
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

    /**
     * Retrieves the location for editing and returns the corresponding HTML form.
     *
     * @return void
     */
    public static function getEditLocation()
    {
        try {
            // Retrieve the location data from the server
            $location = FastCourierRequests::httpGet('merchant_domain/location/' . sanitize_text_field($_GET['id']));

            if (empty($location['data']['data'])) {
                // If location data is empty, return error response
                $response = (FastCourierRequests::failResponse('Location not found!'));
            } else {
                // Retrieve the tags
                $tags = Self::tags();

                // Start output buffering
                ob_start();

                // Include the location-edit.php view
                include('views/location-edit.php');

                // Get the form HTML from output buffer and clean the buffer
                $form_html = ob_get_clean();

                // Create success response with form HTML
                $response = (FastCourierRequests::successResponse(200, ['html' => $form_html]));
            }
        } catch (\Exception $e) {
            // If an exception occurs, return error response with the error message
            $response = (FastCourierRequests::failResponse($e->getMessage()));
        }

        // Set the response header to indicate JSON content type
        header('Content-type: application/json');

        // Encode the response as JSON and echo it
        echo wp_json_encode($response);

        // Stop further execution of the script
        exit;
    }
}
