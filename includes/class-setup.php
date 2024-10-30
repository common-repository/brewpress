<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'BrewPress_Setup', false ) ) :

/**
 * BrewPress_Setup Class.
 */
class BrewPress_Setup {

	/**
	 * Main constructor
	 *
	 * @since 1.0.0
	 *
	 */
	public function __construct() {

		add_action( 'template_redirect', array( $this, 'force_login' ) );

		add_action( 'init', array( $this, 'disable_wp_emojicons' ) );
		add_filter( 'show_admin_bar', '__return_false' );

		/**
		 * Clean up wp_head()
		 * 
		 */
		remove_action( 'wp_head', 'wp_generator' );
		remove_action( 'wp_head', 'rsd_link' );
		remove_action( 'wp_head', 'wlwmanifest_link' );
		remove_action( 'wp_head', 'index_rel_link' );
		remove_action( 'wp_head', 'feed_links', 2 );
		remove_action( 'wp_head', 'feed_links_extra', 3 );
		remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head', 10, 0 );
		remove_action( 'wp_head', 'wp_shortlink_wp_head', 10, 0 );
		remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
		remove_action( 'wp_head', 'rest_output_link_wp_head' );
		remove_action( 'wp_head', 'wp_resource_hints', 2 );

		remove_action( 'wp_head', 'rel_canonical' );



	}

	public function force_login() {
		if ( ! is_user_logged_in() && is_brewpress_page() ){
			auth_redirect();
		}
	}


	/**
	 * Disable emojis
	 *
	 */
	public function disable_wp_emojicons() {

	    remove_action( 'admin_print_styles', 'print_emoji_styles' );
	    remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	    remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	    remove_action( 'wp_print_styles', 'print_emoji_styles' );
	    remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
	    remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
	    remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );

	}


}

endif;

return new BrewPress_Setup();
