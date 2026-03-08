/**
 * Element Pack Custom Fonts Admin Script
 */
(function($) {
	'use strict';

	var EPCustomFonts = {
		variationCount: 0,

		init: function() {
			this.variationCount = $('.ep-font-variation').length;
			this.bindEvents();
		},

		bindEvents: function() {
			var self = this;

			// Upload font file
			$(document).on('click', '.ep-upload-font-file', function(e) {
				e.preventDefault();
				self.uploadFontFile($(this));
			});

			// Remove font file
			$(document).on('click', '.ep-remove-font-file', function(e) {
				e.preventDefault();
				self.removeFontFile($(this));
			});

			// Add variation
			$(document).on('click', '.ep-add-variation', function(e) {
				e.preventDefault();
				self.addVariation();
			});

			// Remove variation
			$(document).on('click', '.ep-remove-variation', function(e) {
				e.preventDefault();
				self.removeVariation($(this));
			});
		},

		uploadFontFile: function($button) {
			var $row = $button.closest('.ep-font-file-row');
			var $idInput = $row.find('.ep-font-file-id');
			var $urlInput = $row.find('.ep-font-file-url');
			var format = $button.data('format');

			// Create media frame with extension filtering
			var frame = wp.media({
				title: EPCustomFontsAdmin.uploadTitle + ' - ' + format.toUpperCase(),
				button: {
					text: EPCustomFontsAdmin.uploadButton
				},
				multiple: false
			});
			
			// Filter attachments by file extension
			frame.on('open', function() {
				var library = frame.state().get('library');
				library.props.set({
					search: '.' + format
				});
			});

			// When file is selected
			frame.on('select', function() {
				var attachment = frame.state().get('selection').first().toJSON();
				
				$idInput.val(attachment.id);
				$urlInput.val(attachment.url);

				// Show remove button
				if ($row.find('.ep-remove-font-file').length === 0) {
					$button.after('<button type="button" class="button ep-remove-font-file">' + 
						'Remove</button>');
				}
			});

			frame.open();
		},

		removeFontFile: function($button) {
			var $row = $button.closest('.ep-font-file-row');
			$row.find('.ep-font-file-id').val('');
			$row.find('.ep-font-file-url').val('');
			$button.remove();
		},

		addVariation: function() {
			var $wrapper = $('.ep-font-variations');
			var newIndex = this.variationCount;

			var template = this.getVariationTemplate(newIndex);
			$wrapper.append(template);

			this.variationCount++;
		},

		removeVariation: function($button) {
			if ($('.ep-font-variation').length > 1) {
				$button.closest('.ep-font-variation').remove();
				this.updateVariationHeaders();
			}
		},

		updateVariationHeaders: function() {
			$('.ep-font-variation').each(function(index) {
				$(this).find('h4').text('Font Variation ' + (index + 1));
				
				// Disable remove button on first variation
				if (index === 0) {
					$(this).find('.ep-remove-variation').prop('disabled', true);
				} else {
					$(this).find('.ep-remove-variation').prop('disabled', false);
				}
			});
		},

		getVariationTemplate: function(index) {
			var template = '<div class="ep-font-variation" data-index="' + index + '">';
			template += '<div class="ep-font-variation-header">';
			template += '<h4>Font Variation ' + (index + 1) + '</h4>';
			template += '<button type="button" class="button ep-remove-variation">Remove</button>';
			template += '</div>';
			template += '<table class="form-table">';
			
			// Weight
			template += '<tr>';
			template += '<th scope="row"><label>Font Weight</label></th>';
			template += '<td><select name="ep_font_files[' + index + '][font_weight]" class="regular-text">';
			var weights = {
				'100': '100 - Thin',
				'200': '200 - Extra Light',
				'300': '300 - Light',
				'400': '400 - Normal',
				'500': '500 - Medium',
				'600': '600 - Semi Bold',
				'700': '700 - Bold',
				'800': '800 - Extra Bold',
				'900': '900 - Black'
			};
			$.each(weights, function(value, label) {
				var selected = value === '400' ? ' selected' : '';
				template += '<option value="' + value + '"' + selected + '>' + label + '</option>';
			});
			template += '</select></td>';
			template += '</tr>';
			
			// Style
			template += '<tr>';
			template += '<th scope="row"><label>Font Style</label></th>';
			template += '<td><select name="ep_font_files[' + index + '][font_style]" class="regular-text">';
			template += '<option value="normal" selected>Normal</option>';
			template += '<option value="italic">Italic</option>';
			template += '<option value="oblique">Oblique</option>';
			template += '</select></td>';
			template += '</tr>';

			// Font formats
			var formats = {
				'woff2': 'WOFF2 (Recommended)',
				'woff': 'WOFF',
				'ttf': 'TTF',
				'otf': 'OTF',
				'eot': 'EOT (IE Support)',
				'svg': 'SVG (Legacy)'
			};

			$.each(formats, function(format, label) {
				template += '<tr class="ep-font-file-row">';
				template += '<th scope="row"><label>' + label + '</label></th>';
				template += '<td>';
				template += '<input type="hidden" name="ep_font_files[' + index + '][' + format + '][id]" class="ep-font-file-id" value="">';
				template += '<input type="text" name="ep_font_files[' + index + '][' + format + '][url]" class="regular-text ep-font-file-url" value="" readonly>';
				template += '<button type="button" class="button ep-upload-font-file" data-format="' + format + '" data-index="' + index + '">Upload</button>';
				template += '</td>';
				template += '</tr>';
			});

			template += '</table>';
			template += '</div>';

			return template;
		},

		getMediaType: function(format) {
			var types = {
				'woff2': 'application/font-woff2',
				'woff': 'application/font-woff',
				'ttf': 'application/x-font-ttf',
				'otf': 'application/x-font-otf',
				'eot': 'application/vnd.ms-fontobject',
				'svg': 'image/svg+xml'
			};

			return types[format] || 'application/octet-stream';
		}
	};

	// Initialize on document ready
	$(document).ready(function() {
		if ($('.ep-custom-fonts-wrapper').length) {
			EPCustomFonts.init();
		}
	});

})(jQuery);
