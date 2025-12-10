<?php
global $fc_name;
?>
<div class="notice notice-info sf-notice-nux is-dismissible">
    <span class="sf-icon">
        <img src="<?php echo esc_url(plugins_url('views/images/icon-master.png', __FILE__)) ?>" alt="Storefront" width="250"> </span>
    <div class="notice-content">
        <h2>Thanks for installing <?php echo esc_html($fc_name) ?>, you rock! <img draggable="false" role="img" class="emoji" alt="🤘" src="https://s.w.org/images/core/emoji/13.1.0/svg/1f918.svg"></h2>
        <p>To enable <?php echo esc_html($fc_name) ?> features you need to verify activation token.</p>
        <p> <span class="plugin-card-woocommerce">
                <a href="admin.php?page=fast-courier" class="activate-now" data-originaltext="Activate WooCommerce" data-name="WooCommerce" data-slug="woocommerce" aria-label="Activate WooCommerce">Verify Token</a>
            </span>
        </p>
    </div>
</div>