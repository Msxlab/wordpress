<?php
namespace UiCore\ThemeBuilder;

use UiCore\AdminCustomizer;

defined('ABSPATH') || exit();

/**
 * Custom Dashboard generic functions
 *
 * @author Andrei Voica <andrei@uicore.co
 * @since 6.2.2
 */
class CustomDashboard
{
	/** Capability required to view the custom dashboard */
	const CAPABILITY = 'manage_options';

	/** Slug for the hidden admin page */
	private $wp_cd_slug = 'cdash';

    /**
     * Construct Custom Dashboard generic functions
     *
     * @author Andrei Voica <andrei@uicore.co
     * @since 6.2.2
     */
    public function __construct()
    {

        // register ctp
        add_action('init', [$this, 'register_ctp']);

        // change robots data
        add_filter( 'wp_robots', function( $robots ) {
            if ( is_singular( 'uicore-cd' )  ) {
                $robots['noindex'] = true;
            }
            return $robots;
        } );

		//wp custom dashboard
        if(is_admin()) {
			$wp_dash_enabled = AdminCustomizer::get_instance()->get_prop('wp_custom_dash') === 'true';
			$admin_customizer_enabled = AdminCustomizer::get_instance()->get_prop('admin_customizer') === 'true';

			//return if both are not true
			if (!$wp_dash_enabled || !$admin_customizer_enabled) {
				return;
			}

			// Register the custom dashboard page
			add_action( 'admin_menu', [ $this, 'register_page' ] );
			//ensure posts exists and set the page ID
			// self::ensure_posts_exists();

            // Register hidden page & tweak menu labels.
            add_action( 'admin_menu', [ $this, 'register_page' ] );

            // Redirect visits to /wp-admin/index.php.
            add_action( 'load-index.php', [ $this, 'redirect_dashboard' ] );

            // Hide the menu item
            add_action( 'admin_enqueue_scripts', function () {
                echo '<style>
                    #toplevel_page_cdash {
                        display: none; /* hide the menu item */
                    }
                </style>';
            } );

        }

