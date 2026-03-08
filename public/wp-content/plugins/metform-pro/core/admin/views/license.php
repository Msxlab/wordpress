<?php

defined( 'ABSPATH' ) || exit;

$license_status = \MetForm_Pro\Libs\License::instance()->status();

?>
<div class="metform-wrap">
	<div class="metform-admin-container">
		<?php if ($license_status == 'valid') : ?>
		<!-- Two Column Layout for Valid License -->
		<div class="metform-license-dashboard">
			<div class="license-main-content">
				<div class="attr-card-body list-item">
					<h2 class="license-setting-title">License Settings</h2>

					<form action="" method="post"
						class="form-group attr-input-group mf-admin-input-text mf-admin-input-text--metform-license-key">
						<!-- License Activated Success State -->
						<div id="metform-sites-notice-id-license-status"
							class="metform-notice notice metform-active-notice notice-success" dismissible-meta="user">
							<p><?php esc_html_e('Your License Is Activated', 'metform-pro') ?></p>
						</div>

						<!-- License Management Actions -->
						<div class="attr-revoke-btn-container">
							<div class="license-actions-header">
								<h4>License Management</h4>
								<p>Manage your license activation for this domain</p>
							</div>

							<div class="license-action-buttons">
								<?php if(method_exists('\MetForm\Utils\Util', 'is_top_tier') && !\MetForm\Utils\Util::is_top_tier()) : ?>
									<form action="" method="post" class="license-action-form">
										<input type="hidden" name="metform-pro-settings-page-key"
											value="<?php echo esc_attr(\MetForm_Pro\Libs\License::instance()->get_license()); ?>">
										<input type="hidden" name="metform-pro-settings-page-action" value="reactivate">
										<?php wp_nonce_field('metform-pro-settings-page', 'metform-pro-settings-page'); ?>
										<button type="submit" class="button license-reactivate-btn">
											<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
												viewBox="0 0 24 24" fill="none">
												<path d="M1 4v6h6M23 20v-6h-6" stroke="currentColor" stroke-width="2"
													stroke-linecap="round" stroke-linejoin="round" />
												<path
													d="M20.49 9A9 9 0 0 0 5.64 5.64L1 10m22 4l-4.64 4.36A9 9 0 0 1 3.51 15"
													stroke="currentColor" stroke-width="2" stroke-linecap="round"
													stroke-linejoin="round" />
											</svg>
											Re-activate License
										</button>
									</form>
								<?php endif; ?>

								<form action="" method="post" class="license-action-form">
									<input type="hidden" name="metform-pro-settings-page-action" value="deactivate">
									<?php wp_nonce_field('metform-pro-settings-page', 'metform-pro-settings-page'); ?>
									<button type="submit" class="button license-remove-btn">
										<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none">
											<path
												d="M1 3.667h12M11.667 3.667V13a1.333 1.333 0 0 1-1.333 1.333H3.667A1.333 1.333 0 0 1 2.334 13V3.667m2 0V2.333A1.333 1.333 0 0 1 5.667 1h2.667a1.333 1.333 0 0 1 1.333 1.333v1.334M5.667 7v4M8.334 7v4"
												stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
												stroke-linejoin="round"></path>
										</svg>
										Remove License
									</button>
								</form>
							</div>

							<div class="license-help-section">
								<p class="license-docs">
									<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
										fill="none">
										<circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" />
										<path d="M12 16v-4M12 8h.01" stroke="currentColor" stroke-width="2"
											stroke-linecap="round" stroke-linejoin="round" />
									</svg>
									Need help? Check our <a target="_blank"
										href="https://help.wpmet.com/docs/how-to-revoke-product-license-key/">Documentation</a>
									or <a target="_blank" href="https://wpmet.com/support-ticket">Contact Support</a>.
								</p>
							</div>
						</div>
					</form>
				</div>
			</div>

			<!-- Resources & Support Sidebar -->
			<div class="license-sidebar">
				<div class="license-resources-section">
					<h3 class="resources-section-title">Resources & Support</h3>
					<div class="license-resources-grid">
						<div class="resource-card tutorial-card">
							<div class="resource-icon">
								<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
									fill="none">
									<polygon points="5,3 19,12 5,21" stroke="#F59E0B" stroke-width="2" fill="#F59E0B" />
								</svg>
							</div>
							<div class="resource-content">
								<h4>Video Tutorials</h4>
								<p>Learn the step by step process for developing your site easily from video tutorials.
								</p>
								<a href="https://www.youtube.com/c/Wpmet/videos" target="_blank"
									class="resource-link">Watch Videos <svg xmlns="http://www.w3.org/2000/svg"
										width="16" height="16" fill="currentColor" class="bi bi-arrow-right"
										viewBox="0 0 16 16">
										<path fill-rule="evenodd"
											d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8" />
									</svg></a>
							</div>
						</div>

						<div class="resource-card community-card">
							<div class="resource-icon">
								<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
									fill="none">
									<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" stroke="#10B981"
										stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
									<circle cx="9" cy="7" r="4" stroke="#10B981" stroke-width="2" stroke-linecap="round"
										stroke-linejoin="round" />
									<path d="M23 21v-2a4 4 0 0 0-3-3.87" stroke="#10B981" stroke-width="2"
										stroke-linecap="round" stroke-linejoin="round" />
									<path d="M16 3.13a4 4 0 0 1 0 7.75" stroke="#10B981" stroke-width="2"
										stroke-linecap="round" stroke-linejoin="round" />
								</svg>
							</div>
							<div class="resource-content">
								<h4>Join the Community</h4>
								<p>Join our Facebook group to get 20% discount coupon on premium products. Follow us to
									get more exciting offers.</p>
								<a href="https://www.facebook.com/groups/wpmet" target="_blank"
									class="resource-link">Join Community <svg xmlns="http://www.w3.org/2000/svg"
										width="16" height="16" fill="currentColor" class="bi bi-arrow-right"
										viewBox="0 0 16 16">
										<path fill-rule="evenodd"
											d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8" />
									</svg></a>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>

		<?php else : ?>
		<!-- Single Column Layout for Invalid License -->
		<div class="attr-card-body list-item mf-max-width-800">
			<h2 class="license-setting-title v2">License Settings</h2>
			<form action="" method="post"
				class="form-group attr-input-group mf-admin-input-text mf-admin-input-text--metform-license-key">
				<p class="license-title v1">Enter your license key here to activate MetForm Pro. It will enable update notice and auto updates.</p>

				<ol class="license-info-lists">
					<li><span class="pointer">1</span> Log in to your Wpmet account to get the license key.</li>
					<li><span class="pointer">2</span> If you don't yet buy this product, get <a
							href="https://wpmet.com/metform-pricing/" target="_blank">MetForm Pro</a> now.</li>
					<li> <span class="pointer">3</span> Copy the MetForm Pro license key from your account and paste it
						below.</li>
				</ol>

				<label for="mf-admin-option-text-metform-license-key"
					style="margin-bottom: 8px; display:inline-block"><b>Your License Key</b></label><br />
				<input type="text" class="attr-form-control license-input" id="mf-admin-option-text-metform-license-key"
					placeholder="Please insert your license key here" name="metform-pro-settings-page-key" value="">
				<span class="attr-input-group-btn">
					<input class="license-input" type="hidden" name="metform-pro-settings-page-action" value="activate">
					<button class="button license-btn" type="submit" id="license-activate-btn" disabled>
						<div class="mf-spinner"></div>Activate
					</button>
				</span>

				<div class="metform-license-form-result">
					<p class="attr-alert attr-alert-info license-alert">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24"
							color="#000000" fill="none">
							<path
								d="M22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22C17.5228 22 22 17.5228 22 12Z"
								stroke="#000000" stroke-width="1.5" />
							<path
								d="M12.2422 17V12C12.2422 11.5286 12.2422 11.2929 12.0957 11.1464C11.9493 11 11.7136 11 11.2422 11"
								stroke="#000000" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
							<path d="M11.992 8H12.001" stroke="#000000" stroke-width="1.5" stroke-linecap="round"
								stroke-linejoin="round" />
						</svg> Still can't find your licence key? <a target="_blank"
							href="https://wpmet.com/support-ticket">Knock us here!</a>
					</p>
				</div>

				<?php wp_nonce_field('metform-pro-settings-page', 'metform-pro-settings-page'); ?>
			</form>
		</div>
		<?php endif; ?>
	</div>
</div>

<script type="text/javascript">
(function() {
	// Enable/disable license button based on input value
	const licenseInput = document.getElementById('mf-admin-option-text-metform-license-key');
	const licenseBtn = document.getElementById('license-activate-btn');
	
	if (licenseInput && licenseBtn) {
		// Check initial state
		licenseBtn.disabled = licenseInput.value.trim() === '';
		
		// Add event listener for input changes
		licenseInput.addEventListener('input', function() {
			const hasValue = this.value.trim() !== '';
			licenseBtn.disabled = !hasValue;
			
			// Add visual feedback
			if (hasValue) {
				licenseBtn.style.opacity = '1';
				licenseBtn.style.cursor = 'pointer';
			} else {
				licenseBtn.style.opacity = '0.6';
				licenseBtn.style.cursor = 'not-allowed';
			}
		});
		
		// Set initial visual state
		if (licenseBtn.disabled) {
			licenseBtn.style.opacity = '0.6';
			licenseBtn.style.cursor = 'not-allowed';
		}
	}
})();
</script>