<?php
/**
 * Core setup, site hooks and filters.
 *
 * @package Maverick\Core
 */

namespace Maverick\Core;

use function Maverick\hex_to_hsl;
use function Maverick\load_inline_svg;

/**
 * Set up theme defaults and register supported WordPress features.
 *
 * @return void
 */
function setup() {
	$n = function( $function ) {
		return __NAMESPACE__ . "\\$function";
	};

	add_action( 'init', $n( 'init' ) );
	add_action( 'after_setup_theme', $n( 'i18n' ) );
	add_action( 'after_setup_theme', $n( 'theme_setup' ) );
	add_action( 'wp_enqueue_scripts', $n( 'scripts' ) );
	add_action( 'wp_enqueue_scripts', $n( 'styles' ) );
	add_action( 'admin_init', $n( 'editor_styles' ) );
	add_action( 'wp_head', $n( 'js_detection' ), 0 );

	add_filter( 'script_loader_tag', $n( 'script_loader_tag' ), 10, 2 );
	add_filter( 'body_class', $n( 'body_classes' ) );
	add_filter( 'nav_menu_item_title', $n( 'add_dropdown_icons' ), 10, 4 );
}

/**
 * Runs code on init hook
 *
 * @return void
 */
function init() {
	remove_post_type_support( 'page', 'thumbnail' );
}

/**
 * Makes Theme available for translation.
 *
 * Translations can be added to the /languages directory.
 * If you're building a theme based on "godaddy", change the
 * filename of '/languages/maverick.pot' to the name of your project.
 *
 * @return void
 */
function i18n() {
	load_theme_textdomain( 'maverick', MAVERICK_PATH . '/languages' );
}

/**
 * Sets up theme defaults and registers support for various WordPress features.
 */
function theme_setup() {
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );

	add_theme_support(
		'html5',
		[
			'search-form',
			'gallery',
		]
	);
	add_theme_support( 'disable-custom-colors' );
	add_theme_support( 'disable-custom-font-sizes' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'editor-styles' );
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'woocommerce' );

	$custom_logo_defaults = [
		'flex-height' => true,
		'flex-width'  => true,
	];
	add_theme_support( 'custom-logo', $custom_logo_defaults );

	// This theme uses wp_nav_menu() in three locations.
	register_nav_menus(
		array(
			'primary'  => esc_html__( 'Primary Menu', 'maverick' ),
			'footer-1' => esc_html__( 'Footer Menu #1 (Primary)', 'maverick' ),
			'footer-2' => esc_html__( 'Footer Menu #2', 'maverick' ),
			'footer-3' => esc_html__( 'Footer Menu #3', 'maverick' ),
		)
	);
}

/**
 * Enqueue scripts for front-end.
 *
 * @return void
 */
function scripts() {

	wp_enqueue_script(
		'frontend',
		MAVERICK_TEMPLATE_URL . '/dist/js/frontend.js',
		[],
		MAVERICK_VERSION,
		true
	);

	wp_localize_script(
		'frontend',
		'MaverickText',
		[
			'searchLabel' => esc_html__( 'Expand search field', 'maverick' ),
		]
	);
}

/**
 * Enqueues the editor styles.
 *
 * @return void
 */
function editor_styles() {
	// Enqueue our shared Gutenberg editor styles.
	add_editor_style(
		'dist/css/editor-style.css'
	);

	$design_style = get_design_style();

	if ( $design_style && isset( $design_style['editor_style'] ) ) {
		add_editor_style(
			$design_style['editor_style']
		);
	}
}

/**
 * Enqueue styles for front-end.
 *
 * @return void
 */
function styles() {

	wp_enqueue_style(
		'styles',
		MAVERICK_TEMPLATE_URL . '/dist/css/shared-style.css',
		[],
		MAVERICK_VERSION
	);

	$design_style = get_design_style();

	if ( $design_style ) {
		wp_enqueue_style(
			'design-style',
			$design_style['url'],
			[ 'styles' ],
			MAVERICK_VERSION
		);
	}
}

/**
 * Handles JavaScript detection.
 *
 * Adds a `js` class to the root `<html>` element when JavaScript is detected.
 *
 * @return void
 */
function js_detection() {

	echo "<script>(function(html){html.className = html.className.replace(/\bno-js\b/,'js')})(document.documentElement);</script>\n";
}

