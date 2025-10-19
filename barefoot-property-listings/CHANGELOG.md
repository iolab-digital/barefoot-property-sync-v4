# Changelog

All notable changes to the Barefoot Property Listings plugin.

## [1.3.0] - 2025-01-18

### 🎨 New Feature - Single Hero Image + Gallery Scroller
- **REDESIGNED:** Single property page with luxury minimalist design
- **FEATURE:** Single full-width hero image (600px height)
- **FEATURE:** Horizontal scrolling gallery with arrow navigation
- **IMPROVED:** Gallery starts from 2nd image (1st is hero)
- **FEATURE:** Smooth scroll behavior with touch support
- **RESULT:** Beautiful, modern property showcase

### Template Improvements
- Inline styles for faster loading
- Responsive design for all devices
- Sticky sidebar with agent card
- Improved contact form styling
- Related properties with actual property images
- **ADDED:** Separate "Sync Images" button for independent image synchronization
- **IMPROVED:** Property sync now faster (images synced separately on demand)
- **FEATURE:** Dedicated AJAX handler for image sync (`barefoot_ajax_sync_images`)
- **UX:** Clear workflow - sync properties first, then sync images
- **FEATURE:** Image sync history logging
- **RESULT:** Better control, easier troubleshooting, no timeouts on large syncs

### Image Synchronization Implementation
- **ADDED:** Automatic image sync using `GetPropertyAllImgsXML` API method
- **FEATURE:** Downloads property images to WordPress media library
- **FEATURE:** Sets first image as featured image (post thumbnail)
- **FEATURE:** Creates image galleries attached to property posts
- **FEATURE:** Duplicate detection prevents re-downloading existing images
- **RESULT:** Property pages display full image galleries with proper thumbnails

### Changed
- Enhanced `class-barefoot-api.php` with `get_property_images()` method
- Added XML parsing for property images with `parse_property_images_xml()`
- Updated `class-property-sync.php` to sync images during property sync
- Added `download_and_attach_image()` method with WordPress media integration
- Comprehensive error logging for image download and attachment process
- Images synced in batch during property synchronization

### Technical Details
- **API Method:** `GetPropertyAllImgsXML` (returns all images for a property)
- **Image Storage:** WordPress uploads directory via `wp_upload_bits()`
- **Image Processing:** Full WordPress attachment metadata generation
- **Duplicate Detection:** Tracks original image URLs to prevent duplicates
- **Resync Behavior:** Replaces existing images on re-sync

## [1.1.0] - 2025-01-18

### 🎉 Major Fix - API Integration Working
- **FIXED:** Corrected SOAP API method from `GetAllProperty` to `GetProperty`
- **RESULT:** Successfully retrieving 95+ properties from Barefoot API

### Changed
- Updated `class-barefoot-api.php` to use correct `GetProperty` method
- Added new `parse_property_list_xml()` method for proper XML parsing
- Improved error handling and logging
- Enhanced XML parsing for PropertyList structure

### Security
- Removed hardcoded credentials from main plugin file
- Credentials now loaded from `wp-config.php` or WordPress options
- Added CONFIG.md with secure configuration instructions

### Documentation
- Updated README.md with comprehensive guide
- Added CONFIG.md for credential setup
- Created .gitignore for sensitive files

### Technical Details
- **API Method:** `GetProperty` (with auth-only parameters)
- **Property Fields:** 104 fields per property parsed
- **Endpoint:** `https://portals.barefoot.com/BarefootWebService/BarefootService.asmx`

## [1.0.0] - Initial Release

---

**Current Version:** 1.1.0  
**API Status:** ✅ Working
