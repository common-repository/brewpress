<?php
/**
 * Plugin Name: BrewPress
 * Description: A WordPress plugin that brews beer.
 * Author: BrewPress
 * Author URI: BrewPress.beer
 * Version: 1.0.4
 * Text Domain: 'brewpress'
 * Domain Path: /languages
 * License: GPL2 or later
 * Donate link: https://paypal.me/brewpress
 *
 */


// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

/*
 * Helper function for quick debugging
 */
if (!function_exists('pp')) {
	function pp( $array ) {
		echo '<pre style="white-space:pre-wrap;">';
			print_r( $array );
		echo '</pre>' . "\n";
	}
}

/**
 * Main Class.
 *
 */
final class BrewPress {

	/**
	 * @var The one true instance
	 * @since 1.0.0
	 */
	protected static $_instance = null;

	public $version = '1.0.4';

	/**
	 * Main Instance.
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Throw error on object clone.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @return void
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'brewpress' ), '1.0.0' );
	}

	/**
	 * Disable unserializing of the class.
	 * @since 1.0.0
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'brewpress' ), '1.0.0' );
	}

	/**
	 * 
	 * @since 1.0.0
	 */
	public function __construct() {

		$this->define_constants();
		$this->includes();
		$this->init_hooks();

		do_action( 'brewpress_loaded' );
	}

	/**
	 * Hook into actions and filters.
	 * @since  1.0.0
	 */
	private function init_hooks() {
		add_action( 'init', array( $this, 'init' ), 0 );
	}

	/**
	 * Define Constants.
	 * @since  1.0.0
	 */
	private function define_constants() {
		$this->define( 'BREWPRESS_PLUGIN_FILE', __FILE__ );// Plugin Root File.
		$this->define( 'BREWPRESS_DIR',plugin_dir_path( __FILE__ ) );
		$this->define( 'BREWPRESS_URL',plugin_dir_url( __FILE__ ) );
		$this->define( 'BREWPRESS_BASENAME', plugin_basename( __FILE__ ) );
		$this->define( 'BREWPRESS_VERSION', $this->version );
		
	}

	/**
	 * Define constant if not already set.
	 * @since  1.0.0
	 */
	private function define( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}


	/**
	 * Include required files.
	 * @since  1.0.0
	 */
	public function includes() {

		// includes
		include_once( 'includes/lib/cmb2/init.php' );
		include_once( 'includes/lib/cmb2-conditionals/cmb2-conditionals.php' );
		
		include_once( 'includes/class-setup.php' );
		include_once( 'includes/class-post-types.php' );
		include_once( 'includes/class-menus.php' );
				
		// frontend
		include_once( 'frontend/class-frontend.php' );
		include_once( 'frontend/class-duplicate.php' );
		
		include_once( 'frontend/template-tags.php' );
		include_once( 'frontend/functions.php' );

		include_once( 'frontend/batch/class-batch-new.php' );
		include_once( 'frontend/batch/class-batch-edit.php' );
		include_once( 'frontend/batch/class-batch-view-all.php' );
		include_once( 'frontend/batch/class-batch-output.php' );

		include_once( 'frontend/pages/dashboard.php' );
		include_once( 'frontend/pages/settings.php' );
		include_once( 'frontend/pages/brewing.php' );

        include_once( 'frontend/brewing/class-brewing.php' );
        include_once( 'frontend/brewing/class-switching.php' );
		

		include_once( 'includes/install.php' );

	}

	/**
	 * Init when WordPress Initialises.
	 * @since 1.0.0
	 */
	public function init() {
		// Before init action.
		do_action( 'before_brewpress_init' );
		// Set up localisation.
		$this->load_plugin_textdomain();
	
		// Init action.
		do_action( 'brewpress_init' );
	}

	/**
	 * Load Localisation files.
	 * @since  1.0.0
	 */
	public function load_plugin_textdomain() {
		$locale = apply_filters( 'plugin_locale', get_locale(), 'brewpress' );

		load_textdomain( 'brewpress', WP_LANG_DIR . '/brewpress-' . $locale . '.mo' );
		load_plugin_textdomain( 'brewpress', false, plugin_basename( dirname( __FILE__ ) ) . '/languages' );

	}


}


/**
 * Run the plugin.
 */
function brewpress() {
	return BrewPress::instance();
}
brewpress();