/**
 * Add async/defer attributes to enqueued scripts that have the specified script_execution flag.
 *
 * @link https://core.trac.wordpress.org/ticket/12009
 * @param string $tag    The script tag.
 * @param string $handle The script handle.
 * @return string
 */
function script_loader_tag( $tag, $handle ) {
	$script_execution = wp_scripts()->get_data( $handle, 'script_execution' );

	if ( ! $script_execution ) {
		return $tag;
	}

	if ( 'async' !== $script_execution && 'defer' !== $script_execution ) {
		return $tag;
	}

	// Abort adding async/defer for scripts that have this script as a dependency. _doing_it_wrong()?
	foreach ( wp_scripts()->registered as $script ) {
		if ( in_array( $handle, $script->deps, true ) ) {
			return $tag;
		}
	}

	// Add the attribute if it hasn't already been added.
	if ( ! preg_match( ":\s$script_execution(=|>|\s):", $tag ) ) {
		$tag = preg_replace( ':(?=></script>):', " $script_execution", $tag, 1 );
	}

	return $tag;
}

/**
 * Add classes to body element.
 *
 * @param string|array $classes Classes to be added to body.
 * @return array
 */
function body_classes( $classes ) {
	$design_style     = get_theme_mod( 'maverick_design_style', get_default_design_style() );
	$header_variation = get_theme_mod( 'maverick_header_variation_setting', get_default_header_variation() );
	$footer_variation = get_theme_mod( 'maverick_footer_variation_setting', get_default_footer_variation() );

	// Design style variation body class.
	if ( $design_style ) {
		$classes[] = 'is-' . esc_attr( $design_style );
	}

	// Header variation body class.
	if ( $header_variation ) {
		$classes[] = 'is-' . esc_attr( $header_variation );
	}

	// Footer variation body class.
	if ( $footer_variation ) {
		$classes[] = 'is-' . esc_attr( $footer_variation );
	}

	// Add woo class whenever block is on page
	if (
		has_block( 'woocommerce/handpicked-products' )
		|| has_block( 'woocommerce/product-best-sellers' )
		|| has_block( 'woocommerce/product-category' )
		|| has_block( 'woocommerce/product-new' )
		|| has_block( 'woocommerce/product-on-sale' )
		|| has_block( 'woocommerce/product-top-rated' )
		|| has_block( 'woocommerce/products-by-attribute' )
		|| has_block( 'woocommerce/featured-product' )
	) {
		$classes[] = 'woocommerce-page';
	}

	return $classes;
}

/**
 * Returns the default design style
 *
 * @return string
 */
function get_default_design_style() {
	/**
	 * Filters the default design style.
	 *
	 * @since 0.1.0
	 *
	 * @param array $default_design_style The slug of the default design style.
	 */
	return apply_filters( 'maverick_default_design_style', 'creative-services' );
}

/**
 * Returns the avaliable design styles.
 *
 * @return array
 */
