<?php

namespace FastCourier;


use FastCourier\FastCourierRequests;
use FastCourier\FastCourierMenuPage;

class FastCourierBatches
{
    public static function index()
    {
        global $wpdb;
        $getData = fc_sanitize_data($_GET);
        if (isset($getData['batch'])) {

            $batch = FastCourierRequests::httpGet('batchDetail/' . $getData['batch']);

            return FastCourierMenuPage::layout('views/view-batch.php', ['page_title' => 'Batch Details', 'batch' => @$batch['data']['data'], 'status' => $batch['status']]);
        }

        try {
            // Pagination setup
            $page = isset($getData['page_no']) ? $getData['page_no'] : 1;
            if (isset($getData['tab'])) {
                if ($getData['tab'] == 'cron-logs') {
                    global $fc_cron_logs_table;

                    $per_page = 10; // Number of items to display per page

                    // Get the total number of rows in the table
                    $total_items = $wpdb->get_var("SELECT COUNT(*) FROM {$fc_cron_logs_table}");

                    // Calculate the total number of pages
                    $totalPages = ceil($total_items / $per_page);

                    // Calculate the offset for the current page
                    $offset = ($page - 1) * $per_page;

                    $logsQuery = "SELECT * FROM {$fc_cron_logs_table} ORDER BY id DESC LIMIT {$per_page} OFFSET {$offset}";
                    $cronLogs = $wpdb->get_results($logsQuery, ARRAY_A);

                    return FastCourierMenuPage::layout('views/batches-summary.php', ['page_title' => 'Activity Log', 'totalPages' => $totalPages, 'sub_page_title' => 'Cron logs', 'cronLogs' => $cronLogs]);
                }
                // Web hook logs
                if ($getData['tab'] == 'webhook-logs') {
                    global $fc_web_hook_logs_table;
                    // Number of items to display per page
                    $per_page = 10;

                    // Get the total number of rows in the table
                    $total_items = $wpdb->get_var("SELECT COUNT(*) FROM {$fc_web_hook_logs_table}");

                    // Calculate the total number of pages
                    $totalPages = ceil($total_items / $per_page);

                    // Calculate the offset for the current page
                    $offset = ($page - 1) * $per_page;

                    // Fetch the data from the database
                    $webhookLogsQuery = "SELECT * FROM {$fc_web_hook_logs_table} WHERE is_deleted = 0 ORDER BY id DESC LIMIT {$per_page} OFFSET {$offset}";
                    $webHookLogs = $wpdb->get_results($webhookLogsQuery, ARRAY_A);

                    return FastCourierMenuPage::layout('views/batches-summary.php', ['page_title' => 'Webhook Logs', 'totalPages' => $totalPages, 'sub_page_title' => 'Webhook logs', 'webHookLogs' => $webHookLogs]);
                }
            }
            $query = http_build_query(['page' => $page]);

            $batches = FastCourierRequests::httpGet('batchesReport' . '?' . $query);

            return FastCourierMenuPage::layout('views/batches-summary.php', ['page_title' => 'Activity Log', 'sub_page_title' => 'Bulk export your Shipping Invoices, Shipping Labels and Order Summaries.', 'batches' => $batches['data']['data']]);
        } catch (\Exception $e) {
        }
    }

    public static function downloadZip($urls)
    {
        $upload_dir = wp_upload_dir();

        $downloadName = 'FC_docs_' . date('Y_m_d_h_i_s');
        $user_dirname = $upload_dir['basedir'] . '/' . $downloadName;
        if (!file_exists($user_dirname)) wp_mkdir_p($user_dirname);

        foreach ($urls as $url) {
            // dd(str_replace(" ", "%20", $url));
            $file = file_get_contents(str_replace(" ", "%20", $url));
            // dd($file);

            $baseName = basename($url);

            file_put_contents($user_dirname . '/' . $baseName, $file);
        }

        $zipname = $user_dirname . '.zip';
        $zip = new \ZipArchive;
        $zip->open($zipname, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        if ($handle = opendir($user_dirname)) {
            while (false !== ($entry = readdir($handle))) {
                if ($entry != "." && $entry != "..") {
                    $zip->addFile($user_dirname . '/' . $entry, $entry);
                }
            }
            closedir($handle);
        }

        $zip->close();
        // dd($zipname);

        header('Content-Type: application/zip');
        header("Content-Disposition: attachment; filename=adcs.zip");
        header('Content-Length: ' . filesize($zipname));
        header("Location: " . $upload_dir['baseurl'] . '/' . $downloadName . '.zip');
    }

    /**
     * Deletes the webhook logs
     *
     * Empties the log file to prevent it from growing indefinitely.
     *
     * @return void
     */
    public static function deleteWebhookLogs()
    {
        try {
            global $wpdb, $fc_web_hook_logs_table;

            // mark record as deleted
            $updateQuery = "UPDATE {$fc_web_hook_logs_table} SET is_deleted = 1 WHERE is_deleted = 0";
            $wpdb->query($updateQuery);
            echo 1;
        } catch (\Exception $e) {
            // If an exception occurs, return the error message
            echo esc_html($e->getMessage());
        }
        die;
    }
}
