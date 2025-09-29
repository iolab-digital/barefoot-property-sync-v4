<?php
/**
 * AJAX Handlers for Admin Functions
 * 
 * @package BarefootPropertyListings
 * @since 1.1.0
 */

/**
 * AJAX handler for syncing properties
 */
function barefoot_ajax_sync_properties() {
    // Verify nonce
    check_ajax_referer('barefoot_nonce', 'nonce');
    
    // Check user permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('Insufficient permissions', 'barefoot-properties')));
    }
    
    try {
        $sync = new Barefoot_Property_Sync();
        $result = $sync->sync_all_properties();
        
        // Log the sync result
        $sync_logs = get_option('barefoot_sync_history', array());
        $sync_logs[] = array(
            'date' => current_time('mysql'),
            'success' => $result['success'],
            'count' => $result['count'],
            'message' => $result['message'],
            'errors' => $result['errors'] ?? array()
        );
        
        // Keep only last 50 logs
        if (count($sync_logs) > 50) {
            $sync_logs = array_slice($sync_logs, -50);
        }
        
        update_option('barefoot_sync_history', $sync_logs);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
        
    } catch (Exception $e) {
        wp_send_json_error(array(
            'message' => 'Sync failed: ' . $e->getMessage()
        ));
    }
}

/**
 * AJAX handler for testing API connection
 */
function barefoot_ajax_test_connection() {
    // Verify nonce
    check_ajax_referer('barefoot_nonce', 'nonce');
    
    // Check user permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('Insufficient permissions', 'barefoot-properties')));
    }
    
    try {
        $api = new Barefoot_API();
        $result = $api->test_connection();
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
        
    } catch (Exception $e) {
        wp_send_json_error(array(
            'message' => 'Connection test failed: ' . $e->getMessage()
        ));
    }
}

/**
 * AJAX handler for testing property retrieval
 */
function barefoot_ajax_test_get_properties() {
    // Verify nonce
    check_ajax_referer('barefoot_nonce', 'nonce');
    
    // Check user permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('Insufficient permissions', 'barefoot-properties')));
    }
    
    try {
        $api = new Barefoot_API();
        $result = $api->get_all_properties();
        
        // Prepare response data
        $response_data = array(
            'success' => $result['success'],
            'count' => $result['count'] ?? 0,
            'method_used' => $result['method_used'] ?? 'Unknown',
            'message' => $result['message'] ?? '',
        );
        
        // Add sample property data if available
        if (!empty($result['data'])) {
            $sample_property = $result['data'][0];
            $response_data['sample_property'] = array(
                'PropertyID' => $sample_property->PropertyID ?? 'N/A',
                'Name' => $sample_property->Name ?? 'N/A',
                'City' => $sample_property->City ?? 'N/A',
                'State' => $sample_property->State ?? 'N/A',
                'Occupancy' => $sample_property->Occupancy ?? 'N/A'
            );
        }
        
        if ($result['success']) {
            wp_send_json_success($response_data);
        } else {
            wp_send_json_error($response_data);
        }
        
    } catch (Exception $e) {
        wp_send_json_error(array(
            'message' => 'Property retrieval test failed: ' . $e->getMessage()
        ));
    }
}

/**
 * AJAX handler for getting available API functions
 */
function barefoot_ajax_get_api_functions() {
    // Verify nonce
    check_ajax_referer('barefoot_nonce', 'nonce');
    
    // Check user permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('Insufficient permissions', 'barefoot-properties')));
    }
    
    try {
        $api = new Barefoot_API();
        $functions = $api->get_available_functions();
        
        // Filter to show only property-related functions
        $property_functions = array();
        foreach ($functions as $function) {
            if (stripos($function, 'property') !== false || stripos($function, 'Property') !== false) {
                $property_functions[] = $function;
            }
        }
        
        wp_send_json_success(array(
            'total_functions' => count($functions),
            'property_functions' => array_slice($property_functions, 0, 20), // Show first 20
            'property_functions_count' => count($property_functions)
        ));
        
    } catch (Exception $e) {
        wp_send_json_error(array(
            'message' => 'Failed to get API functions: ' . $e->getMessage()
        ));
    }
}

/**
 * AJAX handler for cleanup orphaned properties
 */
