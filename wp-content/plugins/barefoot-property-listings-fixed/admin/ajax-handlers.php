<?php
/**
 * AJAX Handlers for Barefoot Property Management
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Test API connection
 */
function barefoot_test_api_connection() {
    // Check user permissions
    if (!current_user_can('manage_options')) {
        wp_die(__('Insufficient permissions', 'barefoot-properties'));
    }
    
    // Verify nonce (would add in production)
    // check_ajax_referer('barefoot_nonce', 'nonce');
    
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
 * Handle property synchronization
 */
function barefoot_handle_property_sync() {
    // Check user permissions
    if (!current_user_can('manage_options')) {
        wp_die(__('Insufficient permissions', 'barefoot-properties'));
    }
    
    // Verify nonce (would add in production)
    // check_ajax_referer('barefoot_nonce', 'nonce');
    
    try {
        // Increase time limit for large syncs
        set_time_limit(300); // 5 minutes
        
        $sync = new Barefoot_Property_Sync();
        $result = $sync->sync_all_properties();
        
        if ($result['success']) {
            // Update last sync time
            update_option('barefoot_last_sync', current_time('mysql'));
            
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
 * Handle property search (frontend)
 */
function barefoot_handle_property_search() {
    $search_term = sanitize_text_field($_POST['property_search'] ?? '');
    $property_type = sanitize_text_field($_POST['property_type'] ?? '');
    $location = sanitize_text_field($_POST['location'] ?? '');
    
    $query_args = array(
        'post_type' => 'barefoot_property',
        'posts_per_page' => 12,
        'post_status' => 'publish'
    );
    
    if (!empty($search_term)) {
        $query_args['s'] = $search_term;
    }
    
    if (!empty($property_type) || !empty($location)) {
        $query_args['tax_query'] = array('relation' => 'AND');
        
        if (!empty($property_type)) {
            $query_args['tax_query'][] = array(
                'taxonomy' => 'property_type',
                'field' => 'slug',
                'terms' => $property_type
            );
        }
        
        if (!empty($location)) {
            $query_args['tax_query'][] = array(
                'taxonomy' => 'location',
                'field' => 'slug',
                'terms' => $location
            );
        }
    }
    
    $properties = new WP_Query($query_args);
    
    ob_start();
    
    if ($properties->have_posts()) {
        echo '<div class="property-grid">';
        
        while ($properties->have_posts()) {
            $properties->the_post();
            barefoot_render_property_card();
        }
        
        echo '</div>';
        wp_reset_postdata();
    } else {
        echo '<p>' . __('No properties found matching your criteria.', 'barefoot-properties') . '</p>';
    }
    
    $output = ob_get_clean();
    
    echo $output;
    wp_die();
}

// Register AJAX handlers for logged-in users
add_action('wp_ajax_test_connection', 'barefoot_test_api_connection');
add_action('wp_ajax_sync_properties', 'barefoot_handle_property_sync');

// Register AJAX handlers for non-logged-in users (frontend search)
add_action('wp_ajax_nopriv_search_properties', 'barefoot_handle_property_search');
add_action('wp_ajax_search_properties', 'barefoot_handle_property_search');

/**
 * Helper function to render property card (used in shortcode and AJAX)
 */
function barefoot_render_property_card() {
    $property_id = get_the_ID();
    $price = get_post_meta($property_id, '_barefoot_price', true);
    $bedrooms = get_post_meta($property_id, '_barefoot_bedrooms', true);
    $bathrooms = get_post_meta($property_id, '_barefoot_bathrooms', true);
    $max_guests = get_post_meta($property_id, '_barefoot_max_guests', true);
    
    echo '<div class="property-card">';
    
    if (has_post_thumbnail()) {
        echo '<a href="' . get_permalink() . '">';
        the_post_thumbnail('medium');
        echo '</a>';
    }
    
    echo '<div class="property-card-content">';
    echo '<h3 class="property-title"><a href="' . get_permalink() . '">' . get_the_title() . '</a></h3>';
    
    if ($price) {
        echo '<div class="property-price">From $' . number_format($price) . '/night</div>';
    }
    
    echo '<div class="property-details">';
    if ($bedrooms) echo '<span>🛏️ ' . $bedrooms . ' bed</span> ';
    if ($bathrooms) echo '<span>🛁 ' . $bathrooms . ' bath</span> ';
    if ($max_guests) echo '<span>👥 Up to ' . $max_guests . ' guests</span>';
    echo '</div>';
    
    echo '</div>';
    echo '</div>';
}

?>