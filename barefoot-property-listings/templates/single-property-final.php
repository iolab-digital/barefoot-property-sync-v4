<?php
/**
 * Single Property Template - Luxury Minimalist Design
 * 
 * @package BarefootPropertyListings
 * @since 1.3.0
 */

// Get property meta data
$property_id = get_post_meta(get_the_ID(), '_barefoot_property_id', true);
$city = get_post_meta(get_the_ID(), '_barefoot_city', true);
$state = get_post_meta(get_the_ID(), '_barefoot_state', true);
$zip = get_post_meta(get_the_ID(), '_barefoot_zip', true);
$address = get_post_meta(get_the_ID(), '_barefoot_prop_address', true);

// Pricing
$min_price = get_post_meta(get_the_ID(), '_barefoot_min_price', true);
$max_price = get_post_meta(get_the_ID(), '_barefoot_max_price', true);
$min_price_numeric = is_numeric($min_price) ? floatval($min_price) : 0;

// Property specs
$occupancy = get_post_meta(get_the_ID(), '_barefoot_occupancy', true);
$bedrooms = get_post_meta(get_the_ID(), '_barefoot_bedrooms', true);
$bathrooms = get_post_meta(get_the_ID(), '_barefoot_bathrooms', true);
$sleeps_beds = get_post_meta(get_the_ID(), '_barefoot_sleeps_beds', true);

// Get all images
$attachments = get_posts(array(
    'post_type' => 'attachment',
    'post_mime_type' => 'image',
    'post_parent' => get_the_ID(),
    'posts_per_page' => -1,
    'orderby' => 'menu_order',
    'order' => 'ASC'
));

get_header(); 
?>

<style>
/* Property Page Styles */
.hero-image-single {
    height: 600px;
    position: relative;
    overflow: hidden;
    background-size: cover;
    background-position: center;
}

.hero-image-single img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.hero-placeholder {
    background: linear-gradient(to bottom, #e8eef2 0%, #d4dfe8 100%);
}

.breadcrumb {
    padding: 15px 60px;
    font-size: 11px;
    color: #666;
    border-bottom: 1px solid #e5e5e5;
}

.breadcrumb a {
    color: #666;
    text-decoration: none;
}

.breadcrumb a:hover {
    color: #0a3d5c;
}

.bf-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 40px 60px;
}

.two-col {
    display: grid;
    grid-template-columns: 1.5fr 1fr;
    gap: 60px;
    margin-bottom: 60px;
}

.property-header {
    margin-bottom: 30px;
}

.property-header h1 {
    font-size: 28px;
    font-weight: 400;
    margin-bottom: 8px;
    letter-spacing: 0.5px;
    color: #333;
}

.property-address {
    font-size: 13px;
    color: #666;
}

.stats-bar {
    display: flex;
    gap: 40px;
    padding: 25px 0;
    border-top: 1px solid #e5e5e5;
    border-bottom: 1px solid #e5e5e5;
    margin-bottom: 40px;
    flex-wrap: wrap;
}

.stat {
    display: flex;
    flex-direction: column;
}

.stat-label {
    font-size: 10px;
    color: #999;
    letter-spacing: 1px;
    text-transform: uppercase;
    margin-bottom: 5px;
}

.stat-value {
    font-size: 20px;
    font-weight: 300;
    color: #333;
}

.description {
    font-size: 14px;
    line-height: 1.9;
    color: #555;
    margin-bottom: 50px;
}

.description p {
    margin-bottom: 20px;
}

/* Gallery Scroller */
.gallery-section {
    margin: 60px 0;
    position: relative;
}

.gallery-container {
    position: relative;
    overflow: hidden;
}

.gallery-scroller {
    display: flex;
    gap: 20px;
    overflow-x: auto;
    scroll-behavior: smooth;
    scrollbar-width: none;
    -ms-overflow-style: none;
    padding: 10px 0;
}

.gallery-scroller::-webkit-scrollbar {
    display: none;
}

.gallery-item {
    flex: 0 0 calc(33.333% - 14px);
    height: 350px;
    position: relative;
    overflow: hidden;
}

