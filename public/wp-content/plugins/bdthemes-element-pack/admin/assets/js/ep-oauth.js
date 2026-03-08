/**
 * Element Pack Google OAuth Admin JavaScript - Server-side flow
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        
        var EPGoogleOAuth = {
            
            clientId: '',
            ajaxUrl: '',
            ajaxNonce: '',
            
            init: function() {
                if (typeof element_pack_oauth_admin !== 'undefined') {
                    this.clientId = element_pack_oauth_admin.client_id || '';
                    this.ajaxUrl = element_pack_oauth_admin.ajaxurl || ajaxurl;
                    this.ajaxNonce = element_pack_oauth_admin.nonce || '';
                } else {
                    return;
                }
                
                this.bindEvents();
            },
            
            bindEvents: function() {
                var self = this;
                
                $('#ep-connect-google').on('click', function(e) {
                    e.preventDefault();
                    self.connectToGoogle($(this));
                });
                
                $('#ep-disconnect-google').on('click', function(e) {
                    e.preventDefault();
                    self.disconnectFromGoogle($(this));
                });
            },
            
            connectToGoogle: function($button) {
                if (!this.clientId) {
                    alert('Please configure your Google OAuth Client ID first.');
                    return;
                }
                
            $button.prop('disabled', true).text('Connecting...');
            $('#ep-oauth-status').html(
                '<div class="ep-oauth-loading">' +
                    '<div class="ep-oauth-spinner"></div>' +
                    '<span class="ep-oauth-message">Redirecting to Google for authentication...</span>' +
                '</div>'
            );
                
                // Redirect to server-side OAuth URL generator
                window.location.href = this.ajaxUrl + '?action=ep_get_google_oauth_url&nonce=' + this.ajaxNonce;
            },
            
            disconnectFromGoogle: function($button) {
                if (!confirm('Are you sure you want to disconnect your Google account?')) {
                    return;
                }
                
                $button.prop('disabled', true).text('Disconnecting...');
                
                $.ajax({
                    url: this.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'ep_disconnect_google_oauth',
                        nonce: this.ajaxNonce
                    },
                    success: function(result) {
                        if (result.success) {
                            location.reload();
                        } else {
                            alert('Failed to disconnect: ' + result.data);
                            $button.prop('disabled', false).text('Disconnect');
                        }
                    },
                    error: function() {
                        alert('AJAX error occurred during disconnect');
                        $button.prop('disabled', false).text('Disconnect');
                    }
                });
            }
        };
        
        EPGoogleOAuth.init();
        window.EPGoogleOAuth = EPGoogleOAuth;
    });
    
})(jQuery);