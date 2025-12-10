<div class="container">
    <div class="row">
        <div class="w-lg-500px m-auto">
            <div class="login-logo">
                <img src="<?php echo esc_url(plugins_url('images/fast-courier-dark.png', __DIR__)) ?>" alt="">
            </div>
            <?php
            if (!fc_check_is_woocommerce_active()) { ?>
                <div class="w-lg-500px bg-body rounded shadow-sm p-5 mx-auto login-form">
                    <p>You're gonna need WooCommerce to run this plugin!</p>
                </div>
            <?php } else { ?>
                <div class="w-lg-500px bg-body rounded shadow-sm p-5 mx-auto login-form">
                    <form class="form w-100" style="display:none" onsubmit="login(); return false" oninput="handleFormChange()">
                        <div class="form-box-title">
                            <h3 class="text-dark mb-2 h1">Sign In to FastCourier</h3>
                            <div class="text-gray-400 fs-4">New Here?
                                <a href="javascript:void(0)" onClick="toggleWelcomeForms('login')" class="link-primary fw-bolder">Create an Account</a>
                            </div>
                        </div>
                        <div class="fv-row mb-3">
                            <label class="form-label fs-6 fw-bolder text-dark mb-1 required-label" for="loginEmail">Email</label>
                            <input required class="form-control form-control-lg form-control-solid" id="loginEmail" type="text" name="email" autocomplete="off" value="">
                        </div>

                        <div class="fv-row mb-3">
                            <div class="d-flex justify-content-between mb-2">
                                <label class="form-label fw-bolder text-dark fs-6 mb-0 required-label" for="loginPassword">Password</label>
                                <a href="javascript:void(0)" onClick="toggleWelcomeForms('password')" class="link-primary fs-6 fw-bolder">Forgot Password ?</a>
                            </div>
                            <input required class="form-control form-control-lg form-control-solid" id="loginPassword" type="password" name="password" autocomplete="off" value="">
                        </div>

                        <div class="text-center">
                            <button type="button" onclick="login()" id="kt_sign_in_submit" class="btn btn-lg btn-primary w-100">
                                <span class="indicator-label">Continue</span>
                                <span class="indicator-progress">Please wait...
                                    <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                                </span>
                            </button>
                        </div>
                    </form>
                    <div class="text-center">
                        <h3 class="text-dark mb-2 h1">Connect to Fast Courier</h3>
                    </div>
                    <div class="text-center">
                        <button type="button" onclick="connect()" id="kt_sign_up_submit" class="btn btn-lg btn-primary w-100 mt-4">Connect</button>
                    </div>
                </div>

                <div class="w-lg-500px bg-body rounded shadow-sm p-5 mx-auto password-form" style="display:none">
                    <form class="form w-100" onsubmit="login(); return false" oninput="handleFormChange()">
                        <div class="form-box-title">
                            <h3 class="text-dark mb-2 h1">Forgot Password ?</h3>
                            <div class="text-gray-400 fs-4">Enter your email to reset your password.</div>
                        </div>
                        <div class="fv-row mb-3">
                            <label class="form-label fs-6 fw-bolder text-dark mb-1 required-label" for="frgtPassEmail">Email</label>
                            <input required class="form-control form-control-lg form-control-solid" id="frgtPassEmail" type="text" name="email" autocomplete="off">
                        </div>

                        <div class="text-center">
                            <button type="button" onclick="forgotPassword()" id="kt_forget_password_submit" class="btn btn-lg btn-primary">
                                <span class="indicator-label">Submit</span>
                            </button>

                            <button type="button" onClick="toggleWelcomeForms()" id="kt_forget_password_cancel" class="btn btn-lg btn-light-primary">
                                <span class="indicator-label">Cancel</span>
                            </button>
                        </div>
                    </form>
                </div>

                <div class="w-lg-500px bg-body rounded shadow-sm p-5 mx-auto register-form" style="display:none">
                    <form class="form w-100" onsubmit="activateMerchant(); return false" oninput="handleFormChange()">
                        <div class="form-box-title">
                            <h3 class="text-dark mb-1 h1">Create an Account</h3>
                            <div class="text-gray-400 fs-4">Already have an account?
                                <a href="javascript:void(0)" onClick="toggleWelcomeForms('register')" class="link-primary fw-bolder">Sign in here</a>
                            </div>
                        </div>
                        <div class="row fv-row mb-3">
                            <div class="col-xl-6">
                                <label class="form-label fs-6 fw-bolder text-dark mb-1 required-label" for="firstName">First Name</label>
                                <input required class="form-control form-control-lg form-control-solid" id="firstName" type="text" placeholder="" name="firstName" autocomplete="off" value="">
                            </div>
                            <div class="col-xl-6">
                                <label class="form-label fs-6 fw-bolder text-dark mb-1 required-label" for="lastName">Last Name</label>
                                <input required class="form-control form-control-lg form-control-solid" id="lastName" type="text" placeholder="" name="lastName" autocomplete="off" value="">
                            </div>
                        </div>
                        <div class="fv-row mb-3">
                            <label class="form-label fs-6 fw-bolder text-dark mb-1 required-label" for="registerEmail">Email</label>
                            <input required class="form-control form-control-lg form-control-solid" type="email" id="registerEmail" placeholder="" name="email" autocomplete="off" value="">
                        </div>
                        <div class="fv-row mb-3">
                            <label class="form-label fs-6 fw-bolder text-dark mb-1" for="companyName">Company (Optional)</label>
                            <input class="form-control form-control-lg form-control-solid" type="text" id="companyName" placeholder="" name="companyName" autocomplete="off" value="">
                        </div>
                        <div class="mb-10 fv-row" data-kt-password-meter="true">
                            <div class="mb-1">
                                <label class="form-label fs-6 fw-bolder text-dark mb-1 required-label" for="registerPassword">Password</label>
                                <div class="position-relative mb-3">
                                    <input required class="form-control form-control-lg form-control-solid" id="registerPassword" type="password" placeholder="" name="password" autocomplete="off" value="">
                                </div>
                            </div>
                        </div>
                        <div class="fv-row mb-3">
                            <label class="form-label fs-6 fw-bolder text-dark mb-1 required-label" for="confirmPassword">Confirm Password</label>
                            <input required class="form-control form-control-lg form-control-solid" id="confirmPassword" type="password" placeholder="" name="confirmPassword" autocomplete="off">
                        </div>
                        <div class="text-center">
                            <button type="button" onclick="registration()" id="kt_sign_up_submit" class="btn btn-lg btn-primary w-100">Submit</button>
                        </div>
                    </form>

                </div>
            <?php } ?>
        </div>
    </div>