.gallery-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.gallery-placeholder {
    background: linear-gradient(135deg, #f0f4f7 0%, #e1e8ed 100%);
}

.gallery-arrow {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background: white;
    border: none;
    width: 50px;
    height: 50px;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    transition: all 0.3s;
    z-index: 10;
}

.gallery-arrow:hover {
    background: #f5f5f5;
    box-shadow: 0 4px 15px rgba(0,0,0,0.15);
}

.gallery-arrow.left {
    left: 20px;
}

.gallery-arrow.right {
    right: 20px;
}

.gallery-arrow svg {
    width: 24px;
    height: 24px;
    stroke: #333;
    stroke-width: 2;
    fill: none;
}

.sidebar {
    position: sticky;
    top: 20px;
    height: fit-content;
}

.agent-card {
    background: #0a3d5c;
    color: white;
    padding: 30px;
    margin-bottom: 20px;
}

.agent-photo {
    width: 70px;
    height: 70px;
    background: rgba(255,255,255,0.2);
    border-radius: 50%;
    margin-bottom: 15px;
}

.agent-name {
    font-size: 16px;
    margin-bottom: 5px;
}

.agent-title {
    font-size: 12px;
    opacity: 0.8;
}

.sidebar-buttons {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.btn {
    padding: 15px;
    text-align: center;
    font-size: 11px;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    cursor: pointer;
    transition: all 0.3s;
    border: none;
    width: 100%;
}

.btn-primary {
    background: white;
    border: 1px solid #ddd;
    color: #333;
}

.btn-primary:hover {
    background: #f5f5f5;
}

.btn-secondary {
    background: transparent;
    border: 1px solid #ddd;
    color: #333;
}

.btn-secondary:hover {
    background: #f9f9f9;
}

.map-section {
    height: 400px;
    background: #e5e5e5;
    margin: 60px 0;
    position: relative;
}

#property-map {
    width: 100%;
    height: 100%;
}

.map-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #888;
    font-size: 14px;
    letter-spacing: 3px;
    background: linear-gradient(to bottom, #c8e5d4 0%, #a8d5c4 100%);
}

.about-area {
    background: #0a3d5c;
    color: white;
    padding: 80px 60px;
    text-align: center;
    margin: 60px -60px;
}

.about-area h2 {
    font-size: 32px;
    font-weight: 300;
    margin-bottom: 25px;
    letter-spacing: 1px;
}

.about-area p {
    max-width: 800px;
    margin: 0 auto;
    line-height: 1.9;
    opacity: 0.9;
    font-size: 14px;
}

.property-details {
    margin: 80px 0;
}

.section-title {
    font-size: 24px;
    font-weight: 300;
    margin-bottom: 40px;
    letter-spacing: 0.5px;
    color: #333;
}

.details-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 40px 60px;
}

.detail-item {
    border-bottom: 1px solid #f0f0f0;
    padding-bottom: 15px;
}

.detail-label {
    font-size: 10px;
    color: #999;
    letter-spacing: 1px;
    text-transform: uppercase;
    margin-bottom: 8px;
}

.detail-value {
    font-size: 14px;
    color: #333;
}

.detail-value a {
    color: #0a3d5c;
    text-decoration: none;
}

.detail-value a:hover {
    text-decoration: underline;
}

.agent-section {
    background: #fafafa;
    padding: 60px;
    margin: 60px -60px;
    display: grid;
    grid-template-columns: 250px 1fr;
    gap: 60px;
}

.agent-photo-large {
    width: 250px;
    height: 300px;
    background: linear-gradient(135deg, #e8eef2 0%, #d4dfe8 100%);
}

.agent-info h3 {
    font-size: 22px;
    font-weight: 400;
    margin-bottom: 10px;
    color: #333;
}

.contact-details {
    font-size: 13px;
    color: #666;
    margin: 20px 0 30px;
}

.contact-details div {
    margin-bottom: 5px;
}

.contact-form {
    background: white;
    padding: 30px;
    margin-top: 30px;
}

.form-field {
    width: 100%;
    padding: 15px;
    background: #f5f5f5;
    margin-bottom: 12px;
    font-size: 13px;
    color: #333;
    border: 1px solid #e5e5e5;
    font-family: inherit;
}

.form-field:focus {
    outline: none;
    border-color: #0a3d5c;
    background: white;
}

.form-field.large {
    min-height: 120px;
    resize: vertical;
}

.submit-btn {
    background: #0a3d5c;
    color: white;
    padding: 15px 40px;
    font-size: 11px;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    margin-top: 20px;
    border: none;
    cursor: pointer;
    transition: all 0.3s;
}

.submit-btn:hover {
    background: #083046;
}

.similar-properties {
    background: #0a3d5c;
    padding: 80px 60px;
    margin: 60px -60px;
}

.similar-properties h2 {
    color: white;
    font-size: 28px;
    font-weight: 300;
    margin-bottom: 40px;
    letter-spacing: 0.5px;
}

.properties-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 30px;
}