function get_available_design_styles() {
	$default_design_styles = [
		'creative-services' => [
			'label'         => esc_html__( 'Creative Services', 'maverick' ),
			'url'           => MAVERICK_TEMPLATE_URL . '/dist/css/design-styles/creative-services.css',
			'editor_style'  => 'dist/css/design-styles/creative-services-editor.css',
			'preview_image' => 'https://via.placeholder.com/400x100.png?text=Creative+Services',
			'color_schemes' => [
				'light' => [
					'label'           => esc_html__( 'Light', 'maverick' ),
					'primary_color'   => '#ffffff',
					'secondary_color' => '#000000',
				],
				'dark'  => [
					'label'           => esc_html__( 'Dark', 'maverick' ),
					'primary_color'   => '#000000',
					'secondary_color' => '#ffffff',
				],
			],
		],
		'traditional'       => [
			'label'         => esc_html__( 'Traditional', 'maverick' ),
			'url'           => MAVERICK_TEMPLATE_URL . '/dist/css/design-styles/traditional.css',
			'editor_style'  => 'dist/css/design-styles/traditional-editor.css',
			'preview_image' => 'https://via.placeholder.com/400x100.png?text=Traditional',
			'color_schemes' => [
				'light' => [
					'label'           => esc_html__( 'Light', 'maverick' ),
					'primary_color'   => '#c76919',
					'secondary_color' => '#a0510e',
				],
				'dark'  => [
					'label'           => esc_html__( 'Dark', 'maverick' ),
					'primary_color'   => '#3f5836',
					'secondary_color' => '#293922',
				],
			],
		],
		'trendy-shop'       => [
			'label'         => esc_html__( 'Trendy Shop', 'maverick' ),
			'url'           => MAVERICK_TEMPLATE_URL . '/dist/css/design-styles/trendy-shop.css',
			'editor_style'  => 'dist/css/design-styles/trendy-shop-editor.css',
			'preview_image' => 'https://via.placeholder.com/400x100.png?text=Trendy+Shop',
			'color_schemes' => [
				'light' => [
					'label'           => esc_html__( 'Light', 'maverick' ),
					'primary_color'   => '#fcfcfc',
					'secondary_color' => '#f3f0ed',
				],
				'dark'  => [
					'label'           => esc_html__( 'Dark', 'maverick' ),
					'primary_color'   => '#f1f4f4',
					'secondary_color' => '#ebeeee',
				],
			],
		],
		'welcoming'         => [
			'label'         => esc_html__( 'Welcoming', 'maverick' ),
			'url'           => MAVERICK_TEMPLATE_URL . '/dist/css/design-styles/welcoming.css',
			'editor_style'  => 'dist/css/design-styles/welcoming-editor.css',
			'preview_image' => 'https://via.placeholder.com/400x100.png?text=Welcoming',
			'color_schemes' => [
				'light' => [
					'label'           => esc_html__( 'Light', 'maverick' ),
					'primary_color'   => '#02392f',
					'secondary_color' => '#f1f1f1',
				],
				'dark'  => [
					'label'           => esc_html__( 'Dark', 'maverick' ),
					'primary_color'   => '#49384d',
					'secondary_color' => '#f7f5e9',
				],
			],
		],
		'play'              => [
			'label'         => esc_html__( 'Play', 'maverick' ),
			'url'           => MAVERICK_TEMPLATE_URL . '/dist/css/design-styles/play.css',
			'editor_style'  => 'dist/css/design-styles/play-editor.css',
			'preview_image' => 'https://via.placeholder.com/400x100.png?text=Play',
			'color_schemes' => [
				'light' => [
					'label'           => esc_html__( 'Light', 'maverick' ),
					'primary_color'   => '#254e9c',
					'secondary_color' => '#fcae6e',
				],
				'dark'  => [
					'label'           => esc_html__( 'Dark', 'maverick' ),
					'primary_color'   => '#41b093',
					'secondary_color' => '#eecd94',
				],
			],
		],
	];

	/**
	 * Filters the supported design styles.
	 *
	 * @since 0.1.0
	 *
	 * @param array $design_styles Array containings the supported design styles,
	 * where the index is the slug of design style and value an array of options that sets up the design styles.
	 */
	$supported_design_styles = apply_filters( 'maverick_design_styles', $default_design_styles );

	return $supported_design_styles;
}

/**
 * Returns the current design style.
 *
 * @return array
 */
function get_design_style() {
	$design_style = get_theme_mod( 'maverick_design_style', get_default_design_style() );

	$supported_design_styles = get_available_design_styles();

	if ( in_array( $design_style, array_keys( $supported_design_styles ), true ) ) {
		return $supported_design_styles[ $design_style ];
	}

	return false;
}

/**
 * Returns the default design style
 *
 * @return string
 */
function get_default_header_variation() {
	/**
	 * Filters the default header variation.
	 *
	 * @since 0.1.0
	 *
	 * @param array $default_header_variation The slug of the default header variation.
	 */
	return apply_filters( 'maverick_default_header_variation', 'header-logo-nav' );
}

/**
 * Returns the avaliable header variations.
 *
 * @return array
 */
