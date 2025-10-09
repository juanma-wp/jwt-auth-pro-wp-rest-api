<?php
// Enable pretty permalinks automatically when wp-env starts.
add_action('init', function () {
    $desired_structure = '/%postname%/';
    if (get_option('permalink_structure') !== $desired_structure) {
        update_option('permalink_structure', $desired_structure);
        flush_rewrite_rules();
        error_log('Permalinks updated and flushed.');
    }
});
