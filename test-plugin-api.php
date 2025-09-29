<?php
/**
 * Test the WordPress plugin API classes directly
 */

// Include the plugin files
require_once '/app/wp-content/plugins/barefoot-property-listings/includes/class-barefoot-api.php';
require_once '/app/wp-content/plugins/barefoot-property-listings/includes/class-property-sync.php';

echo "<h2>WordPress Plugin API Test</h2>\n\n";

// Define constants that the plugin expects
if (!defined('BAREFOOT_API_ENDPOINT')) {
    define('BAREFOOT_API_ENDPOINT', 'https://portals.barefoot.com/BarefootWebService/BarefootService.asmx');
    define('BAREFOOT_API_USERNAME', 'hfa20250814');
    define('BAREFOOT_API_PASSWORD', '#20250825@xcfvgrt!54687');
    define('BAREFOOT_API_VERSION', 'v3chfa0604');
}

try {
    echo "🔧 Testing Barefoot_API class...\n";
    
    $api = new Barefoot_API();
    
    // Test connection
    echo "🧪 Testing API connection...\n";
    $connection_test = $api->test_connection();
    
    if ($connection_test['success']) {
        echo "✅ Connection test successful: " . $connection_test['message'] . "\n\n";
        
        // Test getting properties
        echo "🏠 Testing GetAllProperty...\n";
        $properties_result = $api->get_all_properties();
        
        if ($properties_result['success']) {
            echo "✅ Properties retrieved successfully!\n";
            echo "   Properties count: " . $properties_result['count'] . "\n";
            
            if (!empty($properties_result['data'])) {
                $sample = $properties_result['data'][0];
                echo "   Sample property data available: " . (is_object($sample) ? 'Yes (object)' : 'Yes (array)') . "\n";
                
                // Try to access some fields
                if (is_object($sample)) {
                    echo "   Sample Property ID: " . (isset($sample->PropertyId) ? $sample->PropertyId : 'N/A') . "\n";
                    echo "   Sample Property Name: " . (isset($sample->PropertyName) ? $sample->PropertyName : 'N/A') . "\n";
                } elseif (is_array($sample)) {
                    echo "   Sample Property ID: " . (isset($sample['PropertyId']) ? $sample['PropertyId'] : 'N/A') . "\n";
                    echo "   Sample Property Name: " . (isset($sample['PropertyName']) ? $sample['PropertyName'] : 'N/A') . "\n";
                }
            }
            
            echo "\n🎯 Testing Property Sync (simulation)...\n";
            // Note: We won't actually sync to avoid creating WordPress posts
            echo "✅ Property sync class loaded successfully\n";
            echo "   Ready to sync " . $properties_result['count'] . " properties to WordPress\n";
            
        } else {
            echo "❌ Failed to get properties: " . $properties_result['message'] . "\n";
        }
        
    } else {
        echo "❌ Connection test failed: " . $connection_test['message'] . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Plugin test error: " . $e->getMessage() . "\n";
}

echo "\n================================================================================\n";
echo "Plugin API test completed at: " . date('Y-m-d H:i:s') . "\n";
?>