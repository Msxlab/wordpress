/**
 * Element Pack Custom Icons - Admin JavaScript
 * Handles file upload, drag & drop, and AJAX operations
 */

(function($) {
	'use strict';

	const BDTCustomIcons = {
		
		init: function() {
			this.dropzone = document.getElementById('bdt-icon-dropzone');
			this.fileInput = document.getElementById('bdt-icon-file-input');
			this.configInput = document.getElementById('bdt-icon-config');
			this.progressArea = document.querySelector('.bdt-upload-progress');
			this.progressFill = document.querySelector('.bdt-progress-fill');
			this.progressText = document.querySelector('.bdt-progress-text');

			if (this.dropzone && this.fileInput) {
				this.bindEvents();
			}
		},

		bindEvents: function() {
			const self = this;

			// Click to browse - trigger file input
			this.dropzone.addEventListener('click', function(e) {
				// Don't trigger if clicking the file input itself
				if (e.target.id !== 'bdt-icon-file-input') {
					e.preventDefault();
					e.stopPropagation();
					// Trigger click on file input
					const event = new MouseEvent('click', {
						view: window,
						bubbles: true,
						cancelable: true
					});
					self.fileInput.dispatchEvent(event);
				}
			});

			// File input change - use native addEventListener
			this.fileInput.addEventListener('change', function(e) {
				e.stopPropagation();
				const file = e.target.files[0];
				if (file) {
					self.handleFile(file);
				}
			});

			// Drag and drop events - use native addEventListener
			this.dropzone.addEventListener('dragover', function(e) {
				e.preventDefault();
				e.stopPropagation();
				this.classList.add('bdt-dragover');
			});

			this.dropzone.addEventListener('dragleave', function(e) {
				e.preventDefault();
				e.stopPropagation();
				this.classList.remove('bdt-dragover');
			});

			this.dropzone.addEventListener('drop', function(e) {
				e.preventDefault();
				e.stopPropagation();
				this.classList.remove('bdt-dragover');

				const files = e.dataTransfer.files;
				if (files.length > 0) {
					self.handleFile(files[0]);
				}
			});
		},

		handleFile: function(file) {
			// Validate file type
			if (file.type !== 'application/zip' && 
				file.type !== 'application/x-zip-compressed' &&
				!file.name.endsWith('.zip')) {
				this.showError(bdtCustomIcons.strings.invalidFile);
				return;
			}

			this.uploadFile(file);
		},

		uploadFile: function(file) {
			const self = this;
			const formData = new FormData();

			formData.append('action', 'bdt_custom_icon_upload');
			formData.append('nonce', bdtCustomIcons.nonce);
			formData.append('post_id', bdtCustomIcons.postId);
			formData.append('file', file);

			// Show progress
			this.dropzone.classList.add('loading');
			this.progressArea.style.display = 'block';
			this.progressFill.style.width = '0%';
			this.progressText.textContent = bdtCustomIcons.strings.uploading;

			$.ajax({
				url: bdtCustomIcons.ajaxUrl,
				type: 'POST',
				data: formData,
				processData: false,
				contentType: false,
				xhr: function() {
					const xhr = new window.XMLHttpRequest();
					
					// Upload progress
					xhr.upload.addEventListener('progress', function(e) {
						if (e.lengthComputable) {
							const percentComplete = (e.loaded / e.total) * 50; // 50% for upload
							self.progressFill.style.width = percentComplete + '%';
						}
					}, false);

					return xhr;
				},
				success: function(response) {
					self.progressFill.style.width = '75%';
					self.progressText.textContent = bdtCustomIcons.strings.processing;

					if (response.success && response.data.config) {
						self.handleSuccess(response.data.config);
					} else {
						self.handleError(response.data.message || bdtCustomIcons.strings.error);
					}
				},
				error: function(xhr, status, error) {
					self.handleError(bdtCustomIcons.strings.error + ': ' + error);
				}
			});
		},

		handleSuccess: function(config) {
			const self = this;

			// Complete progress
			this.progressFill.style.width = '100%';
			this.progressText.textContent = bdtCustomIcons.strings.success;

			// Update config input
			this.configInput.value = JSON.stringify(config);

			// Show success message
			this.showSuccess(bdtCustomIcons.strings.uploadComplete || 'Icon set uploaded successfully! Please click "Publish" or "Update" to save.');

			// Check for duplicate prefix warning
			if (config.duplicate_prefix) {
				this.showWarning(bdtCustomIcons.strings.duplicatePrefix);
			}

			// Hide progress after delay
			setTimeout(function() {
				self.progressArea.style.display = 'none';
				self.dropzone.classList.remove('loading');
				
				// Update the dropzone to show icon set info
				self.updateDropzoneInfo(config);
			}, 1500);
		},

		handleError: function(message) {
			this.progressArea.style.display = 'none';
			this.dropzone.classList.remove('loading');
			this.showError(message);
			
			// Reset file input
			this.fileInput.value = '';
		},

		showSuccess: function(message) {
			this.showMessage(message, 'success');
		},

		showError: function(message) {
			this.showMessage(message, 'error');
		},

		showWarning: function(message) {
			this.showMessage(message, 'warning');
		},

		showMessage: function(message, type) {
			const messageEl = document.createElement('div');
			messageEl.className = 'bdt-message ' + type;
			messageEl.textContent = message;

			const uploadArea = document.querySelector('.bdt-icon-upload-area');
			uploadArea.parentNode.insertBefore(messageEl, uploadArea);

			setTimeout(function() {
				messageEl.style.opacity = '0';
				messageEl.style.transition = 'opacity 0.3s';
				setTimeout(function() {
					messageEl.remove();
				}, 300);
			}, 5000);
		},

		triggerPostUpdate: function() {
			// Programmatically trigger WordPress save
			// This will save the config to post meta
			if (typeof wp !== 'undefined' && wp.autosave) {
				wp.autosave.server.triggerSave();
			}
		},

		updateDropzoneInfo: function(config) {
			// Update the dropzone to show icon set information
			const infoHtml = `
				<div class="bdt-icon-set-info">
					<h3>${config.label || config.name}</h3>
					<p><strong>Type:</strong> ${config.type}</p>
					<p><strong>Prefix:</strong> ${config.prefix}</p>
					<p><strong>Icons:</strong> ${config.count}</p>
					<p><strong>Version:</strong> ${config.ver || 'N/A'}</p>
					<p class="bdt-upload-another">
						<button type="button" class="button" onclick="document.getElementById('bdt-icon-file-input').click()">Upload Different Icon Set</button>
					</p>
				</div>
			`;
			
			this.dropzone.innerHTML = infoHtml;
			this.dropzone.classList.add('has-icon-set');
		}
	};

	// Initialize on document ready
	$(document).ready(function() {
		BDTCustomIcons.init();
	});

})(jQuery);
