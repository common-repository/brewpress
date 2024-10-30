<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}



class BrewPress_Frontend_Batch_View_All {

	/**
	 * Shortcode ID
	 * @var string
	 */
	public $shortcode_id = 'brewpress_all_batches';

	public $user_id = '';
	

	public function __construct() {

		add_action( 'init', array( $this, 'init' ) );

		add_shortcode( $this->shortcode_id, array( $this, 'shortcode' ) );

	}


	public function init() {
		$this->user_id = get_current_user_id();
	}

	/**
	 * Handle the output
	 *
	 * @param  array  $atts Array of shortcode attributes
	 * @return string       Form html
	 */
	public function shortcode( $atts = array() ) {

		// Initiate our output variable
		$output = '<div class="brewpress">';

		$output .= $this->table();

		$output .= '</div>';

		return $output;
	}



	public function table() {

		ob_start();

		?>

			<div class="filter-table view-all">

				<?php do_action( 'brewpress_view_all_message' ); ?>

				<div class="input-group search-filter">
					<div class="input-group-prepend">
			          	<div class="input-group-text"><i class="fa fa-search"></i></div>
			        </div>
					<input type="text" class="form-control" id="batch-table-filter" data-action="filter" data-filters=".batch-table" placeholder="<?php _e( 'Start typing to filter batches', 'brewpress' ); ?>" />
					
				</div>

				<div class="table-responsive">
					<table class="table table-hover batch-table">
						<thead class="thead-dark">
							<?php echo $this->table_header_row(); ?>
						</thead>
						<tbody>

						<?php 
						$batches = brewpress_get_batches();
						$count = 0;
						if( $batches ) {
							foreach ( $batches as $index => $id ) { 
								echo $this->item_row( $id );
							$count++;
							} 
						}
							
						if( $count == 0 ) { ?>
							<tr class="batch-row">
								<td colspan="7"><?php _e( 'No batches', 'brewpress' ); ?></td>
							</tr>
						<?php }	?>

						</tbody>
					</table>
				</div>

			<?php do_action( 'brewpress_after_batches_table' ); ?>

			</div>

		<?php
		$content = ob_get_contents();
   		ob_end_clean();

   		return $content;

	}

	public function table_header_row() {
		ob_start();

		?>

		<tr>
			<th><?php _e( 'Name', 'brewpress' ); ?></th>
			<th><?php _e( 'Created', 'brewpress' ); ?></th>
			<th><?php _e( 'Brewed', 'brewpress' ); ?></th>
			<th><?php _e( 'Volume', 'brewpress' ); ?></th>
			<th></th>
		</tr>

		<?php
		$content = ob_get_contents();
   		ob_end_clean();

   		return $content;

	}

	public function item_row( $id ) {

		ob_start();

		?>

		<tr class="batch-row" data-id="<?php echo esc_attr( $id ); ?>">

			<td class="name">
				<span class="title"><a href="<?php echo get_the_permalink( $id ); ?>"><?php echo get_the_title( $id ); ?></a></span>
				<span class="style"><?php echo esc_html( brewpress_get_batch_style( $id ) ); ?></span>
			</td>
			
			<td class="date">
				<span><?php echo esc_html( brewpress_get_batch_created_date( $id ) ); ?></span>
			</td>
			<td class="date">
				<span><?php echo esc_html( brewpress_get_batch_brewed_date( $id ) ); ?></span>
			</td>
			<td class="volume">
				<span><?php echo wp_kses_post( brewpress_get_batch_volume( $id ) ); ?></span>
			</td>

			<td class="no-search">

			  	<a class="btn btn-sm btn-info" href="<?php echo esc_url( add_query_arg( 'id', $id, home_url( '/edit-batch') ) ); ?>" data-toggle="tooltip" data-placement="bottom" title="<?php _e( 'Edit batch', 'brewpress' ); ?>" type="button"><i class="fa fa-pencil-alt"></i></a>

			  	<a class="btn btn-sm btn-primary" id="duplicate-item" href="<?php echo wp_nonce_url( admin_url( 'admin.php?action=brewpress_duplicate&post=' . $id ), 'duplicate', 'security' ); ?>" data-toggle="tooltip" data-placement="bottom" title="<?php _e( 'Duplicate batch', 'brewpress' ); ?>" type="button"><i class="fa fa-clone"></i></a>

			  	<a class="btn btn-sm btn-danger" id="delete-item" href="<?php echo get_delete_post_link( $id ); ?>" data-toggle="tooltip" data-placement="bottom" title="<?php _e( 'Delete batch', 'brewpress' ); ?>" ><i class="fa fa-trash"></i></a>

			  	<a class="btn btn-sm btn-success" href="<?php echo esc_url( brewpress_get_batch_brew_url( $id ) ); ?>"><i class="fa fa-beer"></i> <?php _e( 'Brew', 'brewpress' ); ?></a>

			</td>

		</tr>

		<?php
		$content = ob_get_contents();
   		ob_end_clean();

   		return $content;

	}


}

return new BrewPress_Frontend_Batch_View_All;