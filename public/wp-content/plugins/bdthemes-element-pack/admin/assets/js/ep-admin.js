jQuery(document).ready(function ($) {

    if (jQuery('.wrap').hasClass('element-pack-dashboard')) {

        // modules
        var moduleUsedWidget = jQuery('#element_pack_active_modules_page').find('.ep-used-widget');
        var moduleUsedWidgetCount = jQuery('#element_pack_active_modules_page').find('.ep-options .ep-used').length;
        moduleUsedWidget.text(moduleUsedWidgetCount);
        var moduleUnusedWidget = jQuery('#element_pack_active_modules_page').find('.ep-unused-widget');
        var moduleUnusedWidgetCount = jQuery('#element_pack_active_modules_page').find('.ep-options .ep-unused').length;
        moduleUnusedWidget.text(moduleUnusedWidgetCount);

        // 3rd party
        var thirdPartyUsedWidget = jQuery('#element_pack_third_party_widget_page').find('.ep-used-widget');
        var thirdPartyUsedWidgetCount = jQuery('#element_pack_third_party_widget_page').find('.ep-options .ep-used').length;
        thirdPartyUsedWidget.text(thirdPartyUsedWidgetCount);
        var thirdPartyUnusedWidget = jQuery('#element_pack_third_party_widget_page').find('.ep-unused-widget');
        var thirdPartyUnusedWidgetCount = jQuery('#element_pack_third_party_widget_page').find('.ep-options .ep-unused').length;
        thirdPartyUnusedWidget.text(thirdPartyUnusedWidgetCount);
        
        // Function to update admin menu active state
        function updateAdminMenuState(hash) {
            // Remove current/aria-current from all Element Pack submenu items
            jQuery('#toplevel_page_element_pack_options .wp-submenu li').removeClass('current');
            jQuery('#toplevel_page_element_pack_options .wp-submenu a').removeClass('current').removeAttr('aria-current');
            
            var $targetMenuItem;
            
            if (!hash || hash === '#element_pack_welcome') {
                // Dashboard - no hash - match exact href without hash
                $targetMenuItem = jQuery('#toplevel_page_element_pack_options .wp-submenu a').filter(function() {
                    var itemHref = jQuery(this).attr('href');
                    return itemHref && itemHref === 'admin.php?page=element_pack_options';
                });
            } else {
                // Other tabs - match by hash
                $targetMenuItem = jQuery('#toplevel_page_element_pack_options .wp-submenu a[href*="' + hash + '"]');
            }
            
            if ($targetMenuItem.length) {
                $targetMenuItem.addClass('current').attr('aria-current', 'page');
                $targetMenuItem.parent('li').addClass('current');
            }
        }
        
        // Initialize admin menu state on page load
        updateAdminMenuState(window.location.hash);
        
        // Add scroll-to-top functionality for all tab navigation clicks
        jQuery(document).on('click', '.bdt-dashboard-navigation a, .bdt-tab a, .bdt-tab-item, .ep-widget-filter a, .bdt-subnav a', function() {
            // Get the hash from the clicked element
            var href = jQuery(this).attr('href');
            var hash = href && href.includes('#') ? href.substring(href.indexOf('#')) : '';
            
            // Update admin menu state
            updateAdminMenuState(hash);
            
            // Scroll to top smoothly when any tab or navigation link is clicked
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
        
        // Handle WordPress admin sub menu clicks - Prevent reload when clicking Dashboard while on dashboard
        jQuery(document).on('click', '#adminmenu .wp-submenu a, .toplevel_page_element_pack_options .wp-submenu a', function(e) {
            var href = jQuery(this).attr('href');
            
            // Allow upgrade link and license renew to navigate normally
            if (href && (href.includes('element_pack_options_upgrade') || href.includes('element_pack_options_license_renew'))) {
                return true; // Let it navigate normally
            }
            
            // Check if clicking Dashboard menu item while already on Element Pack page
            if (href && href.includes('page=element_pack_options')) {
                // Get current page query without hash
                var currentSearch = window.location.search;
                
                // Check if link has no hash (dashboard link)
                var linkHasHash = href.includes('#');
                
                // If clicking dashboard link (no hash)
                if (!linkHasHash && currentSearch.includes('page=element_pack_options')) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    // Clear any hash from URL if present
                    if (window.location.hash) {
                        history.pushState("", document.title, window.location.pathname + window.location.search);
                    }
                    
                    // Activate the dashboard tab (tab index 0)
                    var $tab = jQuery('.element-pack-dashboard .bdt-dashboard-navigation .bdt-tab');
                    if ($tab.length && typeof bdtUIkit !== 'undefined' && bdtUIkit.tab) {
                        bdtUIkit.tab($tab).show(0);
                    }
                    
                    // Scroll to top smoothly
                    window.scrollTo({
                        top: 0,
                        behavior: 'smooth'
                    });
                    
                    return false;
                }
            }
            
            // Only scroll to top if it's an Element Pack related link
            if (href && (href.includes('element_pack') || href.includes('#'))) {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            }
        });
        
        // Also handle hash change events to scroll to top
        jQuery(window).on('hashchange', function() {
            // Update admin menu based on current hash
            updateAdminMenuState(window.location.hash);
            
            // Small delay to ensure tab content is loaded before scrolling
            setTimeout(function() {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            }, 100);
        });
    }

    jQuery('.element-pack-biggopti.biggopti-error img').css({
        'margin-right': '8px',
        'vertical-align': 'middle'
    });

    // Variations swatches
    const variationSwatchesBtn = jQuery(".ep-feature-option-parent");
    const variationDependentOptions = variationSwatchesBtn.length > 0 
        ? variationSwatchesBtn.closest(".ep-option-item").nextAll()
        : jQuery('.ep-option-item[class*="ep-ep_variation_swatches_"]');
    
    const toggleVariationOptions = function() {
        if (variationSwatchesBtn.length > 0 && variationSwatchesBtn.prop("checked")) {
            variationDependentOptions.fadeIn(250);
        } else {
            variationDependentOptions.hide();
        }
    };
    
    toggleVariationOptions();
    
    if (variationSwatchesBtn.length > 0) {
        variationSwatchesBtn.on("change", toggleVariationOptions);
    }
    
    jQuery("#bdt-element_pack_other_settings").on("click", toggleVariationOptions);

    //End Variations swatches

});