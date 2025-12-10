<?php
$inititalCustomId = (is_array($locationResult) && count($locationResult) > 0) ? $locationResult[count($locationResult) - 1]['id'] : 0;
$firstRecord = true;
?>

<div class="mt-3">
    <div class="row">
        <div class="col-sm-6">
        </div>
        <div class="col-sm-6">
            <button class="btn btn-primary mr-2 pull-right" onclick="toggleAddLocationModal()">Add New Location</button>
        </div>
    </div>
    <form class="mt-3" method='post' action="">
        <table class="table fc-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Suburb, Postcode, State</th>
                    <th>Tags</th>
                    <th>Free Shipping Postcodes</th>
                    <th>Flat rate details</th>
                    <th>Default</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="packages">
                <?php
                if (is_array($locationResult) && count($locationResult) > 0) {
                    foreach ($locationResult as $key => $locations) {
                ?>
                        <tr>
                            <td><?php echo esc_html($locations['id']) ?></td>
                            <td><?php echo esc_attr($locations['location_name']) ?></td>
                            <td><?php echo esc_attr($locations['phone']) ?></td>
                            <td><?php echo esc_attr($locations['email']) ?></td>
                            <td><?php echo esc_attr($locations['suburb']) ?>, <?php echo esc_attr($locations['postcode']) ?> (<?php echo esc_attr($locations['state']) ?>)</td>
                            <?php
                            $tag_names = null;
                            if ($locations['tag']) {
                                $locationTags = explode(',', $locations['tag']);
                                foreach ($locationTags as $location_tag) {
                                    foreach ($available_tags as $available_tag) {
                                        if ($location_tag == $available_tag['id']) {
                                            if ($tag_names) {
                                                $tag_names .= ', ';
                                            }
                                            $tag_names .= $available_tag['name'];
                                        }
                                    }
                                }
                            } else {
                                $tag_names = '-';
                            }
                            ?>
                            <td><?php echo $tag_names ?></td>
                            <?php
                            $total_free_postcodes = 0;
                            if ($locations['free_shipping_postcodes']) {
                                $postcodes = explode(',', $locations['free_shipping_postcodes']);
                                $total_free_postcodes = count($postcodes);
                            }
                            ?>
                            <td class="<?php if ($total_free_postcodes > 0) { ?> position-relative processed-order <?php } ?>" title="<?php echo esc_html($locations['free_shipping_postcodes']) ?>"><?php echo esc_html($total_free_postcodes) ?> </td>
                            <td>
                                <?php if (esc_attr($locations['is_flat_enable']) == 1): ?>
                                    <div class="d-flex align-items-center tooltip-hover w-fit-content"
                                        data-tooltip="Post codes (or ranges): <?php echo esc_attr($locations['flat_shipping_postcodes']) ?>">
                                        $<?php echo esc_attr($locations['flat_rate']) ?>

                                        <i class="fas fa-info-circle info-icon ml-1"> </i>
                                    </div>
                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_attr($locations['is_default']) ? 'Default' : '' ?></td>
                            <td>
                                <a class='btn text-info bg-transparent p-0' type='button' onClick='toggleEditModal(this, <?php echo esc_html($locations['id']) ?>)' href="#">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>

                                <?php if (esc_attr($locations['is_default'])) {
                                    $firstRecord = false; ?>
                                    <span class='text-secondary bg-transparent p-0 ms-3'>
                                        <svg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='currentColor' class='bi bi-trash3-fill' viewBox='0 0 16 16'>
                                            <path d='M11 1.5v1h3.5a.5.5 0 0 1 0 1h-.538l-.853 10.66A2 2 0 0 1 11.115 16h-6.23a2 2 0 0 1-1.994-1.84L2.038 3.5H1.5a.5.5 0 0 1 0-1H5v-1A1.5 1.5 0 0 1 6.5 0h3A1.5 1.5 0 0 1 11 1.5Zm-5 0v1h4v-1a.5.5 0 0 0-.5-.5h-3a.5.5 0 0 0-.5.5ZM4.5 5.029l.5 8.5a.5.5 0 1 0 .998-.06l-.5-8.5a.5.5 0 1 0-.998.06Zm6.53-.528a.5.5 0 0 0-.528.47l-.5 8.5a.5.5 0 0 0 .998.058l.5-8.5a.5.5 0 0 0-.47-.528ZM8 4.5a.5.5 0 0 0-.5.5v8.5a.5.5 0 0 0 1 0V5a.5.5 0 0 0-.5-.5Z' />
                                        </svg>
                                    </span>
                                <?php } else { ?>
                                    <button class='btn text-danger bg-transparent p-0 ms-3' type='button' onClick='toggleDeletion(this, <?php echo $locations['id'] ? esc_html($locations['id']) : null ?>)'>
                                        <svg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='currentColor' class='bi bi-trash3-fill' viewBox='0 0 16 16'>
                                            <path d='M11 1.5v1h3.5a.5.5 0 0 1 0 1h-.538l-.853 10.66A2 2 0 0 1 11.115 16h-6.23a2 2 0 0 1-1.994-1.84L2.038 3.5H1.5a.5.5 0 0 1 0-1H5v-1A1.5 1.5 0 0 1 6.5 0h3A1.5 1.5 0 0 1 11 1.5Zm-5 0v1h4v-1a.5.5 0 0 0-.5-.5h-3a.5.5 0 0 0-.5.5ZM4.5 5.029l.5 8.5a.5.5 0 1 0 .998-.06l-.5-8.5a.5.5 0 1 0-.998.06Zm6.53-.528a.5.5 0 0 0-.528.47l-.5 8.5a.5.5 0 0 0 .998.058l.5-8.5a.5.5 0 0 0-.47-.528ZM8 4.5a.5.5 0 0 0-.5.5v8.5a.5.5 0 0 0 1 0V5a.5.5 0 0 0-.5-.5Z' />
                                        </svg>
                                    </button>
                                <?php } ?>
                            </td>
                        </tr>
                <?php
                    }
                }
                ?>
            </tbody>
        </table>
    </form>
