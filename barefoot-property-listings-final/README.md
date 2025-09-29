# Barefoot Property Listings WordPress Plugin

A comprehensive WordPress plugin that integrates with the Barefoot Property Management System's SOAP API to synchronize and display vacation rental properties.

## Features

- **SOAP API Integration**: Direct connection to Barefoot Property Management System
- **Property Synchronization**: Automatic sync of properties, images, rates, and availability
- **Custom Post Types**: Properties managed as WordPress posts with 30+ custom fields
- **Responsive Frontend**: Mobile-friendly property listings and search functionality
- **Advanced Search**: Filter by location, property type, amenities, price, and more
- **Admin Dashboard**: Easy-to-use interface for managing synchronization
- **Booking Inquiries**: Built-in contact forms for guest inquiries

## Requirements

- PHP 7.4 or higher
- PHP SOAP extension enabled
- WordPress 5.0 or higher
- Valid Barefoot Property Management System API credentials

## Installation

1. Download or clone this repository
2. Upload the `barefoot-property-listings` folder to `/wp-content/plugins/`
3. Activate the plugin through the WordPress admin
4. Configure your Barefoot API credentials in the plugin code
5. Go to **Barefoot Properties > Sync Properties** to test connection and sync

## Configuration

Edit the main plugin file to configure your API credentials:

```php
define('BAREFOOT_API_ENDPOINT', 'https://portals.barefoot.com/BarefootWebService/BarefootService.asmx');
define('BAREFOOT_API_USERNAME', 'your_username');
define('BAREFOOT_API_PASSWORD', 'your_password');
define('BAREFOOT_API_VERSION', 'your_version');
```

## Usage

### Shortcodes

- `[barefoot_properties]` - Display property listings
- `[barefoot_search]` - Property search form
- `[barefoot_properties limit="6" type="villa"]` - Display specific property types

### Admin Features

- **Sync Properties**: Bulk sync all properties from Barefoot API
- **Property Management**: Edit individual properties with custom fields
- **Search & Filter**: Advanced property filtering and sorting

## File Structure

```
barefoot-property-listings/
├── barefoot-property-listings.php    # Main plugin file
├── readme.txt                        # WordPress plugin readme
├── includes/
│   ├── class-barefoot-api.php        # SOAP API integration
│   ├── class-property-sync.php       # Property synchronization
│   └── class-frontend-display.php    # Frontend functionality
├── admin/
│   ├── class-admin-page.php          # Admin interface
│   └── ajax-handlers.php             # AJAX functionality
├── templates/
│   ├── single-property.php           # Single property template
│   └── archive-property.php          # Property listings template
└── languages/
    └── .gitkeep                      # Translation files
```

## API Integration

This plugin integrates with Barefoot's SOAP API using the following key methods:

- `GetAllProperty` - Retrieve all properties
- `GetPropertyAllImgs` - Get property images
- `GetPropertyRates` - Fetch pricing information
- `GetPropertyBookingDate` - Get availability calendar

## Troubleshooting

### SOAP Extension Error
If you get a SOAP extension error during activation, contact your hosting provider to enable the PHP SOAP extension.

### Connection Issues
1. Verify your API credentials are correct
2. Check that your server can connect to the Barefoot API endpoint
3. Enable WordPress debug logging to see detailed error messages

### No Properties Synced
1. Check the debug logs for API response details
2. Verify the API is returning property data
3. Check that property field names match the API response

## Development

### Debug Mode
Enable WordPress debug logging to see detailed API interactions:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

### Testing
Use the included debug helper to test API responses:
- Access `/wp-content/plugins/barefoot-property-listings/debug-helper.php`

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## License

GPL v2 or later

## Support

For support issues, please create an issue in this repository or contact the plugin author.

## Changelog

### Version 1.0.1
- Fixed PHP parse errors in JavaScript code
- Added comprehensive SOAP extension checking
- Enhanced debugging and error handling
- Improved API response parsing
- Better compatibility with hosting environments

### Version 1.0.0
- Initial release
- SOAP API integration with Barefoot Property Management
- Property synchronization functionality
- Custom post types and taxonomies
- Frontend property display templates
- Admin interface for sync management