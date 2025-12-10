<?php

use FastCourier\FastCourierManagePackages;
// shipping boxes
$shipping_boxes = FastCourierManagePackages::index();

?>

<!-- start listing modal -->
<div class="modal" id="modalShippingBoxes" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dailog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="order-modal-title">Shared Shipping box</h3> <button class="btn btn-primary mr-2 pull-right" onclick="toggleAddShippingBoxModal()">Add Shipping Box</button>
            </div>
            <div class="modal-body" style="max-height: 45vh; overflow-y: auto;">
                <form class="mt-3" method='post' action="">
                    <table class="table fc-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Length</th>
                                <th>Width</th>
                                <th>Height</th>
                                <th>Default</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="packages">
                            <?php
                            foreach ($shipping_boxes as $key => $pack) {
                            ?>
                                <tr>
                                    <td><?php echo esc_html($pack['id']) ?></td>
                                    <td><?php echo esc_attr($pack['package_name']) ?></td>
                                    <td><?php echo esc_attr($pack['package_type']) ?></td>
                                    <td><?php echo esc_attr($pack['outside_l']) ?></td>
                                    <td><?php echo esc_attr($pack['outside_w']) ?></td>
                                    <td><?php echo esc_attr($pack['outside_h']) ?></td>
                                    <td><?php echo esc_attr($pack['is_default']) ? 'Default' : '' ?></td>
                                    <td>
                                        <!-- <a class='btn text-info bg-transparent p-0' type='button' onClick='toggleEditModal(this, <?php //echo esc_html($pack['id']) 
                                                                                                                                        ?>)' href="#">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </a> -->
                                        <button class='btn text-danger bg-transparent p-0 ms-3' type='button' onClick='toggleDeletionShippingBox(this, <?php echo $pack['id'] ? esc_html($pack['id']) : null ?>)'>
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
                    </table>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-dismiss="modal" onclick="toggleShippingBoxModal();">Close</button>
            </div>
        </div>
    </div>
</div>
<!-- end listing modal -->

<!-- start add shipping boxes modal -->
<div class="modal" id="modalAddShippingBoxes">
    <div class="modal-dailog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="order-modal-title">Add Shipping Box</h3>
            </div>
            <div class="modal-body">
                <form action="" name="addShippingBox">
                    <div class="form-group">
                        <label class="required-label w-100" for="package_name">Package Name</label>
                        <input type="text" id="package_name" class="form-control" name="package_name" required />
                    </div>
                    <div class="form-group">
                        <label class="required-label" for="dimensions">Package Types</label>
                        <select class="form-control w-100" name="package_type" id="package_type" required>
                            <?php
                            foreach ($packageTypes as $packageType) {
                            ?>
                                <option value="<?php echo esc_html($packageType['name']) ?>" <?php if (esc_html($packageType['name']) == 'box') { ?> selected <?php } ?>><?php echo esc_html($packageType['name']) ?></option>
                            <?php
                            }
                            ?>
                        </select>
                    </div>

                    <div class="form-group mb-0">
                        <label for="dimensions">Dimensions</label>
                    </div>
                    <div class="row">
                        <div class="col-sm-4">
                            <div class="form-group">
                                <label class="required-label w-100" for="outside_l">Length <small>(CMs)</small></label>
                                <input type="number" min="0" id="outside_l" class="form-control" name="outside_l" required />
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="form-group">
                                <label class="required-label w-100" for="outside_w">Width <small>(CMs)</small></label>
                                <input type="number" min="0" id="outside_w" class="form-control" name="outside_w" required />
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="form-group">
                                <label class="required-label w-100" for="outside_h">Height <small>(CMs)</small></label>
                                <input type="number" min="0" id="outside_h" class="form-control" name="outside_h" required />
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="required-label" class="required-label" for="isDefault">Default</label>
                        <div class="w-100">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" name="is_default" type="radio" id="yesDefault" value="1" required>
                                <label class="form-check-label" for="yesDefault">Yes</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" name="is_default" checked type="radio" id="noDefault" value="0" required>
                                <label class="form-check-label" for="noDefault">No</label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-light" onclick="toggleAddShippingBoxModal()">Close</button>
                <button type="submit" class="btn btn-primary ml-2 addShipping">Submit</button>
            </div>
        </div>
    </div>
</div>

<!-- end add shipping boxes modal -->

<script>
    function toggleShippingBoxModal() {
        $('#modalShippingBoxes').toggleClass('show d-flex align-items-center justify-content-center');
    }

    function toggleAddShippingBoxModal() {
        $('#modalAddShippingBoxes').toggleClass('show d-flex align-items-center justify-content-center');
    }

    function toggleDeletionShippingBox(element, shipping_box_id) {
        Swal.fire({
            title: 'Do you want to delete the selected shipping box?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Delete',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.value) {
                deleteShippingBox(element, shipping_box_id);
            }
        });
    }

    function addShippingBox() {
        var formFields = $("[name='addShippingBox']").find("input");
        if (!validateFormInputs(formFields)) {
            return;
        }

        toggleLoader();
        var ajaxurl = '<?php echo WP_API_URL; ?>';

        const shippingBox = $("[name='addShippingBox']").serialize();
        const params = `action=post_add_shipping_box&${shippingBox}`;

        $.post(ajaxurl, params, function(result) {
            if (result.status == 200) {
                $('form').trigger('reset');

                toggleAddShippingBoxModal();
                toggleShippingBoxModal();

                Swal.fire('', 'Shipping Box Added Successfully', 'success').then(function() {
                    location.reload();
                });
            } else {
                Swal.fire('', result.message, 'error');
            }
            toggleLoader();
        });
    }

    $(document).on('click', '.addShipping', addShippingBox)

    function deleteShippingBox(element, shipping_box_id) {
        toggleLoader();

        var ajaxurl = '<?php echo esc_js(admin_url('admin-ajax.php', 'relative')); ?>';
        var formData = `id=${shipping_box_id}&action=post_delete_packages`;

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
</script>