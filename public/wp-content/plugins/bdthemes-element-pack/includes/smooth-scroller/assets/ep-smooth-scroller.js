(function ($) {
  "use strict";

  $(document).ready(function () {

    if ($('.elementor-editor-active').length > 0) {
      console.log('Element Pack Smooth Scroller is disabled in Elementor Editor');
      return;
    }

    const lenis = new Lenis({
      duration: 1.2,
      easing: (t) => Math.min(1, 1.001 - Math.pow(2, -10 * t)),
      direction: 'vertical',
      gestureDirection: 'vertical',
      smooth: true,
      smoothTouch: false,
      touchMultiplier: 2,
      wheelEventsTarget: document.body,
      wrapper: window,
      content: document.documentElement,
      // Don't apply smooth scroll to modals, dialogs, or popups
      shouldScroll: (e) => {
        // Check if the event target is inside a modal, dialog, or popup
        const isInsideModal = e.target.closest('[role="dialog"], .modal, .popup, .elementor-popup-modal, .media-modal, .media-frame, .mfp-container, .swal2-container, .pswp, .fancybox-container');
        return !isInsideModal;
      }
    });

    // Add data-lenis-prevent to common modal/dialog elements
    function addLenisPrevent() {
      const modalSelectors = [
        '.modal',
        '.popup',
        '[role="dialog"]',
        '.elementor-popup-modal',
        '.media-modal',
        '.media-frame',
        '.mfp-container',
        '.swal2-container',
        '.pswp',
        '.fancybox-container'
      ];

      modalSelectors.forEach(selector => {
        document.querySelectorAll(selector).forEach(el => {
          el.setAttribute('data-lenis-prevent', '');
        });
      });
    }

    // Handle dynamically added modals
    const observer = new MutationObserver((mutations) => {
      mutations.forEach((mutation) => {
        if (mutation.addedNodes.length) {
          addLenisPrevent();
        }
      });
    });

    observer.observe(document.body, {
      childList: true,
      subtree: true
    });

    // Initialize and add prevent attributes
    addLenisPrevent();

    function raf(time) {
      lenis.raf(time);
      requestAnimationFrame(raf);
    }
    requestAnimationFrame(raf);

    // Handle modal open/close events to pause/resume Lenis
    // Watch for when modals become visible
    const modalVisibilityObserver = new MutationObserver((mutations) => {
      mutations.forEach((mutation) => {
        if (mutation.type === 'attributes' && mutation.attributeName === 'style') {
          const target = mutation.target;
          const modalSelectors = '.modal, .popup, [role="dialog"], .elementor-popup-modal, .media-modal, .mfp-container, .swal2-container';
          
          if (target.matches && target.matches(modalSelectors)) {
            const isVisible = window.getComputedStyle(target).display !== 'none' && target.offsetParent !== null;
            
            if (isVisible) {
              lenis.stop();
            } else {
              // Check if any other modal is still open
              const anyModalOpen = document.querySelector('.modal[style*="display: block"], .popup[style*="display: block"], [role="dialog"][style*="display: block"]');
              if (!anyModalOpen) {
                lenis.start();
              }
            }
          }
        }
      });
    });

    // Start observing modals
    modalVisibilityObserver.observe(document.body, {
      attributes: true,
      attributeFilter: ['style'],
      subtree: true
    });

    // Handle offcanvas overlay conflict with Lenis smooth scroll
    if (typeof bdtUIkit !== 'undefined') {
      bdtUIkit.util.on(document, 'beforeshow', '.bdt-offcanvas', function (e) {
        // Get the offcanvas element from the event target
        const $offcanvas = $(e.target);
        
        // Check if offcanvas has overlay enabled by checking data attribute
        const offcanvasAttr = $offcanvas.attr('data-bdt-offcanvas');
        
        if (offcanvasAttr && offcanvasAttr.includes('overlay: true')) {
          // Stop Lenis only when offcanvas has overlay enabled
          // The offcanvas bar has data-lenis-prevent to allow scrolling inside
          lenis.stop();
        }
      });

      bdtUIkit.util.on(document, 'hidden', '.bdt-offcanvas', function (e) {
        const $offcanvas = $(e.target);
        
        // Check if offcanvas had overlay enabled
        const offcanvasAttr = $offcanvas.attr('data-bdt-offcanvas');
        
        if (offcanvasAttr && offcanvasAttr.includes('overlay: true')) {
          // Start Lenis when offcanvas with overlay closes
          lenis.start();
        }
      });
    }
  });
  
})(jQuery);