</div>


<?php
$portal_url = is_test_mode_active() ? $GLOBALS['api_origin'] : $GLOBALS['prod_api_origin'];


function fetch_and_store_fastcourier_credentials($portal_url)
{
    global $wpdb, $fc_options;

    // Set up API endpoint and payload
    $api_url = $portal_url . 'api/create-client';
    $body = [
        'name' => site_url('/'),
        'redirect_uri' => site_url('/?rest_route=/fastcourier/oauth-callback')
    ];

    // Send API request to fetch client_id and client_secret
    $response = wp_remote_post($api_url, [
        'body' => json_encode($body),
        'headers' => [
            'Content-Type' => 'application/json'
        ]
    ]);

    // Check for errors in API response
    if (is_wp_error($response)) {
        error_log('API Request failed: ' . $response->get_error_message());
        return;
    }

    $data = json_decode(wp_remote_retrieve_body($response), true);

    // Check if response contains client_id and client_secret
    if (!isset($data['client_id']) || !isset($data['client_secret'])) {
        error_log('Missing client_id or client_secret in API response.');
        return;
    }

    // Prepare data for insertion
    $client_id = sanitize_text_field($data['client_id']);
    $client_secret = sanitize_text_field($data['client_secret']);

    // Define custom table (adjust table name as needed)
    // Insert or update credentials in the custom table
    $wpdb->replace($fc_options, [
        'option_name' => "client_id",
        'option_value' => $client_id,
        'autoload' => 'yes'
    ]);
    $wpdb->replace($fc_options, [
        'option_name' => "client_secret",
        'option_value' => $client_secret,
        'autoload' => 'yes'
    ]);
}
fetch_and_store_fastcourier_credentials($portal_url);
?>

