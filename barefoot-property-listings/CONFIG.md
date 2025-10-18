# Barefoot Property Listings - Configuration Guide

## API Credentials Setup

You need to configure your Barefoot API credentials before using the plugin.

### Option 1: Using wp-config.php (Recommended)

Add these lines to your `wp-config.php` file (above the "That's all" comment):

```php
// Barefoot Property Management API Configuration
define('BAREFOOT_API_ENDPOINT', 'https://portals.barefoot.com/BarefootWebService/BarefootService.asmx');
define('BAREFOOT_API_USERNAME', 'your_username_here');
define('BAREFOOT_API_PASSWORD', 'your_password_here');
define('BAREFOOT_API_ACCOUNT', 'your_account_id_here');
```

### Option 2: Using WordPress Database Options

Run these commands in WordPress (via plugin code, theme, or wp-cli):

```php
update_option('barefoot_api_username', 'your_username_here');
update_option('barefoot_api_password', 'your_password_here');
update_option('barefoot_api_account', 'your_account_id_here');
```

### Example Credentials Format

```
Username:        hfa20250814
Password:        #20250825@xcfvgrt!54687
Account ID:      v3chfa0604
Endpoint:        https://portals.barefoot.com/BarefootWebService/BarefootService.asmx
```

## Testing Your Configuration

After setting credentials:

1. Go to **WordPress Admin → Barefoot Properties → API Test**
2. Click **"Test Connection"** - Should show success message
3. Click **"Test Get Properties"** - Should retrieve your properties (e.g., "95 properties found")

## Security Notes

- ⚠️ **Never commit credentials to Git**
- ⚠️ **Keep credentials in `wp-config.php` or database**
- ⚠️ **Restrict file permissions on production servers**
- ✅ **Use environment variables in production**
- ✅ **Regularly rotate API passwords**

## Troubleshooting

### "SOAP client not initialized"
- PHP SOAP extension not enabled
- Contact your hosting provider to enable it

### "Connection failed"
- Check credentials are correct
- Verify endpoint URL is accessible
- Check firewall/security rules

### "0 properties found"
- Verify your Barefoot account has properties
- Check API credentials with Barefoot support
- Review debug logs in `/wp-content/debug.log`

## Getting API Credentials

Contact Barefoot Property Management support to obtain:
- API Username
- API Password
- Barefoot Account ID

Website: https://www.barefoottech.com/
