<?php
/**
 * Single Property Template
 * Template for displaying individual property pages
 */

get_header(); ?>

<div id="primary" class="content-area">
    <main id="main" class="site-main">
        
        <?php while (have_posts()) : the_post(); ?>
            
            <article id="post-<?php the_ID(); ?>" <?php post_class('barefoot-single-property'); ?>>
                
                <header class="entry-header">
                    <h1 class="entry-title"><?php the_title(); ?></h1>
                </header>
                
                <div class="property-content">
                    
                    <?php if (has_post_thumbnail()) : ?>
                    <div class="property-featured-image">
                        <?php the_post_thumbnail('large'); ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="property-details-grid">
                        
                        <div class="property-main-info">
                            <div class="property-summary">
                                <?php
                                $bedrooms = get_post_meta(get_the_ID(), '_barefoot_bedrooms', true);
                                $bathrooms = get_post_meta(get_the_ID(), '_barefoot_bathrooms', true);
                                $max_guests = get_post_meta(get_the_ID(), '_barefoot_max_guests', true);
                                $square_feet = get_post_meta(get_the_ID(), '_barefoot_square_feet', true);
                                $price = get_post_meta(get_the_ID(), '_barefoot_price', true);
                                ?>
                                
                                <div class="property-stats">
                                    <?php if ($bedrooms) : ?>
                                    <div class="stat">
                                        <span class="icon">🛏️</span>
                                        <span class="label"><?php echo esc_html($bedrooms); ?> Bedroom<?php echo $bedrooms > 1 ? 's' : ''; ?></span>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($bathrooms) : ?>
                                    <div class="stat">
                                        <span class="icon">🛁</span>
                                        <span class="label"><?php echo esc_html($bathrooms); ?> Bathroom<?php echo $bathrooms > 1 ? 's' : ''; ?></span>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($max_guests) : ?>
                                    <div class="stat">
                                        <span class="icon">👥</span>
                                        <span class="label">Up to <?php echo esc_html($max_guests); ?> Guests</span>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($square_feet) : ?>
                                    <div class="stat">
                                        <span class="icon">📏</span>
                                        <span class="label"><?php echo number_format($square_feet); ?> sq ft</span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if ($price) : ?>
                                <div class="property-price">
                                    <span class="price-amount">$<?php echo number_format($price); ?></span>
                                    <span class="price-period">per night</span>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="property-description">
                                <h2><?php _e('Description', 'barefoot-properties'); ?></h2>
                                <div class="entry-content">
                                    <?php the_content(); ?>
                                </div>
                            </div>
                            
                            <?php 
                            // Display amenities
                            $amenities = get_the_terms(get_the_ID(), 'amenity');
                            if ($amenities && !is_wp_error($amenities)) :
                            ?>
                            <div class="property-amenities">
                                <h2><?php _e('Amenities', 'barefoot-properties'); ?></h2>
                                <ul class="amenities-list">
                                    <?php foreach ($amenities as $amenity) : ?>
                                    <li><?php echo esc_html($amenity->name); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <?php endif; ?>
                            
                            <?php
                            // Display additional amenities from meta
                            $meta_amenities = array(
                                '_barefoot_pets_allowed' => __('Pets Allowed', 'barefoot-properties'),
                                '_barefoot_pool' => __('Swimming Pool', 'barefoot-properties'),
                                '_barefoot_hot_tub' => __('Hot Tub', 'barefoot-properties'),
                                '_barefoot_internet' => __('WiFi Internet', 'barefoot-properties'),
                                '_barefoot_air_conditioning' => __('Air Conditioning', 'barefoot-properties'),
                                '_barefoot_heating' => __('Heating', 'barefoot-properties'),
                                '_barefoot_parking' => __('Parking Available', 'barefoot-properties'),
                                '_barefoot_kitchen' => __('Full Kitchen', 'barefoot-properties'),
                                '_barefoot_washer_dryer' => __('Washer/Dryer', 'barefoot-properties'),
                                '_barefoot_tv_cable' => __('TV/Cable', 'barefoot-properties')
                            );
                            
                            $available_amenities = array();
                            foreach ($meta_amenities as $meta_key => $label) {
                                if (get_post_meta(get_the_ID(), $meta_key, true)) {
                                    $available_amenities[] = $label;
                                }
                            }
                            
                            if (!empty($available_amenities)) :
                            ?>
                            <div class="property-features">
                                <h2><?php _e('Features', 'barefoot-properties'); ?></h2>
                                <ul class="features-list">
                                    <?php foreach ($available_amenities as $feature) : ?>
                                    <li>✓ <?php echo esc_html($feature); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <?php endif; ?>
                            
                        </div>
                        
                        <div class="property-sidebar">
                            
                            <div class="booking-widget">
                                <h3><?php _e('Book This Property', 'barefoot-properties'); ?></h3>
                                
                                <form class="booking-inquiry-form" method="post" action="#">
                                    <div class="form-row">
                                        <label for="check_in"><?php _e('Check-in', 'barefoot-properties'); ?></label>
                                        <input type="date" id="check_in" name="check_in" required>
                                    </div>
                                    
                                    <div class="form-row">
                                        <label for="check_out"><?php _e('Check-out', 'barefoot-properties'); ?></label>
                                        <input type="date" id="check_out" name="check_out" required>
                                    </div>
                                    
                                    <div class="form-row">
                                        <label for="guests"><?php _e('Guests', 'barefoot-properties'); ?></label>
                                        <select id="guests" name="guests">
                                            <?php for ($i = 1; $i <= ($max_guests ?: 8); $i++) : ?>
                                            <option value="<?php echo $i; ?>"><?php echo $i; ?> Guest<?php echo $i > 1 ? 's' : ''; ?></option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="form-row">
                                        <label for="guest_name"><?php _e('Your Name', 'barefoot-properties'); ?></label>
                                        <input type="text" id="guest_name" name="guest_name" required>
                                    </div>
                                    
                                    <div class="form-row">
                                        <label for="guest_email"><?php _e('Email', 'barefoot-properties'); ?></label>
                                        <input type="email" id="guest_email" name="guest_email" required>
                                    </div>
                                    
                                    <div class="form-row">
                                        <label for="guest_phone"><?php _e('Phone', 'barefoot-properties'); ?></label>
                                        <input type="tel" id="guest_phone" name="guest_phone">
                                    </div>
                                    
                                    <div class="form-row">
                                        <label for="message"><?php _e('Message', 'barefoot-properties'); ?></label>
                                        <textarea id="message" name="message" rows="3" placeholder="<?php _e('Any special requests or questions?', 'barefoot-properties'); ?>"></textarea>
                                    </div>
                                    
                                    <button type="submit" class="booking-submit"><?php _e('Send Inquiry', 'barefoot-properties'); ?></button>
                                    
                                    <input type="hidden" name="property_id" value="<?php echo get_the_ID(); ?>">
                                    <input type="hidden" name="action" value="booking_inquiry">
                                    <?php wp_nonce_field('booking_inquiry', 'booking_nonce'); ?>
                                </form>
                            </div>
                            
                            <?php
                            // Display location info
                            $address = get_post_meta(get_the_ID(), '_barefoot_address', true);
                            $city = get_post_meta(get_the_ID(), '_barefoot_city', true);
                            $state = get_post_meta(get_the_ID(), '_barefoot_state', true);
                            $zip = get_post_meta(get_the_ID(), '_barefoot_zip', true);
                            
                            if ($address || $city) :
                            ?>
                            <div class="property-location">
                                <h3><?php _e('Location', 'barefoot-properties'); ?></h3>
                                <div class="location-info">
                                    <?php if ($address) : ?>
                                    <p><?php echo esc_html($address); ?></p>
                                    <?php endif; ?>
                                    
                                    <?php if ($city || $state) : ?>
                                    <p>
                                        <?php echo esc_html($city); ?><?php if ($city && $state) echo ', '; ?><?php echo esc_html($state); ?>
                                        <?php if ($zip) echo ' ' . esc_html($zip); ?>
                                    </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <?php
                            // Display check-in/check-out times
                            $check_in_time = get_post_meta(get_the_ID(), '_barefoot_check_in', true);
                            $check_out_time = get_post_meta(get_the_ID(), '_barefoot_check_out', true);
                            $min_stay = get_post_meta(get_the_ID(), '_barefoot_min_stay', true);
                            $max_stay = get_post_meta(get_the_ID(), '_barefoot_max_stay', true);
                            
                            if ($check_in_time || $check_out_time || $min_stay || $max_stay) :
                            ?>
                            <div class="property-policies">
                                <h3><?php _e('Policies', 'barefoot-properties'); ?></h3>
                                <ul>
                                    <?php if ($check_in_time) : ?>
                                    <li><?php _e('Check-in:', 'barefoot-properties'); ?> <?php echo esc_html($check_in_time); ?></li>
                                    <?php endif; ?>
                                    
                                    <?php if ($check_out_time) : ?>
                                    <li><?php _e('Check-out:', 'barefoot-properties'); ?> <?php echo esc_html($check_out_time); ?></li>
                                    <?php endif; ?>
                                    
                                    <?php if ($min_stay) : ?>
                                    <li><?php _e('Minimum stay:', 'barefoot-properties'); ?> <?php echo esc_html($min_stay); ?> night<?php echo $min_stay > 1 ? 's' : ''; ?></li>
                                    <?php endif; ?>
                                    
                                    <?php if ($max_stay) : ?>
                                    <li><?php _e('Maximum stay:', 'barefoot-properties'); ?> <?php echo esc_html($max_stay); ?> night<?php echo $max_stay > 1 ? 's' : ''; ?></li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                    </div>
                    
                </div>
                
            </article>
            
        <?php endwhile; ?>
        
    </main>
