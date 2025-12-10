<?php
global $fc_name;

$packages = "";
foreach ($packageTypes as $key => $package) {
    $packages .= "<option value={$package['name']}>{$package['name']}</option>";
}
$inititalCustomId = (is_array($result) && count($result) > 0) ? $result[count($result) - 1]['id'] : 0;

?>

<?php if($error) { ?>
    <div id="message" class="updated woocommerce-message" style="border-left-color:red">
	    <p> <?php print_r($error); ?> </p>
    </div>
<?php } ?>
<form method='post' action="">
    <table class="table fc-table">
        <thead>
            <tr>
                <th>Package ID</th>
                <th>Package Name</th>
                <th>Package Type</th>
                <th>Dimensions (CM)</th>
                <th>Default</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="packages">
            <?php
            foreach ($result as $key => $package) {
                $selectedPackage = str_replace(['\'', '\\'], '', $package['package_type']);
            ?>
                <tr>
                    <td><?php echo esc_html($package['id']) ?></td>
                    <td>
                        <input placeholder='Name' value="<?php echo esc_attr($package['package_name']) ?>" type='text' name='package_name[]' class='form-control' required>
                        <input value="<?php echo esc_attr($package['id']) ?>" type='hidden' name='id[]'>
                    </td>
                    <td>
                        <select class='form-control' name='package_type[]' required>
                            <option value=''>Select</option>
                            <?php
                            foreach ($packageTypes as $key => $type) {
                            ?>
                                <option value='<?php echo esc_attr($type['name']) ?>' <?php echo esc_attr($type['name'] == $selectedPackage ? 'selected' : '') ?>><?php echo esc_html($type['name']) ?></option>
                            <?php
                            }
                            ?>
                        </select>
                    </td>
                    <td>
                        <span>
                            L: <input type='number' min='0' value="<?php echo esc_attr($package['outside_l']) ?>" placeholder='l' name='outside_l[]' class='package-dimensions' required>
                        </span>
                        <span>
                            W: <input type='number' min='0' value="<?php echo esc_attr($package['outside_w']) ?>" placeholder='w' name='outside_w[]' class='package-dimensions' required>
                        </span>
                        <span>
                            H: <input type='number' min='0' value="<?php echo esc_attr($package['outside_h']) ?>" placeholder='h' name='outside_h[]' class='package-dimensions' required>
                        </span>
                    </td>
                    <td>
                        <input type="checkbox" class='default' name='is_default' <?php echo esc_attr($package['is_default'] ? "value=" . ($package['id']) . "" : '') ?> <?php echo esc_attr($package['is_default'] ? 'checked' : '') ?> onclick="setDefault(this)" package-key='<?php echo esc_html($package['id']) ?>'>
                    </td>
                    <td>
                        <button class='btn text-danger bg-transparent p-0' type='button' onClick='toggleDeletion(this, <?php echo $package['id'] ? esc_html($package['id']) : null ?>)'>
                            <svg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='currentColor' class='bi bi-trash3-fill' viewBox='0 0 16 16'>
                                <path d='M11 1.5v1h3.5a.5.5 0 0 1 0 1h-.538l-.853 10.66A2 2 0 0 1 11.115 16h-6.23a2 2 0 0 1-1.994-1.84L2.038 3.5H1.5a.5.5 0 0 1 0-1H5v-1A1.5 1.5 0 0 1 6.5 0h3A1.5 1.5 0 0 1 11 1.5Zm-5 0v1h4v-1a.5.5 0 0 0-.5-.5h-3a.5.5 0 0 0-.5.5ZM4.5 5.029l.5 8.5a.5.5 0 1 0 .998-.06l-.5-8.5a.5.5 0 1 0-.998.06Zm6.53-.528a.5.5 0 0 0-.528.47l-.5 8.5a.5.5 0 0 0 .998.058l.5-8.5a.5.5 0 0 0-.47-.528ZM8 4.5a.5.5 0 0 0-.5.5v8.5a.5.5 0 0 0 1 0V5a.5.5 0 0 0-.5-.5Z' />
                            </svg>
                        </button>
                    </td>
                </tr>
            <?php
            }
            ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3">
                    <button name="update_packages" class="btn btn-primary" type='submit'>Update Packages</button>
                </td>
                <td colspan="4" class="text-right">
                    <button class='btn text-primary bg-transparent pull-rights' type='button' onClick='newPackage()'>
                        <svg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='currentColor' class='bi bi-plus-square-fill' viewBox='0 0 16 16'>
                            <path d='M2 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H2zm6.5 4.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3a.5.5 0 0 1 1 0z' />
                        </svg> Add Package Row
                    </button>
                </td>
            </tr>
        </tfoot>
    </table>
