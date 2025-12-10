<?php
$quotes = json_decode($order->get_meta('order_quotes'), true);
?>
<h3>FC Recommended Packages</h3>
<table class="table table-bordered table-sriped table-packages">
    <thead>
        <tr>
            <td>#</td>
            <td>Package</td>
            <td>Dimensions</td>
            <td>Sub Items</td>
            <td></td>
        </tr>
    </thead>
    <tbody>
        <?php
        foreach ($quotes as $key => $quote) {
        ?>
            <tr>
                <td></td>
                <td><?php echo esc_html($quote['package_name']) ?></td>
                <td><?php echo esc_html($quote['outside_l']) ?> x <?php echo esc_html($quote['outside_w']) ?> x <?php echo esc_html($quote['outside_h']) ?> </td>
                <td><?php echo isset($quote['sub_packs']) ? esc_html(count($quote['sub_packs'])) : 1 ?></td>
                <td></td>
            </tr>
        <?php
        }
        ?>
    </tbody>
</table>