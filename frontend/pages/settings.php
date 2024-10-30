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
class BrewPress_Frontend_Page_Settings {

	/**
	 * Metabox ID
	 * @var string
	 */
	public $metabox_id = 'brewpress';

	/**
	 * Metabox prefix
	 *
	 * @since 1.0.0
	 */
	private $pre = '_brewpress_';

	/**
	 * Shortcode ID
	 * @var string
	 */
	public $shortcode_id = 'brewpress_settings';

	public $user_id = '';
	

	public function __construct() {
		$this->hooks();
	}

	/**
	 * Hook in to actions & filters
	 *
	 * @since 1.0.0
	 */
	public function hooks() {

		add_action( 'init', array( $this, 'init' ) );
		add_action( 'cmb2_init', array( $this, 'init_form_fields' ) );
		add_action( 'cmb2_after_init', array( $this, 'handle_new_form_submission' ) );
		
		add_shortcode( $this->shortcode_id, array( $this, 'shortcode' ) );
	}

	public function init() {
		$this->user_id = get_current_user_id();
	}

	/**
	 * Gets the front-end-post-form cmb instance
	 *
	 * @return CMB2 object
	 */
	public function get_form() {
		return cmb2_get_metabox( $this->metabox_id, $this->user_id );
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
			$output .= '<div class="alert alert-warning">' . sprintf( __( 'There was an error: %s', 'brewpress' ), '<strong>'. $error->get_error_message() .'</strong>' ) . '</div>';
		}

		// If the post was submitted successfully, notify the user.
		if ( isset( $_GET['updated'] ) && ( $post = get_post( absint( $_GET['updated'] ) ) ) ) {
			$output .= '<div class="alert alert-success"><i class="far fa-check-circle"></i> &nbsp; Settings updated successfully.</div>';
		}

		$output .= apply_filters( 'brewpress_settings_before_form', $output );

		// Get our form
		$output .= cmb2_get_metabox_form( 
			$cmb, 
			$this->user_id, 
			array( 
				'save_button' => __( 'Update Settings', 'brewpress' ) 
			) 
		);

		$output .= '</div>';

