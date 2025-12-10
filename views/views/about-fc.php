<?php
$step = 0;
function currentStep($step)
{
    return $step + 1;
}
?>
<div class="container-fluid custom_outer_wrapper">
    <div class="custom_inner_wrapper mx-auto" style="max-width: 1044px;">
        <div class="w-100 text-center">
            <div class="logo_wrapper text-center">
                <img src="<?php echo esc_url(plugins_url('../images/fast-courier-bkp.png', __FILE__)) ?>" height="85" />
            </div>
            <h5 class="heading_text text-center">Welcome to the Fast Courier Shipping and Freight Woo Commerce Plugin.</h5>            
            <h4 class="mt-5 text-center">⚠️This Plugin depends on Woo-Commerce.</h4>
            <h6>(Please make sure your woo-commerce store is completely setup)</h6>
            <!-- <div class="seperator"></div> -->
        </div>

        <div class="row">
            <div class="col-sm-12">
                <h4 class="heading_text setup_text">Follow the below steps for Plugin Set-Up.</h4>
                <ul class="steps">
                    <li>
                        <b> Step <?php echo esc_html($step = currentStep($step)) ?></b>:
                        As a New user just click on the <b>'Create New Account'</b>' on Login page and Sign-up for New Merchant Account.
                    </li>

                    <li>
                        <b>Note</b>: If you already have an account with us on our <a href="https://portal.fastcourier.com.au">Portal</a> just log-in with same credentials and proceed further.
                    </li>

                    <li>
                        <img src="<?php echo esc_url(plugins_url('../images/fast-courier-login-page.png', __FILE__)) ?>" height="600" />
                    </li>
                    
                    <li class="mt-5">
                        <b>Step <?php echo esc_html($step = currentStep($step)) ?></b>:
                        Please click on the <b>'Configuration'</b> Section and configure your Merchant details like Merchant billing address, shipping configurations, courier and insurance Preferences and more.
                    </li>

                    <li>  
                        <img src="<?php echo esc_url(plugins_url('../images/fast-courier-configuration.png', __FILE__)) ?>" height="600" />
                    </li>

                    <li class="mt-5">
                        <b>Step <?php echo esc_html($step = currentStep($step)) ?></b>:
                        Setup your payment method & company details in the <b>'Payment Methods'</b> Section. Payment method is required to be set for further processing of the orders.
                    </li>

                    <li>
                        <img src="<?php echo esc_url(plugins_url('../images/fast-courier-payment-method.png', __FILE__)) ?>" height="600" />
                    </li>

                    <li class="mt-5">
                        <b>Step <?php echo esc_html($step = currentStep($step)) ?></b>:
                        Define the package types your store uses in the <b>'Package Types'</b> section and select a default package type.
                    </li>

                    <li>  
                        <img src="<?php echo esc_url(plugins_url('../images/fast-courier-package-type.png', __FILE__)) ?>" height="500" />
                    </li>

                    <li class="mt-5">
                        <b>Step <?php echo esc_html($step = currentStep($step)) ?></b>:
                        Assign Dimensions, Weight & Package Types to products. You can filter and bulk assign your defined package types, dimensions or weight.
                    </li>

                    <li>  
                        <img src="<?php echo esc_url(plugins_url('../images/fast-courier-products-package-type.png', __FILE__)) ?>" height="400" />
                    </li>

                    <li class="mt-3">
                        <b>For example:-</b>
                        If you sets your <b>Package Type Dimenstions L x W x H (CMs)</b> as 6*6*6 your product should be 5*5*5 <br/>
                        <b>Note:-</b>
                        Your package types dimensions should be greater than your product’s dimensions so your product can be fit in that package.
                    </li>
                </ul>
            </div>

            <div class="col-sm-12">
                <h5 class="text-center">Support & Assistance</h5>
                For any billing queries or shipment support please log into the Merchant Portal and select 'Action' on specific consignments or select 'Support' and choose the relevant support. 
                Contact Email: <a href="mailto:hello@fastcourier.com.au"> hello@fastcourier.com.au </a>
            </div>
            <div class="col-sm-12">
                <h5 class="mt-4 text-center">Sell & Ship easily with Fast Courier Shipping and Freight!</h5>
            </div>
        </div>

    </div>
</div>

<div class="row footer_wrapper">
    <div class="col-sm-5 m-auto">
        <ul class="list-unstyled text-center about-address">
            <li><img class="footer-image" src="<?php echo esc_url(plugins_url('../images/fast-courier-white.png', __FILE__)) ?>" height="43" /></li>
            <li>ABN 31 627 497 223</li>
            <li>Merchant Portal: <a href="https://portal.fastcourier.com.au">https://portal.fastcourier.com.au</a>
            </li>
            <li>Contact Email: hello@fastcourier.com.au</li>
            <li>Website: <a href="https://fastcourier.com.au">https://fastcourier.com.au</a></li>
        </ul>
    </div>
</div>

<div class="copyright">
    Copyright © <?php echo date('Y') ?> Fast Courier (Equipment Hunt Group Pty Ltd.)
</div>