/**
 * Element Pack Custom Fonts - Editor Integration
 */
(function($) {
	'use strict';

	var EPCustomFontsEditor = {
		loadedFonts: [],

		init: function() {
			this.bindEvents();
		},

		bindEvents: function() {
			var self = this;

			// Listen for font changes in Elementor
			elementor.channels.editor.on('font:insertion', function(fontType, fontFamily) {
				if (fontType === 'ep-custom') {
					self.loadCustomFont(fontFamily);
				}
			});
		},

		loadCustomFont: function(fontFamily) {
			var self = this;

			// Check if already loaded
			if ($.inArray(fontFamily, self.loadedFonts) !== -1) {
				return;
			}

			// Load font via AJAX
			$.ajax({
				url: EPCustomFonts.ajaxurl,
				type: 'POST',
				data: {
					action: 'ep_get_custom_font_css',
					nonce: EPCustomFonts.nonce,
					font_family: fontFamily
				},
				success: function(response) {
					if (response.success && response.data.css) {
						self.injectFontCSS(response.data.css);
						self.loadedFonts.push(fontFamily);
					}
				}
			});
		},

		injectFontCSS: function(css) {
			// Inject into preview iframe
			var $previewHead = elementor.$previewContents.find('head');
			
			if ($previewHead.length) {
				$previewHead.append('<style type="text/css">' + css + '</style>');
			}
		}
	};

	// Initialize when Elementor is ready
	$(window).on('elementor:init', function() {
		EPCustomFontsEditor.init();
	});

})(jQuery);
