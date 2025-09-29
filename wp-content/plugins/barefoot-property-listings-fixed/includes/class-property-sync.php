<?php
/**
 * Property Synchronization Class
 * Handles syncing properties from Barefoot API to WordPress
 */

class Barefoot_Property_Sync {
    
    private $api;
    
    public function __construct() {
        $this->api = new Barefoot_API();
    }
    
    /**
     * Sync all properties from Barefoot API
     */
    public function sync_all_properties() {
        $result = array(
            'success' => false,
            'message' => '',
            'count' => 0,
            'errors' => array()
        );
        
        try {
            // Get properties from API
            $api_response = $this->api->get_all_properties();
            
            if (!$api_response['success']) {
                $result['message'] = $api_response['message'];
                return $result;
            }
            
            $properties = $api_response['data'];
            $synced_count = 0;
            
            foreach ($properties as $property_data) {
                $sync_result = $this->sync_single_property($property_data);
                
                if ($sync_result['success']) {
                    $synced_count++;
                } else {
                    $result['errors'][] = 'Property sync failed: ' . $sync_result['message'];
                }
            }
            
            $result['success'] = true;
            $result['count'] = $synced_count;
            $result['message'] = "Successfully synced {$synced_count} properties";
            
            if (!empty($result['errors'])) {
                $result['message'] .= ' (' . count($result['errors']) . ' errors occurred)';
            }
            
        } catch (Exception $e) {
            $result['message'] = 'Sync error: ' . $e->getMessage();
            error_log('Barefoot Property Sync Error: ' . $e->getMessage());
        }
        
        return $result;
    }
    
