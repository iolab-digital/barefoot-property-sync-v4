<?php
/**
 * Archive Property Template
 * Template for displaying property listings
 */

get_header(); ?>

<div id="primary" class="content-area">
    <main id="main" class="site-main">
        
        <header class="page-header">
            <h1 class="page-title">
                <?php
                if (is_tax()) {
                    $term = get_queried_object();
                    echo esc_html($term->name) . ' ' . __('Properties', 'barefoot-properties');
                } else {
                    _e('Vacation Rental Properties', 'barefoot-properties');
                }
                ?>
            </h1>
            
            <?php if (is_tax() && !empty(get_queried_object()->description)) : ?>
            <div class="archive-description">
                <?php echo wpautop(esc_html(get_queried_object()->description)); ?>
            </div>
            <?php endif; ?>
        </header>
        
        <!-- Property Search Form -->
        <div class="property-search-section">
            <?php echo do_shortcode('[barefoot_search]'); ?>
        </div>
        
        <!-- Property Filters -->
        <div class="property-filters">
            <div class="filter-row">
                <select id="sort-properties">
                    <option value=""><?php _e('Sort by', 'barefoot-properties'); ?></option>
                    <option value="price_low"><?php _e('Price: Low to High', 'barefoot-properties'); ?></option>
                    <option value="price_high"><?php _e('Price: High to Low', 'barefoot-properties'); ?></option>
                    <option value="bedrooms"><?php _e('Most Bedrooms', 'barefoot-properties'); ?></option>
                    <option value="newest"><?php _e('Newest First', 'barefoot-properties'); ?></option>
                </select>
                
                <div class="view-toggle">
                    <button class="view-btn active" data-view="grid">📱 <?php _e('Grid', 'barefoot-properties'); ?></button>
                    <button class="view-btn" data-view="list">📋 <?php _e('List', 'barefoot-properties'); ?></button>
                </div>
            </div>
        </div>
        
        <?php if (have_posts()) : ?>
            
            <div class="properties-container">
                <div id="properties-grid" class="property-grid view-grid">
                    
                    <?php while (have_posts()) : the_post(); ?>
                        
                        <div class="property-card" data-price="<?php echo get_post_meta(get_the_ID(), '_barefoot_price', true); ?>" data-bedrooms="<?php echo get_post_meta(get_the_ID(), '_barefoot_bedrooms', true); ?>">
                            
                            <?php if (has_post_thumbnail()) : ?>
                            <div class="property-image">
                                <a href="<?php the_permalink(); ?>">
                                    <?php the_post_thumbnail('medium'); ?>
                                </a>
                                
                                <?php
                                $price = get_post_meta(get_the_ID(), '_barefoot_price', true);
                                if ($price) :
                                ?>
                                <div class="property-price-badge">
                                    $<?php echo number_format($price); ?>/night
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                            
                            <div class="property-card-content">
                                <h2 class="property-title">
                                    <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                </h2>
                                
                                <?php
                                $bedrooms = get_post_meta(get_the_ID(), '_barefoot_bedrooms', true);
                                $bathrooms = get_post_meta(get_the_ID(), '_barefoot_bathrooms', true);
                                $max_guests = get_post_meta(get_the_ID(), '_barefoot_max_guests', true);
                                $city = get_post_meta(get_the_ID(), '_barefoot_city', true);
                                $state = get_post_meta(get_the_ID(), '_barefoot_state', true);
                                ?>
                                
                                <?php if ($city || $state) : ?>
                                <div class="property-location">
                                    📍 <?php echo esc_html($city); ?><?php if ($city && $state) echo ', '; ?><?php echo esc_html($state); ?>
                                </div>
                                <?php endif; ?>
                                
                                <div class="property-details">
                                    <?php if ($bedrooms) : ?>
                                    <span class="detail">🛏️ <?php echo esc_html($bedrooms); ?> bed</span>
                                    <?php endif; ?>
                                    
                                    <?php if ($bathrooms) : ?>
                                    <span class="detail">🛁 <?php echo esc_html($bathrooms); ?> bath</span>
                                    <?php endif; ?>
                                    
                                    <?php if ($max_guests) : ?>
                                    <span class="detail">👥 <?php echo esc_html($max_guests); ?> guests</span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="property-excerpt">
                                    <?php echo wp_trim_words(get_the_excerpt(), 20); ?>
                                </div>
                                
                                <?php
                                $amenities = get_the_terms(get_the_ID(), 'amenity');
                                if ($amenities && !is_wp_error($amenities)) :
                                ?>
                                <div class="property-amenities-preview">
                                    <?php 
                                    $amenity_names = array_slice(wp_list_pluck($amenities, 'name'), 0, 3);
                                    echo implode(' • ', $amenity_names);
                                    if (count($amenities) > 3) {
                                        echo ' • +' . (count($amenities) - 3) . ' more';
                                    }
                                    ?>
                                </div>
                                <?php endif; ?>
                                
                                <div class="property-actions">
                                    <a href="<?php the_permalink(); ?>" class="view-property-btn">
                                        <?php _e('View Details', 'barefoot-properties'); ?>
                                    </a>
                                </div>
                                
                            </div>
                            
                        </div>
                        
                    <?php endwhile; ?>
                    
                </div>
            </div>
            
            <?php
            // Pagination
            the_posts_pagination(array(
                'prev_text' => __('« Previous', 'barefoot-properties'),
                'next_text' => __('Next »', 'barefoot-properties'),
                'before_page_number' => '<span class="meta-nav screen-reader-text">' . __('Page', 'barefoot-properties') . ' </span>',
            ));
            ?>
            
        <?php else : ?>
            
            <div class="no-properties-found">
                <h2><?php _e('No Properties Found', 'barefoot-properties'); ?></h2>
                <p><?php _e('Sorry, no properties match your search criteria. Please try adjusting your filters.', 'barefoot-properties'); ?></p>
                <a href="<?php echo get_post_type_archive_link('barefoot_property'); ?>" class="button">
                    <?php _e('View All Properties', 'barefoot-properties'); ?>
                </a>
            </div>
            
        <?php endif; ?>
        
    </main>
