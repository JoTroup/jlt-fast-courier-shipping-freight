<form method="post" onsubmit="FastCourierVerifyToken(); return false" name='token-verification'>
    <div class="row">
        <div class="col-sm-6">
            <div class="form-group m-0">
                <label for="accessToken">Access Token</label>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <?php
                        if ($accessToken) {
                        ?>
                            <span class="input-group-text">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-patch-check-fill" viewBox="0 0 16 16">
                                    <path d="M10.067.87a2.89 2.89 0 0 0-4.134 0l-.622.638-.89-.011a2.89 2.89 0 0 0-2.924 2.924l.01.89-.636.622a2.89 2.89 0 0 0 0 4.134l.637.622-.011.89a2.89 2.89 0 0 0 2.924 2.924l.89-.01.622.636a2.89 2.89 0 0 0 4.134 0l.622-.637.89.011a2.89 2.89 0 0 0 2.924-2.924l-.01-.89.636-.622a2.89 2.89 0 0 0 0-4.134l-.637-.622.011-.89a2.89 2.89 0 0 0-2.924-2.924l-.89.01-.622-.636zm.287 5.984-3 3a.5.5 0 0 1-.708 0l-1.5-1.5a.5.5 0 1 1 .708-.708L7 8.793l2.646-2.647a.5.5 0 0 1 .708.708z" />
                                </svg>
                            </span>
                        <?php
                        } else {
                        ?>
                            <span class="input-group-text unverified">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-patch-exclamation" viewBox="0 0 16 16">
                                    <path d="M7.001 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0zM7.1 4.995a.905.905 0 1 1 1.8 0l-.35 3.507a.553.553 0 0 1-1.1 0L7.1 4.995z" />
                                    <path d="m10.273 2.513-.921-.944.715-.698.622.637.89-.011a2.89 2.89 0 0 1 2.924 2.924l-.01.89.636.622a2.89 2.89 0 0 1 0 4.134l-.637.622.011.89a2.89 2.89 0 0 1-2.924 2.924l-.89-.01-.622.636a2.89 2.89 0 0 1-4.134 0l-.622-.637-.89.011a2.89 2.89 0 0 1-2.924-2.924l.01-.89-.636-.622a2.89 2.89 0 0 1 0-4.134l.637-.622-.011-.89a2.89 2.89 0 0 1 2.924-2.924l.89.01.622-.636a2.89 2.89 0 0 1 4.134 0l-.715.698a1.89 1.89 0 0 0-2.704 0l-.92.944-1.32-.016a1.89 1.89 0 0 0-1.911 1.912l.016 1.318-.944.921a1.89 1.89 0 0 0 0 2.704l.944.92-.016 1.32a1.89 1.89 0 0 0 1.912 1.911l1.318-.016.921.944a1.89 1.89 0 0 0 2.704 0l.92-.944 1.32.016a1.89 1.89 0 0 0 1.911-1.912l-.016-1.318.944-.921a1.89 1.89 0 0 0 0-2.704l-.944-.92.016-1.32a1.89 1.89 0 0 0-1.912-1.911l-1.318.016z" />
                                </svg>
                            </span>
                        <?php
                        }
                        ?>
                    </div>
                    <input type="text" placeholder="Merchant Access Token" name="access_token" value="<?php echo esc_html(@$accessToken) ?>" id="accessToken" class="form-control" />
                </div>
            </div>
        </div>
        <div class="col-sm-6 d-flex align-items-end position-relative">
            <button type='button' onclick='FastCourierVerifyToken()' class='btn btn-primary pull-left mt-2 mr-2'><?php echo esc_html($accessToken ? 'Reconnect' : 'Connect With Fast Courier') ?></button>
            &nbsp;
            <?php
            if ($accessToken) {
            ?>
                <button type='button' onclick='syncMerchantDetails()' class='btn btn-primary pull-left mt-2 ml-2'>Sync Details</button>
            <?php
            }
            ?>

            <style>
                .config-warning {
                    position: absolute;
                    right: 20px;
                    font-size: 20px;
                    color: red;
                }
            </style>
        </div>
    </div>
</form>
<hr class="mb-0">

<script>
    jQuery('.config-warning').on('mouseover', function() {
        jQuery('.config-errors').fadeIn();
    });

    jQuery('.config-warning').on('mouseleave', function() {
        jQuery('.config-errors').fadeOut();
    });

    function FastCourierVerifyToken() {
        toggleLoader();
        var ajaxurl = '<?php echo esc_js(admin_url('admin-ajax.php', 'relative')); ?>';

        var formData = jQuery("[name='token-verification']").serialize() + '&action=post_verify_token';

        jQuery.post(ajaxurl, formData, function(response) {
            toggleLoader();
            if (response == 1) {
                Swal.fire("", "Token Verified Successfully", "success").then(function() {
                    location.reload();
                });
                return;
            }

            Swal.fire("", response, "error");
        });
    }

    function syncMerchantDetails() {
        toggleLoader();
        const url = "<?php echo WP_API_URL ?>?action=sync_merchant_details";

        $.get(url, {}, function(result) {
            if (result.status == 200) {
                location.reload();
            } else {
                Swal.fire('', result.message, 'error');
            }
            toggleLoader();
        });
    }
</script>