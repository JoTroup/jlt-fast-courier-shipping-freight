<?php
global $slug;
if (WP_ENV == 'STAGING') {
?>
    <div class="test-mode-header <?php echo is_test_mode_active() ? 'bg-light border-orange text-dark' : '' ?>">
        <span class="mr-2  tooltip-hover w-fit-content" data-tooltip="This allows you to safely test the app without impacting real data or live transactions.">Test mode</span>
        <label class="switch">
            <input type="checkbox" onchange="toggleTestMode()" <?php echo is_test_mode_active() ? 'checked' : '' ?>>
            <span class="slider round"></span>
        </label>

        <?php
        if (is_test_mode_active()) {
        ?>
            <div class="test-mode-label">
                <span>
                    <p class="m-0">Test Mode</p>
                </span>
            </div>
        <?php
        }
        ?>
    </div>
    <div id="custom-tooltip"></div>
    <script>
        function toggleTestMode() {
            toggleLoader();
            const loginUrl = "<?php echo esc_url(admin_url('admin.php?page=' . esc_html($slug)))  ?>";
            const apiUrl = "<?php echo WP_API_URL ?>?action=toggle_test_mode";
            jQuery.ajax({
                url: apiUrl,
                success: function(result) {
                    if (result.success) {
                        location.href = loginUrl;
                        window.location.reload()
                    } else {
                        toggleLoader();
                    }
                },
                error: function(err) {
                    Swal.fire('', 'Server Error', 'error');
                    toggleLoader();
                }
            })
        }
    </script>
<?php } ?>