</div>
<!-- Start of Add Location Modal -->
<div class="modal" id="modalAddLocation">
    <div class="modal-dailog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="order-modal-title">Pick Up Location</h3>
            </div>
            <div class="modal-body" style="max-height:60vh;overflow-y:auto">
                <?php include_once('location-add.php'); ?>
            </div>
            <div class="modal-footer">
                <button class="btn btn-light" onclick="toggleAddLocationModal()">Close</button>
                <button class="btn btn-primary ml-2" onclick="addNewLocation()">Submit</button>
            </div>
        </div>
    </div>
</div>
<!-- End of Add Location Modal -->

<!-- Start of Edit Location Modal -->
<div class="modal" id="modalEditLocation">
    <div class="modal-dailog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="order-modal-title">Edit Location</h3>
            </div>
            <div class="modal-body edit-location-modal-body" style="max-height:60vh;overflow-y:auto">
            </div>
            <div class="modal-footer">
                <button class="btn btn-light" onclick="toggleEditLocationModal()">Close</button>
                <button class="btn btn-primary ml-2" onclick="editLocation()">Submit</button>
            </div>
        </div>
    </div>
</div>
<!-- End of Edit Location Modal -->
<?php
global $token;
?>

<script>
    // Update Location
    function editLocation() {
        var validateInputs = new Promise(function(resolve, reject) {
            var formFields = $("[name='location-edit-form']").find("input, textarea").filter(":visible");;
            resolve(validateFormInputs(formFields));
        });

        validateInputs.then(function(isValidated) {
            if (isValidated) {
                toggleLoader();
                var ajaxurl = '<?php echo esc_js(admin_url('admin-ajax.php', 'relative')); ?>';

                var formData = $("[name='location-edit-form']").serialize() + '&action=edit_location';

                $.post(ajaxurl, formData, function(response) {
                    if (response == '1') {
                        Swal.fire({
                            title: 'Location Updated Successfully',
                            icon: 'success',
                            theme: 'success',
                            showDenyButton: true,
                            confirmButtonText: 'Ok',
                            dangerMode: true,
                        }).then(function() {
                            toggleEditLocationModal();
                            location.reload();
                        });

                    } else {
                        Swal.fire({
                            title: 'Warning',
                            text: response,
                            icon: 'error',
                            theme: 'error',
                            showDenyButton: true,
                            confirmButtonText: 'Ok',
                            dangerMode: true,
                        });
                        toggleLoader();
                    }
                });
            } else {
                try {
                    $('html, body').animate({
                        scrollTop: $('.text-danger').offset().top - 100
                    })
                } catch (error) {

                }

            }
        }).catch(function(error) {
            console.error(error);
        });

    }

    function toggleEditModal(element, locationId) {
        toggleLoader();

        const url = "<?php echo WP_API_URL ?>?action=get_edit_locaton";
        let data = `id=${locationId}`

        $.get(url, data, function(result) {
            if (result.status == 200) {
                $('.edit-location-modal-body').html(result.data.html);
                $('#modalEditLocation').toggleClass('show d-flex align-items-center justify-content-center');
            } else {
                Swal.fire('', result.message, 'error');
            }
            toggleLoader();
        });
    }

    function toggleDeletion(element, locationId) {
        Swal.fire({
            title: 'Do you want to delete the location?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Delete',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.value) {
                deleteLocation(element, locationId);
            }
        });
    }

    function deleteLocation(element, locationId) {
        toggleLoader();
        var ajaxurl = '<?php echo esc_js(admin_url('admin-ajax.php', 'relative')); ?>';

        var formData = `id=${locationId}&action=delete_location`;

        jQuery.post(ajaxurl, formData, function(response) {
            if (response == 1) {
                jQuery(element.parentNode.parentNode).remove();
                Swal.fire("", "Location deleted", "success");
                location.reload();
            } else {
                Swal.fire("", response, "error");
            }

            toggleLoader();
        });

    }

    function toggleAddLocationModal() {
        $('.suburbs-selection').val(null).trigger('change');
        $('#modalAddLocation').toggleClass('show d-flex align-items-center justify-content-center');
    }

    function toggleEditLocationModal() {
        $('#modalEditLocation').toggleClass('show d-flex align-items-center justify-content-center');
    }

    $(document).on('keyup', 'input', function() {
        var field = $(this);
        var idOfField = field.attr('id');
        if ($(`[error-for=${idOfField}]`).length) {
            $(`[error-for=${idOfField}]`).remove();
            field.removeClass('border-danger');
        }
    });

    function validateFormInputs(formFields) {
        let i = 0;
        let isValid = true;

        while (i < formFields.length) {
            const field = $(formFields[i]);
            if (field.attr('required') && !field.val()) {

                const idOfField = field.attr('id');
                if (!$(`[error-for=${idOfField}]`).length) {
                    $(`[for=${idOfField}]`).after(`<small class='text-danger' error-for=${idOfField}>(Required)</small>`);
                    field.addClass('border-danger');
                }
                isValid = false;
            } else if (field.attr('id') == 'email' && field.val()) {
                var emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
                if (!emailRegex.test(field.val())) {
                    isValid = false;
                    if (!$(`[error-for="email"]`).length) {
                        $(`[for="email"]`).after(`<small class='text-danger' error-for='email'>(Invalid Email)</small>`);
                        $('input[name="email"]').addClass('border-danger');
                    }
                }
            }

            if (field.attr('limit')) {
                if (field.attr('limit') < field.val().length) {
                    isValid = false;
                    const idOfField = field.attr('id');
                    if (!$(`[error-for=${idOfField}]`).length) {
                        $(`[for=${idOfField}]`).after(`<small class='text-danger' error-for=${idOfField}>(Max. ${field.attr('limit')} characters)</small>`);
                        field.addClass('border-danger');
                    }
                }
            }
            // validation of min length of value (e.g. Phone number must be 8 numbers)
            if (field.attr('min-length')) {
                let currentValue = field.val();
                // Remove any non-digit characters (e.g., spaces, dashes, etc.)
                currentValue = currentValue.replace(/\D/g, '');
                if (currentValue.length < 8 && !isNaN(currentValue)) {
                    isValid = false;
                    const idOfField = field.attr('id');
                    if (!$(`[error-for=${idOfField}]`).length) {
                        $(`[for=${idOfField}]`).after(`<small class='text-danger' error-for=${idOfField}>(Value must be Min. ${field.attr('min-length')} Numbers)</small>`);
                        field.addClass('border-danger');
                    }
                }
            }

            i++;
        }

        return isValid;
    }

    function addNewLocation() {
        var validateInputs = new Promise(function(resolve, reject) {
            var formFields = $("[name='locationForm']").find("input, textarea").filter(":visible");;
            resolve(validateFormInputs(formFields));
        });

        validateInputs.then(function(isValidated) {
            if (isValidated) {
                toggleLoader();

                var ajaxurl = '<?php echo esc_js(admin_url('admin-ajax.php', 'relative')); ?>';

                var formData = $("[name='locationForm']").serialize() + '&action=add_location';

                $.post(ajaxurl, formData, function(response) {

                    if (response == '1') {
                        Swal.fire({
                            title: 'Location Added Successfully',
                            icon: 'success',
                            theme: 'success',
                            showDenyButton: true,
                            confirmButtonText: 'Ok',
                            dangerMode: true,
                        }).then((result) => {
                            $('#modalAddLocation').toggleClass('show d-flex align-items-center justify-content-center');
                            localStorage.setItem('selectedTabHref', "#product-mapping");
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            title: 'Warning',
                            text: response,
                            icon: 'error',
                            theme: 'error',
                            showDenyButton: true,
                            confirmButtonText: 'Ok',
                            dangerMode: true,
                        });
                        toggleLoader();
                    }
                });
            } else {
                try {
                    $('html, body').animate({
                        scrollTop: $('.text-danger').offset().top - 100
                    })
                } catch (err) {

                }

            }

        }).catch(function(error) {
            console.error(error);
        });
    }

    function formatSuburbs(subsurbs) {
        if (subsurbs.loading) {
            return subsurbs.text;
        }

        var $container = $(`<div class="select2-user-result">${subsurbs.name || ''}, ${subsurbs.state || ''} (${subsurbs.postcode || ''})</div>`);

        return $container;
    }

    function suburbsSelection(suburb) {
        return suburb.name ? `${suburb.name}, ${suburb.postcode} (${suburb.state})` : '';
    }

    $('.tags-input').select2({
        tags: true,
        tokenSeparators: [',', ' '],
        placeholder: 'Select or add tags'
    });

    $('.postcode-tags-input').select2({
        tags: true,
        tokenSeparators: [',', ' '],
        placeholder: 'Select or add postcode'
    });

    // process postcodes CSV and return the postcodes HTML
    function processCsv(file) {
        var deferred = $.Deferred();

        var formData = new FormData();
        formData.append("csvFile", file);

        const Url = '<?php echo esc_js(admin_url('admin-ajax.php', 'relative')); ?>?action=process_csv';
        // AJAX request
        $.ajax({
            url: Url,
            type: "POST",
            data: formData,
            contentType: false,
            processData: false,
            success: function(response) {
                deferred.resolve(response); // Resolve the promise with the AJAX response
            },
            error: function(xhr, status, error) {
                deferred.reject(error); // Reject the promise in case of an error
            }
        });

        return deferred.promise();
    }
    // get the uploaded csv file and send it for process
    function uploadCSV(file) {
        var deferred = $.Deferred();

        // process the uploaded CSV
        processCsv(file)
            .done(function(response) {
                deferred.resolve(response); // Resolve the processCsv promise with the response
            })
            .fail(function(error) {
                deferred.reject(error); // Handle any errors
            });

        return deferred.promise();
    }


    // for upload and process the free postcode CSV file add location
    $(document).on("change", "#modalAddLocation #csvFile", function(e) {
        toggleLoader();

        let fileInput = $(this)[0];
        var file = fileInput.files[0]; // file

        if (file) {
            if (file.type === 'text/csv') {
                var postcodeSelector = $(this).parents('.free-postcode-upload-section').find('.postcode-tags-input');
                uploadCSV(file)
                    .done(function(result) {
                        if (result.status && result.status == '200' && result.data != '') {
                            postcodeSelector.empty();
                            postcodeSelector.append(result.data[0]); // append the uploaded postcodes
                            fileInput.value = null;
                        }
                        toggleLoader();
                    })
                    .fail(function(error) {
                        console.error("Error:", error);
                    });
            } else {
                // if uploaded file is not CSV, return error
                var postcodeError = $(this).parents('.free-postcode-upload-section').find('.csv_error_message');
                postcodeError.html('<small class="text-danger" error-for="csv_error">(Uploaded file is not a CSV file)</small>');
                toggleLoader();
            }
        } else {
            toggleLoader();
        }
    });

    // for upload and process the free postcode CSV file edit location
    $(document).on("change", "#modalEditLocation #csvFile", function(e) {
        toggleLoader();

        let fileInput = $(this)[0];
        var file = fileInput.files[0]; // uploaded file

        if (file) {
            if (file.type === 'text/csv') {
                var postcodeSelector = $(this).parents('.free-postcode-upload-section').find('.postcode-tags-input');
                uploadCSV(file)
                    .done(function(result) {
                        if (result.status && result.status == '200' && result.data != '') {
                            postcodeSelector.empty();
                            postcodeSelector.append(result.data[0]); // append the uploaded postcodes
                            fileInput.value = null;
                        }
                        toggleLoader();
                    })
                    .fail(function(error) {
                        console.error("Error:", error);
                    });
            } else {
                // if uploaded file is not CSV, return error
                var postcodeError = $(this).parents('.free-postcode-upload-section').find('.csv_error_message');
                postcodeError.html('<small class="text-danger" error-for="csv_error">(Uploaded file is not a CSV file)</small>');
                toggleLoader();
            }
        } else {
            toggleLoader();
        }
    });
    $(document).ready(function() {
        // Start - add location suburb populate
        $('.add-location-form-suburb').on('click', function() {
            // Clear input value
            $(this).val('');
            // Show suburb list
            $('.add-location-form .fc-suburb-list').show();
        });

        // Event handler for populating suburbs based on user input
        $('.add-location-form-suburb').on('keyup', function() {
            // Call function to populate suburbs
            populateSuburbs(".add-location-form", $(this));
        });

        // Event handler for selecting a suburb from the list
        $(document).on('click', '.add-location-form .suburb-list', function() {
            // Assign selected suburb, state, and postcode values to form fields
            assignSelectedSuburbStatePostcodeValues('.add-location-form', $(this), true);
            // Hide suburb list
            $('.add-location-form .fc-suburb-list').hide();
        });
        // End - add location suburb populate

        // Start - edit location suburb populate
        $(document).on('click', '.edit-location-form-suburb', function() {
            // Clear input value
            $(this).val('');
            // Show suburb list
            $('.edit-location-form .fc-suburb-list').show();
        });

        // Event handler for populating suburbs based on user input
        $(document).on('keyup', '.edit-location-form-suburb', function() {
            // Call function to populate suburbs
            populateSuburbs(".edit-location-form", $(this));
        });

        // Event handler for selecting a suburb from the list
        $(document).on('click', '.edit-location-form .suburb-list', function() {
            // Assign selected suburb, state, and postcode values to form fields
            assignSelectedSuburbStatePostcodeValues('.edit-location-form', $(this), true);
            // Hide suburb list
            $('.edit-location-form .fc-suburb-list').hide();
        });
        // End - edit location suburb populate
    });
</script>