# Barefoot Property Listings - WordPress Plugin

A WordPress plugin that integrates with Barefoot Property Management System's SOAP API to synchronize and display vacation rental properties.

## 🎯 Features

- **Full SOAP API Integration** - Connect to Barefoot Property Management System
- **Property Synchronization** - Automatically sync properties from Barefoot API
- **Image Gallery Sync** - Downloads property images to WordPress media library
- **Featured Images** - Automatically sets first image as post thumbnail
- **Custom Post Type** - Properties stored as WordPress custom post type
- **Admin Interface** - Easy-to-use admin dashboard for managing properties
- **Frontend Display** - Templates for displaying properties on your website
- **Meta Fields** - 100+ property fields including descriptions, amenities, and custom attributes

## ✅ Current Status

**Version:** 1.2.0  
**API Integration:** ✅ WORKING - Successfully retrieves 95+ properties  
**Image Sync:** ✅ WORKING - Downloads images to WordPress media library  
**Method:** Uses `GetProperty` and `GetPropertyAllImgsXML` SOAP methods  
**Tested:** January 2025

## 📋 Requirements

- **WordPress:** 5.0 or higher
- **PHP:** 7.4 or higher
- **PHP SOAP Extension:** Required for API communication
- **Barefoot Account:** Valid Barefoot Property Management API credentials

## 🚀 Installation

### Option 1: Upload via WordPress Admin

1. Download the plugin as a ZIP file
2. Go to WordPress Admin → Plugins → Add New → Upload Plugin
3. Select the ZIP file and click "Install Now"
4. Click "Activate Plugin"

### Option 2: Manual Installation

1. Download/clone this repository
2. Upload the `barefoot-property-listings` folder to `/wp-content/plugins/`
3. Go to WordPress Admin → Plugins
4. Find "Barefoot Property Listings" and click "Activate"

## ⚙️ Configuration

### Set API Credentials

Add your Barefoot API credentials to `wp-config.php` (recommended for security):

```php
// Barefoot API Configuration
define('BAREFOOT_API_ENDPOINT', 'https://portals.barefoot.com/BarefootWebService/BarefootService.asmx');
define('BAREFOOT_API_USERNAME', 'your_username_here');
define('BAREFOOT_API_PASSWORD', 'your_password_here');
define('BAREFOOT_API_ACCOUNT', 'your_account_id_here');
```

**Or** use WordPress options (via database):

```php
update_option('barefoot_api_username', 'your_username_here');
update_option('barefoot_api_password', 'your_password_here');
update_option('barefoot_api_account', 'your_account_id_here');
```

### Test Connection

1. Go to WordPress Admin → Barefoot Properties → API Test
2. Click "Test Connection" to verify API connectivity
3. Click "Test Get Properties" to confirm property retrieval

## 🔧 API Integration

### Correct SOAP Method

The plugin uses **`GetProperty`** (singular) with authentication-only parameters to retrieve all properties:

```php
$response = $this->soap_client->GetProperty($auth_params);
```

### Property Data

Each property contains **104 fields** including PropertyID, name, address, description, status, occupancy, pricing, and custom attributes.

## 🐛 Troubleshooting

### Check PHP SOAP Extension

```php
<?php phpinfo(); ?>
// Look for "soap" section
```

### Enable Debug Logging

```php
// Add to wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

### Check Logs

Look in `/wp-content/debug.log` for "Barefoot" entries.

## 🔐 Security

**Never commit credentials to Git!**

- Use `wp-config.php` for credentials
- Or store in WordPress options via database
- Add sensitive files to `.gitignore`

## 📜 License

GPL v2 or later

---

**Last Updated:** January 2025  
**Plugin Version:** 1.1.0  
**API Status:** ✅ Working
