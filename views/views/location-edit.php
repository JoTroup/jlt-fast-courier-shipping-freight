<?php
$location = $location['data']['data'];
global $bookingPreferences, $shippingPreferences;
?>
<form name='location-edit-form' id="editLocation" class="edit-location-form" autocomplete="off">
    <div class="row">
        <input type="hidden" name="id" value="<?php echo esc_attr($location['id']) ?>" />
        <div class="col-12 mt-2">
            <label class="required-label mb-1" for='name'>Location Name</label>
            <input placeholder='Location Name' value="<?php echo esc_attr($location['location_name']) ?>" limit="19" type='text' name='name' class='form-control' id="name" required autocomplete="off">
        </div>
        <div class="col-6 mt-2">
            <label class="required-label mb-1" for='first_name'>First Name</label>
            <input placeholder='First Name' value="<?php echo esc_attr($location['first_name']) ?>" limit="10" type='text' name='first_name' id='first_name' class='form-control' required autocomplete="off">
        </div>
        <div class="col-6 mt-2">
            <label class="required-label mb-1" for='last_name'>Last Name</label>
            <input placeholder='Last Name' value="<?php echo esc_attr($location['last_name']) ?>" limit="10" type='text' name='last_name' id='last_name' class='form-control' required autocomplete="off">
        </div>
        <div class="col-6 mt-2">
            <label class="required-label mb-1" for="email">Email</label>
            <input placeholder='Email' value="<?php echo esc_attr($location['email']) ?>" type='text' name='email' class='form-control' id="email" required autocomplete="off">
        </div>
        <div class="col-6 mt-2">
            <label class="required-label mb-1" for='phone'>Phone Number</label>
            <input placeholder='Phone Number' value="<?php echo esc_attr($location['phone']) ?>" type='number' min-length="8" name='phone' id='phone' class='form-control' required autocomplete="off">
        </div>
        <div class="col-6 mt-2">
            <label class="mb-1" for='address1'>House / Office / Building Number</label>
            <input placeholder='House / Office / Building Number' value="<?php echo esc_attr($location['address1']) ?>" type='text' name='address1' id='address1' class='form-control' autocomplete="off">
        </div>
        <div class="col-6 mt-2">
            <label class="required-label mb-2" for='street_number'>Street Number</label>
            <input placeholder='Street Number' type='text' name='street_number' value="<?php echo esc_attr($location['street_number']) ?>" id='street_number' class='form-control' required autocomplete="off">
        </div>
        <div class="col-6 mt-2">
            <label class="required-label mb-2" for='street_name'>Street Name</label>
            <input placeholder='Street Name' type='text' name='street_name' value="<?php echo esc_attr($location['street_name']) ?>" id='street_name' class='form-control' required autocomplete="off">
        </div>
        <div class="col-6 mt-2">
            <label class="mb-1">Apartment, suite, unit, etc.</label>
            <input placeholder='Apartment, suite, unit, etc.' value="<?php echo esc_attr($location['address2']) ?>" type='text' name='address2' class='form-control' autocomplete="off">
        </div>
        <div class="col-6 mt-2 fc-suburb-dropdown">
            <label class="required-label mb-1" for='suburb-postcode-state'>Suburb, Postcode, State</label></br>
            <input placeholder="Search for postcode or suburb" type="text" value="<?php echo esc_attr(@$location['suburb']) . ', ' . esc_attr(@$location['postcode']) . ' (' . esc_attr(@$location['state']) . ')' ?>" required id="suburb-postcode-state" class="form-control fc-selected-suburb edit-location-form-suburb inputwidth" autocomplete="off" />
            <ul class="wp-ajax-suburbs fc-suburb-list form-control"></ul>
            <input class="fc-suburb" placeholder="Suburb" required type="hidden" name="suburb" value="<?php echo esc_attr($location['suburb']) ?>" />
            <input class="fc-state" placeholder="State" required type="hidden" name="state" value="<?php echo esc_attr($location['state']) ?>" />
            <input class="fc-postcode" placeholder="Post Code" required type="hidden" name="postcode" value="<?php echo esc_attr($location['postcode']) ?>" />
            <input class="fc-latitude" required type="hidden" name="latitude" value="<?php echo esc_attr($location['latitude']) ?>" />
            <input class="fc-longitude" required type="hidden" name="longitude" value="<?php echo esc_attr($location['longitude']) ?>" />
        </div>
        <div class="col-6 mt-2">
            <label class="required-label mb-1">Building Type</label>
            <select name="building_type" class="form-control inputwidth">
                <option value="residential" <?php if (esc_attr($location['building_type']) == 'residential') {
                                                echo 'selected';
                                            } ?>>Residential</option>
                <option value="commercial" <?php if (esc_attr($location['building_type']) == 'commercial') {
                                                echo 'selected';
                                            } ?>>Commercial</option>
            </select>
        </div>
        <div class="col-6 mt-2">
            <label class="required-label mb-1">Pick up time Window</label>
            <select name="time_window" class="form-control inputwidth">
                <option value="9am to 5pm" <?php if (esc_attr($location['time_window']) == '9am to 5pm"') {
                                                echo 'selected';
                                            } ?>>9am to 5pm</option>
                <option value="12pm to 5pm" <?php if (esc_attr($location['time_window']) == '12pm to 5pm') {
                                                echo 'selected';
                                            } ?>>12pm to 5pm</option>
            </select>
        </div>

        <div class="col-6 mt-2">
            <label for="is_default" class="required-label mb-1">Is this your primary pickup location</label>
            <select name="is_default" class="form-control inputwidth" <?php if (esc_attr($location['is_default']) == 1) { ?> disabled <?php } ?>>
                <option value="0" <?php if (esc_attr($location['is_default']) == 0) {
                                        echo 'selected';
                                    } ?>>No</option>
                <option value="1" <?php if (esc_attr($location['is_default']) == 1) {
                                        echo 'selected';
                                    } ?>>Yes</option>
            </select>
            <?php if (esc_attr($location['is_default']) == 1) { ?> <input type="hidden" name="is_default" value="1" /> <?php } ?>
        </div>
        <div class="col-6 mt-2">
            <div class="d-flex  tooltip-hover w-fit-content align-items-center mb-1" data-tooltip="Tag your location with a unique identifier. This allows you to allocate various products to one or more locations. You can assign tags to products in the ‘Product Mapping’ section">
                <label class="mb-1">Location tag (Optional)</label>
                <i class="fas fa-info-circle info-icon ml-1"> </i>
            </div>
            <select class="tags-input form-control inputwidth" name="tags[]" multiple required>
                <option></option>
                <?php
                $selected_tags = explode(',', $location['tag']);
                foreach ($tags as $tag) {
                    if (in_array($tag['id'], $selected_tags)) {
                ?>
                        <option value="<?php echo $tag['id'] ?>" selected><?php echo $tag['name'] ?></option>
                    <?php
                    } else {
                    ?>
                        <option value="<?php echo $tag['id'] ?>"><?php echo $tag['name'] ?></option>
                <?php
                    }
                }
                ?>
            </select>
        </div>
        <div class="col-6 mt-2">
            <label for="tail_lift" class="required-label mb-1">Do you require a tail lift in this location</label>
            <select name="tail_lift" class="form-control inputwidth">
                <option value="0" <?php if (esc_attr($location['tail_lift']) == 0) {
                                        echo 'selected';
                                    } ?>>No</option>
                <option value="1" <?php if (esc_attr($location['tail_lift']) == 1) {
                                        echo 'selected';
                                    } ?>>Yes</option>
            </select>

        </div>
        <div class="col-6 mt-2 free-postcode-upload-section">
            <div class="d-flex  tooltip-hover w-fit-content align-items-center mb-1" data-tooltip="Enter a postcode or postcode- range to enable free shipping for these postcodes from this location">
                <label class="">Free Shipping Area Postcodes</label>
                <i class="fas fa-info-circle info-icon ml-1"> </i>
            </div>
            <select class="postcode-tags-input form-control inputwidth" name="free_shipping_postcodes[]" multiple>
                <option></option>
                <?php
                if (isset($location['free_shipping_postcodes']) && !empty($location['free_shipping_postcodes'])) {
                    $freeShippingCodes = explode(',', $location['free_shipping_postcodes']);
                    foreach ($freeShippingCodes as $tag) { ?>
                        <option value="<?php echo $tag ?>" selected><?php echo $tag ?></option>
                <?php }
                } ?>
            </select>
            <div class="row">
                <div class="col-6">
                    <input type="file" id="csvFile" accept=".csv" style="padding: unset !important; padding-top: 1rem!important;">
                </div>
                <div class="col-6 pt-3 text-right">
                    <span style="padding-top: 1rem!important;">
                        <a href="<?php echo esc_url(plugins_url('../sample/sample.csv', __FILE__)) ?>"> Free Shipping Sample CSV </a>
                    </span>
                </div>
                <span class="csv_error_message"></span>
            </div>
        </div>

        <div class="col-6 mt-2"></div>
        <div class="col-6 mt-2">
            <!-- FLAT RATE IMPLEMENTATION -->
            <div class="d-flex align-items-center justify-content-between mt-3">
                <div>
                    <div class="fw-bolder">Apply Flat rate option:</div>
                    <div class="text-muted small">By turning on this option, you can apply a flat rate to all the orders that are placed for selected postcodes.</div>
                </div>
                <div>
                    <label class="switch no-click-effect">
                        <input
                            type="checkbox"
                            name="is_flat_rate_enabled"
                            class="no-click-effect"
                            id="edit-flatRateCheckbox"
                            onchange="toggleFlatRateFields()"
                            value="1"
                            <?php checked($location['is_flat_enable'], 1); ?>>
                        <span class="slider round no-click-effect"></span>
                    </label>
                </div>
            </div>
            <!-- Flat Rate input Fields -->
            <div id="edit-flatRateFields" style="display: <?php echo $location['is_flat_enable'] == 1 ? 'block' : 'none'; ?>;">
                <div class="mt-2">
                    <label class="required-label mb-1" for="flatrate">Flat rate</label>
                    <input placeholder="Flat rate" type="text" name="flatrate" accept=""
                        value="<?php echo esc_attr($location['flat_rate']) ?>"
                        id="edit-flatrate" class="form-control" required>
                </div>
                <div class="mt-2">
                    <label class="required-label mb-1" for="flatratecodes">Flat Rate Shipping postcodes</label>
                    <textarea
                        placeholder="Comma separated postcodes or postcode range 1111, 2222, 3333, 3000-4000, 5000-6000"
                        name="flatratecodes"
                        id="edit-flatratecodes"
                        rows="4"
                        class="form-control"
                        required><?php echo esc_attr($location['flat_shipping_postcodes']); ?></textarea>
                </div>
            </div>
        </div>
    </div>
