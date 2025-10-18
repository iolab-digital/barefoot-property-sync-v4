/**
 * Barefoot Properties Frontend JavaScript
 * 
 * @package BarefootPropertyListings
 * @since 1.1.0
 */

(function($) {
    'use strict';
    
    // DOM Ready
    $(document).ready(function() {
        initPropertySearch();
        initPropertyFilters();
        initPropertyModals();
        initPropertyGallery();
        initPropertyMaps();
    });
    
    /**
     * Initialize property search functionality
     */
    function initPropertySearch() {
        // AJAX property search
        $('.barefoot-search-form').on('submit', function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $container = $('.barefoot-properties-grid');
            
            // Show loading
            $container.html('<div class="barefoot-loading">Searching properties...</div>');
            
            // Get form data
            var searchData = {
                action: 'barefoot_property_search',
                nonce: barefoot_ajax.nonce,
                search: $form.find('[name="property_search"]').val(),
                property_type: $form.find('[name="property_type"]').val(),
                location: $form.find('[name="location"]').val(),
                amenity: $form.find('[name="amenity"]').val(),
                min_price: $form.find('[name="min_price"]').val(),
                max_price: $form.find('[name="max_price"]').val(),
                min_occupancy: $form.find('[name="min_occupancy"]').val()
            };
            
            // AJAX request
            $.post(barefoot_ajax.ajax_url, searchData, function(response) {
                if (response.success) {
                    displaySearchResults(response.data.properties, $container);
                } else {
                    $container.html('<p class="barefoot-error">Search failed. Please try again.</p>');
                }
            }).fail(function() {
                $container.html('<p class="barefoot-error">Search request failed. Please try again.</p>');
            });
        });
    }
    
    /**
     * Initialize property modals
     */
    function initPropertyModals() {
        // Property inquiry modal
        $('.contact-button, .quick-inquiry-button').on('click', function(e) {
            e.preventDefault();
            
            var propertyId = $(this).data('property');
            var propertyTitle = $(this).data('title') || $(this).closest('.property-card').find('.property-title').text();
            
            var modalId = $(this).hasClass('quick-inquiry-button') ? '#quick-inquiry-modal' : '#property-inquiry-modal';
            var $modal = $(modalId);
            
            $modal.find('[name="property_id"]').val(propertyId);
            if ($modal.find('.modal-property-title').length) {
                $modal.find('.modal-property-title').text(propertyTitle);
            }
            
            $modal.show().addClass('fadeIn');
        });
        
        // Close modal
        $('.modal-close, .cancel-button').on('click', function() {
            $(this).closest('.modal').hide().removeClass('fadeIn');
        });
    }
    
    function initPropertyFilters() {
        // Placeholder for filters
    }
    
    function initPropertyGallery() {
        // Placeholder for gallery
    }
    
    function initPropertyMaps() {
        // Placeholder for maps
    }
    
})(jQuery);