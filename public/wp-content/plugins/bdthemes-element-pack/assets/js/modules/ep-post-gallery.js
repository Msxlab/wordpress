(function ($, elementor) {
    'use strict';

    var widgetPostGallery = function ($scope, $) {
        var $postGalleryWrapper = $scope.find('.bdt-post-gallery-wrapper'),
            $bdtPostGallery = $scope.find('.bdt-post-gallery'),
            $settings = $bdtPostGallery.data('settings'),
            $postFilter = $postGalleryWrapper.find('.bdt-ep-grid-filters-wrapper'),
            _skin = (typeof $settings !== 'undefined' && typeof $settings._skin !== 'undefined') ? $settings._skin.split('-').pop() : 'default',
            isEditorMode = $('.elementor-editor-active').length > 0 ? true : false;

        const tiltSelector = $settings.id + " [data-tilt]";

        if (!$postGalleryWrapper.length) {
            return;
        }

        if ($settings.tilt_show == true) {
            initializeTilt(tiltSelector);
        }

        if (!$postFilter.length) {
            return;
        }

        var $hashSettings = $postFilter.data('hash-settings');
        var activeHash = $hashSettings.activeHash;
        var hashTopOffset = $hashSettings.hashTopOffset || 70;
        var hashScrollspyTime = $hashSettings.hashScrollspyTime || 1000;

        var categoryCache = {},
            tabs_header = $postGalleryWrapper.find(".bdt-ep-grid-filter"),
            tabs = tabs_header.find(".bdt-option"),
            loader = $postGalleryWrapper.find("#bdt-loading-image");

        // Function to load content via AJAX
        function loadCategoryData(slug) {
            $(loader).show();

            if (!isEditorMode && categoryCache[slug]) {
                $bdtPostGallery.fadeOut(200, function () {
                    $(this)
                        .html(categoryCache[slug])
                        .fadeIn(300)
                        .css("transform", "translateY(-10px)")
                        .animate({ transform: "translateY(0)" }, 300);
                });
                $(loader).hide();
            } else {
                $.ajax({
                    url: ElementPackConfig.ajaxurl,
                    data: {
                        action: "bdt_post_gallery",
                        settings: $settings,
                        category: slug,
                        _skin: _skin,
                        nonce: ElementPackConfig.nonce,
                    },
                    type: "POST",
                    dataType: "HTML",
                    beforeSend: function () {
                        $(loader).show();
                    },
                    success: function (response) {
                        categoryCache[slug] = response;
                        $bdtPostGallery.fadeOut(200, function () {
                            $(this).html(response).fadeIn(300, function () {
                                if ($settings.tilt_show == true) {
                                    destroyTiltInstances(tiltSelector);
                                    initializeTilt(tiltSelector);
                                    observeTiltElements(tiltSelector);
                                }
                            });
                        });
                    },
                    error: function (response) {
                        console.log(response);
                    },
                    complete: function () {
                        $(loader).hide();
                    },
                });
            }
        }

        // Function to handle hash-based navigation
        function hashHandler() {
            if (window.location.hash) {
                var currentHash = window.location.hash.substring(1);
                
                // Decode for both decode and encoding
                var decodedHash = decodeURIComponent(currentHash);

                // Case-insensitive matching
                var targetTab = tabs_header.find('[data-slug]').filter(function() {
                    var dataSlug = $.trim($(this).attr('data-slug')).toLowerCase();
                    var hashValue = $.trim(decodedHash).toLowerCase();
                    return dataSlug === hashValue;
                });

                if (targetTab.length) {
                    tabs_header.removeClass("bdt-active");
                    targetTab.parent().addClass("bdt-active");

                    loadCategoryData(targetTab.data('slug'));

                    $('html, body').animate({
                        easing: 'slow',
                        scrollTop: $postGalleryWrapper.offset().top - hashTopOffset
                    }, hashScrollspyTime);
                }
            }
        }

        // Initialize hash-based navigation
        if (activeHash) {
            $(document).ready(function () {
                setTimeout(function() {
                    hashHandler();
                }, 100);
            });
            
            $(window).on('load', function () {
                setTimeout(function() {
                    hashHandler();
                }, 100);
            });

            $(window).on('hashchange', function () {
                hashHandler();
            });
        }

        // Tab click Handle
        tabs.on("click", function (e) {
            e.preventDefault();
            var $this = $(this),
                slug = $this.data("slug");

            tabs_header.removeClass("bdt-active");
            $this.parent().addClass("bdt-active");

            loadCategoryData(slug);
            
            // Set hash if activeHash is enabled
            if (activeHash) {
                // Encode and Decoded
                const title = $.trim(slug);
                // Set encoded hash for logic
                const encoded = encodeURIComponent(title);
                // Update location.hash (encoded, works with browser reloads)
                window.location.hash = encoded;
                // Replace the ugly encoded with friendly version
                history.replaceState(null, null, '#' + title);
            }
        });

        // Tilt effect functions
        function destroyTiltInstances(selector) {
            var elements = document.querySelectorAll(selector);
            elements.forEach(function (element) {
                if (element.vanillaTilt) {
                    element.vanillaTilt.destroy();
                }
            });
        }

        function initializeTilt(selector) {
            var elements = document.querySelectorAll(selector);
            if (elements.length > 0) {
                VanillaTilt.init(elements);
            }
        }

        function observeTiltElements(selector) {
            var observer = new MutationObserver(function (mutations) {
                mutations.forEach(function (mutation) {
                    if (mutation.type === 'childList') {
                        initializeTilt(selector);
                    }
                });
            });

            var container = document.querySelector($settings.id);
            if (container) {
                observer.observe(container, { childList: true, subtree: true });
            }
        }
    };

    jQuery(window).on("elementor/frontend/init", function () {
        [
            "bdt-post-gallery.default",
            "bdt-post-gallery.bdt-abetis",
            "bdt-post-gallery.bdt-fedara",
            "bdt-post-gallery.bdt-trosia",
        ].forEach((hook) => elementorFrontend.hooks.addAction(`frontend/element_ready/${hook}`, widgetPostGallery));
    });
})(jQuery, window.elementorFrontend);