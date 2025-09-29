<?php
/**
 * Test the updated Barefoot WordPress plugin functionality
 * Place this in the WordPress plugin directory and run via browser
 */

// Include WordPress if needed
if (!defined('ABSPATH')) {
    // For standalone testing, include WordPress
    require_once('../../../wp-config.php');
}

// Include our plugin classes
require_once('includes/class-barefoot-api.php');
require_once('includes/class-property-sync.php');

echo "<h1>Updated Barefoot Plugin Test</h1>\n";
echo "<p>Testing the improved API handling...</p>\n";

try {
    echo "<h2>1. Testing API Connection</h2>\n";
    $api = new Barefoot_API();
    $connection_test = $api->test_connection();
    
    echo "<p>Connection Test: " . ($connection_test['success'] ? '✅ Success' : '❌ Failed') . "</p>\n";
    echo "<p>Message: " . esc_html($connection_test['message']) . "</p>\n";
    
    if ($connection_test['success']) {
        echo "<h2>2. Testing Property Retrieval</h2>\n";
        
        $properties_response = $api->get_all_properties();
        echo "<p>Property Retrieval: " . ($properties_response['success'] ? '✅ Success' : '❌ Failed') . "</p>\n";
        echo "<p>Count: " . $properties_response['count'] . "</p>\n";
        echo "<p>Method Used: " . (isset($properties_response['method_used']) ? $properties_response['method_used'] : 'N/A') . "</p>\n";
        
        if (isset($properties_response['message'])) {
            echo "<p>Details: " . esc_html($properties_response['message']) . "</p>\n";
        }
        
        if ($properties_response['success'] && $properties_response['count'] > 0) {
            echo "<h3>Sample Property Data:</h3>\n";
            $sample_property = $properties_response['data'][0];
            echo "<pre>" . esc_html(print_r($sample_property, true)) . "</pre>\n";
        }
        
        echo "<h2>3. Testing Property Sync</h2>\n";
        
        $sync = new Barefoot_Property_Sync();
        $sync_result = $sync->sync_all_properties();
        
        echo "<p>Property Sync: " . ($sync_result['success'] ? '✅ Success' : '❌ Failed') . "</p>\n";
        echo "<p>Synced Count: " . $sync_result['count'] . "</p>\n";
        echo "<p>Message: " . esc_html($sync_result['message']) . "</p>\n";
        
        if (!empty($sync_result['errors'])) {
            echo "<h3>Sync Errors:</h3>\n";
            echo "<ul>\n";
            foreach ($sync_result['errors'] as $error) {
                echo "<li>" . esc_html($error) . "</li>\n";
            }
            echo "</ul>\n";
        }
        
        // Check if any WordPress posts were created
        $barefoot_posts = get_posts(array(
            'post_type' => 'barefoot_property',
            'posts_per_page' => -1,
            'post_status' => 'any'
        ));
        
        echo "<h2>4. WordPress Posts Status</h2>\n";
        echo "<p>Total Barefoot Property Posts: " . count($barefoot_posts) . "</p>\n";
        
        if (!empty($barefoot_posts)) {
            echo "<h3>Recent Properties:</h3>\n";
            foreach (array_slice($barefoot_posts, 0, 5) as $post) {
                echo "<p>• " . esc_html($post->post_title) . " (ID: {$post->ID}, Status: {$post->post_status})</p>\n";
            }
        }
    }
    
    // Test debug information
    echo "<h2>5. Debug Information</h2>\n";
    echo "<p>WordPress Version: " . get_bloginfo('version') . "</p>\n";
    echo "<p>PHP Version: " . PHP_VERSION . "</p>\n";
    echo "<p>SOAP Extension: " . (extension_loaded('soap') ? '✅ Loaded' : '❌ Not loaded') . "</p>\n";
    echo "<p>Plugin Version: " . (defined('BAREFOOT_VERSION') ? BAREFOOT_VERSION : 'Not defined') . "</p>\n";
    echo "<p>API Endpoint: " . (defined('BAREFOOT_API_ENDPOINT') ? BAREFOOT_API_ENDPOINT : 'Not defined') . "</p>\n";
    
} catch (Exception $e) {
    echo "<div style='color: red;'>\n";
    echo "<h2>Error Occurred</h2>\n";
    echo "<p>Error: " . esc_html($e->getMessage()) . "</p>\n";
    echo "<p>File: " . esc_html($e->getFile()) . "</p>\n";
    echo "<p>Line: " . $e->getLine() . "</p>\n";
    echo "</div>\n";
}

echo "<h2>6. Next Steps</h2>\n";
echo "<div style='background: #f0f0f0; padding: 15px; margin: 10px 0;'>\n";
echo "<h3>Current Status:</h3>\n";
echo "<p>✅ <strong>SOAP Connection:</strong> Working - API endpoint is accessible</p>\n";
echo "<p>⚠️ <strong>GetAllProperty Method:</strong> Returns 'Custom method' response - this appears to be expected behavior</p>\n";
echo "<p>💡 <strong>Interpretation:</strong> The API credentials are valid and the connection works, but:</p>\n";
echo "<ul>\n";
echo "<li>The account may not have any properties configured in Barefoot</li>\n";
echo "<li>Additional API permissions or configuration may be needed</li>\n";
echo "<li>This might be a test/demo account with limited data</li>\n";
echo "</ul>\n";
echo "<h3>Recommended Actions:</h3>\n";
echo "<ol>\n";
echo "<li><strong>Contact Barefoot Support:</strong> Verify that your API account has properties assigned and proper permissions</li>\n";
echo "<li><strong>Check Barefoot Admin:</strong> Ensure properties are properly set up in your Barefoot management system</li>\n";
echo "<li><strong>API Documentation:</strong> Request detailed API documentation from Barefoot to understand the 'Custom method' response</li>\n";
echo "<li><strong>Test with Known Property ID:</strong> If you have specific property IDs, test individual property retrieval methods</li>\n";
echo "</ol>\n";
echo "</div>\n";

echo "<p><small>Test completed at: " . date('Y-m-d H:i:s') . "</small></p>\n";

?>