<?php if ( ! defined('ABSPATH') ) {
	exit;
}

get_header();

$post_id 	= get_the_ID();
$post 		= get_post( $post_id );
?>


	<div class="container brewpress">
		
		<?php do_action( 'brewpress_batch_output_row_1', $post ); ?>

		<?php do_action( 'brewpress_batch_output_row_2', $post ); ?>

		<?php do_action( 'brewpress_batch_output_row_3', $post ); ?>

		<?php do_action( 'brewpress_batch_output_row_4', $post ); ?>

		<?php do_action( 'brewpress_batch_output_row_5', $post ); ?>

		<?php do_action( 'brewpress_batch_output_row_6', $post ); ?>

		<?php do_action( 'brewpress_batch_output_row_7', $post ); ?>

	</div>

<?php get_footer(); ?>