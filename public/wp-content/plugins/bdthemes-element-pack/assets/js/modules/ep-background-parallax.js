; (function ($, elementor) {
  'use strict';

  $(window).on('elementor/frontend/init', function () {
      var ModuleHandler = elementorModules.frontend.handlers.Base,
          BackgroundParallax;

      BackgroundParallax = ModuleHandler.extend({

          bindEvents: function () {
              this.run();
          },

          getDefaultSettings: function () {
              return {
                  media: false,
                  easing: 1,
                  viewport: 1,
              };
          },

          onElementChange: debounce(function (prop) {
              if ((prop.indexOf('section_parallax_') !== -1) || (prop.indexOf('ep_parallax_') !== -1)) {
                  this.run();
              }
          }, 400),

          settings: function (key) {
              return this.getElementSettings(key);
          },

          run: function () {
              if (elementorFrontend.isEditMode()) {
                  return;
              }

              if (this.bgParallax) {
                  this.bgParallax.$destroy();
                  this.bgParallax = null;
              }

              var settings = this.getElementSettings(),
                  options = this.getDefaultSettings(),
                  ID = this.$element.data('id');

              if ('yes' !== settings.section_parallax_on) {
                  return;
              }

              let element = jQuery('.elementor-element-' + ID).get(0);

              if (!element) {
                  return;
              }

              if (settings.section_parallax_x_value && settings.section_parallax_x_value.size) {
                  options.bgx = settings.section_parallax_x_value.size || 0;
              }
              if (settings.section_parallax_value && settings.section_parallax_value.size) {
                  options.bgy = settings.section_parallax_value.size || 0;
              }

              if (settings.ep_parallax_bg_colors) {
                  if (settings.ep_parallax_bg_border_color_start || settings.ep_parallax_bg_border_color_end) {
                      options.borderColor = [settings.ep_parallax_bg_border_color_start || 0, settings.ep_parallax_bg_border_color_end || 0];
                  }
              }
              if (settings.ep_parallax_bg_colors) {
                  if (settings.ep_parallax_bg_color_start || settings.ep_parallax_bg_color_end) {
                      options.backgroundColor = [settings.ep_parallax_bg_color_start || 0, settings.ep_parallax_bg_color_end || 0];
                  }
              }

              if (
                  settings.section_parallax_x_value ||
                  settings.section_parallax_value ||
                  settings.ep_parallax_bg_colors
              ) {
                  this.bgParallax = bdtUIkit.parallax(element, options);
              }

          },

          onDestroy: function () {
              if (this.bgParallax) {
                  this.bgParallax.$destroy();
                  this.bgParallax = null;
              }
          }
      });


      elementorFrontend.hooks.addAction('frontend/element_ready/section', function ($scope) {
          elementorFrontend.elementsHandler.addHandler(BackgroundParallax, {
              $element: $scope
          });
      });

      elementorFrontend.hooks.addAction('frontend/element_ready/container', function ($scope) {
          elementorFrontend.elementsHandler.addHandler(BackgroundParallax, {
              $element: $scope
          });
      });

  });
})(jQuery, window.elementorFrontend);
