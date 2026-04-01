<?php

use FastCourier\FastCourierMenuPage;

if (!fc_is_woocommerce_active()) {
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