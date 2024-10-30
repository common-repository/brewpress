<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( "admin_init", function(){
    if ( get_option( $opt_name = "brewpress_plugin_wizard_notice" ) ) {
        delete_option( $opt_name );
        add_action( "admin_notices", "brewpress_wizard_notice" );
    } return;
});

/**
  * Check if user has completed wizard already
  * if so then return true (don't show notice)
  *
  */
function brewpress_wizard_completed() {
    return false;
}

function brewpress_wizard_notice() {

    if ( brewpress_wizard_completed() ) return; // completed already
    ?>

    <div class="updated notice is-dismissible">
        <p><?php _e( 'Welcome to BrewPress! Please run the Setup Wizard to help you configure WordPress.' ); ?></p>
        <p><a href="admin.php?page=brewpress_plugin_wizard" class="button button-primary"><?php _e( 'Start the Wizard' ); ?></a> <a href="javascript:window.location.reload()" class="button"><?php _e( 'Cancel' ); ?></a></p>
    </div>

    <?php

}


register_activation_hook( BREWPRESS_PLUGIN_FILE, function() {
    update_option( "brewpress_plugin_wizard_notice", 1 );
});




// Add menu and pages to WordPress admin area
add_action('admin_menu', 'brewpress_create_wizard_page');

function brewpress_create_wizard_page() {


    add_submenu_page(
      	null, 
      	'BrewPress Wizard',
      	'BrewPress Wizard', 
      	'manage_options', 
      	'brewpress_plugin_wizard', 
      	'brewpress_wizard_page_callback'
    );

    add_submenu_page(
      	null, 
      	'BrewPress Wizard',
      	'BrewPress Wizard', 
      	'manage_options', 
      	'brewpress_plugin_wizard_complete', 
      	'brewpress_wizard_complete_page_callback'
    );
}

function brewpress_wizard_page_callback() {

	?>

	<div class="wrap">

		<h2><?php _e( 'BrewPress Setup Wizard', 'brewpress' ); ?></h2>

		<div class="updated notice">
			<p><?php _e( 'This is a simple wizard to help with the setup of your WordPress installation.', 'brewpress' ); ?><br>
			<?php _e( 'If you are using a fresh install of WordPress and only using it for the purpose of running BrewPress, we recommend you run each of the options below.', 'brewpress' ); ?></p>
		</div>

		<form id="brewpress-wizard" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
			<div class="tablenav top">
				<div class="alignleft actions">

					<div class="">
						<input type="checkbox" id="create_pages" checked="checked" value="1" name="create_pages" />
						<label for="create_pages"><?php _e( 'Create Required Pages', 'brewpress' ); ?></label>
					</div>

					<div class="">
						<input type="checkbox" id="set_home_page" checked="checked" value="1" name="set_home_page" />
						<label for="set_home_page"><?php _e( 'Set Dashboard as the home page (only works if the above is checked)', 'brewpress' ); ?></label>
					</div>

					<div class="">
						<input type="checkbox" id="create_menu" checked="checked" value="1" name="create_menu" />
						<label for="create_menu"><?php _e( 'Create Navigation Menu', 'brewpress' ); ?></label>
					</div>

					<div class="">
						<input type="checkbox" id="delete_dummy" checked="checked" value="1" name="delete_dummy" />
						<label for="delete_dummy"><?php _e( 'Delete WordPress default post', 'brewpress' ); ?></label>
					</div>

					<div class="">
						<input type="checkbox" id="set_permalinks" checked="checked" value="1" name="set_permalinks" />
						<label for="set_permalinks"><?php _e( 'Set permalinks to \'pretty permalinks\'', 'brewpress' ); ?></label>
					</div>

					<div class="">
						<input type="checkbox" id="copy_sse" checked="checked" value="1" name="copy_sse" />
						<label for="copy_sse"><?php _e( 'Copy the Server-sent Events file to the root folder', 'brewpress' ); ?></label>
					</div>

					<div class="">
						<?php wp_nonce_field( 'run_install', 'brewpress_wizard' ); ?>
						<input type="hidden" name="action" value="brewpress_run_install" />
						<input type="submit" class="button-primary" value="<?php _e( 'Run Wizard', 'brewpress' ); ?>"/>
					</div>
				</div>
			</div>
		</form>
	</div><!-- .wrap -->

	<?php

}