.property-card {
    background: white;
    overflow: hidden;
    text-decoration: none;
    color: inherit;
    display: block;
    transition: transform 0.3s;
}

.property-card:hover {
    transform: translateY(-5px);
}

.card-image {
    height: 200px;
    background: linear-gradient(135deg, #e8eef2 0%, #d4dfe8 100%);
    overflow: hidden;
}

.card-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.card-content {
    padding: 20px;
}

.card-price {
    font-size: 18px;
    font-weight: 400;
    margin-bottom: 8px;
    color: #333;
}

.card-specs {
    font-size: 12px;
    color: #666;
    margin-bottom: 8px;
}

.card-title {
    font-size: 14px;
    font-weight: 400;
    margin-bottom: 5px;
    color: #333;
}

.card-address {
    font-size: 11px;
    color: #999;
}

/* Responsive */
@media (max-width: 1024px) {
    .two-col,
    .agent-section {
        grid-template-columns: 1fr;
        gap: 40px;
    }
    
    .sidebar {
        position: relative;
        top: 0;
    }
    
    .properties-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .details-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .gallery-item {
        flex: 0 0 calc(50% - 10px);
    }
}

@media (max-width: 768px) {
    .hero-image-single {
        height: 400px;
    }
    
    .bf-container {
        padding: 30px 20px;
    }
    
    .breadcrumb {
        padding: 15px 20px;
    }
    
    .about-area,
    .similar-properties,
    .agent-section {
        margin: 40px -20px;
        padding: 50px 20px;
    }
    
    .properties-grid,
    .details-grid {
        grid-template-columns: 1fr;
    }
    
    .stats-bar {
        gap: 20px;
    }
    
    .gallery-item {
        flex: 0 0 calc(100% - 20px);
    }
    
    .gallery-arrow {
        width: 40px;
        height: 40px;
    }
    
    .gallery-arrow.left {
        left: 10px;
    }
    
    .gallery-arrow.right {
        right: 10px;
    }
}
</style>

<!-- Hero Image Section -->
<div class="hero-image-single <?php echo empty($attachments) ? 'hero-placeholder' : ''; ?>" 
     <?php if (!empty($attachments)): ?>
     style="background-image: url('<?php echo wp_get_attachment_image_url($attachments[0]->ID, 'full'); ?>');"
     <?php endif; ?>>
    <?php if (!empty($attachments)): ?>
        <img src="<?php echo wp_get_attachment_image_url($attachments[0]->ID, 'full'); ?>" alt="<?php the_title(); ?>">
    <?php endif; ?>
</div>

<!-- Breadcrumb -->
<div class="breadcrumb">
    <a href="<?php echo home_url(); ?>">Home</a> / 
    <a href="<?php echo get_post_type_archive_link('barefoot_property'); ?>">Properties</a> / 
    <?php if ($city): ?>
        <?php echo esc_html($city); ?> / 
    <?php endif; ?>
    <?php the_title(); ?>
</div>

<!-- Main Container -->
<div class="bf-container">
    <div class="two-col">
        <!-- Left Column -->
        <div class="left-col">
            <!-- Property Header -->
            <div class="property-header">
                <h1><?php the_title(); ?></h1>
                <div class="property-address">
                    <?php 
                    $full_address = array();
                    if ($address) $full_address[] = $address;
                    if ($city) $full_address[] = $city;
                    if ($state) $full_address[] = $state;
                    if ($zip) $full_address[] = $zip;
                    echo esc_html(implode(', ', $full_address));
                    ?>
                </div>
            </div>

            <!-- Stats Bar -->
            <div class="stats-bar">
                <?php if ($min_price_numeric > 0): ?>
                <div class="stat">
                    <div class="stat-label">From</div>
                    <div class="stat-value">$<?php echo number_format($min_price_numeric); ?></div>
                </div>
                <?php endif; ?>
                
                <?php if ($bedrooms): ?>
                <div class="stat">
                    <div class="stat-label">Bedrooms</div>
                    <div class="stat-value"><?php echo esc_html($bedrooms); ?></div>
                </div>
                <?php endif; ?>
                
                <?php if ($bathrooms): ?>
                <div class="stat">
                    <div class="stat-label">Bathrooms</div>
                    <div class="stat-value"><?php echo esc_html($bathrooms); ?></div>
                </div>
                <?php endif; ?>
                
                <?php if ($occupancy): ?>
                <div class="stat">
                    <div class="stat-label">Sleeps</div>
                    <div class="stat-value"><?php echo esc_html($occupancy); ?></div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Description -->
            <div class="description">
                <?php the_content(); ?>
            </div>
        </div>

        <!-- Right Column / Sidebar -->
        <div class="sidebar">
            <div class="agent-card">
                <div class="agent-photo"></div>
                <div class="agent-name">Property Manager</div>
                <div class="agent-title">Barefoot Vacation Rentals</div>
            </div>
            <div class="sidebar-buttons">
                <button class="btn btn-primary" onclick="document.getElementById('contact-form-section').scrollIntoView({behavior: 'smooth'});">
                    Request Information
                </button>
                <button class="btn btn-secondary" onclick="document.getElementById('contact-form-section').scrollIntoView({behavior: 'smooth'});">
                    Check Availability
                </button>
            </div>
        </div>
    </div>

    <!-- Gallery Scroller -->
    <?php if (!empty($attachments) && count($attachments) > 1): ?>
    <div class="gallery-section">
        <button class="gallery-arrow left" onclick="scrollGallery('left')">
            <svg viewBox="0 0 24 24">
                <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
        </button>
        <div class="gallery-container">
            <div class="gallery-scroller" id="galleryScroller">
                <?php 
                // Start from second image since first is hero
                $gallery_images = array_slice($attachments, 1);
                foreach ($gallery_images as $img): 
                ?>
                    <div class="gallery-item">
                        <img src="<?php echo wp_get_attachment_image_url($img->ID, 'large'); ?>" alt="<?php echo esc_attr($img->post_title); ?>">
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <button class="gallery-arrow right" onclick="scrollGallery('right')">
            <svg viewBox="0 0 24 24">
                <polyline points="9 18 15 12 9 6"></polyline>
            </svg>
        </button>
    </div>
    <?php endif; ?>

    <!-- Map Section -->
    <?php 
    $latitude = get_post_meta(get_the_ID(), '_barefoot_latitude', true);
    $longitude = get_post_meta(get_the_ID(), '_barefoot_longitude', true);
    ?>
    <div class="map-section">
        <?php if ($latitude && $longitude): ?>
            <div id="property-map" data-lat="<?php echo esc_attr($latitude); ?>" data-lng="<?php echo esc_attr($longitude); ?>"></div>
        <?php else: ?>
            <div class="map-placeholder">MAP</div>
        <?php endif; ?>
    </div>

    <!-- About Area -->
    <div class="about-area">
        <h2>About <?php echo esc_html($city ?: 'The Area'); ?></h2>
        <p>
            <?php 
            $ext_description = get_post_meta(get_the_ID(), '_barefoot_ext_description', true);
            if ($ext_description) {
                echo wp_kses_post($ext_description);
            } else {
                echo esc_html($city ?: 'This location') . ' offers an exceptional vacation experience with beautiful surroundings and convenient access to local attractions. This prime location provides the perfect setting for a memorable getaway.';
            }
            ?>
        </p>
    </div>

    <!-- Property Details -->
    <div class="property-details">
        <h2 class="section-title">Property Details</h2>
        <div class="details-grid">
            <?php
            // Collect all property details
            $details = array();
            
            $property_type = get_post_meta(get_the_ID(), '_barefoot_property_type', true);
            if ($property_type) $details['Property Type'] = $property_type;
            
            $unit_type = get_post_meta(get_the_ID(), '_barefoot_unit_type', true);
            if ($unit_type) $details['Unit Type'] = $unit_type;
            
            if ($occupancy) $details['Maximum Occupancy'] = $occupancy . ' Guests';
            if ($bedrooms) $details['Bedrooms'] = $bedrooms;
            if ($bathrooms) $details['Bathrooms'] = $bathrooms;
            if ($sleeps_beds) $details['Sleeping Arrangements'] = $sleeps_beds;
            
            $number_floors = get_post_meta(get_the_ID(), '_barefoot_number_floors', true);
            if ($number_floors) $details['Number of Floors'] = $number_floors;
            
            // Get amenities from taxonomy
            $amenities = get_the_terms(get_the_ID(), 'amenity');
            if ($amenities && !is_wp_error($amenities)) {
                $amenity_names = array();
                foreach ($amenities as $amenity) {
                    $amenity_names[] = $amenity->name;
                }
                if (!empty($amenity_names)) {
                    $details['Amenities'] = implode(', ', $amenity_names);
                }
            }
            
            $video_link = get_post_meta(get_the_ID(), '_barefoot_video_link', true);
            if ($video_link) $details['Video Tour'] = '<a href="' . esc_url($video_link) . '" target="_blank">View Video</a>';
            
            // Display details
            foreach ($details as $label => $value):
            ?>
                <div class="detail-item">
                    <div class="detail-label"><?php echo esc_html($label); ?></div>
                    <div class="detail-value"><?php echo wp_kses_post($value); ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Agent Section / Contact Form -->
    <div class="agent-section" id="contact-form-section">
        <div class="agent-photo-large"></div>
        <div class="agent-info">
            <h3>Contact Our Team</h3>
            <div style="font-size: 13px; color: #999; margin-bottom: 5px;">Barefoot Vacation Rentals</div>
            <div class="contact-details">
                <?php 
                $contact_phone = get_option('barefoot_contact_phone');
                $contact_email = get_option('barefoot_contact_email');
                if ($contact_phone): ?>
                    <div>Phone: <?php echo esc_html($contact_phone); ?></div>
                <?php endif; ?>
                <?php if ($contact_email): ?>
                    <div>Email: <?php echo esc_html($contact_email); ?></div>
                <?php endif; ?>
            </div>
            <div style="font-size: 13px; line-height: 1.8; color: #666;">
                Our experienced team is ready to help you plan your perfect vacation. Contact us for availability, rates, and any questions you may have about this property.
            </div>
            <div class="contact-form">
                <form id="property-inquiry-form" method="post">
                    <input type="hidden" name="property_id" value="<?php echo esc_attr($property_id); ?>">
                    <input type="hidden" name="property_title" value="<?php echo esc_attr(get_the_title()); ?>">
                    
                    <input type="text" name="name" placeholder="Name *" class="form-field" required>
                    <input type="email" name="email" placeholder="Email *" class="form-field" required>
                    <input type="tel" name="phone" placeholder="Phone" class="form-field">
                    <textarea name="message" placeholder="Message *" class="form-field large" required></textarea>
                    <button type="submit" class="submit-btn">Send Message</button>
                </form>
                <div id="form-message" style="display: none; margin-top: 15px; padding: 15px; font-size: 13px; border-radius: 4px;"></div>
            </div>
        </div>
    </div>

    <!-- Similar Properties -->
    <?php
    $location_terms = get_the_terms(get_the_ID(), 'location');
    $property_type_terms = get_the_terms(get_the_ID(), 'property_type');
    
    $related_args = array(
        'post_type' => 'barefoot_property',
        'posts_per_page' => 3,
        'post__not_in' => array(get_the_ID()),
        'orderby' => 'rand'
    );
    
    $tax_query = array('relation' => 'OR');
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
    <div class="similar-properties">
        <h2>Continue Your Search</h2>
        <div class="properties-grid">
            <?php while ($related_properties->have_posts()): $related_properties->the_post(); 
                $related_min_price = get_post_meta(get_the_ID(), '_barefoot_min_price', true);
                $related_city = get_post_meta(get_the_ID(), '_barefoot_city', true);
                $related_state = get_post_meta(get_the_ID(), '_barefoot_state', true);
                $related_beds = get_post_meta(get_the_ID(), '_barefoot_bedrooms', true);
                $related_baths = get_post_meta(get_the_ID(), '_barefoot_bathrooms', true);
                $related_sleeps = get_post_meta(get_the_ID(), '_barefoot_occupancy', true);
                
                $related_attachments = get_posts(array(
                    'post_type' => 'attachment',
                    'post_mime_type' => 'image',
                    'post_parent' => get_the_ID(),
                    'posts_per_page' => 1,
                    'orderby' => 'menu_order',
                    'order' => 'ASC'
                ));
            ?>
                <a href="<?php the_permalink(); ?>" class="property-card">
                    <div class="card-image">
                        <?php if (!empty($related_attachments)): ?>
                            <img src="<?php echo wp_get_attachment_image_url($related_attachments[0]->ID, 'medium'); ?>" alt="<?php the_title(); ?>">
                        <?php elseif (has_post_thumbnail()): ?>
                            <?php the_post_thumbnail('medium'); ?>
                        <?php endif; ?>
                    </div>
                    <div class="card-content">
                        <?php if (is_numeric($related_min_price) && floatval($related_min_price) > 0): ?>
                            <div class="card-price">From $<?php echo number_format(floatval($related_min_price)); ?></div>
                        <?php endif; ?>
                        <div class="card-specs">
                            <?php 
                            $specs = array();
                            if ($related_beds) $specs[] = $related_beds . ' Beds';
                            if ($related_baths) $specs[] = $related_baths . ' Baths';
                            if ($related_sleeps) $specs[] = 'Sleeps ' . $related_sleeps;
                            echo esc_html(implode(' | ', $specs));
                            ?>
                        </div>
                        <div class="card-title"><?php the_title(); ?></div>
                        <div class="card-address">
                            <?php echo esc_html($related_city . ($related_city && $related_state ? ', ' : '') . $related_state); ?>
                        </div>
                    </div>
                </a>
            <?php endwhile; ?>
        </div>
    </div>
    <?php 
    wp_reset_postdata();
    endif; 
    ?>
</div>

<script>
// Gallery scroller function
function scrollGallery(direction) {
    const scroller = document.getElementById('galleryScroller');
    const scrollAmount = scroller.offsetWidth * 0.8;
    
    if (direction === 'left') {
        scroller.scrollBy({ left: -scrollAmount, behavior: 'smooth' });
    } else {
        scroller.scrollBy({ left: scrollAmount, behavior: 'smooth' });
    }
}

jQuery(document).ready(function($) {
    // Handle form submission
    $('#property-inquiry-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $message = $('#form-message');
        var $button = $form.find('.submit-btn');
        
        $button.prop('disabled', true).text('Sending...');
        $message.hide();
        
        $.post('<?php echo admin_url('admin-ajax.php'); ?>', {
            action: 'barefoot_property_inquiry',
            nonce: '<?php echo wp_create_nonce('barefoot_nonce'); ?>',
            property_id: $form.find('[name="property_id"]').val(),
            property_title: $form.find('[name="property_title"]').val(),
            name: $form.find('[name="name"]').val(),
            email: $form.find('[name="email"]').val(),
            phone: $form.find('[name="phone"]').val(),
            message: $form.find('[name="message"]').val()
        })
        .done(function(response) {
            if (response.success) {
                $message.css({
                    'background': '#d4edda',
                    'color': '#155724',
                    'border': '1px solid #c3e6cb'
                }).text(response.data.message).show();
                $form[0].reset();
            } else {
                $message.css({
                    'background': '#f8d7da',
                    'color': '#721c24',
                    'border': '1px solid #f5c6cb'
                }).text(response.data.message || 'An error occurred. Please try again.').show();
            }
        })
        .fail(function() {
            $message.css({
                'background': '#f8d7da',
                'color': '#721c24',
                'border': '1px solid #f5c6cb'
            }).text('An error occurred. Please try again.').show();
        })
        .always(function() {
            $button.prop('disabled', false).text('Send Message');
        });
    });
});
</script>

<?php get_footer(); ?>
