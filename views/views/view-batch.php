<?php

use FastCourier\FastCourierBatches;


if ($status == 400) {
?>
    <p class="alert alert-warning">Batch details not available.</p>
<?php
    return;
}

$ordersInvoices = array_column(@$batch['orders'], 'documents');
$invoices = array_column($ordersInvoices, 'invoice');

$ordersLabels = array_column(@$batch['orders'], 'documents');
$labels = array_column($ordersInvoices, 'label');

if (isset($_POST['download_invoices'])) {
    FastCourierBatches::downloadZip(array_column(array_column($invoices, 0), 'url'));
}

if (isset($_POST['download_labels'])) {
    FastCourierBatches::downloadZip(array_column(array_column($labels, 0), 'url'));
}


if (isset($_POST['download'])) {
    $urls = explode(",", sanitize_text_field($_POST['download']));

    FastCourierBatches::downloadZip($urls);
}

?>


<div class="container-fluid">
    <div class="d-flex justify-content-end mb-3">
        <form method="post">
            <button class="btn btn-success mr-2" <?php echo esc_attr(count(array_column(array_column($labels, '0'), 'url')) ? '' : 'disabled') ?> name="download_labels">Download Labels</button>
        </form>

        <form method="post">
            <button class="btn btn-warning mr-2" <?php echo esc_attr(count(array_column(array_column($invoices, '0'), 'url')) ? '' : 'disabled') ?> name="download_invoices">Download Invoices</button>
        </form>

        <a class="btn btn-primary" href="<?php echo esc_url($_SERVER['HTTP_REFERER']) ?>">Back</a>
    </div>
    <div class="row batch-details">
        <div class="col-sm-6">
            <p>Batch ID: <span><?php echo esc_html($_GET['batch']) ?></span></p>
            <p>Batch Started At: <span><?php echo esc_html(@$batch['batch_started_at']) ?></span></p>
            <p>Batch Completed At: <span><?php echo esc_html(@$batch['batch_completed_at']) ?></span></p>
        </div>
        <div class="col-sm-6">
            <p>Successful Orders: <span><?php echo esc_html(@$batch['order_succeeded']) ?></span></p>
            <p>Total Orders: <span><?php echo esc_html(@$batch['total_orders']) ?></span></p>
            <p>Total Amount: <span><?php echo esc_html(@$batch['total_amount']) ?></span></p>
        </div>


        <table class="table table-bordered fc-table mt-3">
            <thead>
                <tr>
                    <th>#</th>
                    <th>FC Ref ID</th>
                    <th>WP Order ID</th>
                    <th>Consignment</th>
                    <th>Order Total</th>
                    <th>Items</th>
                    <th class="text-center" width="200px">Documents</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if(!count(@$batch['orders'])){
                    ?>
                    <tr>
                        <th colspan="7">No Records Found</th>
                    </tr>
                    <?php
                }

                foreach (@$batch['orders'] as $key => $order) {
                    $items = json_decode($order['items']);
                    $label = isset($order['documents']['label'][0]) ? $order['documents']['label'][0]['url'] : null;
                    $invoice = isset($order['documents']['invoice'][0]) ? $order['documents']['invoice'][0]['url'] : null;
                    $additional = isset($order['documents']['additional'][0]) ? $order['documents']['additional'][0]['url'] : null;
                ?>
                    <tr>
                        <td><?php echo esc_html($key + 1) ?></td>
                        <td><?php echo esc_html($order['hash_id']) ?></td>
                        <td><?php echo esc_html($order['wp_order_id']) ?></td>
                        <td><?php echo esc_html($order['consignment_code']) ?></td>
                        <td><?php echo esc_html($order['order_amount']) ?></td>
                        <td><?php echo count($items) ?></td>
                        <td class="d-flex justify-content-center">
                            <form method="post">
                                <a class="btn btn-success mr-2 <?php echo esc_attr($label ? '' : 'disabled') ?>" title='Label' href="<?php echo esc_url($label ? $label : '#') ?>" download target="_blank"><i class="fa-solid fa-receipt"></i></a>
                                <a class="btn btn-warning mr-2 <?php echo esc_attr($invoice ? '' : 'disabled') ?>" title='Invoice' href="<?php echo esc_url($invoice ? $invoice : '#') ?>" download target="_blank"><i class="fa-solid fa-file-invoice"></i></a>
                                <button class="btn btn-danger <?php echo esc_attr($additional ? '' : 'disabled') ?>" name="download" title='Additional Documents' value="<?php echo esc_html($additional ? $additional : '') ?>"><i class="fa-solid fa-file-lines"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php
                }
                ?>
            </tbody>
        </table>
    </div>
</div>