<?php

namespace FastCourier;


/**
 * Creates the submenu item for the plugin.
 *
 * @package Fast Courier
 */

/**
 * Creates the Menu item for the plugin.
 *
 * Registers a new menu and uses the dependency passed into
 * the constructor in order to display the page corresponding to this menu item.
 *
 * @package Fast Courier
 */
class Menu
{
    /**
     * Adds a submenu for this plugin to the 'Tools' menu.
     */
    public function init()
    {
        add_action('admin_menu', array($this, 'add_options_page'));
    }


    public static function menuOptions()
    {
        global $slug, $fc_name, $token;
        $landing = $token ? 'render' : 'loginPage';
        $menus = [
            [
                'page_title' => 'Fast Courier',
                'menu_title' => 'Fast Courier',
                'slug' => $slug,
                'capability' => 'manage_fast_courier',
                'renderer' => array('FastCourier\FastCourierMenuPage', $landing),
                'sub_menus' => [
                    [
                        'page_title' => $fc_name . ' | Configuration',
                        'menu_title' => 'Configuration',
                        'slug' => $slug,
                        'capability' => 'manage_fast_courier',
                        'renderer' => array('FastCourier\FastCourierMenuPage', $landing),
                        'position' => 0,
                        'is_enabled_in_test' => true,
                        'enable_after_configuration_completed' => false,
                    ],
                    [
                        'page_title' => $fc_name . ' | All Orders',
                        'menu_title' => 'All  Orders',
                        'slug' => $slug . '-all-orders',
                        'capability' => 'manage_fast_courier',
                        'renderer' => array('FastCourier\FastCourierOrders', 'allOrders'),
                        'position' => 5,
                        'is_enabled_in_test' => true,
                        'enable_after_configuration_completed' => true,
                    ],
                    [
                        'page_title' => $fc_name . ' | Logout',
                        'menu_title' => 'Logout',
                        'slug' => $slug . '-logout',
                        'capability' => 'manage_fast_courier',
                        'renderer' => array('FastCourier\FastCourierMenuPage', 'logout'),
                        'position' => 11,
                        'is_enabled_in_test' => true,
                        'enable_after_configuration_completed' => false,
                    ],
                ]
            ]
        ];

        return $menus;
    }

    /**
     * Creates the main menu item and calls on the Menu Page object to render
     * the actual contents of the page.
     */
    public static function add_options_page()
    {
        global $token;

        $session = WC()->session;
        $configuration_completed = $session->get('configuration_completed');
        $configurationCompleted = $session->get('configurationCompleted');

        foreach (Self::menuOptions() as $menu) {
            add_menu_page(
                $menu['page_title'],
                $menu['menu_title'],
                $menu['capability'],
                $menu['slug'],
                $menu['renderer'],
                plugins_url('images/favicon-16x16.png', __FILE__)
            );

            if ($token) {
                foreach ($menu['sub_menus'] as $subMenu) {
                    // skip the menu items that are visible after configuration is completed
                    if ($subMenu['enable_after_configuration_completed']) {
                        if (($configuration_completed != null && is_array($configuration_completed) && count($configuration_completed) !== 4) || ($configurationCompleted == 0)) {
                            continue;
                        }
                    }
                    add_submenu_page(
                        $menu['slug'],
                        $subMenu['page_title'],
                        $subMenu['menu_title'],
                        $subMenu['capability'],
                        $subMenu['slug'],
                        $subMenu['renderer'],
                        $subMenu['position']
                    );
                }
            }
        }
    }
}
