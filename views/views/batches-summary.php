<?php

use FastCourier\FastCourierBatches;

$fcBatches = new FastCourierBatches();

if (isset($_POST['download'])) {
    $urls = explode(",", sanitize_text_field($_POST['download']));

    $fcBatches->downloadZip($urls);
}
$active_log_tab = $active_webhook_tab = '';
$active_batch_tab = 'nav-tab-active';
if (isset($_GET['tab']) && $_GET['tab'] == 'cron-logs') {
    $active_log_tab = 'nav-tab-active';
    $active_batch_tab = '';
} elseif (isset($_GET['tab']) && $_GET['tab'] == 'webhook-logs') {
    $active_webhook_tab = 'nav-tab-active';
    $active_log_tab = $active_batch_tab = '';
}
?>

<!-- CSS for custom styling of Logs detail modal -->
<style>
    .log-table tr {
        border: 1px solid #dee2e6;
    }

    .log-table tr td {
        font: 16px poppinsregular;
        color: #212529;
        padding: 8px;
    }

    .log-table tr:first-child td {
        min-width: 180px !important;
    }

    .log-table tr:last-child td {
        align-items: left;
    }
</style>

<div class="container-fluid">
    <div class="row mt-3">
        <nav class="nav-tab-wrapper woo-nav-tab-wrapper">
            <a href="" onclick="switchTo('tab', 'batch-logs', event)" class="nav-tab <?php echo esc_html($active_batch_tab) ?>">Batch Logs</a>
            <a href="" onclick="switchTo('tab', 'cron-logs', event)" class="nav-tab <?php echo esc_html($active_log_tab) ?>">Cron Logs</a>
            <a href="" onclick="switchTo('tab', 'webhook-logs', event)" class="nav-tab <?php echo esc_html($active_webhook_tab) ?>">Webhook Logs</a>
        </nav>
    </div>
    <?php if (isset($active_webhook_tab) && !empty($active_webhook_tab) && count($webHookLogs) > 0) { ?>
        <div class="row mt-1">
            <div class="col-sm-12">
                <button class="btn btn-primary m-1 pull-right" onclick="toggleClearLogs()">Clear All Logs</button>
            </div>
        </div>
    <?php } ?>
    <div class="row">
        <?php if (isset($cronLogs)) { ?>
            <table class="table fc-table">
                <thead>
                    <tr>
                        <th>Log ID</th>
                        <th>Name</th>
                        <th>Total Orders</th>
                        <th>Processed Orders</th>
                        <th>Started At</th>
                        <th>Completed At</th>
                        <th>Collection Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!count($cronLogs)) { ?>
                        <tr>
                            <th colspan="7" class="text-center">No Records Found</th>
                        </tr>
                    <?php }
                    foreach ($cronLogs as $key => $log) {
                        $formattedDate = "--";
                        if (isset($log['collection_date'])) {
                            $date = new DateTime($log['collection_date']);
                            $formattedDate = $date->format('Y-m-d');
                        }
                    ?>
                        <tr>
                            <td><?php echo esc_html($log['id']) ?></td>
                            <td><?php echo esc_html($log['name']) ?></td>
                            <td><?php echo esc_html($log['total_orders']) ?></td>
                            <td class="<?php if (isset($log['order_ids'])) { ?> position-relative processed-order <?php } ?>" title="<?php echo esc_html($log['order_ids']) ?>"><?php echo esc_html($log['processed_orders'] ?? 0) ?> </td>
                            <td><?php echo esc_html($log['started_at']) ?></td>
                            <td><?php echo esc_html($log['completed_at']) ?></td>
                            <td><?php echo esc_html($formattedDate) ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        <?php
            fc_pagination($totalPages);
        } elseif (isset($webHookLogs)) { ?>
            <table class="table fc-table">
                <thead>
                    <tr>
                        <th>Log Id</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!count($webHookLogs)) { ?>
                        <tr>
                            <th colspan="7" class="text-center">No Records Found</th>
                        </tr>
                        <?php } else {
                        foreach ($webHookLogs as $webHooklog) {
                            $logDate = date('d M, y H:i:s', strtotime($webHooklog['created_at']));
                        ?>
                            <tr>
                                <td><?php echo $webHooklog['id'] ?></td>
                                <td><?php echo $logDate ?></td>
                                <td><button class='log-details-btn btn btn-primary' data-log-date="<?php echo $logDate ?>" data-log='<?php echo esc_html($webHooklog['payload']) ?>'>View Details</button></td>
                            </tr>
                    <?php }
                    } ?>
                </tbody>
            </table>
        <?php fc_pagination($totalPages);
        } else { ?>
            <table class="table fc-table">
                <thead>
                    <tr>
                        <th>Batch ID</th>
                        <th>Total Orders</th>
                        <th>Successful Orders</th>
                        <th>Started At</th>
                        <th>Completed At</th>
                        <th class="text-center" width="200">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (!count($batches['data'])) {
                    ?>
                        <tr>
                            <th colspan="7" class="text-center">No Records Found</th>
                        </tr>
                    <?php
                    }

                    foreach ($batches['data'] as $batch) {
                        $labels = $batch['documents']['label'];
                        $invoices = $batch['documents']['invoice'];
                    ?>
                        <tr>
                            <td><?php echo esc_html($batch['id']) ?></td>
                            <td><?php echo esc_html($batch['total_orders']) ?></td>
                            <td><?php echo esc_html($batch['order_succeeded']) ?></td>
                            <td><?php echo esc_html($batch['batch_started_at']) ?></td>
                            <td><?php echo esc_html($batch['batch_completed_at']) ?></td>
                            <td>
                                <div class="d-flex">
                                    <div class="d-flex m-auto">
                                        <a class="btn btn-primary mr-2 p-1" title="View Batch" href="?page=<?php echo esc_html($_GET['page']) ?>&batch=<?php echo esc_html($batch['id']) ?>">
                                            <i class="fa fa-eye"></i>
                                        </a>
                                        <form method="post">
                                            <button class="btn btn-success mr-2 p-1 px-2" title="Download Labels" name="download" type="submit" <?php echo esc_attr(count($labels) ? '' : 'disabled') ?> value="<?php echo esc_html(count($labels) ? implode(",", array_column($labels, 'url')) : '') ?>">
                                                <i class="fa-solid fa-receipt"></i>
                                            </button>
                                        </form>
                                        <form method="post">
                                            <button class="btn btn-warning p-1 px-2" title="Download Invoices" name="download" type="submit" <?php echo esc_attr(count($invoices) ? '' : 'disabled') ?> value="<?php echo esc_html(count($invoices) ? implode(",", array_column($invoices, 'url')) : '') ?>">
                                                <i class="fa-solid fa-file-invoice"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php
                    }
                    ?>
                </tbody>
            </table>
        <?php
            fc_pagination($batches['last_page']);
        } ?>
    </div>