		return $output;
	}
	

	/**
	 * Register the form and fields for our front-end submission form
	 */
	function init_form_fields() {

		$cmb = new_cmb2_box( array(
			'id'           => $this->metabox_id,
			'object_types' => array( 'user' ),
			'hookup'       => false,
			'save_fields'  => false,
		) );

		$cmb->add_field( array(
			'name'    		=> __( '', 'brewpress' ),
			'id'      		=> $this->pre . 'intro',
			'type'    		=> 'title',
			'classes' 		=> '',
		) );

		$cmb->add_field( array(
		    'name'    		=> __( 'Temperature Unit', 'brewpress' ),
		    'id' 			=> $this->pre . 'temp_unit',
		    'type' 			=> 'select',
		    'classes' 		=> 'form-group temp-unit',
		    'options' 		=> array(
		        'c' => __( 'Celcius', 'brewpress' ),
		        'f' => __( 'Fahrenheit', 'brewpress' ),
		    ),
		    'attributes'  	=> array(
				'class' => 'form-control',
			),
		));

		$cmb->add_field( array(
		    'name'    		=> __( 'Volume Unit', 'brewpress' ),
		    'id' 			=> $this->pre . 'volume_unit',
		    'type' 			=> 'select',
		    'classes' 		=> 'form-group volume-unit',
		    'options' 		=> array(
		        'metric' => __( 'Metric (litres)', 'brewpress' ),
		        'imperial' => __( 'Imperial (gallons)', 'brewpress' ),
		    ),
		    'attributes'  	=> array(
				'class' => 'form-control',
			),
		));

		$cmb->add_field( array(
		    'name'    		=> __( 'Heartbeat', 'brewpress' ),
		    'id' 			=> $this->pre . 'heartbeat',
		    'type' 			=> 'text',
		    'classes' 		=> 'form-group heartbeat',
		    'default' 		=> '2',
		    'desc' 			=> __( 'The time in seconds of the heartbeat. Default is 2.', 'brewpress' ),
		    'attributes'  	=> array(
				'class' => 'form-control',
			),
		));

		$cmb->add_field( array(
		    'name'    		=> __( 'Log Temps', 'brewpress' ),
		    'id' 			=> $this->pre . 'log_temps',
		    'type' 			=> 'text',
		    'classes' 		=> 'form-group log-temps',
		    'default' 		=> '30',
		    'desc' 			=> __( 'When a program is started, the time in seconds of temperature logging. Default is 30.', 'brewpress' ),
		    'attributes'  	=> array(
				'class' => 'form-control',
			),
		));

		$cmb->add_field( array(
		    'name'    		=> __( 'Debug Mode', 'brewpress' ),
		    'id' 			=> $this->pre . 'debug',
		    'type' 			=> 'select',
		    'desc' 			=> __( 'Raw program data will be printed to the bottom of the Brewing page.', 'brewpress' ),
		    'classes' 		=> 'form-group debug',
		    'options' 		=> array(
		        'off' => __( 'Off', 'brewpress' ),
		        'on' => __( 'On', 'brewpress' ),
		    ),
		    'attributes'  	=> array(
				'class' => 'form-control',
			),
		));

		$cmb->add_field( array(
		    'name'    		=> __( 'Testing Mode', 'brewpress' ),
		    'id' 			=> $this->pre . 'testing',
		    'type' 			=> 'select',
		    'desc' 			=> __( 'Brewpress will setup dummy sensors for when you\'re not connected to a RaspberryPi.', 'brewpress' ),
		    'classes' 		=> 'form-group testing',
		    'options' 		=> array(
		        'off' => __( 'Off', 'brewpress' ),
		        'on' => __( 'On', 'brewpress' ),
		    ),
		    'attributes'  	=> array(
				'class' => 'form-control',
			),
		));

		do_action( 'brewpress_settings_before_elements', $cmb );

		$element = $cmb->add_field( array(
			'name'   		=> __( 'Heating Elements', 'brewpress' ),
		    'id' 			=> $this->pre . 'elements',
		    'type' 			=> 'group',
		    'desc' 			=> '',
		    'options' 		=> array(
		        'group_title' => esc_html__('Element {#}', 'brewpress'),
		        'add_button' => esc_html__('Add New Element', 'brewpress'),
		        'remove_button' => esc_html__('Remove Element', 'brewpress'),
		        'sortable' => true,
		    ),

		));

		$cmb->add_group_field( $element, array(
		    'name'    		=> __( 'Name', 'brewpress' ),
			'id'      		=> 'name',
			'type'    		=> 'text',
			'desc'    		=> __( 'Give this element a unique name.', 'brewpress' ),
			'classes' 		=> 'form-group name col-md',
			'attributes'  	=> array(
				'class' => 'form-control',
			),
			'before_row' 	=> '<div class="row">',
		));

		do_action( 'brewpress_settings_after_element_name', $cmb, $element );

		$cmb->add_group_field( $element, array(
		    'name'    		=> __( 'Output GPIO', 'brewpress' ),
			'id'      		=> 'gpio',
			'type'    		=> 'text',
			'desc'    		=> __( 'The GPIO for this element (not the physical pin number).', 'brewpress' ),
			'classes' 		=> 'form-group gpio col-md',
			'attributes'  	=> array(
				'class' => 'form-control',
				'type' => 'number',
			),
		));
		$cmb->add_group_field( $element, array(
		    'name'    		=> __( 'Sensor', 'brewpress' ),
			'id'      		=> 'sensor',
			'type'    		=> 'select',
			'desc'    		=> __( 'The sensor that is used to control the element.', 'brewpress' ),
			'show_option_none' => true,
			'options_cb'    => 'brewpress_get_pi_sensors',
			'classes' 		=> 'form-group sensor col-md',
			'attributes'  	=> array(
				'class' => 'form-control',
			),
			'after_row' 	=> apply_filters( 'brewpress_settings_elements_last_div', '</div>' ),
		));

		do_action( 'brewpress_settings_after_element_sensor', $cmb, $element );

		$cmb->add_group_field( $element, array(
		    'name'    		=> __( 'Mode', 'brewpress' ),
			'id'      		=> 'mode',
			'type'    		=> 'select',
			'desc'    		=> __( 'The mode that this element will run on.', 'brewpress' ),
			'classes' 		=> 'form-group mode col-md',
			'options'  		=> apply_filters( 'brewpress_kettle_modes', array(
				'hysteresis' => __( 'Hysteresis', 'brewpress' ),
			) ),
			'attributes'  	=> array(
				'class' => 'form-control',
			),
			'before_row' 	=> '<div class="row">',
			'after_row' 	=> '</div>',
			
		));

		$cmb->add_group_field( $element, array(
			'name'       	=> __( 'Turn On', 'brewpress' ),
			'desc'       	=> __( 'Switch element on when temperature is x degrees from set point.', 'brewpress' ),
			'id'         	=> 'hysteresis_on',
			'type'       	=> 'text',
			'default'       => '-0.6',
			'classes' 		=> 'form-group hysteresis col-md',
			'attributes' => array(
				'data-conditional-id' => 'mode',
				'data-conditional-value' => 'hysteresis',
				'type' => 'number',
				'step' => '0.01',
				'class' => 'form-control',
			),
			'before_row' 	=> '<div class="row">',
			
		) );
		$cmb->add_group_field( $element, array(
			'name'       	=> __( 'Turn Off', 'brewpress' ),
			'id'         	=> 'hysteresis_off',
			'desc'       	=> __( 'Switch element off when temperature is x degrees from set point.', 'brewpress' ),
			'type'       	=> 'text',
			'default'       => '0',
			'classes' 		=> 'form-group hysteresis col-md',
			'attributes' => array(
				'data-conditional-id'    => 'mode',
				'data-conditional-value' => 'hysteresis',
				'type' => 'number',
				'step' => '0.01',
				'class' => 'form-control',
			),
			'after_row' 	=> '</div>',
		) );


		$pump = $cmb->add_field( array(
			'name'   		=> __( 'Pumps', 'brewpress' ),
		    'id' 			=> $this->pre . 'pumps',
		    'type' 			=> 'group',
		    'desc' 			=> '',
		    'options' 		=> array(
		        'group_title' => esc_html__('Pump {#}', 'brewpress'),
		        'add_button' => esc_html__('Add New Pump', 'brewpress'),
		        'remove_button' => esc_html__('Remove Pump', 'brewpress'),
		        'sortable' => true,
		    ),
		));

		$cmb->add_group_field( $pump, array(
		    'name'    		=> __( 'Name', 'brewpress' ),
			'id'      		=> 'name',
			'type'    		=> 'text',
			'desc'    		=> 'Give this pump a unique name.',
			'classes' 		=> 'form-group name col-md',
			'attributes'  	=> array(
				'class' => 'form-control',
			),
			'before_row' 	=> '<div class="row">',
		));

		do_action( 'brewpress_settings_after_pump_name', $cmb, $pump );

		$cmb->add_group_field( $pump, array(
		    'name'    		=> __( 'Output GPIO', 'brewpress' ),
			'id'      		=> 'gpio',
			'desc'    		=> __( 'The GPIO for this pump (not the physical pin number).', 'brewpress' ),
			'type'    		=> 'text',
			'classes' 		=> 'form-group gpio col-md',
			'attributes'  	=> array(
				'class' => 'form-control',
				'type' => 'number',
			),
			'after_row' 	=> apply_filters( 'brewpress_settings_pumps_last_div', '</div>' ),
		));

		do_action( 'brewpress_settings_after_pump_gpio', $cmb, $pump );

	}


	/**
	 * Handles form submission on save. Redirects if save is successful, otherwise sets an error message as a cmb property
	 *
	 * @return void
	 */
	function handle_new_form_submission() {


		// If no form submission, bail
		if ( empty( $_POST ) || ! isset( $_POST['submit-cmb'], $_POST['object_id'] ) )
			return false;

		if( $_POST['object_id'] != $this->user_id )
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

		// If we hit a snag, let the user know
		// if ( is_wp_error( $new_submission_id ) ) {
		// 	return $cmb->prop( 'submission_error', $new_submission_id );
		// }

		do_action( 'brewpress_settings_before_form_save', $sanitized_values, $this->user_id, $cmb );

		$cmb->save_fields( $this->user_id, 'user', $sanitized_values );
		
		/*
		 * Redirect back to the form page with a query variable with the new post ID.
		 * This will help double-submissions with browser refreshes
		 */
		wp_redirect( esc_url_raw( add_query_arg( 'updated', 'true' ) ) );
		exit;

	}


}

return new BrewPress_Frontend_Page_Settings;