(function ($, elementor) {
  "use strict";
  const MegaMenuAjax = {

    initAjaxLoading($container, mode) {
      $container
        .find('.ep-ajax-megamenu[data-ajax-load="true"]')
        .each(function () {
          const $panel = $(this);
          const postId = $panel.data("id");
          let loaded = false;

          const $menuItem = $panel.closest("li.ep-has-megamenu");
          if (!$menuItem.length || !postId) return;
          $menuItem.on(mode === "click" ? "click.epAjaxMegamenu" : "mouseenter.epAjaxMegamenu", () => {
            if (!loaded) {
              MegaMenuAjax.loadAjaxContent($panel, postId);
              loaded = true;
            }
          });
        });
    },

    initAjaxMobileLoading($container, mode) {
      const postId = $container.data("id");
      //remove hidden attribute
      $container.removeAttr("hidden");
      MegaMenuAjax.loadAjaxContent($container, postId);
    },

    loadAjaxContent($panel, postId) {
      const $menuItem = $panel.closest(".ep-has-megamenu");
      const $menuLink = $menuItem.find(".ep-menu-nav-link");

      // Prevent multiple loading states
      if ($menuLink.hasClass("ajax-loading")) {
        return;
      }

      $.ajax({
        url: `${window.location.origin}/wp-json/element-pack/v1/megamenu/ajax_content/`,
        type: "GET",
        data: { id: postId },
        dataType: "json",
        beforeSend: () => {
          // Add loading class and spinner
          $menuLink.addClass("ajax-loading");
          $menuLink.append('<span class="bdt-megamenu-indicator bdt-spinner" bdt-spinner="ratio: 0.5"></span>');
        },
        success: (response) => {
          this.cleanupLoading($menuLink);
          if (response?.content) {
            $panel.html(response.content);
            this.reinitializeWidgets($panel);
          } else {
            this.showError($panel);
          }
        },
        error: (xhr, status, error) => {
          console.error("MegaMenu Ajax Error:", status, error);
          this.cleanupLoading($menuLink);
          this.showError($panel);
        }
      });
    },

    cleanupLoading($menuLink) {
      // Remove loading class and spinner
      $menuLink.removeClass("ajax-loading");
      $menuLink.find(".bdt-spinner").remove();
    },

    reinitializeWidgets($panel) {
      if (typeof elementorFrontend !== "undefined") {
        $panel.find(".elementor-element").each((_, el) => {
          elementorFrontend.elementsHandler.runReadyTrigger($(el));
        });
      }
    },

    showError($panel) {
      $panel.html('<li class="ep-ajax-error">Failed to load content</li>');
    },
  };

  $(window).on("elementor/frontend/init", function () {

    var ModuleHandler = elementorModules.frontend.handlers.Base,
      MegaMenu;

    MegaMenu = ModuleHandler.extend({
      bindEvents: function () {
        this.run();
      },

      getDefaultSettings: function () {
        return {};
      },

      onElementChange: debounce(function (prop) {
        if (prop.indexOf("ep_megamenu_") !== -1) {
          this.run();
        }
      }, 400),

      settings: function (key) {
        return this.getElementSettings("ep_megamenu_" + key);
      },

      run: function () {
        var $this = this;
        var options = this.getDefaultSettings(),
          widgetID = this.$element.data("id");
        var element = this.$element.get(0);
        var $container = this.$element.find(".ep-megamenu");

        if (!$container.length) {
          return;
        }

        // Get mobile menu type setting
        var mobileMenuType =
          this.getElementSettings("mobile_menu_type") || "hamburger";

        MegaMenuAjax.initAjaxLoading($container, this.settings("mode"));

        //initial breking issue fixed
        $container.find(".megamenu-header-mobile").removeAttr("style");
        $container.removeClass("initialized");

        // Add active class to parent menu link if child item is active
        $container.find(".ep-has-megamenu").each(function() {
          var $megamenuItem = $(this);
          if ($megamenuItem.find(".ep-item.active").length > 0) {
            $megamenuItem.find("> .ep-menu-nav-link").addClass("active");
          }
        });

        var dropMenu = $($container).find(".ep-megamenu-vertical-dropdown");

        bdtUIkit.drop(dropMenu, {
          offset:
            this.settings("vertical_dropdown_offset") !== undefined
              ? this.settings("vertical_dropdown_offset")
              : "10",
          animation:
            this.settings("vertical_dropdown_animation_type") !== undefined
              ? this.settings("vertical_dropdown_animation_type")
              : "fade",
          duration:
            this.settings("vertical_dropdown_animation_duration") !== undefined
              ? this.settings("vertical_dropdown_animation_duration")
              : "200",
          delayHide:
            this.settings("vertical_dropdown_delay_hide") !== undefined
              ? this.settings("vertical_dropdown_delay_hide")
              : "800",
          mode:
            this.settings("vertical_dropdown_mode") !== undefined
              ? this.settings("vertical_dropdown_mode")
              : "click",
          animateOut:
            this.settings("vertical_dropdown_animate_out") !== undefined
              ? this.settings("vertical_dropdown_animate_out")
              : false,
        });

        //has megamenu
        var $megamenu_items = $container.find(".ep-has-megamenu");
        var $subDropdown = $container.find(".ep-default-submenu-panel");

        //dropdown options
        options.flip = false;
        options.offset =
          this.settings("offset.size") !== ""
            ? this.settings("offset.size")
            : "10";
        options.animation =
          this.settings("animation_type") !== undefined
            ? this.settings("animation_type")
            : "fade";
        options.duration =
          this.settings("animation_duration") !== undefined
            ? this.settings("animation_duration")
            : "200";
        options.delayHide =
          this.settings("delay_hide") !== undefined
            ? this.settings("delay_hide")
            : "800";
        options.mode =
          this.settings("mode") !== undefined ? this.settings("mode") : "hover";
        options.animateOut =
          this.settings("animate_out") !== undefined
            ? this.settings("animate_out")
            : false;

        $($megamenu_items).each(function (index, item) {
          var $drop = $(item).find(".ep-megamenu-panel");
          var widthType = $(item).data("width-type");

          var defaltWidthSelector = $(item).closest(".e-con-inner");
          if (defaltWidthSelector.length <= 0) {
            var defaltWidthSelector = $(item).closest(".elementor-container");
          }

          if ("horizontal" === $this.settings("direction")) {
            switch (widthType) {
              case "custom":
                options.stretch = null;
                options.target = null;
                options.boundary = null;
                options.pos = $(item).data("content-pos");
                $(this)
                  .find(".ep-megamenu-panel")
                  .css({
                    "min-width": $(item).data("content-width"),
                    "max-width": $(item).data("content-width"),
                  });
                break;
              case "full":
                options.stretch = "x";
                options.target = "#ep-megamenu-" + widgetID;
                options.boundary = false;
                break;
              default:
                options.stretch = "x";
                options.target = "#ep-megamenu-" + widgetID;
                options.boundary = defaltWidthSelector;
                break;
            }
          } else if ("vertical" === $this.settings("direction")) {
            switch (widthType) {
              case "custom":
                options.stretch = false;
                options.target = false;
                options.boundary = false;
                $(this)
                  .find(".ep-megamenu-panel")
                  .css({
                    "min-width": $(item).data("content-width"),
                    "max-width": $(item).data("content-width"),
                  });
                break;
              default:
                options.stretch = "x";
                break;
            }
            //check is RTL
            if ($($container).data("is-rtl") === 1) {
              options.pos = "left-top";
            } else {
              options.pos = "right-top";
            }
          }

          // options.toggle = $($drop).closest('.menu-item').find('.ep-menu-nav-link');

          bdtUIkit.drop($drop, options);
        });

        $($subDropdown).each(function (index, item) {
          if ("horizontal" === $this.settings("direction")) {
            if ($(item).hasClass("ep-parent-element")) {
              options.pos = "bottom-left";
            } else {
              options.pos = "right-top";
            }
          } else if ("vertical" === $this.settings("direction")) {
            options.stretch = false;
            $(this).find(".ep-megamenu-panel").css({
              "padding-left": "20px",
            });

            //check is RTL
            if ($($container).data("is-rtl") === 1) {
              options.pos = "left-top";
            } else {
              options.pos = "right-top";
            }
          }
          options.stretch = false;
          options.target = false;
          options.flip = true;

          bdtUIkit.drop(item, options);
        });

        var dropWrapper = $(element).closest(".elementor-top-section");
        if (dropWrapper.length <= 0) {
          var dropWrapper = $(element).closest(
            ".elementor-element.e-con.e-parent"
          );
          if (dropWrapper.length <= 0) {
            var dropWrapper = $(element).closest(
              ".elementor-widget-bdt-mega-menu"
            );
          }
        }

        if ($(dropWrapper).find(".ep-virtual-area").length === 0) {
          // code to run if it isn't there
          $("#ep-megamenu-" + widgetID)
            .clone()
            .appendTo(dropWrapper)
            .wrapAll('<div class="ep-virtual-area" />');
          dropWrapper.find(".ep-virtual-area [id]").each(function (index, obj) {
            let old_id = $(obj).attr("id");
            $(obj).attr("id", old_id + "-virtual");
          });
          dropWrapper
            .find(".ep-virtual-area [fill]")
            .each(function (index, obj) {
              let fill_id = $(obj).attr("fill");
              if (fill_id.indexOf("url(#") == 0) {
                $(obj).attr(
                  "fill",
                  ("url(#", fill_id.slice(0, -1) + "-virtual)")
                );
              }
            });
        }

        /**
         * Remove Attributes from Virtual DOMS
         */

        dropWrapper
          .find(".ep-virtual-area .bdt-navbar-nav")
          .removeAttr("class");
        dropWrapper
          .find(".ep-virtual-area .menu-item")
          .removeAttr("data-width-type");
        dropWrapper
          .find(".ep-virtual-area .menu-item")
          .removeAttr("data-content-width");
        dropWrapper
          .find(".ep-virtual-area .menu-item")
          .removeAttr("data-content-pos");
        dropWrapper
          .find(".ep-virtual-area .menu-item-has-children")
          .addClass("ep-has-megamenu");
        dropWrapper
          .find(".ep-virtual-area .ep-megamenu-panel")
          .removeClass()
          .addClass("bdt-accordion-content");
        dropWrapper
          .find(".ep-virtual-area .bdt-accordion-content")
          .removeAttr("style");

        $(this).find(".details").removeClass("hidden");
        $($container).find(".sub-menu-toggle").remove();

        if ($(dropWrapper).find(".bdt-accrodion-title-megamenu").length === 0) {
          dropWrapper
            .find(".ep-virtual-area .ep-menu-nav-link")
            .wrap(
              "<span class='bdt-accordion-title bdt-accrodion-title-megamenu'></span>"
            );
          dropWrapper
            .find(".ep-virtual-area .ep-menu-nav-link")
            .attr("onclick", "event.stopPropagation();");
          dropWrapper.find(".ep-virtual-area .bdt-megamenu-indicator").remove();
          $(
            '<i class="bdt-megamenu-indicator ep-icon-arrow-down-3"></i>'
          ).appendTo(dropWrapper.find(".ep-has-megamenu .bdt-accordion-title"));
        }

        /**
         *  Mobile toggler
         */
        var $toggler = $container.find(".bdt-navbar-toggle");
        var $toggleContent = dropWrapper.find(".ep-virtual-area");
        bdtUIkit.drop($toggleContent, {
          offset:
            this.settings("offset_mobile.size") !== ""
              ? this.settings("offset_mobile.size")
              : "5",
          // offset:5,
          toggle: $toggler,
          animation:
            this.settings("animation_type") !== undefined
              ? this.settings("animation_type")
              : "fade",
          duration:
            this.settings("animation_duration") !== undefined
              ? this.settings("animation_duration")
              : "200",
          mode: "click",
        });
        // Initialize accordion
        const accordionSelector = "#ep-megamenu-" + widgetID + "-virtual";
        bdtUIkit.accordion(accordionSelector, { offset: 10 });

        // Handle when accordion item opens
        $(accordionSelector).on("show", function (event, accordion) {
          const $targetItem = $(event.target)
            .find("[data-ajax-load='true']")
            .first();

          if ($targetItem.length && !$targetItem.data("ajax-loaded")) {
            $targetItem.data("ajax-loaded", true); // prevent double load
            MegaMenuAjax.initAjaxMobileLoading($targetItem, "accordion");
          }
        });

        // Toggle icon handler
        $toggler.find("svg:last").hide();
        $toggler.on("click", function (event) {
          event.stopPropagation(); // Prevent event from bubbling up
          var $icons = $(this).find("svg");
          $icons.toggle();
        });

        // Prevent icon change when clicking outside
        $(document).on("click", function (e) {
          if (
            !$(e.target).closest($toggler).length &&
            !$toggleContent.hasClass("bdt-open")
          ) {
            // Reset icons if dropdown is open and click is outside
            $toggler.find("svg:first").show();
            $toggler.find("svg:last").hide();
          }
        });

        /**
         * Simple Offcanvas Accordion for parent menu items
         */
        if (mobileMenuType === "offcanvas") {
          var $offcanvas = this.$element.find(".ep-megamenu-offcanvas");
          var $offcanvasNav = $offcanvas.find(".ep-offcanvas-nav");

          if ($offcanvas.length && $offcanvasNav.length) {
            // Remove bdt-drop and bdt-open classes from mega menu panels (we don't want dropdown behavior in offcanvas)
            $offcanvasNav
              .find(".ep-megamenu-panel, .ep-default-submenu-panel")
              .removeClass("bdt-drop bdt-open");

            // Initialize UIkit offcanvas
            bdtUIkit.offcanvas($offcanvas);

            // Find all parent menu items (items with submenus)
            $offcanvasNav
              .find("li.ep-has-megamenu, li.menu-item-has-children")
              .each(function () {
                var $menuItem = $(this);
                var $link = $menuItem.find("> a").first();
                var $submenu = $menuItem
                  .find(
                    "> .ep-megamenu-panel, > .ep-default-submenu-panel, > .sub-menu"
                  )
                  .first();

                if ($submenu.length) {
                  // Remove bdt-drop and bdt-open classes if present
                  $submenu.removeClass("bdt-drop bdt-open");

                  // Hide submenu initially
                  $submenu.hide();

                  $link.off("click");

                  // Make parent link clickable for accordion
                  $link.on("click", function (e) {
                    e.preventDefault();

                    // Toggle current submenu
                    if ($menuItem.hasClass("menu-open")) {
                      $menuItem.removeClass("menu-open");
                      $submenu.slideUp(300);
                    } else {
                      // Close siblings
                      $menuItem.siblings().removeClass("menu-open");
                      $menuItem
                        .siblings()
                        .find(
                          "> .ep-megamenu-panel, > .ep-default-submenu-panel, > .sub-menu"
                        )
                        .slideUp(300);

                      // Open current
                      $menuItem.addClass("menu-open");
                      $submenu.slideDown(300);
                    }
                  });
                }
              });

            // Reset when offcanvas closes
            // $offcanvas.on("hidden", function() {
            //   $offcanvasNav.find(".menu-open").removeClass("menu-open");
            //   $offcanvasNav.find(".ep-megamenu-panel, .ep-default-submenu-panel, .sub-menu").hide().removeClass("bdt-open");
            // });
          }
        }
      },
    });

    elementorFrontend.hooks.addAction(
      "frontend/element_ready/bdt-mega-menu.default",
      function ($scope) {
        elementorFrontend.elementsHandler.addHandler(MegaMenu, {
          $element: $scope,
        });
      }
    );
  });
})(jQuery, window.elementorFrontend);