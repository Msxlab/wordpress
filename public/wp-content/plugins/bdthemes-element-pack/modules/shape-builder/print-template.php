<?php

trait Shape_Builder_Print_Template_Trait
{
    public function print_shape_builder_template($template, $widget)
    {
        if (empty($template)) {
            return false;
        }

        if (! $widget->get_controls('bdt_shape_builder_list')) {
            return $template;
        }

        ob_start();
?>
        <#
            if ( settings.bdt_shape_builder_enable === 'yes' && settings.bdt_shape_builder_list ) {
                _.each( settings.bdt_shape_builder_list, function( shape ) {
                    var shapeId = shape._id || elementor.helpers.getUniqueID();
                    var selectedShape = _.findWhere(epShapeBuilderEditor.shapes, { id: shape.shape_type });
                    var isCustomShape = (shape.shape_type==='custom' );

                    // Animation settings
                    var animationEnabled = shape.shape_builder_animation_popover === 'yes';
                    var animationAttrs = '';
                    
                    if (animationEnabled) {
                        var animationTrigger = shape.animation_trigger_type || 'on-load';
                        var animationName = shape.animation_name || 'fade-in';
                        var animationDuration = (shape.animation_duration && shape.animation_duration.size) ? shape.animation_duration.size : 1;
                        var animationDelay = (shape.animation_delay && shape.animation_delay.size) ? shape.animation_delay.size : 0;
                        var animationEasing = shape.animation_easing || 'power2.out';
                        var animationRepeat = shape.animation_repeat || '0';
                        var animationYoyo = shape.animation_yoyo === 'yes' ? 'true' : 'false';
                        var animationViewport = (shape.animation_viewport && shape.animation_viewport.size) ? shape.animation_viewport.size : 0.1;
                        
                        animationAttrs = ' data-animation-enabled="true"' +
                                       ' data-animation-trigger="' + animationTrigger + '"' +
                                       ' data-animation-name="' + animationName + '"' +
                                       ' data-animation-duration="' + animationDuration + '"' +
                                       ' data-animation-delay="' + animationDelay + '"' +
                                       ' data-animation-easing="' + animationEasing + '"' +
                                       ' data-animation-repeat="' + animationRepeat + '"' +
                                       ' data-animation-yoyo="' + animationYoyo + '"' +
                                       ' data-animation-viewport="' + animationViewport + '"';
                    }

                    // Determine fill type
                    var fillType = shape.shape_fill_type || 'solid' ;
                    var defs = '' ;
                    var fillValue = 'currentColor' ;

                    if ( fillType === 'solid' ) {
                        fillValue = shape.shape_color || 'currentColor' ;
                    } else if ( fillType === 'gradient' ) {
                        var gradientId = 'grad-' + shapeId;
                        var color1 = shape.shape_gradient_color_1 || '#08AEEC' ;
                        var loc1 = (shape.shape_gradient_location_1 && shape.shape_gradient_location_1.size) ? shape.shape_gradient_location_1.size + '%' : '0%' ;
                        var color2 = shape.shape_gradient_color_2 || '#20E2AD' ;
                        var loc2 = (shape.shape_gradient_location_2 && shape.shape_gradient_location_2.size) ? shape.shape_gradient_location_2.size + '%' : '100%' ;
                        var type = shape.shape_gradient_type || 'linear' ;
                        var angle = (shape.shape_gradient_angle && shape.shape_gradient_angle.size) ? shape.shape_gradient_angle.size : 90;

                        if ( type==='linear' ) {
                            defs = '<defs><linearGradient id="' + gradientId + '" gradientTransform="rotate(' + angle + ')">' + '<stop offset="' + loc1 + '" stop-color="' + color1 + '"></stop>' + '<stop offset="' + loc2 + '" stop-color="' + color2 + '"></stop>' + '</linearGradient></defs>' ;
                        } else {
                            defs = '<defs><radialGradient id="' + gradientId + '" cx="50%" cy="50%" r="50%">' + '<stop offset="' + loc1 + '" stop-color="' + color1 + '"></stop>' + '<stop offset="' + loc2 + '" stop-color="' + color2 + '"></stop>' + '</radialGradient></defs>' ;
                        }
                        fillValue='url(#' + gradientId + ')' ;
                    }
                
                    if ( isCustomShape && shape.custom_shape_upload ) {
                        try {
                            // Handle MEDIA control format - fetch SVG content
                            var svgUrl = shape.custom_shape_upload.url || '';
                            if (!svgUrl) {
                                throw new Error('No SVG URL found');
                            }
                            
                            // For editor preview, we need to fetch and parse the SVG
                            // Since this is async, we'll use a placeholder approach
                            var svgData = null;
                            
                            // Try to get from cache or fetch
                            if (window.epShapeBuilderSvgCache && window.epShapeBuilderSvgCache[svgUrl]) {
                                svgData = window.epShapeBuilderSvgCache[svgUrl];
                            } else {
                                // Use fetch API synchronously (for editor preview only)
                                var xhr = new XMLHttpRequest();
                                xhr.open('GET', svgUrl, false); // synchronous
                                xhr.send();
                                if (xhr.status === 200) {
                                    window.epShapeBuilderSvgCache = window.epShapeBuilderSvgCache || {};
                                    window.epShapeBuilderSvgCache[svgUrl] = xhr.responseText;
                                    svgData = xhr.responseText;
                                }
                            }
                            
                            if (!svgData) {
                                throw new Error('Could not parse SVG');
                            }
                            #>
                            <div class="bdt-shape-builder bdt-shape-builder-custom elementor-repeater-item-{{ shapeId }}" data-shape-id="{{ shapeId }}" {{{ animationAttrs }}}>
                                {{{ svgData }}}
                            </div>
                        <# } catch(e) { #>
                            <!-- <div class="bdt-shape-builder elementor-repeater-item-{{ shapeId }}">
                                <span style="color:red;">Invalid custom shape</span>
                            </div> -->
                    <# }} else if ( selectedShape ) {
                        // ✅ Built-in shape
                        var viewBox=selectedShape.viewBox || '0 0 100 100' ;
                        var pathData=selectedShape.path;
                        var transform=selectedShape.transform ? 'transform="' + selectedShape.transform + '"' : '' ;
                        #>
                        <div class="bdt-shape-builder elementor-repeater-item-{{ shapeId }}" data-shape-id="{{ shapeId }}" {{{ animationAttrs }}}>
                            <svg viewBox="{{ viewBox }}" preserveAspectRatio="none">
                                {{{ defs }}}
                                <# if ( transform ) { #>
                                    <g {{{ transform }}}>
                                        <path d="{{ pathData }}" fill="{{ fillValue }}"></path>
                                    </g>
                                    <# } else { #>
                                        <path d="{{ pathData }}" fill="{{ fillValue }}"></path>
                                        <# } #>
                            </svg>
                        </div>
                    <#}});}#>
                <?php
                return $template . ob_get_clean();
            }
        }