</form>

<?php
global $token;
?>

<script>
    $('input').on('keyup', function() {
        var field = $(this);
        var idOfField = field.attr('id');
        if ($(`[error-for=${idOfField}]`).length) {
            $(`[error-for=${idOfField}]`).remove();
            field.removeClass('border-danger');
        }
    });

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

    $('.suburbs-selection').each(function() {
        $(this).on('select2:select', function(e) {
            var data = e.params.data;
            if (data.id == data.postcode + "_" + data.name) {
                $($(this).parent().children()[8]).val(data.longitude);
                $($(this).parent().children()[7]).val(data.latitude);
                $($(this).parent().children()[6]).val(data.postcode);
                $($(this).parent().children()[5]).val(data.state);
                $($(this).parent().children()[4]).val(data.name);
            }
        });

        $(this).select2({
            placeholder: 'Search your Suburb',
            ajax: {
                url: "<?php echo fc_apis_prefix() ?>suburbs",
                dataType: 'json',
                headers: {
                    'Authorization': `Bearer <?php echo $token ?>`,
                    'version': '5.2.0',
                },
                data: (params) => {
                    var query = {
                        q: 'term',
                        term: params.term
                    }
                    return query;
                },
                processResults: (data, params) => {
                    const results = data.data.map(item => {
                        return {
                            ...item,
                            id: item.postcode + '_' + item.name,
                            text: item.name + ', ' + item.postcode + ' (' + item.state + ')',
                        };
                    });

                    return {
                        results,
                    }
                },
            },
        });

        if ($($(this).parent().children()[4]).val()) {
            $(this).append($("<option></option>")
                .prop('selected', true)
                .attr("value", $($(this).parent().children()[4]).val() + ", " + $($(this).parent().children()[6]).val() + " (" + $($(this).parent().children()[5]).val() + ")")
                .text($($(this).parent().children()[4]).val() + ", " + $($(this).parent().children()[6]).val() + " (" + $($(this).parent().children()[5]).val() + ")"));
        }
    });

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


    document.querySelectorAll('.tooltip-hover').forEach(cell => {
        cell.addEventListener('mouseover', function(event) {
            const tooltip = document.getElementById('custom-tooltip');
            const cellRect = cell.getBoundingClientRect();

            // Set the text of the tooltip
            tooltip.textContent = cell.getAttribute('data-tooltip');

            // Make the tooltip visible
            tooltip.style.opacity = '1';
            tooltip.style.position = 'fixed'; // Use fixed position for consistency with scrolling
            tooltip.style.width = 'auto'; // Allow the width to adjust based on content
            tooltip.style.maxWidth = '300px';
            tooltip.style.zIndex = '3000000';
            // Get tooltip dimensions
            const tooltipWidth = tooltip.offsetWidth;
            const tooltipHeight = tooltip.offsetHeight;

            // Calculate available space
            const spaceAbove = cellRect.top;
            const spaceBelow = window.innerHeight - cellRect.bottom;
            const spaceLeft = cellRect.left;
            const spaceRight = window.innerWidth - cellRect.right;

            // Position the tooltip to stay within visible area, centered over the cell
            if (spaceBelow >= tooltipHeight) {
                // Position tooltip below the cell
                tooltip.style.top = `${cellRect.bottom + 5}px`;
                tooltip.style.left = `${cellRect.left + cellRect.width / 2}px`;
                tooltip.style.transform = 'translateX(-50%)';
            } else if (spaceAbove >= tooltipHeight) {
                // Position tooltip above the cell
                tooltip.style.top = `${cellRect.top - tooltipHeight - 5}px`;
                tooltip.style.left = `${cellRect.left + cellRect.width / 2}px`;
                tooltip.style.transform = 'translateX(-50%)';
            } else if (spaceRight >= tooltipWidth) {
                // Position tooltip to the right of the cell
                tooltip.style.top = `${cellRect.top + cellRect.height / 2}px`;
                tooltip.style.left = `${cellRect.right + 5}px`;
                tooltip.style.transform = 'translateY(-50%)';
            } else if (spaceLeft >= tooltipWidth) {
                // Position tooltip to the left of the cell
                tooltip.style.top = `${cellRect.top + cellRect.height / 2}px`;
                tooltip.style.left = `${cellRect.left - tooltipWidth - 5}px`;
                tooltip.style.transform = 'translateY(-50%)';
            } else {
                // Default to positioning above if space is limited
                tooltip.style.top = `${cellRect.top - tooltipHeight - 5}px`;
                tooltip.style.left = `${cellRect.left + cellRect.width / 2}px`;
                tooltip.style.transform = 'translateX(-50%)';
            }
        });

        cell.addEventListener('mouseout', function() {
            const tooltip = document.getElementById('custom-tooltip');
            // Hide the tooltip
            tooltip.style.opacity = '0';
            tooltip.style.top = '-9999px'; // Move it out of view to avoid display issues
            tooltip.style.left = '-9999px';
        });
    });


    function toggleFlatRateFields() {
        const checkbox = document.getElementById('edit-flatRateCheckbox');
        const flatRateFields = document.getElementById('edit-flatRateFields');
        const flatRateInput = document.getElementById('edit-flatrate');
        const flatRateCodesInput = document.getElementById('edit-flatratecodes');

        if (checkbox.checked) {
            flatRateFields.style.display = 'block';
            flatRateInput.required = true;
            flatRateCodesInput.required = true;
        } else {
            flatRateFields.style.display = 'none';
            flatRateInput.required = false;
            flatRateCodesInput.required = false;
            flatRateInput.value = '';
            flatRateCodesInput.value = '';
        }
    }
</script>