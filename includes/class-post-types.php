<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * The main class
 *
 * @since 1.0.0
 */
class BrewPress_Post_Types {

	/**
	 * Main constructor
	 *
	 * @since 1.0.0
	 *
	 */
	public function __construct() {

		// post types
		add_action( 'init', array( $this, 'register_batch' ) );

	}



	/**
	 * Registers and sets up the custom post types
	 *
	 * @since 1.0
	 * @return void
	 */
	public function register_batch() {

		$labels = apply_filters( 'brewpress_batch_labels', array(
			'name'                  => _x( '%2$s', 'post type general name', 'brewpress' ),
			'singular_name'         => _x( '%1$s', 'post type singular name', 'brewpress' ),
			'add_new'               => __( 'New %1s', 'brewpress' ),
			'add_new_item'          => __( 'Add New %1$s', 'brewpress' ),
			'edit_item'             => __( 'Edit %1$s', 'brewpress' ),
			'new_item'              => __( 'New %1$s', 'brewpress' ),
			'all_items'             => __( '%2$s', 'brewpress' ),
			'view_item'             => __( 'View %1$s', 'brewpress' ),
			'search_items'          => __( 'Search %2$s', 'brewpress' ),
			'not_found'             => __( 'No %2$s found', 'brewpress' ),
			'not_found_in_trash'    => __( 'No %2$s found in Trash', 'brewpress' ),
			'parent_item_colon'     => '',
			'menu_name'             => _x( '%2$s', 'admin menu', 'brewpress' ),
			'filter_items_list'     => __( 'Filter %2$s list', 'brewpress' ),
			'items_list_navigation' => __( '%2$s list navigation', 'brewpress' ),
			'items_list'            => __( '%2$s list', 'brewpress' ),
		) );

		foreach ( $labels as $key => $value ) {
			$labels[ $key ] = sprintf( $value, 'Batch', 'Batches' );
		}

		$args = array(
			'labels'             	=> $labels,
			'public'             	=> true,
			'show_in_rest' 			=> false,
			'exclude_from_search'	=> true,
			'publicly_queryable' 	=> true,
			'show_ui'            	=> true,
			'show_in_menu'       	=> false, // we are using custom add_submenu_page
			'query_var'          	=> true,
			'capability_type'    	=> 'post',
			'map_meta_cap' 		 	=> true,
			'has_archive'        	=> true,
			'hierarchical'       	=> false,
			'supports'           	=> array( 'title', 'revisions', 'author' ),
		);

		register_post_type( 'batch', apply_filters( 'brewpress_batch_post_type_args', $args ) );

	}


}

return new BrewPress_Post_Types();