add_action( 'admin_post_brewpress_run_install', 'brewpress_run_install' );
function brewpress_run_install() {

	if ( empty( $_POST ) )
	   return;

    if ( ! isset( $_POST['brewpress_wizard'] ) || ! wp_verify_nonce( $_POST['brewpress_wizard'], 'run_install' ) )
       return;

   	global $wp_rewrite; 
	$home = null;
	$query_args = array();

	if( isset( $_POST['create_pages'] ) && $_POST['create_pages'] == true ) {

		$pages = array(
			'brewing' => array(
				'title'   => __( 'Brewing', 'brewpress' ),
				'content' => '[brewpress_brewing]',
				'menu' 		=> false,
			),
			'view-batches' => array(
				'title'   => __( 'View Batches', 'brewpress' ),
				'content' => '[brewpress_all_batches]',
				'menu' 		=> true,
				'url' 		=> 'view-batches',
			),
			'new-batch' => array(
				'title'   => __( 'New Batch', 'brewpress' ),
				'content' => '[brewpress_new_batch]',
				'menu' 		=> true,
				'url' 		=> 'new-batch',
			),
			'edit-batch' => array(
				'title'   	=> __( 'Edit Batch', 'brewpress' ),
				'content' 	=> '[brewpress_edit_batch]',
				'menu' 		=> false,
			),
			'settings' => array(
				'title'   => __( 'Settings', 'brewpress' ),
				'content' => '[brewpress_settings]',
				'menu' 		=> true,
				'url' 		=> 'settings',
			),
			'dashboard' => array(
				'title'   => __( 'Dashboard', 'brewpress' ),
				'content' => '[brewpress_dashboard]',
				'menu' 		=> true,
				'url' 		=> 'dashboard',
			),

		);

		foreach ( $pages as $key => $page ) {

			if( $existing = post_exists( $page['title'] ) !== 0 )
				continue;

			$page_id = wp_insert_post(
				array(
					'post_title'     => $page['title'],
					'post_content'   => $page['content'],
					'post_status'    => 'publish',
					'post_type'      => 'page',
					'comment_status' => 'closed'
				)
			);
			if( $page['title'] == 'Dashboard' )
				$home = $page_id;

			$pages[$key]['id'] = $page_id;
		}

		

	} else {
		$query_args['pages'] = 'false';
	}

	if( isset( $_POST['set_home_page'] ) && $_POST['set_home_page'] == true ) {

		update_option( "show_on_front", 'page' );
		if( $home )
			update_option( "page_on_front", $home ); 

	} else {
		$query_args['home'] = 'false';
	}


	if( isset( $_POST['create_menu'] ) && $_POST['create_menu'] == true ) {

		// Check if the menu exists
		$menu_name 		= 'BrewPress Menu';
		$menu_exists 	= wp_get_nav_menu_object( $menu_name );

		// If it doesn't exist, let's create it.
		if( ! $menu_exists ) {
		    $menu_id = wp_create_nav_menu( $menu_name );

		    foreach ($pages as $key => $page) {
		    	if( $page['menu'] && isset( $page['id'] ) ) {
			    	// Set up default menu items
				    wp_update_nav_menu_item( $menu_id, 0, array(
				    	'menu-item-object-id' => $page['id'],
					    'menu-item-object' => 'page',
					    'menu-item-status' => 'publish',
					    'menu-item-type' => 'post_type'
					) );
				}
		    }

		    if( ! has_nav_menu( 'brewpress-primary' ) ){
		        $locations = get_theme_mod('nav_menu_locations');
		        $locations['brewpress-primary'] = $menu_id;
		        set_theme_mod( 'nav_menu_locations', $locations );
		    }

		}

	} else {
		$query_args['menu'] = 'false';
	}


	if( isset( $_POST['delete_dummy'] ) && $_POST['delete_dummy'] == true ) {

		// Delete dummy post and comment.
    	wp_delete_post(1, TRUE);
    	wp_delete_comment(1);

    } else {
		$query_args['dummy'] = 'false';
	}
    

    if( isset( $_POST['set_permalinks'] ) && $_POST['set_permalinks'] == true ) {

	    //Write the rule
		$wp_rewrite->set_permalink_structure('/%postname%/'); 
		//Set the option
		update_option( "rewrite_rules", FALSE ); 
		//Flush the rules and tell it to write htaccess
		$wp_rewrite->flush_rules( true );

	} else {
		$query_args['permalinks'] = 'false';
	}


    if( isset( $_POST['copy_sse'] ) && $_POST['copy_sse'] == true ) {

	    $file = BREWPRESS_DIR . 'sse.php';
		$newfile = get_home_path() . 'sse.php';

		if (!copy($file, $newfile)) {
		    echo "failed to copy $file...\n";
		}

	} else {
		$query_args['sse'] = 'false';
	}

	$query_args['page'] = 'brewpress_plugin_wizard_complete';
	$url = add_query_arg(
		$query_args,
		admin_url()
	);
	wp_redirect( $url );
	exit;

}




function brewpress_wizard_complete_page_callback() {

	?>

	<div class="wrap">

		<h2><?php _e( 'BrewPress Install Wizard', 'brewpress' ); ?></h2>

		<div class="updated notice">
			<h4><?php _e( 'Awesome, the wizard has been run!', 'brewpress' ); ?></h4>
			<h4><a href="<?php echo home_url(); ?>"><?php _e( 'Start Brewing', 'brewpress' ); ?></a></h4>

		<?php if( isset( $_GET['pages'] ) && $_GET['pages'] == 'false' ) { ?>
			<p><?php _e( 'Required pages were not setup.', 'brewpress' ); ?></p>
		<?php } ?>
		<?php if( isset( $_GET['home'] ) && $_GET['home'] == 'false' ) { ?>
			<p><?php _e( 'Dashboard was not set as the home page.', 'brewpress' ); ?></p>
		<?php } ?>
		<?php if( isset( $_GET['menu'] ) && $_GET['menu'] == 'false' ) { ?>
			<p><?php _e( 'Menu was not set up.', 'brewpress' ); ?></p>
		<?php } ?>
		<?php if( isset( $_GET['dummy'] ) && $_GET['dummy'] == 'false' ) { ?>
			<p><?php _e( 'Dummy data was not removed.', 'brewpress' ); ?></p>
		<?php } ?>
		<?php if( isset( $_GET['permalinks'] ) && $_GET['permalinks'] == 'false' ) { ?>
			<p><?php _e( 'Permalinks were not set.', 'brewpress' ); ?></p>
		<?php } ?>
		<?php if( isset( $_GET['sse'] ) && $_GET['sse'] == 'false' ) { ?>
			<p><?php _e( 'Sse file was not copied to root directory.', 'brewpress' ); ?></p>
		<?php } ?>


	</div><!-- .wrap -->

	<?php

}