</div>

<style>
.page-header {
    text-align: center;
    margin-bottom: 40px;
    padding-bottom: 20px;
    border-bottom: 2px solid #e9ecef;
}

.page-title {
    font-size: 2.5em;
    color: #2c5aa0;
    margin-bottom: 10px;
}

.archive-description {
    font-size: 1.1em;
    color: #666;
    max-width: 600px;
    margin: 0 auto;
}

.property-search-section {
    margin-bottom: 30px;
}

.property-filters {
    margin-bottom: 30px;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
}

.filter-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
}

#sort-properties {
    padding: 10px 15px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.view-toggle {
    display: flex;
    gap: 5px;
}

.view-btn {
    padding: 10px 15px;
    border: 1px solid #ddd;
    background: white;
    cursor: pointer;
    font-size: 14px;
    border-radius: 4px;
    transition: all 0.3s;
}

.view-btn.active {
    background: #2c5aa0;
    color: white;
    border-color: #2c5aa0;
}

.properties-container {
    margin-bottom: 40px;
}

.property-grid {
    display: grid;
    gap: 25px;
}

.view-grid {
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
}

.view-list {
    grid-template-columns: 1fr;
}

.view-list .property-card {
    display: grid;
    grid-template-columns: 300px 1fr;
    gap: 20px;
    align-items: start;
}

.property-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s, box-shadow 0.3s;
}

.property-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.property-image {
    position: relative;
    overflow: hidden;
}

.property-image img {
    width: 100%;
    height: 250px;
    object-fit: cover;
    transition: transform 0.3s;
}

.property-card:hover .property-image img {
    transform: scale(1.05);
}

.property-price-badge {
    position: absolute;
    top: 15px;
    right: 15px;
    background: rgba(44, 90, 160, 0.9);
    color: white;
    padding: 8px 12px;
    border-radius: 20px;
    font-weight: bold;
    font-size: 14px;
}

.property-card-content {
    padding: 20px;
}

.property-title {
    margin: 0 0 10px;
    font-size: 1.3em;
    line-height: 1.3;
}

