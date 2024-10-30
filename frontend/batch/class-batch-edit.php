<?php
/**
 * @link https://github.com/CMB2/CMB2-Snippet-Library/blob/master/front-end/cmb2-front-end-editor.php
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * BrewPress_Setup Class.
 */
class BrewPress_Frontend_Edit_Batch {

	/**
	 * Post type
	 * @var string
	 */
	public $post_type = 'batch';

	/**
	 * Post ID that we are editing
	 * @var string
	 */
	public $post_id = null;

	/**
	 * Metabox ID
	 * @var string
	 */
	public $metabox_id = 'batch';

	/**
	 * Metabox prefix
	 *
	 * @since 1.0.0
	 */
	private $pre = '_brewpress_batch_';

	/**
	 * Shortcode ID
	 * @var string
	 */
	public $shortcode_id = 'brewpress_edit_batch';
	

	public function __construct() {
		
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'cmb2_after_init', array( $this, 'handle_new_batch_form_submission' ) );

		add_shortcode( $this->shortcode_id, array( $this, 'batch_form_shortcode' ) );

		add_action( 'brewpress_edit_page_before', array( $this, 'edit_page_message' ), 10, 2 );
		add_action( 'brewpress_edit_page_before', array( $this, 'extra_buttons' ), 10, 2 );

		add_action( 'brewpress_edit_page_top', array( $this, 'info' ), 10, 2 );

	}

	public function init() {

		require_once BREWPRESS_DIR . 'frontend/batch/class-batch-form.php';

		$this->batch_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : null;
		$this->program 	= brewpress_get_batch_program( $this->batch_id );

	}

	/**
	 * Gets the front-end-post-form cmb instance
	 *
	 * @return CMB2 object
	 */
	public function get_form() {
		return cmb2_get_metabox( $this->metabox_id, $this->batch_id );
	}
	
	/**
	 * Handle the cmb_frontend_form shortcode
	 *
	 * @param  array  $atts Array of shortcode attributes
	 * @return string       Form html
	 */
	public function batch_form_shortcode( $atts = array() ) {
		
		// Get CMB2 metabox object
		$cmb = $this->get_form();

		do_action( 'brewpress_edit_page_before', $cmb, $this->batch_id );

		// Initiate our output variable
		$output = '<div class="brewpress">';

		do_action( 'brewpress_edit_page_top', $cmb, $this->batch_id );

		// Get our form
		$output .= cmb2_get_metabox_form( 
			$cmb, 
			$this->batch_id, 
			array( 
				'save_button' => __( 'Update Batch', 'brewpress' ) 
			) 
		);

		do_action( 'brewpress_edit_page_bottom', $cmb, $this->batch_id );

		$output .= '</div>';

		return $output;

		
		
	}



    public function info() {
        ob_start();

        if( ! $this->program )
            return;
        ?>
        
        <div class="row program-info">

            <div class="col-sm-12">
                <div class="alert alert-info">
                    <?php printf( 
                            __( 'Program was started at %1s on %2s', 'brewpress' ), 
                            date_i18n( get_option( 'time_format' ), $this->program['start'] ), 
                            date_i18n( get_option( 'date_format' ), $this->program['start'] ) 
                    ); 

                    if( isset( $this->program['end'] ) && $this->program['end'] != '' ) {
                        echo '<br>';
                        printf( 
                            __( 'Program finished at %1s on %2s', 'brewpress' ), 
                            date_i18n( get_option( 'time_format' ), $this->program['end'] ), 
                            date_i18n( get_option( 'date_format' ), $this->program['end'] ) 
                        ); 
                    }
                    	echo '<br>';

                    _e( '<strong>Warning!</strong> Updating this batch will remove all program data and all temperature logs!', 'brewpress' ); 
                    ?>
                </div>
            </div>

        </div>

        <?php
        $content = ob_get_contents();
        ob_end_clean();
        echo $content;

    }


	/**
	 * Show some action buttons
	 *
	 * @return void
	 */
	public function extra_buttons() {
		ob_start();

		?>
			<div class="edit-btns btn-wrap text-right">
				
				<?php if( $this->program ) { ?>
					<a class="btn btn-sm btn-info view" target="_blank" href="<?php echo esc_url( get_the_permalink( $this->batch_id ) ); ?>"><?php _e( 'View', 'brewpress' ); ?></a>
				<?php } ?>

				<a class="btn btn-sm btn-success" href="<?php echo esc_url( brewpress_get_batch_brew_url( $this->batch_id ) ); ?>"><i class="fa fa-beer"></i> <?php _e( 'Brew', 'brewpress' ); ?></a>

			</div>

		<?php
		$content = ob_get_contents();
   		ob_end_clean();

   		echo $content;

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

		if( $_POST['object_id'] != $this->batch_id )
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

		$cmb->save_fields( $this->batch_id, 'post', $sanitized_values );
		
		wp_update_post( array( 'ID' => $this->batch_id, 'post_title' => sanitize_text_field( $_POST['_brewpress_batch_name'] ) ) );

		// delete any program if we update
		update_post_meta( $this->batch_id, '_brewpress_program', '' );

		do_action( 'brewpress_edited_batch', $this->batch_id );

		/*
		 * Redirect back to the form page with a query variable with the new post ID.
		 * This will help double-submissions with browser refreshes
		 */
		wp_redirect( esc_url_raw( 
			add_query_arg( 
				array( 
					'updated' => 'true',
					'created' => false,
				)
			) 
		) );
		exit;
	}



	/**
	 * Handles the message on this page
	 *
	 */
	function edit_page_message( $cmb, $batch_id ) {

		// Get any submission errors
		if ( ( $error = $cmb->prop( 'submission_error' ) ) && is_wp_error( $error ) ) {
			do_action( 'brewpress_message', sprintf( __( 'There was an error in the submission: %s', 'brewpress' ), '<strong>'. $error->get_error_message() .'</strong>' ), 'danger' );
		}

		// If the post was submitted successfully, notify the user.
		if ( isset( $_GET['created'] ) && ( $post = get_post( absint( $_GET['id'] ) ) ) ) {
			do_action( 'brewpress_message', __( ' Batch created successfully', 'brewpress' ) );
		}
		// If the post was submitted successfully, notify the user.
		if ( isset( $_GET['updated'] ) && ( $post = get_post( absint( $_GET['updated'] ) ) ) ) {
			do_action( 'brewpress_message', __( ' Batch updated successfully', 'brewpress' ) );
		}

	}


	
}

return new BrewPress_Frontend_Edit_Batch;