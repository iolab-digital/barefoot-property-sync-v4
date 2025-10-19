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
        console.log('Barefoot Admin JS Loaded');
        
        // Check if required variables are available
        if (typeof barefoot_ajax === 'undefined') {
            console.error('barefoot_ajax is not defined');
            return;
        }
        
        // Use ajaxurl if available (WordPress admin), otherwise use barefoot_ajax.ajax_url
        var ajaxUrl = (typeof ajaxurl !== 'undefined') ? ajaxurl : barefoot_ajax.ajax_url || barefoot_ajax.ajaxurl;
        
        if (!ajaxUrl) {
            console.error('No AJAX URL available');
            return;
        }
        
        console.log('AJAX URL:', ajaxUrl);
        console.log('Nonce:', barefoot_ajax.nonce);
        
        initSyncControls(ajaxUrl);
        initTestControls(ajaxUrl);
    });
    
    /**
     * Initialize sync controls
     */
    function initSyncControls(ajaxUrl) {
        // Sync all properties
        $(document).on('click', '#sync-all-properties', function(e) {
            e.preventDefault();
            console.log('Sync button clicked');
            
            var $button = $(this);
            var originalText = $button.text();
            
            $button.text('Syncing...').prop('disabled', true);
            showProgress('Synchronizing properties...');
            
            $.post(ajaxUrl, {
                action: 'barefoot_sync_properties',
                nonce: barefoot_ajax.nonce
            })
            .done(function(response) {
                console.log('Sync response:', response);
                hideProgress();
                
                if (response && response.success) {
                    showResults({
                        success: true,
                        message: response.data.message,
                        count: response.data.count,
                        errors: response.data.errors || []
                    });
                } else {
                    showResults({
                        success: false,
                        message: (response && response.data && response.data.message) ? response.data.message : 'Sync failed - no response',
                        errors: []
                    });
                }
            })
            .fail(function(xhr, status, error) {
                console.error('Sync AJAX failed:', status, error);
                console.error('Response:', xhr.responseText);
                hideProgress();
                showResults({
                    success: false,
                    message: 'AJAX request failed: ' + error + ' (Status: ' + status + ')',
                    errors: []
                });
            })
            .always(function() {
                $button.text(originalText).prop('disabled', false);
            });
        });
        
        // Test API connection
        $(document).on('click', '#test-api-connection', function(e) {
            e.preventDefault();
            console.log('Test connection button clicked');
            
            var $button = $(this);
            var originalText = $button.text();
            
            $button.text('Testing...').prop('disabled', true);
            
            $.post(ajaxUrl, {
                action: 'barefoot_test_connection',
                nonce: barefoot_ajax.nonce
            })
            .done(function(response) {
                console.log('Test connection response:', response);
                
                if (response && response.success) {
                    showResults({
                        success: true,
                        message: 'Connection successful: ' + response.data.message,
                        count: response.data.functions_count || 0
                    });
                } else {
                    showResults({
                        success: false,
                        message: 'Connection failed: ' + ((response && response.data && response.data.message) ? response.data.message : 'Unknown error'),
                        errors: []
                    });
                }
            })
            .fail(function(xhr, status, error) {
                console.error('Test connection AJAX failed:', status, error);
                console.error('Response:', xhr.responseText);
                showResults({
                    success: false,
                    message: 'AJAX request failed: ' + error + ' (Status: ' + status + ')',
                    errors: []
                });
            })
            .always(function() {
                $button.text(originalText).prop('disabled', false);
            });
        });
        
        // Cleanup orphaned
        $(document).on('click', '#cleanup-orphaned', function(e) {
            e.preventDefault();
            
            if (!confirm('This will move properties that no longer exist in Barefoot API to draft status. Continue?')) {
                return;
            }
            
            console.log('Cleanup button clicked');
            
            var $button = $(this);
            var originalText = $button.text();
            
            $button.text('Cleaning...').prop('disabled', true);
            
            $.post(ajaxUrl, {
                action: 'barefoot_cleanup_orphaned',
                nonce: barefoot_ajax.nonce
            })
            .done(function(response) {
                console.log('Cleanup response:', response);
                
                if (response && response.success) {
                    showResults({
                        success: true,
                        message: response.data.message,
                        count: response.data.count
                    });
                } else {
                    showResults({
                        success: false,
                        message: 'Cleanup failed: ' + ((response && response.data && response.data.message) ? response.data.message : 'Unknown error'),
                        errors: []
                    });
                }
            })
            .fail(function(xhr, status, error) {
                console.error('Cleanup AJAX failed:', status, error);
                showResults({
                    success: false,
                    message: 'AJAX request failed: ' + error,
                    errors: []
                });
            })
            .always(function() {
                $button.text(originalText).prop('disabled', false);
            });
        });
        
        // Sync images
        $(document).on('click', '#sync-images', function(e) {
            e.preventDefault();
            console.log('Sync images button clicked');
            
            var $button = $(this);
            var originalText = $button.text();
            
            $button.text('📥 Downloading Images...').prop('disabled', true);
            showProgress('Downloading property images from Barefoot API...');
            
            $.post(ajaxUrl, {
                action: 'barefoot_sync_images',
                nonce: barefoot_ajax.nonce
            })
            .done(function(response) {
                console.log('Image sync response:', response);
                hideProgress();
                
                if (response && response.success) {
                    showResults({
                        success: true,
                        message: response.data.message,
                        count: response.data.properties_synced,
                        total_images: response.data.total_images,
                        errors: response.data.errors || []
                    });
                } else {
                    showResults({
                        success: false,
                        message: (response && response.data && response.data.message) ? response.data.message : 'Image sync failed - no response',
                        errors: []
                    });
                }
            })
            .fail(function(xhr, status, error) {
                console.error('Image sync AJAX failed:', status, error);
                console.error('Response:', xhr.responseText);
                hideProgress();
                showResults({
                    success: false,
                    message: 'AJAX request failed: ' + error + ' (Status: ' + status + ')',
                    errors: []
                });
            })
            .always(function() {
                $button.text(originalText).prop('disabled', false);
            });
        });
    }
    
    /**
     * Initialize test controls (for API test page)
     */
    function initTestControls(ajaxUrl) {
        // Test connection (on test page)
        $(document).on('click', '#test-connection', function(e) {
            e.preventDefault();
            console.log('Test page connection button clicked');
            runTest(ajaxUrl, 'barefoot_test_connection', 'Testing API connection...');
        });
        
        // Test get properties
        $(document).on('click', '#test-get-properties', function(e) {
            e.preventDefault();
            console.log('Test get properties button clicked');
            runTest(ajaxUrl, 'barefoot_test_get_properties', 'Testing property retrieval...');
        });
        
        // Get available functions
        $(document).on('click', '#test-get-functions', function(e) {
            e.preventDefault();
            console.log('Test get functions button clicked');
            runTest(ajaxUrl, 'barefoot_get_api_functions', 'Getting available API functions...');
        });
    }
    
    /**
     * Run a test and display results
     */
    function runTest(ajaxUrl, action, loadingMessage) {
        var $output = $('.results-output');
        
        if ($output.length === 0) {
            // Create results output if it doesn't exist
            $output = $('<div class="results-output"></div>');
            $('.test-results').append($output);
        }
        
        $output.removeClass('success error').html(loadingMessage);
        
        $.post(ajaxUrl, {
            action: action,
            nonce: barefoot_ajax.nonce
        })
        .done(function(response) {
            console.log('Test response:', response);
            
            if (response && response.success) {
                $output.addClass('success').html(formatTestResults(response.data));
            } else {
                $output.addClass('error').html('Error: ' + ((response && response.data && response.data.message) ? response.data.message : 'Unknown error'));
            }
        })
        .fail(function(xhr, status, error) {
            console.error('Test AJAX failed:', status, error);
            $output.addClass('error').html('Request failed: ' + error + ' (Status: ' + status + ')');
        });
    }
    
    /**
     * Format test results for display
     */
    function formatTestResults(data) {
        var html = '';
        
        if (data.message) {
            html += 'Message: ' + data.message + '\n\n';
        }
        
        if (data.count !== undefined) {
            html += 'Count: ' + data.count + '\n\n';
        }
        
        if (data.method_used) {
            html += 'Method Used: ' + data.method_used + '\n\n';
        }
        
        if (data.sample_property) {
            html += 'Sample Property:\n';
            for (var key in data.sample_property) {
                html += '  ' + key + ': ' + data.sample_property[key] + '\n';
            }
            html += '\n';
        }
        
        if (data.property_functions) {
            html += 'Property-related API Functions (' + data.property_functions_count + ' total):\n';
            data.property_functions.forEach(function(func) {
                html += '  • ' + func + '\n';
            });
            html += '\n';
        }
        
        if (data.total_functions) {
            html += 'Total API Functions Available: ' + data.total_functions + '\n';
        }
        
        return html || 'Test completed successfully.';
    }
    
    /**
     * Show progress indicator
     */
    function showProgress(message) {
        var $progress = $('.sync-progress');
        
        if ($progress.length) {
            $progress.find('.progress-text').text(message);
            $progress.show();
            
            // Simple progress animation
            var $fill = $progress.find('.progress-fill');
            var width = 0;
            var interval = setInterval(function() {
                width += Math.random() * 10;
                if (width > 90) {
                    width = 90;
                    clearInterval(interval);
                }
                $fill.css('width', width + '%');
            }, 500);
            
            $progress.data('interval', interval);
        } else {
            console.log('Progress:', message);
        }
    }
    
    /**
     * Hide progress indicator
     */
    function hideProgress() {
        var $progress = $('.sync-progress');
        
        if ($progress.length) {
            var interval = $progress.data('interval');
            if (interval) {
                clearInterval(interval);
            }
            
            $progress.find('.progress-fill').css('width', '100%');
            
            setTimeout(function() {
                $progress.hide();
                $progress.find('.progress-fill').css('width', '0%');
            }, 1000);
        }
    }
    
    /**
     * Show sync results
     */
    function showResults(data) {
        console.log('Showing results:', data);
        
        var $results = $('.sync-results');
        
        if ($results.length) {
            var $content = $results.find('.results-content');
            
            if ($content.length === 0) {
                $content = $('<div class="results-content"></div>');
                $results.append($content);
            }
            
            $content.removeClass('success error')
                   .addClass(data.success ? 'success' : 'error');
            
            var html = '<p><strong>' + data.message + '</strong></p>';
            
            if (data.count !== undefined) {
                html += '<p>Properties processed: ' + data.count + '</p>';
            }
            
            if (data.total_images !== undefined) {
                html += '<p>Total images synced: ' + data.total_images + '</p>';
            }
            
            if (data.errors && data.errors.length > 0) {
                html += '<h4>Errors encountered:</h4><ul>';
                data.errors.forEach(function(error) {
                    html += '<li>' + error + '</li>';
                });
                html += '</ul>';
            }
            
            $content.html(html);
            $results.show();
        } else {
            // Fallback to alert if no results container
            alert(data.message);
        }
    }
    
})(jQuery);