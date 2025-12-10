<div class="d-flex justify-content-between align-items-center" style="padding: 30px 30px 0px 30px;">
    <div>
        <h3 class="form-title m-0"><?php echo esc_html($page_title) ?></h3>
        <?php
        if (isset($sub_page_title)) {
        ?>
            <p><?php echo esc_html($sub_page_title) ?></p>
        <?php
        }
        ?>
    </div>
    <?php
    if ($isFullPage && $page_title != 'Login') {
    ?>
        <a href="<?php echo esc_url($_SERVER['HTTP_REFERER']) ?>" class="btn btn-primary">Back</a>
    <?php
    }
    ?>
</div>