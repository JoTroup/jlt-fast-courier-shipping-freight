<?php

namespace FastCourier;

use Exception;
use DVDoug\BoxPacker\Packer;
use DVDoug\BoxPacker\Test\TestBox;  // use your own `Box` implementation
use DVDoug\BoxPacker\Test\TestItem; // use your own `Item` implementation
use DVDoug\BoxPacker\NoBoxesAvailableException;
use FastCourier\FastCourierProducts;

/**
 * Creates the submenu page for the plugin.
 *
 * @package Fast Courier
 */

/**
 * Creates the submenu page for the plugin.
 *
 * Provides the functionality necessary for rendering the page corresponding
 * to the submenu with which this page is associated.
 *
 * @package Fast Courier
 */
class FastCourierMenuPage
{
    public static $prefix = 'fast_courier_';

    public static function layout($file, $vars = [], $isFullPage = false)
    {
        extract($vars);
        $colWidth = $isFullPage ? 12 : 10;

        include_once('views/common/test-mode-header.php');
        echo '<div class="wrap fast-courier"><div class="container-fluid position-relative"><div class="row">';
        echo "<div class='col-sm-12 p-0'>";

        include ('views/common/sidenav.php');
        
        if (isset($header) && $header) include_once('views/common/header.php');
        echo "<div class='fc-wraper'>";
        include_once($file);
        include_once('views/common/loader.php');
        echo '</div></div></div></div></div>';
    }

    /**
     * This function renders the contents of the page associated with the Submenu
     * that invokes the render method. In the context of this plugin, this is the
     * Submenu class.
     */
    public static function render()
    {
        global $wpdb, $token;
        $table = $wpdb->prefix . 'options';

        $accessToken = $token;

        Self::layout('views/settings.php', array('accessToken' => $accessToken, 'page_title' => 'Configurations'));
    }

    public static function fcOrdersList()
    {
        if (isset($_GET['order_id'])) {
            echo "Order";
            exit;
        }
        Self::layout('views/orders.php', array('page_title' => 'Orders'));
        exit;
    }

    public static function merchantRender()
    {
        global $wpdb, $options_prefix;

        $table = $wpdb->prefix . 'options';
        $response = FastCourierRequests::httpGet('couriers');

        $paymentMethodResponse = FastCourierRequests::httpGet('payment_method');

        $data = $wpdb->get_results("SELECT * FROM {$table} WHERE option_name LIKE '%{$options_prefix}%'", ARRAY_A);

        $couriers = $response['data'];
        $paymentMethods = $paymentMethodResponse['data'];
        $formattedData = [];
        foreach ($data as $field) {
            $formattedData[$field['option_name']] = $field['option_value'];
        }

        $categoriesOfGoodsList = FastCourierRequests::httpGet('categories_of_goods');
        $categoriesOfGoods = ($categoriesOfGoodsList) ? $categoriesOfGoodsList['data']['data'] : [];

        include('views/merchant.php');
    }

    public static function packageDataFormatter($data)
    {
        $totalRecords = count($data['package_name']);
        $packages = [];

        $i = 0;
        while ($i < $totalRecords) {
            if (
                !$data['package_name'][$i] &&
                !$data['package_type'][$i] &&
                !$data['l'][$i] &&
                !$data['w'][$i] &&
                !$data['h'][$i]
            ) {
                $i++;
                continue;
            }
            $packages[$i]['id'] = $data['id'][$i] ? $data['id'][$i] : 0;
            $packages[$i]['package_name'] = $data['package_name'][$i];
            $packages[$i]['package_type'] = $data['package_type'][$i];
            $packages[$i]['outside_l'] = $data['outside_l'][$i];
            $packages[$i]['outside_w'] = $data['outside_w'][$i];
            $packages[$i]['outside_h'] = $data['outside_h'][$i];
            $packages[$i]['is_default'] = $data['is_default'] == $packages[$i]['id'] ? 1 : 0;

            if (isset($packages[$i]['is_custom'])) {
                unset($packages[$i]['id']);
            }

            $i++;
        }
        return $packages;
    }

