# Changelog

All notable changes to the Barefoot Property Listings plugin.

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