</form>

<script>
    function newPackage() {
        var customId = <?php echo esc_html($inititalCustomId) ?> + parseInt(jQuery('.custom').length + 1);
        const packages = "<?php echo html_entity_decode(esc_html($packages)) ?>";
        const html = `<tr class='custom'>
                        <td></td>
                        <td><input placeholder='Name' type='text' name='package_name[]' class='form-control' required>
                        <input value="${customId}" type='hidden' name='id[]'>
                        <input value="true" type='hidden' name='is_custom'>
                        </td>
                        <td>
                            <select class='form-control' name='package_type[]' required>
                                <option value=''>Select</option>
                                ${packages}
                            </select>
                        </td>
                        <td>
                            <span>
                                L: <input type='number' min='0' placeholder='l' name='outside_l[]' class='package-dimensions' required>
                            </span>
                            <span>
                                W: <input type='number' min='0' placeholder='w' name='outside_w[]' class='package-dimensions' required>
                            </span>
                            <span>
                                H: <input type='number' min='0' placeholder='h' name='outside_h[]' class='package-dimensions' required>
                            </span>
                        </td>
                        <td>
                            <input type='checkbox' class='default' name='is_default' onclick='setDefault(this)' package-key='${customId}'>
                        </td>
                        <td>
                            <button class='btn text-danger bg-transparent p-0' type='button' onClick='toggleDeletion(this, null)'>
                                <svg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='currentColor' class='bi bi-trash3-fill' viewBox='0 0 16 16'>
                                    <path d='M11 1.5v1h3.5a.5.5 0 0 1 0 1h-.538l-.853 10.66A2 2 0 0 1 11.115 16h-6.23a2 2 0 0 1-1.994-1.84L2.038 3.5H1.5a.5.5 0 0 1 0-1H5v-1A1.5 1.5 0 0 1 6.5 0h3A1.5 1.5 0 0 1 11 1.5Zm-5 0v1h4v-1a.5.5 0 0 0-.5-.5h-3a.5.5 0 0 0-.5.5ZM4.5 5.029l.5 8.5a.5.5 0 1 0 .998-.06l-.5-8.5a.5.5 0 1 0-.998.06Zm6.53-.528a.5.5 0 0 0-.528.47l-.5 8.5a.5.5 0 0 0 .998.058l.5-8.5a.5.5 0 0 0-.47-.528ZM8 4.5a.5.5 0 0 0-.5.5v8.5a.5.5 0 0 0 1 0V5a.5.5 0 0 0-.5-.5Z' />
                                </svg>
                            </button>
                        </td>
                    </tr>`;
        jQuery("#packages").append(html);
    }

    function toggleDeletion(element, packageId) {
        if (packageId) {
            Swal.fire({
                    title: "Are you sure?",
                    text: "Once deleted, you will not be able to recover this pacakge!",
                    icon: "warning",
                    buttons: true,
                    dangerMode: true,
                })
                .then((willDelete) => {
                    if (willDelete) {
                        deletePackage(element, packageId);
                    }
                });
        } else {
            if (jQuery('#packages > tr').length == 1) {
                Swal.fire("", "At least 1 package is required", "error");
            } else {
                jQuery(element.parentNode.parentNode).remove();
            }
        }
    }

    function deletePackage(element, packageId) {
        toggleLoader();
        var ajaxurl = '<?php echo esc_js(admin_url('admin-ajax.php', 'relative')); ?>';

        var formData = `id=${packageId}&action=post_delete_packages`;

        jQuery.post(ajaxurl, formData, function(response) {
            if (response == 1) {
                jQuery(element.parentNode.parentNode).remove();
                Swal.fire("", "Package deleted", "success");
            } else {
                Swal.fire("", response, "error");
            }

            toggleLoader();
        });

    }

    function setDefault(e) {
        jQuery('.default').removeAttr('value');
        jQuery('.default').removeAttr('checked');
        // alert(jQuery(e).attr('package-key'));
        jQuery(e).attr('value', jQuery(e).attr('package-key'));
        jQuery(e).prop('checked', true);
    }
</script>