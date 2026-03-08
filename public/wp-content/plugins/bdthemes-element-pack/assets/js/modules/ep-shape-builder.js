/**
 * Shape Builder with GSAP Animation Integration
 * 
 * This module handles the Shape Builder feature with advanced GSAP animations.
 * 
 * Features:
 * - Automatic shape positioning within wrappers
 * - GSAP-powered animations with multiple trigger types
 * - Support for 18 different animation effects
 * - Configurable duration, delay, easing, repeat, and yoyo options
 * 
 * Animation Trigger Types:
 * - on-load: Animation plays when the page loads
 * - on-hover: Animation plays on mouse hover
 * 
 * Available Animations:
 * - Fade: fade-in, fade-in-up, fade-in-down, fade-in-left, fade-in-right
 * - Zoom: zoom-in, zoom-out
 * - Rotate: rotate-in, flip-x, flip-y
 * - Motion: bounce, pulse, swing, shake
 * - Slide: slide-in-left, slide-in-right, slide-in-up, slide-in-down
 */

jQuery(window).on('elementor/frontend/init', () => {

  // Animation presets - centralized configuration
  const ANIMATION_PRESETS = {
    'fade-in': { opacity: 0 },
    'fade-in-up': { opacity: 0, y: 50 },
    'fade-in-down': { opacity: 0, y: -50 },
    'fade-in-left': { opacity: 0, x: -50 },
    'fade-in-right': { opacity: 0, x: 50 },
    'zoom-in': { scale: 0 },
    'zoom-out': { scale: 2 },
    'rotate-in': { rotation: -360 },
    'flip-x': { rotationX: 180 },
    'flip-y': { rotationY: 180 },
    'bounce': { y: -30 },
    'pulse': { scale: 0.9 },
    'swing': { rotation: 15 },
    'shake': { x: -10 },
    'slide-in-left': { x: -100 },
    'slide-in-right': { x: 100 },
    'slide-in-up': { y: 100 },
    'slide-in-down': { y: -100 }
  };

  const MOTION_ANIMATIONS = ['bounce', 'pulse', 'swing', 'shake'];

  // Get animation properties from preset
  const getAnimationProps = (name) => ANIMATION_PRESETS[name] || { opacity: 0 };

  // Build target properties for animation
  const buildTargetProps = (fromProps, options = {}) => {
    const toProps = {
      ...options,
      transformOrigin: 'center center'
    };

    // Reset animated properties to their default values
    if ('opacity' in fromProps) toProps.opacity = 1;
    if ('x' in fromProps) toProps.x = 0;
    if ('y' in fromProps) toProps.y = 0;
    if ('scale' in fromProps) toProps.scale = 1;
    if ('rotation' in fromProps) toProps.rotation = 0;
    if ('rotationX' in fromProps) toProps.rotationX = 0;
    if ('rotationY' in fromProps) toProps.rotationY = 0;

    return toProps;
  };

  const applyShapeToWrapper = () => {
    const shapes = document.querySelectorAll('.bdt-shape-builder');
    
    shapes.forEach(el => {
      const wrapperClass = el.dataset.wrapperId;
      if (!wrapperClass) return;

      const wrapper = document.querySelector(`.${wrapperClass}`);
      if (wrapper) {
        wrapper.appendChild(el);
      }
    });
  };

  // Handle on-load animations with Intersection Observer
  const initOnLoadAnimations = () => {
    const shapes = document.querySelectorAll('.bdt-shape-builder[data-animation-enabled="true"][data-animation-trigger="on-load"]');
    
    shapes.forEach(el => {
      const animationName = el.dataset.animationName;
      const duration = parseFloat(el.dataset.animationDuration) || 1;
      const delay = parseFloat(el.dataset.animationDelay) || 0;
      const easing = el.dataset.animationEasing || 'none';
      const repeat = parseInt(el.dataset.animationRepeat) || 0;
      const yoyo = el.dataset.animationYoyo;
      const viewport = parseFloat(el.dataset.animationViewport) || 0.1;

      const fromProps = getAnimationProps(animationName);
      const toProps = buildTargetProps(fromProps, { duration, delay, ease: easing, repeat, yoyo });

      // Set initial state immediately to prevent flash/jump
      gsap.set(el, { 
        ...fromProps, 
        transformOrigin: 'center center' 
      });

      // Create Intersection Observer for viewport triggering
      const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            gsap.to(el, toProps);
            observer.unobserve(el);
          }
        });
      }, {
        threshold: viewport,
        rootMargin: '0px'
      });

      observer.observe(el);
    });
  };

  // Handle on-hover animations
  const initOnHoverAnimations = () => {
    const elements = document.querySelectorAll('.elementor-element[data-settings]');
    
    elements.forEach(parentEl => {
      const settingsAttr = parentEl.getAttribute('data-settings');
      if (!settingsAttr) return;

      let settings;
      try {
        const decodedSettings = jQuery('<textarea/>').html(settingsAttr).text();
        settings = JSON.parse(decodedSettings);
      } catch (e) {
        return;
      }

      // Validate shape builder settings
      if (!settings.bdt_shape_builder_list || !Array.isArray(settings.bdt_shape_builder_list)) {
        return;
      }

      const hoverShapes = settings.bdt_shape_builder_list.filter(shape => 
        shape.shape_builder_animation_popover === 'yes' && shape.animation_trigger_type === 'on-hover'
      );

      if (hoverShapes.length === 0) return;

      const timelines = [];

      hoverShapes.forEach(shapeSettings => {
        const shapeId = shapeSettings._id;
        const shapeEl = parentEl.querySelector(`.bdt-shape-builder.elementor-repeater-item-${shapeId}`);
        
        if (!shapeEl) return;

        const animationName = shapeSettings.animation_name || 'fade-in';
        const duration = shapeSettings.animation_duration?.size 
          ? parseFloat(shapeSettings.animation_duration.size) 
          : 1;
        const easing = shapeSettings.animation_easing || 'none';

        const fromProps = getAnimationProps(animationName);

        // Set initial visible state
        gsap.set(shapeEl, { opacity: 1 });

        // Create paused timeline
        const tl = gsap.timeline({ paused: true });

        if (MOTION_ANIMATIONS.includes(animationName)) {
          // For motion animations - yoyo effect
          tl.to(shapeEl, {
            ...fromProps,
            duration: duration / 2,
            ease: easing,
            yoyo: true,
            repeat: 1
          });
        } else {
          // For transition animations
          tl.to(shapeEl, {
            ...fromProps,
            duration: duration,
            ease: easing
          });
        }

        timelines.push(tl);
      });

      // Attach hover event listeners
      if (timelines.length > 0) {
        parentEl.addEventListener('mouseenter', () => {
          timelines.forEach(tl => tl.restart());
        });
        
        parentEl.addEventListener('mouseleave', () => {
          timelines.forEach(tl => tl.reverse());
        });
      }
    });
  };

  // Initialize animations
  const initShapeAnimations = () => {
    initOnLoadAnimations();
    initOnHoverAnimations();
  };

  // Initialize shapes and animations
  const initShapeBuilder = () => {
    applyShapeToWrapper();
    initShapeAnimations();
  };

  // Elementor element hooks - Container, Section, Column, Inner Section
  const elementTypes = ['container', 'section', 'column', 'inner-section'];
  elementTypes.forEach(type => {
    elementorFrontend.hooks.addAction(`frontend/element_ready/${type}`, initShapeBuilder);
  });

  // Also run once when the page is fully loaded
  jQuery(window).on('load', initShapeBuilder);
});
