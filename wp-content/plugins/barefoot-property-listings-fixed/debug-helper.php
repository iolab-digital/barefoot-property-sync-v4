<?php
/**
 * Debug Helper for Barefoot Plugin
 * Add this as a WordPress page or run directly to debug API response
 */

// Include WordPress if running standalone
if (!defined('ABSPATH')) {
    // Adjust this path to your WordPress installation
    require_once('../../../wp-config.php');
}

// Make sure our plugin classes are loaded
if (!class_exists('Barefoot_API')) {
    require_once('includes/class-barefoot-api.php');
}

echo "<h1>Barefoot API Debug Helper</h1>";

try {
    $api = new Barefoot_API();
    
    echo "<h2>1. Testing API Connection</h2>";
    $connection = $api->test_connection();
    echo "<p>Connection: " . ($connection['success'] ? '✅ Success' : '❌ Failed') . "</p>";
    echo "<p>Message: " . esc_html($connection['message']) . "</p>";
    
    if ($connection['success']) {
        echo "<h2>2. Getting Properties</h2>";
        $properties = $api->get_all_properties();
        
        echo "<p>Properties Retrieved: " . ($properties['success'] ? '✅ Success' : '❌ Failed') . "</p>";
        echo "<p>Count: " . $properties['count'] . "</p>";
        
        if ($properties['success'] && !empty($properties['data'])) {
            echo "<h3>Sample Property Data:</h3>";
            echo "<pre>" . esc_html(print_r($properties['data'][0], true)) . "</pre>";
            
            echo "<h3>All Available Fields in First Property:</h3>";
            $sample = $properties['data'][0];
            $fields = array();
            
            if (is_object($sample)) {
                $fields = array_keys(get_object_vars($sample));
            } elseif (is_array($sample)) {
                $fields = array_keys($sample);
            }
            
            echo "<ul>";
            foreach ($fields as $field) {
                $value = is_object($sample) ? $sample->$field : $sample[$field];
                echo "<li><strong>" . esc_html($field) . ":</strong> " . esc_html(substr($value, 0, 100)) . (strlen($value) > 100 ? '...' : '') . "</li>";
            }
            echo "</ul>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . esc_html($e->getMessage()) . "</p>";
}

echo "<h2>3. Check Debug Logs</h2>";
echo "<p>Check your WordPress debug.log file for detailed API response information.</p>";
echo "<p>Log location: <code>" . (defined('WP_CONTENT_DIR') ? WP_CONTENT_DIR : '/wp-content') . "/debug.log</code></p>";

?>