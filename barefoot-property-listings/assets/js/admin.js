/**
 * Barefoot Properties Admin JavaScript
 * 
 * @package BarefootPropertyListings
 * @since 1.1.0
 */

(function($) {
    'use strict';
    
    // DOM Ready
    $(document).ready(function() {
        initSyncControls();
        initTestControls();
    });
    
    /**
     * Initialize sync controls
     */
    function initSyncControls() {
        // Sync all properties
        $('#sync-all-properties').on('click', function() {
            var $button = $(this);
            var originalText = $button.text();
            
            $button.text('Syncing...').prop('disabled', true);
            
            $.post(ajaxurl, {
                action: 'barefoot_sync_properties',
                nonce: barefoot_ajax.nonce
            }, function(response) {
                if (response.success) {
                    showResults({
                        success: true,
                        message: response.data.message,
                        count: response.data.count
                    });
                } else {
                    showResults({
                        success: false,
                        message: response.data.message || 'Sync failed'
                    });
                }
            }).always(function() {
                $button.text(originalText).prop('disabled', false);
            });
        });
        
        // Test API connection
        $('#test-api-connection').on('click', function() {
            var $button = $(this);
            var originalText = $button.text();
            
            $button.text('Testing...').prop('disabled', true);
            
            $.post(ajaxurl, {
                action: 'barefoot_test_connection',
                nonce: barefoot_ajax.nonce
            }, function(response) {
                if (response.success) {
                    showResults({
                        success: true,
                        message: 'Connection successful: ' + response.data.message
                    });
                } else {
                    showResults({
                        success: false,
                        message: 'Connection failed: ' + response.data.message
                    });
                }
            }).always(function() {
                $button.text(originalText).prop('disabled', false);
            });
        });
    }
    
    function initTestControls() {
        // Placeholder for test controls
    }
    
    function showResults(data) {
        var $results = $('.sync-results');
        if ($results.length) {
            var $content = $results.find('.results-content');
            $content.removeClass('success error').addClass(data.success ? 'success' : 'error');
            $content.html('<p><strong>' + data.message + '</strong></p>');
            $results.show();
        } else {
            alert(data.message);
        }
    }
    
})(jQuery);