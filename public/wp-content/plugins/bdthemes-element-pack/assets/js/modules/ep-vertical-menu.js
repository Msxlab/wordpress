/**
 * Start vertical menu widget script
 */

(function ($, elementor) {
    'use strict';
    // Vertical Menu
    var widgetVerticalMenu = function ($scope, $) {
        var $vrMenu = $scope.find('.bdt-vertical-menu');
        var $settings = $vrMenu.data('settings');
        if (!$vrMenu.length) {
            return;
        }

        var $menu = $('#' + $settings.id);

        // Handle separate arrow clicks for inner submenu type
        if ($settings.submenuType === 'inner') {
            // Don't initialize metisMenu for inner type, handle manually
            $($vrMenu).find('.bdt-menu-arrow').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                var $arrow = $(this);
                var $parent = $arrow.closest('li');
                var $submenu = $parent.find('> ul');
                
                if (!$submenu.length) return;

                // Close other open submenus at the same level
                var $siblings = $parent.siblings();
                $siblings.each(function() {
                    var $siblingSubmenu = $(this).find('> ul.mm-show');
                    if ($siblingSubmenu.length) {
                        var siblingHeight = $siblingSubmenu[0].scrollHeight;
                        $siblingSubmenu.removeClass('mm-show').addClass('mm-collapsing').css('height', siblingHeight + 'px');
                        
                        // Force reflow
                        $siblingSubmenu[0].offsetHeight;
                        
                        setTimeout(function() {
                            $siblingSubmenu.css('height', '0px');
                        }, 10);
                        
                        setTimeout(function() {
                            $siblingSubmenu.removeClass('mm-collapsing').addClass('mm-collapse').css('height', '');
                        }, 360);
                        
                        $(this).removeClass('mm-active');
                    }
                });
                
                // Toggle the current submenu with animation
                if ($submenu.hasClass('mm-show')) {
                    // Closing - remove mm-active immediately for arrow rotation
                    $parent.removeClass('mm-active');
                    var currentHeight = $submenu[0].scrollHeight;
                    $submenu.removeClass('mm-show').addClass('mm-collapsing').css('height', currentHeight + 'px');
                    
                    // Force reflow to ensure the height is set before animating
                    $submenu[0].offsetHeight;
                    
                    setTimeout(function() {
                        $submenu.css('height', '0px');
                    }, 10);
                    
                    setTimeout(function() {
                        $submenu.removeClass('mm-collapsing').addClass('mm-collapse').css('height', '');
                    }, 360);
                } else {
                    // Opening - add mm-active immediately for arrow rotation
                    $parent.addClass('mm-active');
                    $submenu.removeClass('mm-collapse').addClass('mm-collapsing');
                    $submenu.css('height', '0px');
                    
                    var targetHeight = $submenu[0].scrollHeight;
                    
                    setTimeout(function() {
                        $submenu.css('height', targetHeight + 'px');
                    }, 10);
                    
                    setTimeout(function() {
                        $submenu.removeClass('mm-collapsing').addClass('mm-show');
                        $submenu.css('height', '');
                    }, 360);
                }
            });
        } else {
            // Initialize metisMenu for outer type
            $menu.metisMenu();
        }

        if ('yes' == $settings.removeParentLink) {
            $($vrMenu).find('.has-arrow').attr('href', 'javascript:void(0);')
            // $($vrMenu).find('.has-arrow').on('click', function () {
            //     return false;
            // });
        }
    }
    jQuery(window).on('elementor/frontend/init', function () {
        elementorFrontend.hooks.addAction('frontend/element_ready/bdt-vertical-menu.default', widgetVerticalMenu);
    });


}(jQuery, window.elementorFrontend));

/**
 * End vertical menu widget script
 */