function get_available_header_variations() {
	$default_header_variations = [
		'header-logo-nav'          => [
			'label'         => esc_html__( 'Logo + Nav + Search', 'maverick' ),
			'preview_image' => MAVERICK_TEMPLATE_URL . '/assets/admin/images/header-logo-nav-search.svg',
			'partial'       => function() {
				return get_template_part( 'partials/headers/header', 'logo-nav-search' );
			},
		],
		'header-logo-nav-vertical' => [
			'label'         => esc_html__( 'Logo + Nav (Vertical)', 'maverick' ),
			'preview_image' => MAVERICK_TEMPLATE_URL . '/assets/admin/images/header-logo-nav-vertical.svg',
			'partial'       => function() {
				return get_template_part( 'partials/headers/header', 'logo-nav-vertical' );
			},
		],
		'header-nav-logo'   => [
			'label'         => esc_html__( 'Nav + Logo', 'maverick' ),
			'preview_image' => MAVERICK_TEMPLATE_URL . '/assets/admin/images/header-nav-logo.svg',
			'partial'       => function() {
				return get_template_part( 'partials/headers/header', 'nav-logo' );
			},
		],
		'header-search-logo-nav'   => [
			'label'         => esc_html__( 'Search + Logo + Nav', 'maverick' ),
			'preview_image' => MAVERICK_TEMPLATE_URL . '/assets/admin/images/header-search-logo-nav.svg',
			'partial'       => function() {
				return get_template_part( 'partials/headers/header', 'search-logo-nav' );
			},
		],
		'header-nav-logo-search'   => [
			'label'         => esc_html__( 'Nav + Logo + Search', 'maverick' ),
			'preview_image' => MAVERICK_TEMPLATE_URL . '/assets/admin/images/header-nav-logo-search.svg',
			'partial'       => function() {
				return get_template_part( 'partials/headers/header', 'nav-logo-search' );
			},
		],
	];

	/**
	 * Filters the supported header variations.
	 *
	 * @since 0.1.0
	 *
	 * @param array $header_variations Array containings the supported header variations,
	 * where the index is the slug of header variation and the value an array of options that sets up the header variation.
	 */
	$supported_header_variations = apply_filters( 'maverick_header_variations', $default_header_variations );

	return $supported_header_variations;
}

/**
 * Returns the current header variation.
 *
 * @return array
 */
function get_header_variation() {
	$selected_variation = get_theme_mod( 'maverick_header_variation_setting', get_default_header_variation() );

	$supported_header_variations = get_available_header_variations();

	if ( in_array( $selected_variation, array_keys( $supported_header_variations ), true ) ) {
		return $supported_header_variations[ $selected_variation ];
	}

	return false;
}

/**
 * Returns the avaliable footer variations.
 *
 * @return array
 */
function get_available_footer_variations() {
	$default_footer_variations = [
		'footer-1' => [
			'label'         => esc_html__( 'Footer 1', 'maverick' ),
			'preview_image' => MAVERICK_TEMPLATE_URL . '/assets/admin/images/footer-1.svg',
			'partial'       => function() {
				return get_template_part( 'partials/footers/footer', '1' );
			},
		],
		'footer-2' => [
			'label'         => esc_html__( 'Footer 2', 'maverick' ),
			'preview_image' => MAVERICK_TEMPLATE_URL . '/assets/admin/images/footer-2.svg',
			'partial'       => function() {
				return get_template_part( 'partials/footers/footer', '2' );
			},
		],
		'footer-3' => [
			'label'         => esc_html__( 'Footer 3', 'maverick' ),
			'preview_image' => MAVERICK_TEMPLATE_URL . '/assets/admin/images/footer-3.svg',
			'partial'       => function() {
				return get_template_part( 'partials/footers/footer', '3' );
			},
		],
		'footer-4' => [
			'label'         => esc_html__( 'Footer 4', 'maverick' ),
			'preview_image' => MAVERICK_TEMPLATE_URL . '/assets/admin/images/footer-4.svg',
			'partial'       => function() {
				return get_template_part( 'partials/footers/footer', '4' );
			},
		],
	];

	/**
	 * Filters the supported header variations.
	 *
	 * @since 0.1.0
	 *
	 * @param array $footer_variations Array containings the supported header variations,
	 * where the index is the slug of header variation and the value an array of options that sets up the header variation.
	 */
	$supported_footer_variations = apply_filters( 'maverick_footer_variations', $default_footer_variations );

	return $supported_footer_variations;
}

/**
 * Returns the default design style
 *
 * @return string
 */
function get_default_footer_variation() {
	/**
	 * Filters the default footer variation.
	 *
	 * @since 0.1.0
	 *
	 * @param array $default_footer_variation The slug of the default footer variation.
	 */
	return apply_filters( 'maverick_default_footer_variation', 'footer-1' );
}

/**
 * Returns the current header variation.
 *
 * @return array
 */
function get_footer_variation() {
	$selected_variation = get_theme_mod( 'maverick_footer_variation_setting', get_default_footer_variation() );

	$supported_header_variations = get_available_footer_variations();

	if ( in_array( $selected_variation, array_keys( $supported_header_variations ), true ) ) {
		return $supported_header_variations[ $selected_variation ];
	}

	return false;
}

