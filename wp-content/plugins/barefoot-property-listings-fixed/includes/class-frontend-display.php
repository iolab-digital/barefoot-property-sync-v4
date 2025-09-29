<?php
/**
 * Frontend Display Class
 * Handles frontend display of properties and booking functionality
 */

class Barefoot_Frontend_Display {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
    }
    
    public function init() {
        // Add custom query vars
        add_filter('query_vars', array($this, 'add_query_vars'));
        
        // Modify main query for property searches
        add_action('pre_get_posts', array($this, 'modify_main_query'));
        
        // Add meta boxes to property edit screen
        add_action('add_meta_boxes', array($this, 'add_property_meta_boxes'));
        
        // Save property meta data
        add_action('save_post_barefoot_property', array($this, 'save_property_meta'));
        
        // Add custom columns to property list
        add_filter('manage_barefoot_property_posts_columns', array($this, 'add_property_columns'));
        add_action('manage_barefoot_property_posts_custom_column', array($this, 'display_property_columns'), 10, 2);
    }
    
    /**
     * Add custom query vars
     */
    public function add_query_vars($vars) {
        $vars[] = 'property_search';
        $vars[] = 'property_type';
        $vars[] = 'location';
        $vars[] = 'min_price';
        $vars[] = 'max_price';
        $vars[] = 'bedrooms';
        $vars[] = 'bathrooms';
        
        return $vars;
    }
    
    /**
     * Modify main query for property searches
     */
    public function modify_main_query($query) {
        if (!is_admin() && $query->is_main_query()) {
            if (is_post_type_archive('barefoot_property') || is_tax(array('property_type', 'amenity', 'location'))) {
                // Handle property search
                $search_term = get_query_var('property_search');
                if (!empty($search_term)) {
                    $query->set('s', $search_term);
                }
                
                // Handle price range
                $min_price = get_query_var('min_price');
                $max_price = get_query_var('max_price');
                
                if (!empty($min_price) || !empty($max_price)) {
                    $meta_query = $query->get('meta_query') ?: array();
                    
                    if (!empty($min_price)) {
                        $meta_query[] = array(
                            'key' => '_barefoot_price',
                            'value' => intval($min_price),
                            'compare' => '>=',
                            'type' => 'NUMERIC'
                        );
                    }
                    
                    if (!empty($max_price)) {
                        $meta_query[] = array(
                            'key' => '_barefoot_price',
                            'value' => intval($max_price),
                            'compare' => '<=',
                            'type' => 'NUMERIC'
                        );
                    }
                    
                    $query->set('meta_query', $meta_query);
                }
                
                // Handle bedrooms/bathrooms
                $bedrooms = get_query_var('bedrooms');
                $bathrooms = get_query_var('bathrooms');
                
                if (!empty($bedrooms) || !empty($bathrooms)) {
                    $meta_query = $query->get('meta_query') ?: array();
                    
                    if (!empty($bedrooms)) {
                        $meta_query[] = array(
                            'key' => '_barefoot_bedrooms',
                            'value' => intval($bedrooms),
                            'compare' => '>=',
                            'type' => 'NUMERIC'
                        );
                    }
                    
                    if (!empty($bathrooms)) {
                        $meta_query[] = array(
                            'key' => '_barefoot_bathrooms',
                            'value' => intval($bathrooms),
                            'compare' => '>=',
                            'type' => 'NUMERIC'
                        );
                    }
                    
                    $query->set('meta_query', $meta_query);
                }
            }
        }
    }
    
    /**
     * Add property meta boxes
     */
    public function add_property_meta_boxes() {
        add_meta_box(
            'barefoot_property_details',
            __('Property Details', 'barefoot-properties'),
            array($this, 'property_details_meta_box'),
            'barefoot_property',
            'normal',
            'high'
        );
        
        add_meta_box(
            'barefoot_property_location',
            __('Location', 'barefoot-properties'),
            array($this, 'property_location_meta_box'),
            'barefoot_property',
            'normal',
            'high'
        );
        
        add_meta_box(
            'barefoot_property_amenities',
            __('Amenities & Features', 'barefoot-properties'),
            array($this, 'property_amenities_meta_box'),
            'barefoot_property',
            'normal',
            'default'
        );
        
        add_meta_box(
            'barefoot_sync_info',
            __('Barefoot Sync Information', 'barefoot-properties'),
            array($this, 'sync_info_meta_box'),
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
        
        $bedrooms = get_post_meta($post->ID, '_barefoot_bedrooms', true);
        $bathrooms = get_post_meta($post->ID, '_barefoot_bathrooms', true);
        $max_guests = get_post_meta($post->ID, '_barefoot_max_guests', true);
        $square_feet = get_post_meta($post->ID, '_barefoot_square_feet', true);
        $price = get_post_meta($post->ID, '_barefoot_price', true);
        $min_stay = get_post_meta($post->ID, '_barefoot_min_stay', true);
        $max_stay = get_post_meta($post->ID, '_barefoot_max_stay', true);
        $check_in = get_post_meta($post->ID, '_barefoot_check_in', true);
        $check_out = get_post_meta($post->ID, '_barefoot_check_out', true);
        
        ?>
        <table class="form-table">
            <tr>
                <th><label for="barefoot_bedrooms"><?php _e('Bedrooms', 'barefoot-properties'); ?></label></th>
                <td><input type="number" id="barefoot_bedrooms" name="barefoot_bedrooms" value="<?php echo esc_attr($bedrooms); ?>" min="0" /></td>
            </tr>
            <tr>
                <th><label for="barefoot_bathrooms"><?php _e('Bathrooms', 'barefoot-properties'); ?></label></th>
                <td><input type="number" id="barefoot_bathrooms" name="barefoot_bathrooms" value="<?php echo esc_attr($bathrooms); ?>" min="0" step="0.5" /></td>
            </tr>
            <tr>
                <th><label for="barefoot_max_guests"><?php _e('Max Guests', 'barefoot-properties'); ?></label></th>
                <td><input type="number" id="barefoot_max_guests" name="barefoot_max_guests" value="<?php echo esc_attr($max_guests); ?>" min="1" /></td>
            </tr>
            <tr>
                <th><label for="barefoot_square_feet"><?php _e('Square Feet', 'barefoot-properties'); ?></label></th>
                <td><input type="number" id="barefoot_square_feet" name="barefoot_square_feet" value="<?php echo esc_attr($square_feet); ?>" min="0" /></td>
            </tr>
            <tr>
                <th><label for="barefoot_price"><?php _e('Base Price (per night)', 'barefoot-properties'); ?></label></th>
                <td><input type="number" id="barefoot_price" name="barefoot_price" value="<?php echo esc_attr($price); ?>" min="0" step="0.01" /></td>
            </tr>
            <tr>
                <th><label for="barefoot_min_stay"><?php _e('Minimum Stay (nights)', 'barefoot-properties'); ?></label></th>
                <td><input type="number" id="barefoot_min_stay" name="barefoot_min_stay" value="<?php echo esc_attr($min_stay); ?>" min="1" /></td>
            </tr>
            <tr>
                <th><label for="barefoot_max_stay"><?php _e('Maximum Stay (nights)', 'barefoot-properties'); ?></label></th>
                <td><input type="number" id="barefoot_max_stay" name="barefoot_max_stay" value="<?php echo esc_attr($max_stay); ?>" min="1" /></td>
            </tr>
            <tr>
                <th><label for="barefoot_check_in"><?php _e('Check-in Time', 'barefoot-properties'); ?></label></th>
                <td><input type="time" id="barefoot_check_in" name="barefoot_check_in" value="<?php echo esc_attr($check_in); ?>" /></td>
            </tr>
            <tr>
                <th><label for="barefoot_check_out"><?php _e('Check-out Time', 'barefoot-properties'); ?></label></th>
                <td><input type="time" id="barefoot_check_out" name="barefoot_check_out" value="<?php echo esc_attr($check_out); ?>" /></td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Property location meta box
     */
    public function property_location_meta_box($post) {
        $address = get_post_meta($post->ID, '_barefoot_address', true);
        $city = get_post_meta($post->ID, '_barefoot_city', true);
        $state = get_post_meta($post->ID, '_barefoot_state', true);
        $zip = get_post_meta($post->ID, '_barefoot_zip', true);
        $country = get_post_meta($post->ID, '_barefoot_country', true);
        $latitude = get_post_meta($post->ID, '_barefoot_latitude', true);
        $longitude = get_post_meta($post->ID, '_barefoot_longitude', true);
        
        ?>
        <table class="form-table">
            <tr>
                <th><label for="barefoot_address"><?php _e('Address', 'barefoot-properties'); ?></label></th>
                <td><input type="text" id="barefoot_address" name="barefoot_address" value="<?php echo esc_attr($address); ?>" class="widefat" /></td>
            </tr>
            <tr>
                <th><label for="barefoot_city"><?php _e('City', 'barefoot-properties'); ?></label></th>
                <td><input type="text" id="barefoot_city" name="barefoot_city" value="<?php echo esc_attr($city); ?>" /></td>
            </tr>
            <tr>
                <th><label for="barefoot_state"><?php _e('State', 'barefoot-properties'); ?></label></th>
                <td><input type="text" id="barefoot_state" name="barefoot_state" value="<?php echo esc_attr($state); ?>" /></td>
            </tr>
            <tr>
                <th><label for="barefoot_zip"><?php _e('ZIP Code', 'barefoot-properties'); ?></label></th>
                <td><input type="text" id="barefoot_zip" name="barefoot_zip" value="<?php echo esc_attr($zip); ?>" /></td>
            </tr>
            <tr>
                <th><label for="barefoot_country"><?php _e('Country', 'barefoot-properties'); ?></label></th>
                <td><input type="text" id="barefoot_country" name="barefoot_country" value="<?php echo esc_attr($country); ?>" /></td>
            </tr>
            <tr>
                <th><label for="barefoot_latitude"><?php _e('Latitude', 'barefoot-properties'); ?></label></th>
                <td><input type="text" id="barefoot_latitude" name="barefoot_latitude" value="<?php echo esc_attr($latitude); ?>" /></td>
            </tr>
            <tr>
                <th><label for="barefoot_longitude"><?php _e('Longitude', 'barefoot-properties'); ?></label></th>
                <td><input type="text" id="barefoot_longitude" name="barefoot_longitude" value="<?php echo esc_attr($longitude); ?>" /></td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Property amenities meta box
     */
    public function property_amenities_meta_box($post) {
        $pets_allowed = get_post_meta($post->ID, '_barefoot_pets_allowed', true);
        $smoking_allowed = get_post_meta($post->ID, '_barefoot_smoking_allowed', true);
        $pool = get_post_meta($post->ID, '_barefoot_pool', true);
        $hot_tub = get_post_meta($post->ID, '_barefoot_hot_tub', true);
        $internet = get_post_meta($post->ID, '_barefoot_internet', true);
        $air_conditioning = get_post_meta($post->ID, '_barefoot_air_conditioning', true);
        $heating = get_post_meta($post->ID, '_barefoot_heating', true);
        $parking = get_post_meta($post->ID, '_barefoot_parking', true);
        $kitchen = get_post_meta($post->ID, '_barefoot_kitchen', true);
        $washer_dryer = get_post_meta($post->ID, '_barefoot_washer_dryer', true);
        $tv_cable = get_post_meta($post->ID, '_barefoot_tv_cable', true);
        
        ?>
        <div class="barefoot-amenities-grid">
            <label><input type="checkbox" name="barefoot_pets_allowed" value="1" <?php checked(1, $pets_allowed); ?> /> <?php _e('Pets Allowed', 'barefoot-properties'); ?></label>
            <label><input type="checkbox" name="barefoot_smoking_allowed" value="1" <?php checked(1, $smoking_allowed); ?> /> <?php _e('Smoking Allowed', 'barefoot-properties'); ?></label>
            <label><input type="checkbox" name="barefoot_pool" value="1" <?php checked(1, $pool); ?> /> <?php _e('Pool', 'barefoot-properties'); ?></label>
            <label><input type="checkbox" name="barefoot_hot_tub" value="1" <?php checked(1, $hot_tub); ?> /> <?php _e('Hot Tub', 'barefoot-properties'); ?></label>
            <label><input type="checkbox" name="barefoot_internet" value="1" <?php checked(1, $internet); ?> /> <?php _e('Internet/WiFi', 'barefoot-properties'); ?></label>
            <label><input type="checkbox" name="barefoot_air_conditioning" value="1" <?php checked(1, $air_conditioning); ?> /> <?php _e('Air Conditioning', 'barefoot-properties'); ?></label>
            <label><input type="checkbox" name="barefoot_heating" value="1" <?php checked(1, $heating); ?> /> <?php _e('Heating', 'barefoot-properties'); ?></label>
            <label><input type="checkbox" name="barefoot_parking" value="1" <?php checked(1, $parking); ?> /> <?php _e('Parking', 'barefoot-properties'); ?></label>
            <label><input type="checkbox" name="barefoot_kitchen" value="1" <?php checked(1, $kitchen); ?> /> <?php _e('Full Kitchen', 'barefoot-properties'); ?></label>
            <label><input type="checkbox" name="barefoot_washer_dryer" value="1" <?php checked(1, $washer_dryer); ?> /> <?php _e('Washer/Dryer', 'barefoot-properties'); ?></label>
            <label><input type="checkbox" name="barefoot_tv_cable" value="1" <?php checked(1, $tv_cable); ?> /> <?php _e('TV/Cable', 'barefoot-properties'); ?></label>
        </div>
        
        <style>
        .barefoot-amenities-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
            margin: 15px 0;
        }
        .barefoot-amenities-grid label {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        </style>
        <?php
    }
    
    /**
     * Sync info meta box
     */
    public function sync_info_meta_box($post) {
        $property_id = get_post_meta($post->ID, '_barefoot_property_id', true);
        $property_code = get_post_meta($post->ID, '_barefoot_property_code', true);
        $last_sync = get_post_meta($post->ID, '_barefoot_last_sync', true);
        
        ?>
        <p><strong><?php _e('Barefoot Property ID:', 'barefoot-properties'); ?></strong><br>
        <code><?php echo esc_html($property_id ?: 'Not synced'); ?></code></p>
        
        <p><strong><?php _e('Property Code:', 'barefoot-properties'); ?></strong><br>
        <code><?php echo esc_html($property_code ?: 'N/A'); ?></code></p>
        
        <p><strong><?php _e('Last Sync:', 'barefoot-properties'); ?></strong><br>
        <?php echo $last_sync ? esc_html($last_sync) : __('Never', 'barefoot-properties'); ?></p>
        
        <?php if ($property_id): ?>
        <p><a href="<?php echo admin_url('admin.php?page=barefoot-sync'); ?>" class="button button-secondary">
        <?php _e('Sync Properties', 'barefoot-properties'); ?></a></p>
        <?php endif; ?>
        <?php
    }
    
    /**
     * Save property meta data
     */
    public function save_property_meta($post_id) {
        if (!isset($_POST['barefoot_property_nonce']) || !wp_verify_nonce($_POST['barefoot_property_nonce'], 'barefoot_property_meta')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Save all meta fields
        $meta_fields = array(
            'barefoot_bedrooms' => '_barefoot_bedrooms',
            'barefoot_bathrooms' => '_barefoot_bathrooms',
            'barefoot_max_guests' => '_barefoot_max_guests',
            'barefoot_square_feet' => '_barefoot_square_feet',
            'barefoot_price' => '_barefoot_price',
            'barefoot_min_stay' => '_barefoot_min_stay',
            'barefoot_max_stay' => '_barefoot_max_stay',
            'barefoot_check_in' => '_barefoot_check_in',
            'barefoot_check_out' => '_barefoot_check_out',
            'barefoot_address' => '_barefoot_address',
            'barefoot_city' => '_barefoot_city',
            'barefoot_state' => '_barefoot_state',
            'barefoot_zip' => '_barefoot_zip',
            'barefoot_country' => '_barefoot_country',
            'barefoot_latitude' => '_barefoot_latitude',
            'barefoot_longitude' => '_barefoot_longitude'
        );
        
        foreach ($meta_fields as $field => $meta_key) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, $meta_key, sanitize_text_field($_POST[$field]));
            }
        }
        
        // Save checkbox fields
        $checkbox_fields = array(
            'barefoot_pets_allowed' => '_barefoot_pets_allowed',
            'barefoot_smoking_allowed' => '_barefoot_smoking_allowed',
            'barefoot_pool' => '_barefoot_pool',
            'barefoot_hot_tub' => '_barefoot_hot_tub',
            'barefoot_internet' => '_barefoot_internet',
            'barefoot_air_conditioning' => '_barefoot_air_conditioning',
            'barefoot_heating' => '_barefoot_heating',
            'barefoot_parking' => '_barefoot_parking',
            'barefoot_kitchen' => '_barefoot_kitchen',
            'barefoot_washer_dryer' => '_barefoot_washer_dryer',
            'barefoot_tv_cable' => '_barefoot_tv_cable'
        );
        
        foreach ($checkbox_fields as $field => $meta_key) {
            $value = isset($_POST[$field]) ? 1 : 0;
            update_post_meta($post_id, $meta_key, $value);
        }
    }
    
    /**
     * Add custom columns to property list
     */
    public function add_property_columns($columns) {
        $new_columns = array();
        
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            
            if ($key === 'title') {
                $new_columns['barefoot_id'] = __('Barefoot ID', 'barefoot-properties');
                $new_columns['property_details'] = __('Details', 'barefoot-properties');
                $new_columns['price'] = __('Price', 'barefoot-properties');
                $new_columns['last_sync'] = __('Last Sync', 'barefoot-properties');
            }
        }
        
        return $new_columns;
    }
    
    /**
     * Display custom column content
     */
    public function display_property_columns($column, $post_id) {
        switch ($column) {
            case 'barefoot_id':
                $barefoot_id = get_post_meta($post_id, '_barefoot_property_id', true);
                echo $barefoot_id ? esc_html($barefoot_id) : '—';
                break;
                
            case 'property_details':
                $bedrooms = get_post_meta($post_id, '_barefoot_bedrooms', true);
                $bathrooms = get_post_meta($post_id, '_barefoot_bathrooms', true);
                $max_guests = get_post_meta($post_id, '_barefoot_max_guests', true);
                
                $details = array();
                if ($bedrooms) $details[] = $bedrooms . ' bed';
                if ($bathrooms) $details[] = $bathrooms . ' bath';
                if ($max_guests) $details[] = $max_guests . ' guests';
                
                echo implode(' • ', $details) ?: '—';
                break;
                
            case 'price':
                $price = get_post_meta($post_id, '_barefoot_price', true);
                echo $price ? '$' . number_format($price) . '/night' : '—';
                break;
                
            case 'last_sync':
                $last_sync = get_post_meta($post_id, '_barefoot_last_sync', true);
                echo $last_sync ? esc_html($last_sync) : __('Never', 'barefoot-properties');
                break;
        }
    }
}

// Initialize the frontend display
new Barefoot_Frontend_Display();

?>