function barefoot_ajax_cleanup_orphaned() {
    // Verify nonce
    check_ajax_referer('barefoot_nonce', 'nonce');
    
    // Check user permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('Insufficient permissions', 'barefoot-properties')));
    }
    
    try {
        $sync = new Barefoot_Property_Sync();
        $result = $sync->cleanup_orphaned_properties();
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
        
    } catch (Exception $e) {
        wp_send_json_error(array(
            'message' => 'Cleanup failed: ' . $e->getMessage()
        ));
    }
}

/**
 * AJAX handler for property inquiry form
 */
function barefoot_ajax_property_inquiry() {
    // Verify nonce
    check_ajax_referer('barefoot_nonce', 'nonce');
    
    // Sanitize input data
    $property_id = sanitize_text_field($_POST['property_id'] ?? '');
    $name = sanitize_text_field($_POST['name'] ?? '');
    $email = sanitize_email($_POST['email'] ?? '');
    $phone = sanitize_text_field($_POST['phone'] ?? '');
    $message = sanitize_textarea_field($_POST['message'] ?? '');
    $check_in = sanitize_text_field($_POST['check_in'] ?? '');
    $check_out = sanitize_text_field($_POST['check_out'] ?? '');
    $guests = intval($_POST['guests'] ?? 1);
    
    // Validate required fields
    if (empty($name) || empty($email) || empty($message)) {
        wp_send_json_error(array(
            'message' => __('Please fill in all required fields.', 'barefoot-properties')
        ));
    }
    
    // Get property information
    $property_posts = get_posts(array(
        'post_type' => 'barefoot_property',
        'meta_key' => '_barefoot_property_id',
        'meta_value' => $property_id,
        'posts_per_page' => 1
    ));
    
    if (empty($property_posts)) {
        wp_send_json_error(array(
            'message' => __('Property not found.', 'barefoot-properties')
        ));
    }
    
    $property = $property_posts[0];
    
    // Prepare email content
    $to = get_option('barefoot_contact_email', get_option('admin_email'));
    $subject = sprintf(__('Property Inquiry: %s', 'barefoot-properties'), $property->post_title);
    
    $email_content = sprintf(
        __("New property inquiry received:\n\nProperty: %s (ID: %s)\nName: %s\nEmail: %s\nPhone: %s\nGuests: %d\nCheck-in: %s\nCheck-out: %s\n\nMessage:\n%s\n\n---\nSent via Barefoot Properties Plugin", 'barefoot-properties'),
        $property->post_title,
        $property_id,
        $name,
        $email,
        $phone,
        $guests,
        $check_in,
        $check_out,
        $message
    );
    
    // Send email
    $headers = array(
        'Reply-To: ' . $name . ' <' . $email . '>',
        'Content-Type: text/plain; charset=UTF-8'
    );
    
    $sent = wp_mail($to, $subject, $email_content, $headers);
    
    if ($sent) {
        // Log the inquiry (optional)
        $inquiries = get_option('barefoot_inquiries', array());
        $inquiries[] = array(
            'date' => current_time('mysql'),
            'property_id' => $property_id,
            'property_title' => $property->post_title,
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'guests' => $guests,
            'check_in' => $check_in,
            'check_out' => $check_out
        );
        
        // Keep only last 100 inquiries
        if (count($inquiries) > 100) {
            $inquiries = array_slice($inquiries, -100);
        }
        
        update_option('barefoot_inquiries', $inquiries);
        
        wp_send_json_success(array(
            'message' => __('Thank you for your inquiry! We will contact you soon.', 'barefoot-properties')
        ));
    } else {
        wp_send_json_error(array(
            'message' => __('Sorry, there was a problem sending your inquiry. Please try again.', 'barefoot-properties')
        ));
    }
}

// Register AJAX handlers for admin
add_action('wp_ajax_barefoot_sync_properties', 'barefoot_ajax_sync_properties');
add_action('wp_ajax_barefoot_test_connection', 'barefoot_ajax_test_connection');
add_action('wp_ajax_barefoot_test_get_properties', 'barefoot_ajax_test_get_properties');
add_action('wp_ajax_barefoot_get_api_functions', 'barefoot_ajax_get_api_functions');
add_action('wp_ajax_barefoot_cleanup_orphaned', 'barefoot_ajax_cleanup_orphaned');

// Register AJAX handlers for frontend
add_action('wp_ajax_barefoot_property_inquiry', 'barefoot_ajax_property_inquiry');
add_action('wp_ajax_nopriv_barefoot_property_inquiry', 'barefoot_ajax_property_inquiry');