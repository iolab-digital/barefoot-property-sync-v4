<?php
/**
 * Admin Page Class
 * Handles the admin interface for the plugin
 * 
 * @package BarefootPropertyListings
 * @since 1.1.0
 */

class Barefoot_Admin_Page {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_property_meta'));
        add_filter('manage_barefoot_property_posts_columns', array($this, 'add_property_columns'));
        add_action('manage_barefoot_property_posts_custom_column', array($this, 'property_column_content'), 10, 2);
    }
    
    /**
     * Add admin menu items
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Barefoot Properties', 'barefoot-properties'),
            __('Barefoot Properties', 'barefoot-properties'),
            'manage_options',
            'barefoot-properties',
            array($this, 'admin_page'),
            'dashicons-building',
            6
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
        
        add_submenu_page(
            'barefoot-properties',
            __('API Test', 'barefoot-properties'),
            __('API Test', 'barefoot-properties'),
            'manage_options',
            'barefoot-test',
            array($this, 'test_page')
        );
    }
    
    /**
     * Initialize admin settings
     */
    public function admin_init() {
        register_setting('barefoot_settings', 'barefoot_sync_frequency');
        register_setting('barefoot_settings', 'barefoot_debug_mode');
        register_setting('barefoot_settings', 'barefoot_auto_sync');
        register_setting('barefoot_settings', 'barefoot_featured_count');
        register_setting('barefoot_settings', 'barefoot_contact_email');
        register_setting('barefoot_settings', 'barefoot_contact_phone');
        
        add_settings_section(
            'barefoot_general_section',
            __('General Settings', 'barefoot-properties'),
            array($this, 'general_section_callback'),
            'barefoot_settings'
        );
        
        add_settings_field(
            'barefoot_sync_frequency',
            __('Sync Frequency', 'barefoot-properties'),
            array($this, 'sync_frequency_callback'),
            'barefoot_settings',
            'barefoot_general_section'
        );
        
        add_settings_field(
            'barefoot_debug_mode',
            __('Debug Mode', 'barefoot-properties'),
            array($this, 'debug_mode_callback'),
            'barefoot_settings',
            'barefoot_general_section'
        );
        
        add_settings_field(
            'barefoot_auto_sync',
            __('Auto Sync', 'barefoot-properties'),
            array($this, 'auto_sync_callback'),
            'barefoot_settings',
            'barefoot_general_section'
        );
        
        add_settings_field(
            'barefoot_featured_count',
            __('Featured Properties Count', 'barefoot-properties'),
            array($this, 'featured_count_callback'),
            'barefoot_settings',
            'barefoot_general_section'
        );
        
        add_settings_field(
            'barefoot_contact_email',
            __('Contact Email', 'barefoot-properties'),
            array($this, 'contact_email_callback'),
            'barefoot_settings',
            'barefoot_general_section'
        );
        
        add_settings_field(
            'barefoot_contact_phone',
            __('Contact Phone', 'barefoot-properties'),
            array($this, 'contact_phone_callback'),
            'barefoot_settings',
            'barefoot_general_section'
        );
    }
    
    /**
     * Main admin page
     */
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Barefoot Properties Overview', 'barefoot-properties'); ?></h1>
            
            <div class="barefoot-admin-dashboard">
                <div class="dashboard-widgets">
                    <div class="dashboard-widget">
                        <h3><?php _e('Property Statistics', 'barefoot-properties'); ?></h3>
                        <?php $this->render_property_stats(); ?>
                    </div>
                    
                    <div class="dashboard-widget">
                        <h3><?php _e('Recent Activity', 'barefoot-properties'); ?></h3>
                        <?php $this->render_recent_activity(); ?>
                    </div>
                    
                    <div class="dashboard-widget">
                        <h3><?php _e('Quick Actions', 'barefoot-properties'); ?></h3>
                        <?php $this->render_quick_actions(); ?>
                    </div>
                    
                    <div class="dashboard-widget">
                        <h3><?php _e('API Status', 'barefoot-properties'); ?></h3>
                        <?php $this->render_api_status(); ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Sync properties page
     */
    public function sync_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Sync Properties from Barefoot API', 'barefoot-properties'); ?></h1>
            
            <div class="barefoot-sync-container">
                <div class="sync-controls">
                    <button id="sync-all-properties" class="button button-primary button-large">
                        <?php _e('Sync All Properties', 'barefoot-properties'); ?>
                    </button>
                    
                    <button id="test-api-connection" class="button button-secondary">
                        <?php _e('Test API Connection', 'barefoot-properties'); ?>
                    </button>
                    
                    <button id="cleanup-orphaned" class="button button-secondary">
                        <?php _e('Cleanup Orphaned Properties', 'barefoot-properties'); ?>
                    </button>
                </div>
                
                <div class="sync-progress" style="display: none;">
                    <div class="progress-bar">
                        <div class="progress-fill"></div>
                    </div>
                    <p class="progress-text"><?php _e('Synchronizing properties...', 'barefoot-properties'); ?></p>
                </div>
                
                <div class="sync-results" style="display: none;">
                    <h3><?php _e('Sync Results', 'barefoot-properties'); ?></h3>
                    <div class="results-content"></div>
                </div>
                
                <div class="sync-log">
                    <h3><?php _e('Sync History', 'barefoot-properties'); ?></h3>
                    <?php $this->render_sync_history(); ?>
                </div>
                
                <!-- Debug Information -->
                <div style="margin-top: 20px; padding: 15px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px;">
                    <h4>Debug Information</h4>
                    <p><strong>Plugin Version:</strong> <?php echo BAREFOOT_VERSION; ?></p>
                    <p><strong>Admin JS Enqueued:</strong> <?php echo wp_script_is('barefoot-admin', 'enqueued') ? 'Yes ✅' : 'No ❌'; ?></p>
                    <p><strong>Admin CSS Enqueued:</strong> <?php echo wp_style_is('barefoot-admin', 'enqueued') ? 'Yes ✅' : 'No ❌'; ?></p>
                    <p><strong>AJAX URL:</strong> <?php echo admin_url('admin-ajax.php'); ?></p>
                    <p><strong>Current Hook:</strong> <?php global $hook_suffix; echo $hook_suffix; ?></p>
                    <p><strong>JavaScript Console:</strong> Check browser console (F12) for any errors</p>
                </div>
                
                <script type="text/javascript">
                console.log('=== BAREFOOT SYNC PAGE DEBUG ===');
                console.log('Page loaded at:', new Date());
                console.log('jQuery available:', typeof jQuery !== 'undefined');
                console.log('$ available:', typeof $ !== 'undefined');
                console.log('ajaxurl available:', typeof ajaxurl !== 'undefined');
                console.log('barefoot_ajax available:', typeof barefoot_ajax !== 'undefined');
                
                if (typeof barefoot_ajax !== 'undefined') {
                    console.log('barefoot_ajax contents:', barefoot_ajax);
                } else {
                    console.error('❌ barefoot_ajax is NOT DEFINED - This is the problem!');
                    console.log('This means the JavaScript localization failed.');
                }
                
                jQuery(document).ready(function($) {
                    console.log('Document ready fired');
                    console.log('Sync button found:', $('#sync-all-properties').length);
                    console.log('Test connection button found:', $('#test-api-connection').length);
                    
                    // Manual click handler for debugging
                    $('#test-api-connection').on('click', function(e) {
                        e.preventDefault();
                        console.log('❗ TEST CONNECTION CLICKED');
                        
                        if (typeof barefoot_ajax === 'undefined') {
                            alert('❌ ERROR: barefoot_ajax is not defined!\n\nThis means the JavaScript/AJAX setup is not working.\n\nPlease check:\n1. Plugin is activated\n2. No JavaScript errors in console\n3. WordPress is loading admin scripts properly');
                            return;
                        }
                        
                        alert('✅ JavaScript is working!\n\nNow testing AJAX connection...');
                        
                        var ajaxUrl = (typeof ajaxurl !== 'undefined') ? ajaxurl : barefoot_ajax.ajax_url;
                        
                        $.post(ajaxUrl, {
                            action: 'barefoot_test_connection',
                            nonce: barefoot_ajax.nonce
                        })
                        .done(function(response) {
                            console.log('✅ AJAX Success:', response);
                            alert('✅ AJAX Connection Test Successful!\n\nResponse: ' + JSON.stringify(response));
                        })
                        .fail(function(xhr, status, error) {
                            console.error('❌ AJAX Failed:', {status: status, error: error, response: xhr.responseText});
                            alert('❌ AJAX Connection Test Failed!\n\nStatus: ' + status + '\nError: ' + error + '\n\nCheck console for details.');
                        });
                    });
                });
                </script>
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
            <h1><?php _e('Barefoot Properties Settings', 'barefoot-properties'); ?></h1>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('barefoot_settings');
                do_settings_sections('barefoot_settings');
                submit_button();
                ?>
            </form>
            
            <div class="barefoot-settings-info">
                <h3><?php _e('API Configuration', 'barefoot-properties'); ?></h3>
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
                        <th scope="row"><?php _e('Barefoot Account', 'barefoot-properties'); ?></th>
                        <td><code><?php echo esc_html(BAREFOOT_API_ACCOUNT); ?></code></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Plugin Version', 'barefoot-properties'); ?></th>
                        <td><code><?php echo esc_html(BAREFOOT_VERSION); ?></code></td>
                    </tr>
                </table>
                
                <p class="description">
                    <?php _e('API credentials are configured in the plugin file. Contact your developer to modify these settings.', 'barefoot-properties'); ?>
                </p>
            </div>
        </div>
        <?php
    }
    
    /**
     * API Test page
     */
    public function test_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Barefoot API Test', 'barefoot-properties'); ?></h1>
            
            <div class="barefoot-test-container">
                <div class="test-controls">
                    <button id="test-connection" class="button button-primary">
                        <?php _e('Test Connection', 'barefoot-properties'); ?>
                    </button>
                    
                    <button id="test-get-properties" class="button button-secondary">
                        <?php _e('Test Get Properties', 'barefoot-properties'); ?>
                    </button>
                    
                    <button id="test-get-functions" class="button button-secondary">
                        <?php _e('Get Available Functions', 'barefoot-properties'); ?>
                    </button>
                </div>
                
                <div class="test-results">
                    <h3><?php _e('Test Results', 'barefoot-properties'); ?></h3>
                    <div class="results-output">
                        <p><?php _e('Click a test button to see results here.', 'barefoot-properties'); ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Debug Information -->
            <div style="margin-top: 30px; padding: 15px; background: #f0f0f0; border: 1px solid #ccc;">
                <h3>Debug Information</h3>
                <p><strong>Plugin Version:</strong> <?php echo BAREFOOT_VERSION; ?></p>
                <p><strong>Admin JS Enqueued:</strong> <?php echo wp_script_is('barefoot-admin', 'enqueued') ? 'Yes' : 'No'; ?></p>
                <p><strong>AJAX URL:</strong> <?php echo admin_url('admin-ajax.php'); ?></p>
                <p><strong>Nonce:</strong> <?php echo wp_create_nonce('barefoot_nonce'); ?></p>
                <p><strong>Current Hook:</strong> <?php global $hook_suffix; echo $hook_suffix; ?></p>
            </div>
            
            <script type="text/javascript">
            jQuery(document).ready(function($) {
                console.log('=== BAREFOOT TEST PAGE DEBUG ===');
                console.log('jQuery available:', typeof $ !== 'undefined');
                console.log('ajaxurl available:', typeof ajaxurl !== 'undefined');
                console.log('barefoot_ajax available:', typeof barefoot_ajax !== 'undefined');
                
                if (typeof barefoot_ajax !== 'undefined') {
                    console.log('barefoot_ajax object:', barefoot_ajax);
                } else {
                    console.warn('barefoot_ajax is not defined - this could be the problem!');
                }
                
                // Test if buttons exist
                console.log('Test connection button exists:', $('#test-connection').length > 0);
                console.log('Test get properties button exists:', $('#test-get-properties').length > 0);
                
                // Add a manual click test
                $('#test-connection').on('click', function(e) {
                    e.preventDefault();
                    console.log('Manual click handler triggered');
                    
                    if (typeof barefoot_ajax === 'undefined') {
                        alert('barefoot_ajax is not defined! JavaScript not loaded properly.');
                        return;
                    }
                    
                    if (typeof ajaxurl === 'undefined' && !barefoot_ajax.ajax_url) {
                        alert('No AJAX URL available!');
                        return;
                    }
                    
                    var ajaxUrl = (typeof ajaxurl !== 'undefined') ? ajaxurl : barefoot_ajax.ajax_url;
                    
                    console.log('Making AJAX call to:', ajaxUrl);
                    
                    $.post(ajaxUrl, {
                        action: 'barefoot_test_connection',
                        nonce: barefoot_ajax.nonce
                    })
                    .done(function(response) {
                        console.log('Manual test response:', response);
                        $('.results-output').html('<pre>' + JSON.stringify(response, null, 2) + '</pre>');
                    })
                    .fail(function(xhr, status, error) {
                        console.error('Manual test failed:', status, error);
                        console.error('Response:', xhr.responseText);
                        $('.results-output').html('<div style="color: red;">AJAX Failed: ' + error + '<br>Status: ' + status + '<br>Response: ' + xhr.responseText + '</div>');
                    });
                });
            });
            </script>
        </div>
        <?php
    }
    
    /**
     * Add meta boxes to property edit page
     */
    public function add_meta_boxes() {
        add_meta_box(
            'barefoot_property_details',
            __('Barefoot Property Details', 'barefoot-properties'),
            array($this, 'property_details_meta_box'),
            'barefoot_property',
            'normal',
            'high'
        );
        
        add_meta_box(
            'barefoot_property_location',
            __('Location Information', 'barefoot-properties'),
            array($this, 'property_location_meta_box'),
            'barefoot_property',
            'side',
            'default'
        );
        
        add_meta_box(
            'barefoot_property_pricing',
            __('Pricing Information', 'barefoot-properties'),
            array($this, 'property_pricing_meta_box'),
            'barefoot_property',
            'side',
            'default'
        );
    }
    
    /**
     * Property details meta box
     */
    public function property_details_meta_box($post) {
        wp_nonce_field('barefoot_property_meta', 'barefoot_property_nonce');
        
        $property_id = get_post_meta($post->ID, '_barefoot_property_id', true);
        $occupancy = get_post_meta($post->ID, '_barefoot_occupancy', true);
        $bedrooms = get_post_meta($post->ID, '_barefoot_bedrooms', true);
        $bathrooms = get_post_meta($post->ID, '_barefoot_bathrooms', true);
        $property_type = get_post_meta($post->ID, '_barefoot_property_type', true);
        $featured = get_post_meta($post->ID, '_barefoot_featured', true);
        $last_sync = get_post_meta($post->ID, '_barefoot_last_sync', true);
        
        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="barefoot_property_id"><?php _e('Barefoot Property ID', 'barefoot-properties'); ?></label>
                </th>
                <td>
                    <input type="text" id="barefoot_property_id" name="barefoot_property_id" 
                           value="<?php echo esc_attr($property_id); ?>" readonly>
                    <p class="description"><?php _e('This ID is automatically assigned by the Barefoot API', 'barefoot-properties'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="barefoot_occupancy"><?php _e('Maximum Occupancy', 'barefoot-properties'); ?></label>
                </th>
                <td>
                    <input type="number" id="barefoot_occupancy" name="barefoot_occupancy" 
                           value="<?php echo esc_attr($occupancy); ?>" min="1" max="50">
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="barefoot_bedrooms"><?php _e('Bedrooms', 'barefoot-properties'); ?></label>
                </th>
                <td>
                    <input type="number" id="barefoot_bedrooms" name="barefoot_bedrooms" 
                           value="<?php echo esc_attr($bedrooms); ?>" min="0" max="20">
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="barefoot_bathrooms"><?php _e('Bathrooms', 'barefoot-properties'); ?></label>
                </th>
                <td>
                    <input type="number" id="barefoot_bathrooms" name="barefoot_bathrooms" 
                           value="<?php echo esc_attr($bathrooms); ?>" min="0" max="20" step="0.5">
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="barefoot_property_type"><?php _e('Property Type', 'barefoot-properties'); ?></label>
                </th>
                <td>
                    <input type="text" id="barefoot_property_type" name="barefoot_property_type" 
                           value="<?php echo esc_attr($property_type); ?>">
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="barefoot_featured"><?php _e('Featured Property', 'barefoot-properties'); ?></label>
                </th>
                <td>
                    <input type="checkbox" id="barefoot_featured" name="barefoot_featured" value="1" 
                           <?php checked($featured, '1'); ?>>
                    <label for="barefoot_featured"><?php _e('Mark this property as featured', 'barefoot-properties'); ?></label>
                </td>
            </tr>
            <?php if ($last_sync): ?>
            <tr>
                <th scope="row"><?php _e('Last Synchronized', 'barefoot-properties'); ?></th>
                <td>
                    <code><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($last_sync))); ?></code>
                </td>
            </tr>
            <?php endif; ?>
        </table>
        <?php
    }
    
    /**
     * Property location meta box
     */
    public function property_location_meta_box($post) {
        $city = get_post_meta($post->ID, '_barefoot_city', true);
        $state = get_post_meta($post->ID, '_barefoot_state', true);
        $zip = get_post_meta($post->ID, '_barefoot_zip', true);
        $latitude = get_post_meta($post->ID, '_barefoot_latitude', true);
        $longitude = get_post_meta($post->ID, '_barefoot_longitude', true);
        
        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="barefoot_city"><?php _e('City', 'barefoot-properties'); ?></label>
                </th>
                <td>
                    <input type="text" id="barefoot_city" name="barefoot_city" 
                           value="<?php echo esc_attr($city); ?>" style="width: 100%;">
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="barefoot_state"><?php _e('State', 'barefoot-properties'); ?></label>
                </th>
                <td>
                    <input type="text" id="barefoot_state" name="barefoot_state" 
                           value="<?php echo esc_attr($state); ?>" style="width: 100%;">
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="barefoot_zip"><?php _e('ZIP Code', 'barefoot-properties'); ?></label>
                </th>
                <td>
                    <input type="text" id="barefoot_zip" name="barefoot_zip" 
                           value="<?php echo esc_attr($zip); ?>" style="width: 100%;">
                </td>
            </tr>
            <?php if ($latitude && $longitude): ?>
            <tr>
                <th scope="row"><?php _e('Coordinates', 'barefoot-properties'); ?></th>
                <td>
                    <small><?php echo esc_html($latitude . ', ' . $longitude); ?></small>
                </td>
            </tr>
            <?php endif; ?>
        </table>
        <?php
    }
    
    /**
     * Property pricing meta box
     */
    public function property_pricing_meta_box($post) {
        $min_price = get_post_meta($post->ID, '_barefoot_min_price', true);
        $max_price = get_post_meta($post->ID, '_barefoot_max_price', true);
        $min_days = get_post_meta($post->ID, '_barefoot_min_days', true);
        
        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="barefoot_min_price"><?php _e('Min Price/Night', 'barefoot-properties'); ?></label>
                </th>
                <td>
                    <input type="number" id="barefoot_min_price" name="barefoot_min_price" 
                           value="<?php echo esc_attr($min_price); ?>" min="0" step="0.01" style="width: 100%;">
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="barefoot_max_price"><?php _e('Max Price/Night', 'barefoot-properties'); ?></label>
                </th>
                <td>
                    <input type="number" id="barefoot_max_price" name="barefoot_max_price" 
                           value="<?php echo esc_attr($max_price); ?>" min="0" step="0.01" style="width: 100%;">
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="barefoot_min_days"><?php _e('Minimum Stay (nights)', 'barefoot-properties'); ?></label>
                </th>
                <td>
                    <input type="number" id="barefoot_min_days" name="barefoot_min_days" 
                           value="<?php echo esc_attr($min_days); ?>" min="1" style="width: 100%;">
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Save property meta data
     */
    public function save_property_meta($post_id) {
        if (!isset($_POST['barefoot_property_nonce']) || 
            !wp_verify_nonce($_POST['barefoot_property_nonce'], 'barefoot_property_meta')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        $fields = array(
            'barefoot_occupancy' => '_barefoot_occupancy',
            'barefoot_bedrooms' => '_barefoot_bedrooms',
            'barefoot_bathrooms' => '_barefoot_bathrooms',
            'barefoot_property_type' => '_barefoot_property_type',
            'barefoot_city' => '_barefoot_city',
            'barefoot_state' => '_barefoot_state',
            'barefoot_zip' => '_barefoot_zip',
            'barefoot_min_price' => '_barefoot_min_price',
            'barefoot_max_price' => '_barefoot_max_price',
            'barefoot_min_days' => '_barefoot_min_days',
        );
        
        foreach ($fields as $field => $meta_key) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, $meta_key, sanitize_text_field($_POST[$field]));
            }
        }
        
        // Handle checkbox for featured
        $featured = isset($_POST['barefoot_featured']) ? '1' : '0';
        update_post_meta($post_id, '_barefoot_featured', $featured);
    }
    
    /**
     * Add custom columns to property list
     */
    public function add_property_columns($columns) {
        $new_columns = array();
        
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            
            if ($key === 'title') {
                $new_columns['property_id'] = __('Property ID', 'barefoot-properties');
                $new_columns['occupancy'] = __('Occupancy', 'barefoot-properties');
                $new_columns['location'] = __('Location', 'barefoot-properties');
                $new_columns['price_range'] = __('Price Range', 'barefoot-properties');
                $new_columns['last_sync'] = __('Last Sync', 'barefoot-properties');
            }
        }
        
        return $new_columns;
    }
    
    /**
     * Display content for custom columns
     */
    public function property_column_content($column, $post_id) {
        switch ($column) {
            case 'property_id':
                $property_id = get_post_meta($post_id, '_barefoot_property_id', true);
                echo $property_id ? esc_html($property_id) : '—';
                break;
                
            case 'occupancy':
                $occupancy = get_post_meta($post_id, '_barefoot_occupancy', true);
                echo $occupancy ? esc_html($occupancy) . ' ' . __('guests', 'barefoot-properties') : '—';
                break;
                
            case 'location':
                $city = get_post_meta($post_id, '_barefoot_city', true);
                $state = get_post_meta($post_id, '_barefoot_state', true);
                if ($city || $state) {
                    echo esc_html($city . ($city && $state ? ', ' : '') . $state);
                } else {
                    echo '—';
                }
                break;
                
            case 'price_range':
                $min_price = get_post_meta($post_id, '_barefoot_min_price', true);
                $max_price = get_post_meta($post_id, '_barefoot_max_price', true);
                if ($min_price || $max_price) {
                    if ($min_price && $max_price) {
                        echo '$' . number_format($min_price) . ' - $' . number_format($max_price);
                    } elseif ($min_price) {
                        echo __('From', 'barefoot-properties') . ' $' . number_format($min_price);
                    } else {
                        echo __('Up to', 'barefoot-properties') . ' $' . number_format($max_price);
                    }
                } else {
                    echo '—';
                }
                break;
                
            case 'last_sync':
                $last_sync = get_post_meta($post_id, '_barefoot_last_sync', true);
                if ($last_sync) {
                    echo esc_html(human_time_diff(strtotime($last_sync), current_time('timestamp'))) . ' ' . __('ago', 'barefoot-properties');
                } else {
                    echo '—';
                }
                break;
        }
    }
    
    // Settings callback functions
    public function general_section_callback() {
        echo '<p>' . __('Configure general settings for the Barefoot Properties plugin.', 'barefoot-properties') . '</p>';
    }
    
    public function sync_frequency_callback() {
        $value = get_option('barefoot_sync_frequency', 'daily');
        echo '<select name="barefoot_sync_frequency">';
        echo '<option value="hourly"' . selected($value, 'hourly', false) . '>' . __('Hourly', 'barefoot-properties') . '</option>';
        echo '<option value="daily"' . selected($value, 'daily', false) . '>' . __('Daily', 'barefoot-properties') . '</option>';
        echo '<option value="weekly"' . selected($value, 'weekly', false) . '>' . __('Weekly', 'barefoot-properties') . '</option>';
        echo '<option value="manual"' . selected($value, 'manual', false) . '>' . __('Manual Only', 'barefoot-properties') . '</option>';
        echo '</select>';
    }
    
    public function debug_mode_callback() {
        $value = get_option('barefoot_debug_mode', 0);
        echo '<input type="checkbox" name="barefoot_debug_mode" value="1"' . checked($value, 1, false) . '>';
        echo '<label for="barefoot_debug_mode"> ' . __('Enable debug logging', 'barefoot-properties') . '</label>';
    }
    
    public function auto_sync_callback() {
        $value = get_option('barefoot_auto_sync', 0);
        echo '<input type="checkbox" name="barefoot_auto_sync" value="1"' . checked($value, 1, false) . '>';
        echo '<label for="barefoot_auto_sync"> ' . __('Enable automatic synchronization', 'barefoot-properties') . '</label>';
    }
    
    public function featured_count_callback() {
        $value = get_option('barefoot_featured_count', 3);
        echo '<input type="number" name="barefoot_featured_count" value="' . esc_attr($value) . '" min="1" max="20">';
        echo '<p class="description">' . __('Number of featured properties to display in widgets', 'barefoot-properties') . '</p>';
    }
    
    public function contact_email_callback() {
        $value = get_option('barefoot_contact_email', '');
        echo '<input type="email" name="barefoot_contact_email" value="' . esc_attr($value) . '" style="width: 300px;">';
    }
    
    public function contact_phone_callback() {
        $value = get_option('barefoot_contact_phone', '');
        echo '<input type="text" name="barefoot_contact_phone" value="' . esc_attr($value) . '" style="width: 300px;">';
    }
    
    // Dashboard widget rendering methods
    private function render_property_stats() {
        $total_properties = wp_count_posts('barefoot_property');
        $published = $total_properties->publish ?? 0;
        $drafts = $total_properties->draft ?? 0;
        
        $featured_count = get_posts(array(
            'post_type' => 'barefoot_property',
            'meta_key' => '_barefoot_featured',
            'meta_value' => '1',
            'posts_per_page' => -1,
            'fields' => 'ids'
        ));
        
        echo '<ul>';
        echo '<li>' . sprintf(__('Published Properties: <strong>%d</strong>', 'barefoot-properties'), $published) . '</li>';
        echo '<li>' . sprintf(__('Draft Properties: <strong>%d</strong>', 'barefoot-properties'), $drafts) . '</li>';
        echo '<li>' . sprintf(__('Featured Properties: <strong>%d</strong>', 'barefoot-properties'), count($featured_count)) . '</li>';
        echo '</ul>';
    }
    
    private function render_recent_activity() {
        $recent_properties = get_posts(array(
            'post_type' => 'barefoot_property',
            'posts_per_page' => 5,
            'orderby' => 'modified',
            'order' => 'DESC'
        ));
        
        if (empty($recent_properties)) {
            echo '<p>' . __('No recent activity.', 'barefoot-properties') . '</p>';
            return;
        }
        
        echo '<ul>';
        foreach ($recent_properties as $property) {
            $edit_link = get_edit_post_link($property->ID);
            echo '<li>';
            echo '<a href="' . esc_url($edit_link) . '">' . esc_html($property->post_title) . '</a>';
            echo '<br><small>' . human_time_diff(strtotime($property->post_modified), current_time('timestamp')) . ' ' . __('ago', 'barefoot-properties') . '</small>';
            echo '</li>';
        }
        echo '</ul>';
    }
    
    private function render_quick_actions() {
        echo '<div class="quick-actions">';
        echo '<a href="' . admin_url('admin.php?page=barefoot-sync') . '" class="button button-primary">' . __('Sync Properties', 'barefoot-properties') . '</a>';
        echo '<a href="' . admin_url('post-new.php?post_type=barefoot_property') . '" class="button button-secondary">' . __('Add Property', 'barefoot-properties') . '</a>';
        echo '<a href="' . admin_url('admin.php?page=barefoot-settings') . '" class="button button-secondary">' . __('Settings', 'barefoot-properties') . '</a>';
        echo '</div>';
    }
    
    private function render_api_status() {
        $api = new Barefoot_API();
        $status = $api->test_connection();
        
        if ($status['success']) {
            echo '<div class="api-status success">';
            echo '<span class="status-indicator">●</span> ' . __('Connected', 'barefoot-properties');
            echo '<p>' . esc_html($status['message']) . '</p>';
            echo '</div>';
        } else {
            echo '<div class="api-status error">';
            echo '<span class="status-indicator">●</span> ' . __('Connection Failed', 'barefoot-properties');
            echo '<p>' . esc_html($status['message']) . '</p>';
            echo '</div>';
        }
    }
    
    private function render_sync_history() {
        $sync_logs = get_option('barefoot_sync_history', array());
        
        if (empty($sync_logs)) {
            echo '<p>' . __('No sync history available.', 'barefoot-properties') . '</p>';
            return;
        }
        
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr>';
        echo '<th>' . __('Date', 'barefoot-properties') . '</th>';
        echo '<th>' . __('Status', 'barefoot-properties') . '</th>';
        echo '<th>' . __('Properties', 'barefoot-properties') . '</th>';
        echo '<th>' . __('Message', 'barefoot-properties') . '</th>';
        echo '</tr></thead>';
        echo '<tbody>';
        
        foreach (array_reverse(array_slice($sync_logs, -10)) as $log) {
            echo '<tr>';
            echo '<td>' . esc_html($log['date']) . '</td>';
            echo '<td>' . ($log['success'] ? '<span class="success">✓</span>' : '<span class="error">✗</span>') . '</td>';
            echo '<td>' . esc_html($log['count']) . '</td>';
            echo '<td>' . esc_html($log['message']) . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
    }
}