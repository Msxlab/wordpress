<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

//return if user is not logged in
if ( ! is_user_logged_in() ) {
    return;
}

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<?php if ( ! current_theme_supports( 'title-tag' ) ) : ?>
		<title><?php echo wp_get_document_title(); ?></title>
	<?php endif; ?>
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
		<?php

		if (class_exists('\Elementor\Plugin')) {
			\Elementor\Plugin::$instance->modules_manager->get_modules( 'page-templates' )->print_content();
		} else {
			// Fallback for when Elementor is not active
			if (have_posts()) :
				while (have_posts()) : the_post();
					the_content();
				endwhile;
			endif;
		}
		
		wp_footer();
		?>	
	</body>
	<script>
            document.documentElement.style.overflow = "hidden";
            document.documentElement.style.height = "fit-content";
            document.body.style.height = "fit-content";

            function sendIframeHeight() {
                const height = document.documentElement.scrollHeight;
                const message = {
                    type: "iframe_height",
                    height: height,
                };
                window.parent.postMessage(JSON.parse(JSON.stringify(message)), "*");
                document.documentElement.style.overflow = "hidden";
            }

            /**
             * Wait for the swiper element to be initialized.
             * If the element exists, poll until it has the "swiper-initialized" class.
             * Otherwise, wait until the window load event.
             */
            function waitForSwiperInitialization(callback) {
                const swiperEl = document.querySelector(".swiper");

                if (swiperEl) {
                    // Poll every 50ms until the swiper element has been initialized
                    const interval = setInterval(() => {
                        if (swiperEl.classList.contains("swiper-initialized")) {
                            clearInterval(interval);
                            callback();
                        }
                    }, 50);
                } else {
                    // If no swiper is found, wait for the window load event.
                    window.addEventListener("load", callback());
                }
            }

            waitForSwiperInitialization(sendIframeHeight);
        </script>
</html>
