# Barefoot Property Listings - WordPress Plugin

A comprehensive WordPress plugin that integrates with the Barefoot Property Management System's SOAP API to synchronize and display vacation rental properties.

## Features

### 🏠 **Property Management**
- **Custom Post Type**: `barefoot_property` with full WordPress integration
- **Taxonomies**: Property Types, Locations, and Amenities
- **30+ Meta Fields**: Complete property information storage
- **Featured Properties**: Mark and display special properties

### 🔄 **API Integration**
- **SOAP API Connection**: Direct integration with Barefoot Property Management System
- **Multiple Retrieval Methods**: Supports various API endpoints for maximum compatibility
- **Smart Property Discovery**: Automatically finds properties using multiple approaches
- **Real-time Synchronization**: Manual and scheduled property sync

### 🎨 **Frontend Display**
- **Responsive Templates**: Beautiful property listings and single property pages
- **Advanced Search**: Filter by type, location, price, occupancy, and amenities
- **Property Cards**: Attractive grid and list view options
- **Image Galleries**: Automatic property image synchronization and display
- **Interactive Maps**: Google Maps integration with property locations

### ⚙️ **Admin Interface**
- **Dashboard Overview**: Property statistics and recent activity
- **Sync Management**: Easy property synchronization with progress tracking
- **API Testing Tools**: Built-in connection and function testing
- **Settings Panel**: Configurable options and contact information
- **Property Editor**: Rich editing interface with meta boxes

### 📧 **Inquiry System**
- **Contact Forms**: Built-in property inquiry modals
- **Email Integration**: Automatic inquiry forwarding
- **Inquiry History**: Track and manage property inquiries

### 🔌 **Shortcodes**
- `[barefoot_properties]` - Display property listings
- `[barefoot_property_search]` - Search form widget
- `[barefoot_featured_properties]` - Featured properties showcase

## Installation

1. **Download the plugin** from the releases page or clone this repository
2. **Upload** the `barefoot-property-listings` folder to `/wp-content/plugins/`
3. **Activate** the plugin through the WordPress admin panel
4. **Configure** your API credentials in the plugin settings

## Requirements

- **WordPress**: 5.0 or higher
- **PHP**: 7.4 or higher
- **PHP Extensions**: SOAP extension must be enabled
- **API Access**: Valid Barefoot Property Management System credentials

## Configuration

### API Credentials

The plugin requires valid Barefoot API credentials. Update these constants in the main plugin file:

```php
define('BAREFOOT_API_ENDPOINT', 'https://portals.barefoot.com/BarefootWebService/BarefootService.asmx');
define('BAREFOOT_API_USERNAME', 'your_username');
define('BAREFOOT_API_PASSWORD', 'your_password');
define('BAREFOOT_API_ACCOUNT', 'your_account_id');
```

### Initial Setup

1. **Test API Connection**: Go to `Barefoot Properties > API Test` and verify connectivity
2. **Sync Properties**: Navigate to `Barefoot Properties > Sync Properties` and run initial sync
3. **Configure Settings**: Set up contact information and sync preferences
4. **Customize Display**: Use shortcodes or modify templates as needed

## Usage

### Displaying Properties

**All Properties Archive:**
Visit `/properties/` on your site to see all properties.

**Property Listings Shortcode:**
```php
[barefoot_properties limit="10" type="condo" location="miami"]
```

**Search Form:**
```php
[barefoot_property_search show_advanced="yes"]
```

**Featured Properties:**
```php
[barefoot_featured_properties limit="3"]
```

### Template Customization

The plugin includes default templates that can be overridden in your theme:

- Copy `templates/single-property.php` to `your-theme/barefoot-properties/single-property.php`
- Copy `templates/archive-property.php` to `your-theme/barefoot-properties/archive-property.php`

## API Integration Details

### Supported Methods

The plugin uses multiple API methods for maximum compatibility:

- `GetAllProperty` - Primary method for bulk property retrieval
- `GetProperty` - Individual property data
- `GetPropertyExt` - Extended property information
- `GetLastUpdatedProperty` - Recently updated properties
- `GetPropertyInfoById` - Property by ID lookup
- `GetPropertyAllImgs` - Property images
- `GetPropertyRates` - Pricing information

### Data Synchronization

**Property Fields Synchronized:**
- Basic Info: ID, Name, Description, Type
- Specifications: Occupancy, Bedrooms, Bathrooms
- Location: Address, City, State, ZIP, Coordinates
- Pricing: Min/Max rates, Minimum stay
- Features: Amenities, Pool, Hot Tub, WiFi, etc.
- Management: Status, Agents, Registration info

**Image Handling:**
- Automatic download and attachment to WordPress
- Featured image assignment
- Gallery creation
- Duplicate prevention

## Troubleshooting

### Common Issues

**"SOAP client not available"**
- Ensure PHP SOAP extension is installed and enabled
- Contact your hosting provider if needed

**"No properties found"**
- Verify API credentials are correct
- Check that properties exist in your Barefoot account
- Test API connection in the admin panel

**"Custom method" response**
- This is normal behavior for some accounts
- The plugin handles this automatically
- Contact Barefoot support for account-specific setup

### Debug Mode

Enable debug mode in `Barefoot Properties > Settings` to log detailed API interactions.

## Hooks and Filters

### Actions

```php
// Before property sync
do_action('barefoot_before_property_sync');

// After property sync
do_action('barefoot_after_property_sync', $results);

// Before property save
do_action('barefoot_before_property_save', $property_data);
```

### Filters

```php
// Modify property data before saving
$property_data = apply_filters('barefoot_property_data', $property_data);

// Customize property meta fields
$meta_fields = apply_filters('barefoot_property_meta_fields', $meta_fields);

// Modify search query args
$args = apply_filters('barefoot_search_query_args', $args);
```

## Development

### File Structure

```
barefoot-property-listings/
├── barefoot-property-listings.php    # Main plugin file
├── includes/
│   ├── class-barefoot-api.php        # SOAP API integration
│   ├── class-property-sync.php       # Property synchronization
│   └── class-frontend-display.php    # Frontend functionality
├── admin/
│   ├── class-admin-page.php          # Admin interface
│   └── ajax-handlers.php             # AJAX callbacks
├── templates/
│   ├── single-property.php           # Single property template
│   └── archive-property.php          # Property archive template
├── assets/
│   ├── css/
│   └── js/
└── languages/                        # Translation files
```

### Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## Changelog

### Version 1.1.0
- ✅ **Fixed**: Corrected `barefootAccount` parameter (now uses account ID instead of empty string)
- ✅ **Enhanced**: Multiple API method support for better compatibility
- ✅ **Added**: Property discovery by ID range
- ✅ **Improved**: Error handling and user messaging
- ✅ **Added**: Comprehensive admin interface
- ✅ **Added**: Frontend templates and shortcodes
- ✅ **Added**: Property inquiry system

### Version 1.0.0
- Initial release
- Basic SOAP API integration
- Property synchronization
- Custom post types and taxonomies

## Support

For technical support:

1. **Check Documentation**: Review this README and inline code comments
2. **Test API Connection**: Use the built-in API test tools
3. **Enable Debug Mode**: Check WordPress debug logs
4. **Contact Barefoot**: For API-specific issues, contact Barefoot support

## License

This plugin is released under the GPL v2 or later license.

## Credits

Developed for integration with the Barefoot Property Management System. This plugin is not officially affiliated with or endorsed by Barefoot.

---

**Version**: 1.1.0  
**Requires WordPress**: 5.0+  
**Tested up to**: 6.4  
**Requires PHP**: 7.4+  
**License**: GPL v2 or later