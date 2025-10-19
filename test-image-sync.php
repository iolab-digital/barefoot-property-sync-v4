<?php
/**
 * Test script for Barefoot Property Image Synchronization
 * Tests GetPropertyAllImgsXML API method and image parsing
 */

// Load WordPress
require_once(__DIR__ . '/barefoot-property-listings/includes/class-barefoot-api.php');

// Define constants if not already defined
if (!defined('BAREFOOT_API_ENDPOINT')) {
    define('BAREFOOT_API_ENDPOINT', 'https://portals.barefoot.com/barefootwebservice/BarefootService.asmx');
}
if (!defined('BAREFOOT_API_USERNAME')) {
    define('BAREFOOT_API_USERNAME', 'hfa20250814');
}
if (!defined('BAREFOOT_API_PASSWORD')) {
    define('BAREFOOT_API_PASSWORD', '#20250825@xcfvgrt!54687');
}
if (!defined('BAREFOOT_API_ACCOUNT')) {
    define('BAREFOOT_API_ACCOUNT', 'v3chfa0604');
}
if (!defined('BAREFOOT_VERSION')) {
    define('BAREFOOT_VERSION', '1.1.0');
}

echo "===========================================\n";
echo "Barefoot Property Image Sync Test\n";
echo "===========================================\n\n";

// Initialize API
$api = new Barefoot_API();

// First, get properties to find valid property IDs
echo "Step 1: Fetching properties to get valid IDs...\n";
$properties_response = $api->get_all_properties();

if (!$properties_response['success']) {
    echo "❌ Failed to fetch properties: " . $properties_response['message'] . "\n";
    exit(1);
}

if (empty($properties_response['data'])) {
    echo "❌ No properties found to test image sync\n";
    exit(1);
}

$properties = $properties_response['data'];
echo "✅ Found " . count($properties) . " properties\n\n";

// Test image fetching for first 3 properties
$test_count = min(3, count($properties));
echo "Step 2: Testing image sync for first {$test_count} properties...\n\n";

$total_images = 0;

for ($i = 0; $i < $test_count; $i++) {
    $property = $properties[$i];
    
    // Get property ID
    $property_id = null;
    if (is_object($property)) {
        $property_id = $property->PropertyID ?? $property->propertyid ?? $property->ID ?? null;
        $property_name = $property->Name ?? $property->name ?? $property->PropertyName ?? 'Unknown';
    } elseif (is_array($property)) {
        $property_id = $property['PropertyID'] ?? $property['propertyid'] ?? $property['ID'] ?? null;
        $property_name = $property['Name'] ?? $property['name'] ?? $property['PropertyName'] ?? 'Unknown';
    }
    
    if (!$property_id) {
        echo "⚠️  Property " . ($i + 1) . ": Could not extract property ID\n\n";
        continue;
    }
    
    echo "-------------------------------------------\n";
    echo "Property " . ($i + 1) . ": {$property_name} (ID: {$property_id})\n";
    echo "-------------------------------------------\n";
    
    // Fetch images
    $images_response = $api->get_property_images($property_id);
    
    if (!$images_response['success']) {
        echo "❌ Failed to fetch images: " . $images_response['message'] . "\n\n";
        continue;
    }
    
    if (empty($images_response['images'])) {
        echo "⚠️  No images found for this property\n\n";
        continue;
    }
    
    $images = $images_response['images'];
    $image_count = count($images);
    $total_images += $image_count;
    
    echo "✅ Found {$image_count} images\n";
    
    // Display first 3 images
    $display_count = min(3, $image_count);
    for ($j = 0; $j < $display_count; $j++) {
        $image = $images[$j];
        echo "  Image " . ($j + 1) . ":\n";
        echo "    - URL: " . ($image['image_url'] ?? 'N/A') . "\n";
        echo "    - Image No: " . ($image['image_no'] ?? 'N/A') . "\n";
        echo "    - Description: " . ($image['description'] ?? 'N/A') . "\n";
    }
    
    if ($image_count > 3) {
        echo "  ... and " . ($image_count - 3) . " more images\n";
    }
    
    echo "\n";
}

echo "===========================================\n";
echo "Test Summary\n";
echo "===========================================\n";
echo "Properties tested: {$test_count}\n";
echo "Total images found: {$total_images}\n";
echo "\n";

if ($total_images > 0) {
    echo "✅ SUCCESS: Image fetching is working!\n";
    echo "   Images can be downloaded and attached to WordPress posts.\n";
} else {
    echo "⚠️  WARNING: No images found across tested properties.\n";
    echo "   This might indicate:\n";
    echo "   - Properties don't have images in Barefoot system\n";
    echo "   - Different property IDs need to be tested\n";
    echo "   - API permissions issue\n";
}

echo "\n";
