<form action="" name="locationForm" id="addLocation" autocomplete="off" class="add-location-form">
    <div class="row">
        <div class="col-12 mt-2">
            <label class="required-label mb-1" for='name'>Location Name</label>
            <input placeholder='Location Name' type='text' name='name' id='name' limit="19" class='form-control'
                required autocomplete="off">
        </div>
        <div class="col-6 mt-2">
            <label class="required-label mb-1" for='first_name'>First Name</label>
            <input placeholder='First Name' type='text' name='first_name' id='first_name' limit="10"
                class='form-control' required autocomplete="off">
        </div>
        <div class="col-6 mt-2">
            <label class="required-label mb-1" for='last_name'>Last Name</label>
            <input placeholder='Last Name' type='text' name='last_name' id='last_name' limit="10" class='form-control'
                required autocomplete="off">
        </div>
        <div class="col-6 mt-2">
            <label class="required-label mb-1" for='email'>Email</label>
            <input placeholder='Email' type='text' name='email' id='email' class='form-control' required autocomplete="off">
        </div>
        <div class="col-6 mt-2">
            <label class="required-label mb-1" for='phone'>Phone Number</label>
            <input placeholder='Phone Number' type='number' name='phone' id='phone' min-length="8" class='form-control'
                required autocomplete="off">
        </div>
        <div class="col-6 mt-2">
            <label class="mb-1" for='address1'>House / Office / Building Number</label>
            <input placeholder='House / Office / Building Number' type='text' name='address1' id='address1'
                class='form-control' autocomplete="off">
        </div>
        <div class="col-6 mt-2">
            <label class="required-label mb-2" for='street_number'>Street Number</label>
            <input placeholder='Street Number' type='text' name='street_number' id='street_number' class='form-control'
                required autocomplete="off">
        </div>
        <div class="col-6 mt-2">
            <label class="required-label mb-2" for='street_name'>Street Name</label>
            <input placeholder='Street Name' type='text' name='street_name' id='street_name' class='form-control'
                required autocomplete="off">
        </div>
        <div class="col-6 mt-2">
            <label class="mb-1">Apartment, suite, unit, etc.</label>
            <input placeholder='Apartment, suite, unit, etc.' type='text' name='address2' class='form-control' autocomplete="off">
        </div>
        <div class="col-6 mt-2 fc-suburb-dropdown">
            <label class="required-label mb-1" for='suburb-postcode-state'>Suburb, Postcode, State</label></br>
            <input placeholder="Search for postcode or suburb" type="text" value="" id="suburb-postcode-state"
                class="form-control fc-selected-suburb add-location-form-suburb inputwidth" autocomplete="off" required />
            <ul class="wp-ajax-suburbs fc-suburb-list form-control"></ul>
            <input class="fc-suburb" placeholder="Suburb" required type="hidden" name="suburb" id='suburb' />
            <input class="fc-state" placeholder="State" required type="hidden" name="state" id='state' />
            <input class="fc-postcode" placeholder="Post Code" required type="hidden" name="postcode" id="postcode" />
            <input required type="hidden" name="latitude" class="fc-latitude" />
            <input required type="hidden" name="longitude" class="fc-longitude" />
        </div>
        <div class="col-6 mt-2">
            <label class="required-label mb-1">Building Type</label>
            <select name="building_type" class="form-control inputwidth">
                <option value="residential">Residential</option>
                <option value="commercial">Commercial</option>
            </select>
        </div>
        <div class="col-6 mt-2">
            <label class="required-label mb-1">Pick up time Window</label>
            <select name="time_window" class="form-control inputwidth">
                <option value="9am to 5pm">9am to 5pm</option>
                <option value="12pm to 5pm">12pm to 5pm</option>
            </select>
        </div>

        <div class="col-6 mt-2">
            <label for="is_default" class="required-label mb-1">Is this your primary pickup location</label>
            <select name="is_default" class="form-control inputwidth" <?php if ($firstRecord) { ?> disabled <?php } ?>>
                <?php if ($firstRecord) { ?>
                    <option value="1" selected>Yes</option>
                <?php } else { ?>
                    <option value="0" selected>No</option>
                    <option value="1">Yes</option>
                <?php } ?>
            </select>
            <?php if ($firstRecord) { ?> <input type="hidden" name="is_default" value="1" /> <?php } ?>
        </div>

        <div class="col-6 mt-2">
            <div class="d-flex  tooltip-hover w-fit-content align-items-center mb-1" data-tooltip="Tag your location with a unique identifier. This allows you to allocate various products to one or more locations. You can assign tags to products in the ‘Product Mapping’ section">
                <label class="mb-1">Location tag (Optional)</label>
                <i class="fas fa-info-circle info-icon ml-1"> </i>
            </div>
            <select class="tags-input form-control inputwidth" name="tags[]" multiple required>
                <option></option>
                <?php foreach ($available_tags as $tag) { ?>
                    <option value="<?php echo $tag['id'] ?>"><?php echo $tag['name'] ?></option>
                <?php } ?>
            </select>
        </div>

        <div class="col-6 mt-2">
            <label for="tail_lift" class="required-label mb-1">Do you require a tail lift in this location</label>
            <select name="tail_lift" class="form-control inputwidth">
                <option value="0" selected>No</option>
                <option value="1">Yes</option>
            </select>
        </div>
        <div class="col-6 mt-2 free-postcode-upload-section">
            <div class="d-flex  tooltip-hover w-fit-content align-items-center mb-1" data-tooltip="Enter a postcode or postcode- range to enable free shipping for these postcodes from this location">
                <label class="">Free Shipping Area Postcodes</label>
                <i class="fas fa-info-circle info-icon ml-1"> </i>

            </div>
            <select class="postcode-tags-input form-control inputwidth" name="free_shipping_postcodes[]" multiple>
                <option></option>
            </select>
            <div class="row">
                <div class="col-6">
                    <input type="file" id="csvFile" accept=".csv"
                        style="padding: unset !important; padding-top: 1rem!important;">
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
                        <input type="checkbox" name="is_flat_rate_enabled" class="no-click-effect" id="flatRateCheckbox" onchange="toggleFlatRateFields()">

                        <span class="slider round no-click-effect"></span>


                    </label>
                </div>
            </div>
            <!-- Flat Rate input Fields -->
            <div id="flatRateFields" style="display: none;">
                <div class="mt-2">
                    <label class="required-label mb-1" for="flatrate">Flat rate</label>
                    <input placeholder="Flat rate" type="text" name="flatrate" id="flatrate" class="form-control" required>
                </div>
                <div class="mt-2">
                    <label class="required-label mb-1" for="flatratecodes">Flat Rate Shipping postcodes</label>
                    <textarea placeholder="Comma separated postcodes or postcode range 1111, 2222, 3333, 3000-4000, 5000-6000" name="flatratecodes" id="flatratecodes" rows="4" class="form-control" required></textarea>
                </div>
            </div>
        </div>

    </div>
</form>


<script>
    window.addEventListener('DOMContentLoaded', function() {
        const checkbox = document.getElementById('flatRateCheckbox');
        const flatRateFields = document.getElementById('flatRateFields');
        const flatRateInput = document.getElementById('flatrate');
        const flatRateCodesInput = document.getElementById('flatratecodes');

        checkbox.checked = false;
        flatRateFields.style.display = 'none';
        flatRateInput.required = false;
        flatRateCodesInput.required = false;
        flatRateInput.value = '';
        flatRateCodesInput.value = '';
    });

    function toggleFlatRateFields() {
        const checkbox = document.getElementById('flatRateCheckbox');
        const flatRateFields = document.getElementById('flatRateFields');
        const flatRateInput = document.getElementById('flatrate');
        const flatRateCodesInput = document.getElementById('flatratecodes');

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