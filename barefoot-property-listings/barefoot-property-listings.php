<?php
/**
 * Plugin Name: Barefoot Property Listings
 * Plugin URI: https://github.com/yourusername/barefoot-property-listings
 * Description: WordPress plugin that integrates with Barefoot Property Management System's SOAP API to synchronize and display vacation rental properties.
 * Version: 1.1.0
 * Author: Your Name
 * Author URI: https://yourwebsite.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: barefoot-properties
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('BAREFOOT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('BAREFOOT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('BAREFOOT_VERSION', '1.1.0');

// Barefoot API Configuration
define('BAREFOOT_API_ENDPOINT', 'https://portals.barefoot.com/BarefootWebService/BarefootService.asmx');
define('BAREFOOT_API_USERNAME', 'hfa20250814');
define('BAREFOOT_API_PASSWORD', '#20250825@xcfvgrt!54687');
define('BAREFOOT_API_ACCOUNT', 'v3chfa0604'); // Corrected: Account identifier, not version

/**
 * Main plugin class
 */
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
        
        // Register custom post types and taxonomies
        $this->register_post_types();
        $this->register_taxonomies();
    }
    
    private function includes() {
        require_once BAREFOOT_PLUGIN_DIR . 'includes/class-barefoot-api.php';
        require_once BAREFOOT_PLUGIN_DIR . 'includes/class-property-sync.php';
        require_once BAREFOOT_PLUGIN_DIR . 'includes/class-frontend-display.php';
        require_once BAREFOOT_PLUGIN_DIR . 'admin/class-admin-page.php';
        require_once BAREFOOT_PLUGIN_DIR . 'admin/ajax-handlers.php';
    }
    
    private function init_hooks() {
        // Admin hooks
        if (is_admin()) {
            new Barefoot_Admin_Page();
        }
        
        // Frontend hooks
        new Barefoot_Frontend_Display();
        
        // AJAX handlers
        add_action('wp_ajax_barefoot_sync_properties', 'barefoot_ajax_sync_properties');
        add_action('wp_ajax_barefoot_test_connection', 'barefoot_ajax_test_connection');
        
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // Template hooks
        add_filter('single_template', array($this, 'single_property_template'));
        add_filter('archive_template', array($this, 'archive_property_template'));
    }
    
    public function register_post_types() {
        $labels = array(
            'name' => __('Properties', 'barefoot-properties'),
            'singular_name' => __('Property', 'barefoot-properties'),
            'menu_name' => __('Barefoot Properties', 'barefoot-properties'),
            'add_new' => __('Add New', 'barefoot-properties'),
            'add_new_item' => __('Add New Property', 'barefoot-properties'),
            'edit_item' => __('Edit Property', 'barefoot-properties'),
            'new_item' => __('New Property', 'barefoot-properties'),
            'view_item' => __('View Property', 'barefoot-properties'),
            'search_items' => __('Search Properties', 'barefoot-properties'),
            'not_found' => __('No properties found', 'barefoot-properties'),
            'not_found_in_trash' => __('No properties found in trash', 'barefoot-properties'),
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
            'has_archive' => 'properties',
            'hierarchical' => false,
            'menu_position' => null,
            'menu_icon' => 'dashicons-building',
            'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'),
            'show_in_rest' => true,
        );
        
        register_post_type('barefoot_property', $args);
    }
    
    public function register_taxonomies() {
        // Property Type taxonomy
        $labels = array(
            'name' => __('Property Types', 'barefoot-properties'),
            'singular_name' => __('Property Type', 'barefoot-properties'),
            'search_items' => __('Search Property Types', 'barefoot-properties'),
            'all_items' => __('All Property Types', 'barefoot-properties'),
            'edit_item' => __('Edit Property Type', 'barefoot-properties'),
            'update_item' => __('Update Property Type', 'barefoot-properties'),
            'add_new_item' => __('Add New Property Type', 'barefoot-properties'),
            'new_item_name' => __('New Property Type Name', 'barefoot-properties'),
            'menu_name' => __('Property Types', 'barefoot-properties'),
        );
        
        register_taxonomy('property_type', 'barefoot_property', array(
            'hierarchical' => true,
            'labels' => $labels,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'property-type'),
            'show_in_rest' => true,
        ));
        
        // Location taxonomy
        $labels = array(
            'name' => __('Locations', 'barefoot-properties'),
            'singular_name' => __('Location', 'barefoot-properties'),
            'search_items' => __('Search Locations', 'barefoot-properties'),
            'all_items' => __('All Locations', 'barefoot-properties'),
            'edit_item' => __('Edit Location', 'barefoot-properties'),
            'update_item' => __('Update Location', 'barefoot-properties'),
            'add_new_item' => __('Add New Location', 'barefoot-properties'),
            'new_item_name' => __('New Location Name', 'barefoot-properties'),
            'menu_name' => __('Locations', 'barefoot-properties'),
        );
        
        register_taxonomy('location', 'barefoot_property', array(
            'hierarchical' => true,
            'labels' => $labels,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'location'),
            'show_in_rest' => true,
        ));
        
        // Amenity taxonomy
        $labels = array(
            'name' => __('Amenities', 'barefoot-properties'),
            'singular_name' => __('Amenity', 'barefoot-properties'),
            'search_items' => __('Search Amenities', 'barefoot-properties'),
            'all_items' => __('All Amenities', 'barefoot-properties'),
            'edit_item' => __('Edit Amenity', 'barefoot-properties'),
            'update_item' => __('Update Amenity', 'barefoot-properties'),
            'add_new_item' => __('Add New Amenity', 'barefoot-properties'),
            'new_item_name' => __('New Amenity Name', 'barefoot-properties'),
            'menu_name' => __('Amenities', 'barefoot-properties'),
        );
        
        register_taxonomy('amenity', 'barefoot_property', array(
            'hierarchical' => false,
            'labels' => $labels,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'amenity'),
            'show_in_rest' => true,
        ));
    }
    
    public function enqueue_frontend_assets() {
        if (is_post_type_archive('barefoot_property') || is_singular('barefoot_property') || is_tax(array('property_type', 'location', 'amenity'))) {
            wp_enqueue_style('barefoot-frontend', BAREFOOT_PLUGIN_URL . 'assets/css/frontend.css', array(), BAREFOOT_VERSION);
            wp_enqueue_script('barefoot-frontend', BAREFOOT_PLUGIN_URL . 'assets/js/frontend.js', array('jquery'), BAREFOOT_VERSION, true);
            
            wp_localize_script('barefoot-frontend', 'barefoot_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('barefoot_nonce'),
            ));
        }
    }
    
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'barefoot') !== false) {
            wp_enqueue_style('barefoot-admin', BAREFOOT_PLUGIN_URL . 'assets/css/admin.css', array(), BAREFOOT_VERSION);
            wp_enqueue_script('barefoot-admin', BAREFOOT_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), BAREFOOT_VERSION, true);
            
            wp_localize_script('barefoot-admin', 'barefoot_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('barefoot_nonce'),
            ));
        }
    }
    
    public function single_property_template($single_template) {
        global $post;
        
        if ($post->post_type === 'barefoot_property') {
            $template = BAREFOOT_PLUGIN_DIR . 'templates/single-property.php';
            if (file_exists($template)) {
                return $template;
            }
        }
        
        return $single_template;
    }
    
    public function archive_property_template($archive_template) {
        if (is_post_type_archive('barefoot_property') || is_tax(array('property_type', 'location', 'amenity'))) {
            $template = BAREFOOT_PLUGIN_DIR . 'templates/archive-property.php';
            if (file_exists($template)) {
                return $template;
            }
        }
        
        return $archive_template;
    }
    
    public function activate() {
        // Check PHP version
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die(__('This plugin requires PHP 7.4 or higher.', 'barefoot-properties'));
        }
        
        // Check SOAP extension
        if (!extension_loaded('soap')) {
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die(__('This plugin requires the PHP SOAP extension.', 'barefoot-properties'));
        }
        
        // Register post types and taxonomies
        $this->register_post_types();
        $this->register_taxonomies();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Create default options
        add_option('barefoot_sync_frequency', 'daily');
        add_option('barefoot_debug_mode', 0);
    }
    
    public function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }
}

// Initialize the plugin
new BarefootPropertyListings();