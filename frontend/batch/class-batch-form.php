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
class BrewPress_Frontend_Batch_Form {

	/**
	 * Metabox prefix
	 *
	 * @since 1.0.0
	 */
	private $pre = '_brewpress_batch_';

	public $date_format = '';
	

	public function __construct() {
		$this->hooks();
	}

	/**
	 * Hook in to actions & filters
	 *
	 * @since 1.0.0
	 */
	public function hooks() {
		add_action( 'cmb2_init', array( $this, 'init_form_fields' ) );
	}


	/**
	 * Register the form and fields for our front-end submission form
	 */
	function init_form_fields() {

		$main = array();

		$cmb = new_cmb2_box( array(
			'id'           => 'batch',
			'object_types' => array( 'batch' ),
			'hookup'       => false,
			'save_fields'  => false,
		) );
		
		$main[] = array(
			'name'    	=> __( 'Name', 'brewpress' ),
			'id'      	=> $this->pre . 'name',
			'type'    	=> 'text',
			'before_row' 	=> '<div class="row">',
			'classes' 	=> 'form-group name col-md',
			'attributes'  => array(
				'class' => 'form-control',
			),
		);
		$main[] = array(
			'name'    	=> __( 'Style', 'brewpress' ),
			'id'      	=> $this->pre . 'style',
			'type'    	=> 'select',
			'classes' 	=> 'form-group style col-md',
			'attributes'  => array(
				'class' => 'form-control',
			),
			'options_cb' => 'brewpress_get_bjcp_styles',
			'show_option_none' => true,
		);
		$main[] = array(
			'name'    	=> __( 'Volume', 'brewpress' ),
			'id'      	=> $this->pre . 'volume',
			'type'    	=> 'text',
			'before_field' => '<div class="input-group">',
			'after_field' => 'brewpress_get_volume_unit_field',
			'classes' 	=> 'form-group volume col-md',
			'after_row' 	=> '</div>',
			'attributes'  => array(
				'class' => 'form-control',
				'type' => 'number',
				'step' => '0.01',
			),
		);

		$main = apply_filters( 'brewpress_batch_form_main_fields', $main );
		
		if( ! empty( $main ) && is_array( $main ) ) {
			foreach ($main as $key => $value) {
				$cmb->add_field( $value );
			}
		}
		

		$group = $cmb->add_field( array(
			'name'   => '',
		    'id' => $this->pre . 'steps',
		    'type' => 'group',
		    'options' => array(
		        'group_title' => esc_html__('Step {#}', 'brewpress'),
		        'add_button' => esc_html__('Add Step', 'brewpress'),
		        'remove_button' => esc_html__('Remove Step', 'brewpress'),
		        'sortable' => true,
		    ),
		));

		$steps[] = array(
		    'name'    => __( 'Temperature', 'brewpress' ),
			'id'      => 'temperature',
			'type'    => 'text',
			'desc'    => __( 'The target temperature to hold this step at.', 'brewpress' ),
			'before_row' 	=> '<div class="row">',
			'classes' 	=> 'form-group temperature col-md',
			'attributes'  => array(
				'class' => 'form-control',
				'type' => 'number',
				'step' => '0.01',
				'required' => 'required',
			),
		);
		$steps[] = array(
		    'name'    => __( 'Time', 'brewpress' ),
			'id'      => 'time',
			'type'    => 'text',
			'desc'    => __( 'The time in minutes to hold this step at.', 'brewpress' ),
			'classes' 	=> 'form-group time col-md',
			'attributes'  => array(
				'class' => 'form-control',
				'type' => 'number',
				'step' => '0.01',
				'required' => 'required',
			),
		);
		$steps[] = array(
		    'name'    => __( 'Element', 'brewpress' ),
			'id'      => 'element',
			'type'    => 'select',
			'desc'    => __( 'The element to control.', 'brewpress' ),
			'options_cb'    => 'brewpress_get_elements_for_dropdown',
			'classes' 	=> 'form-group element col-md',
			'after_row' 	=> '</div>',
			'attributes'  => array(
				'class' => 'form-control',
				'required' => 'required',
			),
		);

		$steps = apply_filters( 'brewpress_batch_form_steps_fields', $steps );
		
		if( ! empty( $steps ) && is_array( $steps ) ) {
			foreach ($steps as $key => $value) {
				$cmb->add_group_field( $group, $value );
			}
		}

		
	}

	


}

return new BrewPress_Frontend_Batch_Form;