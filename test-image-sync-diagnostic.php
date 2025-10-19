<?php
/**
 * Comprehensive Image Sync Diagnostic Test
 * 
 * Instructions:
 * 1. Upload this file to your WordPress root directory
 * 2. Visit: http://yoursite.com/test-image-sync-diagnostic.php
 * 3. Share the output with me
 */

// Load WordPress
require_once('./wp-load.php');

?>
<!DOCTYPE html>
<html>
<head>
    <title>Barefoot Image Sync Diagnostic</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .test { background: white; padding: 15px; margin: 10px 0; border-left: 4px solid #0073aa; }
        .success { border-left-color: #46b450; }
        .error { border-left-color: #dc3232; }
        .warning { border-left-color: #ffb900; }
        pre { background: #f0f0f0; padding: 10px; overflow-x: auto; }
        h2 { color: #0073aa; }
        .status { font-weight: bold; }
    </style>
</head>
<body>
    <h1>🔍 Barefoot Image Sync Diagnostic Test</h1>
    
<?php

// Test 1: Plugin exists
echo '<div class="test">';
echo '<h2>Test 1: Plugin Files</h2>';

if (!defined('BAREFOOT_PLUGIN_DIR')) {
    echo '<p class="status error">❌ BAREFOOT_PLUGIN_DIR not defined</p>';
    echo '<p>Plugin may not be activated</p>';
    exit;
} else {
    echo '<p class="status success">✅ Plugin directory: ' . BAREFOOT_PLUGIN_DIR . '</p>';
}

$api_file = BAREFOOT_PLUGIN_DIR . 'includes/class-barefoot-api.php';
$sync_file = BAREFOOT_PLUGIN_DIR . 'includes/class-property-sync.php';

if (file_exists($api_file)) {
    echo '<p class="status success">✅ API class file exists</p>';
} else {
    echo '<p class="status error">❌ API class file missing</p>';
}

if (file_exists($sync_file)) {
    echo '<p class="status success">✅ Sync class file exists</p>';
} else {
    echo '<p class="status error">❌ Sync class file missing</p>';
}

echo '</div>';

// Test 2: Load classes
echo '<div class="test">';
echo '<h2>Test 2: Load Classes</h2>';

require_once($api_file);
require_once($sync_file);

echo '<p class="status success">✅ Classes loaded successfully</p>';
echo '</div>';

// Test 3: Get a property
echo '<div class="test">';
echo '<h2>Test 3: Find Test Property</h2>';

$properties = get_posts(array(
    'post_type' => 'barefoot_property',
    'posts_per_page' => 1,
    'post_status' => 'publish'
));

if (empty($properties)) {
    echo '<p class="status error">❌ No properties found. Run property sync first!</p>';
    exit;
}

$property = $properties[0];
$property_id = get_post_meta($property->ID, '_barefoot_property_id', true);

echo '<p class="status success">✅ Found property: ' . esc_html($property->post_title) . '</p>';
echo '<p>WordPress Post ID: ' . $property->ID . '</p>';
echo '<p>Barefoot Property ID: ' . esc_html($property_id) . '</p>';

if (empty($property_id)) {
    echo '<p class="status error">❌ Property has no Barefoot ID! Cannot fetch images.</p>';
    exit;
}

echo '</div>';

// Test 4: Initialize API
echo '<div class="test">';
echo '<h2>Test 4: Initialize Barefoot API</h2>';

try {
    $api = new Barefoot_API();
    echo '<p class="status success">✅ API instance created</p>';
} catch (Exception $e) {
    echo '<p class="status error">❌ Failed to create API instance: ' . esc_html($e->getMessage()) . '</p>';
    exit;
}

echo '</div>';

// Test 5: Fetch images
echo '<div class="test">';
echo '<h2>Test 5: Fetch Images from API</h2>';

echo '<p>Calling get_property_images(' . esc_html($property_id) . ')...</p>';

try {
    $result = $api->get_property_images($property_id);
    
    echo '<p class="status">API Response:</p>';
    echo '<pre>' . print_r($result, true) . '</pre>';
    
    if ($result['success']) {
        echo '<p class="status success">✅ API call successful</p>';
        
        if (empty($result['images'])) {
            echo '<p class="status warning">⚠️ No images returned for this property</p>';
        } else {
            echo '<p class="status success">✅ Found ' . count($result['images']) . ' images</p>';
            
            // Display first 3 images
            $display_count = min(3, count($result['images']));
            for ($i = 0; $i < $display_count; $i++) {
                $img = $result['images'][$i];
                echo '<div style="margin: 10px 0; padding: 10px; background: #f9f9f9;">';
                echo '<strong>Image ' . ($i + 1) . ':</strong><br>';
                echo 'URL: ' . esc_html($img['image_url']) . '<br>';
                echo 'Description: ' . esc_html($img['description'] ?: '(empty)') . '<br>';
                echo 'Image No: ' . esc_html($img['image_no']) . '<br>';
                echo '</div>';
            }
            
            if (count($result['images']) > 3) {
                echo '<p>... and ' . (count($result['images']) - 3) . ' more images</p>';
            }
        }
    } else {
        echo '<p class="status error">❌ API call failed: ' . esc_html($result['message']) . '</p>';
    }
    
} catch (Exception $e) {
    echo '<p class="status error">❌ Exception: ' . esc_html($e->getMessage()) . '</p>';
}

echo '</div>';

// Test 6: Test image download
if ($result['success'] && !empty($result['images'])) {
    echo '<div class="test">';
    echo '<h2>Test 6: Test Image Download</h2>';
    
    $test_url = $result['images'][0]['image_url'];
    echo '<p>Testing download from: ' . esc_html($test_url) . '</p>';
    
    $response = wp_remote_get($test_url, array(
        'timeout' => 30,
        'user-agent' => 'WordPress Barefoot Plugin Test'
    ));
    
    if (is_wp_error($response)) {
        echo '<p class="status error">❌ Download failed: ' . esc_html($response->get_error_message()) . '</p>';
    } else {
        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $size = strlen($body);
        
        echo '<p>HTTP Response Code: ' . $code . '</p>';
        echo '<p>Downloaded Size: ' . number_format($size) . ' bytes</p>';
        
        if ($code == 200 && $size > 0) {
            echo '<p class="status success">✅ Image download successful!</p>';
            
            // Display the image
            echo '<p>Image preview:</p>';
            echo '<img src="' . esc_url($test_url) . '" style="max-width: 400px; border: 1px solid #ddd;" />';
        } else {
            echo '<p class="status error">❌ Image not accessible (code: ' . $code . ', size: ' . $size . ')</p>';
        }
    }
    
    echo '</div>';
}

// Test 7: Check uploads directory
echo '<div class="test">';
echo '<h2>Test 7: WordPress Uploads Directory</h2>';

$upload_dir = wp_upload_dir();
echo '<p>Uploads Path: ' . esc_html($upload_dir['path']) . '</p>';
echo '<p>Uploads URL: ' . esc_html($upload_dir['url']) . '</p>';

if (is_writable($upload_dir['path'])) {
    echo '<p class="status success">✅ Uploads directory is writable</p>';
} else {
    echo '<p class="status error">❌ Uploads directory is NOT writable!</p>';
    echo '<p>Fix with: chmod 755 ' . esc_html($upload_dir['path']) . '</p>';
}

echo '</div>';

// Test 8: Test actual sync
if ($result['success'] && !empty($result['images'])) {
    echo '<div class="test">';
    echo '<h2>Test 8: Test Actual Image Sync</h2>';
    
    echo '<p>Attempting to sync images for property...</p>';
    
    try {
        $sync = new Barefoot_Property_Sync();
        
        // Get private method using reflection
        $reflection = new ReflectionClass($sync);
        $method = $reflection->getMethod('sync_property_images');
        $method->setAccessible(true);
        
        // Call the method
        $method->invoke($sync, $property->ID, $property_id);
        
        echo '<p class="status success">✅ sync_property_images() executed without errors</p>';
        
        // Check if featured image was set
        $thumbnail_id = get_post_thumbnail_id($property->ID);
        if ($thumbnail_id) {
            echo '<p class="status success">✅ Featured image set! (Attachment ID: ' . $thumbnail_id . ')</p>';
            $thumb_url = wp_get_attachment_url($thumbnail_id);
            echo '<img src="' . esc_url($thumb_url) . '" style="max-width: 200px; border: 1px solid #ddd;" />';
        } else {
            echo '<p class="status warning">⚠️ No featured image set yet</p>';
        }
        
        // Check attachments
        $attachments = get_posts(array(
            'post_type' => 'attachment',
            'post_parent' => $property->ID,
            'posts_per_page' => -1
        ));
        
        echo '<p>Attachments found: ' . count($attachments) . '</p>';
        
        if (count($attachments) > 0) {
            echo '<p class="status success">✅ Images were attached to property!</p>';
            foreach ($attachments as $att) {
                echo '<p>- ' . esc_html($att->post_title) . ' (ID: ' . $att->ID . ')</p>';
            }
        } else {
            echo '<p class="status error">❌ No images attached to property</p>';
        }
        
    } catch (Exception $e) {
        echo '<p class="status error">❌ Sync failed: ' . esc_html($e->getMessage()) . '</p>';
        echo '<pre>' . esc_html($e->getTraceAsString()) . '</pre>';
    }
    
    echo '</div>';
}

// Test 9: Check debug log
echo '<div class="test">';
echo '<h2>Test 9: Debug Log Check</h2>';

if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
    echo '<p class="status success">✅ Debug logging is enabled</p>';
    
    $log_file = WP_CONTENT_DIR . '/debug.log';
    if (file_exists($log_file)) {
        echo '<p class="status success">✅ Debug log file exists</p>';
        echo '<p>Location: ' . $log_file . '</p>';
        
        // Get last 20 lines
        $lines = file($log_file);
        $last_lines = array_slice($lines, -20);
        
        echo '<p>Last 20 log entries:</p>';
        echo '<pre style="max-height: 300px; overflow-y: scroll;">';
        foreach ($last_lines as $line) {
            if (stripos($line, 'barefoot') !== false) {
                echo '<strong>' . esc_html($line) . '</strong>';
            } else {
                echo esc_html($line);
            }
        }
        echo '</pre>';
    } else {
        echo '<p class="status warning">⚠️ Debug log file does not exist yet</p>';
    }
} else {
    echo '<p class="status warning">⚠️ Debug logging is NOT enabled</p>';
    echo '<p>Enable in wp-config.php:</p>';
    echo '<pre>define(\'WP_DEBUG\', true);
define(\'WP_DEBUG_LOG\', true);</pre>';
}

echo '</div>';

?>

<div class="test">
    <h2>✅ Diagnostic Complete</h2>
    <p>Please share this entire page output for further troubleshooting.</p>
</div>

</body>
</html>
