<?php
/**
 * @link http://webdevstudios.com/2015/03/30/use-cmb2-to-create-a-new-post-submission-form/ Original tutorial
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * BrewPress_Setup Class.
 */
class BrewPress_Frontend_New_Batch {

	/**
	 * Post type
	 * @var string
	 */
	public $post_type = 'batch';

	/**
	 * Metabox prefix
	 *
	 * @since 1.0.0
	 */
	private $pre = '_brewpress_batch_';

	/**
	 * Metabox ID
	 * @var string
	 */
	public $metabox_id = 'batch';
	
	/**
	 * Shortcode ID
	 * @var string
	 */
	public $shortcode_id = 'brewpress_new_batch';

	public $user_id = '';
	

	public function __construct() {
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'cmb2_after_init', array( $this, 'handle_new_batch_form_submission' ) );

		add_shortcode( $this->shortcode_id, array( $this, 'shortcode' ) );
	}


	public function init() {
		require_once BREWPRESS_DIR . 'frontend/batch/class-batch-form.php';
		$this->user_id = get_current_user_id();
	}

	/**
	 * Gets the front-end-post-form cmb instance
	 *
	 * @return CMB2 object
	 */
	public function get_form() {
		// Post/object ID is not applicable since we're using this form for submission
		return cmb2_get_metabox( $this->metabox_id, 'brewpress' );
	}
	/**
	 * Handle the cmb_frontend_form shortcode
	 *
	 * @param  array  $atts Array of shortcode attributes
	 * @return string       Form html
	 */
	public function shortcode( $atts = array() ) {

		
			// Get CMB2 metabox object
			$cmb = $this->get_form();

			// Initiate our output variable
			$output = '<div class="brewpress">';

			// Get any submission errors
			if ( ( $error = $cmb->prop( 'submission_error' ) ) && is_wp_error( $error ) ) {

				$output .= '<div class="alert alert-warning"><p>' . sprintf( __( 'There was an error in the submission: %s', 'brewpress' ), '<strong>'. $error->get_error_message() .'</strong>' ) . '</p></div>';

			}

			// Get our form
			$output .= cmb2_get_metabox_form( 
				$cmb, 
				'brewpress', 
				array( 
					'save_button' => __( 'Create Batch', 'brewpress' ) 
				) 
			);
			
			$output .= '</div>';

			return $output;

	}
	
	/**
	 * Handles form submission on save. Redirects if save is successful, otherwise sets an error message as a cmb property
	 *
	 * @return void
	 */
	function handle_new_batch_form_submission() {

		// If no form submission, bail
		if ( empty( $_POST ) || ! isset( $_POST['submit-cmb'], $_POST['object_id'] ) )
			return false;

		if( $_POST['object_id'] != 'brewpress' )
			return;
		
		// Get CMB2 metabox object
		$cmb = $this->get_form();

		// Check security nonce
		if ( ! isset( $_POST[ $cmb->nonce() ] ) || ! wp_verify_nonce( $_POST[ $cmb->nonce() ], $cmb->nonce() ) ) 
			return $cmb->prop( 'submission_error', new WP_Error( 'security_fail', __( 'Security error. Please contact support.' ) ) );


		/**
		 * Fetch sanitized values
		 * Only returns items if there is a value
		 */
		$sanitized_values = $cmb->get_sanitized_values( $_POST );

		// Set our post data arguments
		$post_data = array(
			'post_author' 	=> $this->user_id, // Current user, or admin
			'post_status' 	=> 'publish',
			'post_content' 	=> '',
			'post_type'   	=> 'batch', // Only use first object_type in array
			'post_title'   	=> sanitize_text_field( $_POST['_brewpress_batch_name'] ), 
		);

		// Create the new post
		$new_submission_id = wp_insert_post( $post_data, true );
		
		// If we hit a snag, let the user know
		if ( is_wp_error( $new_submission_id ) ) {
			return $cmb->prop( 'submission_error', $new_submission_id );
		}

		$cmb->save_fields( $new_submission_id, 'post', $sanitized_values );
		
		do_action( 'brewpress_new_batch', $new_submission_id, 'batch' );

		/*
		 * Redirect back to the form page with a query variable with the new post ID.
		 * This will help double-submissions with browser refreshes
		 */
		wp_redirect( 
			esc_url_raw( 
				add_query_arg( 
					array(
					    'id' => $new_submission_id,
					    'created' => 'true',
					), home_url( '/edit-batch' ) ) 
			) 
		);
		exit;
	}
	
}

return new BrewPress_Frontend_New_Batch;