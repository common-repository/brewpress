<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'BrewPress_Menus', false ) ) :

/**
 * BrewPress_Menus Class.
 */
class BrewPress_Menus {

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'admin_menu' ), 9 );
	}

	/**
	 * Add menu items.
	 */
	public function admin_menu() {

		add_menu_page( 
			__( 'BrewPress', 'brewpress' ), // page title
			__( 'BrewPress', 'brewpress' ), // menu title
			'manage_options', // capability
			'edit.php?post_type=batch', // menu slug
			null,  // callback
			'dashicons-editor-bold', //icon url
			55 // position
		);

		$submenu = array();
		
		// $submenu['batch'] = array(
		// 	'brewpress', // parent slug
		// 	'Batches', // page title
		// 	'Batches', // menu title
		// 	'manage_options', // capability
		// 	'edit.php?post_type=batch', // menu slug
		// 	null // callback
		// );

		if( $submenu ) {
			foreach ($submenu as $key => $value) {
				add_submenu_page( 
					$value[0], 
					$value[1],  
					$value[2], 
					$value[3], 
					$value[4], 
					$value[5] 
				);
			}
		}
		
	}


}

endif;

return new BrewPress_Menus();
