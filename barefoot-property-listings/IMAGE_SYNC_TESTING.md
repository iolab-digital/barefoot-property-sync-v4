# Image Synchronization - Testing Checklist

## Pre-Testing Setup

- [ ] WordPress plugin is installed and activated
- [ ] API credentials configured in `wp-config.php`
- [ ] Debug logging enabled (WP_DEBUG = true, WP_DEBUG_LOG = true)
- [ ] PHP SOAP extension is enabled
- [ ] Write permissions on WordPress uploads directory

## Test 1: Basic Image Sync

### Steps:
1. Go to **WordPress Admin → Barefoot Properties → Sync Properties**
2. Click **"Sync All Properties"** button
3. Wait for sync to complete

### Expected Results:
- [ ] Sync completes without fatal errors
- [ ] Success message displays with property count
- [ ] No PHP errors in debug log

### Verify:
- [ ] Check debug log for entries like:
  ```
  Barefoot Sync: Starting image sync for property X, found Y images
  Barefoot Sync: Downloading image from: [URL]
  Barefoot Sync: Successfully synced X images for property Y
  ```

## Test 2: WordPress Media Library

### Steps:
1. Go to **WordPress Admin → Media → Library**
2. Filter by "Unattached" or view all media

### Expected Results:
- [ ] New images appear in media library
- [ ] Images have proper titles
- [ ] Images show correct parent property (if viewing in detail)

### Verify:
- [ ] Each image has `_barefoot_original_url` in custom fields
- [ ] Images are associated with property posts

## Test 3: Featured Images

### Steps:
1. Go to **WordPress Admin → Barefoot Properties → All Properties**
2. Look at the property list table

### Expected Results:
- [ ] Each property shows a featured image thumbnail
- [ ] Featured images are the first image from the property gallery

### Verify:
- [ ] Properties without images show no thumbnail (expected)
- [ ] All synced properties with images have featured images set

## Test 4: Frontend Display

### Steps:
1. Visit a property page on your website frontend
2. View the property archive page

### Expected Results:
- [ ] Property single page displays featured image
- [ ] Image gallery is available (if template supports it)
- [ ] Images load correctly (no broken links)

### Verify:
- [ ] Images are responsive and properly sized
- [ ] Alt text is present (may be empty initially)
- [ ] Image captions show if available

## Test 5: Re-Sync (Duplicate Detection)

### Steps:
1. Note the current image count in Media Library
2. Go to **WordPress Admin → Barefoot Properties → Sync Properties**
3. Click **"Sync All Properties"** again
4. Check Media Library again

### Expected Results:
- [ ] Sync completes successfully
- [ ] Image count remains the same (no duplicates created)
- [ ] Debug log shows "image already exists" or similar

### Verify:
- [ ] No duplicate images in media library
- [ ] Same attachment IDs are used (check debug log)

## Test 6: Individual Property Re-Sync

### Steps:
1. Go to **WordPress Admin → Barefoot Properties → All Properties**
2. Edit a single property
3. Click "Update" or "Sync" (if available)

### Expected Results:
- [ ] Property updates successfully
- [ ] Images remain attached
- [ ] No errors in debug log

## Test 7: New Images Added

### Steps:
1. If possible, add a new image to a property in Barefoot system
2. Re-sync that property or all properties
3. Check if new image appears

### Expected Results:
- [ ] New image is downloaded
- [ ] Added to existing property images
- [ ] Featured image remains the same (first image)

## Test 8: Error Handling

### Steps:
1. Check debug log for any image download errors
2. Look for patterns in failed downloads

### Expected Results:
- [ ] Failed downloads are logged with error message
- [ ] Sync continues despite individual image failures
- [ ] Property is still created/updated even with image errors

## Test 9: Performance

### Metrics to Note:
- [ ] Time for initial sync with images: _______ seconds
- [ ] Time for re-sync (with duplicates): _______ seconds  
- [ ] Number of properties synced: _______
- [ ] Number of images downloaded: _______
- [ ] Server memory usage during sync: _______

### Expected:
- Initial sync slower due to downloads
- Re-sync much faster due to duplicate detection
- No memory exhaustion errors

## Test 10: Edge Cases

### Test with:
- [ ] Property with no images (should complete without error)
- [ ] Property with 1 image (should become featured image)
- [ ] Property with many images (10+) (all should download)
- [ ] Image URL that's invalid or broken (should log error, continue)

## Common Issues & Solutions

### Issue: No images syncing
**Check:**
- Debug log for API errors
- Network connectivity to image URLs
- WordPress uploads directory permissions
- PHP memory limit (increase if needed)

### Issue: Duplicate images created
**Check:**
- `_barefoot_original_url` meta is being saved
- `find_existing_attachment()` is working
- Post parent is correctly set

### Issue: Slow sync performance
**Solutions:**
- Increase PHP max_execution_time
- Sync properties in smaller batches
- Check network latency to Barefoot servers

### Issue: Featured image not set
**Check:**
- First image downloaded successfully
- `set_post_thumbnail()` being called
- Post thumbnail support enabled for post type

## Success Criteria

All tests pass if:
- ✅ Images download and appear in media library
- ✅ Featured images set on properties
- ✅ No duplicate images on re-sync
- ✅ Images display on frontend
- ✅ Error handling works (sync continues despite failures)
- ✅ Performance acceptable for property count

## Debug Log Review

Look for these patterns:

**✅ Good:**
```
Barefoot Sync: Starting image sync for property 123, found 5 images
Barefoot Sync: Downloading image from: https://...
Barefoot Sync: Set image 456 as featured image for property 123
Barefoot Sync: Successfully synced 5 images for property 123
```

**⚠️ Warnings (acceptable):**
```
Barefoot Sync: No images found for property 789
Image download error: HTTP 404
```

**❌ Errors (need fixing):**
```
Fatal error: Allowed memory size exhausted
SOAP Error: Connection timeout
PHP Warning: failed to open stream
```

---

**Testing Completed By:** _________________  
**Date:** _________________  
**Result:** PASS / FAIL / PARTIAL  
**Notes:** _________________
