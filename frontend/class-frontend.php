<?php
// Exit if accessed directly
if ( ! defined('ABSPATH') ) { exit; }



class BrewPress_Frontend {


	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'styles' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'scripts' ) );

		add_filter( 'cmb2_localized_data', array( $this, 'cmb2' ) );
		add_filter( 'cmb2_enqueue_css', array( $this, 'disable_cmb2_front_end_styles' ) );

		add_action( 'brewpress_message', array( $this, 'message' ) );

		add_filter( 'single_template', array( $this, 'batch_template' ), 999 );
	}


	/**
	 * Styles
	 */
	public function styles() {
		$url = BREWPRESS_URL;
		$v = brewpress()->version;

		wp_enqueue_style( 'fontawesome', 'https://use.fontawesome.com/releases/v5.1.1/css/all.css');
		wp_enqueue_style( 'bootstrap', $url . 'assets/css/bootstrap.min.css');
		wp_enqueue_style( 'select2', $url . 'assets/css/select2.min.css');
		wp_enqueue_style( 'brewpress', $url . 'assets/css/brewpress.css');
	}

	/**
	 * Scripts
	 */
	public function scripts() {

		$url 	= BREWPRESS_URL;
		$v 		= brewpress()->version;

		wp_enqueue_script('jquery');

		if( is_singular('batch') ) {
			wp_enqueue_script( 'chart', $url . 'assets/js/chart.bundle.min.js', array( 'jquery' ), $v, false );
			wp_enqueue_script( 'chart-annotate', $url . 'assets/js/chartjs-plugin-annotation.min.js', array( 'jquery' ), $v, false );
		}

		if( is_page('edit-batch') || is_page('new-batch') ) {
			wp_enqueue_script( 'cmb2-conditionals', $url . 'includes/lib/cmb2-conditionals/cmb2-conditionals.js', array('jquery', 'cmb2-scripts'), $v, true);
		}

		wp_enqueue_script( 'bootstrap', $url . 'assets/js/bootstrap.bundle.min.js', array('jquery'), $v, true);
		wp_enqueue_script( 'confirm', $url . 'assets/js/bootstrap-confirmation.js', array('jquery'), $v, true);
        wp_enqueue_script( 'select2', $url . 'assets/js/select2.min.js', array( 'jquery' ), $v, true );	
        wp_enqueue_script( 'timer', $url . 'assets/js/easytimer.min.js', array( 'jquery' ), $v, true );	

		if( is_page('brewing') ) {
			wp_enqueue_script( 'brewing', $url . 'assets/js/brewpress-brewing.js', array( 'jquery', 'bootstrap', 'confirm', 'select2', 'timer' ), $v, true);
		}
		
		$batch_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : false;
		$options = array( 
			'home_url' 			=> home_url(),
			'ajax_url' 			=> admin_url( 'admin-ajax.php' ),
			'nonce' 			=> wp_create_nonce( 'brewpress_nonce' ),
			'debug' 			=> brewpress_debug(),
			'user' 				=> get_current_user_id(),
			'current_time' 		=> current_time('timestamp'),
			'current_time_gmt' 	=> current_time('timestamp',true),
			'all_steps' 		=> brewpress_get_batch_steps( $batch_id ),
			'sse_query_string' 	=> brewpress_get_sse_query_string( $batch_id ),
			'words'				=> array(
				'step' 				=> __( 'Step', 'brewpress' ),
				'not_started' 		=> __( 'Not Started', 'brewpress' ),
				'manual_mode' 		=> __( 'Manual Mode', 'brewpress' ),
				'manual_mode_alert' => sprintf( '<div class="alert alert-warning"><i class="fa fa-exclamation-triangle"></i> %s</div>', __( 'The element is in Manual Mode for this step. Click here to go back to Program Mode.', 'brewpress' ) ),
			)
		);
		
		wp_localize_script( 'brewing', 'brewpress_l10', $options );

		wp_enqueue_script( 'brewpress', $url . 'assets/js/brewpress.js', array( 'jquery' ), $v, true );

	}


	/**
	 * Display a message
	 *
	 * @since   1.0.0
	 */
	public function message( $message, $type = 'success' ) {
		$output = '';
		$output .= '<div class="alert alert-' . esc_attr( $type ). ' alert-dismissible">';
		$output .= esc_html( $message );
		$output .= '<button type="button" class="close" data-dismiss="alert"><i class="fa fa-times"></i></button>';
		$output .= '</div>';

		echo $output;
	}


	/**
	 * Disable CMB2 styles on front end forms.
	 *
	 * @return bool $enabled Whether to enable (enqueue) styles.
	 */
	function disable_cmb2_front_end_styles( $enabled ) {
		if ( ! is_admin() ) {
			$enabled = false;
		}
		return $enabled;
	}


	function cmb2( $l10n ) {
		$l10n['up_arrow_class'] = 'fa fa-angle-up';
		$l10n['down_arrow_class'] = 'fa fa-angle-down';
		return $l10n;
	}


	/**
	 * Set up the template for the brew and quote.
	 *
	 * @since   1.0.0
	 */
	public function batch_template( $template ) {

		if ( get_post_type() == 'batch' ) {
			if ( ! post_password_required() ) {
				global $post;
				$template = $this->get_template_part( 'batch' );
			}
		}

		return $template;
	}


	/**
	 * Retrieves a template part for displaying batches
	 *
	 * @since   1.0.0
	 */
	private function get_template_part( $slug ) {
		// Execute code for this part
		do_action( 'get_template_part_' . $slug, $slug );
		$template = $slug . '.php';
		// Allow template parts to be filtered
		$template = apply_filters( 'brewpress_get_template_part', $template, $slug );
		// Return the part that is found
		return $this->locate_template( $template );
	}


    /**
	 * Retrieve the name of the highest priority template file that exists.
	 *
	 * @since   1.0.0
	 */
	private function locate_template( $template_name ) {
		
		// No file found yet
		$located = false;
		// Trim off any slashes from the template name
		$template_name = ltrim( $template_name, '/' );
		if ( file_exists( BREWPRESS_DIR ) . 'frontend/pages/' .  $template_name ) {
			$located = BREWPRESS_DIR . 'frontend/pages/' .  $template_name;
		}
		$located = apply_filters( 'brewpress_locate_new_templates', $located, $template_name );
		return $located;

	}

}


return new BrewPress_Frontend();