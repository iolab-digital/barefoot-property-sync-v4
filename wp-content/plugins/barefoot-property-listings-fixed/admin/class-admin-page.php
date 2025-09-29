<?php
/**
 * Admin Page Class
 * Handles the WordPress admin interface for Barefoot properties
 */

class Barefoot_Admin_Page {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Barefoot Properties', 'barefoot-properties'),
            __('Barefoot Properties', 'barefoot-properties'),
            'manage_options',
            'barefoot-properties',
            array($this, 'admin_page'),
            'dashicons-admin-home',
            26
        );
        
        add_submenu_page(
            'barefoot-properties',
            __('Sync Properties', 'barefoot-properties'),
            __('Sync Properties', 'barefoot-properties'),
            'manage_options',
            'barefoot-sync',
            array($this, 'sync_page')
        );
        
        add_submenu_page(
            'barefoot-properties',
            __('Settings', 'barefoot-properties'),
            __('Settings', 'barefoot-properties'),
            'manage_options',
            'barefoot-settings',
            array($this, 'settings_page')
        );
    }
    
    /**
     * Initialize admin settings
     */
    public function admin_init() {
        register_setting('barefoot_settings', 'barefoot_api_settings');
        
        add_settings_section(
            'barefoot_api_section',
            __('API Configuration', 'barefoot-properties'),
            array($this, 'api_section_callback'),
            'barefoot_settings'
        );
        
        add_settings_field(
            'auto_sync',
            __('Auto Sync', 'barefoot-properties'),
            array($this, 'auto_sync_callback'),
            'barefoot_settings',
            'barefoot_api_section'
        );
        
        add_settings_field(
            'sync_frequency',
            __('Sync Frequency', 'barefoot-properties'),
            array($this, 'sync_frequency_callback'),
            'barefoot_settings',
            'barefoot_api_section'
        );
    }
    
    /**
     * Main admin page
     */
    public function admin_page() {
        $properties_count = wp_count_posts('barefoot_property');
        $last_sync = get_option('barefoot_last_sync', 'Never');
        
        ?>
        <div class="wrap barefoot-admin">
            <h1><?php _e('Barefoot Property Management', 'barefoot-properties'); ?></h1>
            
            <div class="barefoot-dashboard">
                <div class="barefoot-stats">
                    <h2><?php _e('Overview', 'barefoot-properties'); ?></h2>
                    
                    <div class="stats-grid">
                        <div class="stat-card">
                            <h3><?php echo $properties_count->publish; ?></h3>
                            <p><?php _e('Published Properties', 'barefoot-properties'); ?></p>
                        </div>
                        
                        <div class="stat-card">
                            <h3><?php echo $properties_count->draft; ?></h3>
                            <p><?php _e('Draft Properties', 'barefoot-properties'); ?></p>
                        </div>
                        
                        <div class="stat-card">
                            <h3><?php echo $last_sync; ?></h3>
                            <p><?php _e('Last Sync', 'barefoot-properties'); ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="barefoot-quick-actions">
                    <h2><?php _e('Quick Actions', 'barefoot-properties'); ?></h2>
                    
                    <p>
                        <a href="<?php echo admin_url('admin.php?page=barefoot-sync'); ?>" class="button button-primary button-large">
                            <?php _e('Sync Properties Now', 'barefoot-properties'); ?>
                        </a>
                    </p>
                    
                    <p>
                        <a href="<?php echo admin_url('edit.php?post_type=barefoot_property'); ?>" class="button button-secondary">
                            <?php _e('View All Properties', 'barefoot-properties'); ?>
                        </a>
                    </p>
                    
                    <p>
                        <a href="<?php echo admin_url('admin.php?page=barefoot-settings'); ?>" class="button button-secondary">
                            <?php _e('Settings', 'barefoot-properties'); ?>
                        </a>
                    </p>
                </div>
            </div>
            
            <style>
            .barefoot-dashboard { display: flex; gap: 30px; margin-top: 20px; }
            .barefoot-stats, .barefoot-quick-actions { background: white; padding: 20px; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,0.04); flex: 1; }
            .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-top: 15px; }
            .stat-card { text-align: center; padding: 15px; background: #f6f7f7; border-radius: 4px; }
            .stat-card h3 { font-size: 2em; margin: 0; color: #2c5aa0; }
            .stat-card p { margin: 5px 0 0; color: #666; }
            .barefoot-quick-actions p { margin-bottom: 15px; }
            </style>
        </div>
        <?php
    }
    
    /**
     * Sync page
     */
    public function sync_page() {
        ?>
        <div class="wrap barefoot-admin">
            <h1><?php _e('Sync Properties', 'barefoot-properties'); ?></h1>
            
            <div class="barefoot-sync-panel">
                <h2><?php _e('API Connection Test', 'barefoot-properties'); ?></h2>
                
                <div class="connection-status">
                    <?php _e('Click "Test Connection" to verify API connectivity', 'barefoot-properties'); ?>
                </div>
                
                <p>
                    <button type="button" id="test-connection" class="sync-button">
                        <?php _e('Test Connection', 'barefoot-properties'); ?>
                    </button>
                </p>
                
                <hr>
                
                <h2><?php _e('Property Synchronization', 'barefoot-properties'); ?></h2>
                
                <p><?php _e('Sync all properties from Barefoot Property Management System. This will:', 'barefoot-properties'); ?></p>
                <ul>
                    <li><?php _e('Fetch all properties from Barefoot API', 'barefoot-properties'); ?></li>
                    <li><?php _e('Create new property posts or update existing ones', 'barefoot-properties'); ?></li>
                    <li><?php _e('Download and attach property images', 'barefoot-properties'); ?></li>
                    <li><?php _e('Sync property rates and availability data', 'barefoot-properties'); ?></li>
                </ul>
                
                <p>
                    <button type="button" id="sync-properties" class="sync-button">
                        <?php _e('Sync All Properties', 'barefoot-properties'); ?>
                    </button>
                </p>
                
                <div class="sync-progress">
                    <h3><?php _e('Sync Progress', 'barefoot-properties'); ?></h3>
                    <div class="progress-bar">
                        <div class="progress-fill"></div>
                    </div>
                    
                    <div class="sync-log"></div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Settings page
     */
    public function settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Barefoot Settings', 'barefoot-properties'); ?></h1>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('barefoot_settings');
                do_settings_sections('barefoot_settings');
                submit_button();
                ?>
            </form>
            
            <div class="barefoot-info-panel">
                <h2><?php _e('API Information', 'barefoot-properties'); ?></h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('API Endpoint', 'barefoot-properties'); ?></th>
                        <td><code><?php echo esc_html(BAREFOOT_API_ENDPOINT); ?></code></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('API Username', 'barefoot-properties'); ?></th>
                        <td><code><?php echo esc_html(BAREFOOT_API_USERNAME); ?></code></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('API Version', 'barefoot-properties'); ?></th>
                        <td><code><?php echo esc_html(BAREFOOT_API_VERSION); ?></code></td>
                    </tr>
                </table>
                
                <p><em><?php _e('API credentials are configured in the plugin code for security.', 'barefoot-properties'); ?></em></p>
            </div>
        </div>
        
        <style>
        .barefoot-info-panel {
            background: white;
            padding: 20px;
            border: 1px solid #ccd0d4;
            box-shadow: 0 1px 1px rgba(0,0,0,0.04);
            margin-top: 20px;
        }
        </style>
        <?php
    }
    
    /**
     * API section callback
     */
    public function api_section_callback() {
        echo '<p>' . __('Configure how the plugin syncs with Barefoot API.', 'barefoot-properties') . '</p>';
    }
    
    /**
     * Auto sync callback
     */
    public function auto_sync_callback() {
        $options = get_option('barefoot_api_settings');
        $auto_sync = isset($options['auto_sync']) ? $options['auto_sync'] : 0;
        
        echo '<input type="checkbox" name="barefoot_api_settings[auto_sync]" value="1" ' . checked(1, $auto_sync, false) . ' />';
        echo '<label for="barefoot_api_settings[auto_sync]">' . __('Enable automatic property synchronization', 'barefoot-properties') . '</label>';
    }
    
    /**
     * Sync frequency callback
     */
    public function sync_frequency_callback() {
        $options = get_option('barefoot_api_settings');
        $frequency = isset($options['sync_frequency']) ? $options['sync_frequency'] : 'daily';
        
        echo '<select name="barefoot_api_settings[sync_frequency]">';
        echo '<option value="hourly" ' . selected('hourly', $frequency, false) . '>' . __('Hourly', 'barefoot-properties') . '</option>';
        echo '<option value="twicedaily" ' . selected('twicedaily', $frequency, false) . '>' . __('Twice Daily', 'barefoot-properties') . '</option>';
        echo '<option value="daily" ' . selected('daily', $frequency, false) . '>' . __('Daily', 'barefoot-properties') . '</option>';
        echo '<option value="weekly" ' . selected('weekly', $frequency, false) . '>' . __('Weekly', 'barefoot-properties') . '</option>';
        echo '</select>';
        echo '<p class="description">' . __('How often should properties be automatically synced from Barefoot API.', 'barefoot-properties') . '</p>';
    }
}

?>