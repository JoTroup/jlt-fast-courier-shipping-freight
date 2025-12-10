<?php
$page = isset($_GET['page_no']) ? esc_html($_GET['page_no']) : 1;
unset($_GET['page_no']);

$baseUrl = admin_url() . 'admin.php?' . http_build_query($_GET, '', '&amp;');
$nextPage = $page < $pages ? $page + 1 : $pages;
// Calculate the range of pages to display
$start_page = max(1, $page - 1);
$end_page = min($pages, $page + 1);
?>
<nav aria-label="Pagination">
    <ul class="pagination justify-content-end">
        <?php if ($page > 2) { ?>
            <li class="page-item">
                <a class="page-link text-primary" href="<?php echo esc_url($baseUrl) ?>&page_no=1" aria-label="Previous">
                    <span aria-hidden="true">&laquo;&laquo;</span>
                </a>
            </li>
        <?php } ?>
        <li class="page-item">
            <a class="page-link text-primary <?php echo esc_html($page == 1 ? 'btn disabled' : '') ?>" href="<?php echo esc_url($baseUrl) ?>&page_no=<?php echo esc_html($start_page) ?>" aria-label="Previous">
                <span aria-hidden="true">&laquo;</span>
            </a>
        </li>
        <?php for ($i = $start_page; $i <= $end_page; $i++) { ?>
            <li class="page-item"><a class="page-link text-primary <?php echo esc_html($i == $page ? 'btn disabled' : '') ?>" href="<?php echo esc_url($baseUrl) ?>&page_no=<?php echo esc_html($i) ?>"><?php echo esc_html($i) ?></a></li>
        <?php } ?>
        <li class="page-item">
            <a class="page-link text-primary <?php echo esc_html($page == $pages ? 'btn disabled' : '') ?>" href="<?php echo esc_url($baseUrl) ?>&page_no=<?php echo esc_html($nextPage) ?>" aria-label="Next">
                <span aria-hidden="true">&raquo;</span>
            </a>
        </li>
        <?php if ($pages > $nextPage) { ?>
            <li class="page-item">
                <a class="page-link text-primary" href="<?php echo esc_url($baseUrl) ?>&page_no=<?php echo esc_html($pages) ?>" aria-label="Next">
                    <span aria-hidden="true">&raquo;&raquo;</span>
                </a>
            </li>
        <?php } ?>
    </ul>
</nav>