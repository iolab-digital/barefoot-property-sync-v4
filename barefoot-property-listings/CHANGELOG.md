# Changelog

All notable changes to the Barefoot Property Listings WordPress plugin.

## [1.1.0] - 2025-01-20

### 🎉 Major Release - Fixed API Integration

### Fixed
- **CRITICAL**: Fixed "SYNC REQUEST FAILED" error by switching from `GetAllProperty` to `GetProperty` method
- **API Parameters**: Corrected `barefootAccount` parameter to use account ID (`v3chfa0604`)
- **Property Retrieval**: Implemented individual property retrieval by ID range
- **Field Mapping**: Enhanced property field mapping for various API response formats

### Added
- **Multiple API Methods**: Support for GetProperty, GetPropertyExt, GetLastUpdatedProperty
- **Fallback Strategies**: Intelligent fallback when primary methods fail
- **Property Discovery**: Automatic discovery of existing properties by ID
- **Admin Dashboard**: Complete admin interface with sync controls and API testing
- **Frontend Templates**: Responsive property listings and single property pages
- **Search & Filters**: Advanced property search and filtering
- **Inquiry System**: Built-in property inquiry forms

### Changed
- **Primary API Method**: Changed from `GetAllProperty` to `GetProperty`
- **Property Sync Logic**: Enhanced synchronization for various response formats

## [1.0.0] - 2025-01-15

### Added
- Initial plugin release
- Basic SOAP API integration
- Custom post type `barefoot_property`
- Property synchronization functionality

### Known Issues (Fixed in 1.1.0)
- GetAllProperty returned "This is a Custom method" error
- Incorrect barefootAccount parameter usage