</div>

<style>
.barefoot-single-property {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.property-featured-image {
    margin-bottom: 30px;
}

.property-featured-image img {
    width: 100%;
    height: 400px;
    object-fit: cover;
    border-radius: 8px;
}

.property-details-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 40px;
    margin-top: 30px;
}

.property-summary {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 30px;
}

.property-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.stat {
    display: flex;
    align-items: center;
    gap: 10px;
}

.stat .icon {
    font-size: 1.2em;
}

.property-price {
    text-align: center;
    padding: 15px;
    background: white;
    border-radius: 6px;
    border: 2px solid #2c5aa0;
}

.price-amount {
    display: block;
    font-size: 2em;
    font-weight: bold;
    color: #2c5aa0;
}

.price-period {
    color: #666;
    font-size: 0.9em;
}

.property-description h2,
.property-amenities h2,
.property-features h2 {
    color: #2c5aa0;
    border-bottom: 2px solid #e9ecef;
    padding-bottom: 10px;
    margin-bottom: 20px;
}

.amenities-list,
.features-list {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 10px;
    list-style: none;
    padding: 0;
}

.amenities-list li,
.features-list li {
    padding: 8px 12px;
    background: #e9f4ff;
    border-radius: 4px;
    font-size: 0.9em;
}

.property-sidebar {
    position: sticky;
    top: 20px;
    height: fit-content;
}

.booking-widget {
    background: white;
    border: 2px solid #2c5aa0;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 30px;
}

.booking-widget h3 {
    margin-top: 0;
    color: #2c5aa0;
    text-align: center;
}

.booking-inquiry-form .form-row {
    margin-bottom: 15px;
}

.booking-inquiry-form label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
    color: #333;
}

.booking-inquiry-form input,
.booking-inquiry-form select,
.booking-inquiry-form textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.booking-submit {
    width: 100%;
    background: #2c5aa0;
    color: white;
    padding: 12px;
    border: none;
    border-radius: 4px;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
    transition: background 0.3s;
}

.booking-submit:hover {
    background: #1e3d6f;
}

.property-location,
.property-policies {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.property-location h3,
.property-policies h3 {
    margin-top: 0;
    color: #2c5aa0;
}

.property-policies ul {
    list-style: none;
    padding: 0;
}

.property-policies li {
    padding: 5px 0;
    border-bottom: 1px solid #e9ecef;
}

@media (max-width: 768px) {
    .property-details-grid {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .property-stats {
        grid-template-columns: 1fr;
    }
    
    .amenities-list,
    .features-list {
        grid-template-columns: 1fr;
    }
}
</style>

<?php get_footer(); ?>