    public static function validatePackages($packages)
    {
        $result = FastCourierProducts::products([]);
        $products = $result['products'];
        $message = '';
        $productIssues = $package_found_for_product_ids = $package_not_available_for_product_ids = [];

        foreach ($packages as $key => $package) {
            foreach ($products as $key => $product) {
                $id = (int) $product->get_id();
                $height = (int) $product->get_meta('fc_height') ?? 0;
                $width = (int) $product->get_meta('fc_width') ?? 0;
                $length = (int) $product->get_meta('fc_length') ?? 0;
                $weight = (float) $product->get_meta('fc_weight') ?? 1;
                $pack_type = $product->get_meta('fc_package_type');
                $name = $product->get_name();
                $pack = ['name' => $name, 'height' => $height, 'width' => $width, 'length' => $length, 'weight' => $weight, 'type' => $pack_type, 'quantity' => 1];

                try {
                    $packer = new Packer();

                    $packer->addBox(new TestBox(
                        wp_json_encode($package),
                        $package['outside_w'],
                        $package['outside_l'],
                        $package['outside_h'],
                        0,
                        $package['outside_w'],
                        $package['outside_l'],
                        $package['outside_h'],
                        50000000
                    ));
                    $packer->addItem(new TestItem(wp_json_encode($pack), $pack['width'], $pack['length'], $pack['height'], $pack['weight'], true), $pack['quantity']);
                    $packedBoxes = $packer->pack();
                    $package_found_for_product_ids[$id] = $name;
                } catch (NoBoxesAvailableException $e) {
                    $package_not_available_for_product_ids[$id] = $name;
                    $productIssues = $name;
                }
            }
        }
        $productIssues = array_diff($package_not_available_for_product_ids, $package_found_for_product_ids);
        if (!empty($productIssues)) {
            $product_name = reset($productIssues);
            $message = 'Unable to save packages, No boxes could be found for some item(s). e.g. ' . $product_name;
        }
        return $message;
    }

    public static function packagesRender($token)
    {
        global $wpdb, $fc_packages_table;

        if (isset($_POST['update_packages'])) {
            unset($_POST['update_packages']);
            $data = fc_sanitize_data($_POST);
            $packages = Self::packageDataFormatter($data);

            foreach ($packages as $package) {
                $wpdb->replace($fc_packages_table, $package);
            }
        }

        $response = FastCourierRequests::httpGet('package_types');
        $responseBody = $response['data'];

        $packageTypes = $responseBody['data'];


        $result = $wpdb->get_results("SELECT * FROM {$fc_packages_table}", ARRAY_A);

        include_once('views/packages.php');
    }

    public static function bulkPackagesRender()
    {
        Self::layout('views/bulk-packages.php', array('page_title' => 'Bulk Packages'));
    }

    public static function about()
    {
        global $token;

        Self::layout('views/about-fc.php', ['page_title' => '', 'accessToken' => $token]);
    }

    public static function loginPage()
    {
        Self::layout('views/login.php', ['page_title' => 'Login', 'header' => false], true);
        // include('views/login.php');
    }

    public static function logout()
    {
        try {
            global $wpdb, $options_prefix, $option_merchant_field, $slug;
            WC()->session->__unset('configuration_completed');
            WC()->session->__unset('configurationCompleted');
            $wpdb->delete($wpdb->options, ['option_name' => $options_prefix . 'access_token']);
            $wpdb->delete($wpdb->options, ['option_name' => $option_merchant_field]);

            header('location: ' . admin_url('admin.php?page=fast-courier'));
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    public static function changePassword()
    {
        Self::layout('views/change-password.php', ['page_title' => 'Change Password', 'header' => true]);
    }
}
