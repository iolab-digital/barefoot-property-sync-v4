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

<!-- Hero Images Section -->
<div class="hero-images">
    <?php if (!empty($attachments) && count($attachments) >= 2): ?>
        <div class="hero-img" style="background-image: url('<?php echo wp_get_attachment_image_url($attachments[0]->ID, 'large'); ?>');">
            <img src="<?php echo wp_get_attachment_image_url($attachments[0]->ID, 'large'); ?>" alt="<?php the_title(); ?>">
        </div>
        <div class="hero-img" style="background-image: url('<?php echo wp_get_attachment_image_url($attachments[1]->ID, 'large'); ?>');">
            <img src="<?php echo wp_get_attachment_image_url($attachments[1]->ID, 'large'); ?>" alt="<?php the_title(); ?>">
        </div>
    <?php elseif (!empty($attachments)): ?>
        <div class="hero-img" style="background-image: url('<?php echo wp_get_attachment_image_url($attachments[0]->ID, 'large'); ?>');">
            <img src="<?php echo wp_get_attachment_image_url($attachments[0]->ID, 'large'); ?>" alt="<?php the_title(); ?>">
        </div>
        <div class="hero-img hero-placeholder"></div>
    <?php else: ?>
        <div class="hero-img hero-placeholder"></div>
        <div class="hero-img hero-placeholder"></div>
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
                    <div class="stat-label">Price</div>
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

            <!-- Image Grid -->
            <?php if (!empty($attachments) && count($attachments) > 2): ?>
            <div class="image-grid">
                <?php 
                $grid_images = array_slice($attachments, 2, 4);
                foreach ($grid_images as $img): 
                ?>
                    <div class="grid-img">
                        <img src="<?php echo wp_get_attachment_image_url($img->ID, 'large'); ?>" alt="<?php echo esc_attr($img->post_title); ?>">
                    </div>
                <?php endforeach; ?>
                
                <?php 
                // Fill remaining spots with placeholders if needed
                $remaining = 4 - count($grid_images);
                for ($i = 0; $i < $remaining; $i++): 
                ?>
                    <div class="grid-img grid-placeholder"></div>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
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
                echo esc_html($city) . ' offers an exceptional vacation experience with beautiful surroundings and convenient access to local attractions. This prime location provides the perfect setting for a memorable getaway.';
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
                <div id="form-message" style="display: none; margin-top: 15px; padding: 15px; background: #f0f0f0; font-size: 13px;"></div>
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
            ?>
                <a href="<?php the_permalink(); ?>" class="property-card">
                    <div class="card-image">
                        <?php if (has_post_thumbnail()): ?>
                            <?php the_post_thumbnail('medium'); ?>
                        <?php endif; ?>
                    </div>
                    <div class="card-content">
                        <?php if (is_numeric($related_min_price) && floatval($related_min_price) > 0): ?>
                            <div class="card-price">$<?php echo number_format(floatval($related_min_price)); ?></div>
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
            name: $form.find('[name="name"]').val(),
            email: $form.find('[name="email"]').val(),
            phone: $form.find('[name="phone"]').val(),
            message: $form.find('[name="message"]').val()
        })
        .done(function(response) {
            if (response.success) {
                $message.css('background', '#d4edda').css('color', '#155724').text(response.data.message).show();
                $form[0].reset();
            } else {
                $message.css('background', '#f8d7da').css('color', '#721c24').text(response.data.message).show();
            }
        })
        .fail(function() {
            $message.css('background', '#f8d7da').css('color', '#721c24').text('An error occurred. Please try again.').show();
        })
        .always(function() {
            $button.prop('disabled', false).text('Send Message');
        });
    });
});
</script>

<?php get_footer(); ?>
