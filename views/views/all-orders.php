<?php
session_start();
// Get the 'tab' parameter from the URL
$current_tab = !empty($_GET['tab']) ? sanitize_text_field($_GET['tab']) : '';

$_SESSION['tab'] = !empty($_GET['tab']) ? sanitize_text_field($_GET['tab']) : $_SESSION['tab'] ?? 'newOrders';
?>
<div class="contai ner mt-4">

    <ul class="nav nav-tabs" id="myTab" role="tablist">
        <li class="nav-item" role="presentation">
            <a onclick="document.querySelector('.loader').classList.add('active');" class="nav-link <?php echo ($_SESSION['tab'] === 'newOrders' || !$_SESSION['tab']) ? 'active' : ''; ?>"
                id="home-tab"
                href="<?php echo esc_url(admin_url('admin.php?page=fast-courier-all-orders&tab=newOrders')); ?>"
                aria-controls="home" aria-selected="true">
                New Orders
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a onclick="document.querySelector('.loader').classList.add('active');" class="nav-link <?php echo ($_SESSION['tab'] === 'processedOrders') ? 'active' : ''; ?>" id="profile-tab"
                href="<?php echo esc_url(admin_url('admin.php?page=fast-courier-all-orders&tab=processedOrders')); ?>"
                role="tab" aria-controls="profile" aria-selected="false">Processed Orders</a>
        </li>
        <li class="nav-item" role="presentation">
            <a onclick="document.querySelector('.loader').classList.add('active');" class="nav-link <?php echo ($_SESSION['tab'] === 'holdOrders') ? 'active' : ''; ?>" id="contact-tab"
                href="<?php echo esc_url(admin_url('admin.php?page=fast-courier-all-orders&tab=holdOrders')); ?>"
                role="tab" aria-controls="contact" aria-selected="false">Hold Orders</a>
        </li>
        <li class="nav-item" role="presentation">
            <a onclick="document.querySelector('.loader').classList.add('active');" class="nav-link <?php echo ($_SESSION['tab'] === 'rejectedOrders') ? 'active' : ''; ?>" id="contact-tab"
                href="<?php echo esc_url(admin_url('admin.php?page=fast-courier-all-orders&tab=rejectedOrders')); ?>"
                role="tab" aria-controls="contact" aria-selected="false">Rejected Orders</a>
        </li>
        <li class="nav-item" role="presentation">
            <a onclick="document.querySelector('.loader').classList.add('active');" class="nav-link <?php echo ($_SESSION['tab'] === 'fallbackOrders') ? 'active' : ''; ?>" id="contact-tab"
                href="<?php echo esc_url(admin_url('admin.php?page=fast-courier-all-orders&tab=fallbackOrders')); ?>"
                role="tab" aria-controls="contact" aria-selected="false">Fallback Orders</a>
        </li>
        <li class="nav-item" role="presentation">
            <a onclick="document.querySelector('.loader').classList.add('active');" class="nav-link <?php echo ($_SESSION['tab'] === 'flatrateOrders') ? 'active' : ''; ?>" id="contact-tab"
                href="<?php echo esc_url(admin_url('admin.php?page=fast-courier-all-orders&tab=flatrateOrders')); ?>"
                role="tab" aria-controls="contact" aria-selected="false">Flat Rate Orders</a>
        </li>
        <li class="nav-item" role="presentation">
            <a onclick="document.querySelector('.loader').classList.add('active');" class="nav-link <?php echo ($_SESSION['tab'] === 'otherOrders') ? 'active' : ''; ?>" id="contact-tab"
                href="<?php echo esc_url(admin_url('admin.php?page=fast-courier-all-orders&tab=otherOrders')); ?>"
                role="tab" aria-controls="contact" aria-selected="false">Other Orders</a>
        </li>
    </ul>
    <div class="mt-4"></div>
    <?php if ($_SESSION['tab'] === 'newOrders' || !$_SESSION['tab']) {
        include 'orders.php';
    } elseif ($_SESSION['tab'] === 'processedOrders') {
        include 'processed-orders.php';
    } elseif ($_SESSION['tab'] === 'holdOrders') {
        include 'hold-orders.php';
    } elseif ($_SESSION['tab'] === 'rejectedOrders') {
        include 'unprocessed-orders.php';
    } elseif ($_SESSION['tab'] === 'fallbackOrders') {
        include 'fallback-orders.php';
    } elseif ($_SESSION['tab'] === 'flatrateOrders') {
        include 'flatrate-orders.php';
    } elseif ($_SESSION['tab'] === 'otherOrders') {
        include 'other-orders.php';
    }
    ?>
</div>
<style>
    .nav-link {
        color: #ff6900
    }

    .nav-link:hover {
        color: #ff6900;
    }
</style>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>