		// ctp is public but we don't want to show it to all users
		if ( is_singular( 'uicore-cd' ) && ! is_user_logged_in() ) {
			wp_redirect( home_url() );
			exit;
		}
        	// Front-end helpers (only fire when ?from_dashboard=1).
		add_filter( 'show_admin_bar', [ $this, 'maybe_hide_admin_bar' ] );
		add_action( 'wp_head',        [ $this, 'maybe_add_base_tag' ], 1 );
		add_filter( 'template_include', [$this, 'maybe_change_template'], 99 );



    }

	static function ensure_posts_exists() {
		// Ensure the custom dashboard posts exist
		if ( ! get_page_by_path( 'ui-cd-wp', OBJECT, 'uicore-cd' ) ) {
			wp_insert_post( [
				'post_title' => __( 'Custom WP Dashboard ', 'uicore' ),
				'post_name'  => 'ui-cd-wp',
				'post_type'  => 'uicore-cd',
				'post_status' => 'publish',
			] );
		}
		if ( ! get_page_by_path( 'ui-cd-to', OBJECT, 'uicore-cd' ) ) {
			wp_insert_post( [
				'post_title' => __( 'Custom TO Dashboard ', 'uicore' ),
				'post_name'  => 'ui-cd-to',
				'post_type'  => 'uicore-cd',
				'post_status' => 'publish',
			] );
		}
	}

	static function get_wp_dash_id() {
		// Ensure the custom dashboard posts exist
		self::ensure_posts_exists();
		// Return the ID of the custom WP dashboard page
		$post = get_page_by_path('ui-cd-wp', OBJECT, 'uicore-cd');
		return $post ? $post->ID : '';
	}

	static function get_to_dash_id() {
		// Ensure the custom dashboard posts exist
		self::ensure_posts_exists();
		// Return the ID of the custom TO dashboard page
		$post = get_page_by_path('ui-cd-to', OBJECT, 'uicore-cd');
		return $post ? $post->ID : '';
	}

    function register_ctp()
    {
        register_post_type('uicore-cd',[
            'public' => true,
            'show_in_rest' => true,
			'show_ui' => current_user_can('administrator'), // Show in admin UI
			'show_in_menu' => false, // Hides from admin menu
			'show_in_admin_bar' => false,
			'show_in_nav_menus' => false,
			'exclude_from_search' => true,
			'has_archive' => false,
			'map_meta_cap' => true,
            'label'  => __('Custom Dashboard', 'uicore'),
            'menu_icon' => 'dashicons-dashboard',
            'supports' => ['title', 'editor','elementor','revisions'],
        ]);
    }

	public function register_page() {

		add_menu_page(
			__( 'Dashboard', 'uicore-framework' ), // <title> tag
			'',                             // empty → hidden
			self::CAPABILITY,
			$this->wp_cd_slug,
			[ $this, 'render_wp_dashboard' ]
		);

		// // Keep “Dashboard” highlighted & (optionally) rename it.
		// global $menu, $submenu, $parent_file, $submenu_file;
		// $parent_file                = 'index.php';
		// $submenu_file               = 'index.php';
		// $menu[2][0]                 = __( 'Dashboard', 'uicore-framework' );
		// $submenu['index.php'][0][0] = __( 'Dashboard', 'uicore-framework' );
	}

	/**
	 * Redirects the stock dashboard early, before its widgets run.
	 */
	public function redirect_dashboard() {

		if ( ! current_user_can( self::CAPABILITY ) ) {
			return; // let lower-privilege users see normal dashboard
		}


		wp_safe_redirect( admin_url( "admin.php?page={$this->wp_cd_slug}" ), 301 );
		exit;
	}

	/**
	 * Outputs the iframe + JS router.
	 */
	public function render_wp_dashboard() {

		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( __( 'Sorry, you’re not allowed to access this page.' ) );
		}

		$src = add_query_arg( 'from_dashboard', 1, get_permalink( $this::get_wp_dash_id() ) );
		?>
		<div class="wrap">
			<h1><?php _e( 'Dashboard', 'uicore-framework' ); ?></h1>

			<iframe
				id="customdash-frame"
				src="<?php echo esc_url( $src ); ?>"
				sandbox="allow-same-origin allow-scripts allow-forms allow-popups allow-top-navigation-by-user-activation"
				style="width:100%;height:calc(100vh - 120px);border:0;">
			</iframe>
		</div>
		<!-- Smart router: wp-admin links replace parent; others open new tab -->
		<script>
		( function () {
            const dashMenuLi = document.querySelector( '#menu-dashboard' );
            dashMenuLi.classList.add( 'current' );
			const frame = document.getElementById( 'customdash-frame' );

			frame.addEventListener( 'load', () => {
				try {
					const doc = frame.contentDocument || frame.contentWindow.document;

					doc.addEventListener( 'click', e => {
						const a = e.target.closest( 'a[href]' );
						if ( ! a ) return;

						// ignore middle-click, ctrl/cmd-click etc.
						if ( e.defaultPrevented || e.metaKey || e.ctrlKey ) return;

						const url = new URL( a.href, location.origin );

						// INTERNAL admin link → replace wp-admin
						if ( url.pathname.includes( '/wp-admin/' ) ) {
							e.preventDefault();
							window.location = url.href;
							return;
						}

						// Everything else → new tab
						e.preventDefault();
						window.open( url.href, '_blank', 'noopener' );
					} );
				} catch ( err ) {
					// Cross-origin: sandbox blocks navigation anyway.
				}
			} );
			window.addEventListener('message', () => {
				if (event.data.type === "iframe_height") {
					const height = event.data.height;
					frame.style.height = height + 'px';
				}
			});
		} )();
		</script>
		<?php
	}

	static function render_to_dashboard(){
		$src = add_query_arg( 'from_dashboard', 1, get_permalink( self::get_to_dash_id() ) );
		\ob_start();
		?>
		<iframe
			id="customdash-frame"
			src="<?php echo esc_url( $src ); ?>"
			sandbox="allow-same-origin allow-scripts allow-forms allow-popups allow-top-navigation-by-user-activation"
			style="width:100%;height:calc(100vh - 120px);border:0;">
		</iframe>
		<!-- Smart router: wp-admin links replace parent; others open new tab -->
		<script>
		( function () {
			const frame = document.getElementById( 'customdash-frame' );

			frame.addEventListener( 'load', () => {
				console. log('Custom Dashboard iframe loaded');
				try {
					const doc = frame.contentDocument || frame.contentWindow.document;

					doc.addEventListener( 'click', e => {
						const a = e.target.closest( 'a[href]' );
						if ( ! a ) return;

						// ignore middle-click, ctrl/cmd-click etc.
						if ( e.defaultPrevented || e.metaKey || e.ctrlKey ) return;

						const url = new URL( a.href, location.origin );

						// INTERNAL admin link → replace wp-admin
						if ( url.pathname.includes( '/wp-admin/' ) ) {
							e.preventDefault();
							window.location = url.href;
							return;
						}

						// Everything else → new tab
						e.preventDefault();
						window.open( url.href, '_blank', 'noopener' );
					} );
				} catch ( err ) {
					// Cross-origin: sandbox blocks navigation anyway.
				}
			} );
			window.addEventListener('message', () => {
				console.log('Received message from iframe');
				if (event.data.type === "iframe_height") {
					const height = event.data.height;
					frame.style.height = height + 'px';
				}
			});
		} )();
		</script>
		<?php
		return \ob_get_clean();
	}

	/* ------- FRONT-END PAGE HELPERS ------- */

	/**
	 * Hides the WP admin bar on the front-end page *inside* the iframe.
	 */
	public function maybe_hide_admin_bar( $show ) {
		return isset( $_GET['from_dashboard'] ) ? false : $show;
	}

	/**
	 * Adds <base target="_parent"> so default clicks leave the iframe.
	 * Editors can override with “Open in new tab” (target="_blank").
	 */
	public function maybe_add_base_tag() {
		if ( isset( $_GET['from_dashboard'] ) ) {
			echo '<base target="_parent">';
		}
	}
    /**
     * Changes the template to the custom dashboard page.
     */
    public function maybe_change_template( $template ) {
        if ( isset( $_GET['from_dashboard'] ) ) {
            $template = \UICORE_INCLUDES . '/admin-customizer/custom-dashboard-template.php';
        }
        return $template;
    }

}
new CustomDashboard();
