=== Barefoot Property Listings ===
Contributors: yourname
Tags: properties, vacation rentals, booking, real estate, SOAP API
Requires at least: 5.0
Tested up to: 6.4
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

WordPress plugin that integrates with Barefoot Property Management System's SOAP API to synchronize and display vacation rental properties.

== Description ==

Barefoot Property Listings is a comprehensive WordPress plugin that connects your website with the Barefoot Property Management System. This plugin allows property managers to:

* Automatically sync properties from Barefoot's SOAP API
* Display beautiful property listings on your WordPress site
* Manage property details, images, and availability
* Provide search and filtering functionality for visitors
* Handle booking inquiries and guest communication

= Key Features =

* **SOAP API Integration**: Direct connection to Barefoot Property Management System
* **Automatic Synchronization**: Keep your property listings up-to-date automatically
* **Custom Post Types**: Properties are managed as WordPress posts with custom fields
* **Responsive Design**: Mobile-friendly property listings and search functionality
* **Advanced Search**: Filter by location, property type, amenities, price, and more
* **Image Management**: Automatic download and management of property photos
* **Booking Inquiries**: Built-in contact forms for guest inquiries
* **Admin Dashboard**: Easy-to-use admin interface for managing synchronization

= Available Shortcodes =

* `[barefoot_properties]` - Display property listings
* `[barefoot_search]` - Property search form
* `[barefoot_properties limit="6" type="villa"]` - Display specific property types

= Requirements =

* PHP SOAP extension enabled
* WordPress 5.0 or higher
* Valid Barefoot Property Management System API credentials

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/barefoot-property-listings` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Configure your Barefoot API credentials in the plugin code (for security).
4. Go to Barefoot Properties > Sync Properties to test your connection and sync your first properties.
5. Use the shortcodes or visit your property archive page to see your listings.

== Frequently Asked Questions ==

= How do I get Barefoot API credentials? =

You need to contact Barefoot Property Management System to obtain your API credentials including username, password, and API version.

= Can I customize the property display? =

Yes! The plugin includes template files that you can copy to your theme and customize. You can also use the built-in CSS classes to style the properties to match your site design.

= How often do properties sync automatically? =

You can configure automatic sync frequency in the plugin settings. Options include hourly, twice daily, daily, or weekly synchronization.

= What property information is synced? =

The plugin syncs comprehensive property data including:
* Basic details (name, description, bedrooms, bathrooms)
* Location information (address, city, state, coordinates)
* Amenities and features
* Pricing and availability
* Property images
* Booking policies and restrictions

== Screenshots ==

1. Property listings grid view
2. Individual property detail page
3. Admin synchronization interface
4. Property search and filters
5. Admin property management

== Changelog ==

= 1.0.0 =
* Initial release
* SOAP API integration with Barefoot Property Management
* Property synchronization functionality
* Custom post types and taxonomies
* Frontend property display templates
* Search and filtering capabilities
* Admin interface for sync management
* Responsive design and mobile optimization

== Upgrade Notice ==

= 1.0.0 =
Initial release of the Barefoot Property Listings plugin.

== Support ==

For support questions, please contact [your support email] or visit [your support website].

== API Documentation ==

This plugin integrates with the Barefoot Property Management SOAP API. The API provides the following key methods:

* `GetAllProperty` - Retrieve all properties
* `GetPropertyAllImgs` - Get property images
* `GetPropertyRates` - Fetch pricing information
* `GetPropertyBookingDate` - Get availability calendar

For detailed API documentation, refer to the Barefoot WSDL at:
https://portals.barefoot.com/BarefootWebService/BarefootService.asmx?WSDL