/**
 * Returns the default value for footer blurb text
 *
 * @return string
 */
function get_default_footer_blurb_text() {
	/**
	 * Filters the default footer blurb text.
	 *
	 * @since 0.1.0
	 *
	 * @param string $default_footer_blurb_text The default text for footer blurb.
	 */
	return apply_filters( 'maverick_default_footer_blurb_text', 'Replace this with real informative text.' );
}

/**
 * Returns the default value for footer blurb text
 *
 * @return string
 */
function get_default_footer_copy_text() {
	/**
	 * Filters the default footer blurb text.
	 *
	 * @since 0.1.0
	 *
	 * @param string $default_footer_blurb_text The default text for footer blurb.
	 */
	return apply_filters( 'maverick_default_footer_copy_text', 'Copyright @ 2019 - WordPress theme by GoDaddy' );
}

/**
 * Returns the supported social icons.
 *
 * @return array
 */
function get_available_social_icons() {
	$social_icons = [
		'facebook'  => [
			'label'       => esc_html__( 'Facebook', 'maverick' ),
			'description' => esc_html__( 'Facebook URL', 'maverick' ),
			'icon'        => MAVERICK_PATH . '/assets/admin/images/facebook.svg',
			'icon_class'  => '',
		],
		'twitter'   => [
			'label'       => esc_html__( 'Twitter', 'maverick' ),
			'description' => esc_html__( 'Twitter URL', 'maverick' ),
			'icon'        => MAVERICK_PATH . '/assets/admin/images/twitter.svg',
			'icon_class'  => '',
		],
		'instagram' => [
			'label'       => esc_html__( 'Instagram', 'maverick' ),
			'description' => esc_html__( 'Instagram URL', 'maverick' ),
			'icon'        => MAVERICK_PATH . '/assets/admin/images/instagram.svg',
			'icon_class'  => '',
		],
		'linkedin'  => [
			'label'       => esc_html__( 'LinkedIn', 'maverick' ),
			'description' => esc_html__( 'LinkedIn URL', 'maverick' ),
			'icon'        => MAVERICK_PATH . '/assets/admin/images/linkedin.svg',
			'icon_class'  => '',
		],
		'pinterest' => [
			'label'       => esc_html__( 'Pinterest', 'maverick' ),
			'description' => esc_html__( 'Pinterest URL', 'maverick' ),
			'icon'        => MAVERICK_PATH . '/assets/admin/images/pinterest.svg',
			'icon_class'  => '',
		],
	];

	/**
	 * Filters the supported social icons.
	 *
	 * @since 0.1.0
	 *
	 * @param array $social_icons Array containings the supported social icons.
	 */
	return apply_filters( 'maverick_avaliable_social_icons', $social_icons );
}

/**
 * Returns the social icons data
 *
 * @return array
 */
function get_social_icons() {
	$social_icons = get_available_social_icons();

	foreach ( $social_icons as $key => &$social_icon ) {
		$social_icon['url'] = get_theme_mod( sprintf( 'maverick_footer_social_%s_setting', $key ), '' );
	}

	return $social_icons;
}

/**
 * Returns the avaliable color schemes
 *
 * @return array
 */
function get_available_color_schemes() {
	$design_style = get_design_style();

	/**
	 * Filters the avaliable color schemes
	 *
	 * @since 0.1.0
	 *
	 * @param array $color_schemes The array containing the color schemes
	 * @param array $design_style  The full design style object
	 */
	return apply_filters( 'maverick_color_schemes', $design_style['color_schemes'], $design_style );
}

/**
 * Add a dropdown icon to top-level menu items.
 *
 * @param string $title  The menu item's title.
 * @param object $item   The current menu item.
 * @param object $args   An object of wp_nav_menu() arguments.
 * @param int    $depth  Depth of menu item. Used for padding.
 * Add a dropdown icon to top-level menu items
 */
function add_dropdown_icons( $title, $item, $args, $depth ) {

	// Only add class to 'top level' items on the 'primary' menu.
	if ( 'primary' === $args->theme_location && 0 === $depth ) {
		foreach ( $item->classes as $value ) {
			if ( 'menu-item-has-children' === $value || 'page_item_has_children' === $value ) {
				$title = $title . load_inline_svg( 'arrow-down.svg' ); // phpcs:ignore;
			}
		}
	}

	return $title;
}