<script>
    function toggleWelcomeForms(type) {
        if (type == 'login') {
            jQuery('.login-form').fadeOut();
            jQuery('.register-form').fadeIn();
            jQuery('.password-form').fadeOut();
        } else if (type == 'password') {
            jQuery('.login-form').fadeOut();
            jQuery('.password-form').fadeIn();
            jQuery('.register-form').fadeOut();
        } else {
            jQuery('.register-form').fadeOut();
            jQuery('.login-form').fadeIn();
            jQuery('.password-form').fadeOut();
        }
    }

    function validateInputs(form) {
        const formFields = $(`.${form} input`);
        let i = 0;
        let isValid = true;

        while (i < formFields.length) {
            const field = $(formFields[i]);
            if (field.attr('required') && !field.val()) {
                const idOfField = field.attr('id');
                if (!$(`[error-for=${idOfField}]`).length) {
                    $(`[for=${idOfField}]`).append(`<small class='text-danger label-error' error-for=${idOfField}>(Required)</small>`);
                    field.addClass('border-danger');
                }

                isValid = false;
            }
            i++;
        }

        return isValid;
    }

    function login() {
        if (!validateInputs('login-form')) return;

        toggleLoader();
        const ajaxurl = '<?php echo esc_js(admin_url('admin-ajax.php', 'relative')); ?>';
        const formData = jQuery('.login-form > form').serialize();

        jQuery.ajax({
            url: ajaxurl + '?action=merchant_login&' + formData,
            success: function(result) {
                if (result.status == 1) {
                    location.reload();
                    return;
                } else {
                    toggleLoader();

                    Swal.fire("", result.message, "error");
                }
            },
            error: function(err) {
                toggleLoader();
                Swal.fire("", result.message, "error");
            }
        });
    }

    function handleFormChange() {
        const field = $('input:focus');
        const idOfField = field.attr('id');

        if ($(`[error-for=${idOfField}]`).length) {
            $(`[error-for=${idOfField}]`).remove();
            field.removeClass('border-danger');
        }
    }

    function registration() {
        if (!validateInputs('register-form')) return;

        toggleLoader();
        const ajaxurl = '<?php echo esc_js(admin_url('admin-ajax.php', 'relative')); ?>';
        const formData = jQuery('.register-form > form').serialize();

        jQuery.ajax({
            url: ajaxurl + '?action=merchant_register&' + formData,
            success: function(result) {
                if (result.status == 1) {
                    location.reload();
                    return;
                } else {
                    toggleLoader();
                    const errorKeys = Object.keys(result.errors);
                    let errors = '<ul>';
                    let i = 0;

                    while (i < errorKeys.length) {
                        errors += `<li>${result.errors[errorKeys[i]][0]} </li>`;
                        i++;
                    }
                    errors += '</ul>';
                    Swal.fire("", errors, "error");
                }
            },
            error: function(err) {
                toggleLoader();
                Swal.fire("", result.message, "error");
            }
        });
    }

    function forgotPassword() {
        if (!validateInputs('password-form')) return;
        if (!isValidEmail($('#frgtPassEmail').val())) return;

        toggleLoader();
        const ajaxurl = '<?php echo esc_js(admin_url('admin-ajax.php', 'relative')); ?>';
        const formData = jQuery('.password-form > form').serialize();

        jQuery.ajax({
            url: ajaxurl + '?action=forgot_password&' + formData,
            success: function(result) {
                if (result.success) {
                    Swal.fire("", result.message, "success");

                    jQuery('.password-form > form').trigger('reset');
                } else {
                    Swal.fire("", result.message, "error");
                }

                toggleLoader();
                toggleWelcomeForms();
            },
            error: function(err) {
                toggleLoader();
                Swal.fire("", result.message, "error");
            }
        });
    }

    const portalUrl = "<?php echo $portal_url; ?>";

    function connect() {
        const origin = getHomeOrigin();
        const redirect_uri = "<?php echo site_url('/?rest_route=/fastcourier/oauth-callback') ?>";

        // Fetch the latest client credentials via AJAX
        jQuery.ajax({
            url: "<?php echo admin_url('admin-ajax.php'); ?>",
            method: "POST",
            data: {
                action: "get_client_credentials"
            },
            success: function(response) {
                if (response.success) {
                    const client_id = response.data.client_id;
                    const client_secret = response.data.client_secret;

                    console.log(client_id, client_secret); // Ensure you have the latest credentials here

                    // Open the login popup with the latest credentials
                    const newWindow = window.open(
                        `${portalUrl}oauth/redirect?client_id=${client_id}&client_secret=${client_secret}&app=wp&wp_origin=${origin}&redirect_uri=${redirect_uri}`,
                        "popupWindow",
                        "width=7000,height=7000"
                    );

                    // The rest of your code to handle the popup response
                    const checkForURL = setInterval(() => {
                        // Check if the window is closed
                        if (newWindow.closed) {
                            clearInterval(checkForURL);
                            console.log('Window closed');
                            return;
                        }

                        // Try-catch block to handle response
                        try {
                            const response = JSON.parse(newWindow.document.body.innerText);

                            if (response.close) {
                                newWindow.close();
                                clearInterval(checkForURL);
                            }
                            if (response.success) {
                                newWindow.close();
                                clearInterval(checkForURL);
                                document.querySelector(".loader").classList.toggle("active");
                                window.location.reload();
                            }
                        } catch (error) {
                            console.warn('Cannot access the window location:', error);
                        }
                    }, 500);
                }
            },
            error: function(error) {
                console.error("Error fetching client credentials:", error);
            }
        });
    }

    function is_test_mode_active() {
        return <?php echo json_encode(is_test_mode_active()); ?>;
    }

    function getHomeOrigin() {
        return <?php echo json_encode(fc_origin()); ?>;

    }
</script>