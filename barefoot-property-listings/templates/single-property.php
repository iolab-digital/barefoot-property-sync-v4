<?php
/**
 * Single Property Template
 * 
 * @package BarefootPropertyListings
 * @since 1.1.0
 */

get_header(); ?>

<div class="barefoot-single-property">
    <?php while (have_posts()) : the_post(); ?>
        
        <article id="post-<?php the_ID(); ?>" <?php post_class('property-single'); ?>>
            
            <header class="property-header">
                <div class="property-title-section">
                    <h1 class="property-title"><?php the_title(); ?></h1>
                    
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
                </div>
                
                <?php
                $min_price = get_post_meta(get_the_ID(), '_barefoot_min_price', true);
                $max_price = get_post_meta(get_the_ID(), '_barefoot_max_price', true);
                
                // Check if prices are numeric
                $min_price_numeric = is_numeric($min_price) ? floatval($min_price) : false;
                $max_price_numeric = is_numeric($max_price) ? floatval($max_price) : false;
                
                if ($min_price || $max_price):
                ?>
                    <div class="property-price">
                        <?php if ($min_price_numeric && $max_price_numeric): ?>
                            <span class="price-range">$<?php echo number_format($min_price_numeric); ?> - $<?php echo number_format($max_price_numeric); ?></span>
                        <?php elseif ($min_price_numeric): ?>
                            <span class="price-from">From $<?php echo number_format($min_price_numeric); ?></span>
                        <?php elseif ($max_price_numeric): ?>
                            <span class="price-up-to">Up to $<?php echo number_format($max_price_numeric); ?></span>
                        <?php else: ?>
                            <span class="price-contact"><?php echo esc_html($min_price ?: $max_price); ?></span>
                        <?php endif; ?>
                        <?php if ($min_price_numeric || $max_price_numeric): ?>
                            <span class="price-unit"><?php _e('per week', 'barefoot-properties'); ?></span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </header>
            
            <?php if (has_post_thumbnail()): ?>
                <div class="property-featured-image">
                    <?php the_post_thumbnail('large'); ?>
                </div>
            <?php endif; ?>
            
            <div class="property-content">
                <div class="property-main">
                    <div class="property-description">
                        <?php the_content(); ?>
                    </div>
                    
                    <?php
                    // Display property gallery if available
                    $attachments = get_attached_media('image', get_the_ID());
                    if (!empty($attachments) && count($attachments) > 1):
                    ?>
                        <div class="property-gallery">
                            <h3><?php _e('Property Gallery', 'barefoot-properties'); ?></h3>
                            <div class="gallery-grid">
                                <?php foreach ($attachments as $attachment): ?>
                                    <div class="gallery-item">
                                        <a href="<?php echo wp_get_attachment_image_url($attachment->ID, 'full'); ?>" class="gallery-link">
                                            <?php echo wp_get_attachment_image($attachment->ID, 'medium'); ?>
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="property-sidebar">
                    <div class="property-quick-info">
                        <h3><?php _e('Property Information', 'barefoot-properties'); ?></h3>
                        
                        <?php
                        $occupancy = get_post_meta(get_the_ID(), '_barefoot_occupancy', true);
                        $bedrooms = get_post_meta(get_the_ID(), '_barefoot_bedrooms', true);
                        $bathrooms = get_post_meta(get_the_ID(), '_barefoot_bathrooms', true);
                        $min_days = get_post_meta(get_the_ID(), '_barefoot_min_days', true);
                        ?>
                        
                        <ul class="property-features">
                            <?php if ($occupancy): ?>
                                <li class="feature-item">
                                    <span class="feature-icon">👥</span>
                                    <span class="feature-text"><?php printf(__('Sleeps %d guests', 'barefoot-properties'), $occupancy); ?></span>
                                </li>
                            <?php endif; ?>
                            
                            <?php if ($bedrooms): ?>
                                <li class="feature-item">
                                    <span class="feature-icon">🛏️</span>
                                    <span class="feature-text"><?php printf(_n('%d Bedroom', '%d Bedrooms', $bedrooms, 'barefoot-properties'), $bedrooms); ?></span>
                                </li>
                            <?php endif; ?>
                            
                            <?php if ($bathrooms): ?>
                                <li class="feature-item">
                                    <span class="feature-icon">🛁</span>
                                    <span class="feature-text"><?php printf(_n('%d Bathroom', '%d Bathrooms', $bathrooms, 'barefoot-properties'), $bathrooms); ?></span>
                                </li>
                            <?php endif; ?>
                            
                            <?php if ($min_days): ?>
                                <li class="feature-item">
                                    <span class="feature-icon">📅</span>
                                    <span class="feature-text"><?php printf(_n('%d night minimum', '%d nights minimum', $min_days, 'barefoot-properties'), $min_days); ?></span>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    
                    <?php
                    // Display amenities
                    $amenities = get_the_terms(get_the_ID(), 'amenity');
                    if ($amenities && !is_wp_error($amenities)):
                    ?>
                        <div class="property-amenities">
                            <h3><?php _e('Amenities', 'barefoot-properties'); ?></h3>
                            <ul class="amenities-list">
                                <?php foreach ($amenities as $amenity): ?>
                                    <li class="amenity-item">
                                        <span class="amenity-check">✓</span>
                                        <?php echo esc_html($amenity->name); ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <div class="property-contact">
                        <h3><?php _e('Interested?', 'barefoot-properties'); ?></h3>
                        <p><?php _e('Contact us for availability and rates.', 'barefoot-properties'); ?></p>
                        
                        <button class="contact-button" data-property="<?php echo esc_attr(get_post_meta(get_the_ID(), '_barefoot_property_id', true)); ?>">
                            <?php _e('Request Information', 'barefoot-properties'); ?>
                        </button>
                        
                        <button class="booking-button" data-property="<?php echo esc_attr(get_post_meta(get_the_ID(), '_barefoot_property_id', true)); ?>">
                            <?php _e('Check Availability', 'barefoot-properties'); ?>
                        </button>
                    </div>
                </div>
            </div>
            
            <?php
            // Display location map if coordinates are available
            $latitude = get_post_meta(get_the_ID(), '_barefoot_latitude', true);
            $longitude = get_post_meta(get_the_ID(), '_barefoot_longitude', true);
            if ($latitude && $longitude):
            ?>
                <div class="property-map-section">
                    <h3><?php _e('Location', 'barefoot-properties'); ?></h3>
                    <div class="property-map" 
                         data-lat="<?php echo esc_attr($latitude); ?>" 
                         data-lng="<?php echo esc_attr($longitude); ?>"
                         data-title="<?php echo esc_attr(get_the_title()); ?>">
                        <p><?php _e('Map loading...', 'barefoot-properties'); ?></p>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="property-related">
                <h3><?php _e('Similar Properties', 'barefoot-properties'); ?></h3>
                <?php
                // Get related properties
                $property_type_terms = get_the_terms(get_the_ID(), 'property_type');
                $location_terms = get_the_terms(get_the_ID(), 'location');
                
                $related_args = array(
                    'post_type' => 'barefoot_property',
                    'posts_per_page' => 3,
                    'post__not_in' => array(get_the_ID()),
                    'orderby' => 'rand'
                );
                
                $tax_query = array('relation' => 'OR');
                
                if ($property_type_terms && !is_wp_error($property_type_terms)) {
                    $tax_query[] = array(
                        'taxonomy' => 'property_type',
                        'field' => 'term_id',
                        'terms' => wp_list_pluck($property_type_terms, 'term_id')
                    );
                }
                
                if ($location_terms && !is_wp_error($location_terms)) {
                    $tax_query[] = array(
                        'taxonomy' => 'location',
                        'field' => 'term_id',
                        'terms' => wp_list_pluck($location_terms, 'term_id')
                    );
                }
                
                if (count($tax_query) > 1) {
                    $related_args['tax_query'] = $tax_query;
                }
                
                $related_properties = new WP_Query($related_args);
                
                if ($related_properties->have_posts()):
                ?>
                    <div class="related-properties">
                        <?php while ($related_properties->have_posts()): $related_properties->the_post(); ?>
                            <div class="related-property">
                                <?php if (has_post_thumbnail()): ?>
                                    <div class="related-image">
                                        <a href="<?php the_permalink(); ?>">
                                            <?php the_post_thumbnail('medium'); ?>
                                        </a>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="related-content">
                                    <h4><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h4>
                                    
                                    <?php
                                    $related_city = get_post_meta(get_the_ID(), '_barefoot_city', true);
                                    $related_state = get_post_meta(get_the_ID(), '_barefoot_state', true);
                                    if ($related_city || $related_state):
                                    ?>
                                        <p class="related-location">
                                            <?php echo esc_html($related_city . ($related_city && $related_state ? ', ' : '') . $related_state); ?>
                                        </p>
                                    <?php endif; ?>
                                    
                                    <?php
                                    $related_min_price = get_post_meta(get_the_ID(), '_barefoot_min_price', true);
                                    if ($related_min_price && is_numeric($related_min_price)):
                                    ?>
                                        <p class="related-price">From $<?php echo number_format(floatval($related_min_price)); ?>/week</p>
                                    <?php elseif ($related_min_price): ?>
                                        <p class="related-price"><?php echo esc_html($related_min_price); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php
                    wp_reset_postdata();
                else:
                ?>
                    <p><?php _e('No similar properties found.', 'barefoot-properties'); ?></p>
                <?php endif; ?>
            </div>
            
        </article>
        
        <!-- Property Inquiry Modal -->
        <div id="property-inquiry-modal" class="modal" style="display: none;">
            <div class="modal-content">
                <span class="modal-close">&times;</span>
                <h3><?php _e('Property Inquiry', 'barefoot-properties'); ?></h3>
                
                <form id="property-inquiry-form">
                    <input type="hidden" name="property_id" value="<?php echo esc_attr(get_post_meta(get_the_ID(), '_barefoot_property_id', true)); ?>">
                    
                    <div class="form-row">
                        <label for="inquiry_name"><?php _e('Name *', 'barefoot-properties'); ?></label>
                        <input type="text" id="inquiry_name" name="name" required>
                    </div>
                    
                    <div class="form-row">
                        <label for="inquiry_email"><?php _e('Email *', 'barefoot-properties'); ?></label>
                        <input type="email" id="inquiry_email" name="email" required>
                    </div>
                    
                    <div class="form-row">
                        <label for="inquiry_phone"><?php _e('Phone', 'barefoot-properties'); ?></label>
                        <input type="tel" id="inquiry_phone" name="phone">
                    </div>
                    
                    <div class="form-row-group">
                        <div class="form-row">
                            <label for="inquiry_checkin"><?php _e('Check-in Date', 'barefoot-properties'); ?></label>
                            <input type="date" id="inquiry_checkin" name="check_in">
                        </div>
                        
                        <div class="form-row">
                            <label for="inquiry_checkout"><?php _e('Check-out Date', 'barefoot-properties'); ?></label>
                            <input type="date" id="inquiry_checkout" name="check_out">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <label for="inquiry_guests"><?php _e('Number of Guests', 'barefoot-properties'); ?></label>
                        <select id="inquiry_guests" name="guests">
                            <?php for ($i = 1; $i <= 20; $i++): ?>
                                <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    
                    <div class="form-row">
                        <label for="inquiry_message"><?php _e('Message *', 'barefoot-properties'); ?></label>
                        <textarea id="inquiry_message" name="message" rows="4" required 
                                  placeholder="<?php esc_attr_e('Please let us know about your stay requirements, any questions, or special requests...', 'barefoot-properties'); ?>"></textarea>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="submit-button"><?php _e('Send Inquiry', 'barefoot-properties'); ?></button>
                        <button type="button" class="cancel-button"><?php _e('Cancel', 'barefoot-properties'); ?></button>
                    </div>
                </form>
            </div>
        </div>
        
    <?php endwhile; ?>
</div>

<?php get_footer(); ?>