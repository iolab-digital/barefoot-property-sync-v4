<?php
/**
 * Plugin Name: Barefoot Property Listings
 * Plugin URI: https://barefoot.com
 * Description: WordPress plugin that integrates with Barefoot Property Management System's SOAP API to synchronize and display vacation rental properties.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: barefoot-properties
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('BAREFOOT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('BAREFOOT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('BAREFOOT_VERSION', '1.0.0');

// Barefoot API credentials
define('BAREFOOT_API_ENDPOINT', 'https://portals.barefoot.com/BarefootWebService/BarefootService.asmx');
define('BAREFOOT_API_USERNAME', 'hfa20250814');
define('BAREFOOT_API_PASSWORD', '#20250825@xcfvgrt!54687');
define('BAREFOOT_API_VERSION', 'v3chfa0604');

class BarefootPropertyListings {
    
    public function __construct() {
        // Check if we're in WordPress environment
        if (!function_exists('add_action')) {
            return;
        }
        
        add_action('init', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    public function init() {
        // Load plugin textdomain
        load_plugin_textdomain('barefoot-properties', false, dirname(plugin_basename(__FILE__)) . '/languages/');
        
        // Initialize components
        $this->includes();
        $this->init_hooks();
        
        // Register custom post types
        $this->register_post_types();
        $this->register_taxonomies();
        
        // Admin functionality
        if (is_admin()) {
            $this->init_admin();
        }
    }
    
    private function includes() {
        // Check if SOAP extension is available
        if (!extension_loaded('soap')) {
            add_action('admin_notices', array($this, 'soap_extension_notice'));
            return;
        }
        
        require_once BAREFOOT_PLUGIN_DIR . 'includes/class-barefoot-api.php';
        require_once BAREFOOT_PLUGIN_DIR . 'includes/class-property-sync.php';
        require_once BAREFOOT_PLUGIN_DIR . 'includes/class-frontend-display.php';
        
        if (is_admin()) {
            require_once BAREFOOT_PLUGIN_DIR . 'admin/class-admin-page.php';
            require_once BAREFOOT_PLUGIN_DIR . 'admin/ajax-handlers.php';
        }
    }
    
    private function init_hooks() {
        // Frontend styles and scripts
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        
        // Admin styles and scripts
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // AJAX hooks
        add_action('wp_ajax_sync_properties', 'barefoot_handle_property_sync');
        add_action('wp_ajax_test_connection', 'barefoot_test_api_connection');
        
        // Custom template loading
        add_filter('template_include', array($this, 'load_property_templates'));
        
        // Shortcodes
        add_shortcode('barefoot_properties', array($this, 'properties_shortcode'));
        add_shortcode('barefoot_search', array($this, 'search_shortcode'));
    }
    
    public function register_post_types() {
        // Register Property Custom Post Type
        $labels = array(
            'name' => __('Properties', 'barefoot-properties'),
            'singular_name' => __('Property', 'barefoot-properties'),
            'menu_name' => __('Properties', 'barefoot-properties'),
            'add_new' => __('Add New', 'barefoot-properties'),
            'add_new_item' => __('Add New Property', 'barefoot-properties'),
            'edit_item' => __('Edit Property', 'barefoot-properties'),
            'new_item' => __('New Property', 'barefoot-properties'),
            'view_item' => __('View Property', 'barefoot-properties'),
            'search_items' => __('Search Properties', 'barefoot-properties'),
            'not_found' => __('No properties found', 'barefoot-properties'),
            'not_found_in_trash' => __('No properties found in trash', 'barefoot-properties')
        );
        
        $args = array(
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'property'),
            'capability_type' => 'post',
            'has_archive' => true,
            'hierarchical' => false,
            'menu_position' => 5,
            'menu_icon' => 'dashicons-admin-home',
            'supports' => array('title', 'editor', 'excerpt', 'thumbnail', 'custom-fields'),
            'show_in_rest' => true
        );
        
        register_post_type('barefoot_property', $args);
    }
    
    public function register_taxonomies() {
        // Property Types
        register_taxonomy('property_type', 'barefoot_property', array(
            'labels' => array(
                'name' => __('Property Types', 'barefoot-properties'),
                'singular_name' => __('Property Type', 'barefoot-properties')
            ),
            'public' => true,
            'hierarchical' => true,
            'show_ui' => true,
            'show_in_rest' => true,
            'rewrite' => array('slug' => 'property-type')
        ));
        
        // Amenities
        register_taxonomy('amenity', 'barefoot_property', array(
            'labels' => array(
                'name' => __('Amenities', 'barefoot-properties'),
                'singular_name' => __('Amenity', 'barefoot-properties')
            ),
            'public' => true,
            'hierarchical' => false,
            'show_ui' => true,
            'show_in_rest' => true,
            'rewrite' => array('slug' => 'amenity')
        ));
        
        // Locations
        register_taxonomy('location', 'barefoot_property', array(
            'labels' => array(
                'name' => __('Locations', 'barefoot-properties'),
                'singular_name' => __('Location', 'barefoot-properties')
            ),
            'public' => true,
            'hierarchical' => true,
            'show_ui' => true,
            'show_in_rest' => true,
            'rewrite' => array('slug' => 'location')
        ));
    }
    
    private function init_admin() {
        new Barefoot_Admin_Page();
    }
    
    public function enqueue_frontend_assets() {
        // Inline CSS to avoid file loading issues
        $css = "
        <style>
        .barefoot-properties { margin: 20px 0; }
        .property-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
        .property-card { border: 1px solid #ddd; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .property-card img { width: 100%; height: 200px; object-fit: cover; }
        .property-card-content { padding: 15px; }
        .property-title { font-size: 1.2em; font-weight: bold; margin-bottom: 10px; }
        .property-price { color: #2c5aa0; font-weight: bold; font-size: 1.1em; }
        .property-details { margin-top: 10px; font-size: 0.9em; color: #666; }
        .barefoot-search { background: #f5f5f5; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .barefoot-search input, .barefoot-search select { margin: 5px; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        .barefoot-search button { background: #2c5aa0; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
        .barefoot-search button:hover { background: #1e3d6f; }
        </style>
        ";
        echo $css;
        
        // Inline JavaScript
        $js = "
        <script>
        jQuery(document).ready(function($) {
            // Property search functionality
            $('.barefoot-search-form').on('submit', function(e) {
                e.preventDefault();
                var formData = $(this).serialize();
                
                $.ajax({
                    url: '" . admin_url('admin-ajax.php') . "',
                    type: 'POST',
                    data: formData + '&action=search_properties',
                    success: function(response) {
                        $('.property-results').html(response);
                    }
                });
            });
        });
        </script>
        ";
        echo $js;
    }
    
    public function enqueue_admin_assets() {
        // Admin inline CSS
        $css = "
        <style>
        .barefoot-admin { max-width: 1200px; }
        .barefoot-sync-panel { background: white; padding: 20px; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,0.04); }
        .sync-button { background: #0073aa; color: white; padding: 10px 20px; border: none; border-radius: 3px; cursor: pointer; font-size: 14px; }
        .sync-button:hover { background: #005a87; }
        .sync-progress { display: none; margin-top: 15px; }
        .progress-bar { width: 100%; height: 20px; background: #f0f0f1; border-radius: 10px; overflow: hidden; }
        .progress-fill { height: 100%; background: #00a32a; width: 0%; transition: width 0.3s ease; }
        .sync-log { background: #f6f7f7; border: 1px solid #c3c4c7; padding: 15px; margin-top: 15px; max-height: 300px; overflow-y: auto; font-family: monospace; }
        .connection-status { padding: 10px; border-radius: 4px; margin-bottom: 20px; }
        .status-success { background: #d1e7dd; border: 1px solid #badbcc; color: #0f5132; }
        .status-error { background: #f8d7da; border: 1px solid #f5c2c7; color: #842029; }
        </style>
        ";
        echo $css;
        
        // Admin inline JavaScript
        $js = "
        <script>
        jQuery(document).ready(function($) {
            // Test API connection
            $('#test-connection').on('click', function() {
                var button = $(this);
                button.prop('disabled', true).text('Testing...');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: { action: 'test_connection' },
                    success: function(response) {
                        if (response.success) {
                            $('.connection-status').removeClass('status-error').addClass('status-success')
                                .text('✓ Connection successful! ' + response.data.message);
                        } else {
                            $('.connection-status').removeClass('status-success').addClass('status-error')
                                .text('✗ Connection failed: ' + response.data.message);
                        }
                    },
                    error: function() {
                        $('.connection-status').removeClass('status-success').addClass('status-error')
                            .text('✗ Connection test failed');
                    },
                    complete: function() {
                        button.prop('disabled', false).text('Test Connection');
                    }
                });
            });
            
            // Sync properties
            $('#sync-properties').on('click', function() {
                var button = $(this);
                var progressContainer = $('.sync-progress');
                var progressBar = $('.progress-fill');
                var logContainer = $('.sync-log');
                
                button.prop('disabled', true).text('Syncing...');
                progressContainer.show();
                progressBar.css('width', '0%');
                logContainer.empty();
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: { action: 'sync_properties' },
                    success: function(response) {
                        if (response.success) {
                            progressBar.css('width', '100%');
                            logContainer.append('<div>✓ Sync completed successfully!</div>');
                            logContainer.append('<div>Properties synced: ' + response.data.count + '</div>');
                        } else {
                            logContainer.append('<div style=\"color: red;\">✗ Sync failed: ' + response.data.message + '</div>');
                        }
                    },
                    error: function() {
                        logContainer.append('<div style=\"color: red;\">✗ Sync request failed</div>');
                    },
                    complete: function() {
                        button.prop('disabled', false).text('Sync Properties');
                    }
                });
            });
        });
        </script>
        ";
        echo $js;
    }
    
    public function load_property_templates($template) {
        global $post;
        
        if ($post && $post->post_type == 'barefoot_property') {
            if (is_single()) {
                $plugin_template = BAREFOOT_PLUGIN_DIR . 'templates/single-property.php';
                if (file_exists($plugin_template)) {
                    return $plugin_template;
                }
            } elseif (is_archive()) {
                $plugin_template = BAREFOOT_PLUGIN_DIR . 'templates/archive-property.php';
                if (file_exists($plugin_template)) {
                    return $plugin_template;
                }
            }
        }
        
        return $template;
    }
    
    public function properties_shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit' => 12,
            'type' => '',
            'location' => '',
            'show_search' => 'yes'
        ), $atts);
        
        ob_start();
        
        echo '<div class="barefoot-properties">';
        
        if ($atts['show_search'] === 'yes') {
            echo $this->search_shortcode();
        }
        
        $query_args = array(
            'post_type' => 'barefoot_property',
            'posts_per_page' => intval($atts['limit']),
            'post_status' => 'publish'
        );
        
        if (!empty($atts['type'])) {
            $query_args['tax_query'][] = array(
                'taxonomy' => 'property_type',
                'field' => 'slug',
                'terms' => $atts['type']
            );
        }
        
        if (!empty($atts['location'])) {
            $query_args['tax_query'][] = array(
                'taxonomy' => 'location',
                'field' => 'slug',
                'terms' => $atts['location']
            );
        }
        
        $properties = new WP_Query($query_args);
        
        if ($properties->have_posts()) {
            echo '<div class="property-results"><div class="property-grid">';
            
            while ($properties->have_posts()) {
                $properties->the_post();
                $this->render_property_card();
            }
            
            echo '</div></div>';
            wp_reset_postdata();
        } else {
            echo '<p>No properties found.</p>';
        }
        
        echo '</div>';
        
        return ob_get_clean();
    }
    
    public function search_shortcode($atts = array()) {
        ob_start();
        
        echo '<div class="barefoot-search">';
        echo '<form class="barefoot-search-form" method="get">';
        echo '<input type="text" name="property_search" placeholder="Search properties..." value="' . esc_attr(get_query_var('property_search')) . '">';
        
        // Property type dropdown
        $property_types = get_terms(array(
            'taxonomy' => 'property_type',
            'hide_empty' => false
        ));
        
        if (!empty($property_types)) {
            echo '<select name="property_type">';
            echo '<option value="">All Types</option>';
            foreach ($property_types as $type) {
                $selected = (get_query_var('property_type') === $type->slug) ? 'selected' : '';
                echo '<option value="' . esc_attr($type->slug) . '" ' . $selected . '>' . esc_html($type->name) . '</option>';
            }
            echo '</select>';
        }
        
        // Location dropdown
        $locations = get_terms(array(
            'taxonomy' => 'location',
            'hide_empty' => false
        ));
        
        if (!empty($locations)) {
            echo '<select name="location">';
            echo '<option value="">All Locations</option>';
            foreach ($locations as $location) {
                $selected = (get_query_var('location') === $location->slug) ? 'selected' : '';
                echo '<option value="' . esc_attr($location->slug) . '" ' . $selected . '>' . esc_html($location->name) . '</option>';
            }
            echo '</select>';
        }
        
        echo '<button type="submit">Search Properties</button>';
        echo '</form>';
        echo '</div>';
        
        return ob_get_clean();
    }
    
    private function render_property_card() {
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
    
    public function activate() {
        // Flush rewrite rules
        $this->register_post_types();
        $this->register_taxonomies();
        flush_rewrite_rules();
        
        // Create default terms
        $this->create_default_terms();
    }
    
    public function deactivate() {
        flush_rewrite_rules();
    }
    
    private function create_default_terms() {
        // Create default property types
        $property_types = array('House', 'Condo', 'Villa', 'Cottage', 'Townhouse');
        foreach ($property_types as $type) {
            if (!term_exists($type, 'property_type')) {
                wp_insert_term($type, 'property_type');
            }
        }
        
        // Create default amenities
        $amenities = array('Pool', 'Hot Tub', 'WiFi', 'Kitchen', 'Parking', 'Pet Friendly', 'Beach Access');
        foreach ($amenities as $amenity) {
            if (!term_exists($amenity, 'amenity')) {
                wp_insert_term($amenity, 'amenity');
            }
        }
    }
    
    /**
     * Admin notice for missing SOAP extension
     */
    public function soap_extension_notice() {
        echo '<div class="notice notice-error"><p>';
        echo '<strong>Barefoot Property Listings:</strong> PHP SOAP extension is required but not installed. ';
        echo 'Please contact your hosting provider to enable the SOAP extension.';
        echo '</p></div>';
    }
}

// Initialize the plugin
new BarefootPropertyListings();

?>