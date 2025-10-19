# Property Image Synchronization - Technical Implementation

## Overview

The Barefoot Property Listings plugin now includes automatic image synchronization that downloads property images from the Barefoot API and integrates them with the WordPress media library.

## Implementation Details

### 1. API Method: GetPropertyAllImgsXML

**Location:** `/includes/class-barefoot-api.php`  
**Method:** `get_property_images($property_id)`  
**Lines:** 565-619

The API method makes a SOAP call to `GetPropertyAllImgsXML` with the following parameters:
- `username`: API authentication
- `password`: API authentication  
- `barefootAccount`: Account identifier
- `propertyId`: The specific property ID to fetch images for

**Returns:**
```php
array(
    'success' => true/false,
    'images' => array(
        array(
            'property_id' => '123',
            'image_no' => 1,
            'image_url' => 'https://...',
            'description' => 'Living room'
        ),
        // ... more images
    ),
    'count' => 5
)
```

### 2. XML Parsing

**Location:** `/includes/class-barefoot-api.php`  
**Method:** `parse_property_images_xml($xml_string)`  
**Lines:** 624-668

Parses the XML response from GetPropertyAllImgsXML API:

```xml
<Property>
    <PropertyImg>
        <propertyId>123</propertyId>
        <imageNo>1</imageNo>
        <imagepath>https://example.com/image1.jpg</imagepath>
        <imageDesc>Living room view</imageDesc>
    </PropertyImg>
    <PropertyImg>
        <!-- more images -->
    </PropertyImg>
</Property>
```

The parser:
- Extracts `PropertyImg` nodes using XPath
- Maps fields to standardized array keys
- Sorts images by `image_no` 
- Removes entries with empty URLs

### 3. Property Sync Integration

**Location:** `/includes/class-property-sync.php`  
**Method:** `sync_single_property($property_data)`  
**Line:** 157

Image sync is called automatically during property synchronization:

```php
// Sync property images
$this->sync_property_images($post_id, $property_id);
```

This ensures images are downloaded and attached every time properties are synced.

### 4. Image Download & Attachment

**Location:** `/includes/class-property-sync.php`  
**Method:** `sync_property_images($post_id, $property_id)`  
**Lines:** 327-375

The sync process:

1. **Fetches images** from API using `get_property_images()`
2. **Loops through each image** returned
3. **Downloads image** using `download_and_attach_image()`
4. **Sets featured image** - first image becomes post thumbnail
5. **Logs progress** for debugging

**Method:** `download_and_attach_image($image_url, $post_id, $caption)`  
**Lines:** 368-434

The download process:

1. **Checks for duplicates** using `find_existing_attachment()`
   - Looks up by original URL in post meta
   - Prevents re-downloading same image
   
2. **Downloads image** using WordPress `wp_remote_get()`
   - 30-second timeout
   - Custom user agent for tracking
   - Error handling for failed downloads
   
3. **Uploads to WordPress** using `wp_upload_bits()`
   - Saves to WordPress uploads directory
   - Preserves original filename when possible
   - Generates fallback filename if needed
   
4. **Creates attachment** using `wp_insert_attachment()`
   - Sets proper MIME type
   - Adds caption as post excerpt
   - Links to parent property post
   
5. **Generates metadata** using `wp_generate_attachment_metadata()`
   - Creates thumbnail sizes
   - Stores image dimensions
   - WordPress standard processing
   
6. **Stores original URL** in post meta
   - Key: `_barefoot_original_url`
   - Used for duplicate detection

### 5. Duplicate Detection

**Location:** `/includes/class-property-sync.php`  
**Method:** `find_existing_attachment($image_url, $post_id)`  
**Lines:** 471-481

Prevents downloading the same image twice:

```php
$attachments = get_posts(array(
    'post_type' => 'attachment',
    'meta_key' => '_barefoot_original_url',
    'meta_value' => $image_url,
    'post_parent' => $post_id,
    'posts_per_page' => 1
));
```

If an attachment with the same original URL exists for this property, it's reused instead of re-downloaded.

## Integration Flow

```
User clicks "Sync Properties"
    ↓
sync_all_properties() - Gets all properties from API
    ↓
sync_single_property() - For each property:
    ↓
    1. Create/update property post
    2. Set taxonomies
    3. sync_property_images(post_id, property_id)
        ↓
        a. get_property_images(property_id) - API call
        b. For each image:
            - download_and_attach_image()
                - Check if exists (duplicate detection)
                - Download from URL
                - Upload to WordPress
                - Create attachment
                - Generate metadata
            - Set first as featured image
    4. Sync property rates
    ↓
Complete with summary
```

## Configuration

No additional configuration needed! Image sync happens automatically when:

- Running "Sync Properties" from admin panel
- Auto-sync if enabled in settings
- Manually syncing individual properties

## Resync Behavior

When properties are re-synced:

- **Existing images are kept** (duplicate detection prevents re-download)
- **New images are added** if they weren't there before
- **Deleted images remain** (plugin doesn't delete images no longer in API)

To do a fresh sync, manually delete property images from WordPress media library first.

## Error Handling

Comprehensive error handling at each step:

1. **API call fails** - Logs error, continues with property sync
2. **Image download fails** - Logs error, continues with next image
3. **Upload fails** - Logs error, continues with next image
4. **Attachment creation fails** - Logs error, continues with next image

Properties are still created/updated even if image sync fails.

## Logging

All image sync activity is logged to WordPress debug log:

```php
// Enable WordPress debug logging in wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

Log entries include:
- "Barefoot Sync: Starting image sync for property X, found Y images"
- "Barefoot Sync: Downloading image from: [URL]"
- "Barefoot Sync: Set image X as featured image for property Y"
- "Barefoot Sync: Successfully synced X images for property Y"
- Error messages for any failures

## Testing

To test image synchronization:

1. Go to **WordPress Admin → Barefoot Properties → Sync Properties**
2. Click **"Sync All Properties"** button
3. Watch the sync progress
4. Check **debug log** for image sync activity
5. View **property pages** to see images
6. Check **Media Library** for downloaded images

Verify:
- ✅ Property has featured image
- ✅ Images appear in WordPress Media Library
- ✅ Images are attached to property post
- ✅ Image galleries display on frontend

## Known Limitations

1. **No image resizing** - Images downloaded in original size (as requested)
2. **No image cleanup** - Deleted Barefoot images remain in WordPress
3. **Sequential download** - Images downloaded one at a time (no parallel)
4. **No retry logic** - Failed downloads are logged but not retried

## Future Enhancements

Possible improvements for future versions:

1. **Batch image download** - Parallel processing for faster sync
2. **Selective sync** - Option to skip image sync if needed
3. **Image cleanup** - Remove WordPress images not in Barefoot API
4. **Image caching** - Track API image checksums to detect changes
5. **Progress indicator** - Real-time UI feedback during image download
6. **Image optimization** - Compress images during download
7. **Retry logic** - Automatically retry failed downloads

## Performance Considerations

- **Initial sync** may take longer due to image downloads
- **Re-sync** is faster due to duplicate detection
- Each property typically has 3-10 images
- 100 properties = 300-1000 images to potentially download
- Recommend testing with small batch first

## Security

- Images downloaded via HTTPS URLs from Barefoot API
- WordPress handles file security and permissions
- No direct file system access outside WordPress uploads
- Original URLs stored for reference, not exposed publicly

---

**Version:** 1.2.0  
**Feature Status:** ✅ Implemented and Ready for Testing  
**Last Updated:** January 2025
