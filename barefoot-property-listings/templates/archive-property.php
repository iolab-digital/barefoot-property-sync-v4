<?php
/**
 * Archive Template for Properties
 * 
 * @package BarefootPropertyListings
 * @since 1.1.0
 */

get_header(); ?>

<div class="barefoot-archive-properties">
    
    <header class="archive-header">
        <div class="archive-title-section">
            <?php if (is_post_type_archive('barefoot_property')): ?>
                <h1 class="archive-title"><?php _e('All Properties', 'barefoot-properties'); ?></h1>
                <p class="archive-description"><?php _e('Browse our collection of vacation rental properties.', 'barefoot-properties'); ?></p>
            <?php elseif (is_tax()): ?>
                <h1 class="archive-title"><?php single_term_title(); ?></h1>
                <?php if (term_description()): ?>
                    <div class="archive-description"><?php echo term_description(); ?></div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        
        <div class="archive-stats">
            <span class="properties-count">
                <?php
                global $wp_query;
                printf(_n('%d property found', '%d properties found', $wp_query->found_posts, 'barefoot-properties'), $wp_query->found_posts);
                ?>
            </span>
        </div>
    </header>
    
    <div class="archive-controls">
        <div class="search-form-container">
            <?php echo do_shortcode('[barefoot_property_search show_advanced="yes"]'); ?>
        </div>
        
        <div class="archive-filters">
            <div class="filter-group">
                <label for="sort-properties"><?php _e('Sort by:', 'barefoot-properties'); ?></label>
                <select id="sort-properties">
                    <option value="date"><?php _e('Newest First', 'barefoot-properties'); ?></option>
                    <option value="title"><?php _e('Name A-Z', 'barefoot-properties'); ?></option>
                    <option value="price_low"><?php _e('Price: Low to High', 'barefoot-properties'); ?></option>
                    <option value="price_high"><?php _e('Price: High to Low', 'barefoot-properties'); ?></option>
                    <option value="occupancy"><?php _e('Occupancy', 'barefoot-properties'); ?></option>
                </select>
            </div>
            
            <div class="filter-group">
                <label><?php _e('View:', 'barefoot-properties'); ?></label>
                <div class="view-toggle">
                    <button class="view-grid active" data-view="grid" title="<?php esc_attr_e('Grid View', 'barefoot-properties'); ?>">
                        <span class="view-icon">⊞</span>
                    </button>
                    <button class="view-list" data-view="list" title="<?php esc_attr_e('List View', 'barefoot-properties'); ?>">
                        <span class="view-icon">☰</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <div class="archive-content">
        <?php if (have_posts()): ?>
            
            <div class="properties-container" data-view="grid">
                <div class="properties-grid">
                    <?php while (have_posts()): the_post(); ?>
                        
                        <article id="post-<?php the_ID(); ?>" <?php post_class('property-card'); ?>>
                            
                            <?php if (has_post_thumbnail()): ?>
                                <div class="property-image">
                                    <a href="<?php the_permalink(); ?>">
                                        <?php the_post_thumbnail('medium'); ?>
                                    </a>
                                    
                                    <?php if (get_post_meta(get_the_ID(), '_barefoot_featured', true)): ?>
                                        <span class="featured-badge"><?php _e('Featured', 'barefoot-properties'); ?></span>
                                    <?php endif; ?>
                                    
                                    <?php
                                    $min_price = get_post_meta(get_the_ID(), '_barefoot_min_price', true);
                                    if ($min_price):
                                        if (is_numeric($min_price)):
                                    ?>
                                        <div class="price-overlay">
                                            <span class="price-text">From $<?php echo number_format(floatval($min_price)); ?></span>
                                            <span class="price-unit">/week</span>
                                        </div>
                                    <?php else: ?>
                                        <div class="price-overlay">
                                            <span class="price-text"><?php echo esc_html($min_price); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="property-content">
                                <header class="property-header">
                                    <h2 class="property-title">
                                        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                    </h2>
                                    
                                    <?php
                                    $city = get_post_meta(get_the_ID(), '_barefoot_city', true);
                                    $state = get_post_meta(get_the_ID(), '_barefoot_state', true);
                                    if ($city || $state):
                                    ?>
                                        <div class="property-location">
                                            <span class="location-icon">📍</span>
                                            <?php echo esc_html($city . ($city && $state ? ', ' : '') . $state); ?>
                                        </div>
                                    <?php endif; ?>
                                </header>
                                
                                <div class="property-features">
                                    <?php
                                    $occupancy = get_post_meta(get_the_ID(), '_barefoot_occupancy', true);
                                    $bedrooms = get_post_meta(get_the_ID(), '_barefoot_bedrooms', true);
                                    $bathrooms = get_post_meta(get_the_ID(), '_barefoot_bathrooms', true);
                                    ?>
                                    
                                    <?php if ($occupancy): ?>
                                        <span class="feature">
                                            <span class="feature-icon">👥</span>
                                            <span class="feature-text"><?php printf(__('Sleeps %d', 'barefoot-properties'), $occupancy); ?></span>
                                        </span>
                                    <?php endif; ?>
                                    
                                    <?php if ($bedrooms): ?>
                                        <span class="feature">
                                            <span class="feature-icon">🛏️</span>
                                            <span class="feature-text"><?php printf(_n('%d Bedroom', '%d Bedrooms', $bedrooms, 'barefoot-properties'), $bedrooms); ?></span>
                                        </span>
                                    <?php endif; ?>
                                    
                                    <?php if ($bathrooms): ?>
                                        <span class="feature">
                                            <span class="feature-icon">🛁</span>
                                            <span class="feature-text"><?php printf(_n('%d Bathroom', '%d Bathrooms', $bathrooms, 'barefoot-properties'), $bathrooms); ?></span>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="property-excerpt">
                                    <?php echo wp_trim_words(get_the_excerpt(), 20); ?>
                                </div>
                                
                                <?php
                                $amenities = get_the_terms(get_the_ID(), 'amenity');
                                if ($amenities && !is_wp_error($amenities) && count($amenities) > 0):
                                ?>
                                    <div class="property-amenities-preview">
                                        <?php
                                        $amenity_names = wp_list_pluck($amenities, 'name');
                                        echo '<span class="amenities-text">' . implode(', ', array_slice($amenity_names, 0, 3)) . '</span>';
                                        if (count($amenities) > 3):
                                            echo '<span class="amenities-more"> +' . (count($amenities) - 3) . ' more</span>';
                                        endif;
                                        ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <footer class="property-footer">
                                <div class="property-actions">
                                    <a href="<?php the_permalink(); ?>" class="view-details-button">
                                        <?php _e('View Details', 'barefoot-properties'); ?>
                                    </a>
                                    
                                    <button class="quick-inquiry-button" 
                                            data-property="<?php echo esc_attr(get_post_meta(get_the_ID(), '_barefoot_property_id', true)); ?>"
                                            data-title="<?php echo esc_attr(get_the_title()); ?>">
                                        <?php _e('Quick Inquiry', 'barefoot-properties'); ?>
                                    </button>
                                </div>
                            </footer>
                            
                        </article>
                        
                    <?php endwhile; ?>
                </div>
            </div>
            
            <?php
            // Pagination
            the_posts_pagination(array(
                'mid_size' => 2,
                'prev_text' => __('← Previous', 'barefoot-properties'),
                'next_text' => __('Next →', 'barefoot-properties'),
                'before_page_number' => '<span class="screen-reader-text">' . __('Page', 'barefoot-properties') . ' </span>',
            ));
            ?>
            
        <?php else: ?>
            
            <div class="no-properties-found">
                <h2><?php _e('No Properties Found', 'barefoot-properties'); ?></h2>
                <p><?php _e('Sorry, no properties match your search criteria. Please try adjusting your filters or search terms.', 'barefoot-properties'); ?></p>
                
                <div class="no-results-suggestions">
                    <h3><?php _e('Suggestions:', 'barefoot-properties'); ?></h3>
                    <ul>
                        <li><?php _e('Try different location keywords', 'barefoot-properties'); ?></li>
                        <li><?php _e('Adjust your price range', 'barefoot-properties'); ?></li>
                        <li><?php _e('Reduce the number of guests', 'barefoot-properties'); ?></li>
                        <li><a href="<?php echo get_post_type_archive_link('barefoot_property'); ?>"><?php _e('View all properties', 'barefoot-properties'); ?></a></li>
                    </ul>
                </div>
            </div>
            
        <?php endif; ?>
    </div>
    
    <aside class="archive-sidebar">
        <div class="sidebar-widget">
            <h3><?php _e('Property Types', 'barefoot-properties'); ?></h3>
            <?php
            $property_types = get_terms(array(
                'taxonomy' => 'property_type',
                'hide_empty' => true
            ));
            
            if (!empty($property_types) && !is_wp_error($property_types)):
            ?>
                <ul class="property-type-filter">
                    <?php foreach ($property_types as $type): ?>
                        <li>
                            <a href="<?php echo get_term_link($type); ?>" class="filter-link">
                                <?php echo esc_html($type->name); ?>
                                <span class="count">(<?php echo $type->count; ?>)</span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
        
        <div class="sidebar-widget">
            <h3><?php _e('Locations', 'barefoot-properties'); ?></h3>
            <?php
            $locations = get_terms(array(
                'taxonomy' => 'location',
                'hide_empty' => true,
                'number' => 10
            ));
            
            if (!empty($locations) && !is_wp_error($locations)):
            ?>
                <ul class="location-filter">
                    <?php foreach ($locations as $location): ?>
                        <li>
                            <a href="<?php echo get_term_link($location); ?>" class="filter-link">
                                <?php echo esc_html($location->name); ?>
                                <span class="count">(<?php echo $location->count; ?>)</span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
        
        <div class="sidebar-widget">
            <h3><?php _e('Popular Amenities', 'barefoot-properties'); ?></h3>
            <?php
            $popular_amenities = get_terms(array(
                'taxonomy' => 'amenity',
                'hide_empty' => true,
                'orderby' => 'count',
                'order' => 'DESC',
                'number' => 8
            ));
            
            if (!empty($popular_amenities) && !is_wp_error($popular_amenities)):
            ?>
                <div class="amenity-tags">
                    <?php foreach ($popular_amenities as $amenity): ?>
                        <a href="<?php echo get_term_link($amenity); ?>" class="amenity-tag">
                            <?php echo esc_html($amenity->name); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="sidebar-widget">
            <h3><?php _e('Need Help?', 'barefoot-properties'); ?></h3>
            <p><?php _e('Contact us if you need assistance finding the perfect property for your stay.', 'barefoot-properties'); ?></p>
            
            <?php
            $contact_phone = get_option('barefoot_contact_phone');
            $contact_email = get_option('barefoot_contact_email');
            ?>
            
            <div class="contact-info">
                <?php if ($contact_phone): ?>
                    <p><strong><?php _e('Phone:', 'barefoot-properties'); ?></strong> <a href="tel:<?php echo esc_attr($contact_phone); ?>"><?php echo esc_html($contact_phone); ?></a></p>
                <?php endif; ?>
                
                <?php if ($contact_email): ?>
                    <p><strong><?php _e('Email:', 'barefoot-properties'); ?></strong> <a href="mailto:<?php echo esc_attr($contact_email); ?>"><?php echo esc_html($contact_email); ?></a></p>
                <?php endif; ?>
            </div>
        </div>
    </aside>
    
