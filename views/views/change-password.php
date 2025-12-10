<div class="container-fluid">
    <div class="row">
        <div class="col-sm-5 w-lg-500px bg-body rounded shadow-sm p-5 mx-auto password-form">
            <form class="form w-100" onSubmit="changePassword(); return false" onInput="handleFormInputChange()">
                <div class="fv-row mb-3">
                    <label class="form-label fs-6 fw-bolder text-dark mb-1 required-label" for="password">Password</label>
                    <input required class="form-control form-control-lg form-control-solid" id="password" type="password" name="current_password" autocomplete="off">
                </div>

                <div class="fv-row mb-3">
                    <label class="form-label fs-6 fw-bolder text-dark mb-1 required-label" for="newPassword">New Password</label>
                    <input required class="form-control form-control-lg form-control-solid" id="newPassword" type="password" name="new_password" autocomplete="off">
                </div>

                <div class="fv-row mb-3">
                    <label class="form-label fs-6 fw-bolder text-dark mb-1 required-label" for="confirmPassword">Confirm Password</label>
                    <input required class="form-control form-control-lg form-control-solid" id="confirmPassword" type="password" name="confirm_password" autocomplete="off">
                </div>

                <div class="text-center">
                    <button type="button" onClick="changePassword()" id="kt_sign_in_submit" class="btn btn-lg btn-primary">
                        <span class="indicator-label">Submit</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function changePassword() {
        if (!validateFormsInputs('.password-form > form')) return;
        
        if (jQuery('#newPassword').val() != jQuery('#confirmPassword').val()) {
            Swal.fire('', 'New Password and Confirm Password fields must have same values', 'error');
            
            return;
        }
        toggleLoader();

        const formData = jQuery('.password-form > form').serialize();
        const url = '<?php echo esc_js(admin_url('admin-ajax.php', 'relative')); ?>?action=change_password';

        jQuery.ajax({
            url,
            data: formData,
            success: function(result) {
                toggleLoader();
                if (result.status == 400) {
                    Swal.fire('', result.message, 'error');
                    return;
                }

                Swal.fire('', result.data.message, 'success');

                jQuery('form').trigger('reset');
            },
            error: function(err) {
                console.log(err);
            }
        })
    }
</script>