.property-title a {
    color: #333;
    text-decoration: none;
    transition: color 0.3s;
}

.property-title a:hover {
    color: #2c5aa0;
}

.property-location {
    color: #666;
    font-size: 0.9em;
    margin-bottom: 15px;
}

.property-details {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 15px;
}

.detail {
    font-size: 0.9em;
    color: #555;
}

.property-excerpt {
    color: #666;
    font-size: 0.95em;
    line-height: 1.5;
    margin-bottom: 15px;
}

.property-amenities-preview {
    font-size: 0.85em;
    color: #888;
    margin-bottom: 20px;
    padding: 8px 0;
    border-top: 1px solid #eee;
    border-bottom: 1px solid #eee;
}

.property-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.view-property-btn {
    background: #2c5aa0;
    color: white;
    padding: 10px 20px;
    text-decoration: none;
    border-radius: 25px;
    font-weight: bold;
    font-size: 14px;
    transition: background 0.3s;
    display: inline-block;
}

.view-property-btn:hover {
    background: #1e3d6f;
    color: white;
}

.no-properties-found {
    text-align: center;
    padding: 60px 20px;
    background: #f8f9fa;
    border-radius: 12px;
}

.no-properties-found h2 {
    color: #2c5aa0;
    margin-bottom: 15px;
}

.no-properties-found .button {
    background: #2c5aa0;
    color: white;
    padding: 12px 24px;
    text-decoration: none;
    border-radius: 6px;
    display: inline-block;
    margin-top: 20px;
    transition: background 0.3s;
}

.no-properties-found .button:hover {
    background: #1e3d6f;
}

/* Pagination */
.pagination {
    display: flex;
    justify-content: center;
    margin-top: 40px;
}

.page-numbers {
    display: inline-block;
    padding: 10px 15px;
    margin: 0 5px;
    background: white;
    border: 1px solid #ddd;
    color: #333;
    text-decoration: none;
    border-radius: 4px;
    transition: all 0.3s;
}

.page-numbers:hover,
.page-numbers.current {
    background: #2c5aa0;
    color: white;
    border-color: #2c5aa0;
}

/* Mobile Responsiveness */
@media (max-width: 768px) {
    .view-grid {
        grid-template-columns: 1fr;
    }
    
    .view-list .property-card {
        grid-template-columns: 1fr;
    }
    
    .filter-row {
        flex-direction: column;
        align-items: stretch;
    }
    
    .view-toggle {
        justify-content: center;
    }
    
    .property-details {
        flex-direction: column;
        gap: 8px;
    }
    
    .page-title {
        font-size: 2em;
    }
}
</style>

<script>
// View toggle functionality
document.addEventListener('DOMContentLoaded', function() {
    const viewButtons = document.querySelectorAll('.view-btn');
    const propertiesGrid = document.getElementById('properties-grid');
    
    viewButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Remove active class from all buttons
            viewButtons.forEach(btn => btn.classList.remove('active'));
            
            // Add active class to clicked button
            this.classList.add('active');
            
            // Change view
            const view = this.dataset.view;
            propertiesGrid.className = `property-grid view-${view}`;
        });
    });
    
    // Sort functionality
    const sortSelect = document.getElementById('sort-properties');
    if (sortSelect) {
        sortSelect.addEventListener('change', function() {
            const sortBy = this.value;
            const cards = Array.from(propertiesGrid.querySelectorAll('.property-card'));
            
            cards.sort((a, b) => {
                switch(sortBy) {
                    case 'price_low':
                        return parseInt(a.dataset.price || 0) - parseInt(b.dataset.price || 0);
                    case 'price_high':
                        return parseInt(b.dataset.price || 0) - parseInt(a.dataset.price || 0);
                    case 'bedrooms':
                        return parseInt(b.dataset.bedrooms || 0) - parseInt(a.dataset.bedrooms || 0);
                    default:
                        return 0;
                }
            });
            
            // Re-append sorted cards
            cards.forEach(card => propertiesGrid.appendChild(card));
        });
    }
});
</script>

<?php get_footer(); ?>