    /**
     * Sync a single property
     */
    public function sync_single_property($property_data) {
        try {
            // Debug: Log the property data structure
            error_log('Syncing property data: ' . print_r($property_data, true));
            
            // Extract property information using correct WSDL field names
            $property_id = $this->get_property_field($property_data, 'PropertyID'); // Note: PropertyID not PropertyId
            $property_name = $this->get_property_field($property_data, 'Name'); // Note: Name not PropertyName  
            $property_description = $this->get_property_field($property_data, 'Description');
            
            // Debug: Log extracted values
            error_log("Extracted values - ID: {$property_id}, Name: {$property_name}");
            
            // Try alternative field names if primary ones don't work (for backwards compatibility)
            if (empty($property_id)) {
                $property_id = $this->get_property_field($property_data, 'PropertyId') ?: 
                              $this->get_property_field($property_data, 'ID') ?: 
                              $this->get_property_field($property_data, 'id');
            }
            
            if (empty($property_name)) {
                $property_name = $this->get_property_field($property_data, 'PropertyName') ?: 
                                $this->get_property_field($property_data, 'PropertyTitle') ?: 
                                $this->get_property_field($property_data, 'Title');
            }
            
            error_log("After alternative fields - ID: {$property_id}, Name: {$property_name}");
            
            if (empty($property_id) || empty($property_name)) {
                // List all available fields for debugging
                $available_fields = array();
                if (is_object($property_data)) {
                    $available_fields = get_object_vars($property_data);
                } elseif (is_array($property_data)) {
                    $available_fields = array_keys($property_data);
                }
                
                error_log('Available property fields: ' . implode(', ', $available_fields));
                
                return array(
                    'success' => false,
                    'message' => 'Missing required property data (ID or Name). Available fields: ' . implode(', ', $available_fields)
                );
            }
            
            // Check if property already exists
            $existing_post = $this->find_existing_property($property_id);
            
            // Prepare post data
            $post_data = array(
                'post_type' => 'barefoot_property',
                'post_title' => sanitize_text_field($property_name),
                'post_content' => wp_kses_post($property_description),
                'post_status' => 'publish',
                'post_author' => 1,
                'meta_input' => $this->prepare_property_meta($property_data)
            );
            
            if ($existing_post) {
                // Update existing property
                $post_data['ID'] = $existing_post->ID;
                $post_id = wp_update_post($post_data);
            } else {
                // Create new property
                $post_id = wp_insert_post($post_data);
            }
            
            if (is_wp_error($post_id)) {
                return array(
                    'success' => false,
                    'message' => 'Failed to save property: ' . $post_id->get_error_message()
                );
            }
            
            // Set property taxonomies
            $this->set_property_taxonomies($post_id, $property_data);
            
            // Sync property images
            $this->sync_property_images($post_id, $property_id);
            
            // Sync property rates
            $this->sync_property_rates($post_id, $property_id);
            
            return array(
                'success' => true,
                'post_id' => $post_id,
                'message' => 'Property synced successfully'
            );
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => 'Sync error: ' . $e->getMessage()
            );
        }
    }
    
    /**
     * Prepare property meta fields
     */
    private function prepare_property_meta($property_data) {
        $meta = array(
            // Use correct WSDL field names
            '_barefoot_property_id' => $this->get_property_field($property_data, 'PropertyID'),
            '_barefoot_property_code' => $this->get_property_field($property_data, 'Keyboardid'),
            '_barefoot_occupancy' => $this->get_property_field($property_data, 'Occupancy'),
            '_barefoot_sleeps_beds' => $this->get_property_field($property_data, 'SleepsBeds'),
            '_barefoot_number_floors' => $this->get_property_field($property_data, 'NumberFloors'),
            '_barefoot_unit_type' => $this->get_property_field($property_data, 'UnitType'),
            '_barefoot_address' => $this->get_property_field($property_data, 'PropAddress'),
            '_barefoot_address_new' => $this->get_property_field($property_data, 'PropAddressNew'),
            '_barefoot_street' => $this->get_property_field($property_data, 'Street'),
            '_barefoot_street2' => $this->get_property_field($property_data, 'Street2'),
            '_barefoot_city' => $this->get_property_field($property_data, 'City'),
            '_barefoot_state' => $this->get_property_field($property_data, 'State'),
            '_barefoot_zip' => $this->get_property_field($property_data, 'Zip'),
            '_barefoot_country' => $this->get_property_field($property_data, 'Country'),
            '_barefoot_latitude' => $this->get_property_field($property_data, 'Latitude'),
            '_barefoot_longitude' => $this->get_property_field($property_data, 'Longitude'),
            '_barefoot_min_price' => $this->get_property_field($property_data, 'Minprice'),
            '_barefoot_max_price' => $this->get_property_field($property_data, 'Maxprice'),
            '_barefoot_min_days' => $this->get_property_field($property_data, 'Mindays'),
            '_barefoot_property_type' => $this->get_property_field($property_data, 'PropertyType'),
            '_barefoot_status' => $this->get_property_field($property_data, 'Status'),
            '_barefoot_deadline' => $this->get_property_field($property_data, 'Deadline'),
            '_barefoot_image_path' => $this->get_property_field($property_data, 'Imagepath'),
            '_barefoot_property_title' => $this->get_property_field($property_data, 'PropertyTitle'),
            '_barefoot_ext_description' => $this->get_property_field($property_data, 'Extdescription'),
            '_barefoot_amenities' => $this->get_property_field($property_data, 'PropertyAmenities'),
            '_barefoot_registnumber' => $this->get_property_field($property_data, 'Registnumber'),
            '_barefoot_regist_expire_date' => $this->get_property_field($property_data, 'Registexpirdate'),
            '_barefoot_agent1' => $this->get_property_field($property_data, 'Agent1'),
            '_barefoot_agent2' => $this->get_property_field($property_data, 'Agent2'),
            '_barefoot_agent3' => $this->get_property_field($property_data, 'Agent3'),
            '_barefoot_last_sync' => current_time('mysql')
        );
        
        // Remove empty values
        return array_filter($meta, function($value) {
            return $value !== null && $value !== '';
        });
    }
    
    /**
     * Set property taxonomies
     */
    private function set_property_taxonomies($post_id, $property_data) {
        // Property type
        $property_type = $this->get_property_field($property_data, 'PropertyType');
        if ($property_type) {
            wp_set_object_terms($post_id, sanitize_text_field($property_type), 'property_type');
        }
        
        // Location (use city or region)
        $location = $this->get_property_field($property_data, 'City') ?: $this->get_property_field($property_data, 'Region');
        if ($location) {
            wp_set_object_terms($post_id, sanitize_text_field($location), 'location');
        }
        
        // Amenities based on property features
        $amenities = array();
        
        if ($this->get_property_field($property_data, 'Pool')) {
            $amenities[] = 'Pool';
        }
        if ($this->get_property_field($property_data, 'HotTub')) {
            $amenities[] = 'Hot Tub';
        }
        if ($this->get_property_field($property_data, 'Internet')) {
            $amenities[] = 'WiFi';
        }
        if ($this->get_property_field($property_data, 'Kitchen')) {
            $amenities[] = 'Kitchen';
        }
        if ($this->get_property_field($property_data, 'Parking')) {
            $amenities[] = 'Parking';
        }
        if ($this->get_property_field($property_data, 'PetsAllowed')) {
            $amenities[] = 'Pet Friendly';
        }
        
        if (!empty($amenities)) {
            wp_set_object_terms($post_id, $amenities, 'amenity');
        }
    }
    
    /**
     * Sync property images
     */
    private function sync_property_images($post_id, $property_id) {
        try {
            $images_response = $this->api->get_property_images($property_id);
            
            if (!$images_response['success'] || empty($images_response['data'])) {
                return;
            }
            
            $images = $images_response['data'];
            $featured_set = false;
            
            foreach ($images as $image_data) {
                $image_url = $this->get_property_field($image_data, 'ImageUrl') ?: $this->get_property_field($image_data, 'Url');
                $image_caption = $this->get_property_field($image_data, 'Caption') ?: $this->get_property_field($image_data, 'Description');
                
                if (empty($image_url)) {
                    continue;
                }
                
                // Download and attach image
                $attachment_id = $this->download_and_attach_image($image_url, $post_id, $image_caption);
                
                if ($attachment_id && !$featured_set) {
                    set_post_thumbnail($post_id, $attachment_id);
                    $featured_set = true;
                }
            }
            
        } catch (Exception $e) {
            error_log('Barefoot Image Sync Error: ' . $e->getMessage());
        }
    }
    
    /**
     * Download and attach image to post
     */
    private function download_and_attach_image($image_url, $post_id, $caption = '') {
        try {
            // Check if image already exists
            $existing_attachment = $this->find_existing_attachment($image_url, $post_id);
            if ($existing_attachment) {
                return $existing_attachment;
            }
            
            // Download image
            $response = wp_remote_get($image_url, array(
                'timeout' => 30
            ));
            
            if (is_wp_error($response)) {
                return false;
            }
            
            $image_data = wp_remote_retrieve_body($response);
            if (empty($image_data)) {
                return false;
            }
            
            // Get filename from URL
            $filename = basename(parse_url($image_url, PHP_URL_PATH));
            if (empty($filename)) {
                $filename = 'barefoot-property-' . $post_id . '-' . time() . '.jpg';
            }
            
            // Upload image
            $upload = wp_upload_bits($filename, null, $image_data);
            
            if ($upload['error']) {
                return false;
            }
            
            // Create attachment
            $attachment_data = array(
                'post_mime_type' => wp_check_filetype($filename)['type'],
                'post_title' => sanitize_file_name(pathinfo($filename, PATHINFO_FILENAME)),
                'post_content' => '',
                'post_excerpt' => $caption,
                'post_status' => 'inherit',
                'post_parent' => $post_id
            );
            
            $attachment_id = wp_insert_attachment($attachment_data, $upload['file'], $post_id);
            
            if (!is_wp_error($attachment_id)) {
                require_once(ABSPATH . 'wp-admin/includes/image.php');
                $attachment_metadata = wp_generate_attachment_metadata($attachment_id, $upload['file']);
                wp_update_attachment_metadata($attachment_id, $attachment_metadata);
                
                // Store original URL for future reference
                update_post_meta($attachment_id, '_barefoot_original_url', $image_url);
                
                return $attachment_id;
            }
            
        } catch (Exception $e) {
            error_log('Barefoot Image Download Error: ' . $e->getMessage());
        }
        
        return false;
    }
    
    /**
     * Sync property rates
     */
    private function sync_property_rates($post_id, $property_id) {
        try {
            $rates_response = $this->api->get_property_rates($property_id);
            
            if ($rates_response['success'] && !empty($rates_response['data'])) {
                update_post_meta($post_id, '_barefoot_rates_data', $rates_response['data']);
                update_post_meta($post_id, '_barefoot_rates_last_sync', current_time('mysql'));
            }
            
        } catch (Exception $e) {
            error_log('Barefoot Rates Sync Error: ' . $e->getMessage());
        }
    }
    
    /**
     * Find existing property by Barefoot ID
     */
    private function find_existing_property($property_id) {
        $posts = get_posts(array(
            'post_type' => 'barefoot_property',
            'meta_key' => '_barefoot_property_id',
            'meta_value' => $property_id,
            'posts_per_page' => 1,
            'post_status' => array('publish', 'draft', 'pending', 'private')
        ));
        
        return !empty($posts) ? $posts[0] : null;
    }
    
    /**
     * Find existing attachment by URL
     */
    private function find_existing_attachment($image_url, $post_id) {
        $attachments = get_posts(array(
            'post_type' => 'attachment',
            'meta_key' => '_barefoot_original_url',
            'meta_value' => $image_url,
            'post_parent' => $post_id,
            'posts_per_page' => 1
        ));
        
        return !empty($attachments) ? $attachments[0]->ID : false;
    }
    
    /**
     * Get field value from property data (handles different object structures)
     */
    private function get_property_field($property_data, $field_name) {
        if (is_array($property_data)) {
            return isset($property_data[$field_name]) ? $property_data[$field_name] : null;
        } elseif (is_object($property_data)) {
            return isset($property_data->$field_name) ? $property_data->$field_name : null;
        }
        
        return null;
    }
    
    /**
     * Clean up orphaned properties (not in Barefoot API anymore)
     */
    public function cleanup_orphaned_properties() {
        // Get all Barefoot properties from API
        $api_response = $this->api->get_all_properties();
        
        if (!$api_response['success']) {
            return array(
                'success' => false,
                'message' => 'Could not fetch properties from API for cleanup'
            );
        }
        
        $api_properties = $api_response['data'];
        $api_property_ids = array();
        
        foreach ($api_properties as $property_data) {
            $property_id = $this->get_property_field($property_data, 'PropertyId');
            if ($property_id) {
                $api_property_ids[] = $property_id;
            }
        }
        
        // Get all WordPress Barefoot properties
        $wp_properties = get_posts(array(
            'post_type' => 'barefoot_property',
            'posts_per_page' => -1,
            'post_status' => 'any',
            'meta_query' => array(
                array(
                    'key' => '_barefoot_property_id',
                    'compare' => 'EXISTS'
                )
            )
        ));
        
        $cleaned_count = 0;
        
        foreach ($wp_properties as $wp_property) {
            $barefoot_id = get_post_meta($wp_property->ID, '_barefoot_property_id', true);
            
            if (!in_array($barefoot_id, $api_property_ids)) {
                // Property no longer exists in Barefoot API
                wp_update_post(array(
                    'ID' => $wp_property->ID,
                    'post_status' => 'draft'
                ));
                $cleaned_count++;
            }
        }
        
        return array(
            'success' => true,
            'count' => $cleaned_count,
            'message' => "Moved {$cleaned_count} orphaned properties to draft status"
        );
    }
}

?>