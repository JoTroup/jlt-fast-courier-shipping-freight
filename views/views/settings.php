<?php

use FastCourier\FastCourierMenuPage;

if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    include_once('notice.php');
}
global $token;
?>
<div class="container-fluid p-0">
    <div class="row">
        <div class="col-sm-12">
            <?php // include_once('access-token.php') ?>
        </div>

        <?php
        if ($accessToken) {
        ?>
            <?php FastCourierMenuPage::merchantRender(); ?>
        <?php

        }
        ?>
    </div>
</div>