<?php
/**
 * Frontend Display Class
 * Handles the display of properties on the frontend
 * 
 * @package BarefootPropertyListings
 * @since 1.1.0
 */

class Barefoot_Frontend_Display {
    
    public function __construct() {
        // Add shortcodes
        add_shortcode('barefoot_properties', array($this, 'properties_shortcode'));
        add_shortcode('barefoot_property_search', array($this, 'property_search_shortcode'));
        add_shortcode('barefoot_featured_properties', array($this, 'featured_properties_shortcode'));
        
        // Add filters for property display
        add_filter('the_content', array($this, 'add_property_details'));
        
        // AJAX handlers for frontend
        add_action('wp_ajax_barefoot_property_search', array($this, 'ajax_property_search'));
        add_action('wp_ajax_nopriv_barefoot_property_search', array($this, 'ajax_property_search'));
    }
    
    /**
     * Properties listing shortcode
     * Usage: [barefoot_properties limit="10" type="condo" location="miami"]
     */
    public function properties_shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit' => 10,
            'type' => '',
            'location' => '',
            'amenity' => '',
            'orderby' => 'date',
            'order' => 'DESC',
            'show_search' => 'yes',
            'show_filters' => 'yes'
        ), $atts);
        
        $args = array(
            'post_type' => 'barefoot_property',
            'posts_per_page' => intval($atts['limit']),
            'post_status' => 'publish',
            'orderby' => sanitize_text_field($atts['orderby']),
            'order' => sanitize_text_field($atts['order'])
        );
        
        // Add taxonomy filters
        $tax_query = array();
        
        if (!empty($atts['type'])) {
            $tax_query[] = array(
                'taxonomy' => 'property_type',
                'field' => 'slug',
                'terms' => sanitize_text_field($atts['type'])
            );
        }
        
        if (!empty($atts['location'])) {
            $tax_query[] = array(
                'taxonomy' => 'location',
                'field' => 'slug', 
                'terms' => sanitize_text_field($atts['location'])
            );
        }
        
        if (!empty($atts['amenity'])) {
            $tax_query[] = array(
                'taxonomy' => 'amenity',
                'field' => 'slug',
                'terms' => sanitize_text_field($atts['amenity'])
            );
        }
        
        if (!empty($tax_query)) {
            $args['tax_query'] = $tax_query;
        }
        
        $properties = new WP_Query($args);
        
        ob_start();
        ?>
        <div class="barefoot-properties-container">
            <?php if ($atts['show_search'] === 'yes'): ?>
                <div class="barefoot-property-search">
                    <?php echo $this->render_property_search_form(); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($atts['show_filters'] === 'yes'): ?>
                <div class="barefoot-property-filters">
                    <?php echo $this->render_property_filters(); ?>
                </div>
            <?php endif; ?>
            
            <div class="barefoot-properties-grid">
                <?php if ($properties->have_posts()): ?>
                    <?php while ($properties->have_posts()): $properties->the_post(); ?>
                        <div class="barefoot-property-card">
                            <?php echo $this->render_property_card(); ?>
                        </div>
                    <?php endwhile; ?>
                    <?php wp_reset_postdata(); ?>
                <?php else: ?>
                    <p class="barefoot-no-properties"><?php _e('No properties found.', 'barefoot-properties'); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Property search shortcode
     * Usage: [barefoot_property_search]
     */
    public function property_search_shortcode($atts) {
        $atts = shortcode_atts(array(
            'show_advanced' => 'no'
        ), $atts);
        
        ob_start();
        echo '<div class="barefoot-property-search-widget">';
        echo $this->render_property_search_form($atts['show_advanced'] === 'yes');
        echo '</div>';
        return ob_get_clean();
    }
    
    /**
     * Featured properties shortcode
     * Usage: [barefoot_featured_properties limit="3"]
     */
    public function featured_properties_shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit' => 3
        ), $atts);
        
        $args = array(
            'post_type' => 'barefoot_property',
            'posts_per_page' => intval($atts['limit']),
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key' => '_barefoot_featured',
                    'value' => '1',
                    'compare' => '='
                )
            )
        );
        
        $properties = new WP_Query($args);
        
        ob_start();
        ?>
        <div class="barefoot-featured-properties">
            <h3><?php _e('Featured Properties', 'barefoot-properties'); ?></h3>
            <div class="barefoot-featured-grid">
                <?php if ($properties->have_posts()): ?>
                    <?php while ($properties->have_posts()): $properties->the_post(); ?>
                        <div class="barefoot-featured-card">
                            <?php echo $this->render_property_card(true); ?>
                        </div>
                    <?php endwhile; ?>
                    <?php wp_reset_postdata(); ?>
                <?php else: ?>
                    <p><?php _e('No featured properties available.', 'barefoot-properties'); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Add property details to single property content
     */
    public function add_property_details($content) {
        if (is_singular('barefoot_property') && is_main_query()) {
            $property_details = $this->render_property_details();
            $content = $content . $property_details;
        }
        
        return $content;
    }
    
    /**
     * Render property search form
     */
    private function render_property_search_form($show_advanced = false) {
        ob_start();
        ?>
        <form class="barefoot-search-form" method="GET">
            <div class="search-row">
                <div class="search-field">
                    <input type="text" name="property_search" placeholder="<?php _e('Search properties...', 'barefoot-properties'); ?>" 
                           value="<?php echo esc_attr(get_query_var('property_search')); ?>">
                </div>
                
                <div class="search-field">
                    <select name="property_type">
                        <option value=""><?php _e('All Types', 'barefoot-properties'); ?></option>
                        <?php
                        $types = get_terms('property_type', array('hide_empty' => false));
                        foreach ($types as $type) {
                            echo '<option value="' . esc_attr($type->slug) . '"' . selected(get_query_var('property_type'), $type->slug, false) . '>' . esc_html($type->name) . '</option>';
                        }
                        ?>
                    </select>
                </div>
                
                <div class="search-field">
                    <select name="location">
                        <option value=""><?php _e('All Locations', 'barefoot-properties'); ?></option>
                        <?php
                        $locations = get_terms('location', array('hide_empty' => false));
                        foreach ($locations as $location) {
                            echo '<option value="' . esc_attr($location->slug) . '"' . selected(get_query_var('location'), $location->slug, false) . '>' . esc_html($location->name) . '</option>';
                        }
                        ?>
                    </select>
                </div>
                
                <div class="search-field">
                    <button type="submit" class="search-button"><?php _e('Search', 'barefoot-properties'); ?></button>
                </div>
            </div>
            
            <?php if ($show_advanced): ?>
                <div class="search-advanced">
                    <div class="search-field">
                        <label><?php _e('Min Price', 'barefoot-properties'); ?></label>
                        <input type="number" name="min_price" value="<?php echo esc_attr(get_query_var('min_price')); ?>">
                    </div>
                    
                    <div class="search-field">
                        <label><?php _e('Max Price', 'barefoot-properties'); ?></label>
                        <input type="number" name="max_price" value="<?php echo esc_attr(get_query_var('max_price')); ?>">
                    </div>
                    
                    <div class="search-field">
                        <label><?php _e('Min Occupancy', 'barefoot-properties'); ?></label>
                        <input type="number" name="min_occupancy" value="<?php echo esc_attr(get_query_var('min_occupancy')); ?>">
                    </div>
                    
                    <div class="search-field">
                        <select name="amenity" multiple>
                            <option value=""><?php _e('Select Amenities', 'barefoot-properties'); ?></option>
                            <?php
                            $amenities = get_terms('amenity', array('hide_empty' => false));
                            foreach ($amenities as $amenity) {
                                echo '<option value="' . esc_attr($amenity->slug) . '">' . esc_html($amenity->name) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                </div>
            <?php endif; ?>
        </form>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render property filters
     */
    private function render_property_filters() {
        ob_start();
        ?>
        <div class="barefoot-filters">
            <div class="filter-group">
                <label><?php _e('Sort by:', 'barefoot-properties'); ?></label>
                <select id="property-sort">
                    <option value="date"><?php _e('Newest First', 'barefoot-properties'); ?></option>
                    <option value="price_low"><?php _e('Price: Low to High', 'barefoot-properties'); ?></option>
                    <option value="price_high"><?php _e('Price: High to Low', 'barefoot-properties'); ?></option>
                    <option value="occupancy"><?php _e('Occupancy', 'barefoot-properties'); ?></option>
                </select>
            </div>
            
            <div class="filter-group">
                <label><?php _e('View:', 'barefoot-properties'); ?></label>
                <div class="view-toggle">
                    <button class="view-grid active" data-view="grid"><?php _e('Grid', 'barefoot-properties'); ?></button>
                    <button class="view-list" data-view="list"><?php _e('List', 'barefoot-properties'); ?></button>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render property card
     */
    private function render_property_card($featured = false) {
        global $post;
        
        $property_id = get_post_meta($post->ID, '_barefoot_property_id', true);
        $occupancy = get_post_meta($post->ID, '_barefoot_occupancy', true);
        $bedrooms = get_post_meta($post->ID, '_barefoot_bedrooms', true);
        $bathrooms = get_post_meta($post->ID, '_barefoot_bathrooms', true);
        $min_price = get_post_meta($post->ID, '_barefoot_min_price', true);
        $city = get_post_meta($post->ID, '_barefoot_city', true);
        $state = get_post_meta($post->ID, '_barefoot_state', true);
        
        ob_start();
        ?>
        <div class="property-card<?php echo $featured ? ' featured' : ''; ?>">
            <?php if (has_post_thumbnail()): ?>
                <div class="property-image">
                    <a href="<?php the_permalink(); ?>">
                        <?php the_post_thumbnail('medium'); ?>
                    </a>
                    <?php if ($featured): ?>
                        <span class="featured-badge"><?php _e('Featured', 'barefoot-properties'); ?></span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <div class="property-content">
                <h3 class="property-title">
                    <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                </h3>
                
                <?php if ($city || $state): ?>
                    <div class="property-location">
                        <span class="location-icon">📍</span>
                        <?php echo esc_html($city . ($city && $state ? ', ' : '') . $state); ?>
                    </div>
                <?php endif; ?>
                
                <div class="property-features">
                    <?php if ($occupancy): ?>
                        <span class="feature">
                            <span class="feature-icon">👥</span>
                            <?php printf(__('Sleeps %d', 'barefoot-properties'), $occupancy); ?>
                        </span>
                    <?php endif; ?>
                    
                    <?php if ($bedrooms): ?>
                        <span class="feature">
                            <span class="feature-icon">🛏️</span>
                            <?php printf(_n('%d Bedroom', '%d Bedrooms', $bedrooms, 'barefoot-properties'), $bedrooms); ?>
                        </span>
                    <?php endif; ?>
                    
                    <?php if ($bathrooms): ?>
                        <span class="feature">
                            <span class="feature-icon">🛁</span>
                            <?php printf(_n('%d Bathroom', '%d Bathrooms', $bathrooms, 'barefoot-properties'), $bathrooms); ?>
                        </span>
                    <?php endif; ?>
                </div>
                
                <?php if ($min_price): ?>
                    <div class="property-price">
                        <?php printf(__('From $%s/night', 'barefoot-properties'), number_format($min_price)); ?>
                    </div>
                <?php endif; ?>
                
                <div class="property-excerpt">
                    <?php echo wp_trim_words(get_the_excerpt(), 15); ?>
                </div>
                
                <div class="property-actions">
                    <a href="<?php the_permalink(); ?>" class="view-property">
                        <?php _e('View Details', 'barefoot-properties'); ?>
                    </a>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render property details for single property page
     */
    private function render_property_details() {
        global $post;
        
        // Get all property meta data
        $property_data = array(
            'property_id' => get_post_meta($post->ID, '_barefoot_property_id', true),
            'occupancy' => get_post_meta($post->ID, '_barefoot_occupancy', true),
            'bedrooms' => get_post_meta($post->ID, '_barefoot_bedrooms', true),
            'bathrooms' => get_post_meta($post->ID, '_barefoot_bathrooms', true),
            'min_price' => get_post_meta($post->ID, '_barefoot_min_price', true),
            'max_price' => get_post_meta($post->ID, '_barefoot_max_price', true),
            'min_days' => get_post_meta($post->ID, '_barefoot_min_days', true),
            'address' => get_post_meta($post->ID, '_barefoot_address', true),
            'city' => get_post_meta($post->ID, '_barefoot_city', true),
            'state' => get_post_meta($post->ID, '_barefoot_state', true),
            'zip' => get_post_meta($post->ID, '_barefoot_zip', true),
            'latitude' => get_post_meta($post->ID, '_barefoot_latitude', true),
            'longitude' => get_post_meta($post->ID, '_barefoot_longitude', true),
        );
        
        ob_start();
        ?>
        <div class="barefoot-property-details">
            <div class="property-info-grid">
                <div class="property-specifications">
                    <h3><?php _e('Property Specifications', 'barefoot-properties'); ?></h3>
                    <dl class="property-specs">
                        <?php if ($property_data['occupancy']): ?>
                            <dt><?php _e('Maximum Occupancy', 'barefoot-properties'); ?></dt>
                            <dd><?php echo esc_html($property_data['occupancy']); ?> <?php _e('guests', 'barefoot-properties'); ?></dd>
                        <?php endif; ?>
                        
                        <?php if ($property_data['bedrooms']): ?>
                            <dt><?php _e('Bedrooms', 'barefoot-properties'); ?></dt>
                            <dd><?php echo esc_html($property_data['bedrooms']); ?></dd>
                        <?php endif; ?>
                        
                        <?php if ($property_data['bathrooms']): ?>
                            <dt><?php _e('Bathrooms', 'barefoot-properties'); ?></dt>
                            <dd><?php echo esc_html($property_data['bathrooms']); ?></dd>
                        <?php endif; ?>
                        
                        <?php if ($property_data['min_days']): ?>
                            <dt><?php _e('Minimum Stay', 'barefoot-properties'); ?></dt>
                            <dd><?php printf(_n('%d night', '%d nights', $property_data['min_days'], 'barefoot-properties'), $property_data['min_days']); ?></dd>
                        <?php endif; ?>
                    </dl>
                </div>
                
                <div class="property-pricing">
                    <h3><?php _e('Pricing Information', 'barefoot-properties'); ?></h3>
                    <?php if ($property_data['min_price'] || $property_data['max_price']): ?>
                        <div class="price-range">
                            <?php 
                            $min_numeric = is_numeric($property_data['min_price']) ? floatval($property_data['min_price']) : false;
                            $max_numeric = is_numeric($property_data['max_price']) ? floatval($property_data['max_price']) : false;
                            ?>
                            <?php if ($min_numeric && $max_numeric): ?>
                                <?php printf(__('$%s - $%s per week', 'barefoot-properties'), 
                                           number_format($min_numeric), 
                                           number_format($max_numeric)); ?>
                            <?php elseif ($min_numeric): ?>
                                <?php printf(__('From $%s per week', 'barefoot-properties'), number_format($min_numeric)); ?>
                            <?php elseif ($max_numeric): ?>
                                <?php printf(__('Up to $%s per week', 'barefoot-properties'), number_format($max_numeric)); ?>
                            <?php else: ?>
                                <p class="price-contact"><?php echo esc_html($property_data['min_price'] ?: $property_data['max_price']); ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($min_numeric || $max_numeric): ?>
                        <p class="pricing-note"><?php _e('*Prices may vary by season and availability', 'barefoot-properties'); ?></p>
                    <?php endif; ?>
                </div>
                
                <?php if ($property_data['address'] || $property_data['city'] || $property_data['state']): ?>
                <div class="property-location">
                    <h3><?php _e('Location', 'barefoot-properties'); ?></h3>
                    <address class="property-address">
                        <?php if ($property_data['address']): ?>
                            <?php echo esc_html($property_data['address']); ?><br>
                        <?php endif; ?>
                        <?php if ($property_data['city'] || $property_data['state'] || $property_data['zip']): ?>
                            <?php echo esc_html($property_data['city']); ?>
                            <?php echo $property_data['city'] && ($property_data['state'] || $property_data['zip']) ? ', ' : ''; ?>
                            <?php echo esc_html($property_data['state']); ?>
                            <?php echo $property_data['state'] && $property_data['zip'] ? ' ' : ''; ?>
                            <?php echo esc_html($property_data['zip']); ?>
                        <?php endif; ?>
                    </address>
                    
                    <?php if ($property_data['latitude'] && $property_data['longitude']): ?>
                        <div class="property-map" data-lat="<?php echo esc_attr($property_data['latitude']); ?>" 
                             data-lng="<?php echo esc_attr($property_data['longitude']); ?>">
                            <!-- Map will be loaded here via JavaScript -->
                        </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="property-amenities">
                <h3><?php _e('Amenities', 'barefoot-properties'); ?></h3>
                <?php
                $amenities = get_the_terms($post->ID, 'amenity');
                if ($amenities && !is_wp_error($amenities)):
                ?>
                    <ul class="amenities-list">
                        <?php foreach ($amenities as $amenity): ?>
                            <li class="amenity-item">
                                <span class="amenity-name"><?php echo esc_html($amenity->name); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p><?php _e('Amenity information will be updated soon.', 'barefoot-properties'); ?></p>
                <?php endif; ?>
            </div>
            
            <div class="property-contact">
                <h3><?php _e('Contact Information', 'barefoot-properties'); ?></h3>
                <p><?php _e('For reservations and inquiries, please contact us:', 'barefoot-properties'); ?></p>
                <div class="contact-methods">
                    <button class="contact-button" data-property="<?php echo esc_attr($property_data['property_id']); ?>">
                        <?php _e('Request Information', 'barefoot-properties'); ?>
                    </button>
                    <button class="booking-button" data-property="<?php echo esc_attr($property_data['property_id']); ?>">
                        <?php _e('Check Availability', 'barefoot-properties'); ?>
                    </button>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * AJAX handler for property search
     */
    public function ajax_property_search() {
        check_ajax_referer('barefoot_nonce', 'nonce');
        
        $search_params = array(
            'search' => sanitize_text_field($_POST['search'] ?? ''),
            'property_type' => sanitize_text_field($_POST['property_type'] ?? ''),
            'location' => sanitize_text_field($_POST['location'] ?? ''),
            'amenity' => sanitize_text_field($_POST['amenity'] ?? ''),
            'min_price' => intval($_POST['min_price'] ?? 0),
            'max_price' => intval($_POST['max_price'] ?? 0),
            'min_occupancy' => intval($_POST['min_occupancy'] ?? 0)
        );
        
        // Build query args
        $args = array(
            'post_type' => 'barefoot_property',
            'posts_per_page' => 10,
            'post_status' => 'publish'
        );
        
        if (!empty($search_params['search'])) {
            $args['s'] = $search_params['search'];
        }
        
        // Add meta queries for pricing and occupancy
        $meta_query = array();
        
        if ($search_params['min_price'] > 0) {
            $meta_query[] = array(
                'key' => '_barefoot_min_price',
                'value' => $search_params['min_price'],
                'compare' => '>=',
                'type' => 'NUMERIC'
            );
        }
        
        if ($search_params['max_price'] > 0) {
            $meta_query[] = array(
                'key' => '_barefoot_max_price',
                'value' => $search_params['max_price'],
                'compare' => '<=',
                'type' => 'NUMERIC'
            );
        }
        
        if ($search_params['min_occupancy'] > 0) {
            $meta_query[] = array(
                'key' => '_barefoot_occupancy',
                'value' => $search_params['min_occupancy'],
                'compare' => '>=',
                'type' => 'NUMERIC'
            );
        }
        
        if (!empty($meta_query)) {
            $args['meta_query'] = $meta_query;
        }
        
        // Add tax queries
        $tax_query = array();
        
        if (!empty($search_params['property_type'])) {
            $tax_query[] = array(
                'taxonomy' => 'property_type',
                'field' => 'slug',
                'terms' => $search_params['property_type']
            );
        }
        
        if (!empty($search_params['location'])) {
            $tax_query[] = array(
                'taxonomy' => 'location',
                'field' => 'slug',
                'terms' => $search_params['location']
            );
        }
        
        if (!empty($search_params['amenity'])) {
            $tax_query[] = array(
                'taxonomy' => 'amenity',
                'field' => 'slug',
                'terms' => $search_params['amenity']
            );
        }
        
        if (!empty($tax_query)) {
            $args['tax_query'] = $tax_query;
        }
        
        $properties = new WP_Query($args);
        
        $results = array();
        if ($properties->have_posts()) {
            while ($properties->have_posts()) {
                $properties->the_post();
                global $post;
                
                $results[] = array(
                    'id' => $post->ID,
                    'title' => get_the_title(),
                    'permalink' => get_permalink(),
                    'excerpt' => wp_trim_words(get_the_excerpt(), 15),
                    'thumbnail' => get_the_post_thumbnail_url($post->ID, 'medium'),
                    'occupancy' => get_post_meta($post->ID, '_barefoot_occupancy', true),
                    'min_price' => get_post_meta($post->ID, '_barefoot_min_price', true),
                    'city' => get_post_meta($post->ID, '_barefoot_city', true),
                    'state' => get_post_meta($post->ID, '_barefoot_state', true)
                );
            }
            wp_reset_postdata();
        }
        
        wp_send_json_success(array(
            'properties' => $results,
            'found' => $properties->found_posts
        ));
    }
}