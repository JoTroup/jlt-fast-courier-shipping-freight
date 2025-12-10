<?php

use FastCourier\FastCourierRequests;

$paymentMethodResponse = FastCourierRequests::httpGet('payment_method');

$paymentMethods = $paymentMethodResponse['data'];
?>