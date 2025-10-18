<?php
/**
 * Debug AJAX setup - Add this temporarily to check what's happening
 */

// Add this to functions.php or create a simple plugin to test
add_action('wp_ajax_barefoot_debug_test', function() {
    wp_send_json_success(array(
        'message' => 'AJAX is working!',
        'time' => current_time('mysql')
    ));
});

// Add debug JavaScript to admin footer
add_action('admin_footer', function() {
    if (isset($_GET['page']) && strpos($_GET['page'], 'barefoot') !== false) {
        ?>
        <script>
        jQuery(document).ready(function($) {
            console.log('=== BAREFOOT DEBUG INFO ===');
            console.log('jQuery loaded:', typeof $ !== 'undefined');
            console.log('ajaxurl available:', typeof ajaxurl !== 'undefined' ? ajaxurl : 'NOT DEFINED');
            console.log('barefoot_ajax available:', typeof barefoot_ajax !== 'undefined');
            
            if (typeof barefoot_ajax !== 'undefined') {
                console.log('barefoot_ajax contents:', barefoot_ajax);
            }
            
            console.log('Sync button exists:', $('#sync-all-properties').length > 0);
            console.log('Test connection button exists:', $('#test-api-connection').length > 0);
            console.log('Test get properties button exists:', $('#test-get-properties').length > 0);
            
            // Test a simple AJAX call
            console.log('Testing basic AJAX...');
            $.post(ajaxurl || barefoot_ajax.ajax_url, {
                action: 'barefoot_debug_test'
            })
            .done(function(response) {
                console.log('Basic AJAX test SUCCESS:', response);
            })
            .fail(function(xhr, status, error) {
                console.error('Basic AJAX test FAILED:', status, error);
                console.error('Response text:', xhr.responseText);
            });
        });
        </script>
        <?php
    }
});
?>