</div>
<script>
    function switchTo(param, value, event) {
        event.preventDefault(); // Prevent the default link behavior

        var url = window.location.href; // Get the current URL

        // Check if the URL already has query parameters
        if (url.indexOf('?') !== -1) {
            // Check if the parameter already exists
            if (url.indexOf(param) !== -1) {
                // Remove the existing parameter and its value
                var regex = new RegExp(param + '=[^&]*(&|$)', 'g');
                url = url.replace(regex, '');
            }
            // Append the new parameter with an ampersand (&)
            url += '&' + param + '=' + value;
        } else {
            // Append the new parameter with a question mark (?)
            url += '?' + param + '=' + value;
        }

        // Update the current URL with the modified query parameter
        window.location.href = url;
    }
    // confirm box for delete the webhook logs
    function toggleClearLogs() {
        Swal.fire({
                title: "Are you sure?",
                text: "Once deleted, you will not be able to recover the logs!",
                showDenyButton: true,
                showCancelButton: true,
                confirmButtonText: "Delete",
                denyButtonText: "Cancel"
            })
            .then((result) => {
                if (result.value) {
                    // call clear log function
                    clearLogs();
                }
            });
    }
    // clear / Delete the logs from the txt file
    function clearLogs() {
        toggleLoader();
        var ajaxurl = '<?php echo esc_js(admin_url('admin-ajax.php', 'relative')); ?>';
        var formData = `action=delete_webhook_logs`;

        jQuery.post(ajaxurl, formData, function(response) {
            if (response == 1) {
                Swal.fire('', 'Logs deleted', 'success').then(function() {
                    toggleLoader();
                    location.reload();
                });
            } else {
                Swal.fire("", response, "error");
            }
            toggleLoader();
        });

    }

    document.addEventListener('DOMContentLoaded', function() {
        // Add click event listener to buttons with class 'log-details-btn'
        document.querySelectorAll('.log-details-btn').forEach(button => {
            // Add click event listener
            button.addEventListener('click', function() {
                // Get log data from data attribute
                const logData = JSON.parse(this.getAttribute('data-log'));
                var logDate = this.getAttribute('data-log-date');

                // Function to remove underscores and capitalize first letter
                function formatKey(key) {
                    return key.replace(/_/g, ' ').replace(/^\w/, match => match.toUpperCase());
                }

                // Create HTML content for the modal
                let modalContent = "<table class='log-table'><tbody>";
                modalContent += "<thead><tr><th>Key</th><th>Value</th></tr></thead>";
                modalContent += `<tr><td>Date</td><td>${logDate}</td></tr>`; // set date here
                for (const key in logData) {
                    if (logData.hasOwnProperty(key)) {
                        const formattedKey = formatKey(key);
                        modalContent += `<tr><td>${formattedKey}</td><td>${logData[key]}</td></tr>`;
                    }
                }
                modalContent += "</tbody></table>";

                Swal.fire({
                    title: 'Log Details',
                    html: modalContent,
                    showCloseButton: true,
                    confirmButtonText: 'Close',
                });

                const modalContainer = document.querySelector('.swal2-popup');
                modalContainer.style.width = '820px'; // Adjust the width as needed
            });
        });
    });
</script>