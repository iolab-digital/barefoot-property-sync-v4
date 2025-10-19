<?php
/**
 * Property Synchronization Class
 * Handles syncing properties from Barefoot API to WordPress
 * 
 * @package BarefootPropertyListings
 * @since 1.1.0
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
            
            // Handle case where API responds successfully but has no properties
            if (empty($properties)) {
                $message_details = isset($api_response['message']) ? $api_response['message'] : '';
                $method_used = isset($api_response['method_used']) ? $api_response['method_used'] : 'Unknown';
                
                error_log('Barefoot Property Sync: API responded successfully but returned no properties');
                error_log('Method used: ' . $method_used);
                error_log('Details: ' . $message_details);
                
                $result['success'] = true;
                $result['count'] = 0;
                $result['message'] = 'API connection successful but no properties found. This may indicate: 1) No properties are configured in your Barefoot account, 2) Additional API permissions may be needed, or 3) The account may need additional setup.';
                
                if (!empty($message_details)) {
                    $result['message'] .= ' Details: ' . $message_details;
                }
                
                return $result;
            }
            
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
            // Debug: Log the property data structure (first property only to avoid log spam)
            static $logged_once = false;
            if (!$logged_once) {
                error_log('Barefoot Sync: Sample property data structure: ' . print_r($property_data, true));
                $logged_once = true;
            }
            
            // Extract property information - API returns lowercase field names
            $property_id = $this->get_property_field($property_data, 'PropertyID');
            $property_name = $this->get_property_field($property_data, 'name');  // lowercase from XML
            $property_description = $this->get_property_field($property_data, 'description');  // lowercase from XML
            
            // PropertyID might be lowercase in some responses
            if (empty($property_id)) {
                $property_id = $this->get_property_field($property_data, 'propertyid') ?: 
                              $this->get_property_field($property_data, 'addressid');
            }
            
            if (empty($property_id)) {
                return array(
                    'success' => false,
                    'message' => 'Missing PropertyID field'
                );
            }
            
            if (empty($property_name)) {
                return array(
                    'success' => false,
                    'message' => "Property ID {$property_id}: Missing name field"
                );
            }
            
            // Check if property already exists
            $existing_post = $this->find_existing_property($property_id);
            
            // Prepare post data
            $post_data = array(
                'post_type' => 'barefoot_property',
                'post_title' => sanitize_text_field($property_name),
                'post_content' => wp_kses_post($property_description ?: 'Property information will be updated from Barefoot API.'),
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
            // Core property information (lowercase field names from XML)
            '_barefoot_property_id' => $this->get_property_field($property_data, 'PropertyID'),
            '_barefoot_address_id' => $this->get_property_field($property_data, 'addressid'),
            '_barefoot_keyboard_id' => $this->get_property_field($property_data, 'keyboardid'),
            
            // Property specifications
            '_barefoot_occupancy' => $this->get_property_field($property_data, 'occupancy'),
            '_barefoot_status' => $this->get_property_field($property_data, 'status'),
            '_barefoot_deadline' => $this->get_property_field($property_data, 'deadline'),
            
            // Address information  
            '_barefoot_prop_address' => $this->get_property_field($property_data, 'propAddress'),
            '_barefoot_prop_address_new' => $this->get_property_field($property_data, 'propAddressNew'),
            '_barefoot_street' => $this->get_property_field($property_data, 'street'),
            '_barefoot_street2' => $this->get_property_field($property_data, 'street2'),
            '_barefoot_city' => $this->get_property_field($property_data, 'city'),
            '_barefoot_state' => $this->get_property_field($property_data, 'state'),
            '_barefoot_zip' => $this->get_property_field($property_data, 'zip'),
            '_barefoot_country' => $this->get_property_field($property_data, 'country'),
            
            // Location coordinates
            '_barefoot_latitude' => $this->get_property_field($property_data, 'Latitude'),
            '_barefoot_longitude' => $this->get_property_field($property_data, 'Longitude'),
            
            // Pricing information
            '_barefoot_min_price' => $this->get_property_field($property_data, 'minprice'),
            '_barefoot_max_price' => $this->get_property_field($property_data, 'maxprice'),
            
            // Extended description and media
            '_barefoot_ext_description' => $this->get_property_field($property_data, 'extdescription'),
            '_barefoot_internet_description' => $this->get_property_field($property_data, 'InternetDescription'),
            '_barefoot_video_link' => $this->get_property_field($property_data, 'VideoLink'),
            '_barefoot_image_path' => $this->get_property_field($property_data, 'imagepath'),
            '_barefoot_property_title' => $this->get_property_field($property_data, 'PropertyTitle'),
            '_barefoot_sleeps_beds' => $this->get_property_field($property_data, 'SleepsBeds'),
            '_barefoot_number_floors' => $this->get_property_field($property_data, 'NumberFloors'),
            '_barefoot_unit_type' => $this->get_property_field($property_data, 'UnitType'),
            '_barefoot_property_type' => $this->get_property_field($property_data, 'PropertyType'),
            
            // Agent information
            '_barefoot_agent1' => $this->get_property_field($property_data, 'agent1'),
            '_barefoot_agent2' => $this->get_property_field($property_data, 'agent2'),
            '_barefoot_agent3' => $this->get_property_field($property_data, 'agent3'),
            '_barefoot_agent_name' => $this->get_property_field($property_data, 'a246'),  // a246 field contains agent name
            
            // Registration
            '_barefoot_registnumber' => $this->get_property_field($property_data, 'Registnumber'),
            '_barefoot_regist_expire_date' => $this->get_property_field($property_data, 'Registexpirdate'),
            
            // Sync tracking
            '_barefoot_last_sync' => current_time('mysql'),
            '_barefoot_sync_version' => BAREFOOT_VERSION
        );
        
        // Add all custom "a" fields (a7, a28, a53, etc.) - there are 104 total fields
        if (is_array($property_data)) {
            foreach ($property_data as $key => $value) {
                if (preg_match('/^a\d+$/', $key) && !empty($value)) {
                    $meta['_barefoot_' . $key] = $value;
                }
            }
        }
        
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
        $property_type = $this->get_property_field($property_data, 'PropertyType') ?: 
                        $this->get_property_field($property_data, 'UnitType');
        if ($property_type) {
            wp_set_object_terms($post_id, sanitize_text_field($property_type), 'property_type');
        }
        
        // Location (use city, state, or region)
        $location_parts = array();
        $city = $this->get_property_field($property_data, 'City');
        $state = $this->get_property_field($property_data, 'State');
        $region = $this->get_property_field($property_data, 'Region');
        
        if ($city) $location_parts[] = $city;
        if ($state) $location_parts[] = $state;
        if (empty($location_parts) && $region) $location_parts[] = $region;
        
        if (!empty($location_parts)) {
            wp_set_object_terms($post_id, sanitize_text_field(implode(', ', $location_parts)), 'location');
        }
        
        // Amenities based on property features
        $amenities = array();
        
        if ($this->get_property_field($property_data, 'Pool') || 
            stripos($this->get_property_field($property_data, 'PropertyAmenities') ?: '', 'pool') !== false) {
            $amenities[] = 'Pool';
        }
        
        if ($this->get_property_field($property_data, 'HotTub') || 
            stripos($this->get_property_field($property_data, 'PropertyAmenities') ?: '', 'hot tub') !== false) {
            $amenities[] = 'Hot Tub';
        }
        
        if ($this->get_property_field($property_data, 'Internet') || 
            stripos($this->get_property_field($property_data, 'PropertyAmenities') ?: '', 'wifi') !== false ||
            stripos($this->get_property_field($property_data, 'PropertyAmenities') ?: '', 'internet') !== false) {
            $amenities[] = 'WiFi';
        }
        
        if ($this->get_property_field($property_data, 'Kitchen') || 
            stripos($this->get_property_field($property_data, 'PropertyAmenities') ?: '', 'kitchen') !== false) {
            $amenities[] = 'Kitchen';
        }
        
        if ($this->get_property_field($property_data, 'Parking') || 
            stripos($this->get_property_field($property_data, 'PropertyAmenities') ?: '', 'parking') !== false) {
            $amenities[] = 'Parking';
        }
        
        if ($this->get_property_field($property_data, 'PetsAllowed') || 
            stripos($this->get_property_field($property_data, 'PropertyAmenities') ?: '', 'pet') !== false) {
            $amenities[] = 'Pet Friendly';
        }
        
        // Add amenities based on occupancy
        $occupancy = intval($this->get_property_field($property_data, 'Occupancy'));
        if ($occupancy >= 8) {
            $amenities[] = 'Large Group';
        } elseif ($occupancy >= 4) {
            $amenities[] = 'Family Friendly';
        } elseif ($occupancy <= 2) {
            $amenities[] = 'Romantic Getaway';
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
            
            if (!$images_response['success'] || empty($images_response['images'])) {
                error_log("Barefoot Sync: No images found for property {$property_id}");
                return;
            }
            
            $images = $images_response['images'];
            $featured_set = false;
            $images_synced = 0;
            
            error_log("Barefoot Sync: Starting image sync for property {$property_id}, found " . count($images) . " images");
            
            foreach ($images as $image_data) {
                // Get image URL directly from array (returned by parse_property_images_xml)
                $image_url = isset($image_data['image_url']) ? $image_data['image_url'] : '';
                $image_caption = isset($image_data['description']) ? $image_data['description'] : 'Property Image';
                
                if (empty($image_url)) {
                    error_log("Barefoot Sync: Skipping image with empty URL. Image data: " . print_r($image_data, true));
                    continue;
                }
                
                error_log("Barefoot Sync: Downloading image from: {$image_url}");
                
                // Download and attach image
                $attachment_id = $this->download_and_attach_image($image_url, $post_id, $image_caption);
                
                if ($attachment_id) {
                    $images_synced++;
                    
                    // Set first image as featured image
                    if (!$featured_set) {
                        set_post_thumbnail($post_id, $attachment_id);
                        $featured_set = true;
                        error_log("Barefoot Sync: Set image {$attachment_id} as featured image for property {$property_id}");
                    }
                }
            }
            
            error_log("Barefoot Sync: Successfully synced {$images_synced} images for property {$property_id}");
            
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
                'timeout' => 30,
                'user-agent' => 'WordPress Barefoot Plugin/' . BAREFOOT_VERSION
            ));
            
            if (is_wp_error($response)) {
                error_log('Image download error: ' . $response->get_error_message());
                return false;
            }
            
            $image_data = wp_remote_retrieve_body($response);
            if (empty($image_data)) {
                return false;
            }
            
            // Get filename from URL
            $filename = basename(parse_url($image_url, PHP_URL_PATH));
            if (empty($filename) || strpos($filename, '.') === false) {
                $filename = 'barefoot-property-' . $post_id . '-' . time() . '.jpg';
            }
            
            // Upload image
            $upload = wp_upload_bits($filename, null, $image_data);
            
            if ($upload['error']) {
                error_log('Image upload error: ' . $upload['error']);
                return false;
            }
            
            // Create attachment
            $attachment_data = array(
                'post_mime_type' => wp_check_filetype($filename)['type'],
                'post_title' => sanitize_file_name(pathinfo($filename, PATHINFO_FILENAME)),
                'post_content' => '',
                'post_excerpt' => $caption ? sanitize_text_field($caption) : '',
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
     * Get field value from property data (handles different object structures and new GetProperty method)
     */
    private function get_property_field($property_data, $field_name) {
        if (is_array($property_data)) {
            return isset($property_data[$field_name]) ? $property_data[$field_name] : null;
        } elseif (is_object($property_data)) {
            // Check direct property first
            if (isset($property_data->$field_name)) {
                return $property_data->$field_name;
            }
            
            // Check alternative field names based on GetProperty method structure
            $alternative_fields = array(
                'PropertyID' => array('PropertyID', 'PropertyId', 'ID', 'id', 'PropertyNumber'),
                'Name' => array('Name', 'PropertyName', 'PropertyTitle', 'Title'),
                'Description' => array('Description', 'PropertyDescription', 'Desc', 'LongDescription'),
                'City' => array('City', 'PropertyCity', 'CityName'),
                'State' => array('State', 'PropertyState', 'StateCode'),
                'Zip' => array('Zip', 'ZipCode', 'PostalCode'),
                'Occupancy' => array('Occupancy', 'MaxOccupancy', 'Sleeps', 'GuestCapacity'),
                'Bedrooms' => array('Bedrooms', 'BedroomCount', 'Beds', 'NumBedrooms'),
                'Bathrooms' => array('Bathrooms', 'BathroomCount', 'Baths', 'NumBathrooms'),
                'Minprice' => array('Minprice', 'MinPrice', 'MinimumRate', 'LowRate'),
                'Maxprice' => array('Maxprice', 'MaxPrice', 'MaximumRate', 'HighRate')
            );
            
            if (isset($alternative_fields[$field_name])) {
                foreach ($alternative_fields[$field_name] as $alt_field) {
                    if (isset($property_data->$alt_field)) {
                        return $property_data->$alt_field;
                    }
                }
            }
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
            $property_id = $this->get_property_field($property_data, 'PropertyID');
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