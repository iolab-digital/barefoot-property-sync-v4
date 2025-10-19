# Image Sync Troubleshooting Guide

## Issue: No Images Appearing in Media Library

### Step 1: Enable WordPress Debug Logging

Add to `wp-config.php`:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

### Step 2: Run Property Sync Again

1. Go to **WordPress Admin → Barefoot Properties → Sync Properties**
2. Click **"Sync All Properties"**
3. Wait for sync to complete

### Step 3: Check Debug Log

Location: `/wp-content/debug.log`

Look for these log entries:

#### ✅ Expected Log Entries (Working)

```
Barefoot Sync: Attempting to fetch images for property 123
Barefoot API: get_property_images called for property ID: 123
Barefoot API: Calling GetPropertyAllImgsXML with params: array(...)
Barefoot API: GetPropertyAllImgsXML response received: ...
Barefoot Sync: Image API response: array('success' => true, 'images' => ...)
Barefoot Sync: Starting image sync for property 123, found 5 images
Barefoot Sync: Downloading image from: https://...
Barefoot Sync: Set image 456 as featured image for property 123
Barefoot Sync: Successfully synced 5 images for property 123
```

#### ❌ Problem Indicators

**No API calls:**
```
Barefoot Sync: Attempting to fetch images for property 123
(nothing after this)
```
→ Issue: sync_property_images() method not being called or failing early

**API returns no images:**
```
Barefoot Sync: No images found for property 123
```
→ Issue: GetPropertyAllImgsXML returns empty or properties have no images

**Empty image URL:**
```
Barefoot Sync: Skipping image with empty URL. Image data: array(...)
```
→ Issue: XML parsing not extracting image URLs correctly

**Download failures:**
```
Image download error: HTTP 404
Image upload error: ...
```
→ Issue: Network problems or invalid image URLs

### Step 4: Test Image API Directly

Create a test file `test-images.php` in WordPress root:

```php
<?php
require_once('./wp-load.php');

// Test with first property
$args = array(
    'post_type' => 'barefoot_property',
    'posts_per_page' => 1,
    'post_status' => 'publish'
);

$properties = get_posts($args);

if (empty($properties)) {
    echo "No properties found!\n";
    exit;
}

$property = $properties[0];
$property_id = get_post_meta($property->ID, '_barefoot_property_id', true);

echo "Testing image fetch for property: {$property->post_title} (ID: {$property_id})\n\n";

// Initialize API
require_once(BAREFOOT_PLUGIN_DIR . 'includes/class-barefoot-api.php');
$api = new Barefoot_API();

// Fetch images
$result = $api->get_property_images($property_id);

echo "API Response:\n";
print_r($result);
echo "\n";

if ($result['success'] && !empty($result['images'])) {
    echo "✅ SUCCESS: Found " . count($result['images']) . " images\n";
    foreach ($result['images'] as $img) {
        echo "  - {$img['image_url']}\n";
    }
} else {
    echo "❌ FAILED: " . ($result['message'] ?? 'No images') . "\n";
}
```

Run from command line:
```bash
php test-images.php
```

### Step 5: Check Property IDs

The API needs valid property IDs. Check if properties have IDs:

```php
<?php
require_once('./wp-load.php');

$properties = get_posts(array(
    'post_type' => 'barefoot_property',
    'posts_per_page' => 10
));

foreach ($properties as $property) {
    $property_id = get_post_meta($property->ID, '_barefoot_property_id', true);
    echo "Property: {$property->post_title}\n";
    echo "  WordPress ID: {$property->ID}\n";
    echo "  Barefoot ID: {$property_id}\n\n";
}
```

If properties have no Barefoot ID, images can't be fetched.

### Step 6: Check API Method Availability

Test if GetPropertyAllImgsXML is available:

```php
<?php
require_once('./wp-load.php');
require_once(BAREFOOT_PLUGIN_DIR . 'includes/class-barefoot-api.php');

$api = new Barefoot_API();
$functions = $api->get_available_functions();

echo "Searching for image-related methods:\n\n";
foreach ($functions as $func) {
    if (stripos($func, 'img') !== false || stripos($func, 'image') !== false) {
        echo "- {$func}\n";
    }
}
```

Look for:
- `GetPropertyAllImgsXML`
- `GetPropertyAllImgs`
- Similar methods

### Step 7: Check File Permissions

WordPress uploads directory must be writable:

```bash
ls -la wp-content/uploads/
```

Should show permissions like `drwxr-xr-x` or similar.

Test write permissions:
```bash
touch wp-content/uploads/test.txt
rm wp-content/uploads/test.txt
```

If it fails, fix permissions:
```bash
chmod 755 wp-content/uploads/
```

### Step 8: Check PHP Configuration

Required PHP settings:

```php
<?php
phpinfo();
```

Look for:
- `allow_url_fopen = On` (for wp_remote_get)
- `max_execution_time >= 300` (5 minutes minimum)
- `memory_limit >= 256M`
- `upload_max_filesize >= 10M`
- `post_max_size >= 10M`

### Step 9: Manual Image Test

Test if images can be downloaded manually:

```php
<?php
require_once('./wp-load.php');

$test_url = 'https://example.com/test-image.jpg'; // Replace with actual image URL from Barefoot

echo "Testing image download from: {$test_url}\n\n";

$response = wp_remote_get($test_url, array(
    'timeout' => 30,
    'user-agent' => 'WordPress Test'
));

if (is_wp_error($response)) {
    echo "❌ FAILED: " . $response->get_error_message() . "\n";
} else {
    $code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    
    echo "✅ Response Code: {$code}\n";
    echo "✅ Body Size: " . strlen($body) . " bytes\n";
    
    if ($code == 200 && !empty($body)) {
        echo "✅ Image download works!\n";
    } else {
        echo "❌ Image not accessible\n";
    }
}
```

### Common Issues & Solutions

| Issue | Cause | Solution |
|-------|-------|----------|
| No log entries | Debug logging not enabled | Enable WP_DEBUG_LOG in wp-config.php |
| "SOAP client is null" | SOAP extension missing | Install PHP SOAP: `apt-get install php-soap` |
| "Failed to initialize" | API credentials wrong | Check credentials in wp-config.php |
| "No images found" | Properties have no images | Normal if Barefoot account has no images |
| "Empty URL" | XML parsing failed | Check API response format in debug log |
| "Download error 403" | Access denied | Check image URL authentication |
| "Download error 404" | Image doesn't exist | Image was deleted from Barefoot |
| "Upload error" | Permissions issue | Fix wp-content/uploads permissions |
| "Memory exhausted" | Large images | Increase PHP memory_limit |
| "Timeout" | Slow network | Increase max_execution_time |

### Quick Diagnostic Checklist

- [ ] WordPress debug logging enabled
- [ ] Debug log shows "Attempting to fetch images"
- [ ] Debug log shows "get_property_images called"
- [ ] Debug log shows API response with image data
- [ ] Properties have valid Barefoot property IDs
- [ ] GetPropertyAllImgsXML method exists in API
- [ ] wp-content/uploads directory is writable
- [ ] PHP allow_url_fopen is enabled
- [ ] No PHP errors in debug log
- [ ] Network can access Barefoot image URLs

### Get Help

If none of the above resolves the issue, provide:

1. **Debug log excerpt** (last 100 lines during sync)
2. **Property ID test** output
3. **API functions list** (image-related methods)
4. **PHP settings** (memory, execution time, allow_url_fopen)
5. **File permissions** for wp-content/uploads

This will help identify the exact issue preventing image sync.

---

**Updated:** January 2025  
**Version:** 1.2.0 with enhanced diagnostics
