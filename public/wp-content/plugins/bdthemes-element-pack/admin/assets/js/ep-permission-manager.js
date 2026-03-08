/**
 * Element Pack Permission Manager
 * 
 * This script handles permission manager functionality and role-based filtering
 * of Element Pack widgets in the Elementor editor and admin pages.
 */

(function($) {
    'use strict';

    // Permission Manager Functionality
    if ($('.ep-permission-manager-content').length > 0) {
        // Ensure required variables are defined
        if (typeof epRoleElementsData === 'undefined') {
            window.epRoleElementsData = {};
        }
        if (typeof epRoleElementsNonce === 'undefined') {
            window.epRoleElementsNonce = '';
        }
        
        // Add custom CSS styles
        if ($('#ep-permission-manager-styles').length === 0) {
            let customCSS = `
                <style id="ep-permission-manager-styles">
                    @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
                    .ep-setting-item .ep-widget-badge.setting { 
                        background: #0073aa !important; 
                        color: #fff !important; 
                        font-size: 11px !important;
                        font-weight: 600 !important;
                    }
                    .ep-setting-item .ep-widget-title i.dashicons { 
                        color: #0073aa; 
                        margin-right: 8px; 
                    }
                    .ep-setting-item.selected { 
                        border-left-color: #00a32a !important; 
                    }
                    .ep-setting-item.selected .ep-widget-title i.dashicons { 
                        color: #00a32a; 
                    }
                    .ep-tab-button { 
                        transition: opacity 0.3s ease; 
                    }
                </style>
            `;
            $('head').append(customCSS);
        }
        initPermissionManager();
    }

    function initPermissionManager() {
        let currentRole = '';
        let currentTab = 'core-widgets';
        let currentTypeFilter = 'all';
        let allWidgets = [];
        let allowedWidgets = [];
        let roleElements = {};
        let originalWidgetsHTML = '';
        let storedAllowedMap = {};
        
        // Initialize the interface
        setupEventListeners();
        
        function setupEventListeners() {
            // Role selector change (for single site)
            $('#ep-role-selector').on('change', function() {
                currentRole = $(this).val();
                if (currentRole) {
                    // Save selected role to localStorage
                    localStorage.setItem('ep_selected_role', currentRole);
                    loadRoleElements(currentRole);
                } else {
                    // Clear saved role if none selected
                    localStorage.removeItem('ep_selected_role');
                    showNoRoleSelected();
                }
            });
            
            // Subsite selector change (for multisite)
            $('#ep-subsite-selector').on('change', function() {
                currentRole = $(this).val();
                if (currentRole) {
                    // Save selected subsite to localStorage
                    localStorage.setItem('ep_selected_subsite', currentRole);
                    loadRoleElements(currentRole);
                } else {
                    // Clear saved subsite if none selected
                    localStorage.removeItem('ep_selected_subsite');
                    showNoRoleSelected();
                }
            });
            
            // Restore previously selected role/subsite on page load
            setTimeout(function() {
                // Check for role selector (single site)
                if ($('#ep-role-selector').length > 0) {
                    const savedRole = localStorage.getItem('ep_selected_role');
                    if (savedRole) {
                        const roleSelector = $('#ep-role-selector');
                        const optionExists = roleSelector.find('option[value="' + savedRole + '"]').length > 0;
                        
                        if (optionExists) {
                            roleSelector.val(savedRole);
                            currentRole = savedRole;
                            loadRoleElements(savedRole);
                        } else {
                            localStorage.removeItem('ep_selected_role');
                        }
                    }
                }
                
                // Check for subsite selector (multisite)
                if ($('#ep-subsite-selector').length > 0) {
                    const savedSubsite = localStorage.getItem('ep_selected_subsite');
                    if (savedSubsite) {
                        const subsiteSelector = $('#ep-subsite-selector');
                        const optionExists = subsiteSelector.find('option[value="' + savedSubsite + '"]').length > 0;
                        
                        if (optionExists) {
                            subsiteSelector.val(savedSubsite);
                            currentRole = savedSubsite;
                            loadRoleElements(savedSubsite);
                        } else {
                            localStorage.removeItem('ep_selected_subsite');
                        }
                    }
                }
            }, 200);

            // Tab button handlers
            $('.ep-tab-button').on('click', function() {
                const previousTab = currentTab;
                currentTab = $(this).data('category');
                
                // Update active tab
                $('.ep-tab-button').removeClass('active');
                $(this).addClass('active');
                
                // Update tab styles
                updateTabStyles();
                
                // Handle tab switching logic
                if (currentTab === 'settings') {
                    // Switching TO settings tab
                    showSettingsItems();
                } else {
                    // Switching FROM settings tab or between widget tabs
                    if (previousTab === 'settings') {
                        // Restore widgets first, then filter
                        restoreWidgets();
                    }
                    
                    // Show all widgets first, then apply category filter
                    if (currentTab === 'all') {
                        $('.ep-widget-item').show();
                    } else {
                        // Filter widgets by category
                        filterWidgetsByCategory(currentTab);
                    }
                }
            });
            


            // Category filter buttons (legacy - keeping for compatibility)
            $(document).on('click', '.ep-category-filter-btn', function() {
                var category = $(this).data('category');
                // Highlight active button
                $('.ep-category-filter-btn').removeClass('bdt-button-primary-active bdt-button-default-active');
                if ($(this).hasClass('bdt-button-primary')) {
                    $(this).addClass('bdt-button-primary-active');
                } else {
                    $(this).addClass('bdt-button-default-active');
                }
            });

            // Quick actions - Fixed selectors to match actual HTML IDs
            $('#ep-select-all-role-elements').on('click', function() {
                selectAllWidgets();
            });

            $('#ep-deselect-all-role-elements').on('click', function() {
                deselectAllWidgets();
            });

            // Search functionality
            $('#ep-widgets-search').on('input', function() {
                var searchTerm = $(this).val().toLowerCase().trim();
                filterWidgetsBySearch(searchTerm);
            });

            // Type filter buttons (Pro/Free/All)
            $(document).on('click', '.ep-type-filter-btn', function() {
                var filterType = $(this).data('filter');
                
                // Update active state
                $('.ep-type-filter-btn').removeClass('active').css({
                    'background': '#f0f0f1',
                    'color': '#3c434a',
                    'border': '1px solid #c3c4c7'
                });
                
                $(this).addClass('active');
                
                // Set button-specific colors when active
                if (filterType === 'all') {
                    $(this).css({
                        'background': '#0073aa',
                        'color': '#fff',
                        'border': '1px solid #0073aa'
                    });
                } else if (filterType === 'free') {
                    $(this).css({
                        'background': '#00a32a',
                        'color': '#fff',
                        'border': '1px solid #00a32a'
                    });
                } else if (filterType === 'pro') {
                    $(this).css({
                        'background': '#d63638',
                        'color': '#fff',
                        'border': '1px solid #d63638'
                    });
                }
                
                currentTypeFilter = filterType;
                applyAllFilters();
            });

            // Save and reset buttons
            $('.ep-save-role-elements').on('click', function() {
                saveRoleElements();
            });

            $('.ep-reset-role-elements').on('click', function() {
                resetRoleElements();
            });

            // Widget item click
            $(document).on('click', '.ep-widget-item:not(.disabled)', function() {
                const widgetName = $(this).data('widget');
                const isSelected = $(this).hasClass('selected');
                
                $(this).toggleClass('selected');
                
                // Update stored allowed map to keep state in sync
                if (storedAllowedMap) {
                    if (!isSelected) {
                        // Widget was just selected
                        storedAllowedMap[widgetName] = 'on';
                    } else {
                        // Widget was just deselected
                        delete storedAllowedMap[widgetName];
                    }
                }
            });
        }
        
        function updateTabStyles() {
            $('.ep-tab-button').each(function() {
                if ($(this).hasClass('active')) {
                    $(this).css({
                        'background': '#0073aa',
                        'color': '#fff',
                        'border-bottom': '2px solid #0073aa'
                    });
                } else {
                    $(this).css({
                        'background': '#f0f0f1',
                        'color': '#3c434a',
                        'border-bottom': '1px solid #c3c4c7'
                    });
                }
            });
        }
        
        function showSettingsItems() {
            
            // Check if allWidgets is available
            if (typeof allWidgets === 'undefined' || !allWidgets) {
                $('#ep-widgets-grid').html('<p>No widgets data available. Please select a role first.</p>');
                $('#ep-widgets-grid').show();
                $('#ep-role-elements-loading').hide();
                return;
            }
            
            // Get current role's allowed settings from stored data
            let allowedSettings = {};
            if (typeof epRoleElementsData !== 'undefined' && epRoleElementsData[currentRole]) {
                allowedSettings = epRoleElementsData[currentRole];
            }
            
            // Filter widgets to show only settings items
            const settingsWidgets = allWidgets.filter(function(widget) {
                return widget.module_type === 'settings';
            });
            
            // Generate HTML for settings items
            let html = '';
            settingsWidgets.forEach(function(widget) {
                // Use storedAllowedMap for consistent selection state
                let isSelected = storedAllowedMap && storedAllowedMap[widget.name] === 'on';
                
                html += '<div class="ep-widget-item ep-setting-item ' + (isSelected ? 'selected' : '') + '" data-widget="' + widget.name + '" data-category="settings" data-module-type="' + widget.module_type + '">';
                html += '<div class="ep-widget-header">';
                html += '<h4 class="ep-widget-title"><i class="dashicons dashicons-admin-settings"></i> ' + widget.label + '</h4>';
                html += '<span class="ep-widget-badge setting">SETTING</span>';
                html += '</div>';
                html += '</div>';
            });
            
            // Update the widgets grid
            $('#ep-widgets-grid').html(html);
            $('#ep-widgets-grid').show();
            $('#ep-role-elements-loading').hide();
            
            // Update tab styles
            updateTabStyles();
            
        }
        
        function categorizeWidget(widgetName, widgetElement) {
            // Use the module_type from widget data instead of guessing from names
            if (widgetElement && $(widgetElement).data('module-type')) {
                const moduleType = $(widgetElement).data('module-type');
                return moduleType;
            }
            
            // 3rd Party Widgets
            if (widgetName.includes('woocommerce') || widgetName.includes('wc-') || 
                widgetName.includes('contact-form-7') || widgetName.includes('cf7') ||
                widgetName.includes('bbpress') || widgetName.includes('buddypress') ||
                widgetName.includes('edd-') || widgetName.includes('charitable') ||
                widgetName.includes('events-calendar') || widgetName.includes('everest-forms')) {
                return '3rd-party-widgets';
            }
            
            // Extensions
            if (widgetName.includes('background-') || widgetName.includes('backdrop-filter') ||
                widgetName.includes('cursor-effects') || widgetName.includes('confetti-effects') ||
                widgetName.includes('animated-gradient-background') || widgetName.includes('animated-link') ||
                widgetName.includes('animated-heading') || widgetName.includes('parallax') ||
                widgetName.includes('overlay') || widgetName.includes('expand') ||
                widgetName.includes('smooth-scroller') || widgetName.includes('live-copy') ||
                widgetName.includes('duplicator') || widgetName.includes('custom-js') ||
                widgetName.includes('custom-css') || widgetName.includes('-effects') ||
                widgetName === 'equal-height' || widgetName === 'visibility-controls' ||
                widgetName === 'content-protector' || widgetName === 'elementor') {
                return 'extensions';
            }
            
            // Special Features
            if (widgetName.includes('dark-mode') || widgetName.includes('age-gate') ||
                widgetName.includes('cookie-consent') || widgetName.includes('adblock-detector') ||
                widgetName.includes('mega-menu')) {
                return 'special-features';
            }
            
            // Default to Core Widgets
            return 'core-widgets';
        }
        
        function filterWidgetsByCategory(category) {
            currentTab = category;
            
            // Handle settings tab
            if (currentTab === 'settings') {
                showSettingsItems();
                return;
            }
            
            applyAllFilters();
        }
        
        function filterWidgetsBySearch(searchTerm) {
            applyAllFilters();
        }
        
        function applyAllFilters() {
            const searchTerm = $('#ep-widgets-search').val().toLowerCase().trim();
            let visibleCount = 0;
            let hiddenCount = 0;
            
            // Filter widgets based on all criteria
            $('.ep-widget-item').each(function() {
                const widgetTitle = $(this).find('.ep-widget-title').text().toLowerCase();
                const widgetDescription = $(this).find('.ep-widget-description').text().toLowerCase();
                const widgetName = $(this).data('widget') || '';
                const widgetType = $(this).find('.ep-widget-badge').text().toLowerCase();
                const widgetSelected = $(this).hasClass('selected');
                
                let shouldShow = true;
                
                // Apply search filter
                if (searchTerm) {
                    const searchMatches = widgetTitle.includes(searchTerm) || 
                                        widgetDescription.includes(searchTerm) || 
                                        widgetName.toLowerCase().includes(searchTerm);
                    if (!searchMatches) {
                        shouldShow = false;
                    }
                }
                
                // Apply category filter
                if (shouldShow && currentTab !== 'all' && currentTab !== 'settings') {
                    const widgetCategory = categorizeWidget(widgetName, this);
                    if (widgetCategory !== currentTab) {
                        shouldShow = false;
                    }
                }
                
                // Apply type filter (Pro/Free/All)
                if (shouldShow && currentTypeFilter !== 'all') {
                    // Check if widget type matches filter
                    if (currentTypeFilter === 'pro' && !widgetType.includes('pro')) {
                        shouldShow = false;
                    } else if (currentTypeFilter === 'free' && !widgetType.includes('free')) {
                        shouldShow = false;
                    } else if (currentTypeFilter === 'selected' && !widgetSelected) {
                        shouldShow = false;
                    }
                }
                
                // Apply visibility
                if (shouldShow) {
                    $(this).show();
                    visibleCount++;
                } else {
                    $(this).hide();
                    hiddenCount++;
                }
            });
        }

        function restoreWidgets() {
            if (originalWidgetsHTML) {
                // Restore the original widgets HTML
                $('#ep-widgets-grid').html(originalWidgetsHTML);
                $('#ep-widgets-grid').show();
                
                // Reapply selections based on stored allowed map
                $('.ep-widget-item').each(function() {
                    const widgetName = $(this).data('widget');
                    if (storedAllowedMap && storedAllowedMap[widgetName]) {
                        $(this).addClass('selected');
                    } else {
                        $(this).removeClass('selected');
                    }
                });
            }
        }
        
        function debugWidgetCategories() {
            let categoryCounts = {
                'core-widgets': 0,
                '3rd-party-widgets': 0,
                'extensions': 0,
                'special-features': 0,
                'settings': 0
            };
            
            let extensionWidgets = [];
            let allWidgetNames = [];
            
            $('.ep-widget-item').each(function() {
                const widgetName = $(this).data('widget') || '';
                const widgetLabel = $(this).find('.ep-widget-title').text() || '';
                const moduleType = $(this).data('module-type') || '';
                const widgetCategory = $(this).hasClass('ep-setting-item') ? 'settings' : categorizeWidget(widgetName, this);
                
                allWidgetNames.push(widgetName);
                
                if (widgetCategory === 'extensions') {
                    extensionWidgets.push({name: widgetName, label: widgetLabel, moduleType: moduleType});
                }
                
                if (categoryCounts.hasOwnProperty(widgetCategory)) {
                    categoryCounts[widgetCategory]++;
                } else {
                    categoryCounts[widgetCategory] = 1;
                }
            });
        }
        
        function loadRoleElements(role) {
            // Show the container and loading state
            $('#ep-role-elements-container').show();
            showLoading();
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'ep_get_role_elements',
                    role: role,
                    nonce: epRoleElementsNonce,
                    ep_permission_manager_bypass: true // <--- added for backend bypass
                },
                success: function(response) {
                    if (response.success) {
                        var widgets = response.data.all_widgets;
                        var allowed = response.data.allowed_widgets;
                        var allowedMap = {};
                        
                        // Convert array of widget names to map with 'on' status
                        if (Array.isArray(allowed)) {
                            allowed.forEach(function(name) { 
                                allowedMap[name] = 'on'; 
                            });
                        } else if (typeof allowed === 'object') {
                            // If it's already a map, use it directly
                            allowedMap = allowed;
                        }
                        
                        // Store widgets globally for settings tab
                        allWidgets = widgets;
                        allowedWidgets = allowed;
                        
                        renderWidgets(widgets, allowedMap);
                    } else {
                        showNotice('Error loading widgets: ' + response.data, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    showNotice('Error loading widgets. Please try again.', 'error');
                }
            });
        }
        
        function renderWidgets(widgets, allowedMap) {
            
            let html = '';
            
            if (widgets && widgets.length > 0) {
                widgets.forEach(function(widget) {
                    let isSelected = allowedMap && (allowedMap[widget.name] === 'on' || allowedMap[widget.name] === true);
                    
                    html += '<div class="ep-widget-item ' + (isSelected ? 'selected' : '') + '" data-widget="' + widget.name + '" data-category="' + (widget.content_type || 'basic') + '" data-module-type="' + (widget.module_type || 'core-widgets') + '">';
                    html += '<div class="ep-widget-header">';
                    html += '<h4 class="ep-widget-title">' + widget.label + '</h4>';
                    html += '<span class="ep-widget-badge ' + widget.widget_type + '">' + widget.widget_type + '</span>';
                    html += '</div>';
                    // Only render description if it exists
                    if (widget.description) {
                        html += '<p class="ep-widget-description">' + widget.description + '</p>';
                    }
                    // Do NOT render .ep-widget-category (content_type) anymore
                    html += '</div>';
                });
            } else {
                html = '<div class="ep-loading"><i class="dashicons dashicons-warning"></i><p>No widgets available</p></div>';
            }
            
            $('#ep-widgets-grid').html(html);
            $('#ep-widgets-grid').show(); // Make sure it's visible
            $('#ep-role-elements-loading').hide(); // Hide loading indicator
            
            // Store original widgets HTML and allowed map for later restoration
            originalWidgetsHTML = html;
            storedAllowedMap = allowedMap || {};
            
            // Initialize tab filtering and update styles
            updateTabStyles();
            
            // Show all widgets initially (no filtering)
            currentTab = 'all';
            
            // Debug widget categories
            debugWidgetCategories();
        }
        
        // Add search field above widget grid on init
        if ($('.ep-permission-manager-content').length > 0) {
            if ($('#ep-widget-search').length === 0) {
                $('.ep-permission-manager-controls').append('<div style="margin: 16px 0 8px 0;"><input type="text" id="ep-widget-search" class="bdt-input" placeholder="Search widgets..." style="width: 100%; max-width: 400px;"></div>');
            }
        }

        let currentCategory = '';
        let currentSearch = '';

        // Add secondary filter group tracking
        let currentPrimary = '';
        let currentSecondary = '';

        // Update filter button event handlers
        $(document).on('click', '.ep-category-filter-btn', function() {
            var category = $(this).data('category');
            var isPrimary = $(this).hasClass('bdt-button-primary');
            var isAll = (category === "");

            if (isAll) {
                // Deselect all filter buttons in both groups
                $('.ep-category-filter-btn.bdt-button-primary').removeClass('bdt-button-primary-active');
                $('.ep-category-filter-btn.bdt-button-default').removeClass('bdt-button-default-active');
                // Set both filter states to ""
                currentPrimary = "";
                currentSecondary = "";
                // Highlight both "All" buttons
                $('.ep-category-filter-btn.bdt-button-primary[data-category=""]').addClass('bdt-button-primary-active');
                $('.ep-category-filter-btn.bdt-button-default[data-category=""]').addClass('bdt-button-default-active');
            } else if (isPrimary) {
                currentPrimary = category;
                $('.ep-category-filter-btn.bdt-button-primary').removeClass('bdt-button-primary-active');
                $(this).addClass('bdt-button-primary-active');
            } else {
                currentSecondary = category;
                $('.ep-category-filter-btn.bdt-button-default').removeClass('bdt-button-default-active');
                $(this).addClass('bdt-button-default-active');
            }
            applyWidgetFilters();
        });

        // Set initial filter states (All for both)
        currentPrimary = '';
        currentSecondary = '';
        // Highlight 'All' buttons on load
        $('.ep-category-filter-btn.bdt-button-primary[data-category=""]').addClass('bdt-button-primary-active');
        $('.ep-category-filter-btn.bdt-button-default[data-category=""]').addClass('bdt-button-default-active');

        // Update applyWidgetFilters to AND both filters
        function applyWidgetFilters() {
            $('.ep-widget-item').each(function() {
                var $item = $(this);
                var matchesPrimary = false;
                var matchesSecondary = false;
                // --- Primary filter logic ---
                if (!currentPrimary) {
                    matchesPrimary = true;
                } else if (currentPrimary === 'pro') {
                    matchesPrimary = $item.find('.ep-widget-badge').hasClass('pro');
                } else if (currentPrimary === 'free') {
                    matchesPrimary = $item.find('.ep-widget-badge').hasClass('free');
                } else {
                    matchesPrimary = true;
                }
                // --- Secondary filter logic ---
                if (!currentSecondary) {
                    matchesSecondary = true;
                } else if (currentSecondary === 'forms') {
                    matchesSecondary = ($item.data('category')+"").toLowerCase().indexOf('form') !== -1;
                } else if (currentSecondary === 'third-party') {
                    matchesSecondary = ($item.data('category')+"").toLowerCase().indexOf('third') !== -1;
                } else if ([
                    'new', 'post', 'custom', 'gallery', 'slider', 'carousel', 'template-builder', 'others', 'woocommerce', 'basic'
                ].includes(currentSecondary)) {
                    var cat = ($item.data('category')+"").toLowerCase().replace(/[_\s-]+/g, '-');
                    var keyword = currentSecondary.replace(/[_\s-]+/g, '-');
                    matchesSecondary = cat.indexOf(keyword) !== -1;
                } else {
                    matchesSecondary = $item.data('category') === currentSecondary;
                }
                // --- Search logic (unchanged) ---
                var label = $item.find('.ep-widget-title').text().toLowerCase();
                var desc = $item.find('.ep-widget-description').text().toLowerCase();
                var matchesSearch = (!currentSearch || label.indexOf(currentSearch) !== -1 || desc.indexOf(currentSearch) !== -1);
                // Show/hide
                if (matchesPrimary && matchesSecondary && matchesSearch) {
                    $item.show();
                } else {
                    $item.hide();
                }
            });
        }
        
        function selectAllWidgets() {
            var widgets = $('.ep-widget-item:visible:not(.disabled)');
            
            widgets.addClass('selected');
            
            // Update stored allowed map
            if (storedAllowedMap) {
                widgets.each(function() {
                    const widgetName = $(this).data('widget');
                    if (widgetName) {
                        storedAllowedMap[widgetName] = 'on';
                    }
                });
            }
        }
        
        function deselectAllWidgets() {
            var widgets = $('.ep-widget-item:visible');
            
            widgets.removeClass('selected');
            
            // Update stored allowed map
            if (storedAllowedMap) {
                widgets.each(function() {
                    const widgetName = $(this).data('widget');
                    if (widgetName) {
                        delete storedAllowedMap[widgetName];
                    }
                });
            }
        }
        
        function showLoading() {
            $('#ep-widgets-grid').html('<div class="ep-loading"><i class="dashicons dashicons-update"></i><p>Loading elements...</p></div>');
        }
        
        function showNoRoleSelected() {
            // Hide the container when no role is selected
            $('#ep-role-elements-container').hide();
            
            if (typeof epIsMultisite !== 'undefined' && epIsMultisite) {
                $('#ep-widgets-grid').html('<div class="ep-no-role-selected"><i class="dashicons dashicons-admin-site-alt3"></i><p>Please select a subsite to manage its element access</p></div>');
                $('.ep-status-text').text('Select a subsite to manage its element access');
            } else {
                $('#ep-widgets-grid').html('<div class="ep-no-role-selected"><i class="dashicons dashicons-admin-users"></i><p>Please select a user role to manage its element access</p></div>');
                $('.ep-status-text').text('Select a role to manage its element access');
            }
        }
        
        function saveRoleElements() {
            if (!currentRole) {
                let message = 'Please select a ' + (typeof epIsMultisite !== 'undefined' && epIsMultisite ? 'subsite' : 'role') + ' first.';
                showNotice(message, 'warning');
                return;
            }
            
            // Show saving state
            $('.ep-save-role-elements').prop('disabled', true).html('<i class="dashicons dashicons-update" style="animation: spin 1s linear infinite;"></i> Saving...');
            
            let selectedWidgets = {};
            
            // Get all widgets from allWidgets array to ensure we save all widgets
            if (allWidgets && allWidgets.length > 0) {
                allWidgets.forEach(function(widget) {
                    // Check if this widget is selected in the DOM
                    let widgetElement = $('.ep-widget-item[data-widget="' + widget.name + '"]');
                    if (widgetElement.length > 0 && widgetElement.hasClass('selected')) {
                        selectedWidgets[widget.name] = 'on';
                    } else {
                        // If widget element doesn't exist in DOM (e.g., settings items when on other tabs),
                        // check if it was previously selected
                        if (storedAllowedMap && storedAllowedMap[widget.name] === 'on') {
                            selectedWidgets[widget.name] = 'on';
                        }
                    }
                });
            }
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'ep_save_role_elements',
                    role: currentRole,
                    elements: selectedWidgets,
                    nonce: epRoleElementsNonce
                },
                success: function(response) {
                    if (response.success) {
                        if (typeof epRoleElementsData === 'undefined') {
                            epRoleElementsData = {};
                        }
                        epRoleElementsData[currentRole] = selectedWidgets;
                        
                        // Update stored allowed map to keep selections in sync
                        storedAllowedMap = selectedWidgets;
                        
                        // Also update epRoleElementsData for consistency
                        if (typeof epRoleElementsData === 'undefined') {
                            epRoleElementsData = {};
                        }
                        epRoleElementsData[currentRole] = selectedWidgets;
                        
                        showNotice('Permission saved successfully! ' + Object.keys(selectedWidgets).length + ' elements configured for ' + currentRole + ' role.', 'success');
                        
                        // Temporary success button state
                        $('.ep-save-role-elements').html('<i class="dashicons dashicons-yes"></i> Saved!').removeClass('bdt-button-primary').addClass('bdt-button-success');
                        setTimeout(function() {
                            $('.ep-save-role-elements').html('Save Settings').removeClass('bdt-button-success').addClass('bdt-button-primary');
                        }, 2000);
                    } else {
                        showNotice('Error saving settings: ' + response.data, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    showNotice('Error saving settings. Please try again.', 'error');
                },
                complete: function() {
                    $('.ep-save-role-elements').prop('disabled', false);
                }
            });
        }
        
        function resetRoleElements() {
            if (!currentRole) {
                let message = 'Please select a ' + (typeof epIsMultisite !== 'undefined' && epIsMultisite ? 'subsite' : 'role') + ' first.';
                showNotice(message, 'error');
                return;
            }
            
            let confirmMessage = 'Are you sure you want to reset all elements for this ' + (typeof epIsMultisite !== 'undefined' && epIsMultisite ? 'subsite' : 'role') + ' to default settings?';
            if (!confirm(confirmMessage)) {
                return;
            }
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'ep_reset_role_elements',
                    role: currentRole,
                    nonce: epRoleElementsNonce
                },
                success: function(response) {
                    if (response.success) {
                        if (typeof epRoleElementsData !== 'undefined') {
                            delete epRoleElementsData[currentRole];
                        }
                        loadRoleElements(currentRole);
                        
                        // Select the first tab after reset
                        setTimeout(function() {
                            // Remove active class from all tabs
                            $('.ep-tab-button').removeClass('active');
                            // Add active class to first tab (core-widgets)
                            $('.ep-tab-button[data-category="core-widgets"]').addClass('active');
                            // Update current tab variable
                            currentTab = 'core-widgets';
                            // Update tab styles
                            updateTabStyles();
                            // Apply filters to show core widgets
                            applyAllFilters();
                        }, 100);
                        
                        let successMessage = (typeof epIsMultisite !== 'undefined' && epIsMultisite ? 'Subsite' : 'Role') + ' elements reset to default successfully!';
                        showNotice(successMessage, 'success');
                    } else {
                        showNotice('Error resetting settings: ' + response.data, 'error');
                    }
                },
                error: function() {
                    showNotice('Error resetting settings. Please try again.', 'error');
                }
            });
        }
        
        function showNotice(message, type) {
            // Try UIKit notification first
            if (typeof bdtUIkit !== 'undefined' && bdtUIkit.notification) {
                let status = 'primary';
                if (type === 'success') status = 'success';
                else if (type === 'error') status = 'danger';
                else if (type === 'warning') status = 'warning';
                
                bdtUIkit.notification({
                    message: message,
                    status: status,
                    pos: 'top-center',
                    timeout: 3000
                });
            } 
            // Fallback to WordPress admin notice style
            else {
                // Create a temporary notice element
                let noticeClass = 'notice notice-info';
                if (type === 'success') noticeClass = 'notice notice-success';
                else if (type === 'error') noticeClass = 'notice notice-error';
                else if (type === 'warning') noticeClass = 'notice notice-warning';
                
                let noticeHtml = '<div class="' + noticeClass + ' is-dismissible ep-temp-notice" style="position: fixed; top: 32px; right: 20px; z-index: 999999; min-width: 300px; box-shadow: 0 2px 5px rgba(0,0,0,0.2);">';
                noticeHtml += '<p><strong>' + message + '</strong></p>';
                noticeHtml += '<button type="button" class="notice-dismiss" onclick="$(this).parent().fadeOut();"><span class="screen-reader-text">Dismiss this notice.</span></button>';
                noticeHtml += '</div>';
                
                // Add to body
                $('body').append(noticeHtml);
                
                // Auto-remove after 3 seconds
                setTimeout(function() {
                    $('.ep-temp-notice').fadeOut(function() {
                        $(this).remove();
                    });
                }, 3000);
            }
        }
        
        // Show initial state
        showNoRoleSelected();
        
        // Auto-select subsite for subsite admins (if only one option available)
        if (typeof epIsMultisite !== 'undefined' && epIsMultisite && 
            typeof epIsMainSiteAdmin !== 'undefined' && !epIsMainSiteAdmin &&
            typeof epCurrentSubsiteId !== 'undefined' && epCurrentSubsiteId) {
            // Auto-select the current subsite for subsite admins
            if ($('#ep-subsite-selector').length > 0) {
                $('#ep-subsite-selector').val(epCurrentSubsiteId).trigger('change');
            } else if ($('#ep-role-selector').length > 0) {
                $('#ep-role-selector').val(epCurrentSubsiteId).trigger('change');
            }
        }

        // Toggle Permission Manager Save Button Section
        function togglePermissionManagerSaveSection() {
            var hash = window.location.hash;
            var showBtns = (hash.indexOf('element_pack_permission_manager') !== -1);
            if (showBtns) {
                $('.ep-permission-manager-save-section').show();
            } else {
                $('.ep-permission-manager-save-section').hide();
            }
        }
        // Run on load
        togglePermissionManagerSaveSection();
        // Run on hash change
        $(window).on('hashchange', togglePermissionManagerSaveSection);

        // Search input event handler (fix)
        $(document).on('input', '#ep-widget-search', function() {
            currentSearch = $(this).val().toLowerCase();
            applyWidgetFilters();
        });
    }

    // Role Filters functionality (from ep-role-filters.js)
    var RoleFilters = {
        init: function() {
            this.bindEvents();
            this.applyRoleFilters();
            this.applyAdminSettingsFilters();
        },

        bindEvents: function() {
            // Listen for Elementor editor ready
            $(document).on('elementor/editor/init', function() {
                RoleFilters.applyRoleFilters();
            });

            // Listen for widget panel updates
            $(document).on('elementor/panel/widgets/loaded', function() {
                RoleFilters.hideRestrictedWidgets();
            });

            // Listen for new widgets added
            $(document).on('elementor/widgets/register', function() {
                RoleFilters.hideRestrictedWidgets();
            });
        },

        applyRoleFilters: function() {
            // Only apply in Elementor editor
            if (typeof elementorFrontend === 'undefined') {
                return;
            }
            
            var settings = elementorFrontend.config;
            
            if (settings.ep_role_filters && settings.ep_role_filters.has_restrictions) {
                this.hideRestrictedWidgets();
                this.showRestrictionNotice();
            }
        },

        hideRestrictedWidgets: function() {
            // Only apply in Elementor editor
            if (typeof elementorFrontend === 'undefined') {
                return;
            }
            
            var settings = elementorFrontend.config;
            
            if (!settings.ep_role_filters || !settings.ep_role_filters.restricted_widgets) {
                return;
            }

            var restrictedWidgets = settings.ep_role_filters.restricted_widgets;

            // Hide restricted widgets from the widgets panel
            restrictedWidgets.forEach(function(widgetName) {
                // Hide from the widgets list in the panel
                var panelWidget = $('.elementor-element-wrapper[data-widget-type="' + widgetName + '"]');
                if (panelWidget.length) {
                    panelWidget.hide();
                    panelWidget.addClass('ep-restricted');
                }

                // Also hide from any other widget elements
                var widgetElement = $('[data-widget-type="' + widgetName + '"]');
                if (widgetElement.length) {
                    widgetElement.hide();
                    widgetElement.addClass('ep-restricted');
                }
            });

            // Hide restricted widgets from search results
            $(document).on('input', '.elementor-panel-search-input', function() {
                setTimeout(function() {
                    restrictedWidgets.forEach(function(widgetName) {
                        var searchResult = $('.elementor-element-wrapper[data-widget-type="' + widgetName + '"]');
                        if (searchResult.length) {
                            searchResult.hide();
                            searchResult.addClass('ep-restricted');
                        }
                    });
                }, 100);
            });

            // Also hide from category tabs
            $(document).on('click', '.elementor-panel-category', function() {
                setTimeout(function() {
                    restrictedWidgets.forEach(function(widgetName) {
                        var categoryWidget = $('.elementor-element-wrapper[data-widget-type="' + widgetName + '"]');
                        if (categoryWidget.length) {
                            categoryWidget.hide();
                            categoryWidget.addClass('ep-restricted');
                        }
                    });
                }, 100);
            });
        },

        showRestrictionNotice: function() {
            // Only apply in Elementor editor
            if (typeof elementorFrontend === 'undefined') {
                return;
            }
            
            var settings = elementorFrontend.config;
            
            if (!settings.ep_role_filters || !settings.ep_role_filters.has_restrictions) {
                return;
            }

            // Check if notice already exists
            if ($('.ep-role-restriction-notice').length) {
                return;
            }

            var notice = $('<div class="ep-role-restriction-notice notice notice-info is-dismissible">' +
                '<p><strong>Element Pack:</strong> Some widgets are restricted based on your user role. ' +
                'Contact an administrator if you need access to additional widgets.</p>' +
                '</div>');

            // Add notice to the editor
            $('.elementor-panel-header').after(notice);

            // Make notice dismissible
            notice.find('.notice-dismiss').on('click', function() {
                notice.fadeOut();
            });
        },

        getRestrictedWidgets: function() {
            // For admin pages, we'll get this from PHP via global variable
            if (typeof window.epRestrictedWidgets !== 'undefined') {
                return window.epRestrictedWidgets;
            }
            
            // For Elementor editor
            if (typeof elementorFrontend !== 'undefined' && elementorFrontend.config.ep_role_filters) {
                return elementorFrontend.config.ep_role_filters.restricted_widgets || [];
            }
            
            // Default fallback
            return [];
        },

        isWidgetRestricted: function(widgetName) {
            var restrictedWidgets = this.getRestrictedWidgets();
            return restrictedWidgets.indexOf(widgetName) !== -1;
        },

        applyAdminSettingsFilters: function() {
            // Only apply on Element Pack admin pages
            if (!window.location.href.includes('element-pack')) {
                return;
            }

            // Get restricted widgets from PHP
            var restrictedWidgets = this.getRestrictedWidgets();
            
            if (restrictedWidgets.length === 0) {
                return;
            }

            // Hide restricted widgets in admin settings
            restrictedWidgets.forEach(function(widgetName) {
                var cleanWidgetName = widgetName.replace('bdt-', '');
                
                // Hide widget options in admin settings
                $('.ep-option-item-inner').each(function() {
                    var $item = $(this);
                    var $input = $item.find('input[name*="' + cleanWidgetName + '"], input[id*="' + cleanWidgetName + '"]');
                    
                    if ($input.length > 0) {
                        $item.hide();
                        $item.addClass('ep-restricted');
                    }
                });
            });

            // Update widget counts after hiding restricted widgets
            setTimeout(function() {
                // Trigger any existing count update functions
                if (typeof updateTotalStatus === 'function') {
                    updateTotalStatus();
                }
                
                // Update used/unused counts
                RoleFilters.updateAdminCounts();
            }, 500);
        },

        updateAdminCounts: function() {
            // Update used widget counts
            $('.ep-used-widget').each(function() {
                var $container = $(this).closest('.ep-options-parent');
                var usedCount = $container.find('.ep-options .ep-used:visible').length;
                $(this).text(usedCount);
            });

            // Update unused widget counts
            $('.ep-unused-widget').each(function() {
                var $container = $(this).closest('.ep-options-parent');
                var unusedCount = $container.find('.ep-options .ep-unused:visible').length;
                $(this).text(unusedCount);
            });
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        RoleFilters.init();
    });

    // Make RoleFilters available globally
    window.ElementPackRoleFilters = RoleFilters;

    // Elementor Editor Restricted Widget Management
    var ElementorRestrictedWidgets = {
        init: function() {
            this.waitForDependencies();
        },

        waitForDependencies: function() {
            if (typeof $ !== 'undefined' && typeof elementor !== 'undefined') {
                this.setupRestrictedWidgetManagement();
            } else {
                setTimeout(this.waitForDependencies.bind(this), 100);
            }
        },

        setupRestrictedWidgetManagement: function() {
            this.hideRestrictedWidgets();
            this.setupEventListeners();
            this.setupSearchMonitoring();
            this.addCSSRules();
            this.hideExtensionControlSections();
        },

        hideRestrictedWidgets: function() {
            // Check if $ is available
            if (typeof $ === 'undefined') {
                setTimeout(this.hideRestrictedWidgets.bind(this), 100);
                return;
            }
            
            // Hide from main panel
            this.hideRestrictedWidgetsFromPanel();
            
            // Hide from search results
            this.hideRestrictedWidgetsFromSearch();
        },

        hideRestrictedWidgetsFromPanel: function() {
            var restrictedWidgets = this.getRestrictedWidgets();
            if (restrictedWidgets && restrictedWidgets.length > 0) {
                
                // Count how many widgets we find
                var foundWidgets = 0;
                var hiddenWidgets = 0;
                
                // Find all widget elements in the Element Pack category
                var $elementPackCategory = $('#elementor-panel-category-element-pack');
                
                if ($elementPackCategory.length) {
                    // Find all elementor-element-wrapper elements that contain bdt-wi- classes
                    $elementPackCategory.find('.elementor-element-wrapper').each(function () {
                        var $widget = $(this);
                        var $icon = $widget.find('[class*="bdt-wi-"]');
                        
                        if ($icon.length) {
                            foundWidgets++;
                            
                            // Primary method: Extract from icon class
                            var widgetName = null;
                            $icon.each(function () {
                                var iconClass = $(this).attr('class');
                                if (iconClass) {
                                    var iconMatch = iconClass.match(/bdt-wi-([^\s]+)/);
                                    if (iconMatch) {
                                        widgetName = 'bdt-' + iconMatch[1];
                                    }
                                }
                            });
                            
                            if (widgetName) {
                                // Check if this widget is in the restricted list
                                var isRestricted = restrictedWidgets.includes(widgetName);
                                
                                if (isRestricted) {
                                    // Hide the restricted widget
                                    $widget.remove();
                                    hiddenWidgets++;
                                }
                            }
                        }
                    });
                }
            }
        },

        hideRestrictedWidgetsFromSearch: function() {
            var restrictedWidgets = this.getRestrictedWidgets();
            if (restrictedWidgets && restrictedWidgets.length > 0) {
                
                // Hide from search results - target the specific search panel
                var $searchResults = $('#elementor-panel-elements .elementor-element-wrapper');
                var hiddenFromSearch = 0;
                
                // Also check if search is active
                var $searchInput = $('#elementor-panel-elements-search-input');
                var searchValue = $searchInput.val();
                
                if (searchValue && searchValue.length > 0) {
                    $searchResults.each(function () {
                        var $widget = $(this);
                        var $icon = $widget.find('[class*="bdt-wi-"]');
                        
                        if ($icon.length) {
                            // Extract widget name from icon class
                            var widgetName = null;
                            $icon.each(function () {
                                var iconClass = $(this).attr('class');
                                if (iconClass) {
                                    var iconMatch = iconClass.match(/bdt-wi-([^\s]+)/);
                                    if (iconMatch) {
                                        widgetName = 'bdt-' + iconMatch[1];
                                    }
                                }
                            });
                            
                            if (widgetName) {
                                // Check if this widget is in the restricted list
                                var isRestricted = restrictedWidgets.includes(widgetName);
                                
                                if (isRestricted) {
                                    // Remove the restricted widget from search completely
                                    $widget.remove();
                                    hiddenFromSearch++;
                                }
                            }
                        }
                    });
                }
            }
        },

        setupEventListeners: function() {
            // Hide restricted widgets immediately
            this.hideRestrictedWidgets();
            
            // Try to use elementor channels if available
            if (typeof elementor !== 'undefined' && elementor.channels && elementor.channels.panel) {
                
                // Also hide when panel is refreshed or widgets are updated
                elementor.channels.panel.on('change', function () {
                    setTimeout(ElementorRestrictedWidgets.hideRestrictedWidgets.bind(ElementorRestrictedWidgets), 100);
                });
                
                // Hide when switching between categories
                elementor.channels.panel.on('category:activated', function () {
                    setTimeout(ElementorRestrictedWidgets.hideRestrictedWidgets.bind(ElementorRestrictedWidgets), 100);
                });
                
                // Hide when panel is opened
                elementor.channels.panel.on('open', function () {
                    setTimeout(ElementorRestrictedWidgets.hideRestrictedWidgets.bind(ElementorRestrictedWidgets), 100);
                });
                
                // Hide when widgets are loaded
                elementor.channels.panel.on('widgets:loaded', function () {
                    setTimeout(ElementorRestrictedWidgets.hideRestrictedWidgets.bind(ElementorRestrictedWidgets), 200);
                });
                
                // Hide when elements are loaded
                elementor.channels.panel.on('elements:loaded', function () {
                    setTimeout(ElementorRestrictedWidgets.hideRestrictedWidgets.bind(ElementorRestrictedWidgets), 200);
                });
            }
            
            // Use MutationObserver to watch for DOM changes (fallback method)
            if (typeof MutationObserver !== 'undefined') {
                var observer = new MutationObserver(function (mutations) {
                    mutations.forEach(function (mutation) {
                        if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                            setTimeout(ElementorRestrictedWidgets.hideRestrictedWidgets.bind(ElementorRestrictedWidgets), 100);
                        }
                    });
                });
                
                // Start observing the panel
                var panelElement = document.querySelector('#elementor-panel-category-element-pack');
                if (panelElement) {
                    observer.observe(panelElement, {
                        childList: true,
                        subtree: true
                    });
                }
                
                // Also observe the entire panel area for changes
                var panelArea = document.querySelector('#elementor-panel-elements');
                if (panelArea) {
                    observer.observe(panelArea, {
                        childList: true,
                        subtree: true
                    });
                }
            }
            
            // Set up periodic checking as a fallback
            setInterval(this.hideRestrictedWidgets.bind(this), 2000);
            
            // Periodic check for control sections (more frequent to handle timing issues)
            setInterval(this.hideExtensionControlSections.bind(this), 500);
            
            // Monitor for control sections being added
            $(document).on('DOMNodeInserted', '#elementor-control-section_element_pack_confetti_controls', function() {
                ElementorRestrictedWidgets.hideExtensionControlSections();
            });
            
            // Also monitor the entire control panel for any new sections
            $(document).on('DOMNodeInserted', '.elementor-controls', function() {
                setTimeout(ElementorRestrictedWidgets.hideExtensionControlSections.bind(ElementorRestrictedWidgets), 100);
            });
            
            // More aggressive monitoring for any control section with confetti
            $(document).on('DOMNodeInserted', '[id*="confetti"], [class*="confetti"]', function() {
                setTimeout(ElementorRestrictedWidgets.hideExtensionControlSections.bind(ElementorRestrictedWidgets), 50);
            });
            
            // Monitor when any control is added
            $(document).on('DOMNodeInserted', '.elementor-control', function() {
                var $control = $(this);
                var controlClass = $control.attr('class') || '';
                
                // Check if this control is for a restricted widget
                var restrictedWidgets = ElementorRestrictedWidgets.getRestrictedWidgets();
                if (restrictedWidgets && restrictedWidgets.length > 0) {
                    
                    restrictedWidgets.forEach(function(widgetName) {
                        var widgetShortName = widgetName.replace('bdt-', '');
                        
                        // Look for controls with both patterns - account for the elementor-control prefix
                        if (controlClass.includes('elementor-control-section_element_pack_'+widgetShortName+'_controls') || 
                            controlClass.includes('elementor-control-element_pack_'+widgetShortName+'_section') ||
                            controlClass.includes('section_element_pack_'+widgetShortName+'_controls') ||
                            controlClass.includes('element_pack_'+widgetShortName+'_section') ||
                            controlClass.includes('elementor-control elementor-control-section_element_pack_'+widgetShortName+'_controls') ||
                            controlClass.includes('elementor-control elementor-control-element_pack_'+widgetShortName+'_section')) {
                            $control.remove();
                        }
                    });
                }
            });
            
            // Monitor when Elementor editor is ready
            $(document).on('elementor/editor/init', function() {
                setTimeout(ElementorRestrictedWidgets.hideExtensionControlSections.bind(ElementorRestrictedWidgets), 500);
            });
            
            // Monitor when widgets are selected/activated
            $(document).on('elementor/editor/widget/activated', function() {
                setTimeout(ElementorRestrictedWidgets.hideExtensionControlSections.bind(ElementorRestrictedWidgets), 100);
            });
            
            // Monitor when any element is selected
            $(document).on('elementor/editor/element/activated', function() {
                setTimeout(ElementorRestrictedWidgets.hideExtensionControlSections.bind(ElementorRestrictedWidgets), 100);
            });
            
            // Monitor when a widget is selected (this is when controls are loaded)
            $(document).on('elementor/editor/widget/activated', function(event, widget) {
                // Wait a bit longer for controls to load
                setTimeout(ElementorRestrictedWidgets.hideExtensionControlSections.bind(ElementorRestrictedWidgets), 300);
            });
            
            // Monitor when control panel is updated
            $(document).on('elementor/panel/control/loaded', function() {
                setTimeout(ElementorRestrictedWidgets.hideExtensionControlSections.bind(ElementorRestrictedWidgets), 100);
            });
            
            // Monitor when control panel is opened
            $(document).on('elementor/panel/control/opened', function() {
                setTimeout(ElementorRestrictedWidgets.hideExtensionControlSections.bind(ElementorRestrictedWidgets), 100);
            });
            
            // Add MutationObserver specifically for control panel
            if (typeof MutationObserver !== 'undefined') {
                var controlObserver = new MutationObserver(function(mutations) {
                    mutations.forEach(function(mutation) {
                        if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                            // Check if any of the added nodes are control sections
                            mutation.addedNodes.forEach(function(node) {
                                if (node.nodeType === 1) { // Element node
                                    var $node = $(node);
                                    if ($node.hasClass('elementor-control') || $node.find('.elementor-control').length > 0) {
                                        setTimeout(ElementorRestrictedWidgets.hideExtensionControlSections.bind(ElementorRestrictedWidgets), 50);
                                    }
                                }
                            });
                        }
                    });
                });
                
                // Start observing the control panel
                var controlPanel = document.querySelector('#elementor-controls');
                if (controlPanel) {
                    controlObserver.observe(controlPanel, {
                        childList: true,
                        subtree: true
                    });
                }
                
                // Also add a more aggressive observer that continuously removes restricted controls
                var aggressiveControlObserver = new MutationObserver(function(mutations) {
                    mutations.forEach(function(mutation) {
                        if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                            mutation.addedNodes.forEach(function(node) {
                                if (node.nodeType === 1) { // Element node
                                    var $node = $(node);
                                    if ($node.hasClass('elementor-control')) {
                                        var controlClass = $node.attr('class') || '';
                                        
                                        // Check if this is a restricted control
                                        var restrictedWidgets = ElementorRestrictedWidgets.getRestrictedWidgets();
                                        if (restrictedWidgets && restrictedWidgets.length > 0) {
                                            
                                            for (var i = 0; i < restrictedWidgets.length; i++) {
                                                var widgetName = restrictedWidgets[i];
                                                var widgetShortName = widgetName.replace('bdt-', '');
                                                
                                                // Check for restricted control patterns
                                                if (controlClass.includes('elementor-control-section_element_pack_'+widgetShortName+'_controls') || 
                                                    controlClass.includes('elementor-control-element_pack_'+widgetShortName+'_section') ||
                                                    controlClass.includes('elementor-control elementor-control-section_element_pack_'+widgetShortName+'_controls') ||
                                                    controlClass.includes('elementor-control elementor-control-element_pack_'+widgetShortName+'_section')) {
                                                    $node.remove();
                                                    
                                                    // Add CSS to prevent re-appearance
                                                    var cssRule = '.' + $node.attr('class').replace(/\s+/g, '.') + ' { display: none !important; }';
                                                    if (!$('#ep-aggressive-control-hide').length) {
                                                        $('head').append('<style id="ep-aggressive-control-hide"></style>');
                                                    }
                                                    $('#ep-aggressive-control-hide').append(cssRule);
                                                    break;
                                                }
                                            }
                                        }
                                    }
                                }
                            });
                        }
                    });
                });
                
                // Start the aggressive observer
                if (controlPanel) {
                    aggressiveControlObserver.observe(controlPanel, {
                        childList: true,
                        subtree: true
                    });
                }
            }
            
            // Handle search input events - target the correct search field
            $(document).on('input', '#elementor-panel-elements-search-input', function() {
                
                // Prevent restricted widgets from appearing in search
                var searchValue = $(this).val().toLowerCase();
                var restrictedWidgets = ElementorRestrictedWidgets.getRestrictedWidgets();
                if (restrictedWidgets && restrictedWidgets.length > 0) {

                    // Check if search term matches any restricted widget
                    var isSearchingForRestricted = restrictedWidgets.some(function(widget) {
                        return searchValue.includes(widget.replace('bdt-', '').toLowerCase());
                    });
                    
                    if (isSearchingForRestricted) {
                        // Clear the search to prevent restricted widgets from showing
                        $(this).val('');
                        return;
                    }
                }
                
                // Use a more aggressive approach - remove widgets immediately and continuously
                function aggressiveRemove() {
                    var $searchResults = $('#elementor-panel-elements .elementor-element-wrapper');
                    $searchResults.each(function() {
                        var $widget = $(this);
                        var $icon = $widget.find('[class*="bdt-wi-"]');
                        
                        if ($icon.length) {
                            var widgetName = null;
                            $icon.each(function () {
                                var iconClass = $(this).attr('class');
                                if (iconClass) {
                                    var iconMatch = iconClass.match(/bdt-wi-([^\s]+)/);
                                    if (iconMatch) {
                                        widgetName = 'bdt-' + iconMatch[1];
                                    }
                                }
                            });
                            
                            if (widgetName && restrictedWidgets.indexOf(widgetName) !== -1) {
                                $widget.remove();
                            }
                        }
                    });
                }
                
                // Remove immediately and then continuously
                aggressiveRemove();
                setTimeout(aggressiveRemove, 10);
                setTimeout(aggressiveRemove, 50);
                setTimeout(aggressiveRemove, 100);
                setTimeout(aggressiveRemove, 200);
            });
            
            // Handle search results when they appear
            $(document).on('DOMNodeInserted', '.elementor-panel-elements-search-wrapper', function() {
                ElementorRestrictedWidgets.hideRestrictedWidgetsFromSearch(); // Immediate removal
                setTimeout(ElementorRestrictedWidgets.hideRestrictedWidgetsFromSearch.bind(ElementorRestrictedWidgets), 50); // Quick follow-up
            });
            
            // Handle when search is cleared
            $(document).on('click', '.elementor-panel-elements-search-wrapper .elementor-panel-elements-search-input-clear', function() {
                ElementorRestrictedWidgets.hideRestrictedWidgetsFromSearch(); // Immediate removal
                setTimeout(ElementorRestrictedWidgets.hideRestrictedWidgetsFromSearch.bind(ElementorRestrictedWidgets), 50); // Quick follow-up
            });
            
            // Also handle keyup events for search
            $(document).on('keyup', '#elementor-panel-elements-search-input', function() {
                ElementorRestrictedWidgets.hideRestrictedWidgetsFromSearch(); // Immediate removal
                setTimeout(ElementorRestrictedWidgets.hideRestrictedWidgetsFromSearch.bind(ElementorRestrictedWidgets), 50); // Quick follow-up
            });
            
            // Handle search focus events
            $(document).on('focus', '#elementor-panel-elements-search-input', function() {
                ElementorRestrictedWidgets.hideRestrictedWidgetsFromSearch(); // Immediate removal
                setTimeout(ElementorRestrictedWidgets.hideRestrictedWidgetsFromSearch.bind(ElementorRestrictedWidgets), 50); // Quick follow-up
            });
            
            // Handle changes in the search panel specifically
            $(document).on('DOMNodeInserted', '#elementor-panel-elements', function() {
                ElementorRestrictedWidgets.hideRestrictedWidgetsFromSearch(); // Immediate removal
                setTimeout(ElementorRestrictedWidgets.hideRestrictedWidgetsFromSearch.bind(ElementorRestrictedWidgets), 50); // Quick follow-up
            });
            
            // Also handle when the search panel is updated
            $(document).on('DOMSubtreeModified', '#elementor-panel-elements', function() {
                ElementorRestrictedWidgets.hideRestrictedWidgetsFromSearch(); // Immediate removal
                setTimeout(ElementorRestrictedWidgets.hideRestrictedWidgetsFromSearch.bind(ElementorRestrictedWidgets), 50); // Quick follow-up
            });
            
            // More aggressive approach - remove widgets immediately when they appear in search panel
            $(document).on('DOMNodeInserted', '#elementor-panel-elements .elementor-element-wrapper', function() {
                var $widget = $(this);
                var $icon = $widget.find('[class*="bdt-wi-"]');
                
                if ($icon.length) {
                    // Extract widget name from icon class
                    var widgetName = null;
                    $icon.each(function () {
                        var iconClass = $(this).attr('class');
                        if (iconClass) {
                            var iconMatch = iconClass.match(/bdt-wi-([^\s]+)/);
                            if (iconMatch) {
                                widgetName = 'bdt-' + iconMatch[1];
                            }
                        }
                    });
                    
                    if (widgetName) {
                        var restrictedWidgets = ElementorRestrictedWidgets.getRestrictedWidgets();
                        var isRestricted = restrictedWidgets && restrictedWidgets.indexOf(widgetName) !== -1;
                        if (isRestricted) {
                            $widget.remove();
                        }
                    }
                }
            });
            
            // Even more aggressive - monitor all DOM changes in search panel
            var searchObserver = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                        mutation.addedNodes.forEach(function(node) {
                            if (node.nodeType === 1) { // Element node
                                var $node = $(node);
                                if ($node.hasClass('elementor-element-wrapper')) {
                                    var $icon = $node.find('[class*="bdt-wi-"]');
                                    if ($icon.length) {
                                        var widgetName = null;
                                        $icon.each(function () {
                                            var iconClass = $(this).attr('class');
                                            if (iconClass) {
                                                var iconMatch = iconClass.match(/bdt-wi-([^\s]+)/);
                                                if (iconMatch) {
                                                    widgetName = 'bdt-' + iconMatch[1];
                                                }
                                            }
                                        });
                                        
                                        if (widgetName) {
                                            var restrictedWidgets = ElementorRestrictedWidgets.getRestrictedWidgets();
                                            var isRestricted = restrictedWidgets && restrictedWidgets.indexOf(widgetName) !== -1;
                                            if (isRestricted) {
                                                $node.remove();
                                            }
                                        }
                                    }
                                }
                            }
                        });
                    }
                });
            });
            
            // Start observing the search panel
            var searchPanel = document.querySelector('#elementor-panel-elements');
            if (searchPanel) {
                searchObserver.observe(searchPanel, {
                    childList: true,
                    subtree: true
                });
            }
            
            // Also observe the search wrapper
            var searchWrapper = document.querySelector('.elementor-panel-elements-search-wrapper');
            if (searchWrapper) {
                searchObserver.observe(searchWrapper, {
                    childList: true,
                    subtree: true
                });
            }
        },

        setupSearchMonitoring: function() {
            // Monitor the search panel for any changes
            var searchPanel = document.querySelector('#elementor-panel-elements');
            if (searchPanel) {
                var searchObserver = new MutationObserver(function(mutations) {
                    mutations.forEach(function(mutation) {
                        if (mutation.type === 'childList') {
                            mutation.addedNodes.forEach(function(node) {
                                if (node.nodeType === 1) { // Element node
                                    var $node = $(node);
                                    if ($node.hasClass('elementor-element-wrapper')) {
                                        // Check if this is a restricted widget
                                        var $icon = $node.find('[class*="bdt-wi-"]');
                                        if ($icon.length) {
                                            var widgetName = null;
                                            $icon.each(function () {
                                                var iconClass = $(this).attr('class');
                                                if (iconClass) {
                                                    var iconMatch = iconClass.match(/bdt-wi-([^\s]+)/);
                                                    if (iconMatch) {
                                                        widgetName = 'bdt-' + iconMatch[1];
                                                    }
                                                }
                                            });
                                            
                                            if (widgetName) {
                                                var restrictedWidgets = ElementorRestrictedWidgets.getRestrictedWidgets();
                                                var isRestricted = restrictedWidgets && restrictedWidgets.indexOf(widgetName) !== -1;
                                                if (isRestricted) {
                                                    $node.remove();
                                                }
                                            }
                                        }
                                    }
                                }
                            });
                        }
                    });
                });
                
                searchObserver.observe(searchPanel, {
                    childList: true,
                    subtree: true
                });
            }
        },

        addCSSRules: function() {
            // Add CSS to hide restricted widgets immediately
            var style = document.createElement('style');
            style.textContent = `
                #elementor-panel-elements .elementor-element-wrapper[data-widget_type*="bdt-"] {
                    transition: none !important;
                }
                #elementor-panel-elements .elementor-element-wrapper[data-widget_type*="bdt-"]:has([class*="bdt-wi-"]) {
                    display: none !important;
                }
            `;
            document.head.appendChild(style);
            
            // Also add a more aggressive CSS rule for restricted widgets
            var restrictedWidgets = ElementorRestrictedWidgets.getRestrictedWidgets();
            if (restrictedWidgets && restrictedWidgets.length > 0) {
                var restrictedWidgetsArray = Array.isArray(restrictedWidgets) ? restrictedWidgets : Object.values(restrictedWidgets);
                var cssRules = '';
                restrictedWidgetsArray.forEach(function(widget) {
                    cssRules += `#elementor-panel-elements .elementor-element-wrapper[data-widget_type="${widget}"] { display: none !important; }\n`;
                });
                var restrictedStyle = document.createElement('style');
                restrictedStyle.textContent = cssRules;
                document.head.appendChild(restrictedStyle);
            }
            
            // Add CSS to hide restricted control sections
            var restrictedWidgets = ElementorRestrictedWidgets.getRestrictedWidgets();
            if (restrictedWidgets && restrictedWidgets.length > 0) {
                var restrictedWidgetsArray = Array.isArray(restrictedWidgets) ? restrictedWidgets : Object.values(restrictedWidgets);
                var controlCSSRules = '';
                restrictedWidgetsArray.forEach(function(widget) {
                    var widgetShortName = widget.replace('bdt-', '');
                    controlCSSRules += `
                        .elementor-control.elementor-control-section_element_pack_${widgetShortName}_controls,
                        .elementor-control.elementor-control-element_pack_${widgetShortName}_section,
                        .elementor-control[class*="elementor-control-section_element_pack_${widgetShortName}_controls"],
                        .elementor-control[class*="elementor-control-element_pack_${widgetShortName}_section"] {
                            display: none !important;
                            visibility: hidden !important;
                            opacity: 0 !important;
                            height: 0 !important;
                            overflow: hidden !important;
                        }
                    `;
                });
                var controlStyle = document.createElement('style');
                controlStyle.textContent = controlCSSRules;
                controlStyle.id = 'ep-restricted-controls-css';
                document.head.appendChild(controlStyle);
            }
        },

        hideExtensionControlSections: function() {            
            $('.bdt-ep-restricted-badge').closest('.elementor-control').remove();
        },

        getRestrictedWidgets: function() {
            // For admin pages, we'll get this from PHP via global variable
            if (typeof window.epRestrictedWidgets !== 'undefined') {
                var widgets = window.epRestrictedWidgets;
                // Ensure we return an array
                if (widgets && widgets.restricted_widgets) {
                    return Array.isArray(widgets.restricted_widgets) ? widgets.restricted_widgets : Object.values(widgets.restricted_widgets);
                }
                return [];
            }
            
            // For Elementor editor
            if (typeof elementorFrontend !== 'undefined' && elementorFrontend.config.ep_role_filters) {
                var widgets = elementorFrontend.config.ep_role_filters.restricted_widgets;
                return Array.isArray(widgets) ? widgets : [];
            }
            
            // Default fallback
            return [];
        },
    };
        
    // Initialize Elementor Restricted Widgets when document is ready
    $(document).ready(function() {
        ElementorRestrictedWidgets.init();
    });
    
    // Make ElementorRestrictedWidgets available globally
    window.ElementorRestrictedWidgets = ElementorRestrictedWidgets;

})(jQuery); 
    