</div>

<!-- Quick Inquiry Modal (reused from single template) -->
<div id="quick-inquiry-modal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="modal-close">&times;</span>
        <h3><?php _e('Quick Property Inquiry', 'barefoot-properties'); ?></h3>
        <p class="modal-property-title"></p>
        
        <form id="quick-inquiry-form">
            <input type="hidden" name="property_id" value="">
            
            <div class="form-row-group">
                <div class="form-row">
                    <label for="quick_name"><?php _e('Name *', 'barefoot-properties'); ?></label>
                    <input type="text" id="quick_name" name="name" required>
                </div>
                
                <div class="form-row">
                    <label for="quick_email"><?php _e('Email *', 'barefoot-properties'); ?></label>
                    <input type="email" id="quick_email" name="email" required>
                </div>
            </div>
            
            <div class="form-row-group">
                <div class="form-row">
                    <label for="quick_checkin"><?php _e('Check-in', 'barefoot-properties'); ?></label>
                    <input type="date" id="quick_checkin" name="check_in">
                </div>
                
                <div class="form-row">
                    <label for="quick_guests"><?php _e('Guests', 'barefoot-properties'); ?></label>
                    <select id="quick_guests" name="guests">
                        <?php for ($i = 1; $i <= 12; $i++): ?>
                            <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <label for="quick_message"><?php _e('Message *', 'barefoot-properties'); ?></label>
                <textarea id="quick_message" name="message" rows="3" required 
                          placeholder="<?php esc_attr_e('I am interested in this property. Please send me more information.', 'barefoot-properties'); ?>"></textarea>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="submit-button"><?php _e('Send Inquiry', 'barefoot-properties'); ?></button>
                <button type="button" class="cancel-button"><?php _e('Cancel', 'barefoot-properties'); ?></button>
            </div>
        </form>
    </div>
</div>

<?php get_footer(); ?>