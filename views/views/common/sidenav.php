<?php

use FastCourier\Menu;

global $token, $options_prefix;
$menus = Menu::menuOptions();
$page = $_GET['page'] ?? '';
$resync_time = get_option($options_prefix . 'resync_time');
?>
<div class="col-sm-12 p-0 d-flex align-items-center justify-content-between">
    <div class="side-nav">
        <!-- <div class="logo">
            <img src="<?php echo esc_url(plugins_url('../images/fast-courier-dark.png', __DIR__)) ?>" alt="">
        </div> -->
        <ul class="d-flex align-items-center mb-0 mt-2">
            <?php
            $session = WC()->session;
            $configuration_completed = $session->get('configuration_completed');
            $configurationCompleted = $session->get('configurationCompleted');

            foreach ($menus as $menu) {
            ?>
                <?php
                if ($token) {
                    foreach ($menu['sub_menus'] as $submenu) {
                        if (is_test_mode_active() && $submenu['is_enabled_in_test'] || !is_test_mode_active()) {
                            $dNone = '';
                            if ($submenu['enable_after_configuration_completed']) {
                                if ((is_array($configuration_completed) && count($configuration_completed) !== 4) || ($configurationCompleted == 0)) {
                                    $dNone = 'd-none';
                                }
                            }
                ?>
                            <li onclick="document.querySelector('.loader').classList.add('active');" class="<?php echo esc_html($dNone); echo esc_html(sanitize_text_field($submenu['slug'] == $_GET['page'] ? 'active' : '')); echo esc_html(sanitize_text_field($submenu['enable_after_configuration_completed'] === true) ? ' merchant-only' : ''); if ($submenu['menu_title'] == 'Logout') { echo 'merchant-logout active'; } ?>">
                                <a href="<?php echo esc_url(admin_url('admin.php?page=' . esc_html($submenu['slug'])))  ?>"><?php echo esc_html($submenu['menu_title']) ?></a>
                            </li>
            <?php
                        }
                    }
                }
            }
            ?>
        </ul>
    </div>
</div>

    <?php if ($page === "fast-courier-all-orders"): ?>
        <div class="col-sm-12 p-0 d-flex align-items-center justify-content-end">
            <div class="d-flex align-items-end">
                <?php if ($resync_time): ?>
                    <div>
                        <b>
                            Last Synced:
                        </b>
                        <?php echo $resync_time; ?>
                    </div>
                <?php endif; ?>

                <button class="btn btn-primary mt-2 mx-2" id="reSyncBtn" style="z-index:10">
                    Re-Sync all orders
                </button>
            </div>
        </div>
    <?php endif; ?>

<script>
    jQuery('#reSyncBtn').on('click', function(e) {
        e.preventDefault();

        // Show "Please wait..." and disable the button
        const $button = jQuery(this);
        $button.text('Please wait...').prop('disabled', true);

        // toggleLoader();
        const formData = '{}'

        const ajaxurl = '<?php echo esc_js(admin_url('admin-ajax.php', 'relative')); ?>';

        jQuery.ajax({
            url: ajaxurl + '?action=resync_all_orders',
            data: formData,
            success: function(result) {
                // toggleLoader();
                let _result = getParsedValue(result, {});
                if (_result.success === false) {
                    Swal.fire('', _result.message, 'error');
                } else {
                    Swal.fire('', _result.message, 'success').then(() => {
                        location.reload();
                    });
                }
            },
            error: function() {
                Swal.fire('', 'An error occurred. Please try again.', 'error');
                $button.text('Re-Sync all orders').prop('disabled', false);
            },
            complete: function() {
                // Reset button text and enable it after the AJAX call completes
                $button.text('Re-Sync all orders').prop('disabled', false);
            }
        });
    });

    function getParsedValue(value, defaultValue = {}) {
        try {
            return JSON.parse(value) ?? defaultValue;
        } catch (error) {
            return defaultValue
        }
    }

    function checkTabCompleted() {
        const divs = document.querySelectorAll('.merchant-config-tabs .tab');
        return Array.from(divs).filter(div => div.hasAttribute('tab-completed') && div.getAttribute('tab-completed') == "1").length === 4;
    }

    function showSideNavItems() {
        const navItems = document.querySelectorAll('.side-nav ul li');
        navItems.forEach(item => item.classList.remove('d-none'));
    }

    let intervalCount = 0; // Track how many intervals have passed
    const maxIntervals = 20; // Maximum number of intervals (10 seconds)
    const interval = 500; // Interval in milliseconds (0.5 second)

    // Start the interval
    const checkInterval = setInterval(() => {
        intervalCount++;

        // Check the condition
        if (checkTabCompleted()) {
            showSideNavItems(); // Remove the d-none class
            clearInterval(checkInterval); // Clear the interval
        } else if (intervalCount >= maxIntervals) {
            clearInterval(checkInterval); // Clear the interval
        }
    }, interval);
</script>